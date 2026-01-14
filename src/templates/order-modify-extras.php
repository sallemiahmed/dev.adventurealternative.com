<?php

$order_extras = new AA_Trip_Order_Extras( $order );

if( ! isset( $trip_order ) )
	$trip_order = new AA_Trip_Order( $order );

$categories = $order_extras->get_categories();

if( ! empty( $_POST['aa_quantities'] ) ){
    
    if( $trip_order->is_editable() ){
    	
    	if( $trip_order->current_user_can_edit() ){
    		
		    $order_extras->update_quantities( $_POST['aa_quantities'], true );
		    
		    foreach( $order_extras->get_errors() as $error ){
		    	
		    	if( ! empty( $error['message'] ) )
		    		wc_add_notice( $error['message'], 'error' );
		    
		    }
		    
		    wc_add_notice( 'Your changes are saved successfully!', 'success' );
    		
    	}
    	else
    		wc_add_notice( 'You are not allowed to edit this order! Please contact us for more details.', 'error' );
    	
    }
    else
    	wc_add_notice( 'This order is not editable! Please contact us for more details.', 'error' );
    
    wp_redirect( add_query_arg( 'aa-action', 'modify-order-extras', $order->get_view_order_url() ) );
    
    die;

}
elseif( ! $trip_order->is_editable() )
	wc_add_notice( 'This order is not editable! Please contact us for more details.', 'error' );
elseif( ! $trip_order->current_user_can_edit() )
	wc_add_notice( 'You are not allowed to edit this order! Please contact us for more details.', 'error' );

?>
<section id="modify-order-extras">
	<div class="container">
		<?php wc_print_notices(); ?>
		<?php if( $trip_order->is_editable() && $trip_order->current_user_can_edit() ): ?>
		<h2>Order #<?= $order->get_id(); ?> extras</h2>
		<form id="checkout-form-step-2" method="post">
		    <input type="hidden" name="step" value="2">
		    <p>Add or modify extras you'll need for your trip. If you want to remove any item, set its quantity to zero.</p>

		    <div class="extras-tabs">
		    	<?php foreach( $categories as $category ): ?>
		        <button type="button" class="tab-button <?= $category == $order_extras->get_first_category() ? 'active' : ''; ?>" data-tab="<?= $category->slug; ?>"><?= $category->name; ?></button>
		    	<?php endforeach; ?>
		    </div>
		    
			<?php foreach( $categories as $category ): ?>
		    <div class="tab-content" id="<?= $category->slug; ?>" style="<?= $category != $order_extras->get_first_category() ? 'display:none;' : ''; ?>">
		        <?php $order_extras->get_category_products_html( $category->slug ); ?>
		    </div>
			<?php endforeach; ?>
		    <div class="submit-wrapper">
		        <button class="cta-btn" type="submit">Save changes</button>
		    </div>
			<br>
			<h2>Order preview</h2>
			<?php include get_stylesheet_directory() . '/src/templates/order-table.php'; ?>
		    <div class="submit-wrapper">
		        <button class="cta-btn" type="submit">Save changes</button>
		    </div>
		</form>
		<a href="<?= esc_attr( $order->get_view_order_url() ); ?>" class="woocommerce-button aa-button-style button">Back to order</a>
		<script type="text/javascript">
			jQuery(function($){
				
				// Handle tab switching
				const tabs = $('.tab-button');
				const contents = $('.tab-content');

				tabs.on('click', function() {
					const target = $(this).data('tab');

					tabs.removeClass('active').addClass('inactive');
					$(this).addClass('active').removeClass('inactive');

					contents.hide();
					$(`#${target}`).show();
				});

				if (tabs.length > 0) {
					tabs.first().addClass('active').removeClass('inactive');
					$(`#${tabs.first().data('tab')}`).show();
				}

				// Add to cart controls
				const plusButtons = $('.plus-button');
				const minusButtons = $('.minus-button');

				plusButtons.on('click', function() {
					
					if( $(this).hasClass('aa-disabled') )
						return;
					
					const productId = $(this).data('product-id');
					const quantityInput = $(`.product-quantity[data-product-id="${productId}"]`);
					let quantity = parseInt(quantityInput.val());
					
					if( quantityInput.data('max-value') != '' && ( quantity + 1 ) >= parseInt( quantityInput.data('max-value') ) )
						$(this).addClass('aa-disabled');
					
					quantity += 1;
					
					$(this).siblings('.minus-button').removeClass('aa-disabled');
					
					quantityInput.val(quantity);
					aa_update_items_quantities();
				});

				minusButtons.on('click', function() {
					
					if( $(this).hasClass('aa-disabled') )
						return;
					
					$(this).siblings('.plus-button').removeClass('aa-disabled');
					
					const productId = $(this).data('product-id');
					const quantityInput = $(`.product-quantity[data-product-id="${productId}"]`);
					let quantity = parseInt(quantityInput.val());
					if (quantity > 0) {
						
						quantity -= 1;
						
						if( quantity == 0 )
							$(this).addClass('aa-disabled');
						
						quantityInput.val(quantity);
						aa_update_items_quantities();
					}
				});
				
				let update_counter = 0;
				let update_timeout = false;
				let error_retry_counter = 0;

				function aa_update_items_quantities(){
					
					let quantities = {};
					
					update_counter++;
					
					let counter_value = update_counter;
					
					$('input.product-quantity').each(function(index, el) {
						
						quantities[ $(el).data('product-id') ] = parseInt( $(el).val() ) || 0;
						
					});
					
					clearTimeout( update_timeout );
					
					update_timeout = setTimeout( () => {
						
						$.ajax({
							url: '<?= admin_url('admin-ajax.php'); ?>',
							type: 'POST',
							data: {
								action: 'aa_update_order_quantities',
								aa_quantities: quantities,
								aa_order_id: <?= $order->get_id(); ?>,
								aa_nonce : '<?= wp_create_nonce('order-modify-extras-nonce-value'); ?>'
							},
							success: function(response) {
								
								if( counter_value != update_counter )
									return;
								
								error_retry_counter = 0;
								
								if( response && response.data && response.data.table ){
									$('table.shop_table').replaceWith( response.data.table );
									$('table.shop_table').find('a.woocommerce-button').remove();
								}
								
							},
							error: function(error) {
								
								if( error_retry_counter > 5 )
									return;
								
								error_retry_counter++;
								
								setTimeout( aa_update_items_quantities, 1000 );
								
							}
						});
						
					}, 300 );
					
				}
			
			});
		</script>
		<?php endif; ?>
	</div>
</section>