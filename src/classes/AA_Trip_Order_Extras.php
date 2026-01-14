<?php

class AA_Trip_Order_Extras{
	
	protected $order = false;
	protected $stock_quantities = null;
	protected $categories = [];
	protected $order_products = [];
	protected $itinerary_ids = [];
	protected $errors = [];
	
	public function __construct( $order, $categories = [ 'accommodation', 'equipment-rental', 'supplements' ] ){
		
		$this->order = $order;
		$this->trip_order = new AA_Trip_Order( $order );
		$this->categories = array_combine( $categories, array_map( function( $v ){
			
			return get_term_by( 'slug', $v, 'product_cat' );
		
		}, $categories ) );
		
		if( $this->trip_order->is_valid() ){
			
			foreach( $order->get_items() as $item_id => $item ){
				
				if( is_a( $item, 'WC_Order_Item_Product' ) )
					$this->load_item_product_data( $item, true );
		        
		    }
			
		}
		
	}
	
	public function get_errors(){
		
		return $this->errors ?: [];
		
	}
	
	public function get_categories(){
		
		return $this->categories;
		
	}
	
	public function get_first_category(){
		
		foreach( $this->categories as $category )
			return $category;
		
	}
	
	public function get_product_data( $product ){
		
		if( is_numeric( $product ) )
			$product = wc_get_product( $product );
		
		$product_id = $product->get_id();
		
		$product_data = [
			'stock_qty' => $product->get_stock_quantity(),
			'stock_ignore' => ( ! $product->managing_stock() || $product->backorders_allowed() )
		];
		
		return $product_data;
		
	}
	
	public function load_item_product_data( $item, $set_itinerary_id = false ){
		
		if( is_a( $item, 'WC_Order_Item_Product' ) ){
			
			$product_id = $item->get_product_id();
			$product = $item->get_product();
			
			if( has_term( array_keys( $this->get_categories() ), 'product_cat', $product_id ) ){
				
				$product_data = $this->get_product_data( $product );
				
				$this->order_products[ $product_id ]['item'] = $item;
				$this->order_products[ $product_id ]['qty'] = ( $this->order_products[ $product_id ]['qty'] ?? 0 ) + $item->get_quantity();
				$this->order_products[ $product_id ]['stock_qty'] = $product_data['stock_qty'];
				$this->order_products[ $product_id ]['stock_ignore'] = $product_data['stock_ignore'];
				
				if( $set_itinerary_id ){
					
					$itinerary_id = get_field( 'itinerary', $product_id );
					
					if( ! empty( $itinerary_id ) && ! in_array( $itinerary_id, $this->itinerary_ids ) )
						$this->itinerary_ids[] = $itinerary_id;
					
				}
				
			}
			
		}
		
	}
	
	public function get_category_products_query( $category_slug ){
		
		// Fetch extras based on serialized itinerary IDs
	    foreach ($this->itinerary_ids as $id) {
	        $meta_query[] = array(
	            'key' => 'relevant_trips',
	            'value' => '"' . $id . '"',
	            'compare' => 'LIKE',
	        );
	    }
	    
	    if( ! empty( $meta_query ) )
	    	$meta_query['relation'] = 'OR';

	    // Fetch extras based on itinerary IDs
	    $args = array(
	        'post_type' => 'product',
	        'posts_per_page' => -1,
	        'tax_query' => array(
	            array(
	                'taxonomy' => 'product_cat',
	                'field' => 'slug',
	                'terms' => $category_slug,
	            ),
	        ),
	        'meta_query' => $meta_query ?? [],
	    );

	    $products = new WP_Query($args);
	    
	    return $products;
		
	}
	
