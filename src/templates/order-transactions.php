<?php

/*
Template for displaying order's transactions details
Before including this template, these PHP variables should already be defined: $order
*/

if( ! isset( $trip_order ) )
	$trip_order = new AA_Trip_Order( $order );

$transactions = $trip_order->get_transactions();

?>
<div class="order-transactions-container">
	<h3>Payment Summary</h3>
	<table id="order-transactions">
		<thead>
			<tr>
		        <th>Date</th>
		        <th>Description</th>
		        <!--<th>Payment method</th>-->
		        <th>Amount</th>
		    </tr>
		</thead>
		<tbody>
		<?php if( $transactions->exists() ): ?>
		    <?php foreach( $transactions->get() as $transaction ): 
		    
		    	if( ! empty( $transaction['payment_method_title'] ) )
		    		$payment_method = $transaction['payment_method_title'];
		    	elseif( $transaction['payment_method'] == 'stripe' )
		    		$payment_method = 'Credit / Debit Card';
		    	else
		    		$payment_method = ucfirst( $transaction['payment_method'] ?: '-' );
		    
		    ?>
	        <tr>
	            <td><?= wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $transaction['time'] ); ?></td>
	            <td><?= esc_html( $transaction['description'] ); ?></td>
	            <!--<td><?= esc_html( $payment_method ); ?></td>-->
	            <td><?= $transactions->wc_price( $transaction['amount'] ); ?></td>
	        </tr>
		    <?php endforeach; ?>
		<?php else: ?>
			<tr>
				<td colspan="10">No payments found. <?= $transactions->is_order_unpaid() ? '<br>' . $trip_order->get_pay_button_html('Make a payment') : ''; ?></td>
			</tr>
		<?php endif; ?>
	    </tbody>
		<?php if( $transactions->exists() ): ?>
	    <tfoot>
	        <tr>
	            <th colspan="2">Total Paid:</th>
	            <td><?= $transactions->wc_price( $transactions->get_transactions_total() ); ?></td>
	        </tr>
	        <tr>
	            <th colspan="2">Balance Due:</th>
	            <td><?= $transactions->wc_price( $transactions->get_total_to_pay() ); if( $transactions->is_order_unpaid() ) echo $trip_order->get_pay_button_html(); ?></td>
	        </tr>
	    </tfoot>
		<?php endif; ?>
	</table>
</div>