<?php
/**
 * Checkout Response Fix v3
 *
 * SAFE version - no output buffer manipulation
 * Uses JavaScript fallback only
 *
 * @author Ahmed Sallemi
 * @date January 2026
 * @version 3.0
 */

/**
 * Track order when processed (works for ALL order types)
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    // Store in session for JS redirect fallback
    if (WC()->session) {
        WC()->session->set('aa_order_id', $order_id);
        WC()->session->set('aa_order_redirect', $order->get_checkout_order_received_url());
        WC()->session->set('aa_order_time', time());
    }
}, 9999, 3);

/**
 * Clear cart when order is processed
 */
add_action('woocommerce_checkout_order_processed', function($order_id, $posted_data, $order) {
    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }
}, 10000, 3);

/**
 * Also track payment complete
 */
add_action('woocommerce_payment_complete', function($order_id) {
    $order = wc_get_order($order_id);
    if ($order && WC()->session) {
        WC()->session->set('aa_order_id', $order_id);
        WC()->session->set('aa_order_redirect', $order->get_checkout_order_received_url());
        WC()->session->set('aa_order_time', time());
    }
}, 1);

/**
 * JavaScript to handle redirect after checkout
 * This runs on checkout page and handles all cases
 */
add_action('wp_footer', function() {
    if (!is_checkout()) return;
    if (is_wc_endpoint_url('order-received')) return;
    if (is_wc_endpoint_url('order-pay')) return;

    // Check for pending redirect from session
    $order_id = WC()->session ? WC()->session->get('aa_order_id') : null;
    $redirect_url = WC()->session ? WC()->session->get('aa_order_redirect') : null;
    $order_time = WC()->session ? WC()->session->get('aa_order_time') : 0;

    // Auto-redirect if order was just created (within 2 minutes)
    if ($order_id && $redirect_url && $order_time && (time() - $order_time) < 120) {
        $order = wc_get_order($order_id);
        if ($order && in_array($order->get_status(), ['pending', 'processing', 'completed', 'on-hold'])) {
            // Clear session
            WC()->session->set('aa_order_id', null);
            WC()->session->set('aa_order_redirect', null);
            WC()->session->set('aa_order_time', null);

            // Clear cart
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
            }
            ?>
            <script>
            (function() {
                console.log('[AA Checkout] Order #<?php echo esc_js($order_id); ?> found, redirecting to confirmation...');
                // Show overlay
                var overlay = document.createElement('div');
                overlay.id = 'aa-redirect-overlay';
                overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:#fff;z-index:999999;display:flex;align-items:center;justify-content:center;flex-direction:column;';
                overlay.innerHTML = '<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting to confirmation page...</p>';
                document.body.appendChild(overlay);
                // Redirect
                window.location.href = '<?php echo esc_js($redirect_url); ?>';
            })();
            </script>
            <?php
            return; // Don't output the AJAX handler JS
        }
    }

    // AJAX fallback handler
    ?>
    <script>
    jQuery(function($) {
        console.log('[AA Checkout v3] Initializing checkout handler');

        // Handle checkout AJAX responses
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (!settings.url || settings.url.indexOf('wc-ajax=checkout') === -1) return;

            console.log('[AA Checkout] Checkout AJAX completed, status:', xhr.status);

            var responseText = xhr.responseText || '';

            // Try to parse as JSON
            try {
                var response = JSON.parse(responseText);
                console.log('[AA Checkout] Response parsed:', response.result);

                if (response.result === 'success' && response.redirect) {
                    console.log('[AA Checkout] Success! Redirecting...');
                    window.location.href = response.redirect;
                    return;
                }
            } catch(e) {
                console.log('[AA Checkout] Response is not JSON, checking for recovery...');
            }

            // If we're here, something went wrong - try to recover
            setTimeout(function() {
                console.log('[AA Checkout] Attempting recovery...');
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'aa_checkout_recover'
                }, function(result) {
                    console.log('[AA Checkout] Recovery result:', result);
                    if (result.success && result.data && result.data.redirect_url) {
                        console.log('[AA Checkout] Recovery successful, redirecting to:', result.data.redirect_url);
                        // Show overlay
                        var overlay = $('<div>').attr('id', 'aa-redirect-overlay')
                            .css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                            .html('<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                        $('body').append(overlay);
                        window.location.href = result.data.redirect_url;
                    }
                });
            }, 1000);
        });

        // Also handle checkout_error event
        $(document.body).on('checkout_error', function() {
            console.log('[AA Checkout] checkout_error event fired, attempting recovery...');
            setTimeout(function() {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'aa_checkout_recover'
                }, function(result) {
                    if (result.success && result.data && result.data.redirect_url) {
                        console.log('[AA Checkout] Recovery from error successful');
                        var overlay = $('<div>').attr('id', 'aa-redirect-overlay')
                            .css({position:'fixed',top:0,left:0,right:0,bottom:0,background:'#fff',zIndex:999999,display:'flex',alignItems:'center',justifyContent:'center',flexDirection:'column'})
                            .html('<h2 style="margin:0 0 10px 0;color:#333;">Order Received!</h2><p style="margin:0;color:#666;">Redirecting...</p>');
                        $('body').append(overlay);
                        window.location.href = result.data.redirect_url;
                    }
                });
            }, 500);
        });
    });
    </script>
    <?php
}, 50);

/**
 * AJAX handler for checkout recovery
 */
add_action('wp_ajax_aa_checkout_recover', 'aa_checkout_recover_handler');
add_action('wp_ajax_nopriv_aa_checkout_recover', 'aa_checkout_recover_handler');

function aa_checkout_recover_handler() {
    $order_id = WC()->session ? WC()->session->get('aa_order_id') : null;
    $redirect_url = WC()->session ? WC()->session->get('aa_order_redirect') : null;

    if (!$order_id || !$redirect_url) {
        wp_send_json_error(['message' => 'No pending order in session']);
        return;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error(['message' => 'Order not found']);
        return;
    }

    // Verify order is in valid status
    if (!in_array($order->get_status(), ['pending', 'processing', 'completed', 'on-hold'])) {
        wp_send_json_error(['message' => 'Order status not valid: ' . $order->get_status()]);
        return;
    }

    // Clear session data
    WC()->session->set('aa_order_id', null);
    WC()->session->set('aa_order_redirect', null);
    WC()->session->set('aa_order_time', null);

    // Clear cart
    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }

    wp_send_json_success([
        'order_id' => $order_id,
        'redirect_url' => $redirect_url,
        'order_status' => $order->get_status()
    ]);
}

/**
 * Clear cart on order-received page
 */
add_action('template_redirect', function() {
    if (is_wc_endpoint_url('order-received') && WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->empty_cart();
    }
}, 1);
