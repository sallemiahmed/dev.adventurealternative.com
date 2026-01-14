<?php
/**
 * Adventure Alternative - Custom Stripe API for Balance Payments
 *
 * Bypasses WooCommerce Stripe plugin issues with order-pay pages.
 * Handles 3DS authentication properly for subsequent payments.
 *
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AA_Stripe_Custom_API {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp', [$this, 'maybe_replace_payment_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Allow processing/completed orders to be paid if they have a balance
        add_filter('woocommerce_valid_order_statuses_for_payment', [$this, 'allow_partial_payment_statuses'], 100, 2);
    }

    /**
     * Allow processing/completed orders to be paid if they have a balance
     */
    public function allow_partial_payment_statuses($statuses, $order) {
        if (!$order) {
            return $statuses;
        }

        // Check if this order has a balance due
        $balance = $this->get_balance_due($order);

        if ($balance > 0) {
            // Add processing and completed to valid statuses
            $statuses = array_unique(array_merge($statuses, ['processing', 'completed', 'on-hold']));
        }

        return $statuses;
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('aa/v1', '/create-payment-intent', [
            'methods' => 'POST',
            'callback' => [$this, 'create_payment_intent'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('aa/v1', '/confirm-payment', [
            'methods' => 'POST',
            'callback' => [$this, 'confirm_payment'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('aa/v1', '/get-order-details', [
            'methods' => 'POST',
            'callback' => [$this, 'get_order_details'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get Stripe secret key from WooCommerce settings
     */
    private function get_stripe_secret_key() {
        $settings = get_option('woocommerce_stripe_settings', []);
        $testmode = ($settings['testmode'] ?? 'no') === 'yes';

        if ($testmode) {
            return $settings['test_secret_key'] ?? '';
        }
        return $settings['secret_key'] ?? '';
    }

    /**
     * Get Stripe publishable key from WooCommerce settings
     */
    public function get_stripe_publishable_key() {
        $settings = get_option('woocommerce_stripe_settings', []);
        $testmode = ($settings['testmode'] ?? 'no') === 'yes';

        if ($testmode) {
            return $settings['test_publishable_key'] ?? '';
        }
        return $settings['publishable_key'] ?? '';
    }

    /**
     * Validate order and return order object
     */
    private function validate_order($order_id, $order_key) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', 'Order not found', ['status' => 404]);
        }

        if (!$order->key_is_valid($order_key)) {
            return new WP_Error('invalid_key', 'Invalid order key', ['status' => 403]);
        }

        return $order;
    }

    /**
     * Calculate balance due for an order
     * Uses LINE ITEMS total (full trip price), not order total (which may be deposit only)
     */
    private function get_balance_due($order) {
        // Calculate total from LINE ITEMS (full trip price)
        $total = 0;
        foreach ($order->get_items() as $item) {
            $total += floatval($item->get_total());
        }

        // If no line items, fall back to order total
        if ($total <= 0) {
            $total = floatval($order->get_total());
        }

        // Get paid amount from transactions
        $transactions = get_post_meta($order->get_id(), 'aa_order_transactions', true);
        $paid = 0;

        if (is_array($transactions) && !empty($transactions)) {
            foreach ($transactions as $txn) {
                if (!empty($txn['amount'])) {
                    $paid += floatval($txn['amount']);
                }
            }
        }

        // Also check _aa_total_paid meta as fallback
        $meta_paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
        if ($meta_paid > $paid) {
            $paid = $meta_paid;
        }

        return max(0, $total - $paid);
    }

    /**
     * REST API: Get order details
     */
    public function get_order_details(WP_REST_Request $request) {
        $order_id = absint($request->get_param('order_id'));
        $order_key = sanitize_text_field($request->get_param('order_key'));

        $order = $this->validate_order($order_id, $order_key);
        if (is_wp_error($order)) {
            return $order;
        }

        $balance = $this->get_balance_due($order);

        return rest_ensure_response([
            'success' => true,
            'order_id' => $order_id,
            'total' => floatval($order->get_total()),
            'currency' => strtolower($order->get_currency()),
            'balance_due' => $balance,
            'formatted_balance' => strip_tags(wc_price($balance)),
        ]);
    }

    /**
     * REST API: Create PaymentIntent
     */
    public function create_payment_intent(WP_REST_Request $request) {
        $order_id = absint($request->get_param('order_id'));
        $order_key = sanitize_text_field($request->get_param('order_key'));
        $custom_amount = floatval($request->get_param('amount') ?: 0);

        // Validate order
        $order = $this->validate_order($order_id, $order_key);
        if (is_wp_error($order)) {
            return $order;
        }

        // Calculate balance
        $balance_due = $this->get_balance_due($order);

        if ($balance_due <= 0) {
            return new WP_Error('no_balance', 'No balance due on this order', ['status' => 400]);
        }

        // Determine payment amount
        if ($custom_amount > 0 && $custom_amount <= $balance_due) {
            $amount = $custom_amount;
        } else {
            $amount = $balance_due;
        }

        // Convert to cents
        $amount_cents = round($amount * 100);
        $currency = strtolower($order->get_currency());

        // Get Stripe secret key
        $secret_key = $this->get_stripe_secret_key();
        if (empty($secret_key)) {
            return new WP_Error('stripe_error', 'Stripe is not configured', ['status' => 500]);
        }

        // Create PaymentIntent via Stripe API
        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Stripe-Version' => '2024-06-20',
            ],
            'body' => [
                'amount' => $amount_cents,
                'currency' => $currency,
                'automatic_payment_methods[enabled]' => 'false',
                'payment_method_types[]' => 'card',
                'metadata[order_id]' => $order_id,
                'metadata[order_key]' => $order_key,
                'metadata[source]' => 'aa_custom_api',
                'description' => sprintf('Balance payment for Order #%d', $order_id),
                'receipt_email' => $order->get_billing_email(),
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            error_log('[AA Stripe API] PaymentIntent creation failed: ' . $response->get_error_message());
            return new WP_Error('stripe_error', 'Failed to connect to Stripe', ['status' => 500]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code !== 200 || empty($body['client_secret'])) {
            $error_msg = $body['error']['message'] ?? 'Unknown Stripe error';
            error_log('[AA Stripe API] Stripe error: ' . $error_msg);
            return new WP_Error('stripe_error', $error_msg, ['status' => 400]);
        }

        // Store PaymentIntent ID on order for later verification
        $order->update_meta_data('_aa_pending_payment_intent', $body['id']);
        $order->update_meta_data('_aa_pending_payment_amount', $amount);
        $order->save();

        error_log(sprintf('[AA Stripe API] Created PaymentIntent %s for Order #%d, amount: %s %s',
            $body['id'], $order_id, $amount, strtoupper($currency)));

        return rest_ensure_response([
            'success' => true,
            'client_secret' => $body['client_secret'],
            'payment_intent_id' => $body['id'],
            'amount' => $amount,
            'amount_cents' => $amount_cents,
            'currency' => $currency,
            'formatted_amount' => strip_tags(wc_price($amount)),
        ]);
    }

    /**
     * REST API: Confirm payment after 3DS
     */
    public function confirm_payment(WP_REST_Request $request) {
        $order_id = absint($request->get_param('order_id'));
        $order_key = sanitize_text_field($request->get_param('order_key'));
        $payment_intent_id = sanitize_text_field($request->get_param('payment_intent_id'));

        // Validate order
        $order = $this->validate_order($order_id, $order_key);
        if (is_wp_error($order)) {
            return $order;
        }

        // Verify PaymentIntent ID matches what we stored
        $stored_intent = $order->get_meta('_aa_pending_payment_intent');
        if ($stored_intent !== $payment_intent_id) {
            return new WP_Error('intent_mismatch', 'Payment intent does not match', ['status' => 400]);
        }

        // Get PaymentIntent from Stripe to verify status
        $secret_key = $this->get_stripe_secret_key();

        $response = wp_remote_get('https://api.stripe.com/v1/payment_intents/' . $payment_intent_id, [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Stripe-Version' => '2024-06-20',
            ],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('stripe_error', 'Failed to verify payment', ['status' => 500]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['status']) || $body['status'] !== 'succeeded') {
            $status = $body['status'] ?? 'unknown';
            return new WP_Error('payment_failed', "Payment not successful. Status: {$status}", ['status' => 400]);
        }

        // Payment succeeded! Record the transaction
        $amount = floatval($body['amount']) / 100;
        $charge_id = $body['latest_charge'] ?? $payment_intent_id;

        // Use AA_Order_Transactions class to add transaction
        if (class_exists('AA_Order_Transactions')) {
            $transactions = new AA_Order_Transactions($order);
            $added = $transactions->add([
                'description' => 'Balance Payment',
                'payment_method' => 'stripe',
                'payment_method_title' => 'Credit / Debit Card',
                'transaction_id' => $charge_id,
                'amount' => $amount,
            ]);

            if (!$added) {
                error_log('[AA Stripe API] Warning: Transaction may already exist for ' . $charge_id);
            }
        } else {
            // Fallback: Add transaction directly to meta
            $transactions = get_post_meta($order_id, 'aa_order_transactions', true) ?: [];
            $transactions[] = [
                'id' => $order_id . '-' . bin2hex(random_bytes(8)),
                'time' => current_time('timestamp'),
                'description' => 'Balance Payment',
                'payment_method' => 'stripe',
                'payment_method_title' => 'Credit / Debit Card',
                'transaction_id' => $charge_id,
                'amount' => $amount,
            ];
            update_post_meta($order_id, 'aa_order_transactions', $transactions);
        }

        // Update _aa_total_paid
        $current_paid = floatval($order->get_meta('_aa_total_paid') ?: 0);
        $order->update_meta_data('_aa_total_paid', $current_paid + $amount);

        // Clear pending intent
        $order->delete_meta_data('_aa_pending_payment_intent');
        $order->delete_meta_data('_aa_pending_payment_amount');

        // Check if fully paid
        $new_balance = $this->get_balance_due($order);
        $fully_paid = ($new_balance <= 0);

        // Update order status if fully paid
        if ($fully_paid) {
            $order->set_status('processing', 'Full payment received via custom Stripe API.');
            $order->set_date_paid(current_time('timestamp'));
        } else {
            $order->add_order_note(sprintf(
                'Partial payment of %s received. Remaining balance: %s',
                strip_tags(wc_price($amount)),
                strip_tags(wc_price($new_balance))
            ));
        }

        // Store Stripe references
        $order->update_meta_data('_stripe_charge_id', $charge_id);
        $order->update_meta_data('_transaction_id', $charge_id);

        $order->save();

        error_log(sprintf('[AA Stripe API] Payment confirmed for Order #%d, amount: %s, fully_paid: %s',
            $order_id, $amount, $fully_paid ? 'yes' : 'no'));

        // Build redirect URL
        $redirect_url = $order->get_checkout_order_received_url();

        return rest_ensure_response([
            'success' => true,
            'message' => $fully_paid ? 'Payment complete! Order fully paid.' : 'Payment received. Balance updated.',
            'amount_paid' => $amount,
            'formatted_amount' => strip_tags(wc_price($amount)),
            'remaining_balance' => $new_balance,
            'formatted_balance' => strip_tags(wc_price($new_balance)),
            'fully_paid' => $fully_paid,
            'redirect_url' => $redirect_url,
        ]);
    }

    /**
     * Check if we should replace the payment form
     */
    public function maybe_replace_payment_form() {
        if (!is_wc_endpoint_url('order-pay')) {
            return;
        }

        global $wp;
        $order_id = absint($wp->query_vars['order-pay'] ?? 0);

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Check if there's a balance due (partial payment situation)
        $balance = $this->get_balance_due($order);

        // Only use our custom form if there's a balance to pay
        if ($balance > 0) {
            // Remove WooCommerce payment form
            remove_action('woocommerce_pay_order_before_submit', 'woocommerce_pay_order_button_html', 10);

            // Add our custom form
            add_action('woocommerce_pay_order_before_submit', [$this, 'render_custom_payment_form'], 10);

            // Hide the default submit button area
            add_filter('woocommerce_pay_order_button_html', '__return_empty_string');
        }
    }

    /**
     * Enqueue scripts for order-pay page
     */
    public function enqueue_scripts() {
        if (!is_wc_endpoint_url('order-pay')) {
            return;
        }

        global $wp;
        $order_id = absint($wp->query_vars['order-pay'] ?? 0);

        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $balance = $this->get_balance_due($order);
        if ($balance <= 0) {
            return;
        }

        // Enqueue Stripe.js
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', [], null, true);

        // Enqueue our custom checkout script
        wp_enqueue_script(
            'aa-stripe-checkout',
            get_stylesheet_directory_uri() . '/src/aa-stripe-checkout.js',
            ['jquery', 'stripe-js'],
            '1.1.0',
            true
        );

        // Pass data to JavaScript
        wp_localize_script('aa-stripe-checkout', 'aa_stripe_params', [
            'ajax_url' => rest_url('aa/v1/'),
            'order_id' => $order_id,
            'order_key' => $order->get_order_key(),
            'publishable_key' => $this->get_stripe_publishable_key(),
            'balance_due' => $balance,
            'formatted_balance' => strip_tags(wc_price($balance)),
            'currency' => strtolower($order->get_currency()),
            'currency_symbol' => get_woocommerce_currency_symbol($order->get_currency()),
            'return_url' => $order->get_checkout_order_received_url(),
            'nonce' => wp_create_nonce('aa_stripe_payment'),
        ]);

        // Add inline styles
        wp_add_inline_style('woocommerce-general', $this->get_custom_styles());
    }

    /**
     * Render custom payment form
     */
    public function render_custom_payment_form() {
        global $wp;
        $order_id = absint($wp->query_vars['order-pay'] ?? 0);
        $order = wc_get_order($order_id);

        if (!$order) {
            return;
        }

        $balance = $this->get_balance_due($order);
        ?>
        <script>document.body.classList.add("aa-custom-payment-active");</script>
        <div id="aa-stripe-payment-form" class="aa-payment-form" style="width:100%; max-width:700px; margin:20px auto;">
            <h3 style="font-size:22px; margin-bottom:20px;">Pay Balance: <?php echo wc_price($balance); ?></h3>

            <div class="aa-payment-option-wrapper" style="margin:15px 0; padding:12px; background:#fff; border-radius:6px;">
                <label style="display:flex; align-items:center; gap:10px; font-size:16px; cursor:pointer;">
                    <input type="radio" name="aa_payment_option" value="pay_full" checked style="width:18px; height:18px;">
                    Pay full balance: <?php echo wc_price($balance); ?>
                </label>
            </div>

            <div class="aa-payment-option-wrapper" style="margin:15px 0; padding:12px; background:#fff; border-radius:6px;">
                <label style="display:flex; align-items:center; gap:10px; font-size:16px; cursor:pointer;">
                    <input type="radio" name="aa_payment_option" value="pay_custom" style="width:18px; height:18px;">
                    Pay custom amount:
                </label>
                <div class="aa-custom-amount-wrapper" style="display:none; margin:15px 0 0 28px;">
                    <span class="currency-symbol" style="font-size:18px; font-weight:bold;"><?php echo get_woocommerce_currency_symbol(); ?></span>
                    <input type="number" id="aa_custom_amount" name="aa_custom_amount"
                           min="1" max="<?php echo esc_attr($balance); ?>"
                           step="0.01" placeholder="Enter amount"
                           style="width:150px; padding:10px; font-size:16px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div class="aa-card-element-wrapper" style="margin:25px 0; width:100%;">
                <label for="aa-card-element" style="display:block; margin-bottom:12px; font-weight:bold; font-size:16px;">Card Details</label>
                <div id="aa-card-element" style="background:#fff; padding:15px; border:2px solid #ccc; border-radius:6px; min-height:50px; width:100%; box-sizing:border-box;">
                    <!-- Stripe Card Element will be mounted here -->
                </div>
                <div id="aa-card-errors" class="aa-card-errors" role="alert" style="color:#dc3545; margin-top:8px; font-size:14px;"></div>
            </div>

            <div class="aa-payment-actions" style="margin:25px 0;">
                <button type="button" id="aa-submit-payment" class="button alt" style="width:100%; padding:16px; font-size:18px; font-weight:bold; background:#4CAF50; color:#fff; border:none; border-radius:6px; cursor:pointer;">
                    <span class="aa-button-text">Pay <?php echo wc_price($balance); ?></span>
                    <span class="aa-button-loading" style="display:none;">Processing...</span>
                </button>
            </div>

            <div id="aa-payment-messages" class="aa-payment-messages"></div>
        </div>

                <style>
            /* Hide ALL WooCommerce default payment elements */
            #payment .payment_methods,
            #payment .place-order,
            .woocommerce-checkout-payment,
            #order_review .payment_method_stripe,
            .payment_method_stripe,
            .wc_payment_methods,
            .wc_payment_method,
            #payment ul.payment_methods,
            form.checkout_coupon,
            .aa-checkout-payment-option,
            .aa-checkout-payment-options,
            .woocommerce-form-coupon-toggle,
            #aa-order-payment-wrapper,
            .aa-deposit-payment-option,
            p:has(> input[name="payment_option"]),
            label:has(> input[name="payment_option"]),
            input[name="payment_option"],
            .select-payment-text,
            .woocommerce-privacy-policy-text,
            .woocommerce-terms-and-conditions-wrapper,
            /* Hide Apple Pay / Google Pay / Link buttons */
            #wc-stripe-payment-request-wrapper,
            .wc-stripe-payment-request-wrapper,
            #wc-stripe-payment-request-button-separator,
            .payment-request-button,
            #stripe-payment-request-button,
            .stripe-payment-request-button,
            #payment-request-button,
            .wc-stripe-express-checkout-element,
            #wc-stripe-express-checkout-element,
            [id*="express-checkout"],
            [class*="express-checkout"],
            /* Hide duplicate payment option radio buttons */
            .woocommerce-form-row:has(input[type="radio"]),
            #order_review > p:first-of-type,
            form.woocommerce-checkout > p:has(input[type="radio"]),
            .woocommerce-checkout-payment > p,
            #order_review_heading + p {
                display: none !important;
            }

            /* Fix layout - payment form full width and centered */
            #aa-stripe-payment-form {
                clear: both !important;
                float: none !important;
                position: relative !important;
                display: block !important;
                margin: 20px auto !important;
                width: 100% !important;
                max-width: 700px !important;
            }

/* Clear any floats before our form */            #aa-stripe-payment-form::before {                content: "";                display: table;                clear: both;            }            /* Hide WooCommerce default elements, keep our custom form visible */            body.aa-custom-payment-active .wc_payment_methods,            body.aa-custom-payment-active #place_order,            body.aa-custom-payment-active .woocommerce-terms-and-conditions-wrapper,            body.aa-custom-payment-active .woocommerce-checkout-review-order-table,            body.aa-custom-payment-active .form-row > input[type="hidden"] + .woocommerce-privacy-policy-text {                display: none !important;            }            /* Ensure payment container stays visible */            body.aa-custom-payment-active #payment {                display: block !important;            }        </style>
        <?php
    }

    /**
     * Get custom CSS styles
     */
    private function get_custom_styles() {
        return '
            .aa-payment-form {
                background: #f8f8f8;
                padding: 25px;
                border-radius: 8px;
                margin: 20px 0;
                width: 100%;
                max-width: 700px;
                box-sizing: border-box;
            }
            .aa-payment-form h3 {
                margin: 0 0 25px 0;
                color: #333;
                font-size: 24px;
            }
            .aa-payment-option-wrapper {
                margin: 15px 0;
                padding: 12px;
                background: white;
                border-radius: 6px;
                border: 1px solid #ddd;
            }
            .aa-payment-option-wrapper label {
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 16px;
            }
            .aa-payment-option-wrapper input[type="radio"] {
                width: 20px;
                height: 20px;
            }
            .aa-custom-amount-wrapper {
                margin: 15px 0 0 30px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .aa-custom-amount-wrapper .currency-symbol {
                font-size: 18px;
                font-weight: bold;
            }
            .aa-custom-amount-wrapper input {
                width: 150px;
                padding: 12px;
                border: 1px solid #ccc;
                border-radius: 4px;
                font-size: 16px;
            }
            .aa-card-element-wrapper {
                margin: 25px 0;
                width: 100%;
            }
            .aa-card-element-wrapper label {
                display: block;
                margin-bottom: 12px;
                font-weight: bold;
                font-size: 16px;
            }
            .aa-card-element {
                background: white;
                padding: 18px 15px;
                border: 2px solid #ccc;
                border-radius: 6px;
                min-height: 50px;
                width: 100%;
                box-sizing: border-box;
            }
            .aa-card-element iframe {
                width: 100% !important;
                min-width: 300px !important;
            }
            .aa-card-element:focus-within {
                border-color: #4CAF50;
                box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            }
            #aa-card-element {
                width: 100% !important;
                min-width: 350px;
            }
            .aa-card-errors {
                color: #dc3545;
                margin-top: 10px;
                font-size: 14px;
                min-height: 20px;
            }
            .aa-payment-actions {
                margin: 25px 0 15px 0;
            }
            #aa-submit-payment {
                width: 100%;
                padding: 18px;
                font-size: 18px;
                font-weight: bold;
                background: #4CAF50;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                transition: background 0.2s;
            }
            #aa-submit-payment:hover {
                background: #45a049;
            }
            #aa-submit-payment:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .aa-payment-messages {
                margin-top: 15px;
                padding: 15px;
                border-radius: 6px;
                font-size: 15px;
            }
            .aa-payment-messages.success {
                background: #d4edda;
                color: #155724;
            }
            .aa-payment-messages.error {
                background: #f8d7da;
                color: #721c24;
            }
        ';
    }
}

// Initialize
AA_Stripe_Custom_API::get_instance();

error_log('[AA Stripe API] Custom Stripe API loaded');
