/**
 * 3DS Auto-Proceed Fix + Double Payment Protection
 *
 * Handles automatic form resubmission after 3DS verification
 * Prevents double payments with idempotency keys and submit protection
 *
 * @author Ahmed Sallemi
 * @date January 2026
 * @version 2.3 - Disabled order exists modal per client request
 */

(function($) {
    'use strict';

    // Idempotency key for this checkout session
    let checkoutIdempotencyKey = null;
    let isSubmitting = false;
    let orderCreated = false;
    let pendingOrderId = null;
    let overlayInitialized = false;

    /**
     * Generate unique idempotency key for this checkout session
     * Key is based on: cart hash + timestamp + random string
     */
    function generateIdempotencyKey() {
        const cartHash = $('input[name="woocommerce-process-checkout-nonce"]').val() || '';
        const timestamp = Date.now();
        const random = Math.random().toString(36).substring(2, 15);
        return 'aa_' + cartHash.substring(0, 8) + '_' + timestamp + '_' + random;
    }

    /**
     * Get or create idempotency key for this session
     */
    function getIdempotencyKey() {
        // Check sessionStorage first
        let storedKey = sessionStorage.getItem('aa_checkout_idempotency_key');
        let storedCartHash = sessionStorage.getItem('aa_checkout_cart_hash');
        let currentCartHash = $('input[name="woocommerce-process-checkout-nonce"]').val() || '';

        // If cart changed, generate new key
        if (storedKey && storedCartHash === currentCartHash) {
            checkoutIdempotencyKey = storedKey;
        } else {
            checkoutIdempotencyKey = generateIdempotencyKey();
            sessionStorage.setItem('aa_checkout_idempotency_key', checkoutIdempotencyKey);
            sessionStorage.setItem('aa_checkout_cart_hash', currentCartHash);
        }

        console.log('[AA Checkout] Idempotency key:', checkoutIdempotencyKey);
        return checkoutIdempotencyKey;
    }

    /**
     * Initialize loading overlay (inject CSS and HTML once)
     */
    function initLoadingOverlay() {
        if (overlayInitialized) return;
        overlayInitialized = true;

        // Add CSS for loading overlay
        var css = '' +
            '.aa-loading-overlay {' +
            '    position: fixed;' +
            '    top: 0;' +
            '    left: 0;' +
            '    width: 100%;' +
            '    height: 100%;' +
            '    background: rgba(0, 0, 0, 0.75);' +
            '    display: none;' +
            '    justify-content: center;' +
            '    align-items: center;' +
            '    z-index: 999999;' +
            '    flex-direction: column;' +
            '}' +
            '.aa-loading-overlay.active {' +
            '    display: flex;' +
            '}' +
            '.aa-loading-spinner {' +
            '    width: 60px;' +
            '    height: 60px;' +
            '    border: 4px solid rgba(255, 255, 255, 0.3);' +
            '    border-top-color: #ffffff;' +
            '    border-radius: 50%;' +
            '    animation: aa-spin 1s linear infinite;' +
            '}' +
            '.aa-loading-spinner.success {' +
            '    border-color: #4CAF50;' +
            '    border-top-color: #4CAF50;' +
            '    animation: none;' +
            '}' +
            '.aa-loading-spinner.success::after {' +
            '    content: "\\2713";' +
            '    display: flex;' +
            '    justify-content: center;' +
            '    align-items: center;' +
            '    height: 100%;' +
            '    font-size: 30px;' +
            '    color: #4CAF50;' +
            '}' +
            '.aa-loading-text {' +
            '    color: #ffffff;' +
            '    font-size: 20px;' +
            '    margin-top: 25px;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '    font-weight: 500;' +
            '}' +
            '.aa-loading-step {' +
            '    color: rgba(255, 255, 255, 0.7);' +
            '    font-size: 14px;' +
            '    margin-top: 10px;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-loading-warning {' +
            '    color: rgba(255, 255, 255, 0.6);' +
            '    font-size: 12px;' +
            '    margin-top: 30px;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '@keyframes aa-spin {' +
            '    0% { transform: rotate(0deg); }' +
            '    100% { transform: rotate(360deg); }' +
            '}';

        var styleEl = document.createElement('style');
        styleEl.id = 'aa-loading-overlay-styles';
        styleEl.textContent = css;
        document.head.appendChild(styleEl);

        // Add HTML overlay
        var overlayHtml = '<div class="aa-loading-overlay" id="aa-loading-overlay">' +
            '<div class="aa-loading-spinner" id="aa-loading-spinner"></div>' +
            '<div class="aa-loading-text" id="aa-loading-text">Processing...</div>' +
            '<div class="aa-loading-step" id="aa-loading-step">Please wait</div>' +
            '<div class="aa-loading-warning" id="aa-loading-warning">Please do not close this page</div>' +
            '</div>';

        $('body').append(overlayHtml);

        // Add modal CSS
        var modalCss = '' +
            '.aa-modal-overlay {' +
            '    position: fixed;' +
            '    top: 0;' +
            '    left: 0;' +
            '    width: 100%;' +
            '    height: 100%;' +
            '    background: rgba(0, 0, 0, 0.75);' +
            '    display: none;' +
            '    justify-content: center;' +
            '    align-items: center;' +
            '    z-index: 999999;' +
            '}' +
            '.aa-modal-overlay.active {' +
            '    display: flex;' +
            '}' +
            '.aa-modal-box {' +
            '    background: #ffffff;' +
            '    border-radius: 12px;' +
            '    padding: 30px 40px;' +
            '    max-width: 450px;' +
            '    width: 90%;' +
            '    text-align: center;' +
            '    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);' +
            '    animation: aa-modal-pop 0.3s ease-out;' +
            '}' +
            '@keyframes aa-modal-pop {' +
            '    0% { transform: scale(0.8); opacity: 0; }' +
            '    100% { transform: scale(1); opacity: 1; }' +
            '}' +
            '.aa-modal-icon {' +
            '    width: 70px;' +
            '    height: 70px;' +
            '    background: #FFF3CD;' +
            '    border-radius: 50%;' +
            '    display: flex;' +
            '    justify-content: center;' +
            '    align-items: center;' +
            '    margin: 0 auto 20px;' +
            '    font-size: 35px;' +
            '}' +
            '.aa-modal-title {' +
            '    font-size: 22px;' +
            '    font-weight: 600;' +
            '    color: #333;' +
            '    margin: 0 0 15px 0;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-modal-message {' +
            '    font-size: 15px;' +
            '    color: #666;' +
            '    margin: 0 0 25px 0;' +
            '    line-height: 1.5;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-modal-btn {' +
            '    display: inline-block;' +
            '    padding: 14px 35px;' +
            '    font-size: 16px;' +
            '    font-weight: 600;' +
            '    color: #ffffff;' +
            '    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);' +
            '    border: none;' +
            '    border-radius: 8px;' +
            '    cursor: pointer;' +
            '    text-decoration: none;' +
            '    transition: transform 0.2s, box-shadow 0.2s;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-modal-btn:hover {' +
            '    transform: translateY(-2px);' +
            '    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);' +
            '}' +
            '.aa-modal-secondary {' +
            '    display: block;' +
            '    margin-top: 15px;' +
            '    font-size: 13px;' +
            '    color: #999;' +
            '    text-decoration: none;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-modal-secondary:hover {' +
            '    color: #667eea;' +
            '}';

        var modalStyleEl = document.createElement('style');
        modalStyleEl.id = 'aa-modal-styles';
        modalStyleEl.textContent = modalCss;
        document.head.appendChild(modalStyleEl);

        // Add modal HTML
        var modalHtml = '<div class="aa-modal-overlay" id="aa-modal-overlay">' +
            '<div class="aa-modal-box">' +
            '<div class="aa-modal-icon" id="aa-modal-icon">&#9888;</div>' +
            '<h3 class="aa-modal-title" id="aa-modal-title">Order Already Created</h3>' +
            '<p class="aa-modal-message" id="aa-modal-message">An order has already been created for this booking.</p>' +
            '<a href="#" class="aa-modal-btn" id="aa-modal-btn">View Your Order</a>' +
            '<a href="#" class="aa-modal-secondary" id="aa-modal-secondary">Start a new booking</a>' +
            '</div>' +
            '</div>';

        $('body').append(modalHtml);

        // Modal secondary link - reset and close
        $('#aa-modal-secondary').on('click', function(e) {
            e.preventDefault();
            hideModal();
            resetCheckoutState();
            window.location.reload();
        });
    }

    /**
     * Show order exists modal with link to the order
     */
    function showOrderExistsModal(orderId) {
        initLoadingOverlay();
        hideProcessingMessage();

        $('#aa-modal-icon').html('&#9888;'); // Warning icon
        $('#aa-modal-title').text('Order Already Created');
        $('#aa-modal-message').text('An order (#' + orderId + ') has already been created for this booking. Click below to view your order details.');
        $('#aa-modal-btn').attr('href', '/account/view-order/' + orderId + '/').text('View Order #' + orderId);
        $('#aa-modal-overlay').addClass('active');
    }

    /**
     * Hide modal
     */
    function hideModal() {
        $('#aa-modal-overlay').removeClass('active');
    }

    /**
     * Show loading overlay with message
     */
    function showProcessingMessage(message, step) {
        initLoadingOverlay();
        $('#aa-loading-spinner').removeClass('success');
        $('#aa-loading-text').text(message || 'Processing...');
        $('#aa-loading-step').text(step || 'Please wait');
        $('#aa-loading-warning').show();
        $('#aa-loading-overlay').addClass('active');
    }

    /**
     * Update loading overlay message
     */
    function updateProcessingMessage(message, step, isSuccess) {
        $('#aa-loading-text').text(message || 'Processing...');
        if (step) {
            $('#aa-loading-step').text(step);
        }
        if (isSuccess) {
            $('#aa-loading-spinner').addClass('success');
            $('#aa-loading-warning').hide();
        }
    }

    /**
     * Hide loading overlay
     */
    function hideProcessingMessage() {
        $('#aa-loading-overlay').removeClass('active');
    }

    /**
     * Monitor for 3DS completion (when iframe/popup closes)
     */
    var threeDSMonitorInterval = null;
    function start3DSCompletionMonitor() {
        if (threeDSMonitorInterval) return;

        var lastIframeCount = $('iframe[src*="stripe"]').length;

        threeDSMonitorInterval = setInterval(function() {
            var currentIframeCount = $('iframe[src*="stripe"]').length;

            // Check if 3DS iframe was removed (user completed verification)
            if (lastIframeCount > 0 && currentIframeCount < lastIframeCount) {
                console.log('[AA Checkout] 3DS iframe closed - verification likely complete');
                updateProcessingMessage('Verifying card...', 'Step 2 of 3 - Please wait');

                // Continue monitoring for redirect or success
                setTimeout(function() {
                    updateProcessingMessage('Completing order...', 'Step 3 of 3 - Finalizing');
                }, 1500);

                clearInterval(threeDSMonitorInterval);
                threeDSMonitorInterval = null;
            }

            lastIframeCount = currentIframeCount;
        }, 500);

        // Stop monitoring after 5 minutes (timeout)
        setTimeout(function() {
            if (threeDSMonitorInterval) {
                clearInterval(threeDSMonitorInterval);
                threeDSMonitorInterval = null;
            }
        }, 300000);
    }

    /**
     * Check if we're returning from 3DS verification
     */
    function check3DSReturn() {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectStatus = urlParams.get('redirect_status');
        const paymentIntent = urlParams.get('payment_intent');

        if (redirectStatus && paymentIntent) {
            console.log('[AA Checkout] 3DS return detected:', redirectStatus);

            if (redirectStatus === 'succeeded') {
                showProcessingMessage('Verifying payment...', 'Step 2 of 3 - Card verified');
                setTimeout(function() {
                    updateProcessingMessage('Completing order...', 'Step 3 of 3 - Finalizing');
                    if (window.location.pathname.includes('checkout')) {
                        console.log('[AA Checkout] 3DS auto-submit fallback');
                        autoSubmitAfter3DS(paymentIntent);
                    }
                }, 1500);
            } else if (redirectStatus === 'failed') {
                showErrorMessage('Payment verification failed. Please try again.');
                resetCheckoutState();
            }
        }
    }

    /**
     * Show error message
     */
    function showErrorMessage(message) {
        hideProcessingMessage();
        const $notices = $('.woocommerce-notices-wrapper').first();
        if ($notices.length) {
            $notices.html('<div class="woocommerce-error" role="alert">' + message + '</div>');
            $('html, body').animate({ scrollTop: $notices.offset().top - 100 }, 500);
        } else {
            alert(message);
        }
    }

    /**
     * Auto-submit form after 3DS (fallback)
     */
    function autoSubmitAfter3DS(paymentIntent) {
        const $form = $('form.checkout');

        if (!$form.length) {
            console.error('[AA Checkout] Checkout form not found');
            return;
        }

        $('<input type="hidden" name="aa_3ds_completed" value="1">').appendTo($form);
        $('<input type="hidden" name="aa_payment_intent" value="' + paymentIntent + '">').appendTo($form);

        console.log('[AA Checkout] Auto-submitting form after 3DS');
        $form.submit();
    }

    /**
     * Reset checkout state (for fresh retry)
     */
    function resetCheckoutState() {
        isSubmitting = false;
        orderCreated = false;
        pendingOrderId = null;
        sessionStorage.removeItem('aa_checkout_idempotency_key');
        sessionStorage.removeItem('aa_checkout_cart_hash');
        sessionStorage.removeItem('aa_pending_order_id');
        enablePlaceOrderButton();
        hideProcessingMessage();
        console.log('[AA Checkout] Checkout state reset');
    }

    /**
     * Disable place order button
     */
    function disablePlaceOrderButton() {
        const $btn = $('#place_order');
        $btn.prop('disabled', true);
        $btn.addClass('aa-processing');
        if (!$btn.data('original-text')) {
            $btn.data('original-text', $btn.text());
        }
        $btn.text('Processing...');
    }

    /**
     * Enable place order button
     */
    function enablePlaceOrderButton() {
        const $btn = $('#place_order');
        $btn.prop('disabled', false);
        $btn.removeClass('aa-processing');
        if ($btn.data('original-text')) {
            $btn.text($btn.data('original-text'));
        }
    }

    /**
     * Enhanced payment option change handler
     */
    function initPaymentOptionHandler() {
        const $paymentOptions = $('input[name="payment_option"]');

        $paymentOptions.on('change', function() {
            const value = $(this).val();
            console.log('[AA Checkout] Payment option changed:', value);
            sessionStorage.setItem('aa_payment_option', value);
            // CRITICAL: Set cookie so PHP can read it during Stripe AJAX
            document.cookie = 'aa_payment_option=' + encodeURIComponent(value) + '; path=/; SameSite=Lax';
            console.log('[AA Checkout] Cookie set: aa_payment_option=' + value);
            $('body').trigger('update_checkout');
        });

        const savedOption = sessionStorage.getItem('aa_payment_option');
        if (savedOption && $paymentOptions.length) {
            $paymentOptions.filter('[value="' + savedOption + '"]').prop('checked', true).trigger('change');
        }

        // Set initial cookie from current selection
        const currentSelection = $paymentOptions.filter(':checked').val();
        if (currentSelection) {
            document.cookie = 'aa_payment_option=' + encodeURIComponent(currentSelection) + '; path=/; SameSite=Lax';
            console.log('[AA Checkout] Initial cookie set: aa_payment_option=' + currentSelection);
        }
    }

    /**
     * IMPROVED: Submit protection that doesn't reset on error
     */
    function initSubmitProtection() {
        const $form = $('form.checkout');

        // Generate idempotency key on page load
        getIdempotencyKey();

        // Add idempotency key to form
        if (!$('input[name="aa_idempotency_key"]').length) {
            $('<input type="hidden" name="aa_idempotency_key">').appendTo($form);
        }

        $form.on('submit', function(e) {
            // Update idempotency key in form
            $('input[name="aa_idempotency_key"]').val(checkoutIdempotencyKey);

            // Check if already submitting
            if (isSubmitting) {
                console.log('[AA Checkout] BLOCKED: Duplicate submission prevented');
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            // DISABLED: Order exists check - client requested removal
            // if (orderCreated && pendingOrderId) {
            //     showOrderExistsModal(pendingOrderId);
            //     e.preventDefault();
            //     return false;
            // }

            isSubmitting = true;
            disablePlaceOrderButton();

            // Show loading overlay
            showProcessingMessage('Processing payment...', 'Step 1 of 3 - Submitting order');

            console.log('[AA Checkout] Form submitted with idempotency key:', checkoutIdempotencyKey);

            // DO NOT reset isSubmitting on timeout - only on explicit success or page reload
        });

        // Listen for successful checkout (redirect)
        $(document.body).on('checkout_redirect', function(e, url) {
            console.log('[AA Checkout] Checkout successful, redirecting...');

            // Show success message
            updateProcessingMessage('Payment successful!', 'Redirecting...', true);

            // Clear session on success
            sessionStorage.removeItem('aa_checkout_idempotency_key');
            sessionStorage.removeItem('aa_checkout_cart_hash');
            sessionStorage.removeItem('aa_pending_order_id');
        });

        // IMPORTANT: Do NOT reset on checkout_error - this causes double payment!
        // Instead, show a message asking user to refresh
        $(document.body).on('checkout_error', function() {
            console.log('[AA Checkout] Checkout error occurred');
            hideProcessingMessage();

            // Keep button disabled but change text
            const $btn = $('#place_order');
            $btn.text('Error - Please Refresh Page');

            // Add refresh message
            const $notices = $('.woocommerce-notices-wrapper').first();
            if ($notices.length && !$notices.find('.aa-refresh-notice').length) {
                $notices.append('<div class="woocommerce-info aa-refresh-notice" style="margin-top:10px;">' +
                    'If the error persists, please <a href="javascript:location.reload()">refresh the page</a> and try again.' +
                    '</div>');
            }

            // DO NOT reset isSubmitting here - prevents double payment!
        });

        // Track when order is created (via AJAX response)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.indexOf('wc-ajax=checkout') !== -1) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.result === 'success' || response.order_id) {
                        orderCreated = true;
                        pendingOrderId = response.order_id || 'unknown';
                        sessionStorage.setItem('aa_pending_order_id', pendingOrderId);
                        console.log('[AA Checkout] Order created:', pendingOrderId);

                        // Update overlay for redirect
                        if (response.redirect) {
                            updateProcessingMessage('Payment successful!', 'Redirecting...', true);
                        }
                    }

                    // Handle 3DS redirect
                    if (response.result === 'success' && response.redirect && response.redirect.includes('stripe')) {
                        updateProcessingMessage('Verifying your card...', 'Step 2 of 3 - 3D Secure');
                        // Monitor for 3DS completion
                        start3DSCompletionMonitor();
                    }
                } catch (e) {
                    // Not JSON or parsing error, ignore
                }
            }
        });

        // Check for pending order from previous session
        const storedOrderId = sessionStorage.getItem('aa_pending_order_id');
        if (storedOrderId) {
            orderCreated = true;
            pendingOrderId = storedOrderId;
            console.log('[AA Checkout] Restored pending order:', pendingOrderId);
        }
    }

    /**
     * Log checkout events for debugging
     */
    function initCheckoutLogging() {
        $(document.body).on('updated_checkout', function() {
            console.log('[AA Checkout] Checkout updated');
            // Re-add idempotency key field if lost during update
            if (!$('input[name="aa_idempotency_key"]').length) {
                $('<input type="hidden" name="aa_idempotency_key" value="' + checkoutIdempotencyKey + '">').appendTo('form.checkout');
            }
        });

        $(document.body).on('payment_method_selected', function() {
            console.log('[AA Checkout] Payment method selected');
        });

        $('form.checkout').on('checkout_place_order', function() {
            console.log('[AA Checkout] Order being placed with key:', checkoutIdempotencyKey);
            return true;
        });
    }

    /**
     * Add CSS for button states
     */
    function addStyles() {
        $('<style>' +
            '#place_order.aa-processing { opacity: 0.7; cursor: not-allowed; }' +
            '#place_order:disabled { background-color: #ccc !important; }' +
            '.aa-refresh-notice { background-color: #f0f0f0; border-left-color: #0073aa; }' +
        '</style>').appendTo('head');
    }

    // Initialize on document ready
    $(document).ready(function() {
        console.log('[AA Checkout] Initializing v2.3 - Double Payment Protection + Loading Overlay');

        addStyles();
        initLoadingOverlay();
        check3DSReturn();
        initPaymentOptionHandler();
        initSubmitProtection();
        initCheckoutLogging();

        // Expose reset function for manual recovery
        window.aaResetCheckout = resetCheckoutState;
    });

})(jQuery);
