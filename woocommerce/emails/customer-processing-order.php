<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php /* translators: %s: Customer first name */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>
<p><?php printf( esc_html__( 'Here is a summary of your order, please see your attached invoice for the full details and how to make further payment.', 'woocommerce' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

?>

	<p>Thank you very much for making your booking with us. Full payment is due six weeks prior to travel and you can make payments in either sterling, dollars or euros in the following ways:</p>
	<p>1. Access your client account on our website with the user name and password that was generated when you made the booking, and pay online.</p>
	<p>2. Call us on 028708 31258 to make a payment over the phone.</p>
	<p>3. Bank transfer to our Ulster Bank accounts, details are below.</p>

	<h3 class="data-title">Sterling:</h3>
	<table class="data-table">
		<tr>
			<td>Sort code: 98-04-40</td>
			<td>Account number: 24044274</td>
		</tr>
		<tr>
			<td>Swift code: ULSB GB2B</td>
			<td>IBAN: GB46 ULSB 9804 4024 0442 74</td>
		</tr>
	</table>

	<h3 class="data-title">Dollars:</h3>
	<table class="data-table">
		<tr>
			<td>Sort code: 98-00-05</td>
			<td>Account number: 50396819</td>
		</tr>
		<tr>
			<td>Swift code/BIC: ULSBGB2B</td>
			<td>IBAN: GB65ULSB98000550396819</td>
		</tr>
	</table>

	<h3 class="data-title">Euros:</h3>
	<table class="data-table">
		<tr>
			<td>Sort code: 98-00-05</td>
			<td>Account number: 50396736</td>
		</tr>
		<tr>
			<td>Swift code/BIC: ULSBGB2B</td>
			<td>IBAN: GB75ULSB98000550396736</td>
		</tr>
	</table>

	<p>Bank address for USD and € accounts: Ulster Bank, 11-16 Donegall Square East, Belfast BT1 5UB, UK</p>

	<ul>
		<li>Adventure Alternative complies with the UK Package Travel Regulations for financial failure insurance and all payments are made into a client account which is not touched until your trip occurs.</li>
		<li>All bookings and refunds in the event of cancellation are subject to our <a href="https://www.adventurealternative.com/terms-and-conditions/">Terms and Conditions</a>.</li>
		<li>Do please take out travel insurance now so you are covered for cancellation in the event of an accident or incident which prevents you from travelling.</li>
		<li><a href="https://www.abtot.com/">ABTOT</a> provides protection for your booking as set out in Section 25 of our booking conditions.</li>
	</ul>

	<p>
		<a href="https://www.abtot.com/" class="abtot-img"><?= wp_get_attachment_image(5490, 'medium'); ?></a>
	</p>

	<p>Thank you and safe travels,</p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );