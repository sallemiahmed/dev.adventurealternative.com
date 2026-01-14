<?php

// register "new transaction" email
add_filter( 'woocommerce_email_classes', function( $email_classes ) {
    
    $email_classes['AA_Order_Transaction_Email'] = new AA_Order_Transaction_Email();
    
    return $email_classes;
    
});

// include transactions table in emails
add_action('woocommerce_email_after_order_table', function( $order, $sent_to_admin, $plain_text, $email ){

    $transactions = new AA_Order_Transactions( $order );
    
    if( $transactions->exists() ){
        
        echo '<h2>Payment Summary</h2>';
        
        echo $transactions->get_email_table_html();
        
        echo '<br><br>';
        
    }

}, 10, 4 );

// add our custom CSS to emails
add_filter('woocommerce_email_styles', function( $css ){
    
    ob_start();
    
    ?>
    table#aa-order-transactions-table{
        border: 1px solid #e5e5e5;
    }
    table#aa-order-transactions-table th,
    table#aa-order-transactions-table td{
        border: 1px solid #e5e5e5;
    }
    <?php
    
    return $css . ob_get_clean();

}, 10);