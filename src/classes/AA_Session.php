<?php

class AA_Session{
	
	public static $data = null;
	
	// $keys support multidimensional array by using dot as separator
	public static function get( $keys = null, $default_value = null ){
		
		if( ! isset( self::$data ) )
			self::set_initial_data();
		
		if( ! isset( $keys ) || $keys === false )
			return self::$data;
		
		$keys_array = explode('.', (string) $keys );
		$keys_count = count( $keys_array );
		
		$data_ref = &self::$data;
		
		foreach( $keys_array as $key_index => $key ){
			
			if( ! isset( $data_ref[ $key ] ) )
				return $default_value;
			
			if( $key_index + 1 === $keys_count )
				return $data_ref[ $key ];
			else
				$data_ref = &$data_ref[ $key ];
		
		}
		
		return $default_value;
		
	}
	
	// $keys support multidimensional array by using dot as separator
	public static function set( $keys = null, $value = null ){
		
		if( ! isset( self::$data ) )
			self::set_initial_data();
		
		if( ! isset( $keys ) || $keys === false ){
			self::$data = $value;
		}
		else{
			
			$keys_array = explode('.', (string) $keys );
			$keys_count = count( $keys_array );
			
			$data_ref = &self::$data;
			
			foreach( $keys_array as $key_index => $key ){
				
				if( ! isset( $data_ref[ $key ] ) && $key_index + 1 !== $keys_count )
					$data_ref[ $key ] = [];
				
				if( $key_index + 1 === $keys_count )
					$data_ref[ $key ] = $value;
				else
					$data_ref = &$data_ref[ $key ];
			
			}
			
		}
		
		WC()->session->set( 'aa_session', self::$data );
		
	}
	
	// ensure WC()->session is initialized
	public static function set_initial_data(){
		
		if( empty( WC()->session ) )
			WC()->initialize_session();
		
		self::$data = WC()->session->get( 'aa_session' ) ?: [];
		
	}
	
	public static function clear_all(){
		
		self::set( null, [] );
		
	}
	
	public static function get_num_passengers( $default_value = 0 ){
	    
	    return self::get( 'steps_data.1.num_passengers', $default_value );
		
	}
	
	public static function set_num_passengers( $num_passengers ){
		
		if ( self::get('steps_data.1') === null )
	        self::set( 'steps_data.1', [] );
	    
	    self::set( 'steps_data.1.num_passengers', max( 0, (int) $num_passengers ) );
		
	}
	
}