	public function get_category_products_html( $category_slug ){
		
		$products_query = $this->get_category_products_query( $category_slug );
		
		if( ! empty( $products_query->posts ) ){
		
			foreach( $products_query->posts as $post ){
				
				$product = wc_get_product( $post->ID );
				
				$minus_classes = [ 'minus-button' ];
				$plus_classes = [ 'plus-button' ];
					
				$input_value = intval( $this->order_products[ $product->get_id() ]['qty'] ?? 0 );
				$max_value = $this->get_product_max_quantity( $product->get_id(), '' );
					
				if( empty( $input_value ) )
					$minus_classes[] = 'aa-disabled';
				
				if( is_numeric( $max_value ) ){
					
					$max_value = max( $max_value, $input_value );
					
					if( $max_value == $input_value )
						$plus_classes[] = 'aa-disabled';
					
				}
				else
					$max_value = '';
				
	            ?>
	            <li class="product-item">
	                <div class="product-image">
	                    <?php echo $product->get_image(); ?>
	                </div>
	                <div class="product-details">
	                    <h3 class="product-title"><?php echo $product->get_name(); ?></h3>
	                    <p class="product-price"><?php echo $product->get_price_html(); ?></p>
	                    <p class="product-description"><?php echo $product->get_description(); ?></p>
	                    <div class="add-to-cart-controls">
                            <button type="button" class="<?= implode( ' ', $minus_classes ); ?>" data-product-id="<?php echo $product->get_id(); ?>">-</button>
                            <input type="number" class="product-quantity" data-product-id="<?php echo $product->get_id(); ?>" name="aa_quantities[<?= $product->get_id(); ?>]" data-value="<?= $input_value; ?>" data-max-value="<?= $max_value; ?>" value="<?= $input_value; ?>" readonly>
                            <button type="button" class="<?= implode( ' ', $plus_classes ); ?>" data-product-id="<?php echo $product->get_id(); ?>">+</button>
	                    </div>
	                </div>
	            </li>
	            <?php
			
			}
		
		}
		else
			echo '<p>No extras of this type are available for selection.</p>';
		
	}
	
	// check if new quantity is within stock quantity (if stock is enabled)
	public function check_item_new_quantity( $item, $new_qty, $increase_stock_count = false ){
		
		$product = $item->get_product();
		$product_id = $item->get_product_id();
		$product_data = $this->order_products[ $product_id ] ?? false;
		
		if( empty( $product_data ) )
			return $new_qty;
		
		if( ! $product_data['stock_ignore'] ){
			
			$stock_qty = $product_data['stock_qty'];
			
			if( $increase_stock_count )
				$stock_qty += $item->get_quantity();
			
			if( $product->is_sold_individually() )
				$stock_qty = min( $stock_qty, 1 );
			
			if( $stock_qty < $new_qty ){
				
				$new_qty = $stock_qty;
				
				$this->errors[] = [
					'message' => sprintf( 'Max quantity allowed is <b>%d</b>', $stock_qty ),
					'order_item' => $item,
					'product_id' => $product_id
				];
				
			}
			
		}
		
		return $new_qty;
		
	}
	
	// get max quantity allowed for the product (compared to stock levels)
	public function get_product_max_quantity( $product_id, $default = false ){
		
		if( is_numeric( $product_id ) )
			$product = wc_get_product( $product_id );
		elseif( is_a( $product_id, 'WC_Product' ) )
			$product = $product_id;
		
		$product_id = $product->get_id();
		
		$product_data = $this->get_product_data( $product_id );
		
		if( ! empty( $product_data['stock_qty'] ) && ! $product_data['stock_ignore'] ){
			
			$stock_qty = $product_data['stock_qty'];
			
			if( is_numeric( $stock_qty ) && $stock_qty >= 0 ){
				
				if( $this->order && $this->order->has_status( [ 'processing', 'on-hold', 'completed' ] ) )
					$stock_qty += $this->order_products[ $product_id ]['qty'] ?? 0;
				
				if( $product->is_sold_individually() )
					$stock_qty = min( $stock_qty, 1 );
				
				return $stock_qty;
				
			}
			
		} 
		
		return $default;
		
	}
	
