<?php

class AA_Order_Transactions{
	
	protected $order = false;
	protected $order_id = 0;
	protected $order_total = 0;
	protected $transactions = [];
	
	public $just_added_transaction = false;
	
	public function __construct( $order ){
		
		// set order for transactions
		if( is_numeric( $order ) && $order > 0 )
			$this->order = wc_get_order( $order );
		elseif( is_a( $order, 'WC_Order' ) )
			$this->order = $order;
		
		$this->order_id = $this->order ? $this->order->get_id() : 0;
		
		// if order is set, get saved transactions for it, and save original order total into a variable
		if( $this->order_id ){
			
			$this->transactions = get_post_meta( $this->order_id, 'aa_order_transactions', true ) ?: [];
			
			foreach( $this->transactions as &$transaction ){
				
				if( ! empty( $transaction['payment_method'] ) && empty( $transaction['payment_method_title'] ) )
					$transaction['payment_method_title'] = str_replace( '-', ' ', ucfirst( $transaction['payment_method'] ) );
			
			}
			
			// we want to be sure that we get original order total, not filtered one by our code.
			$is_global_filter_active = ! empty( $GLOBALS['disable_order_total_filter'] );
			
			if( ! $is_global_filter_active )
				$GLOBALS['disable_order_total_filter'] = true; 
			
			$this->order_total = $this->order->get_total();
			
			if( ! $is_global_filter_active )
				unset( $GLOBALS['disable_order_total_filter'] );
			
		}
		
	}
	
	// get order id
	public function get_order_id(){
		
		return $this->order_id;
		
	}
	
	// get order
	public function get_order(){
		
		return $this->order;
		
	}
	
	// get all transactions
	public function get(){
		
		return $this->transactions;
		
	}
	
	// get latest transaction
	public function get_latest_transaction(){
		
		// loop through all transactions and find one with highest time value
		foreach( $this->transactions as $transaction ){
			
			if( ! empty( $transaction['time'] ) && $transaction['time'] > ( $latest_transaction['time'] ?? 0 ) )
				$latest_transaction = $transaction;
		
		}
		
		return $latest_transaction ?? false;
		
	}
	
