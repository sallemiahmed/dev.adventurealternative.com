<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>

<style>
	.shop_table, #order-transactions{
		margin:10px 0 18px;
		width: 100%;
	}
	.shop_table td, .shop_table th, #order-transactions td, #order-transactions th{
		border:1px solid #212121;
		padding: 3px 6px;
	}
	td{
		padding: 3px 6px;
	}
	.custom-section a{
		display:none;
	}
	p, table, h3{
		margin-bottom:0px;
	}
	h1, h2, h3{
		color:#B21F24;
	}
	.abtot-img img{
		height: 60px;
		width: auto;
	}
	.shop_table tfoot tr:first-child{
		display:none;
	}
	.shop_table tfoot th{
		text-align:right;
	}
	ul{
		list-style: disc;
		margin-left: 14px;
		margin-bottom:16px;
		margin-top:18px;
	}
</style>

<table class="head container">
	<tr>
		<td class="header">
		<?php
			if ( $this->has_header_logo() ) {
				do_action( 'wpo_wcpdf_before_shop_logo', $this->get_type(), $this->order );
				$this->header_logo();
				do_action( 'wpo_wcpdf_after_shop_logo', $this->get_type(), $this->order );
			} else {
				$this->title();
			}
		?>
		</td>
		<td class="shop-info">
			<?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
			<p><strong>Correspondence Address</strong></p>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
			<p><strong>Tel:</strong> 028708 31258<br>
			<strong>Email:</strong> office@adventurealternative.com<br>
			<strong>Web:</strong> adventurealternative.com</p>
		</td>
	</tr>
</table>
<?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

<?php if ( $this->has_header_logo() ) : ?>
	<h1 class="document-type-label"><?php $this->title(); ?></h1>
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
			<p><?php $this->billing_address(); ?></p>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
				<?php if ( isset( $this->settings['display_number'] ) ) : ?>
					<tr class="invoice-number">
						<th><?php $this->number_title(); ?></th>
						<td><?php $this->number( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( isset( $this->settings['display_date'] ) ) : ?>
					<tr class="invoice-date">
						<th><?php $this->date_title(); ?></th>
						<td><?php $this->date( $this->get_type() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $this->show_due_date() ) : ?>
					<tr class="due-date">
						<th><?php $this->due_date_title(); ?></th>
						<td><?php $this->due_date(); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="order-number">
					<th><?php $this->order_number_title(); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php $this->order_date_title(); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
			</table>
		</td>
	</tr>
</table>
<div class="custom-section">
<h3>Trip Summary</h3>
<?php include get_stylesheet_directory() . '/src/templates/order-table.php'; ?>

<?php include get_stylesheet_directory() . '/src/templates/order-transactions.php'; ?>
</div>
<p>
	Thank you very much for making your booking with us. Full payment is due six weeks prior to travel and you can make payments in either sterling, dollars or euros in the following ways:
</p>
<p>
	1. Access your client account on our website with the user name and password that was generated when you made the booking, and pay online.
</p>
<p>
	2. Call us on 028708 31258 to make a payment over the phone.
</p>
<p>
	3. Bank transfer to our Ulster Bank accounts, details are below.
</p>
<h3 class="data-title">
	Sterling:
</h3>
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
<h3 class="data-title">
	Dollars:
</h3>
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
<h3 class="data-title">
	Euros:
</h3>
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
<p>
	Bank address for USD and € accounts: Ulster Bank, 11-16 Donegall Square East, Belfast BT1 5UB, UK
</p>
<ul>
	<li>Adventure Alternative complies with the UK Package Travel Regulations for financial failure insurance and all payments are made into a client account which is not touched until your trip occurs.</li>
	<li>All bookings and refunds in the event of cancellation are subject to our <a href="https://www.adventurealternative.com/terms-and-conditions/">Terms and Conditions</a>.</li>
	<li>Do please take out travel insurance now so you are covered for cancellation in the event of an accident of incident which prevent you from travelling.</li>
	<li><a href="https://www.abtot.com/">ABTOT</a> provides protection for your booking as set our in Section 25 of our booking conditions.</li>
</ul>
<p>
	<a href="https://www.abtot.com/" class="abtot-img"><?= wp_get_attachment_image(5490, 'medium'); ?></a>
</p>
<p>
	Thank you and safe travels,
</p>
<p>
	<strong>Adventure Alternative Ltd</strong>
</p>
<p>
	Tel:028708 31258<br>
	Email: office@adventurealternative.com<br>
	Registered company address: Holzern Lodge, Friary, Freshford, Bath BA2 7UE, Somerset, UK
</p>

<?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
