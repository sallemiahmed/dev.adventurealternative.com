/**
 * Adventure Alternative - Custom Stripe Checkout
 *
 * Handles card payment with 3DS for balance payments.
 *
 * @version 1.1.0 - Added loading overlay
 */

(function($) {
    'use strict';

    // Ensure params are available
    if (typeof aa_stripe_params === 'undefined') {
        console.error('[AA Stripe] Missing aa_stripe_params');
        return;
    }

    const params = aa_stripe_params;

    // Initialize Stripe
    const stripe = Stripe(params.publishable_key);

    // Create Stripe Elements
    const elements = stripe.elements({ locale: 'en-GB' });

    // Style for Stripe Element - LARGE and easy to use
    const cardStyle = {
        base: {
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '18px',
            lineHeight: '28px',
            '::placeholder': {
                color: '#9ca3af'
            }
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626'
        }
    };

    // Create card element
    const cardElement = elements.create('card', { style: cardStyle, hidePostalCode: true });

    // State
    let isProcessing = false;
    let currentAmount = parseFloat(params.balance_due);

    /**
     * Inject loading overlay CSS and HTML
     */
    function injectLoadingOverlay() {
        // Add CSS
        var css = '' +
            '.aa-loading-overlay {' +
            '    position: fixed;' +
            '    top: 0;' +
            '    left: 0;' +
            '    width: 100%;' +
            '    height: 100%;' +
            '    background: rgba(0, 0, 0, 0.7);' +
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
            '.aa-loading-text {' +
            '    color: #ffffff;' +
            '    font-size: 18px;' +
            '    margin-top: 20px;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '.aa-loading-step {' +
            '    color: rgba(255, 255, 255, 0.7);' +
            '    font-size: 14px;' +
            '    margin-top: 8px;' +
            '    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;' +
            '}' +
            '@keyframes aa-spin {' +
            '    0% { transform: rotate(0deg); }' +
            '    100% { transform: rotate(360deg); }' +
            '}';

        var styleEl = document.createElement('style');
        styleEl.textContent = css;
        document.head.appendChild(styleEl);

        // Add HTML
        var overlayHtml = '<div class="aa-loading-overlay" id="aa-loading-overlay">' +
            '<div class="aa-loading-spinner"></div>' +
            '<div class="aa-loading-text" id="aa-loading-text">Processing...</div>' +
            '<div class="aa-loading-step" id="aa-loading-step">Please wait</div>' +
            '</div>';

        $('body').append(overlayHtml);
    }

    /**
     * Show loading overlay with message
     */
    function showLoadingOverlay(message, step) {
        $('#aa-loading-text').text(message || 'Processing...');
        $('#aa-loading-step').text(step || 'Please wait');
        $('#aa-loading-overlay').addClass('active');
    }

    /**
     * Update loading overlay message
     */
    function updateLoadingOverlay(message, step) {
        $('#aa-loading-text').text(message || 'Processing...');
        if (step) {
            $('#aa-loading-step').text(step);
        }
    }

    /**
     * Hide loading overlay
     */
    function hideLoadingOverlay() {
        $('#aa-loading-overlay').removeClass('active');
    }

    /**
     * Initialize the payment form
     */
    function init() {
        console.log('[AA Stripe] Initializing custom payment form');
        console.log('[AA Stripe] Balance due:', params.formatted_balance);

        // Inject loading overlay
        injectLoadingOverlay();

        // Mount card element
        var cardContainer = document.getElementById('aa-card-element');
        if (cardContainer) {
            cardElement.mount('#aa-card-element');
            $('body').addClass('aa-custom-payment-active');
            console.log('[AA Stripe] Card element mounted');
        } else {
            console.error('[AA Stripe] Card container not found');
            return;
        }

        // Handle card errors
        cardElement.on('change', function(event) {
            var errorElement = document.getElementById('aa-card-errors');
            if (event.error) {
                errorElement.textContent = event.error.message;
            } else {
                errorElement.textContent = '';
            }
        });

        // Bind events
        bindEvents();
    }

    /**
     * Bind UI events
     */
    function bindEvents() {
        // Payment option toggle
        $('input[name="aa_payment_option"]').on('change', function() {
            var isCustom = $(this).val() === 'pay_custom';
            $('.aa-custom-amount-wrapper').toggle(isCustom);

            if (!isCustom) {
                currentAmount = parseFloat(params.balance_due);
                updateButtonText(currentAmount);
            }
        });

        // Custom amount input
        $('#aa_custom_amount').on('input', function() {
            var value = parseFloat($(this).val()) || 0;
            var maxAmount = parseFloat(params.balance_due);

            if (value > 0 && value <= maxAmount) {
                currentAmount = value;
                updateButtonText(value);
            }
        });

        // Submit button
        $('#aa-submit-payment').on('click', handlePayment);

        // Prevent form submission on enter
        $('#aa-stripe-payment-form').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                handlePayment();
            }
        });
    }

    /**
     * Update button text with amount
     */
    function updateButtonText(amount) {
        var formatted = params.currency_symbol + amount.toFixed(2);
        $('.aa-button-text').text('Pay ' + formatted);
    }

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        var messagesDiv = $('#aa-payment-messages');
        messagesDiv
            .removeClass('success error')
            .addClass(type)
            .text(message)
            .show();
    }

    /**
     * Set loading state
     */
    function setLoading(loading) {
        isProcessing = loading;
        var button = $('#aa-submit-payment');

        if (loading) {
            button.prop('disabled', true);
            $('.aa-button-text').hide();
            $('.aa-button-loading').show();
        } else {
            button.prop('disabled', false);
            $('.aa-button-text').show();
            $('.aa-button-loading').hide();
        }
    }

    /**
     * Handle payment submission
     */
    async function handlePayment() {
        if (isProcessing) {
            return;
        }

        // Validate amount
        var paymentOption = $('input[name="aa_payment_option"]:checked').val();
        var amount = parseFloat(params.balance_due);

        if (paymentOption === 'pay_custom') {
            amount = parseFloat($('#aa_custom_amount').val()) || 0;
            if (amount <= 0) {
                showMessage('Please enter a valid amount', 'error');
                return;
            }
            if (amount > parseFloat(params.balance_due)) {
                showMessage('Amount cannot exceed balance due', 'error');
                return;
            }
        }

        setLoading(true);
        showMessage('', '');

        console.log('[AA Stripe] Starting payment for amount:', amount);

        try {
            // Step 1: Create PaymentIntent
            showLoadingOverlay('Creating payment...', 'Step 1 of 3');

            var intentResponse = await fetch(params.ajax_url + 'create-payment-intent', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: params.order_id,
                    order_key: params.order_key,
                    amount: amount,
                }),
            });

            var intentData = await intentResponse.json();

            if (!intentResponse.ok || !intentData.client_secret) {
                throw new Error(intentData.message || 'Failed to create payment intent');
            }

            console.log('[AA Stripe] PaymentIntent created:', intentData.payment_intent_id);
            console.log('[AA Stripe] Amount:', intentData.formatted_amount);

            // Step 2: Confirm payment with Stripe.js (handles 3DS)
            updateLoadingOverlay('Processing payment...', 'Step 2 of 3 - Card verification');

            var result = await stripe.confirmCardPayment(
                intentData.client_secret,
                {
                    payment_method: {
                        card: cardElement,
                    },
                    return_url: params.return_url,
                }
            );

            if (result.error) {
                console.error('[AA Stripe] Payment error:', result.error);
                throw new Error(result.error.message || 'Payment failed');
            }

            var paymentIntent = result.paymentIntent;
            console.log('[AA Stripe] Payment confirmed:', paymentIntent.status);

            if (paymentIntent.status !== 'succeeded') {
                throw new Error('Payment was not successful. Status: ' + paymentIntent.status);
            }

            // Step 3: Confirm with our backend
            updateLoadingOverlay('Verifying payment...', 'Step 3 of 3 - Finalizing');

            var confirmResponse = await fetch(params.ajax_url + 'confirm-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: params.order_id,
                    order_key: params.order_key,
                    payment_intent_id: paymentIntent.id,
                }),
            });

            var confirmData = await confirmResponse.json();

            if (!confirmResponse.ok || !confirmData.success) {
                throw new Error(confirmData.message || 'Failed to confirm payment');
            }

            console.log('[AA Stripe] Payment recorded:', confirmData);

            // Success!
            updateLoadingOverlay('Payment successful!', 'Redirecting...');

            // Redirect after short delay
            setTimeout(function() {
                window.location.href = confirmData.redirect_url || params.return_url;
            }, 1500);

        } catch (err) {
            console.error('[AA Stripe] Error:', err);
            hideLoadingOverlay();
            showMessage(err.message || 'Payment failed. Please try again.', 'error');
            setLoading(false);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Small delay to ensure Stripe.js is fully loaded
        setTimeout(init, 100);
    });

})(jQuery);