	public function add( $data, $time_delay_check = true ){

		if( empty( $this->order ) || empty( $data['amount'] ) || ! is_numeric( $data['amount'] ) )
			return false;

		// If Stripe/other gateway gave us a unique external id, use it for idempotency.
		$external_id = trim( (string) ( $data['transaction_id'] ?? '' ) );
		if ( $external_id && $this->has_external_txn($external_id) ) {
			// Already recorded; treat as success (idempotent).
			return true;
		}

		$lock = $this->acquire_order_lock(5);
		if (!$lock) {
			// Could not get the lock—fail gracefully rather than risking a clobber.
			return false;
		}

		try {
			// Re-read the latest list inside the lock to avoid stale mutations
			$this->transactions = get_post_meta( $this->get_order_id(), 'aa_order_transactions', true ) ?: [];

			$latest_transaction = $this->get_latest_transaction();

			$new_transaction_data = [
				'id'                    => $data['id'] ?? ( $this->get_order_id() . '-' . bin2hex(random_bytes(8)) ),
				'time'                  => ! empty( $data['custom_time'] ) && is_numeric( $data['custom_time'] ) ? $data['custom_time'] : current_time('timestamp'),
				'description'           => $data['description'] ?? 'Deposit',
				'payment_method'        => $data['payment_method'] ?? '',
				'payment_method_title'  => $data['payment_method_title'] ?? '',
				'transaction_id'        => $external_id, // keep whatever was passed (may be empty)
				'amount'                => $data['amount']
			];

			// SAFER duplicate check:
			// Only use the 2-second + same-amount heuristic when we DO NOT have an external txn id.
			if( $time_delay_check
				&& empty($external_id)
				&& ! empty( $latest_transaction['time'] )
				&& abs( $new_transaction_data['time'] - $latest_transaction['time'] ) <= 2
				&& $latest_transaction['amount'] == $new_transaction_data['amount'] ){
				return false;
			}

			// include new transaction locally
			$this->transactions[] = $this->just_added_transaction = $new_transaction_data;

			// persist
			$updated = update_post_meta( $this->get_order_id(), 'aa_order_transactions', $this->transactions );

			if( ! $updated ){
				$this->just_added_transaction = false;
				array_pop( $this->transactions );
				return false;
			}

			// Record idempotency shadow meta (only if we have a real external id)
			if ($external_id) {
				$this->mark_external_txn($external_id, $new_transaction_data['id']);
			}

			return true;

		} finally {
			$this->release_order_lock($lock);
		}
	}

	
	// update a transaction
	public function update( $id, $data = [] ){

		$lock = $this->acquire_order_lock(5);
		if (!$lock) return false;

		try {
			$transaction_key = $this->get_transaction_key_by_id( $id );

			if( ! empty( $this->transactions[ $transaction_key ] ) ){

				$this->transactions[ $transaction_key ] = array_merge( 
					$this->transactions[ $transaction_key ], 
					array_filter( $data, function( $v ){ return $v !== null; }),
					[
						'updated_time' => current_time('timestamp'),
						'updated_by' => get_current_user_id(),
					]
				);

				return update_post_meta( $this->get_order_id(), 'aa_order_transactions', $this->transactions );
			}

			return false;

		} finally {
			$this->release_order_lock($lock);
		}
	}

	
	// remove a transaction
	public function remove( $ids_to_remove ){

		if( empty( $this->transactions ) )
			return false;

		$lock = $this->acquire_order_lock(5);
		if (!$lock) return false;

		try {
			if( ! is_array( $ids_to_remove ) )
				$ids_to_remove = [ $ids_to_remove ];

			$keep_transactions = [];
			$deleted_transactions = [];

			foreach( $this->transactions as $transaction ){
				if( ! empty( $transaction['id'] ) && in_array( $transaction['id'], $ids_to_remove ) ){
					$deleted_transactions[] = array_merge( $transaction, [ 
						'deleted_time' => current_time('timestamp'), 
						'deleted_by' => get_current_user_id() 
					]);
				}
				else {
					$keep_transactions[] = $transaction;
				}
			}

			if( count( $deleted_transactions ) > 0 ){

				$updated = update_post_meta( $this->get_order_id(), 'aa_order_transactions', $keep_transactions );

				if( $updated ){
					$this->transactions = $keep_transactions;

					if( empty( $this->transactions ) ){
						update_post_meta( $this->get_order_id(), 'deposit_is_paid', 0 );
					}

					foreach( $deleted_transactions as $d_transaction ) {
						add_post_meta( $this->get_order_id(), 'aa_deleted_order_transaction', $d_transaction );
					}

					return count( $deleted_transactions );
				}
			}

			return false;

		} finally {
			$this->release_order_lock($lock);
		}
	}

	
	public function send_transaction_email( $id ){
		
		$transaction = $this->get_transaction_by_id( $id );
		
		if( ! empty( $transaction ) ){
			WC_Emails::instance();
			do_action( 'woocommerce_new_transaction_notification', $this->order_id, $id );
			return true;
		}
		else
			return false;
		
	}
	
	public function get_transaction_by_id( $id ){
		
		if( empty( $id ) )
			return false;
		
		// loop through all transactions and find which ones to return
		foreach( $this->transactions as $transaction ){
			
			if( $id == ( $transaction['id'] ?? '' ) )
				return $transaction;
		
		}
		
		return false;
		
	}
	
	public function get_transaction_key_by_id( $id ){
		
		if( empty( $id ) )
			return false;
		
		// loop through all transactions and find which key to return
		foreach( $this->transactions as $transaction_key => $transaction ){
			
			if( $id == ( $transaction['id'] ?? '' ) )
				return $transaction_key;
		
		}
		
		return false;
		
	}
	
	// get total amount for all transactions
	public function get_transactions_total( $return_cents = false ){

		$total = 0;

		foreach( $this->transactions as $transaction ){
			
			if( empty( $transaction['amount'] ) )
				continue;
			
			$total += wc_add_number_precision( $transaction['amount'] );
		
		}
		
		if( $total > 0 && ! $return_cents )
			$total = wc_remove_number_precision( $total );

		return $total;

	}
	
	// get order total
	public function get_order_total( $return_cents = false ){
		if( ! $this->order )
			return 0;

		// Ensure we get the original, unfiltered total each call
		$is_global_filter_active = ! empty( $GLOBALS['disable_order_total_filter'] );
		if( ! $is_global_filter_active )
			$GLOBALS['disable_order_total_filter'] = true;

		$total = $this->order->get_total();

		if( ! $is_global_filter_active )
			unset( $GLOBALS['disable_order_total_filter'] );

		return $return_cents ? wc_add_number_precision( $total ) : $total;
	}

	
	// get total amount to pay
	public function get_total_to_pay( $return_cents = false ){
		
		$to_pay = max( 0, $this->get_order_total(true) - $this->get_transactions_total(true) );
		
		return $return_cents ? $to_pay : wc_remove_number_precision( $to_pay );

	}
	
