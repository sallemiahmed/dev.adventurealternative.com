<?php
/**
 * Payment System Fixes
 *
 * Addresses:
 * 1. 3DS redirect auto-proceed issue
 * 2. Payment/order sync failsafes
 * 3. Enhanced debug logging
 *
 * Add this to: wp-content/themes/generatepress-child/src/payment-fixes.php
 * Include in functions.php: require_once __DIR__ . '/src/payment-fixes.php';
 *
 * @author Ahmed Sallemi
 * @date January 2026
 */

// Load the payment logger
require_once __DIR__ . '/classes/AA_Payment_Logger.php';

/**
 * FIX #1: 3DS Redirect Auto-Proceed
 *
 * After 3DS verification, Stripe redirects back with ?redirect_status=succeeded
 * This fix automatically completes the payment instead of requiring another click
 */
add_action( 'template_redirect', 'aa_handle_3ds_redirect_return', 5 );

function aa_handle_3ds_redirect_return() {

    // Check if this is a 3DS return (Stripe adds these params)
    if ( ! isset( $_GET['redirect_status'] ) ) {
        return;
    }

    $redirect_status = sanitize_text_field( $_GET['redirect_status'] );
    $payment_intent = isset( $_GET['payment_intent'] ) ? sanitize_text_field( $_GET['payment_intent'] ) : '';
    $payment_intent_client_secret = isset( $_GET['payment_intent_client_secret'] ) ? sanitize_text_field( $_GET['payment_intent_client_secret'] ) : '';

    // Log the 3DS return
    AA_Payment_Logger::log( '3DS_RETURN_DETECTED', [
        'redirect_status'              => $redirect_status,
        'payment_intent'               => $payment_intent,
        'payment_intent_client_secret' => substr( $payment_intent_client_secret, 0, 20 ) . '...',
        'full_url'                     => $_SERVER['REQUEST_URI'],
    ]);

    // Only handle successful 3DS verification
    if ( $redirect_status !== 'succeeded' ) {
        AA_Payment_Logger::log_payment_failure( 0, '3DS verification failed', $redirect_status );
        return;
    }

    // Find the order associated with this payment intent
    if ( empty( $payment_intent ) ) {
        return;
    }

    // Query for order with this payment intent
    $orders = wc_get_orders([
        'limit'      => 1,
        'meta_key'   => '_stripe_intent_id',
        'meta_value' => $payment_intent,
    ]);

    if ( empty( $orders ) ) {
        // Try alternate meta key
        $orders = wc_get_orders([
            'limit'      => 1,
            'meta_key'   => '_payment_intent_id',
            'meta_value' => $payment_intent,
        ]);
    }

    if ( empty( $orders ) ) {
        AA_Payment_Logger::log( '3DS_ORDER_NOT_FOUND', [
            'payment_intent' => $payment_intent,
        ], 'WARNING' );
        return;
    }

    $order = $orders[0];
    $order_id = $order->get_id();

    AA_Payment_Logger::log_3ds_return( $order_id, $redirect_status, [
        'payment_intent' => $payment_intent,
        'order_status'   => $order->get_status(),
    ]);

    // If order is still pending, trigger payment completion
    if ( $order->get_status() === 'pending' || $order->get_status() === 'failed' ) {

        AA_Payment_Logger::log( '3DS_AUTO_COMPLETING', [
            'order_id'     => $order_id,
            'order_status' => $order->get_status(),
        ]);

        // Add a flag to indicate this is a 3DS auto-completion
        update_post_meta( $order_id, '_aa_3ds_auto_completed', current_time( 'mysql' ) );

        // Trigger payment completion via Stripe webhook simulation
        // The WooCommerce Stripe plugin should handle this via the webhook,
        // but we add a fallback check
        $transaction_id = get_post_meta( $order_id, '_stripe_charge_id', true );

        if ( ! $transaction_id ) {
            $transaction_id = get_post_meta( $order_id, '_transaction_id', true );
        }

        // If we have a transaction ID, the payment was successful
        if ( $transaction_id ) {
            // Payment was captured, ensure order is processed
            if ( $order->get_status() === 'pending' ) {
                $order->payment_complete( $transaction_id );
                AA_Payment_Logger::log_payment_success( $order_id, $transaction_id, $order->get_total() );
            }
        } else {
            // Schedule a check for payment status
            wp_schedule_single_event( time() + 30, 'aa_verify_payment_status', [ $order_id ] );
        }

        // Redirect to order-received page to prevent double processing
        $redirect_url = $order->get_checkout_order_received_url();
        wp_safe_redirect( $redirect_url );
        exit;
    }
}

