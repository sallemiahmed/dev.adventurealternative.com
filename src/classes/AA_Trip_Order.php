<?php

class AA_Trip_Order{
	
	protected $order = false;
	protected $is_valid = false;
	protected $trip_products = null; // we are using null here because null returns false for "isset" check
	protected $checkout_data = null; // we are using null here because null returns false for "isset" check
	protected $transactions = null; // we are using null here because null returns false for "isset" check
	
	public function __construct( $order ){
		
		if( is_numeric( $order ) )
			$this->order = wc_get_order( $order );
		if( is_a( $order, 'WC_Order' ) )
			$this->order = $order;
		
		// trip order is valid if it contains "has_trip_product" meta and its value is set to 1 (true)
		$this->is_valid = (bool) get_post_meta( $this->get_order_id(), 'has_trip_product', true );
		
	}
	
	public function is_valid(){
		
		return $this->is_valid;
		
	}
	
	public function get_order_id(){
		
		return $this->order ? $this->order->get_id() : 0;
		
	}
	
	public function get_order(){
		
		return $this->order;
		
	}
	
	public function get_transactions(){
		
		if( ! isset( $this->transactions ) )
			$this->transactions = new AA_Order_Transactions( $this->order );
		
		return $this->transactions;
		
	}
	
	// check if order is fully paid, by looking at order transactions
	public function is_paid(){
		
		return $this->get_transactions()->is_order_paid();
		
	}
	
	// get trip products and their data from the order. Not used directly, but can be used if code will start to support multiple trips in a cart
	public function get_trip_products(){
		
		if( ! isset( $this->trip_products ) ){
			
			$this->trip_products = [];
			
			if( ! $this->is_valid() )
				return $this->trip_products;
			
			foreach( $this->order->get_items() as $item_id => $item ){
		        
		        if( is_trip_product( $item->get_product_id() ) ){
		        	
		            $this->trip_products[ $item_id ] = [
		            	'item' => $item,
		            	'product_id' => $item->get_product_id(),
		            	'start_date' => $item->get_meta('start_date') ?: '',
		            	'end_date' => $item->get_meta('end_date') ?: ''
		            ];
		            
		        }
		        
		    }
			
		}
		
	    return $this->trip_products;
		
	}
	
	// get first trip products and related data from the order
	public function get_trip_product(){
		
		$trip_products = $this->get_trip_products();
		
		return array_shift( $trip_products );
		
	}
	
	// get checkout data
	public function get_checkout_data( $key = null, $return_default = null ){
		
		if( ! isset( $this->checkout_data ) )
			$this->checkout_data = get_post_meta( $this->get_order_id(), 'aa_checkout_data', true ) ?: [];
		
		return ( $key === null || $key === false ) ? $this->checkout_data : ( $this->checkout_data[ $key ] ?? $return_default );
		
	}
	
	// get checkout step data
	public function get_checkout_step_data( $step = 1, $key = null, $return_default = null ){
		
		$steps_data = $this->get_checkout_data( 'steps_data' ) ?: [];
		
		if( ! isset( $steps_data[ $step ] ) )
			return $return_default;
		
		return ( $key === null || $key === false ) ? $steps_data[ $step ] : ( $steps_data[ $step ][ $key ] ?? $return_default );
		
	}
	
	// get additional passengers
	public function get_additional_passengers(){
		
		return $this->get_checkout_data('passenger_details') ?? [];
		
	}
	
	// get num of passengers
	public function get_num_passengers(){
		
		return 1 + count( $this->get_additional_passengers() );
		
	}
	
	// get some rules for possible next payment
	public function get_next_payment_rules(){
		
		$transactions = $this->get_transactions();
		
		$total_to_pay = $transactions->get_total_to_pay();
		
		if( ! $total_to_pay )
			return false;
		
		return [
			'allowed_payment_options' => $this->get_allowed_payment_options_for_next_payment(),
			'num_passengers' => $this->get_num_passengers(),
			'min_payment_amount' => $this->get_next_payment_min_amount(),
			'max_payment_amount' => $total_to_pay
		];
		
	}
	
	// get min amount allowed for possible next payment
	public function get_next_payment_min_amount(){
		
		$transactions = $this->get_transactions();
		
		$total_to_pay = $transactions->get_total_to_pay();
		
		if( ! $total_to_pay )
			return 0;
		
		// Connor to decide here if change is needed for min amount logic for next payments. 
		// This code allow 100 as minimum for additional payments
		if( $transactions->exists() )
			$min_amount = 1;
		else
			$min_amount = AA_Checkout::$min_deposit_per_person * $this->get_num_passengers();
		
		return min( $min_amount, $total_to_pay );
		
	}
	
	// get max amount allowed for possible next payment
	public function get_next_payment_max_amount(){
		
		return $this->get_transactions()->get_total_to_pay();
		
	}
	
	// get HTML code for the "Pay" button
	public function get_pay_button_html( $button_text = 'Pay', $new_tab = false ){
		
		if( is_wc_endpoint_url('order-pay') )
			return '';
		
		$checkout_url = $this->order->get_checkout_payment_url();
		
		if( ! $this->is_paid() && ! empty( $checkout_url ) ){
			return sprintf( 
				' <a href="%s" class="woocommerce-button button pay"%s>%s</a>',
				esc_attr( $checkout_url ),
				$new_tab ? ' target="_blank"' : '',
				$button_text
			);
		}
		else
			return '';
		
	}
	
	// get allowed payment options for possible next payment
	public function get_allowed_payment_options_for_next_payment(){
		
		if( $this->is_paid() )
			return [];
		
		$allowed_payment_options = [ 'pay_custom', 'pay_full' ];
		
		if( empty( $this->get_transactions()->get() ) && $this->get_checkout_step_data( 3, 'payment_option' ) != 'pay_later' )
			$allowed_payment_options[] = 'pay_deposit';
		
		return $allowed_payment_options;
		
	}
	
	// check if the order is paid later
	public function is_paid_later(){
		
		if( $this->get_checkout_step_data( 3, 'payment_option' ) == 'pay_later' )
			return $this->get_transactions()->count() > 0;
		else
			return $this->get_transactions()->count() > 1;
		
	}
	
	// check if the selected trip is in future
	public function is_trip_in_future(){
		
		$trip_product = $this->get_trip_product();
		
		if( ! empty( $trip_product['start_date'] ) ){
		
			$start_date = new WC_DateTime( $trip_product['start_date'] );
			
			return ( $start_date && $start_date > ( new WC_DateTime() ) );
		
		}
		
		return false;
		
	}
	
	// check if the order is editable
	public function is_editable(){
		
		return $this->is_trip_in_future() && $this->get_order()->has_status( [ 'pending', 'processing', 'completed', 'on-hold' ] );
		
	}
	
	public function current_user_can_edit(){
		
		return is_user_logged_in() && ( get_current_user_id() == $this->order->get_customer_id() || current_user_can( 'edit_order', $this->order->get_id() ) );
		
	}
	
	public function add_debug_data( $id, $value ){
		
		$order_id = $this->get_order_id();
		
		$debug_data = get_post_meta( $order_id, 'aa_debug_data', true ) ?: [];
		
		$debug_data[] = [
			'id' => $id,
			'time' => current_time('mysql'),
			'data' => $value
		];
		
		update_post_meta( $order_id, 'aa_debug_data', $debug_data );
		
	}
	
}