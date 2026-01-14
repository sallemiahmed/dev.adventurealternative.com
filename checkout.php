<?php
/* Template Name: Custom Checkout */

get_header();

// Start session to store checkout progress - MODIFIED BY DEJAN: not needed anymore
/*if (!session_id()) {
    session_start();
}*/

/* >>> ADDED BY DEJAN */

global $wp;

// Check cart contents for errors.
do_action( 'woocommerce_check_cart_items' );

// Calc totals.
WC()->cart->calculate_totals();

// Get checkout object.
$checkout = WC()->checkout();

$min_deposit = AA_Checkout::get_instance()->get_min_deposit_amount();

/* <<< ADDED BY DEJAN */

$current_trip_product_id = get_cart_trip_product_id();
// Determine the current step
$current_step = AA_Session::get( 'checkout_step', 1 ); // MODIFIED BY DEJAN

/* >>> ADDED BY DEJAN */
if( is_wc_endpoint_url('order-received') ){
    
    $current_step = 4; // if WC is trying to show order received confirmation, thats means we need to set current step to #4
    $order_id = $wp->query_vars['order-received'] ?? 0;
    $order = $order_id ? wc_get_order( $order_id ) : false;
    
    // is additional payment confirmation page? It is if there is more than 1 transaction in the order
    if( ( new AA_Trip_Order( $order ) )->is_paid_later() )
        $current_step = 'additional-payment-received';
    
}
elseif( is_wc_endpoint_url('order-pay') )
    $current_step = 'order-pay';
elseif( $current_step == 4 )
    $current_step = 3;

/* <<< ADDED BY DEJAN */

// Handle form submissions
// MODIFIED BY DEJAN: I think this code is not needed (thats why I disabled it), because code for this logic is already placed in functions.php - handle_custom_checkout()
/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step'])) {
        $current_step = intval($_POST['step']);
        $_SESSION['checkout_step'] = $current_step;

        // Save form data to session
        $_SESSION['checkout_data'][$current_step] = $_POST;
        
    }
}*/

// Load previous data if available
$steps_data = AA_Session::get( 'steps_data', [] ); // MODIFIED BY DEJAN

// Function to merge data from all steps
function get_merged_checkout_data($steps_data) {
    $merged_data = [];
    foreach ($steps_data as $step_key => $step_data) {
        if( $step_key == 3 ) // ADDED BY DEJAN - we don't want to merge data from step 3, because it will contain submitted data
            continue;
            
        $merged_data = array_merge($merged_data, $step_data);
    }
    return $merged_data;
}

// Get all merged data
$merged_data = get_merged_checkout_data($steps_data);

// Function to get field value
function get_checkout_field_value($merged_data, $field) {
    return isset($merged_data[$field]) ? $merged_data[$field] : '';
}

// Get current user information if logged in
$current_user = wp_get_current_user();
$is_logged_in = is_user_logged_in();
if ($is_logged_in) {
    $billing_first_name = get_user_meta($current_user->ID, 'billing_first_name', true);
    $billing_last_name = get_user_meta($current_user->ID, 'billing_last_name', true);
    $billing_address_1 = get_user_meta($current_user->ID, 'billing_address_1', true);
    $billing_address_2 = get_user_meta($current_user->ID, 'billing_address_2', true);
    $billing_city = get_user_meta($current_user->ID, 'billing_city', true);
    $billing_postcode = get_user_meta($current_user->ID, 'billing_postcode', true);
    $billing_country = get_user_meta($current_user->ID, 'billing_country', true);
    $billing_state = get_user_meta($current_user->ID, 'billing_state', true);
    $billing_phone = get_user_meta($current_user->ID, 'billing_phone', true);
    $billing_email = $current_user->user_email;
    $dob = get_user_meta($current_user->ID, 'dob', true);
    $gender = get_user_meta($current_user->ID, 'gender', true);
} else {
    $billing_first_name = get_checkout_field_value($merged_data, 'billing_first_name');
    $billing_last_name = get_checkout_field_value($merged_data, 'billing_last_name');
    $billing_address_1 = get_checkout_field_value($merged_data, 'billing_address_1');
    $billing_address_2 = get_checkout_field_value($merged_data, 'billing_address_2');
    $billing_city = get_checkout_field_value($merged_data, 'billing_city');
    $billing_postcode = get_checkout_field_value($merged_data, 'billing_postcode');
    $billing_country = get_checkout_field_value($merged_data, 'billing_country');
    $billing_state = get_checkout_field_value($merged_data, 'billing_state');
    $billing_phone = get_checkout_field_value($merged_data, 'billing_phone');
    $billing_email = get_checkout_field_value($merged_data, 'billing_email');
    $dob = get_checkout_field_value($merged_data, 'dob');
    $gender = get_checkout_field_value($merged_data, 'gender');
}

if( ! empty( $_POST ) && in_array( $current_step, [ 1, 2 ] ) ){

    maybe_fix_cart_trip_product_quantity( $current_step );

}

?>

<section id="hero">
    <div class="faux-bg-img">
        <?= wp_get_attachment_image(1171, 'full'); ?>
    </div>
    <div class="container">
        <h1 class="title">Your <i>Adventure</i></h1>
        <div class="hero-text">
            <p>You’re moments away from booking your adventure of a lifetime. Simply follow the steps below to book the ultimate alternative experience.</p>
            <p>Remember, if you still have any questions, or are unsure of anything, don’t hesitate to contact us.</p>
        </div>
    </div>
