<?php

// Redirect to checkout page after adding a product to the cart
add_filter('woocommerce_add_to_cart_redirect', function( $url, $added_product ){
    
    // lets try to force step 1 when new trip product is added to the cart
    if( is_a( $added_product, 'WC_Product') && is_trip_product( $added_product ) && ! wp_doing_ajax() )
        AA_Session::set( 'checkout_step', 1 );
    
    return aa_get_cart_count() ? wc_get_checkout_url() : site_url();
    
}, 1000, 2 );

// Change all cart URLs to checkout URLs
add_filter('woocommerce_get_cart_url', function($url){
    return aa_get_cart_count() ? wc_get_checkout_url() : $url;
});

// Skip the cart page and redirect to checkout if cart URL is accessed
add_action('template_redirect', function(){
    if( is_cart() ){
        wp_redirect( aa_get_cart_count() ? wc_get_checkout_url() : site_url() );
        exit;
    }
});

// prevent adding more than 1 trip product to the cart - ADDED BY DEJAN
if( ! empty( $_REQUEST['add-to-cart'] ) && is_numeric( wp_unslash( $_REQUEST['add-to-cart'] ) ) ){
    
    // WC uses this hook with a priority 20, so let us put this check just before WC runs its code
    add_action('wp_loaded', function(){

        if( is_trip_product( $_REQUEST['add-to-cart'] ) && AA_Checkout::get_instance()->get_trip_product() ){
            
            wc_add_notice( 'You cannot add more than 1 trip product to the cart!', 'error' );
            
            wp_redirect( wc_get_checkout_url() );
            die;
            
        }

    }, 11 );
    
}

// we don't want wc notices to be created in session when we are doing updates to cart via ajax call
if( ! empty( $_POST['disable_wc_notices'] ) && wp_doing_ajax() ){

    add_filter('woocommerce_add_error', '__return_false', 100 );
    add_filter('woocommerce_add_success', '__return_false', 100 );
    add_filter('woocommerce_add_notice', '__return_false', 100 );

}

// do some tasks after cart is emptied
add_action('woocommerce_cart_emptied', function( $clear_persistent_cart ){

	// Connor to decide should custom data from session be removed when cart is emptied?
    // 		AA_Session::clear_all();
    // or to remove just some session data:
     		AA_Session::set( 'checkout_step', null );
     		AA_Session::set( 'data_step', null );
     		AA_Session::set( 'steps_data.1.num_passengers', 0 );

}, 10 );