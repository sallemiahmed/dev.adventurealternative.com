<?php

// disable default display for our custom order item's meta
add_filter('woocommerce_order_item_get_formatted_meta_data', function( $metas_to_display ){

    return array_filter( $metas_to_display, function( $meta ){
        
        return ! in_array( $meta->key, [ 'start_date', 'end_date' ] );
    
    });

}, 100 );

// apply our layout for displaying order item's meta
add_action('woocommerce_order_item_meta_start', function( $item_id, $item, $order ){
    
    $metas_to_display = [];
    
    if( is_trip_product( $item->get_product_id() ) ){
        
        $start_date = $item->get_meta('start_date');
        
        if( ! empty( $start_date ) )
            $metas_to_display[] = [ 'label' => 'Start date', 'value' => wc_format_datetime( new WC_DateTime( $start_date ) ) ];
        
        $end_date = $item->get_meta('end_date');
        
        if( ! empty( $end_date ) )
            $metas_to_display[] = [ 'label' => 'End date', 'value' => wc_format_datetime( new WC_DateTime( $end_date ) ) ];
        
    }
    
    if( ! empty( $metas_to_display ) ){
    
        echo '<div class="trip-meta-container">';
        
        foreach( $metas_to_display as $meta_to_display ){
            
            echo sprintf( '<div><b>%s</b>: %s</div>', esc_html( $meta_to_display['label'] ), esc_html( $meta_to_display['value'] ) );
        
        }
        
        echo '</div>';
    
    }

}, 1, 3 );

// ajax call to update order quantities (add/modify extras)
add_action( 'wp_ajax_aa_update_order_quantities', function(){
    
    // check the nonce
    check_ajax_referer('order-modify-extras-nonce-value', 'aa_nonce');
    
    $order_id = intval($_POST['aa_order_id']);
    
    $order = wc_get_order($order_id);
    $trip_order = new AA_Trip_Order( $order );
    $order_extras = new AA_Trip_Order_Extras( $order );
    
    if( $trip_order->is_editable() && $trip_order->current_user_can_edit() )
        $order_extras->update_quantities( $_POST['aa_quantities'], false );
    
    ob_start();
    
    include get_stylesheet_directory() . '/src/templates/order-table.php';
    
    $table_html = ob_get_clean();
    
    wp_send_json_success( [
        'message' => 'Quantity updated',
        'table' => $table_html
    ]);
    
    wp_die();

});