/**
 * Scheduled event to verify payment status
 */
add_action( 'aa_verify_payment_status', 'aa_do_verify_payment_status' );

function aa_do_verify_payment_status( $order_id ) {

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    AA_Payment_Logger::log( 'VERIFY_PAYMENT_STATUS', [
        'order_id'     => $order_id,
        'order_status' => $order->get_status(),
    ]);

    // Check if order is still pending
    if ( $order->get_status() !== 'pending' ) {
        return; // Already processed
    }

    // Check Stripe for payment status
    $payment_intent = get_post_meta( $order_id, '_stripe_intent_id', true );

    if ( ! $payment_intent ) {
        AA_Payment_Logger::log_sync_issue( $order_id, 'No payment intent found', 'payment_intent', 'null' );
        return;
    }

    // If we can't verify with Stripe API, add a note and alert admin
    $order->add_order_note( __( 'Payment verification pending. Please check Stripe dashboard.', 'aa-checkout' ) );

    // Send admin notification
    aa_send_payment_alert( $order_id, 'Payment verification required for 3DS order' );
}


/**
 * FIX #2: Payment/Order Sync Failsafes
 *
 * Ensure transactions are properly recorded even if hooks fail
 */

// Hook into payment complete to verify transaction was added
add_action( 'woocommerce_payment_complete', 'aa_verify_transaction_recorded', 9999 );

function aa_verify_transaction_recorded( $order_id ) {

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    $transaction_id = $order->get_transaction_id();
    $amount_paid = $order->get_total();

    AA_Payment_Logger::log_payment_success( $order_id, $transaction_id, $amount_paid );

    // Check if our custom transaction was recorded
    $trip_order = new AA_Trip_Order( $order );
    $transactions = $trip_order->get_transactions();
    $all_transactions = $transactions->get();

    // Find if a transaction with this transaction_id exists
    $found = false;
    foreach ( $all_transactions as $trans ) {
        if ( isset( $trans['transaction_id'] ) && $trans['transaction_id'] === $transaction_id ) {
            $found = true;
            break;
        }
    }

    if ( ! $found && $transaction_id ) {
        // Transaction was not recorded - this is the sync issue!
        AA_Payment_Logger::log_sync_issue( $order_id, 'Transaction not recorded', $transaction_id, 'missing' );

        // Attempt recovery - add the transaction manually
        $deposit_amount = get_post_meta( $order_id, 'deposit_amount', true );
        $deposit_is_paid = get_post_meta( $order_id, 'deposit_is_paid', true );
        $payment_type = ( ! $deposit_is_paid && $deposit_amount ) ? 'deposit' : 'balance';

        $transactions->add([
            'description'          => $payment_type == 'deposit' ? 'Deposit (Auto-Recovered)' : 'Payment (Auto-Recovered)',
            'payment_method'       => $order->get_payment_method() ?: '',
            'payment_method_title' => $order->get_payment_method_title() ?: '',
            'transaction_id'       => $transaction_id,
            'amount'               => $payment_type == 'deposit' ? $deposit_amount : $amount_paid,
        ]);

        AA_Payment_Logger::log( 'TRANSACTION_RECOVERED', [
            'order_id'       => $order_id,
            'transaction_id' => $transaction_id,
            'amount'         => $amount_paid,
            'type'           => $payment_type,
        ]);

        // Update deposit paid status
        if ( $payment_type == 'deposit' ) {
            update_post_meta( $order_id, 'deposit_is_paid', true );
        }

        // Notify admin of the recovery
        aa_send_payment_alert( $order_id, 'Transaction auto-recovered after sync issue' );
    }
}


/**
 * FIX #3: Enhanced Stripe webhook handling
 *
 * Add additional verification after Stripe webhook
 */
add_action( 'woocommerce_api_wc_stripe', 'aa_log_stripe_webhook', 5 );

function aa_log_stripe_webhook() {
    $payload = file_get_contents( 'php://input' );
    $event = json_decode( $payload, true );

    if ( $event && isset( $event['type'] ) ) {
        AA_Payment_Logger::log( 'STRIPE_WEBHOOK', [
            'event_type' => $event['type'],
            'event_id'   => $event['id'] ?? '',
        ]);
    }
}


