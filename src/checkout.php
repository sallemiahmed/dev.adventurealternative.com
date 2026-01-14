<?php

require_once __DIR__ . '/checkout-currency.php';

// ============================================
// COMPREHENSIVE TRANSACTION LOGGING
// Logs all checkout flow to individual files
// ============================================
function aa_log_transaction($message, $data = [], $force_new = false) {
    static $session_id = null;
    static $log_file = null;

    // Create logs directory
    $logs_dir = __DIR__ . '/logs';
    if (!file_exists($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }

    // Generate session ID for this request
    if ($session_id === null || $force_new) {
        $user_id = get_current_user_id() ?: 'guest';
        $session_id = $user_id . '-' . date('Ymd-His') . '-' . substr(uniqid(), -6);
        $log_file = $logs_dir . '/' . $session_id . '.log';

        // Write header
        $header = "\n" . str_repeat('=', 80) . "\n";
        $header .= "AA CHECKOUT LOG - " . date('Y-m-d H:i:s') . "\n";
        $header .= "Session: " . $session_id . "\n";
        $header .= "User ID: " . $user_id . "\n";
        $header .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
        $header .= "WC-AJAX: " . ($_REQUEST['wc-ajax'] ?? 'N/A') . "\n";
        $header .= "Action: " . ($_REQUEST['action'] ?? 'N/A') . "\n";
        $header .= "Cookies: " . print_r($_COOKIE, true) . "\n";
        $header .= str_repeat('=', 80) . "\n\n";
        file_put_contents($log_file, $header, FILE_APPEND);
    }

    // Format log entry
    $timestamp = date('H:i:s.') . substr(microtime(), 2, 3);
    $entry = "[{$timestamp}] {$message}\n";
    if (!empty($data)) {
        $entry .= "    DATA: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    $entry .= "\n";

    // Write to file
    file_put_contents($log_file, $entry, FILE_APPEND);

    // Also log to debug.log for visibility
    error_log("[AA-LOG] {$message} | " . json_encode($data));

    return $session_id;
}

// Log ALL requests to track the flow
add_action('init', function() {
    // Only log checkout-related requests
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $wc_ajax = $_REQUEST['wc-ajax'] ?? '';
    $action = $_REQUEST['action'] ?? '';

    $is_checkout = (strpos($uri, 'checkout') !== false);
    $is_stripe_ajax = (strpos($wc_ajax, 'stripe') !== false) || (strpos($action, 'stripe') !== false);
    $is_wc_ajax = !empty($wc_ajax);

    if ($is_checkout || $is_stripe_ajax || $is_wc_ajax) {
        aa_log_transaction('=== REQUEST START ===', [
            'uri' => $uri,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'wc_ajax' => $wc_ajax,
            'action' => $action,
            'is_ajax' => wp_doing_ajax() ? 'YES' : 'NO',
            'cookie_aa_payment_option' => $_COOKIE['aa_payment_option'] ?? 'NOT SET',
        ]);
    }
}, 1);

// maybe modify order total just before the payment goes through, so that we apply desired custom amount (if set) for the payment
function maybe_modify_order_total_before_payment_processing( $total, $order ){
    
    $trip_order = new AA_Trip_Order( $order );
    
    /*
    !!!!
    DO NOT PLACE ANY AMOUNTS/TRANSACTIONS-RELATED CODE HERE (BEFORE THE NEXT "IF" CODE BLOCKS RUN), BECAUSE IT CAN CAUSE AN INFINITE LOOP.
    IT'S IMPORTANT THAT THE $GLOBALS FILTERS FROM THE NEXT "IF" BLOCK ARE EVALUATED FIRST.
    !!!!
    */
    
    // if payment is completed, do not change order total anymore
    // or if specific $GLOBALS variable is set to temporary disable this filter
    if( ( did_action('woocommerce_payment_complete') && empty( $GLOBALS['force_order_total_filter_after_payment'] ) ) || ! empty( $GLOBALS['disable_order_total_filter'] ) ){
        
        $trip_order->add_debug_data( 'maybe_modify_order_total_before_payment_processing', [ 
            'skipped' => 1,
            'did_action_woocommerce_payment_complete' => did_action('woocommerce_payment_complete'),
            'force_order_total_filter_after_payment' => intval( $GLOBALS['force_order_total_filter_after_payment'] ?? 0 ),
            'disable_order_total_filter' => intval( $GLOBALS['disable_order_total_filter'] ?? 0 ),
            'initial_total' => $total
        ]);
        
        return $total;
        
    }
    
    $max_payment_amount = $trip_order->get_next_payment_max_amount();
    
    // we are getting transactions directly from db instead of using new instance of AA_Order_Transactions in this filter because we want to prevent potential infinite loop in future if transactions class tries to use $order->get_total() inside __construct() method.
    $transactions = get_post_meta( $order->get_id(), 'aa_order_transactions', true ) ?: [];
    
    // logic to check what payment option is selected (full, deposit or custom). Method params: data, max_amount, allow_deposit
    $payment_option = AA_Checkout::get_selected_payment_option( $_POST, $total, empty( $transactions ) );
    
    $trip_order->add_debug_data( 'maybe_modify_order_total_before_payment_processing', [
        'payment_option' => $payment_option,
        'custom_amount' => $_POST['custom_payment_amount'] ?? '',
        'initial_total' => $total,
        'max_payment_amount' => $max_payment_amount
    ]);
    
    if( empty( $payment_option ) ){
        
        $trip_order->add_debug_data( 'EMPTY PAYMENT OPTION - maybe_modify_order_total_before_payment_processing', [
            'payment_option' => $payment_option,
            'custom_amount' => $_POST['custom_payment_amount'] ?? '',
            'initial_total' => $total,
            'max_payment_amount' => $max_payment_amount,
            'POST_DATA' => $_POST
        ]);
        
    }
    
    // only adjust order total if payment option is set to "pay_deposit" or "pay_custom". Custom amount is already validated in "get_selected_payment_option" method, so we can use raw value
    if( $payment_option == 'pay_deposit' )
        $final_total = AA_Checkout::get_instance()->get_deposit_amount() ?: $total;
    elseif( $payment_option == 'pay_custom' )
        $final_total = $_POST['custom_payment_amount'];
    elseif( empty( $payment_option ) )
        $final_total = 0; // ensure we are not charging anything to prevent unwanted charges if payment option for some reason is not set
    else
        $final_total = $total;
    
    return min( $final_total, $max_payment_amount );
    
}

// use this function to convert all custom checkout data (which you plan to save in order) to order meta data
// To Connor: I decided to save all checkout data at once. If you want to save each data individually, you can change this function and add multiple order metas.
function save_checkout_data_as_order_meta( $order ){
    
    $checkout_data = AA_Session::get();
    
    if( ! empty( $checkout_data['steps_data'] ) ){
        
        $merged_data = array_merge( ... array_values( $checkout_data['steps_data'] ) );

        $num_passengers = intval( $merged_data['num_passengers'] ?? 0 );
        
        for( $i = 0; $i < $num_passengers; $i++ ){
            
            $passenger_details[] = [
                'first_name' => sanitize_text_field( $merged_data['passenger_first_name'][$i] ),
                'last_name' => sanitize_text_field( $merged_data['passenger_last_name'][$i] ),
                'gender' => sanitize_text_field( $merged_data['passenger_gender'][$i] ),
                'dob' => sanitize_text_field( $merged_data['passenger_dob'][$i] ),
                'email' => sanitize_email( $merged_data['passenger_email'][$i] ),
            ];
            
        }
        
    }
        
    $checkout_data['passenger_details'] = $passenger_details ?? [];
    
    $checkout_currency = AA_Session::get( 'checkout_currency', '' );
    
    if( ! empty( $checkout_currency ) )
        setcookie( 'aa_currency', $checkout_currency, time()+60*60*24*30*12*5, '/' );
    
    update_post_meta( $order->get_id(), 'aa_checkout_data', $checkout_data );
    update_post_meta( $order->get_id(), 'aa_currency', $checkout_currency );
    
}

// validate all fields before checkout can be completed by the visitor. This is only for checkout payment. For additional payments, "woocommerce_before_pay_action" hook is used instead
add_action( 'woocommerce_after_checkout_validation', function( $posted, $errors ){
    
    foreach( AA_Checkout::get_selected_payment_option_errors( $_POST, WC()->cart->get_total('edit'), AA_Checkout::get_instance()->get_min_deposit_amount() ) as $error_data ){
        $errors->add( $error_data[0], $error_data[1] );
    }
    
    AA_Checkout::$debug_data[] = [ 
        'woocommerce_after_checkout_validation passed' => 1
    ];
    
}, 10, 2);

// maybe set expected deposit amount just before payment hook on checkout submission - not applied to additional payments
add_action('woocommerce_checkout_order_processed', function( $order_id, $posted_data, $order ){
    
    // only if payment is needed
    if( apply_filters( 'woocommerce_cart_needs_payment', $order->needs_payment(), WC()->cart ) ){
        
        // get expected deposit amount (this can also be a custom deposit amount set by customer)
        $deposit_amount = AA_Checkout::get_instance()->get_deposit_amount();
    
        AA_Checkout::$debug_data[] = [ 
            'deposit_amount-woocommerce_checkout_order_processed' => $deposit_amount
        ];
        
        if( $deposit_amount > 0 ){
            
            // set deposit metas for the order. We will need this after payment is completed, to add a transaction to the order
            update_post_meta( $order_id, 'deposit_amount', $deposit_amount );
            update_post_meta( $order_id, 'deposit_is_paid', false );
            
            // WC will try to capture payment after this hook. So lets change payment amount by adding our filter
            add_filter('woocommerce_order_get_total', 'maybe_modify_order_total_before_payment_processing', 10000, 2);
            
        }
        
    }
    
    AA_Checkout::maybe_save_debug_data_to_trip_order( new AA_Trip_Order( $order ), 'woocommerce_checkout_order_processed' );

}, 1000, 3);

add_filter('wc_stripe_generate_payment_request', function( $payment_request_data, $order ){

    try {

        $trip_order = new AA_Trip_Order( $order );

        $max_payment_amount = $trip_order->get_next_payment_max_amount();
        $stripe_amount = $payment_request_data['amount'] / 100;

        // FIX: Check if deposit payment is selected for NEW orders (no transactions yet)
        $transactions = $trip_order->get_transactions();
        $has_transactions = ! empty( $transactions->get_transactions() );

        // CRITICAL: Must pass allow_deposit=true for new orders, otherwise pay_deposit gets cleared!
        $payment_option = AA_Checkout::get_selected_payment_option( $_POST, false, !$has_transactions );

        // If this is a new order with deposit selected, use deposit amount
        if( ! $has_transactions && $payment_option === 'pay_deposit' ){
            $checkout_instance = AA_Checkout::get_instance();
            $deposit_amount = $checkout_instance->get_min_deposit_amount();

            if( $deposit_amount > 0 && $deposit_amount < $stripe_amount ){
                $payment_request_data['amount'] = intval( $deposit_amount * 100 ); // Convert to cents
            }
        }
        // For existing orders or other cases, apply max payment limit
        elseif( $max_payment_amount < $stripe_amount ){
            $payment_request_data['amount'] = $max_payment_amount * 100; // must be in cents, thats why x100 is used
        }

        $trip_order->add_debug_data( 'wc_stripe_generate_payment_request', [
            'payment_option' => $payment_option,
            'has_transactions' => $has_transactions ? 1 : 0,
            'custom_amount' => $_POST['custom_payment_amount'] ?? '',
            'order_total' => $trip_order->get_transactions()->get_order_total(),
            'order_total_maybe_modified' => $order->get_total(),
            'max_payment_amount' => $max_payment_amount,
            'stripe_amount_original' => $stripe_amount,
            'stripe_final_amount' => $payment_request_data['amount'] / 100,
            //'payment_request_data' => $payment_request_data
        ]);

    } catch (Exception $e) {

        $trip_order->add_debug_data( 'AA_ERROR in wc_stripe_generate_payment_request', [
            'payment_option' => AA_Checkout::get_selected_payment_option( $_POST ),
            'custom_amount' => $_POST['custom_payment_amount'] ?? '',
            'order_total' => $order->get_total(),
            'error_exception_msg' => $e->getMessage(),
            //'payment_request_data' => $payment_request_data
        ]);

    }

    return $payment_request_data;

}, 10, 2);

// FIX: Modify Stripe UPE cartTotal when deposit payment is selected
// This filter runs when checkout page loads AND when checkout is refreshed via AJAX
add_filter('wc_stripe_upe_params', function( $params ){

    // Only apply on checkout page
    if( ! is_checkout() || is_wc_endpoint_url('order-received') ){
        return $params;
    }

    try {
        $checkout_instance = AA_Checkout::get_instance();
        $cart_total = WC()->cart ? WC()->cart->get_total('edit') : 0;
        $min_deposit = $checkout_instance->get_min_deposit_amount();

        // Check payment option from both session (AA_Checkout) and $_POST (AJAX update)
        $payment_option = $_POST['payment_option'] ?? '';

        // Also try to get from AA_Checkout if not in $_POST
        if( empty( $payment_option ) ){
            $deposit_amount = $checkout_instance->get_deposit_amount();
            if( $deposit_amount > 0 ){
                $payment_option = 'pay_deposit'; // deposit is selected via session
            }
        }

        // If deposit payment is selected, calculate deposit amount
        if( $payment_option === 'pay_deposit' ){
            // Use min deposit amount (£200 per person)
            $deposit_amount = $min_deposit;

            if( $deposit_amount > 0 && $deposit_amount < $cart_total ){
                $original_total = $params['cartTotal'] ?? 0;
                $params['cartTotal'] = intval( $deposit_amount * 100 ); // Convert to cents

                AA_Checkout::$debug_data[] = [
                    'wc_stripe_upe_params_modified' => 1,
                    'payment_option' => $payment_option,
                    'original_cartTotal' => $original_total,
                    'deposit_amount' => $deposit_amount,
                    'new_cartTotal' => $params['cartTotal']
                ];
            }
        }

    } catch( Exception $e ){
        // Log error but don't break checkout
        AA_Checkout::$debug_data[] = [
            'wc_stripe_upe_params_error' => $e->getMessage()
        ];
    }

    return $params;

}, 10000 );

// do not require a payment if customer selected "pay later" option in checkout
add_filter('woocommerce_cart_needs_payment', [ 'AA_Checkout', 'needs_payment' ], 1000 );
add_filter('woocommerce_order_needs_payment', [ 'AA_Checkout', 'needs_payment' ], 1000 );

// set specific order status when the order is submitted with "pay later" option selected
// ensure that order status is allowed with "woocommerce_valid_order_statuses_for_payment" hook. By default, WC allows only "pending" and "failed" statuses for making a payment
add_filter('woocommerce_payment_complete_order_status', function( $status, $order_id, $order ){

    if( AA_Checkout::get_selected_payment_option( $_POST ) == 'pay_later' ){
        $order->set_date_paid(); // remove default WC date paid, as its not needed when "pay later" option is selected
        return $status; // can be set to different order status if needed
    }
    else
        return $status;

}, 1000, 3 );

// disable all payment gateways when "pay later" option is selected
add_filter('woocommerce_available_payment_gateways', function( $available_payment_gateways ){

    if( AA_Checkout::get_selected_payment_option( $_POST ) == 'pay_later' )
        return [];
    else
        return $available_payment_gateways;

}, 1000 );

// ensure that our data is set in $_POST when WC is doing ajax update on checkout page
add_action('woocommerce_checkout_update_order_review', function( $post_data_string ){
    
    parse_str( $post_data_string, $post_data );
    
    if( isset( $post_data['payment_option'] ) )
        $_POST['payment_option'] = $post_data['payment_option'];

}, 1);

// modify order total during pay page form submission (for existing orders - additional payments)
if( isset( $_POST['woocommerce_pay'], $_GET['key'] ) ){
    
    // WC uses "WP" action hook when processing the payment on a pay page, so lets add our filter on that action hook
    add_action('wp', function(){
    
        add_filter('woocommerce_order_get_total', 'maybe_modify_order_total_before_payment_processing', 10000, 2);
    
    }, 10);
    
}

// check for errors before processing with payment on a pay page - additional payments only. For checkout payment, "woocommerce_after_checkout_validation" is used instead
add_action('woocommerce_before_pay_action', function( $order ){
    
    $errors_count = 0;
    
    $trip_order = new AA_Trip_Order( $order );
    
    $next_payment_rules = $trip_order->get_next_payment_rules();
    
    if( ! $next_payment_rules ){
        
        wc_add_notice( 'New payments are not allowed for this order!', 'error' );
        $errors_count++;
        
    }
    
    // we don't want to allow offline payment methods for additional payments
    if( in_array( $order->get_payment_method(), [ 'cod', 'cheque' ] ) ){
    
        wc_add_notice( 'Selected payment method is not allowed for additional payments!', 'error' );
        $errors_count++;
    
    }
    
    foreach( AA_Checkout::get_selected_payment_option_errors( $_POST, $next_payment_rules['max_payment_amount'], $next_payment_rules['min_payment_amount'] ) as $error_data ){
        wc_add_notice( $error_data[1], 'error' );
        $errors_count++;
    }
    
    if( $errors_count > 0 ){
        
        wp_redirect( get_current_url() );
        die;
        
    }

}, 100 );

// Do some tasks when payment is successfully completed
// This action hook will be applied to both checkout payment and additional payments
// It will not run if order is created without making actual payment (for example, in cases of "Bank transfer" or "Cash On Delivery")
// We decided not to use hook "woocommerce_payment_complete" because emails are sent before that hook. So instead we use woocommerce_pre_payment_complete. 
// Also note that new order status is not set when woocommerce_pre_payment_complete hook runs
add_action('woocommerce_pre_payment_complete', function( $order_id, $transaction_id ){
    
    $trip_order = new AA_Trip_Order( $order_id );
    
    AA_Checkout::maybe_save_debug_data_to_trip_order( $trip_order, 'woocommerce_pre_payment_complete' );
    
    if( ! empty( AA_Checkout::$debug_data ) ){
        $trip_order->add_debug_data( 'CHECKOUT DEBUG DATA on woocommerce_pre_payment_complete', AA_Checkout::$debug_data );
        AA_Checkout::$debug_data = [];
    }
    
    // no need to add transaction if "pay later" option is selected during checkout. This check will break this hook only one time (on checkout - not for additional payments)
    if( ! isset( $_POST['woocommerce_pay'] ) && AA_Checkout::get_selected_payment_option( $_POST ) == 'pay_later' ){
        
        $trip_order->add_debug_data( 'woocommerce_pre_payment_complete', [
            'payment_option' => AA_Checkout::get_selected_payment_option( $_POST ),
            'skipped_transaction_creation' => 1
            ]);
        
        return;
        
    }
    
    if( false === has_filter('woocommerce_order_get_total', 'maybe_modify_order_total_before_payment_processing') )
        add_filter('woocommerce_order_get_total', 'maybe_modify_order_total_before_payment_processing', 10000, 2);
    
    $order = wc_get_order( $order_id );
    $deposit_amount = get_post_meta( $order_id, 'deposit_amount', true);
    $deposit_is_paid = get_post_meta( $order->get_id(), 'deposit_is_paid', true );
    $payment_type = ( ! $deposit_is_paid && $deposit_amount ) ? 'deposit' : 'balance';
    
    $transactions = $trip_order->get_transactions();
    
    // we are still inside 'woocommerce_payment_complete' hook, but did_action('woocommerce_payment_complete') may return true, so we need to keep forcing filtering of order->get_total() for cases when custom amount is set
    $GLOBALS['force_order_total_filter_after_payment'] = true;
    
    // set new transaction amount. Filter forcing from above will ensure that we get custom amount, if set
    $new_transaction_amount = $order->get_total();
    
    // now we can stop forcing filtering of $order->get_total()
    unset( $GLOBALS['force_order_total_filter_after_payment'] );
    
    // when payment is completed, we need to add our custom transaction to the order
    $added = $transactions->add([
        'description'           => $payment_type == 'deposit' ? 'Deposit' : 'Payment',
        'payment_method'        => $order->get_payment_method() ?: '',
        'payment_method_title'  => $order->get_payment_method_title() ?: '',
        'transaction_id'        => $transaction_id ?: '',
        'amount'                => $payment_type == 'deposit' ? $deposit_amount : min( $new_transaction_amount, $transactions->get_total_to_pay() ) // order_total here is maybe not real order total, because it can be filtered via woocommerce_order_get_total hook (used for applying custom amount for payment)
    ]);
    
    if( $trip_order->is_paid_later() && ! empty( $transactions->just_added_transaction['id'] ) )
        $transactions->send_transaction_email( $transactions->just_added_transaction['id'] );
    
    // when payment is completed and transaction is added, we don't want to adjust order's total value anymore, so lets remove that filter
    remove_filter('woocommerce_order_get_total', 'maybe_modify_order_total_before_payment_processing', 10000 );
    
    // if deposit is paid, save that info to meta
    if( $payment_type == 'deposit' )
        update_post_meta( $order_id, 'deposit_is_paid', true );
    
    // inform customer that payment has completed
    wc_add_notice( 'Payment is successfully completed!', 'success' );
    
}, 1, 2 );

// unset order paid WC sets, if not all transactions are added
foreach( [ 'woocommerce_order_status_processing', 'woocommerce_order_status_completed' ] as $status_action ){
    
    add_action( $status_action, function( $order_id, $order ){
        
        $trip_order = new AA_Trip_Order( $order );
        $transactions = $trip_order->get_transactions();
        
        // if order is still unpaid after this payment, ensure that we remove date paid value WC sets during payment
        if( $transactions->is_order_unpaid() ){
            
            $order->set_date_paid(null);
            $order->save();
            
        }
        
    }, 1000, 2 );
    
}

// if order has deposit payment added and not all transactions are added, mark order as "needs payment"
add_filter('woocommerce_order_needs_payment', function( $needs_payment, $order, $valid_order_statuses_for_payment ){
    
    if( ! $needs_payment ){
        
        $transactions = new AA_Order_Transactions( $order );
        
        if( $transactions->is_order_unpaid() )
            return true;
        
    }

    return $needs_payment;

}, 11, 3 );

// add "processing" and "completed" order statuses as valid for payment completion, in cases when transactions shows that order is still not fully paid
// this will ensure that "woocommerce_payment_complete" hook is not skipped
add_filter('woocommerce_valid_order_statuses_for_payment_complete', function( $statuses, $order ){

    if( ( new AA_Order_Transactions( $order ) )->is_order_unpaid() )
        $statuses = array_unique( array_merge( $statuses, [ 'processing', 'completed' ] ) );

    return $statuses;

}, 1000, 2 );

// do some actions when the order is created, after checkout is completed
add_action('woocommerce_checkout_order_created', function( $order ){

    // after order is created, put all checkout data we collected, to the order meta so that it be used in order related code. 
    save_checkout_data_as_order_meta( $order );
    
    // lets update some data for order items
    foreach( $order->get_items() as $item_id => $item ){
        
        $product_id = $item->get_product_id();
        
        if( $product_id ){
            
            // Check if the order item is a trip product. If yes, let's save some product meta as order item meta. 
            // This will ensure that even if the product meta changes in the future, it will not affect already created orders.
            if( is_trip_product( $product_id ) ){
                
                // needed for updating order meta below
                $has_trip_product = 1;
                
                // get start_date from product meta
                $start_date = DateTime::createFromFormat('d/m/Y', get_field( 'start_date', $product_id ) );
                
                // save start_date to order item meta.
                if( $start_date )
                    $item->add_meta_data( 'start_date', $start_date->format('Y-m-d H:i:s'), true );
                
                // get end_date from product meta
                $end_date = DateTime::createFromFormat('d/m/Y', get_field( 'end_date', $product_id ) );
                
                // save end_date to order item meta.
                if( $end_date )
                    $item->add_meta_data( 'end_date', $end_date->format('Y-m-d H:i:s'), true );
                
                $item->save();
                
            }
            
        }
        
    }
    
    // if the order has a trip product, save that info as order meta
    update_post_meta( $order->get_id(), 'has_trip_product', $has_trip_product ?? 0 );
    
}, 10);
// ============================================
// FIX: Modify cart total for Stripe UPE PaymentIntent creation
// The WooCommerce Stripe plugin calls WC()->cart->get_total(false) directly
// WITHOUT applying the wc_stripe_generate_create_intent_request filter!
// So we must intercept at the cart total level.
//
// WooCommerce AJAX uses $_REQUEST['wc-ajax'] NOT $_REQUEST['action']!
// ============================================
add_filter('woocommerce_cart_get_total', function($total) {
    // Log EVERY call to this filter during AJAX
    if (wp_doing_ajax()) {
        aa_log_transaction('woocommerce_cart_get_total CALLED (AJAX)', [
            'total' => $total,
            'wc_ajax' => $_REQUEST['wc-ajax'] ?? 'NOT SET',
            'action' => $_REQUEST['action'] ?? 'NOT SET',
        ]);
    }

    // Only modify during AJAX
    if (!wp_doing_ajax()) {
        return $total;
    }

    // Check if this is a Stripe payment-related request
    // WC AJAX uses 'wc-ajax' parameter, not 'action'
    // IMPORTANT: Stripe UPE uses DEFERRED payment intents - the intent is created
    // during 'checkout' action, NOT 'wc_stripe_create_payment_intent'!
    $wc_action = $_REQUEST['wc-ajax'] ?? '';
    $action = $_REQUEST['action'] ?? '';

    // Match: checkout, wc_stripe_create_payment_intent, wc_stripe_update_payment_intent
    $is_payment_action = in_array($wc_action, ['checkout', 'wc_stripe_create_payment_intent', 'wc_stripe_update_payment_intent']) ||
                         in_array($action, ['checkout', 'wc_stripe_create_payment_intent', 'wc_stripe_update_payment_intent']);

    if (!$is_payment_action) {
        return $total;
    }

    aa_log_transaction('*** PAYMENT ACTION DETECTED - CHECKING FOR DEPOSIT ***', [
        'wc_ajax' => $wc_action,
        'action' => $action,
        'original_total' => $total,
    ]);

    try {
        // Get payment option from cookie (set by JavaScript)
        $payment_option = '';
        if (isset($_COOKIE['aa_payment_option'])) {
            $payment_option = sanitize_text_field($_COOKIE['aa_payment_option']);
        }

        aa_log_transaction('Payment option from cookie', [
            'payment_option' => $payment_option,
            'payment_option_raw' => $_COOKIE['aa_payment_option'] ?? 'NOT SET',
            'payment_option_length' => strlen($payment_option),
            'is_pay_deposit' => ($payment_option === 'pay_deposit') ? 'YES' : 'NO',
        ]);

        if ($payment_option === 'pay_deposit') {
            aa_log_transaction('INSIDE pay_deposit block - getting checkout instance');

            $checkout_instance = AA_Checkout::get_instance();
            aa_log_transaction('Got checkout instance, getting deposit amount');

            $deposit_amount = $checkout_instance->get_min_deposit_amount();

            aa_log_transaction('Deposit payment selected', [
                'deposit_amount' => $deposit_amount,
                'original_total' => $total,
                'deposit_type' => gettype($deposit_amount),
                'total_type' => gettype($total),
            ]);

            if ($deposit_amount > 0 && $deposit_amount < $total) {
                aa_log_transaction('*** MODIFYING CART TOTAL ***', [
                    'from' => $total,
                    'to' => $deposit_amount,
                ]);
                return $deposit_amount;
            } else {
                aa_log_transaction('NOT modifying - conditions not met', [
                    'deposit_amount' => $deposit_amount,
                    'total' => $total,
                    'deposit_gt_0' => ($deposit_amount > 0) ? 'YES' : 'NO',
                    'deposit_lt_total' => ($deposit_amount < $total) ? 'YES' : 'NO',
                ]);
            }
        } else {
            aa_log_transaction('NOT pay_deposit - skipping modification', [
                'payment_option_value' => $payment_option,
                'expected' => 'pay_deposit',
            ]);
        }

    } catch (Exception $e) {
        aa_log_transaction('ERROR in cart total filter', ['error' => $e->getMessage()]);
    }

    return $total;
}, 99999);

// Also intercept update_payment_intent to ensure amount stays correct
add_filter('woocommerce_cart_get_total', function($total) {
    if (!wp_doing_ajax()) {
        return $total;
    }

    $wc_action = $_REQUEST['wc-ajax'] ?? '';
    $action = $_REQUEST['action'] ?? '';

    $is_stripe_update = ($wc_action === 'wc_stripe_update_payment_intent') ||
                        ($action === 'wc_stripe_update_payment_intent');

    if (!$is_stripe_update) {
        return $total;
    }

    aa_log_transaction('*** STRIPE UPDATE PAYMENT INTENT ***', [
        'original_total' => $total,
    ]);

    try {
        $payment_option = '';
        if (isset($_COOKIE['aa_payment_option'])) {
            $payment_option = sanitize_text_field($_COOKIE['aa_payment_option']);
        }

        if ($payment_option === 'pay_deposit') {
            $checkout_instance = AA_Checkout::get_instance();
            $deposit_amount = $checkout_instance->get_min_deposit_amount();

            if ($deposit_amount > 0 && $deposit_amount < $total) {
                aa_log_transaction('*** MODIFYING UPDATE INTENT TOTAL ***', [
                    'from' => $total,
                    'to' => $deposit_amount,
                ]);
                return $deposit_amount;
            }
        }

    } catch (Exception $e) {
        aa_log_transaction('ERROR in update intent filter', ['error' => $e->getMessage()]);
    }

    return $total;
}, 99998);