	// get total overpaid amount
	public function get_total_overpaid( $return_cents = false ){
		
		$overpaid = max( 0, $this->get_transactions_total(true) - $this->get_order_total(true) );
		
		return $return_cents ? $overpaid : wc_remove_number_precision( $overpaid );

	}
	
	// check if order is paid
	public function is_order_paid(){
		
		return empty( $this->get_total_to_pay(true) );

	}
	
	// check if order is unpaid
	public function is_order_unpaid(){
		
		return ! empty( $this->get_total_to_pay(true) );

	}
	
	// check if order is overpaid
	public function is_order_overpaid(){
		
		return ! empty( $this->get_total_overpaid(true) );

	}
	
	// check if there are existing transactions
	public function exists(){
		
		return ! empty( $this->transactions );

	}
	
	// check existing transactions count
	public function count(){
		
		return empty( $this->transactions ) ? 0 : count( $this->transactions );

	}
	
	// helper function to format price with correct order currency
	public function wc_price( $price ){
		
		return wc_price( $price, [ 'currency' => $this->get_order()->get_currency() ] );

	}
	
	// output table in order edit page
	public function get_admin_table_html(){
		
		ob_start();
		
		?>
		<table id="aa-order-transactions-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Payment method</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach( $this->get() ?: ['no_transactions_found'] as $transaction ): 
            
            if( $transaction === 'no_transactions_found' ){
                
                echo '<tr class="aa-view-row aa-notice-row" data-transaction_id=""><td colspan="6">No transactions found</td></tr>';
                break;
                
            }
                
            ?>
                <tr class="aa-view-row" data-transaction_id="<?= esc_attr( $transaction['id'] ); ?>">
                    <td class="datetime" data-value="<?= wp_date( 'Y-m-d H:i', $transaction['time'] ); ?>"><?= $this->get_datetime_string_from_timestamp( $transaction['time'] ); ?></td>
                    <td class="description" data-value="<?= esc_attr( $transaction['description'] ); ?>"><?= esc_html( $transaction['description'] ); ?></td>
                    <td class="payment-method" data-value="<?= esc_attr( $transaction['payment_method'] ); ?>" data-formatted-value="<?= esc_attr( $transaction['payment_method_title'] ?? '' ); ?>"><?= esc_html( $transaction['payment_method_title'] ?: '-' ); ?></td>
                    <td class="transaction-id" data-value="<?= esc_attr( $transaction['transaction_id'] ); ?>"><?= esc_html( $transaction['transaction_id'] ); ?></td>
                    <td class="amount" data-value="<?= esc_attr( $transaction['amount'] ); ?>"><?= $this->wc_price( $transaction['amount'] ); ?></td>
                    <td class="actions">
                        <div class="multiple-actions">
                            <a href="#" class="transaction-edit" title="Edit"><span class="dashicons dashicons-edit"></span></a>
                            <a href="#" class="transaction-delete" title="Delete"><span class="dashicons dashicons-no"></span></a>
                            <span class="loading-icon"></span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
                <tr class="aa-edit-row" data-transaction_id="">
                    <td class="datetime" data-custom-date="0">
                        <div class="datetime-fields">
                            <label>
                                <input type="checkbox">
                                <span>Custom date<br><span>(if not selected, current date will be used)</span></span>
                            </label>
                            <input type="datetime-local">
                        </div>
                    </td>
                    <td class="description"><input type="text" value="Payment"></td>
                    <td class="payment-method" data-checked-value="bank-transfer">
                    	<?php foreach( self::get_supported_payment_methods(true) as $method_id => $method_label ): ?>
                        <label><input type="radio" name="aa-manual-payment-method" value="<?= esc_attr( $method_id ); ?>"<?= $method_id == 'bank-transfer' ? ' checked' : ''; ?>><?= esc_html( $method_label ); ?></label>
                    	<?php endforeach; ?>
                        <input type="text" class="other-manual-payment-method">
                    </td>
                    <td class="transaction-id"><input type="text"></td>
                    <td class="transaction-amount"><input type="text"></td>
                    <td class="actions">
                        <div class="multiple-actions">
                            <a href="#" class="transaction-save" title="Save"><span class="dashicons dashicons-yes-alt"></span></a>
                            <a href="#" class="transaction-cancel" title="Cancel"><span class="dashicons dashicons-image-rotate"></span></a>
                            <span class="loading-icon"></span>
                        </div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Total Paid:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_transactions_total() ); ?></td>
                    <td class="aa-create-new-transaction"><a href="#"><span class="dashicons dashicons-plus"></span>New</a></td>
                </tr>
                <tr>
                    <td colspan="4">Total Unpaid:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_total_to_pay() ); ?></td>
                    <td></td>
                </tr>
                <?php if( $this->is_order_overpaid() ): ?>
                <tr>
                    <td colspan="4">Total Overpaid:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_total_overpaid() ); ?></td>
                    <td></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
		<?php
		
		$table_html = ob_get_clean();
		
		return $table_html;

	}
	
	// output transactions table in emails
	public function get_email_table_html(){
		
		ob_start();
		
		?>
		<table id="aa-order-transactions-table" border="0" cellpadding="20" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach( $this->get() ?: ['no_transactions_found'] as $transaction ): 
            
            if( $transaction === 'no_transactions_found' ){
                
                echo '<tr class="aa-view-row aa-notice-row"><td colspan="6">No transactions found</td></tr>';
                break;
                
            }
                
            ?>
                <tr class="aa-view-row">
                    <td class="datetime"><?= $this->get_datetime_string_from_timestamp( $transaction['time'] ); ?></td>
                    <td class="description"><?= esc_html( $transaction['description'] ); ?></td>
                    <td class="amount"><?= $this->wc_price( $transaction['amount'] ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total Paid:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_transactions_total() ); ?></td>
                </tr>
                <tr>
                    <td colspan="2">Balance Due:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_total_to_pay() ); ?></td>
                </tr>
                <?php if( $this->is_order_overpaid() ): ?>
                <tr>
                    <td colspan="2">Total Overpaid:</td>
                    <td class="aa-value-column"><?= $this->wc_price( $this->get_total_overpaid() ); ?></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
		<?php
		
		$table_html = ob_get_clean();
		
		return $table_html;

	}
	
	public function get_datetime_string_from_timestamp( $timestamp, $default = '' ){
		
		if( ! empty( $timestamp ) && is_numeric( $timestamp ) )
			return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
		else
			return $default;

	}
	
	public static function get_supported_payment_methods( $include_other = true ){
		
		$methods = [
			'bank-transfer' => 'Bank transfer',
			'phone' => 'Over-the-Phone',
			'stripe' => 'Stripe'
		];
		
		if( $include_other )
			$methods[''] = 'Other';
		
		return $methods;
		
	}
	// --- BEGIN: Concurrency + Idempotency helpers ---

	/**
	 * Acquire a per-order lock using add_option (atomic in WP DB).
	 * Returns the lock key string on success, or false on timeout.
	 */
	private function acquire_order_lock($timeout_seconds = 5){
	    if (!$this->order_id) return false;

	    $key   = 'aa_order_lock_' . $this->order_id;
	    $start = microtime(true);

	    do {
	        // add_option returns true only if the option did NOT exist (atomic)
	        $acquired = add_option($key, time(), '', 'no'); // autoload = no
	        if ($acquired) {
	            return $key;
	        }
	        usleep(150000); // 150ms backoff
	    } while ((microtime(true) - $start) < $timeout_seconds);

	    return false;
	}

	/** Always release the lock you acquired. */
	private function release_order_lock($key){
	    if ($key) {
	        delete_option($key);
	    }
	}

	/** Has an external txn id already been recorded for this order? (idempotency) */
	private function has_external_txn($external_id){
	    if (!$external_id) return false;
	    $meta_key = '_aa_txn_ext_' . sanitize_key($external_id);
	    return (bool) get_post_meta($this->order_id, $meta_key, true);
	}

	/** Mark an external txn id as recorded (atomic “once only”). */
	private function mark_external_txn($external_id, $local_txn_id){
	    if (!$external_id) return;
	    $meta_key = '_aa_txn_ext_' . sanitize_key($external_id);
	    // unique add prevents double-writes
	    add_post_meta($this->order_id, $meta_key, $local_txn_id, true);
	}

	// --- END: Concurrency + Idempotency helpers ---

	
}