	// update existing order quantities
	// $allow_save will be true when saving new quantities in database, otherwise we just need to show a preview of new quantities
	public function update_quantities( $new_quantities, $allow_save = false, $remove_action_and_filters_after_function_end = true ){
		
		$order_status = $this->order->get_status();
		
		if( $allow_save ){
			
			$this->order->set_status('pending');
			$this->order->save(); // saving will incraase back the stock levels, because we set the order status back to pending
			
		}
		else {
			
			add_filter( 'woocommerce_logger_log_message', '__return_null', 1030 );
			add_filter( 'woocommerce_new_order_note_data', '__return_false', 1030 );
			
			add_filter( 'update_order_metadata', '__return_null', 1030 );
			add_filter( 'update_order_item_metadata', '__return_null', 1030 );
			
			add_action( 'woocommerce_before_order_item_object_save', [ $this, 'throw_order_save_exception' ], 1030 );
			add_action( 'woocommerce_before_order_object_save', [ $this, 'throw_order_save_exception' ], 1030 );
			
		}
		
		$updated_count = 0;
		
		$updated_product_ids = [];
		
		foreach( $this->order->get_items() as $item_id => $item ){
			
			if( is_a( $item, 'WC_Order_Item_Product' ) ){
				
				$product = $item->get_product();
				$product_id = $item->get_product_id();
				$product_data = $this->order_products[ $product_id ] ?? false;
				
				$updated_product_ids[] = $product_id;
				
				if( ! isset( $new_quantities[ $product_id ] ) || ! $product_data || $product_data['qty'] == $new_quantities[ $product_id ] )
					continue;
				
				$increase_stock_count = ! $allow_save && in_array( $order_status, [ 'processing', 'on-hold', 'completed' ] );
				
				$new_qty = $this->check_item_new_quantity( $item, $new_quantities[ $product_id ], $increase_stock_count );
				
				$total = wc_get_price_excluding_tax( $product, array( 'qty' => $new_quantities[ $product_id ] ) );
				
				if( $allow_save ){
					
					$item->set_quantity( $new_qty );
					$item->set_subtotal( $total );
					$item->set_total( $total );
					$item->calculate_taxes();
					
					$item->save();
					
				}
				else{
					
					// We are intentionally throwing an exception just before WC tries to save order's preview data to db, because we don't want preview data to be saved. 
					// Thats why we use try blocks, so that script continue executing after save function fails.
					// Before each save function fails, WC correctly set all the data for the $order object, which is enough for generating a preview table.
					try{
						
						$item->set_quantity( $new_qty );
						
					} catch( Exception $e ){}
					
					try{
						
						$item->set_subtotal( $total );
						
					} catch( Exception $e ){}
					
					try{
						
						$item->set_total( $total );
						
					} catch( Exception $e ){}
					
					try{
						
						$item->calculate_taxes();
						
					} catch( Exception $e ){}
					
				}
				
				$updated_count++;
				
			}
	        
	    }
	    
	    foreach( $new_quantities as $p_id => $new_quantity ){
	    	
	    	if( ! in_array( $p_id, $updated_product_ids ) && ! empty( $new_quantity ) ){
	    		
	    		$updated_product_ids[] = $p_id;
	    	
	    		$product = wc_get_product( $p_id );
	    		
	    		$new_item = new WC_Order_Item_Product();
	            $new_item->set_product( $product );
	            $new_item->set_quantity( $new_quantity );
	            
	            $this->load_item_product_data( $new_item );
	            
	            $new_qty = $this->check_item_new_quantity( $new_item, $new_quantity );
	            
	            if( $new_qty != $new_quantity ){
	            	$new_quantity = $new_qty;
	            	$new_item->set_quantity( $new_quantity );
	            }
	            
	            $total = wc_get_price_excluding_tax( $product, array( 'qty' => $new_quantity ) );
	            $new_item->set_total( $total );
	            $new_item->set_subtotal( $total );

	            $this->order->add_item( $new_item );
	            
	            $updated_count++;
	    	
	    	}
	    
	    }
	    
	    if( $updated_count ){
	    	
	    	$this->order->calculate_totals(true);
	    	
	    }
	    
	    if( $allow_save ){
	    	
	    	if( $order_status == 'pending' )
	    		$order_status = $this->order->needs_processing() ? 'processing' : 'completed';
			
			$this->order->set_status( $order_status );
			$this->order->save(); // saving will reduce the stock levels again, because we set the order status back to original order status (which should be processing, on-hold or completed)
			
		}
		
		if( ! $allow_save && $remove_action_and_filters_after_function_end ){
			
			remove_filter( 'woocommerce_logger_log_message', '__return_null', 1030 );
			remove_filter( 'woocommerce_new_order_note_data', '__return_false', 1030 );
			
			remove_filter( 'update_order_metadata', '__return_null', 1030 );
			remove_filter( 'update_order_item_metadata', '__return_null', 1030 );
			
			remove_action( 'woocommerce_before_order_item_object_save', [ $this, 'throw_order_save_exception' ], 1030 );
			remove_action( 'woocommerce_before_order_object_save', [ $this, 'throw_order_save_exception' ], 1030 );
			
		}
		
	}
	
	public function throw_order_save_exception(){
		
		throw new Exception( "Do not save changed order because we need only preview of it with new quantities", 1 );
		
	}
	
}