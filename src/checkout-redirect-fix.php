<?php
/**
 * Checkout Redirect & Cart Clear Fix
 *
 * Ensures proper redirect to order-received page after payment
 * and clears the cart even if WooCommerce AJAX fails
 *
 * @author Ahmed Sallemi
 * @date January 2026
 */

// Store the completed order ID for redirect handling
$GLOBALS['aa_completed_order_id'] = null;

/**
 * Track when payment completes - store order ID for potential redirect
 */
add_action('woocommerce_payment_complete', function($order_id) {
    $GLOBALS['aa_completed_order_id'] = $order_id;

    // Also store in a transient in case of redirect issues
    set_transient('aa_last_completed_order_' . WC()->session->get_customer_id(), $order_id, 300);

    error_log("[AA Checkout] Payment complete for order #{$order_id}");
}, 1);

/**
 * Force proper checkout AJAX response after payment complete
 * This runs late to ensure payment has completed
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {

    // Add a very late action to check payment status before response is sent
    add_action('woocommerce_after_checkout_form_action', function() use ($order_id, $order) {

        // If payment completed, ensure we return success
        if ($GLOBALS['aa_completed_order_id'] == $order_id) {
            // Clear any error notices that might have been added
            wc_clear_notices();

            // Add only success notice
            wc_add_notice(__('Payment is successfully completed!', 'woocommerce'), 'success');
        }
    }, 9999);

}, 9999, 3);

/**
 * Ensure cart is cleared after successful payment
 */
add_action('woocommerce_payment_complete', function($order_id) {

    // Clear the cart
    if (WC()->cart) {
        WC()->cart->empty_cart();
        error_log("[AA Checkout] Cart cleared after payment for order #{$order_id}");
    }

}, 100); // Run after other payment_complete hooks

/**
 * Add JavaScript fallback redirect for cases where AJAX fails but payment succeeds
 */
add_action('wp_footer', function() {
    // Only on checkout page
    if (!is_checkout() || is_wc_endpoint_url('order-received') || is_wc_endpoint_url('order-pay')) {
        return;
    }

    $customer_id = WC()->session ? WC()->session->get_customer_id() : 0;
    $last_order_id = get_transient('aa_last_completed_order_' . $customer_id);

    if (!$last_order_id) {
        return;
    }

    $order = wc_get_order($last_order_id);

    if (!$order || !in_array($order->get_status(), ['processing', 'completed', 'on-hold'])) {
        return;
    }

    // Check if this is a recent order (within last 5 minutes)
    $order_date = $order->get_date_created();
    if (!$order_date || (time() - $order_date->getTimestamp()) > 300) {
        delete_transient('aa_last_completed_order_' . $customer_id);
        return;
    }

    // Order exists and is paid - redirect to confirmation
    $redirect_url = $order->get_checkout_order_received_url();

    // Delete the transient so we don't redirect again
    delete_transient('aa_last_completed_order_' . $customer_id);

    ?>
    <script>
    (function() {
        // Check if we should redirect
        var redirectUrl = <?php echo json_encode($redirect_url); ?>;
        var orderId = <?php echo json_encode($last_order_id); ?>;

        console.log('[AA Checkout] Fallback redirect check - Order #' + orderId + ' found, redirecting...');

        // Show processing overlay
        var overlay = document.createElement('div');
        overlay.id = 'aa-redirect-overlay';
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.95);z-index:99999;display:flex;align-items:center;justify-content:center;flex-direction:column;';
        overlay.innerHTML = '<p style="font-size:18px;">Your payment was successful!</p><p>Redirecting to confirmation page...</p>';
        document.body.appendChild(overlay);

        // Redirect after brief delay
        setTimeout(function() {
            window.location.href = redirectUrl;
        }, 1000);
    })();
    </script>
    <?php
}, 99);

/**
 * Handle checkout_error event - if payment actually completed, redirect anyway
 * This hooks into the WC checkout JS result handling
 */
add_action('wp_footer', function() {
    if (!is_checkout() || is_wc_endpoint_url('order-received') || is_wc_endpoint_url('order-pay')) {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        // Override WooCommerce's error handling for cases where payment completed
        $(document.body).on('checkout_error', function(e, error_message) {
            console.log('[AA Checkout] checkout_error event fired');

            // Check if there's a success message in the notices (indicating payment completed)
            var $successNotice = $('.woocommerce-message, .woocommerce-notices-wrapper .woocommerce-message');
            var hasSuccessNotice = $successNotice.length > 0 ||
                                   (error_message && error_message.indexOf('successfully completed') !== -1);

            if (hasSuccessNotice) {
                console.log('[AA Checkout] Payment success detected despite error - checking order history');

                // The payment succeeded but something else failed
                // Check order history for recent order and redirect if found
                $.ajax({
                    url: wc_checkout_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'aa_get_last_completed_order'
                    },
                    success: function(response) {
                        if (response.success && response.data.redirect_url) {
                            console.log('[AA Checkout] Redirecting to: ' + response.data.redirect_url);
                            window.location.href = response.data.redirect_url;
                        }
                    }
                });
            }
        });
    });
    </script>
    <?php
}, 99);

/**
 * AJAX handler to get last completed order redirect URL
 */
add_action('wp_ajax_aa_get_last_completed_order', 'aa_ajax_get_last_completed_order');
add_action('wp_ajax_nopriv_aa_get_last_completed_order', 'aa_ajax_get_last_completed_order');

function aa_ajax_get_last_completed_order() {
    $customer_id = WC()->session ? WC()->session->get_customer_id() : 0;
    $last_order_id = get_transient('aa_last_completed_order_' . $customer_id);

    if (!$last_order_id) {
        wp_send_json_error(['message' => 'No recent order found']);
        return;
    }

    $order = wc_get_order($last_order_id);

    if (!$order || !in_array($order->get_status(), ['processing', 'completed', 'on-hold'])) {
        wp_send_json_error(['message' => 'Order not in completed state']);
        return;
    }

    // Clear the transient
    delete_transient('aa_last_completed_order_' . $customer_id);

    // Clear the cart if not already
    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }

    wp_send_json_success([
        'order_id' => $last_order_id,
        'redirect_url' => $order->get_checkout_order_received_url()
    ]);
}

/**
 * Clear cart when viewing order-received page (backup)
 */
add_action('template_redirect', function() {
    if (is_wc_endpoint_url('order-received')) {
        // Ensure cart is empty
        if (WC()->cart && !WC()->cart->is_empty()) {
            WC()->cart->empty_cart();
            error_log("[AA Checkout] Cart cleared on order-received page");
        }
    }
}, 1);