/**
 * FIX #4: Order status change logging
 */
add_action( 'woocommerce_order_status_changed', 'aa_log_order_status_change', 10, 4 );

function aa_log_order_status_change( $order_id, $old_status, $new_status, $order ) {
    AA_Payment_Logger::log_order_state( $order_id, $old_status, $new_status );
}


/**
 * FIX #5: Payment gateway request logging
 */
add_filter( 'wc_stripe_generate_payment_request', 'aa_log_stripe_payment_request', 5, 2 );

function aa_log_stripe_payment_request( $payment_request_data, $order ) {
    AA_Payment_Logger::log_stripe_request(
        $order->get_id(),
        $payment_request_data['amount'] ?? 0,
        $payment_request_data['payment_method'] ?? ''
    );
    return $payment_request_data;
}


/**
 * FIX #6: Checkout submission logging
 */
add_action( 'woocommerce_checkout_order_processed', 'aa_log_checkout_order', 5, 3 );

function aa_log_checkout_order( $order_id, $posted_data, $order ) {
    $payment_option = AA_Checkout::get_selected_payment_option( $_POST, $order->get_total(), true );
    $deposit_amount = AA_Checkout::get_instance()->get_deposit_amount();

    AA_Payment_Logger::log_payment_start( $order_id, $payment_option, $deposit_amount ?: $order->get_total() );
}


/**
 * Send payment alert to admin
 */
function aa_send_payment_alert( $order_id, $message ) {
    $admin_email = get_option( 'admin_email' );
    $subject = sprintf( '[%s] Payment Alert - Order #%d', get_bloginfo( 'name' ), $order_id );

    $body = sprintf(
        "Payment Alert for Order #%d\n\n%s\n\nOrder URL: %s\n\nTime: %s",
        $order_id,
        $message,
        admin_url( 'post.php?post=' . $order_id . '&action=edit' ),
        current_time( 'Y-m-d H:i:s' )
    );

    wp_mail( $admin_email, $subject, $body );
}


/**
 * Add admin notice for orders with payment issues
 */
add_action( 'admin_notices', 'aa_payment_issue_admin_notice' );

function aa_payment_issue_admin_notice() {
    $screen = get_current_screen();

    if ( $screen && $screen->id === 'shop_order' && isset( $_GET['post'] ) ) {
        $order_id = intval( $_GET['post'] );
        $auto_completed = get_post_meta( $order_id, '_aa_3ds_auto_completed', true );

        if ( $auto_completed ) {
            echo '<div class="notice notice-info"><p>';
            printf(
                __( 'This order was auto-completed after 3DS verification at %s. Please verify the payment in Stripe.', 'aa-checkout' ),
                esc_html( $auto_completed )
            );
            echo '</p></div>';
        }
    }
}


/**
 * Add payment logs metabox to order page
 */
add_action( 'add_meta_boxes', 'aa_add_payment_logs_metabox' );

function aa_add_payment_logs_metabox() {
    add_meta_box(
        'aa_payment_logs',
        __( 'Payment Debug Logs', 'aa-checkout' ),
        'aa_render_payment_logs_metabox',
        'shop_order',
        'normal',
        'low'
    );
}

function aa_render_payment_logs_metabox( $post ) {
    $logs = AA_Payment_Logger::get_order_logs( $post->ID, 20 );

    if ( empty( $logs ) ) {
        echo '<p>No payment logs found for this order.</p>';
        return;
    }

    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Time</th><th>Level</th><th>Event</th><th>Details</th></tr></thead>';
    echo '<tbody>';

    foreach ( $logs as $log ) {
        $level_class = $log['level'] === 'ERROR' ? 'color:red;' : ( $log['level'] === 'WARNING' ? 'color:orange;' : '' );
        printf(
            '<tr><td>%s</td><td style="%s">%s</td><td>%s</td><td><pre style="margin:0;font-size:11px;">%s</pre></td></tr>',
            esc_html( $log['timestamp'] ),
            $level_class,
            esc_html( $log['level'] ),
            esc_html( $log['event'] ),
            esc_html( json_encode( $log['data'], JSON_PRETTY_PRINT ) )
        );
    }

    echo '</tbody></table>';
}
