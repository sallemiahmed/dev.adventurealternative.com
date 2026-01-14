<?php

// Get rates once per day from the API and save them to WP database so that we don't need to call API multiple times per day
function aa_get_currency_rates_from_api(){
	
	$data = get_option( 'aa_api_currency_rates' ) ?: [];
	
	if( ! isset( $data['date'] ) || $data['date'] != date('Y-m-d') ){
		
		$data = [
			'date' => date('Y-m-d'),
			'currencies' => []
		];
	
		$curl = curl_init( 'https://api.freecurrencyapi.com/v1/latest?base_currency=GBP&currencies=EUR,USD' );
		
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'apikey: fca_live_AcjegblfhN1fsekNta3WAZvJjwEQuaIfBohurNjn' ]);
		curl_setopt( $curl, CURLOPT_TIMEOUT, 2 ); //timeout in seconds

		$response = curl_exec( $curl );
		
		if( ! empty( $response ) && strpos( $response, '"data"' ) ){
			
			$api_rates = json_decode( $response, true ) ?: [];
			
			if( ! empty( $api_rates['data'] ) )
				$data['currencies'] = $api_rates['data'];
			
		}
		
		update_option( 'aa_api_currency_rates', $data );
		
	}
	
	return $data['currencies'] ?? [];
	
}

// Get currently selected currency in the checkout page
function aa_get_checkout_currency(){
	
	return AA_Session::get('checkout_currency') ?: 'gbp';
	
}

function aa_get_currencies(){
	
	// Define the available currencies and their default exchange rates
    $currencies = [
        'gbp' => [
        	'exchange_rate' => 1, // Default currency
        ],
        'usd' => [
        	'exchange_rate' => 1.25,
        ],
        'eur' => [
        	'exchange_rate' => 1.15
        ]
    ];
    
    // try to set latest rates from saved API data
    foreach( aa_get_currency_rates_from_api() as $currency_id => $rate ){
    	
    	$currency_id = strtolower( $currency_id );
    	
    	if( isset( $currencies[ $currency_id ] ) && is_numeric( $rate ) )
    		$currencies[ $currency_id ]['exchange_rate'] = $rate;
    
    }
    
	return $currencies;
	
}

// Maybe save selected currency in session
add_action('wp_loaded', function(){

    $currencies = aa_get_currencies();
    
    $session_currency = AA_Session::get('checkout_currency');
    
	// If we are sending selected currency via POST, save its value to the session
    if( ! empty( $_POST['payment_currency'] ) && ! empty( $currencies[ $_POST['payment_currency'] ] ) && $_POST['payment_currency'] != $session_currency )
        AA_Session::set( 'checkout_currency', $_POST['payment_currency'] );
    // If we have currency saved as cookie (from prev orders), use it
    elseif( empty( $session_currency ) && ! empty( $_COOKIE['aa_currency'] ) )
        AA_Session::set( 'checkout_currency', $_COOKIE['aa_currency'] );

}, 1 );

// Convert currencies in the checkout page
add_action('woocommerce_cart_loaded_from_session', function( $cart ){
    
    $currencies = aa_get_currencies();
    $selected_currency = aa_get_checkout_currency();
    
    $rate = $currencies[ $selected_currency ]['exchange_rate'] ?? 1;
    
    if( ! is_numeric( $rate ) )
    	$rate = 1;
    
    // only modify cart prices if rate is not set to 1
    if( $rate != 1 ){
    	
	    // Modify the cart totals based on the exchange rate
	    foreach( $cart->get_cart() as $cart_item_key => $cart_item ){
	    	
	        $product = $cart_item['data'];
    
    		$product->set_price( $product->get_price() * $rate );
	        
	    }
	    
	    AA_Session::set( 'converted_to_currency', $selected_currency );
	    
	    $cart->calculate_totals();
    	
    }
    elseif( AA_Session::get('converted_to_currency') && $selected_currency != AA_Session::get('converted_to_currency') ){ // when converted back to default currency
    	AA_Session::set( 'converted_to_currency', null );
    	$cart->calculate_totals();
    }
    
});
    
// Update the currency symbol in woocommerce settings
add_filter('woocommerce_currency', function( $currency ){
    
    if( is_checkout() || ! empty( $_POST['payment_currency'] ) || did_action('woocommerce_checkout_order_processed') || strpos( ( $_GET['wc-ajax'] ?? '' ), 'stripe' ) )
    	return strtoupper( aa_get_checkout_currency() );
    else
    	return $currency;
    
});