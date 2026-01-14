<?php

/*
Template for displaying order table with items & totals
Before including this template, these PHP variables should already be defined: $order
*/

if( ! isset( $totals ) )
	$totals = $order->get_order_item_totals();

if( ! isset( $trip_order ) )
	$trip_order = new AA_Trip_Order( $order );

$trip_product = $trip_order->get_trip_product();

$order_extras_errors = isset( $order_extras ) ? $order_extras->get_errors() : [];

?>
<table class="shop_table">
    <thead>
        <tr>
            <th class="product-name"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
            <th class="product-quantity"><?php esc_html_e( 'Number', 'woocommerce' ); ?></th>
            <th class="product-total"><?php esc_html_e( 'Total', 'woocommerce' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( count( $order->get_items() ) > 0 ) : ?>
            <?php foreach ( $order->get_items() as $item_id => $item ) : ?>
                <?php
                if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) || empty( $item->get_quantity() ) ) {
                    continue;
                }
                
                $product_errors = count( $order_extras_errors ) ? array_filter( array_map(function( $error_data ) use ( $item ){
                    
                    if( method_exists( $item, 'get_product_id' ) && $item->get_product_id() == ( $error_data['product_id'] ?? 0 ) )
                        return $error_data['message'] ?? '';
                    else
                        return '';
                
                }, $order_extras_errors ) ) : [];
                
                ?>
                <tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
                    <td class="product-name">
                        <?php
                            echo wp_kses_post( apply_filters( 'woocommerce_order_item_name', $item->get_name(), $item, false ) );
                            
                            if( ! empty( $product_errors ) ){
                            
                                foreach( $product_errors as $error ){
                                    
                                    echo sprintf( '<div class="aa-order-items-product-error-message">%s</div>', $error );
                                
                                }
                            
                            }

                            do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

                            wc_display_item_meta( $item );

                            do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
                        ?>
                    </td>
                    <td class="product-quantity"><?php echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times;&nbsp;%s', esc_html( $item->get_quantity() ) ) . '</strong>', $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
                    <td class="product-subtotal"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
		<?php if ( $totals ) : ?>
			<?php foreach ( $totals as $total ) : ?>
				<tr>
					<th scope="row" colspan="2">
						<?php
						// Check if the label is "Unpaid:" and replace it with "Balance Due:"
						if ( $total['label'] === 'Unpaid:' ) {
							echo 'Balance Due:';
						} else {
							echo $total['label'];
						}
						?>
					</th>
					<td class="product-total"><?php echo $total['value']; ?></td><?php // @codingStandardsIgnoreLine ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
	</tfoot>
</table>
<?php

if( $trip_order->is_editable() && $trip_order->current_user_can_edit() && empty( $_POST ) && ( ( $_GET['aa-action'] ?? '' ) != 'modify-order-extras' || is_wc_endpoint_url('order-received') ) ){
    
    echo '<a href="' . add_query_arg( 'aa-action', 'modify-order-extras', $order->get_view_order_url() ) . '" class="woocommerce-button aa-button-style button">Add/Edit Trip Extras</a>';
    
}