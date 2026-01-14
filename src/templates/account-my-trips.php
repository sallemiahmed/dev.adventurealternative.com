<?php

/*
Template for displaying "My trips" table
*/

$trips_page = empty( $_GET['trips_page'] ) ? 1 : absint( $_GET['trips_page'] );
$trip_orders = wc_get_orders([
    'customer' => get_current_user_id(),
    //'meta_key' => 'has_trip_product', // ensure that we are getting only trip orders
    //'meta_value' => 1, // ensure that we are getting only trip orders
    'page'     => $trips_page,
    'paginate' => true
]);
$has_trip_orders = ! empty( $trip_orders->total );
$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';

// define which columns we should show in a orders table
add_filter('woocommerce_account_orders_columns', function( $columns ){

    return [
        'order-number'      => __( 'Order', 'woocommerce' ),
        'order-status'      => __( 'Status', 'woocommerce' ),
        'order-date'        => __( 'Date created', 'woocommerce' ),
        'order-start-date'  => __( 'Start date', 'woocommerce' ),
        'order-end-date'    => __( 'End date', 'woocommerce' ),
        'order-total'       => __( 'Total', 'woocommerce' ),
        'order-unpaid'      => __( 'Unpaid', 'woocommerce' ),
        'trip-details'      => __( 'Trip Details', 'woocommerce' ),
        'order-actions'     => __( 'Actions', 'woocommerce' ),
    ];

}, 10);


?>
<section id="my-trips">
    <div class="container">
        <h2>My trips</h2>
        <?php
        
        do_action( 'woocommerce_before_account_orders', $has_trip_orders );
        
        if( $has_trip_orders ){
            
            ?>
            <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
                <thead>
                    <tr>
                        <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                            <th class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    foreach ( $trip_orders->orders as $customer_order ) {
                        $order      = wc_get_order( $customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                        $item_count = $order->get_item_count() - $order->get_item_count_refunded();
                        
                        $trip_order = new AA_Trip_Order( $order );
                        $order_transactions = $trip_order->get_transactions();
                        $trip_item_data = $trip_order->get_trip_product();
                        $trip_item = $trip_item_data['item'] ?? false;
                        
                        ?>
                        <tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $order->get_status() ); ?> order">
                            <?php foreach ( wc_get_account_orders_columns() as $column_id => $column_name ) : ?>
                                <td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
                                    <?php 
                                    
                                    if( has_action( 'woocommerce_my_account_my_orders_column_' . $column_id ) ){
                                        
                                        do_action( 'woocommerce_my_account_my_orders_column_' . $column_id, $order ); 
                                        
                                    }
                                    elseif( 'order-number' === $column_id ){
                                        
                                        echo sprintf( 
                                            '<a href="%s">%s</a>',
                                            esc_url( $order->get_view_order_url() ),
                                            esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() )
                                        );
                                        
                                    }
                                    elseif( 'order-date' === $column_id ){
                                        
                                        echo sprintf( 
                                            '<time datetime="%s">%s</time>',
                                            esc_attr( $order->get_date_created()->date( 'c' ) ),
                                            esc_html( wc_format_datetime( $order->get_date_created() ) )
                                        );
                                        
                                    }
                                    elseif( 'order-start-date' === $column_id ){
                                        
                                        $start_date = $trip_item_data['start_date'] ? new WC_DateTime( $trip_item_data['start_date'] ) : false;
                                        
                                        echo sprintf( 
                                            '<time datetime="%s">%s</time>',
                                            esc_attr( $start_date ? $start_date->date('c') : '' ),
                                            esc_html( $start_date ? wc_format_datetime( $start_date ) : '-' )
                                        );
                                        
                                    }
                                    elseif( 'order-end-date' === $column_id ){
                                        
                                        $end_date = $trip_item_data['end_date'] ? new WC_DateTime( $trip_item_data['end_date'] ) : false;
                                        
                                        echo sprintf( 
                                            '<time datetime="%s">%s</time>',
                                            esc_attr( $end_date ? $end_date->date('c') : '' ),
                                            esc_html( $end_date ? wc_format_datetime( $end_date ) : '-' )
                                        );
                                        
                                    }
                                    elseif( 'order-status' === $column_id ){
                                        
                                        echo esc_html( wc_get_order_status_name( $order->get_status() ) );
                                        
                                    }
                                    elseif( 'order-total' === $column_id ){
                                        
                                        echo $order->get_formatted_order_total();
                                        
                                    }
                                    elseif( 'order-unpaid' === $column_id ){
                                        
                                        echo $order_transactions->wc_price( $order_transactions->get_total_to_pay() );
                                        
                                    }
                                    elseif( 'order-actions' === $column_id ){
                                        
                                        $actions = wc_get_account_orders_actions( $order );
                                        
                                        if ( ! empty( $actions ) ) {
                                            foreach ( $actions as $key => $action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                                                echo '<a href="' . esc_url( $action['url'] ) . '" class="woocommerce-button' . esc_attr( $wp_button_class ) . ' button ' . sanitize_html_class( $key ) . '">' . esc_html( $action['name'] ) . '</a>';
                                            }
                                        }
                                        
                                    }
                                    
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>
            
            <?php if ( 1 < $trip_orders->max_num_pages ) : ?>
                <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
                    <?php if ( 1 !== $trips_page ) : ?>
                        <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $trips_page - 1 ) ); ?>"><?php esc_html_e( 'Previous', 'woocommerce' ); ?></a>
                    <?php endif; ?>

                    <?php if ( intval( $trip_orders->max_num_pages ) !== $trips_page ) : ?>
                        <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button<?php echo esc_attr( $wp_button_class ); ?>" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $trips_page + 1 ) ); ?>"><?php esc_html_e( 'Next', 'woocommerce' ); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php
            
        }
        else{
            
            wc_print_notice( esc_html__( 'No order has been made yet.', 'woocommerce' ) . ' <a class="woocommerce-Button wc-forward button' . esc_attr( $wp_button_class ) . '" href="' . esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ) . '">' . esc_html__( 'Browse products', 'woocommerce' ) . '</a>', 'notice' );
            
        }
        
        do_action( 'woocommerce_after_account_orders', $has_trip_orders );
        
        ?>
		<div class="historical-disclaimer">
			Looking for information about trips booked before September 2024? Please <a href="/enquiries/">Contact Us</a>.
		</div>
    </div>
</section>