<?php 

$min_deposit = AA_Checkout::get_instance()->get_min_deposit_amount();

?>
<?php if( isset( $current_step ) ): ?>
<div class="basket"<?= ( is_wc_endpoint_url('order-received') || ! is_numeric( $current_step ) ) ? ' style="display:none;"' : ''; // ADDED BY DEJAN - hide basket in some checkout page types ?>>
<?php endif; ?>
    <?php if (aa_get_cart_count() > 0) : ?>
        <ul>
            <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                //$product = wc_get_product($cart_item['product_id']); // REMOVED BY DEJAN
                $product = $cart_item['data']; ?>
                <li>
                    <span class="product-name"><?php echo $product->get_name(); ?></span>
                    <span class="product-quantity"> x<?php echo $cart_item['quantity']; ?></span>
                    <span class="product-price"><?php echo wc_price($product->get_price() * $cart_item['quantity']); ?></span>
                    <form method="post" class="remove-product-form">
                        <input type="hidden" name="cart_item_key" value="<?php echo $cart_item_key; ?>">
                        <button type="submit" name="remove_product" value="Remove" class="remove-button">-</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="cart-total">Total: <?php echo WC()->cart->get_cart_total(); ?></p>
        <?php if( $min_deposit ): ?>
        <p class="min-deposit">Minimum Deposit: <?php echo wc_price( $min_deposit ); ?></p>
        <?php endif; ?>
    <?php else : ?>
        <p>Your basket is empty.</p>
    <?php endif; ?>
<?php if( isset( $current_step ) ): ?>
</div>
<?php endif; ?>