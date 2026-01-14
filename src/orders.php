<?php

// WC checks if order is paid by getting this value. Ensure we return false for existing order if order is still unpaid via transactions
add_filter('woocommerce_order_get_date_paid', function( $date_paid, $order ){

    if( ( new AA_Order_Transactions( $order ) )->is_order_unpaid() )
        return false;
    
    return $date_paid;

}, 1000, 2 );

// add unpaid and paid rows to totals section if order has transactions
// also hide WC version of payment method info, if there are more than 1 transactions
add_filter('woocommerce_get_order_item_totals', function( $total_rows, $order, $tax_display ){

    $trip_order = new AA_Trip_Order( $order );
    $transactions = $trip_order->get_transactions();
        
    if( $transactions->exists() ){
        
        $total_rows['order_total_paid'] = array(
            'label' => __( 'Paid:', 'woocommerce' ),
            'value' => $transactions->wc_price( $transactions->get_transactions_total() ),
        );
        
        if( $transactions->is_order_unpaid() ){
        
            $total_rows['order_total_unpaid'] = array(
                'label' => __( 'Unpaid:', 'woocommerce' ),
                'value' => $transactions->wc_price( $transactions->get_total_to_pay() ) . $trip_order->get_pay_button_html(),
            );
        
        }
        
    }
    
    if( $transactions->count() > 1 )
        unset( $total_rows['payment_method'] );
    
    return $total_rows;

}, 1000, 3 );