</section>
<?php if( is_numeric( $current_step ) ): // ADDED BY DEJAN - if for example we are trying to show order-pay/additional-payment-received page, step is not defined ?>
<section class="progress-tracker">
    <div class="container">
        <ul>
            <li class="<?= $current_step == 1 ? 'current' : ($current_step > 1 ? 'completed' : 'not-reached'); ?>"><a href="<?= $current_step > 1 ? '/checkout?step=1' : '#'; ?>"><span>Step One</span> Personal Information</a></li>
            <li class="<?= $current_step == 2 ? 'current' : ($current_step > 2 ? 'completed' : 'not-reached'); ?>"><a href="<?= $current_step > 2 ? '/checkout?step=2' : '#'; ?>"><span>Step Two</span> Add Trip Extras</a></li>
            <li class="<?= $current_step == 3 ? 'current' : ($current_step > 3 ? 'completed' : 'not-reached'); ?>"><a href="<?= $current_step > 3 ? '/checkout?step=3' : '#'; ?>"><span>Step Three</span> Payment</a></li>
            <li class="<?= $current_step == 4 ? 'current' : 'not-reached'; ?>"><a href="#"><span>Step Four</span> Booking Confirmation</a></li>
        </ul>
    </div>
</section>
<?php endif; // ADDED BY DEJAN ?>
<section id="checkout" data-step="<?= (string) $current_step; // ADDED BY DEJAN ?>">
    <div class="container">
        <div class="top-panel">
            <?php wc_print_notices(); // ADDED BY DEJAN ?>
            <div class="flex-row">
                <div class="flex-half step-title">
                    <?php if ($current_step == 1) : ?>
                        <h2>Your Details</h2>
                    <?php elseif ($current_step == 2) : ?>
                        <h2>Add Trip Extras</h2>
                    <?php elseif ($current_step == 3) : ?>
                        <h2>Payment</h2>
                    <?php elseif ($current_step == 4) : ?>
                        <h2>Booking Confirmation</h2>
                    <?php elseif ($current_step == 'additional-payment-received') : // ADDED BY DEJAN ?>
                        <h2>Booking Details</h2>
                    <?php elseif ($current_step == 'order-pay') : // ADDED BY DEJAN ?>
                        <h2>Pay For Order</h2>
                    <?php endif; ?>
                    <?php if (!$is_logged_in) : ?>
                        <div class="form-group">
                            <button type="button" id="login-user-btn" href="/user-login/">Already Have An Account? Click Here to Log in.</button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex-half">
                    <?php include get_stylesheet_directory() . '/src/templates/basket.php'; ?>
                </div>
            </div>
        </div>
        <?php if ($current_step == 1) : ?>
            <!-- Step 1: Personal Information -->
            <?php
            
            $billing_fields = $checkout->get_checkout_fields( 'billing' ); // ADDED BY DEJAN - we will use this data to output WC country/state fields
            
            $billing_fields['billing_country']['class'][] = 'form-group'; // ADDED BY DEJAN - add our class to WC field container
            $billing_fields['billing_state']['class'][] = 'form-group'; // ADDED BY DEJAN - add our class to WC field container
            
            ?>
            <form id="checkout-form-step-1" method="post">
                <input type="hidden" name="step" value="1">
                <div class="form-flex woocommerce-billing-fields"><?php // MODIFIED BY DEJAN - "woocommerce-billing-fields" class needed as container class for country/select fields ?>
                    <div class="form-group">
                        <label for="billing_first_name">First Name</label>
                        <input type="text" id="billing_first_name" name="billing_first_name" value="<?= esc_attr($billing_first_name); ?>" placeholder="First Name" required />
                    </div>
                    <div class="form-group">
                        <label for="billing_last_name">Last Name</label>
                        <input type="text" id="billing_last_name" name="billing_last_name" value="<?= esc_attr($billing_last_name); ?>" placeholder="Last Name" required />
                    </div>
                    <div class="form-group">
                        <label for="billing_email">Email</label>
                        <input type="email" id="billing_email" name="billing_email" value="<?= esc_attr($billing_email); ?>" placeholder="Email" required<?= $is_logged_in ? ' disabled' : ''; // ADDED BY DEJAN ?> />
                    </div>
                    <div class="form-group">
                        <label for="billing_phone">Phone</label>
                        <input type="text" id="billing_phone" name="billing_phone" value="<?= esc_attr($billing_phone); ?>" placeholder="Phone" required />
                    </div>
                    <div class="form-group">
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?= esc_attr($dob); ?>" required />
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="male" <?= selected($gender, 'male'); ?>>Male</option>
                            <option value="female" <?= selected($gender, 'female'); ?>>Female</option>
                        </select>
                    </div>
                    <!-- Password for new users -->
                    <?php if (!$is_logged_in) : ?>
                        <div class="form-group password-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Create a Password" required />
                            <p>This will be the password for your new account, where you will be able to access your trip details, provided information, and payments. Already have an account? <a href="/user-login/">Log in</a>.</p>
                        </div>
                    <?php endif; ?>
                    <div class="form-divider"></div>
                    <h2>Your Address</h2>
                    <div class="form-group">
                        <label for="billing_address_1">Address Line 1</label>
                        <input type="text" id="billing_address_1" name="billing_address_1" value="<?= esc_attr($billing_address_1); ?>" placeholder="Address Line 1" required />
                    </div>
                    <div class="form-group">
                        <label for="billing_address_2">Address Line 2</label>
                        <input type="text" id="billing_address_2" name="billing_address_2" value="<?= esc_attr($billing_address_2); ?>" placeholder="Address Line 2" />
                    </div>
                    <div class="form-group">
                        <label for="billing_city">City</label>
                        <input type="text" id="billing_city" name="billing_city" value="<?= esc_attr($billing_city); ?>" placeholder="City" required />
                    </div>
                    <div class="form-group">
                        <label for="billing_postcode">Postcode / ZIP</label>
                        <input type="text" id="billing_postcode" name="billing_postcode" value="<?= esc_attr($billing_postcode); ?>" placeholder="Postcode / ZIP" required />
                    </div>
                    <?php woocommerce_form_field( 'billing_country', $billing_fields['billing_country'], $billing_country ); // ADDED BY DEJAN - javascript code is executed from /wp-content/plugins/woocommerce/assets/js/frontend/country-select.min.js ?>
                    <?php woocommerce_form_field( 'billing_state', $billing_fields['billing_state'], $billing_state ); // ADDED BY DEJAN - javascript code is executed from /wp-content/plugins/woocommerce/assets/js/frontend/country-select.min.js ?>
                    <?php if( false ): // Connor should remove this old version of state select field ?>
                    <div class="form-group">
                        <label for="billing_state">State / County</label>
                        <input type="text" id="billing_state" name="billing_state" value="<?= esc_attr($billing_state); ?>" placeholder="State / County" required />
                    </div>
                    <?php endif; ?>
                    <div class="form-divider"></div>
                    <h2>Additional Passengers</h2>
                    <!-- Number of Passengers -->
                    <div class="form-group">
                        <label for="num_passengers">Number of Additional Passengers</label>
                        <input type="number" id="num_passengers" name="num_passengers" min="0" value="<?= get_checkout_field_value($merged_data, 'num_passengers') ?: 0; ?>" required />
                    </div>
                    <!-- Passenger Details Template -->
                    <div id="passenger-details-template" style="display: none;">
                        <div class="passenger-details">
                            <h3>Passenger <span class="passenger-number"></span></h3>
                            <div class="form-flex">
                                <div class="form-group">
                                    <label for="passenger_first_name[]">First Name</label>
                                    <input type="text" name="passenger_first_name[]" placeholder="First Name" />
                                </div>
                                <div class="form-group">
                                    <label for="passenger_last_name[]">Last Name</label>
                                    <input type="text" name="passenger_last_name[]" placeholder="Last Name" />
                                </div>
                                <div class="form-group">
                                    <label for="passenger_gender[]">Gender</label>
                                    <select name="passenger_gender[]">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="passenger_dob[]">Date of Birth</label>
                                    <input type="date" name="passenger_dob[]" />
                                </div>
                                <div class="form-group">
                                    <label for="passenger_email[]">Email Address</label>
                                    <input type="email" name="passenger_email[]" placeholder="Email Address" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Passenger Details Container -->
                    <div id="passenger-details-container"></div>

                    <div class="submit-wrapper">
                        <p class="submit-policy">
                            At Adventure Alternative, we do not sell or share your data without your consent. By submitting your details you are agreeing to our <a href="/data-protection/">privacy and data protection terms</a>.
                        </p>
                        <button class="cta-btn" type="submit">I'm Happy With This</button>
                    </div>
                </div>
            </form>
        <?php elseif ($current_step == 2) : ?>
            <!-- Step 2: Extras -->
            <form id="checkout-form-step-2" method="post">
                <input type="hidden" name="step" value="2">
                <p>Select any additional extras you'll need for your trip.</p>

                <div class="extras-tabs">
                    <button type="button" class="tab-button" data-tab="accommodation">Accommodation</button>
                    <button type="button" class="tab-button" data-tab="equipment-rental">Equipment Rental</button>
                    <button type="button" class="tab-button" data-tab="supplements">Supplements</button>
                </div>

                <div class="tab-content" id="accommodation">
                    <?php display_linked_extras_by_category('accommodation'); ?>
                </div>
                <div class="tab-content" id="equipment-rental" style="display:none;">
                    <?php display_linked_extras_by_category('equipment-rental'); ?>
                </div>
                <div class="tab-content" id="supplements" style="display:none;">
                    <?php display_linked_extras_by_category('supplements'); ?>
                </div>
                <div class="submit-wrapper">
                    <button class="cta-btn" type="submit">Proceed to Payment</button>
                </div>
            </form>
        <?php elseif ($current_step == 3) : ?>
            <!-- Step 3: Payment -->
            <form id="checkout-form-step-3" name="checkout" method="post" class="checkout woocommerce-checkout"><!-- MODIFIED BY DEJAN - added name="checkout" and class="checkout woocommerce-checkout" -->
                <?php // ADDED BY DEJAN
                echo '<div style="display:none;">'; // we don't want these wc fields to be visible to customer
                do_action('woocommerce_checkout_billing'); // output wc billing fields (Stripe needs these fields)
                echo '</div>';
                ?>
                <input type="hidden" name="step" value="3">
				<div class="custom-currency-switcher">
                    <p>Choose a currency:</p>
                    <?php foreach( aa_get_currencies() as $currency_id => $currency_data ): ?>
                        <label>
                            <input type="radio" name="payment_currency" value="<?= esc_attr( $currency_id ); ?>" required<?= aa_get_checkout_currency() == $currency_id ? ' checked' : ''; ?>>
                            <?= esc_html( strtoupper( $currency_id ) ); ?> (<?= get_woocommerce_currency_symbol( strtoupper( $currency_id ) ); ?>)
                        </label>
                    <?php endforeach; ?>
                </div>
				<div class="payment-option">
					<p>Select how you would like to pay today. Subsequent payments can be made from your account once we've processed your order:</p>
					<label>
						<input type="radio" name="payment_option" value="pay_deposit" required>
						Pay Minimum Deposit (<span class="min-deposit-amount-price"><?php echo wc_price( $min_deposit ); ?></span>)
					</label><br>
					<!--<label>
						<input type="radio" name="payment_option" value="pay_full" required>
						Pay in Full (<span class="full-amount-price"><?php //echo WC()->cart->get_total(); ?></span>)
					</label><br>-->
					<label>
						<input type="radio" name="payment_option" value="pay_later" required>
						Pay Later
					</label><br>
                    <label>
                        <input type="radio" name="payment_option" value="pay_custom" required>
                        Pay a Custom Amount
                    </label>
					<input type="number" name="custom_payment_amount" id="custom_payment_amount" placeholder="Enter custom amount" min="<?php echo $min_deposit; ?>" max="<?= floor( WC()->cart->get_total('edit') ); // ADDED BY DEJAN ?>" style="display: none;"><br>	
				</div>
                <?php do_action( 'woocommerce_review_order_before_payment' ); // ADDED BY DEJAN ?>
                <div id="payment" class="woocommerce-checkout-payment">
					<?php if ( WC()->cart->needs_payment() ) : ?>
						<ul class="wc_payment_methods payment_methods methods">
							<?php
							$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
							if ( ! empty( $available_gateways ) ) {
								foreach ( $available_gateways as $gateway ) {
									wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
								}
							} else {
								echo '<li>' . apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? __( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) : __( 'Please fill in your details above to see available payment methods.', 'woocommerce' ) ) . '</li>';
							}
							?>
						</ul>
					<?php endif; ?>
					<div class="form-row place-order">
						<noscript>
							<?php
							/* translators: $1 and $2 opening and closing emphasis tags respectively */
							printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ), '<em>', '</em>' );
							?>
							<br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
						</noscript>
                        
                        <?php
                        
                        // ADDED BY DEJAN
                        // this variable needs to be defined before it can be used below
                        $order_button_text = apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'woocommerce' ) );
                        
                        ?>

						<?php wc_get_template( 'checkout/terms.php' ); ?>

						<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

						<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); ?>

						<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

						<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
					</div>
				</div>
                <?php do_action( 'woocommerce_review_order_after_payment' ); // ADDED BY DEJAN ?>
            </form>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const paymentOptions = document.getElementsByName('payment_option');
                    const customPaymentAmount = document.getElementById('custom_payment_amount');
                    const paymentDetails = document.getElementById('payment-details');

                    paymentOptions.forEach(option => {
                        option.addEventListener('change', function() {
                            if (this.value === 'pay_custom') {
                                customPaymentAmount.style.display = 'block';
                                customPaymentAmount.setAttribute('required', 'required');
                            } else {
                                customPaymentAmount.style.display = 'none';
                                customPaymentAmount.removeAttribute('required');
                            }
                            if( this.value == 'pay_later' )
                                this.closest('body').classList.add('aa-pay-later');
                            else
                                this.closest('body').classList.remove('aa-pay-later');
                        });
                    });

                    document.getElementById('checkout-form-step-3').addEventListener('submit', function(e) {
                        const selectedOption = document.querySelector('input[name="payment_option"]:checked').value;
                        if (selectedOption === 'pay_custom') {
                            const customAmount = parseFloat(customPaymentAmount.value);
                            if (customAmount < <?php echo $min_deposit; ?>) {
                                alert('Custom amount must be at least the minimum deposit.');
                                e.preventDefault();
                                e.stopPropagation(); // ADDED BY DEJAN
                                e.stopImmediatePropagation(); // ADDED BY DEJAN
                            }
                        }
                    });
                });
            </script>
        <?php elseif ($current_step == 4 || $current_step == 'additional-payment-received') : // MODIFIED BY DEJAN ?>
            <!-- Step 4: Confirmation -->
            <?php
            
            if( is_wc_endpoint_url('order-received') && ! empty( $wp->query_vars['order-received'] ) ){
                
                $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
                
                if ( $order && hash_equals( $order->get_order_key(), $order_key ) ) {
                    
                    ?>
                    <?php include get_stylesheet_directory() . '/src/templates/order-table.php'; ?>
                    <?php include get_stylesheet_directory() . '/src/templates/order-transactions.php'; ?>
                    <form id="checkout-form-step-4" method="post">
                        <input type="hidden" name="step" value="4">
                        <?php include get_stylesheet_directory() . '/src/templates/order-billing-details.php'; ?>
                        <?php include get_stylesheet_directory() . '/src/templates/order-passengers-details.php'; ?>
                        <div style="padding: 2em 0">
                            <a href="<?= wc_get_account_endpoint_url('dashboard'); ?>" class="woocommerce-button button aa-button-style">Go to your account page</a>
                        </div>
                        <!-- Place Order button - MODIFIED BY DEJAN: not needed anymore -->
                        <!-- <button type="submit">Place Order</button> -->
                    </form>
                    
                    <script>
                        /* NOTE: SIMILAR CODE EXISTS IN VIEW-ORDER.PHP TEMPLATE! */
                        jQuery(document).ready(function($){
                            var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                            var nonce = '<?php echo wp_create_nonce('custom_nonce'); ?>';
                            
                            // Handle form submission for main traveller details
                            $('#checkout-form-step-4').on('submit', function(e) {
                                e.preventDefault();

                                var formData = new FormData(this);
                                formData.append('action', 'save_main_traveller_details');
                                formData.append('order_id', '<?php echo $order->get_id(); ?>'); // Ensure the order ID is included
                                formData.append('security', nonce);

                                $.ajax({
                                    url: ajaxUrl,
                                    type: 'POST',
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function(response) {
                                        if (response.success) {
                                            alert('Details updated successfully.');
                                            location.reload(); // Reload to reflect changes
                                        } else {
                                            alert('Error saving details: ' + response.data);
                                        }
                                    }
                                });
                            });

                            // Remove Passport Photo
                            $('.remove-passport-photo').on('click', function() {
                                var passengerId = $(this).data('passenger-id');
                                var orderId = '<?php echo $order->get_id(); ?>';

                                var data = {
                                    action: 'remove_passport_photo',
                                    passenger_id: passengerId,
                                    security: nonce,
                                    order_id: orderId
                                };

                                $.post(ajaxUrl, data, function(response) {
                                    if (response.success) {
                                        alert('Passport photo removed.');
                                        location.reload(); // Reload to reflect changes
                                    } else {
                                        alert('Error removing passport photo: ' + response.data);
                                    }
                                });
                            });

                            // Remove Insurance Document
                            $('.remove-insurance-doc').on('click', function() {
                                var passengerId = $(this).data('passenger-id');
                                var docKey = $(this).data('doc-key');
                                var orderId = '<?php echo $order->get_id(); ?>';

                                var data = {
                                    action: 'remove_insurance_doc',
                                    passenger_id: passengerId,
                                    doc_key: docKey,
                                    security: nonce,
                                    order_id: orderId
                                };

                                $.post(ajaxUrl, data, function(response) {
                                    if (response.success) {
                                        alert('Insurance document removed.');
                                        location.reload(); // Reload to reflect changes
                                    } else {
                                        alert('Error removing insurance document: ' + response.data);
                                    }
                                });
                            });

                            // Handle removing flight details
                            $('.remove-flight-details').on('click', function() {
                                var passengerId = $(this).data('passenger-id');
                                var orderId = '<?php echo $order->get_id(); ?>';

                                var data = {
                                    action: 'remove_flight_details',
                                    passenger_id: passengerId,
                                    order_id: orderId,
                                    security: nonce
                                };

                                $.post(ajaxUrl, data, function(response) {
                                    console.log('AJAX Response:', response);
                                    if (response.success) {
                                        alert('Flight details cleared.');
                                        location.reload(); // Reload to reflect changes
                                    } else {
                                        alert('Error clearing flight details: ' + response.data);
                                    }
                                });
                            });

                            // Initialize TinyMCE for review content
                            tinymce.init({
                                selector: '#review_content',
                                menubar: false,
                                toolbar: 'bold italic underline | bullist numlist | alignleft aligncenter alignright | undo redo',
                                height: 200
                            });
                            
                        });
                        </script>
                    
                    
                    <?php
                    
                    

                }
                
            }
            
            ?>
            
        <?php elseif( $current_step == 'order-pay' && ! empty( $wp->query_vars['order-pay'] ) ) : ?>
            <div class="checkout-custom-page-container">
            <?php
            
            $order_id = absint( $wp->query_vars['order-pay'] );
            
            do_action( 'before_woocommerce_pay' );
            
            // disable some payment gateways when doing additional payments
            add_filter('woocommerce_available_payment_gateways', function( $available_gateways ){
            
                foreach( ['cod', 'cheque'] as $method_id ){
                    
                    if( isset( $available_gateways[$method_id] ) )
                        unset( $available_gateways[$method_id] );
                    
                }
            
                return $available_gateways;
            
            }, 10);
            
            $trip_order = new AA_Trip_Order( $order_id );
            
            // Pay for existing order.
            if ( isset( $_GET['pay_for_order'], $_GET['key'] ) && $order_id ) { // WPCS: input var ok, CSRF ok.
                try {
                    $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
                    $order     = wc_get_order( $order_id );

                    // Order or payment link is invalid.
                    if ( ! $order || $order->get_id() !== $order_id || ! hash_equals( $order->get_order_key(), $order_key ) ) {
                        throw new Exception( __( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ) );
                    }

                    // Logged out customer does not have permission to pay for this order.
                    if ( ! current_user_can( 'pay_for_order', $order_id ) && ! is_user_logged_in() ) {
                        echo '<div class="woocommerce-info">' . esc_html__( 'Please log in to your account below to continue to the payment form.', 'woocommerce' ) . '</div>';
                        woocommerce_login_form(
                            array(
                                'redirect' => $order->get_checkout_payment_url(),
                            )
                        );
                        return;
                    }

                    // Add notice if logged in customer is trying to pay for guest order.
                    if ( ! $order->get_user_id() && is_user_logged_in() ) {
                        // If order has does not have same billing email then current logged in user then show warning.
                        if ( $order->get_billing_email() !== wp_get_current_user()->user_email ) {
                            wc_print_notice( __( 'You are paying for a guest order. Please continue with payment only if you recognize this order.', 'woocommerce' ), 'error' );
                        }
                    }

                    // Logged in customer trying to pay for someone else's order.
                    if ( ! current_user_can( 'pay_for_order', $order_id ) ) {
                        throw new Exception( __( 'This order cannot be paid for. Please contact us if you need assistance.', 'woocommerce' ) );
                    }

                    // Does not need payment.
                    if ( ! $order->needs_payment() ) {
                        /* translators: %s: order status */
                        throw new Exception( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance.', 'woocommerce' ), wc_get_order_status_name( $order->get_status() ) ) );
                    }

                    // Ensure order items are still stocked if paying for a failed order. Pending orders do not need this check because stock is held.
                    if ( ! $order->has_status( wc_get_is_pending_statuses() ) ) {
                        $quantities = array();

                        foreach ( $order->get_items() as $item_key => $item ) {
                            if ( $item && is_callable( array( $item, 'get_product' ) ) ) {
                                $product = $item->get_product();

                                if ( ! $product ) {
                                    continue;
                                }

                                $quantities[ $product->get_stock_managed_by_id() ] = isset( $quantities[ $product->get_stock_managed_by_id() ] ) ? $quantities[ $product->get_stock_managed_by_id() ] + $item->get_quantity() : $item->get_quantity();
                            }
                        }

                        // Stock levels may already have been adjusted for this order (in which case we don't need to worry about checking for low stock).
                        if ( ! $order->get_data_store()->get_stock_reduced( $order->get_id() ) ) {
                            foreach ( $order->get_items() as $item_key => $item ) {
                                if ( $item && is_callable( array( $item, 'get_product' ) ) ) {
                                    $product = $item->get_product();

                                    if ( ! $product ) {
                                        continue;
                                    }

                                    if ( ! apply_filters( 'woocommerce_pay_order_product_in_stock', $product->is_in_stock(), $product, $order ) ) {
                                        /* translators: %s: product name */
                                        throw new Exception( sprintf( __( 'Sorry, "%s" is no longer in stock so this order cannot be paid for. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name() ) );
                                    }

                                    // We only need to check products managing stock, with a limited stock qty.
                                    if ( ! $product->managing_stock() || $product->backorders_allowed()  ) {
                                        continue;
                                    }

                                    // Check stock based on all items in the cart and consider any held stock within pending orders.
                                    $held_stock     = wc_get_held_stock_quantity( $product, $order->get_id() );
                                    $required_stock = $quantities[ $product->get_stock_managed_by_id() ];

                                    if ( ! apply_filters( 'woocommerce_pay_order_product_has_enough_stock', ( $product->get_stock_quantity() >= ( $held_stock + $required_stock ) ), $product, $order ) ) {
                                        /* translators: 1: product name 2: quantity in stock */
                                        throw new Exception( sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s available). We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity() - $held_stock, $product ) ) );
                                    }
                                }
                            }
                        }
                    }

                    WC()->customer->set_props(
                        array(
                            'billing_country'  => $order->get_billing_country() ? $order->get_billing_country() : null,
                            'billing_state'    => $order->get_billing_state() ? $order->get_billing_state() : null,
                            'billing_postcode' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
                        )
                    );
                    WC()->customer->save();

                    $available_gateways = WC()->payment_gateways->get_available_payment_gateways();

                    if ( count( $available_gateways ) ) {
                        current( $available_gateways )->set_current();
                    }

                    /**
                     * Allows the text of the submit button on the Pay for Order page to be changed.
                     *
                     * @param string $text The text of the button.
                     *
                     * @since 3.0.2
                     */
                    $order_button_text = apply_filters( 'woocommerce_pay_order_button_text', __( 'Pay for order', 'woocommerce' ) );

                    /**
                     * Triggered right before the Pay for Order form, after validation of the order and customer.
                     *
                     * @param WC_Order $order              The order that is being paid for.
                     * @param string   $order_button_text  The text for the submit button.
                     * @param array    $available_gateways All available gateways.
                     *
                     * @since 6.6
                     */
                    do_action( 'before_woocommerce_pay_form', $order, $order_button_text, $available_gateways );
                    
                    $next_payment_rules = $trip_order->get_next_payment_rules();
                    
                    if( ! empty( $next_payment_rules['allowed_payment_options'] ) ){
                        
                        ob_start();
                        
                        ?>
                        <div id="pay-for-order-payment_option-container">
                            <div>Select how you would like to pay today.</div>
                            <?php if( in_array( 'pay_deposit', $next_payment_rules['allowed_payment_options'] ) ): ?>
                            <label>
                                <input type="radio" name="payment_option" value="pay_deposit" required>
                                Pay Minimum Deposit (<?php echo wc_price( $next_payment_rules['min_payment_amount'] ); ?>)
                            </label><br>
                            <?php endif; ?>
                            <?php if( in_array( 'pay_full', $next_payment_rules['allowed_payment_options'] ) ): ?>
                            <label>
                                <input type="radio" name="payment_option" value="pay_full" required>
                                Pay in Full (<?php echo wc_price( $next_payment_rules['max_payment_amount'] ); ?>)
                            </label><br>
                            <?php endif; ?>
                            <?php if( in_array( 'pay_custom', $next_payment_rules['allowed_payment_options'] ) ): ?>
                            <label>
                                <input type="radio" name="payment_option" value="pay_custom" required>
                                Pay a Custom Amount
                            </label>
                            <input type="number" name="custom_payment_amount" id="custom_payment_amount" placeholder="Enter custom amount" min="<?php echo $next_payment_rules['min_payment_amount']; ?>" max="<?= $next_payment_rules['max_payment_amount']; ?>" style="display: none;"><br>
                            <?php endif; ?>
                        </div>
                        <?php
                        
                        $payment_options_content = ob_get_clean();
                    
                    }
                    
                    // this template contains both order items table and pay section
                    // we need to put this template content into a variable, so that we can insert our code in the middle of WC template
                    ob_start();
                    wc_get_template(
                        'checkout/form-pay.php',
                        array(
                            'order'              => $order,
                            'available_gateways' => $available_gateways,
                            'order_button_text'  => $order_button_text,
                        )
                    );
                    $form_pay_content = ob_get_clean();
                    
                    echo str_replace( 
                        '<div id="payment"',
                        ( $payment_options_content ?? '' ) . '<div id="payment"',
                        $form_pay_content
                    );

                } catch ( Exception $e ) {
                    wc_print_notice( $e->getMessage(), 'error' );
                }
            } elseif ( $order_id ) {

                // Pay for order after checkout step.
                $order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // WPCS: input var ok, CSRF ok.
                $order     = wc_get_order( $order_id );

                if ( $order && $order->get_id() === $order_id && hash_equals( $order->get_order_key(), $order_key ) ) {

                    if ( $order->needs_payment() ) {

                        wc_get_template( 'checkout/order-receipt.php', array( 'order' => $order ) );

                    } else {
                        /* translators: %s: order status */
                        wc_print_notice( sprintf( __( 'This order&rsquo;s status is &ldquo;%s&rdquo;&mdash;it cannot be paid for. Please contact us if you need assistance.', 'woocommerce' ), wc_get_order_status_name( $order->get_status() ) ), 'error' );
                    }
                } else {
                    wc_print_notice( __( 'Sorry, this order is invalid and cannot be paid for.', 'woocommerce' ), 'error' );
                }
            } else {
                wc_print_notice( __( 'Invalid order.', 'woocommerce' ), 'error' );
            }

            do_action( 'after_woocommerce_pay' );
            
            ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<script type="text/javascript">
    jQuery(document).ready(function($) {
		var current_trip_product_id = <?= json_encode($current_trip_product_id); ?>;
		var merged_data = <?= json_encode($merged_data); ?>;
		console.log('Merged Data:', merged_data);

		let current_step = $('input[name="step"]').val() || 1;

		const numPassengersInput = $('#num_passengers');
		const passengerDetailsContainer = $('#passenger-details-container');
		const passengerDetailsTemplate = $('#passenger-details-template').length ? $('#passenger-details-template').html() : null;

		if (numPassengersInput.length && passengerDetailsContainer.length && passengerDetailsTemplate) {
			function updatePassengerDetails() {
				const numPassengers = parseInt(numPassengersInput.val());
				passengerDetailsContainer.html('');
				for (let i = 0; i < numPassengers; i++) {
					const passengerDetail = $('<div>').html(passengerDetailsTemplate);
					passengerDetail.find('.passenger-number').text(i + 1);

					// Set required attribute if numPassengers > 0
					passengerDetail.find('input, select').each(function() {
						if (numPassengers > 0) {
							$(this).attr('required', 'required');
						} else {
							$(this).removeAttr('required');
						}
					});

					// Populate existing data if available
					if (merged_data['passenger_first_name'] && merged_data['passenger_first_name'][i]) {
						passengerDetail.find('input[name="passenger_first_name[]"]').val(merged_data['passenger_first_name'][i]);
						passengerDetail.find('input[name="passenger_last_name[]"]').val(merged_data['passenger_last_name'][i]);
						passengerDetail.find('select[name="passenger_gender[]"]').val(merged_data['passenger_gender'][i]);
						passengerDetail.find('input[name="passenger_dob[]"]').val(merged_data['passenger_dob'][i]);
						passengerDetail.find('input[name="passenger_email[]"]').val(merged_data['passenger_email'][i]);
					}

					passengerDetailsContainer.append(passengerDetail);
				}
			}

			numPassengersInput.change(updatePassengerDetails);
			updatePassengerDetails(); // Initial call
		}

		// Handle progress tracker clicks
		$('.progress-tracker a').on('click', function(e) {
			const step = $(this).attr('href').split('=')[1];
			if (step) {
				e.preventDefault();
				const form = $(`#checkout-form-step-${current_step}`);
                if (form.length) {
                    
                    // ADDED BY DEJAN - we don't want to use default form, because on step 3 form submit will try to submit the order
                    const temp_form = $('<form style="display:none;" method="post" action="">').appendTo('body');
					
                    $('<input>').attr({
						type: 'hidden',
						name: 'target_step',
						value: step
					}).appendTo(temp_form); // MODIFIED BY DEJAN - "temp_form" instead of "form"
					temp_form.submit(); // MODIFIED BY DEJAN - "temp_form" instead of "form"
				} else {
					console.error('Form not found for current step:', current_step);
				}
			}
		});

		// Handle form submissions to the next step
		$('form').on('submit', function() {
			const next_step = parseInt(current_step) + 1;
			$('<input>').attr({
				type: 'hidden',
				name: 'step',
				value: next_step
			}).appendTo(this);
		});

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
		const toggleButtons = $('.toggle-cart-button');

		plusButtons.on('click', function() {
			const productId = $(this).data('product-id');
			const quantityInput = $(`.product-quantity[data-product-id="${productId}"]`);
			let quantity = parseInt(quantityInput.val());
			quantity += 1;
			quantityInput.val(quantity);

			disableButtons(productId);
			addToCart(productId, 1);
		});

		minusButtons.on('click', function() {
			const productId = $(this).data('product-id');
			const quantityInput = $(`.product-quantity[data-product-id="${productId}"]`);
			let quantity = parseInt(quantityInput.val());
			if (quantity > 0) {
				quantity -= 1;
				quantityInput.val(quantity);

				disableButtons(productId);
				removeOneFromCart(productId);
			}
		});

		toggleButtons.on('click', function() {
			const productId = $(this).data('product-id');
			const isAdded = $(this).toggleClass('added').hasClass('added');
			$(this).text(isAdded ? 'Remove' : 'Add');

			if (isAdded) {
				addToCart(productId, 1);
			} else {
				removeOneFromCart(productId);
			}
		});

		function addToCart(productId, quantity) {
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'woocommerce_add_to_cart',
					product_id: productId,
					quantity: quantity,
					security: wc_add_to_cart_params.add_to_cart_nonce,
                    disable_wc_notices : 1 // ADDED BY DEJAN - we don't need to show notices each time qty is updated
				},
				success: function(response) {
					console.log('Added to cart:', response);
					updateBasket();
					enableButtons(productId);
				},
				error: function(error) {
					console.log('Error adding to cart:', error);
					enableButtons(productId);
				}
			});
		}

		function removeOneFromCart(productId) {
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'custom_remove_one_from_cart',
					product_id: productId,
					security: wc_add_to_cart_params.remove_from_cart_nonce
				},
				success: function(response) {
					console.log('Removed one from cart:', response);
					updateBasket();
					enableButtons(productId);
				},
				error: function(error) {
					console.log('Error removing one from cart:', error);
					enableButtons(productId);
				}
			});
		}

		function removeFromCart(productId) {
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'custom_remove_from_cart',
					product_id: productId,
					security: wc_add_to_cart_params.remove_from_cart_nonce
				},
				success: function(response) {
					console.log('Removed from cart:', response);
					updateBasket();
				},
				error: function(error) {
					console.log('Error removing from cart:', error);
				}
			});
		}

		$('.remove-product-form').on('submit', function(e) {
			e.preventDefault();
			const cart_item_key = $(this).find('input[name="cart_item_key"]').val();

			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'custom_remove_from_cart',
					cart_item_key: cart_item_key,
					security: wc_add_to_cart_params.remove_from_cart_nonce
				},
				success: function(response) {
					if (response.success) {
						location.reload();
					} else {
						console.error('Failed to remove item:', response.data);
					}
				},
				error: function(error) {
					console.error('Error removing item:', error);
				}
			});
		});

		if (numPassengersInput.length) {
			numPassengersInput.on('change', function() {
				const numPassengers = parseInt($(this).val());
				updateTripQuantity(numPassengers + 1);
				//updatePassengerCountInSession(numPassengers);
			});
		}

		function updateTripQuantity(quantity) {
            
            if( ! window.updateTripQuantityCounter )
                window.updateTripQuantityCounter = 0
            
            updateTripQuantityCounter++;
            
            let local_trip_qty_counter = updateTripQuantityCounter; // prevent multiple updates when changing input value quickly.
            
			const productId = current_trip_product_id;
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'update_trip_quantity',
					product_id: productId,
					quantity: quantity,
					security: wc_add_to_cart_params.update_cart_nonce
				},
				success: function(response) {
					console.log('Updated trip quantity:', response);
					if( local_trip_qty_counter === updateTripQuantityCounter )
                        updateBasket();
				},
				error: function(error) {
					console.log('Error updating trip quantity:', error);
				}
			});
		}

		/*function updatePassengerCountInSession(numPassengers) {
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'update_passenger_count',
					num_passengers: numPassengers,
					security: wc_add_to_cart_params.update_cart_nonce
				},
				success: function(response) {
					console.log('Updated passenger count in session:', response);
					updateBasket();
				},
				error: function(error) {
					console.log('Error updating passenger count in session:', error);
				}
			});
		}*/

		function updateBasket() {
			$.ajax({
				url: wc_add_to_cart_params.ajax_url,
				type: 'POST',
				data: {
					action: 'update_basket',
                    payment_currency : $('input[name="payment_currency"]').filter(':checked').val()// ADDED BY DEJAN
				},
				success: function(response) {
					if (response.success) {
						$('.basket').html(response.data.basket_html);
                        
                        // ADDED BY DEJAN
                        $('span.min-deposit-amount-price').html( $('.basket .min-deposit .amount').html() );
                        $('span.full-amount-price').html( $('.basket .cart-total .amount').html() );
                        
                        // if basket is empty, hide checkout form - ADDED BY DEJAN
                        if( 'basket_count' in response.data && ! response.data.basket_count )
                            $('form[name="checkout"]').hide();
                        
						$('.remove-product-form').on('submit', function(e) {
							e.preventDefault();
							const cart_item_key = $(this).find('input[name="cart_item_key"]').val();

							$.ajax({
								url: wc_add_to_cart_params.ajax_url,
								type: 'POST',
								data: {
									action: 'custom_remove_from_cart',
									cart_item_key: cart_item_key,
									security: wc_add_to_cart_params.remove_from_cart_nonce
								},
								success: function(response) {
									if (response.success) {
										updateBasket();
									} else {
										console.error('Failed to remove item:', response.data);
									}
								},
								error: function(error) {
									console.error('Error removing item:', error);
								}
							});
						});
					} else {
						console.log('Failed to update basket:', response.data);
					}
				},
				error: function(error) {
					console.log('Error updating basket:', error);
				}
			});
		}

		// Initial basket update on page load
		updateBasket();

		function disableButtons(productId) {
			const plusButton = $(`.plus-button[data-product-id="${productId}"]`);
			const minusButton = $(`.minus-button[data-product-id="${productId}"]`);
			const toggleButton = $(`.toggle-cart-button[data-product-id="${productId}"]`);

			if (plusButton.length) plusButton.prop('disabled', true);
			if (minusButton.length) minusButton.prop('disabled', true);
			if (toggleButton.length) toggleButton.prop('disabled', true);
		}

		function enableButtons(productId) {
			const plusButton = $(`.plus-button[data-product-id="${productId}"]`);
			const minusButton = $(`.minus-button[data-product-id="${productId}"]`);
			const toggleButton = $(`.toggle-cart-button[data-product-id="${productId}"]`);

			if (plusButton.length) plusButton.prop('disabled', false);
			if (minusButton.length) minusButton.prop('disabled', false);
			if (toggleButton.length) toggleButton.prop('disabled', false);
		}

		if (typeof cartItemQuantities !== 'undefined') {
			for (let productId in cartItemQuantities) {
				const quantityInput = $(`.product-quantity[data-product-id="${productId}"]`);
				if (quantityInput.length) {
					quantityInput.val(cartItemQuantities[productId]);
				}
			}
		}

		// Trigger update checkout on payment method change
		$('form.checkout').on('change', 'input[name="payment_method"], input[name="payment_option"]', function() {
			$('body').trigger('update_checkout');
			console.log('Payment Method Changed');
		});
        
        // >>> ADDED BY DEJAN

        // Trigger update checkout on currency change
        $('form.checkout').on('change', 'input[name="payment_currency"]', function() {
            updateBasket();
            $('body').trigger('update_checkout');
            console.log('Currency Changed');
        });
        
        const paymentOptions = document.getElementsByName('payment_option');
        const customPaymentAmount = document.getElementById('custom_payment_amount');

        paymentOptions.forEach(option => {
            option.addEventListener('change', function() {
                if (this.value === 'pay_custom') {
                    customPaymentAmount.style.display = 'block';
                    customPaymentAmount.setAttribute('required', 'required');
                } else {
                    customPaymentAmount.style.display = 'none';
                    customPaymentAmount.removeAttribute('required');
                }
            });
        });
        
        // <<< ADDED BY DEJAN
        $(document).ready(function() {
			// Change the label for the billing_state field
			$('label[for="billing_state"]').text('State / County');

			// Listen for the country change event and update the label
			$(document.body).on('country_to_state_changed', function() {
				$('label[for="billing_state"]').text('State / County');
			});
		});
		$(document).ready(function() {
			// Move UK, Ireland, and US to the top
			var countriesToMove = ['US', 'IE', 'GB'];
			var $select = $('#billing_country');

			countriesToMove.forEach(function(countryCode) {
				var $option = $select.find('option[value="' + countryCode + '"]');
				if ($option.length) {
					$option.remove();
					$select.prepend($option);
				}
			});

			// Optionally, you can select one of them by default
			// $select.val('GB').trigger('change');
		});
	});
    

</script>

<?php
get_footer();
?>