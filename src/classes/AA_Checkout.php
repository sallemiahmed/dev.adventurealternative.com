<?php

class AA_Checkout{
	
	public static $min_deposit_per_person = 200;
	public static $debug_data = [];
	
	protected static $instance = null;
	
	protected $payment_option = false;
	protected $enable_deposit = true;
	protected $num_passengers = 0;
	protected $min_deposit_amount = 200;
	protected $cart_total = 0;
	protected $custom_amount = 0;
	
	public function __construct(){
		
		if( ! empty( WC()->cart ) )
			$this->cart_total = WC()->cart->get_total('edit') ?: 0;
		
		$this->num_passengers = 1 + intval( AA_Session::get( 'steps_data.1.num_passengers', 0 ) );
		$this->min_deposit_amount = self::$min_deposit_per_person * $this->num_passengers;
		
		$this->payment_option = self::get_selected_payment_option( AA_Session::get( 'steps_data.3', 0 ), $this->cart_total, true );
		
		if( $this->payment_option == 'pay_custom' )
			$this->custom_amount = AA_Session::get( 'steps_data.3.custom_payment_amount', 0 );
		
		self::$debug_data[] = [
			'id' => 'new instance of AA_Checkout',
			'num_passengers' => $this->num_passengers,
			'min_deposit_amount' => $this->min_deposit_amount,
			'cart_total' => $this->cart_total,
			'payment_option' => $this->payment_option,
			'custom_amount' => $this->custom_amount
		];
		
	}
	
	public function is_enabled(){
	
		return $this->enable_deposit;
	
	}
	
	public function get_min_deposit_amount(){
	
		return $this->is_enabled() ? $this->min_deposit_amount : 0;
	
	}
	
	// get deposit amount based on which payment option customer selected
	public function get_deposit_amount(){
		
		if( ! $this->is_enabled() )
			return 0;
		
		if( $this->payment_option == 'pay_custom' ){
			return $this->custom_amount;
		}
		elseif( $this->payment_option == 'pay_deposit' ){
			return ( $this->min_deposit_amount > 0 && $this->min_deposit_amount < $this->cart_total ) ? $this->min_deposit_amount : 0;
		}
		else
			return 0;
	
	}
	
	// get trip product from the cart
	public function get_trip_product(){
		
		if( ! empty( WC()->cart ) ){
		
			foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
				
				if( ! empty( $cart_item['data'] ) && is_trip_product( $cart_item['data'] ) )
					return $cart_item['data'];
			
			}
		
		}
		
		return false;
		
	}
	
	// based on supplied $data, calculate allowed payment option and return it. Deposit should be allowed only if there are no transactions yet.
	public static function get_selected_payment_option( $data, $max_amount = false, $allow_deposit = false ){
		
		if( ! empty( $data['custom_payment_amount'] ) && is_numeric( $data['custom_payment_amount'] ) && $data['custom_payment_amount'] > 0 && empty( $data['payment_option'] ) )
			$payment_option = 'pay_custom';
		else
			$payment_option = $data['payment_option'] ?? '';
		
		// check if custom amount is set and is valid
		if( $payment_option == 'pay_custom' ){
			
			$custom_amount = $data['custom_payment_amount'] ?? 0;
			
			if( ! ( is_numeric( $custom_amount ) && $custom_amount > 0 && ( ! $max_amount || $custom_amount <= $max_amount ) ) ){
				
				self::$debug_data[] = [
					'id' => 'get_selected_payment_option - option is custom amount, but amount is not valid, set option to either deposit or full payment',
					'custom_amount' => $custom_amount,
					'max_amount' => $max_amount,
					'allow_deposit' => $allow_deposit
				];
				
				$payment_option = $allow_deposit ? 'pay_deposit' : 'pay_full'; // if custom amount is not valid, force min deposit to be paid
				
			}
			
		}
		
		if( $payment_option == 'pay_deposit' && ! $allow_deposit ){
			
			self::$debug_data[] = [
				'id' => 'get_selected_payment_option - option is deposit, but deposit is not allowed, set option to full payment',
				'payment_option' => $payment_option
			];
			
			$payment_option = '';
			
		}
		
		if( empty( $payment_option ) ){
			
			self::$debug_data[] = [
				'id' => 'get_selected_payment_option - payment option is not set. It should be pay_deposit or pay_custom or pay_full',
				'custom_amount' => $custom_amount ?? 0,
				'max_amount' => $max_amount,
				'allow_deposit' => $allow_deposit,
				'payment_option' => $payment_option,
				'data' => $data
			];
			
		}
		
		return $payment_option;
		
	}
	
	// used to validate correct payment option values when submitting data from checkout or pay page.
	public static function get_selected_payment_option_errors( $data, $max_amount = false, $min_amount = false ){
		
		$errors = [];
		
		// maybe change 0.1 to 1?
		if( ! is_numeric( $min_amount ) || $min_amount == 0 )
			$min_amount = 0.1;
		
		if( empty( $data['payment_option'] ) ){
	        $errors[] = [ 'payment_option_requred', 'Please select how you would like to pay!' ];
	    }
	    elseif( $data['payment_option'] == 'pay_custom' ){
	        
	        if( empty( $data['custom_payment_amount'] ) || ! is_numeric( $data['custom_payment_amount'] ) ){
	            $errors[] = [ 'custom_payment_amount_required', 'Please define a desired custom amount you would like to pay!' ];
	        }
	        elseif( $data['custom_payment_amount'] < $min_amount ){
	            $errors[] = [ 'custom_payment_amount_min_error', sprintf( 'Custom amount cannot be lower than %s!', trim( strip_tags( wc_price( $min_amount ) ) ) ) ];
	        }
	        elseif( ! ( $data['custom_payment_amount'] >= $min_amount && ( ! $max_amount || $data['custom_payment_amount'] <= $max_amount ) ) ){
	            $errors[] = [ 'custom_payment_amount_max_error', sprintf( 'Custom amount cannot be greater than %s!', trim( strip_tags( wc_price( $max_amount ) ) ) ) ];
	        }
	        
	    }
	    
	    return $errors;
		
	}
	
	public static function needs_payment( $needs_payment = null ){
	
		if( self::get_selected_payment_option( $_POST ) == 'pay_later' )
	        return false;
	    else
	        return $needs_payment;
	
	}
	
	public static function get_instance(){
	
		if( ! isset( self::$instance ) )
			self::$instance = new self();
		
		return self::$instance;
	
	}
	
	public static function maybe_save_debug_data_to_trip_order( $trip_order, $action = '(unknown action)' ){
	
		if( ! empty( self::$debug_data ) ){
	        $trip_order->add_debug_data( 'CHECKOUT DEBUG DATA on ' . $action, self::$debug_data );
	        self::$debug_data = [];
	    }
	
	}
	
}