<?php

// set trip category if admin forgot to set it
add_action( 'save_post', function( $post_id ){
	
	if( get_post_type( $post_id ) !== 'product' )
		return;

	$start_date = get_field( 'start_date', $post_id );
	
	if( empty( $start_date ) )
		return;

	if( ! has_term( 'trip-date', 'product_cat', $post_id ) )
		wp_set_object_terms( $post_id, 'trip-date', 'product_cat', true );
	
});