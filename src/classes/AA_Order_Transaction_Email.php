<?php

class AA_Order_Transaction_Email extends WC_Email{

    public function __construct(){
        
        $this->id             = 'new_transaction';
        $this->customer_email = true;
        
        $this->title          = 'New Transaction';
        $this->description    = 'This email is sent when a new transaction is added to an order.';
        $this->heading        = 'New Transaction Added';
        $this->subject        = 'New Transaction of {transaction_amount} Added to Your Order #{order_number}';
        
        // Email template path
        $this->template_html  = 'emails/new-transaction.php';
        $this->template_plain = 'emails/plain/new-transaction.php';
        
        $this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
			'{transaction_date}' => '',
			'{transaction_description}' => '',
			'{transaction_amount}' => ''
		);

        // Triggers for this email
        add_action( 'woocommerce_new_transaction_notification', [ $this, 'trigger' ], 10, 2 );

        // Call parent constructor to load any other defaults
        parent::__construct();
        
    }

	// Get default email subject.
	public function get_default_subject(){
		return 'New Transaction of {transaction_amount} Added to Your Order #{order_number}';
	}

	// Get default email heading.
	public function get_default_heading(){
		return 'New Transaction Added';
	}
	
	// Get default additional content
	public function get_default_additional_content(){
		return '';
	}

    // Trigger the email sending
    public function trigger( $order_id, $transaction_id ){
    	
        if( $order_id && $transaction_id ) {
        
            $this->object = wc_get_order( $order_id );
            $this->transaction_id = $transaction_id;
            $this->transactions = new AA_Order_Transactions( $this->object );
            $this->transaction = $this->transactions->get_transaction_by_id( $this->transaction_id );
			$customer_email = $this->object->get_billing_email();
			$admin_email = 'office@adventurealternative.com';
            $this->recipient = $customer_email . ',' . $admin_email;
            
            $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
			$this->placeholders['{transaction_date}'] = $this->transactions->get_datetime_string_from_timestamp( $this->transaction['time'] ?? '' );
			$this->placeholders['{transaction_description}'] = esc_html( $this->transaction['description'] ?? '' );
			$this->placeholders['{transaction_amount}'] = html_entity_decode( strip_tags( $this->transactions->wc_price( $this->transaction['amount'] ?? '' ) ) );

            if( $this->is_enabled() && $this->get_recipient() )
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
            
        }
        
    }

    // Get content for email
    public function get_content(){
    	
        return $this->format_string( $this->get_template_html() );
        
    }

    // HTML email template
    public function get_template_html(){
    	
        return wc_get_template_html( $this->template_html, [
            'order'         		=> $this->object,
            'transactions'  		=> $this->transactions,
            'transaction_id'		=> $this->transaction_id,
            'transaction'   		=> $this->transaction,
            'email_heading' 		=> $this->get_heading(),
            'additional_content' 	=> $this->get_additional_content(),
            'sent_to_admin' 		=> false,
            'plain_text'    		=> false,
            'email'         		=> $this,
        ]);
        
    }

    // Plain email template
    public function get_template_plain(){
    	
        return wc_get_template_html( $this->template_plain, [
            'order'         		=> $this->object,
            'transactions'  		=> $this->transactions,
            'transaction_id'		=> $this->transaction_id,
            'transaction'   		=> $this->transaction,
            'email_heading' 		=> $this->get_heading(),
            'additional_content' 	=> $this->get_additional_content(),
            'sent_to_admin' 		=> false,
            'plain_text'    		=> true,
            'email'         		=> $this,
        ]);
    }
    
}