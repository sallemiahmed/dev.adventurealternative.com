<?php

// we need to hide some stripe info when order has deposit
add_filter('wc_stripe_hide_display_order_payout', 'hide_stripe_features_if_order_has_deposit', 1000, 2 );
add_filter('wc_stripe_hide_display_order_fee', 'hide_stripe_features_if_order_has_deposit', 1000, 2 );

function hide_stripe_features_if_order_has_deposit( $hide, $order_id ){

    if( get_post_meta( $order_id, 'deposit_is_paid', true ) )
        return true;

    return $hide;

}