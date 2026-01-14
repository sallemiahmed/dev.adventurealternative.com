<?php
/**
 * Payment Debug Logger
 *
 * Comprehensive logging for payment flow troubleshooting
 * Add this file to: wp-content/themes/generatepress-child/src/classes/AA_Payment_Logger.php
 *
 * @author Ahmed Sallemi
 * @date January 2026
 */

class AA_Payment_Logger {

    private static $log_file = 'aa-payment-debug.log';
    private static $enabled = true;

    /**
     * Log a payment event
     */
    public static function log( $event, $data = [], $level = 'INFO' ) {

        if ( ! self::$enabled ) return;

        $log_entry = [
            'timestamp' => current_time( 'Y-m-d H:i:s' ),
            'level'     => $level,
            'event'     => $event,
            'data'      => $data,
            'user_id'   => get_current_user_id(),
            'ip'        => self::get_client_ip(),
            'request'   => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'uri'    => $_SERVER['REQUEST_URI'] ?? '',
            ]
        ];

        // Log to WooCommerce logger
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $context = [ 'source' => 'aa-payment-debug' ];
            $message = sprintf( '[%s] %s | %s', $level, $event, json_encode( $data ) );

            switch ( $level ) {
                case 'ERROR':
                    $logger->error( $message, $context );
                    break;
                case 'WARNING':
                    $logger->warning( $message, $context );
                    break;
                default:
                    $logger->info( $message, $context );
            }
        }

        // Also log to custom file for easy access (with error handling)
        try {
            $log_dir = WP_CONTENT_DIR . '/aa-logs';

            // Try to create directory if it doesn't exist
            if ( ! file_exists( $log_dir ) ) {
                @mkdir( $log_dir, 0755, true );
            }

            // Only write if directory is writable
            if ( is_dir( $log_dir ) && is_writable( $log_dir ) ) {
                $log_path = $log_dir . '/' . self::$log_file;
                $log_line = json_encode( $log_entry ) . PHP_EOL;
                @file_put_contents( $log_path, $log_line, FILE_APPEND | LOCK_EX );
            }
            // Silently fail if directory is not writable - don't break checkout!
        } catch ( Exception $e ) {
            // Silently fail - logging should never break checkout
        }

    }

    /**
     * Log payment initiation
     */
    public static function log_payment_start( $order_id, $payment_option, $amount ) {
        self::log( 'PAYMENT_START', [
            'order_id'       => $order_id,
            'payment_option' => $payment_option,
            'amount'         => $amount,
            'post_data'      => self::sanitize_post_data( $_POST ),
        ]);
    }

    /**
     * Log Stripe request
     */
    public static function log_stripe_request( $order_id, $stripe_amount, $payment_intent = '' ) {
        self::log( 'STRIPE_REQUEST', [
            'order_id'       => $order_id,
            'stripe_amount'  => $stripe_amount,
            'payment_intent' => $payment_intent,
        ]);
    }

    /**
     * Log 3DS redirect
     */
    public static function log_3ds_redirect( $order_id, $redirect_url ) {
        self::log( '3DS_REDIRECT', [
            'order_id'     => $order_id,
            'redirect_url' => $redirect_url,
        ]);
    }

    /**
     * Log 3DS return
     */
    public static function log_3ds_return( $order_id, $status, $params = [] ) {
        self::log( '3DS_RETURN', [
            'order_id' => $order_id,
            'status'   => $status,
            'params'   => $params,
        ], $status === 'succeeded' ? 'INFO' : 'WARNING' );
    }

    /**
     * Log payment success
     */
    public static function log_payment_success( $order_id, $transaction_id, $amount ) {
        self::log( 'PAYMENT_SUCCESS', [
            'order_id'       => $order_id,
            'transaction_id' => $transaction_id,
            'amount'         => $amount,
        ]);
    }

    /**
     * Log payment failure
     */
    public static function log_payment_failure( $order_id, $error_message, $error_code = '' ) {
        self::log( 'PAYMENT_FAILURE', [
            'order_id'      => $order_id,
            'error_message' => $error_message,
            'error_code'    => $error_code,
        ], 'ERROR' );
    }

    /**
     * Log transaction sync issue
     */
    public static function log_sync_issue( $order_id, $issue, $expected, $actual ) {
        self::log( 'SYNC_ISSUE', [
            'order_id' => $order_id,
            'issue'    => $issue,
            'expected' => $expected,
            'actual'   => $actual,
        ], 'ERROR' );
    }

    /**
     * Log order state change
     */
    public static function log_order_state( $order_id, $old_status, $new_status ) {
        self::log( 'ORDER_STATE_CHANGE', [
            'order_id'   => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]);
    }

    /**
     * Get client IP
     */
    private static function get_client_ip() {
        $ip_keys = [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' ];
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                return $ip;
            }
        }
        return 'unknown';
    }

    /**
     * Sanitize POST data for logging (remove sensitive info)
     */
    private static function sanitize_post_data( $data ) {
        $sensitive_keys = [ 'password', 'card', 'cvc', 'cvv', 'stripe_source', 'wc-stripe-payment-token' ];
        $sanitized = [];

        foreach ( $data as $key => $value ) {
            $is_sensitive = false;
            foreach ( $sensitive_keys as $sensitive ) {
                if ( stripos( $key, $sensitive ) !== false ) {
                    $is_sensitive = true;
                    break;
                }
            }
            $sanitized[ $key ] = $is_sensitive ? '[REDACTED]' : $value;
        }

        return $sanitized;
    }

    /**
     * Get recent logs for an order
     */
    public static function get_order_logs( $order_id, $limit = 50 ) {
        try {
            $log_dir = WP_CONTENT_DIR . '/aa-logs';
            $log_path = $log_dir . '/' . self::$log_file;

            if ( ! file_exists( $log_path ) || ! is_readable( $log_path ) ) {
                return [];
            }

            $logs = [];
            $lines = @file( $log_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

            if ( ! is_array( $lines ) ) {
                return [];
            }

            $lines = array_reverse( $lines );

            foreach ( $lines as $line ) {
                $entry = json_decode( $line, true );
                if ( $entry && isset( $entry['data']['order_id'] ) && $entry['data']['order_id'] == $order_id ) {
                    $logs[] = $entry;
                    if ( count( $logs ) >= $limit ) break;
                }
            }

            return array_reverse( $logs );
        } catch ( Exception $e ) {
            return [];
        }
    }

}
