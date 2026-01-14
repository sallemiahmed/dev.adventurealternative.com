<?php
/**
 * Idempotency Key Support for Checkout
 *
 * Prevents duplicate orders when:
 * - User clicks "Place Order" multiple times
 * - 3DS redirect returns and form resubmits
 * - Network issues cause retry
 *
 * @author Ahmed Sallemi
 * @date January 2026
 */

// Validate idempotency key and prevent duplicate orders
add_action('woocommerce_after_checkout_validation', function($posted, $errors) {

    $idempotency_key = isset($_POST['aa_idempotency_key']) ? sanitize_text_field($_POST['aa_idempotency_key']) : '';

    if (empty($idempotency_key)) {
        // No key provided - this is fine for backwards compatibility
        return;
    }

    // Check if an order with this idempotency key already exists
    $existing_orders = wc_get_orders([
        'meta_key' => 'aa_idempotency_key',
        'meta_value' => $idempotency_key,
        'limit' => 1,
        'return' => 'ids'
    ]);

    if (!empty($existing_orders)) {
        $existing_order_id = $existing_orders[0];
        $existing_order = wc_get_order($existing_order_id);

        if ($existing_order) {
            // Log this duplicate attempt
            error_log("[AA Checkout] Duplicate order prevented. Idempotency key: {$idempotency_key}, Existing order: #{$existing_order_id}");

            // Check order status
            $status = $existing_order->get_status();

            if (in_array($status, ['pending', 'failed'])) {
                // Order exists but not paid - allow retry but use existing order
                // We'll handle this in the order creation hook
                $GLOBALS['aa_existing_order_id'] = $existing_order_id;
            } else {
                // Order exists and is paid/processing - block duplicate
                $errors->add(
                    'duplicate_order',
                    sprintf(
                        __('An order has already been created. <a href="%s">View your order #%d</a> or <a href="%s">view all orders</a>.', 'woocommerce'),
                        $existing_order->get_view_order_url(),
                        $existing_order_id,
                        wc_get_account_endpoint_url('orders')
                    )
                );
            }
        }
    }

}, 5, 2); // Priority 5 - run early before other validations

// Store idempotency key when order is created
add_action('woocommerce_checkout_order_created', function($order) {

    $idempotency_key = isset($_POST['aa_idempotency_key']) ? sanitize_text_field($_POST['aa_idempotency_key']) : '';

    if (!empty($idempotency_key)) {
        update_post_meta($order->get_id(), 'aa_idempotency_key', $idempotency_key);
        update_post_meta($order->get_id(), 'aa_idempotency_timestamp', current_time('mysql'));

        error_log("[AA Checkout] Order #{$order->get_id()} created with idempotency key: {$idempotency_key}");
    }

}, 5); // Priority 5 - run early

// Store idempotency key for pay page (additional payments)
add_action('woocommerce_before_pay_action', function($order) {

    $idempotency_key = isset($_POST['aa_idempotency_key']) ? sanitize_text_field($_POST['aa_idempotency_key']) : '';

    if (!empty($idempotency_key)) {
        // For additional payments, store with a suffix to differentiate
        $payment_key = $idempotency_key . '_payment_' . time();
        update_post_meta($order->get_id(), 'aa_last_payment_idempotency_key', $payment_key);
    }

}, 5);

// Add idempotency key to order notes for debugging
add_action('woocommerce_pre_payment_complete', function($order_id, $transaction_id) {

    $order = wc_get_order($order_id);
    $idempotency_key = get_post_meta($order_id, 'aa_idempotency_key', true);

    if (!empty($idempotency_key) && !empty($transaction_id)) {
        $order->add_order_note(
            sprintf('Payment completed. Transaction: %s, Idempotency: %s', $transaction_id, substr($idempotency_key, 0, 20) . '...')
        );
    }

}, 2, 2);
