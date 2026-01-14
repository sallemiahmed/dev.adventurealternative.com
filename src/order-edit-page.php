<?php

if( isset( $_GET['aa_debug'] ) ){

    add_action( 'woocommerce_admin_order_items_after_line_items', function( $order_id  ) {
        
        $checkout_data = [
            'aa_debug_data' => get_post_meta( $order_id, 'aa_debug_data', true ) ?: [],
            'aa_checkout_data' => get_post_meta( $order_id, 'aa_checkout_data', true ) ?: []
        ];
        
        $checkout_data['deposit_amount'] = get_post_meta( $order_id, 'deposit_amount', true);
        
        echo '<tr><td colspan="80">';
        
        if( ! empty( $checkout_data ) ){
            echo '<h4>AA DEBUG Data</h4>';
            echo '<pre>' . esc_html( print_r( $checkout_data, true ) ) . '</pre>';
        } else {
            echo '<h4>AA DEBUG Data</h4>';
            echo '<p>No data found for this order.</p>';
        }
        
        echo '</td></tr>';
        
    }, 10000);

}

// register our transactions metabox in order edit page
add_action('add_meta_boxes_woocommerce_page_wc-orders', function( $post ){
    
    add_meta_box(
        'aa_order_transactions_metabox', // Metabox ID
        'Transactions', // Metabox title
        'aa_order_transactions_metabox_output', // Callback function
        'woocommerce_page_wc-orders', // Post type is not used here by Woocommerce. So we need to use screen ID instead
        'normal', // Context (side, normal, advanced)
        'high' // Priority (default, high, low)
    );

});

// output transactions metabox content on order edit page
function aa_order_transactions_metabox_output( $order ){
    
    if( ! is_a( $order, 'WC_Order' ) )
        return false;
    
    $transactions = new AA_Order_Transactions( $order );
    
    ?>
    <style>
        .loading-icon{
            width: 18px;
            height: 18px;
            margin: 2px;
            border: 2px solid #2271b1;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: none;
            box-sizing: border-box;
            animation: loading_rotation 1s linear infinite;
        }
        .loading-icon.loading-active{
            display: inline-block;
        }

        @keyframes loading_rotation{
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
        
        #aa-order-transactions-table{
            width: 100%;
        }
        #aa-order-transactions-table thead th,
        #aa-order-transactions-table tfoot td{
            padding: 0.6em 0.5em;
            background: #eee;
            text-align: left;
        }
        #aa-order-transactions-table thead th:last-child{
            width: 40px;
        }
        #aa-order-transactions-table thead th:empty,
        #aa-order-transactions-table tfoot td:empty{
            background: none;
        }
        #aa-order-transactions-table tbody td{
            padding: 0.6em 0.5em;
            text-align: left;
        }
        #aa-order-transactions-table tbody td.actions{
            text-align: center;
            padding-left: 0;
            padding-right: 0;
            width: 26px;
        }
            #aa-order-transactions-table td.actions .multiple-actions{
                display: flex;
                align-items: center;
                gap: 5px;
            }
            #aa-order-transactions-table td.actions a{
                text-decoration: none;
                padding: 3px;
                display: inline-block;
                outline: none;
                box-shadow: none;
            }
            #aa-order-transactions-table td.actions a:focus-visible{
                border: 1px solid #2271b1;
            }
            #aa-order-transactions-table a.transaction-save{
                color: #00a140;
            }
            #aa-order-transactions-table a.transaction-save:hover{
                color: #006c2b;
            }
                #aa-order-transactions-table a.transaction-save span{
                    font-size: 22px;
                }
            #aa-order-transactions-table a.transaction-cancel{
                color: inherit;
            }
            #aa-order-transactions-table a.transaction-cancel:hover{
                color: #000;
            }
                #aa-order-transactions-table a.transaction-cancel span{
                    font-size: 18px;
                }
            #aa-order-transactions-table a.transaction-delete{
                color: #d10000;
            }
            #aa-order-transactions-table a.transaction-delete:hover{
                color: #222;
            }
            #aa-order-transactions-table a.transaction-edit{
                color: #2271b1;
            }
            #aa-order-transactions-table a.transaction-edit:hover{
                color: #0c3352;
            }
            
        #aa-order-transactions-table td.payment-method .other-manual-payment-method{
            display: none;
        }
            
        #aa-order-transactions-table td.payment-method[data-checked-value=""] .other-manual-payment-method{
            display: block;
            margin: 0.5em 0 0;
        }
        
        #aa-order-transactions-table .aa-edit-row > td{
            background: #f1f9ff;
        }
        
        #aa-order-transactions-table .aa-edit-row .payment-method label{
            display: inline-block;
            margin-right: 5px;
        }
        
        #aa-order-transactions-table .aa-edit-row .payment-method label{
            display: inline-block;
            margin-right: 7px;
            white-space: nowrap;
        }
        
        #aa-order-transactions-table .aa-edit-row .payment-method label input[type="radio"]{
            display: inline-block;
            margin-right: 4px;
        }
        
        #aa-order-transactions-table .aa-edit-row .datetime .datetime-fields{
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
            #aa-order-transactions-table .aa-edit-row .datetime .datetime-fields label{
                display: flex;
                align-items: center;
            }
        
                #aa-order-transactions-table .aa-edit-row .datetime .datetime-fields label span span{
                    font-size: 0.8em;
                }
        
        #aa-order-transactions-table .aa-edit-row .datetime input[type="checkbox"] + span{
            display: inline-block;
            margin-left: 4px;
        }
        
        #aa-order-transactions-table .aa-edit-row .datetime[data-custom-date="1"] input[type="checkbox"] + span,
        #aa-order-transactions-table .aa-edit-row .datetime[data-custom-date="0"] input[type="datetime-local"]{
            display: none !important;
        }
        
        #aa-order-transactions-table tfoot td:first-child{
            text-align: right;
            background: none;
            font-weight: 700;
        }
        #aa-order-transactions-table tfoot td.aa-value-column{
            font-weight: 700;
        }
        #aa-order-transactions-table tfoot td.aa-create-new-transaction{
            background: #fff;
        }
            #aa-order-transactions-table tfoot td.aa-create-new-transaction a{
                display: flex;
                gap: 4px;
                align-items: center;
                text-decoration: none;
            }
        #aa-order-transactions-table-notices{
            
        }
        #aa-order-transactions-table-notices:empty{
            display: none;
        }
            #aa-order-transactions-table-notices .notice{
                padding-top: 0.4em;
                padding-bottom: 0.4em;
            }
        
        .aa-order-transactions-table-container tr.aa-view-row.aa-saving-changes,
        .aa-order-transactions-table-container tr.aa-edit-row.aa-saving-changes{
            pointer-events: none;
            opacity: 0.7;
        }
        
        .aa-order-transactions-table-container tr.aa-view-row.aa-saving-changes td.actions span.loading-icon,
        .aa-order-transactions-table-container tr.aa-edit-row.aa-saving-changes td.actions span.loading-icon{
            display: inline-block !important;
        }
        
        .aa-order-transactions-table-container[data-mode="disabled"]{
            opacity: 0.3;
            pointer-events: none;
            user-select: none;
        }
        
        .aa-order-transactions-table-container tr.aa-editing,
        .aa-order-transactions-table-container[data-mode="add-new"] tr.aa-notice-row,
        .aa-order-transactions-table-container[data-mode="view"] tr.aa-edit-row,
        .aa-order-transactions-table-container[data-mode="disabled"] tr.aa-edit-row,
        .aa-order-transactions-table-container[data-mode="delete"] tr.aa-edit-row,
        .aa-order-transactions-table-container[data-mode="add-new"] tr.aa-view-row td.actions > *,
        .aa-order-transactions-table-container[data-mode="edit"] tr.aa-view-row td.actions > *,
        .aa-order-transactions-table-container[data-mode="delete"] tr.aa-view-row:not(.aa-saving-changes) td.actions > *,
        .aa-order-transactions-table-container[data-mode="add-new"] tfoot .aa-create-new-transaction > *,
        .aa-order-transactions-table-container[data-mode="edit"] tfoot .aa-create-new-transaction > *,
        .aa-order-transactions-table-container[data-mode="delete"] tfoot .aa-create-new-transaction > *,
        .aa-order-transactions-table-container tr.aa-view-row.aa-saving-changes td.actions a,
        .aa-order-transactions-table-container tr.aa-edit-row.aa-saving-changes td.actions a{
            display: none !important;
        }
        
        .aa-order-transactions-table-container label{
            user-select: none !important;
        }
        
    </style>
    <script type="text/javascript">
        
        jQuery(function($){
            
            function get_transactions_elements( $target, get_edit_elements ){
                
                let $table = $target ? $target.closest('table') : false;
                
                let els = {
                    container : ( $table ? $table.closest('.aa-order-transactions-table-container') : false ),
                    target : ( $target ? $target : false ),
                    table : $table,
                    row : ( $target ? $target.closest('tr') : false ),
                    loading : ( $target ? $target.closest('tr').find('span.loading-icon') : false ),
                    edit_row : ( $table ? $table.find('tr.aa-edit-row') : false )
                };
                
                if( get_edit_elements ){
                    
                    els.edit_date_td = els.edit_row ? els.edit_row.find('td.datetime') : false;
                    els.edit_date_input = els.edit_row ? els.edit_row.find('td.datetime input[type="datetime-local"]') : false;
                    els.edit_date_input_cb = els.edit_row ? els.edit_row.find('td.datetime input[type="checkbox"]') : false;
                    els.edit_description_input = els.edit_row ? els.edit_row.find('td.description input') : false;
                    els.edit_payment_method_td = els.edit_row ? els.edit_row.find('td.payment-method') : false;
                    els.edit_payment_method_radios = els.edit_row ? els.edit_row.find('td.payment-method input[type="radio"]') : false;
                    els.edit_payment_method_other_input = els.edit_row ? els.edit_row.find('td.payment-method input.other-manual-payment-method') : false;
                    els.edit_transaction_id_input = els.edit_row ? els.edit_row.find('td.transaction-id input') : false;
                    els.edit_amount_input = els.edit_row ? els.edit_row.find('td.transaction-amount input') : false;
                    
                }
                
                return els;
                
            }
            
            let transactions_options = {
                order_id : <?= $order->get_id(); ?>,
                transaction_nonce : '<?= wp_create_nonce('order-transaction-nonce-value'); ?>'
            };
            
            // helper function to show notices above transactions table
            let show_table_notice = function( notice_text, notice_type ){
                
                $('#aa-order-transactions-table-notices').html('<div class="notice is-dismissible notice-' + ( notice_type || 'error' ) + '"><p></p></div>').children('div.notice').text( notice_text );
            
            };
            
            // show/hide payment method input when clicking on payment method radio input
            $('.aa-order-transactions-table-container').on('change', '.aa-edit-row .payment-method input[type="radio"]', function(event) {
                
                $(this).closest('td.payment-method').attr('data-checked-value', $(this).val() );
                
                if( ! $(this).val() )
                    $(this).closest('td.payment-method').find('input.other-manual-payment-method').focus();
                
            });
            
            // show/hide date input when clicking on checkbox
            $('.aa-order-transactions-table-container').on('change', '.datetime input[type="checkbox"]', function(event) {
                
                $(this).closest('.datetime').attr('data-custom-date', $(this).is(':checked') ? 1 : 0 );
                
            });
            
            // allow keyboard enter function on actions links
            $('.aa-order-transactions-table-container').on('keydown', 'td.actions a', function(event){
                
                if( event.key === 'Enter' )
                    $(this).trigger('click');
                
            });
            
            // show edit row
            $('.aa-order-transactions-table-container').on('click', 'a.transaction-edit', function(event){
                
                event.preventDefault();
                
                let $els = get_transactions_elements( $(this), true );
                
                $els.edit_row.attr( 'data-transaction_id', $els.row.attr('data-transaction_id') ).insertAfter( $els.row );
                $els.row.addClass('aa-editing');
                $els.container.attr('data-mode', 'edit');
                
                let datetime = ( $els.row.find('td.datetime').attr('data-value') || '' ).split(' ').join('T');
                
                $els.edit_date_td.attr('data-custom-date', ( !! datetime ? 1 : 0 ) );
                $els.edit_date_input_cb.prop( 'checked', !! datetime );
                $els.edit_date_input.val( datetime ? datetime : '' );
                
                let $selected_payment_radio = $els.edit_payment_method_radios.filter( '[value="' + $els.row.find('td.payment-method').attr('data-value') + '"]');
                
                if( $selected_payment_radio.length && $selected_payment_radio.val() != '' ){
                    $els.edit_payment_method_td.attr('data-checked-value', $selected_payment_radio.val() );
                    $selected_payment_radio.prop( 'checked', true );
                    $els.edit_payment_method_other_input.val('');
                }
                else{
                    $els.edit_payment_method_td.attr('data-checked-value', '' );
                    $els.edit_payment_method_radios.filter( '[value=""]' ).prop( 'checked', true );
                    $els.edit_payment_method_other_input.val( $els.row.find('td.payment-method').attr('data-formatted-value') );
                }
                
                $els.edit_description_input.val( $els.row.find('td.description').attr('data-value') );
                $els.edit_transaction_id_input.val( $els.row.find('td.transaction-id').attr('data-value') );
                $els.edit_amount_input.val( $els.row.find('td.amount').attr('data-value') );
                
            });
            
            // save changes
            $('.aa-order-transactions-table-container').on('click', 'a.transaction-save', function(event){
                
                event.preventDefault();
                
                let $els = get_transactions_elements( $(this), true );
                
                let mode = $els.container.attr('data-mode') || '';
                
                let ajax_data = {
                    action : ( mode == 'add-new' ? 'add_order_transaction' : 'edit_order_transaction' ),
                    t_id : $els.edit_row.attr('data-transaction_id'),
                    t_date : ( $els.edit_date_input_cb.is(':checked') ? $els.edit_date_input.val().split('T').join(' ') : '' ),
                    t_description : $els.edit_description_input.val(),
                    t_payment_method : ( $els.edit_payment_method_radios.filter(':checked').val() ? $els.edit_payment_method_radios.filter(':checked').val() : $els.edit_payment_method_other_input.val() ),
                    t_transaction_id : $els.edit_transaction_id_input.val(),
                    t_amount : $els.edit_amount_input.val(),
                    order_id : transactions_options.order_id,
                    transaction_nonce : transactions_options.transaction_nonce
                };
                
                $els.row.addClass('aa-saving-changes');
                
                $.post( ajaxurl, ajax_data, function( ajax_response, textStatus, xhr){
                    
                    $els.row.removeClass('aa-saving-changes');
                    
                    if( ajax_response && 'data' in ajax_response ){
                        
                        if( 'success_message' in ajax_response.data ){
                            
                            if( 'table_html' in ajax_response.data ){
                                $els.container.attr('data-mode', 'view');
                                $els.table.replaceWith( ajax_response.data.table_html );
                                $els.container.find('.aa-create-new-transaction a').focus();
                            }
                            
                            show_table_notice( ajax_response.data.success_message, 'success' );
                            
                            return;
                            
                        }
                        else if( 'error' in ajax_response.data ){
                            
                            show_table_notice( ajax_response.data.error, 'error' );
                            
                            return;
                            
                        }
                        
                    }
                    
                    show_table_notice( 'Transaction cannot be deleted. Please try again.', 'error' );
                    
                }).fail(function() {
                    
                    $els.row.removeClass('aa-saving-changes');
                    
                    show_table_notice( 'Transaction cannot be deleted. Please try again.', 'error' );
                    
                });
                
            });
            
            // cancel edit mode
            $('.aa-order-transactions-table-container').on('click', 'a.transaction-cancel', function(event){
                
                event.preventDefault();
                
                let $els = get_transactions_elements( $(this), true );
                
                $els.container.attr('data-mode', 'view');
                
                $els.edit_row.siblings('.aa-editing').removeClass('aa-editing');
                
            });
            
            // prevent unintentional order save
            $('.aa-order-transactions-table-container').on('keydown', 'input', function(event){
                
                if( event.key === 'Enter' )
                    event.preventDefault();
                
            });
            
            // create new transaction layout
            $('.aa-order-transactions-table-container').on('click', 'td.aa-create-new-transaction a', function(event){
                
                event.preventDefault();
                
                let $els = get_transactions_elements( $(this).closest('table').find('tr.aa-view-row').last().find('td').first(), true );
                
                $els.container.attr('data-mode', 'add-new');
                $els.edit_row.attr('data-transaction_id', '').insertAfter( $els.row );
                
                let datetime = ( $els.row.find('td.datetime').attr('data-value') || '' ).split(' ').join('T');
                
                $els.edit_date_input_cb.prop( 'checked', false );
                $els.edit_date_td.attr('data-custom-date', 0 );
                $els.edit_date_input.val('');
                
                let $selected_payment_radio = $els.edit_payment_method_radios.first();
                
                $els.edit_payment_method_td.attr('data-checked-value', $selected_payment_radio.val() );
                $selected_payment_radio.prop( 'checked', true );
                $els.edit_payment_method_other_input.val('');
                
                $els.edit_description_input.val('Payment');
                $els.edit_transaction_id_input.val('');
                $els.edit_amount_input.val('').focus();
                
            });
            
            // try to remove transaction when clicking on "X" icon
            $('.aa-order-transactions-table-container').on('click', 'a.transaction-delete', function(event) {
                
                event.preventDefault();
                
                if( ! confirm('Are you sure you want to delete this transaction?') )
                    return false;
                
                let $els = get_transactions_elements( $(this), true );
                
                $els.container.attr('data-mode', 'delete');
                
                $els.row.addClass('aa-saving-changes');
                
                $.post( ajaxurl, { 
                    action : 'delete_order_transaction',
                    t_id : $els.row.attr('data-transaction_id'),
                    order_id : transactions_options.order_id,
                    transaction_nonce : transactions_options.transaction_nonce
                }, function( ajax_response, textStatus, xhr){
                    
                    $els.row.removeClass('aa-saving-changes');
                    
                    if( ajax_response && 'data' in ajax_response ){
                        
                        if( 'success_message' in ajax_response.data ){
                            
                            if( 'table_html' in ajax_response.data ){
                                $els.container.attr('data-mode', 'view');
                                $els.table.replaceWith( ajax_response.data.table_html );
                            }
                            
                            show_table_notice( ajax_response.data.success_message, 'success' );
                            
                            return;
                            
                        }
                        else if( 'error' in ajax_response.data ){
                            
                            show_table_notice( ajax_response.data.error, 'error' );
                            
                            return;
                            
                        }
                        
                    }
                    
                    show_table_notice( 'Transaction cannot be deleted. Please try again.', 'error' );
                    
                }).fail(function() {
                    
                    $els.row.removeClass('aa-saving-changes');
                    
                    show_table_notice( 'Transaction cannot be deleted. Please try again.', 'error' );
                    
                });
                
            });
            
            // Watch for changes in order items section, and then update the transactions section
            $('#woocommerce-order-items table.wc-order-totals').first().each(function(index, el) {
                
                $(el).data('aa_observe_init', 1);
                
                const order_totals_observer = new MutationObserver( (mutations) => {
                    
                    mutations.forEach((mutation) => {
                        
                        const $totals_table = $('#woocommerce-order-items table.wc-order-totals').first();
                        
                        if( $totals_table.length && ! $totals_table.data('aa_observe_init') ){
                            
                            let $els = get_transactions_elements( $('.aa-order-transactions-table-container tr.aa-edit-row td.actions').first(), false );
                            
                            $els.container.data('initial-data-mode', $els.container.attr('data-mode') );
                            $els.container.attr('data-mode', 'disabled');
                            
                            $.post( ajaxurl, {
                                action : 'get_order_transactions_table',
                                order_id : transactions_options.order_id,
                                transaction_nonce : transactions_options.transaction_nonce
                            }, function( ajax_response, textStatus, xhr){
                                
                                if( ajax_response && 'data' in ajax_response && 'table_html' in ajax_response.data ){
                                        
                                    $els.container.attr('data-mode', 'view');
                                    $els.table.replaceWith( ajax_response.data.table_html );
                                    
                                }
                                
                            }).fail(function() {
                                
                                $els.container.attr('data-mode', $els.container.data('initial-data-mode') || 'view' );
                                
                            });
                        
                            $totals_table.data('aa_observe_init', 1);
                          
                        }
                        
                    });
                });
                
                order_totals_observer.observe( $('#woocommerce-order-items .inside')[0], { childList: true, subtree: false } );
                
            });
        
        });
        
    </script>
    <div class="aa-order-transactions-table-container" data-mode="view">
        <div id="aa-order-transactions-table-notices"></div>
        <?= $transactions->get_admin_table_html(); ?>
    </div>
    <?php
    
}

// add order transaction via ajax
add_action( 'wp_ajax_add_order_transaction', function(){
    
    // check the nonce
    check_ajax_referer('order-transaction-nonce-value', 'transaction_nonce');
    
    // check the order id and transaction id
    if( empty( $_POST['order_id'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not add the transaction. Please try again.'
        ]);
        wp_die();
    
    }
    
    // check the transaction amount
    if( empty( $_POST['t_amount'] ) || ! is_numeric( $_POST['t_amount'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not add the transaction. Amount is not in a valid format!'
        ]);
        wp_die();
    
    }
    
    $transactions = new AA_Order_Transactions( $_POST['order_id'] );
    
    $payment_methods = AA_Order_Transactions::get_supported_payment_methods(true);
    
    $selected_payment_method = $_POST['t_payment_method'] ?? '';
    
    $added = $transactions->add([
        'custom_time' => ! empty( $_POST['t_date'] ) ? mysql2date( 'U', "{$_POST['t_date']}:00" ) : null,
        'description' => ! empty( $_POST['t_description'] ) ? strip_tags( $_POST['t_description'] ) : null,
        'payment_method' => strip_tags( $selected_payment_method ),
        'payment_method_title' => ! empty( $payment_methods[ $selected_payment_method ] ) ? $payment_methods[ $selected_payment_method ] : strip_tags( $selected_payment_method ),
        'transaction_id' => ! empty( $_POST['t_transaction_id'] ) ? strip_tags( $_POST['t_transaction_id'] ) : null,
        'amount' => ! empty( $_POST['t_amount'] ) && is_numeric( $_POST['t_amount'] ) ? $_POST['t_amount'] : 0,
    ]);
    
    if( $added ){
        
        if( ! empty( $transactions->just_added_transaction['id'] ) )
            $tr_result = $transactions->send_transaction_email( $transactions->just_added_transaction['id'] );
        
        wp_send_json_success([
            'success_message' => 'Transaction has been successfully created!',
            'table_html' => $transactions->get_admin_table_html()
        ]);
        wp_die();
        
    }
    else{
        
        wp_send_json_error([
            'error' => 'Could not remove the transaction because it cannot be found in database'
        ]);
        wp_die();
        
    }

});

// edit order transaction via ajax
add_action( 'wp_ajax_edit_order_transaction', function(){
    
    // check the nonce
    check_ajax_referer('order-transaction-nonce-value', 'transaction_nonce');
    
    // check the order id and transaction id
    if( empty( $_POST['order_id'] ) || empty( $_POST['t_id'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not edit the transaction. Please try again.'
        ]);
        wp_die();
    
    }
    
    // check the transaction amount
    if( empty( $_POST['t_amount'] ) || ! is_numeric( $_POST['t_amount'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not edit the transaction. Amount is not in a valid format!'
        ]);
        wp_die();
    
    }
    
    $transactions = new AA_Order_Transactions( $_POST['order_id'] );
    
    $payment_methods = AA_Order_Transactions::get_supported_payment_methods(true);
    
    $selected_payment_method = $_POST['t_payment_method'] ?? '';
    
    $updated = $transactions->update( $_POST['t_id'], [
        'time' => ! empty( $_POST['t_date'] ) ? mysql2date( 'U', "{$_POST['t_date']}:00" ) : null,
        'description' => ! empty( $_POST['t_description'] ) ? strip_tags( $_POST['t_description'] ) : null,
        'payment_method' => strip_tags( $selected_payment_method ),
        'payment_method_title' => ! empty( $payment_methods[ $selected_payment_method ] ) ? $payment_methods[ $selected_payment_method ] : strip_tags( $selected_payment_method ),
        'transaction_id' => ! empty( $_POST['t_transaction_id'] ) ? strip_tags( $_POST['t_transaction_id'] ) : null,
        'amount' => ! empty( $_POST['t_amount'] ) && is_numeric( $_POST['t_amount'] ) ? $_POST['t_amount'] : 0,
    ]);
    
    if( $updated ){
        
        wp_send_json_success([
            'success_message' => 'Transaction has been successfully updated!',
            'table_html' => $transactions->get_admin_table_html()
        ]);
        wp_die();
        
    }
    else{
        
        wp_send_json_error([
            'error' => 'Could not remove the transaction because it cannot be found in database'
        ]);
        wp_die();
        
    }

});

// delete order transaction via ajax
add_action( 'wp_ajax_delete_order_transaction', function(){
    
    // check the nonce
    check_ajax_referer('order-transaction-nonce-value', 'transaction_nonce');
    
    // check the order id and transaction id
    if( empty( $_POST['order_id'] ) || empty( $_POST['t_id'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not remove the transaction. Please try again.'
        ]);
        wp_die();
    
    }
    
    $transactions = new AA_Order_Transactions( $_POST['order_id'] );
    
    $removed_count = $transactions->remove( $_POST['t_id'] );
    
    if( $removed_count ){
        
        wp_send_json_success([
            'success_message' => ( $removed_count > 1 ? 'Transactions' : 'Transaction' ) . ' has been successfully removed!',
            'removed_count' => $removed_count,
            'table_html' => $transactions->get_admin_table_html()
        ]);
        wp_die();
        
    }
    else{
        
        wp_send_json_error([
            'error' => 'Could not remove the transaction because it cannot be found in database'
        ]);
        wp_die();
        
    }

});

// get transactions table via ajax
add_action( 'wp_ajax_get_order_transactions_table', function(){
    
    // check the nonce
    check_ajax_referer('order-transaction-nonce-value', 'transaction_nonce');
    
    // check the order id and transaction id
    if( empty( $_POST['order_id'] ) ){
    
        wp_send_json_error([
            'error' => 'Could not load the transactions table. Please try again.'
        ]);
        wp_die();
    
    }
    
    $transactions = new AA_Order_Transactions( $_POST['order_id'] );
    
    wp_send_json_success([
        'table_html' => $transactions->get_admin_table_html()
    ]);
    wp_die();

});

// show deposit and unpaid info on order edit page, in totals section
add_action('woocommerce_admin_order_totals_after_total', function( $order_id ){
    
    $transactions = new AA_Order_Transactions( $order_id );
    
    $amount_to_pay = $transactions->get_total_to_pay();
    $amount_paid = $transactions->get_transactions_total();
    $amount_overpaid = $transactions->get_total_overpaid();
    
    // there is no need to show paid and unpaid rows if order is fully paid
    if( ! $amount_to_pay && ! $amount_overpaid )
        return;
    
    if( $amount_paid > 0 ){
        
        ?>
        <tr>
            <td class="label">Paid:</td>
            <td width="1%"></td>
            <td class="total"><?= $transactions->wc_price( $amount_paid ); ?></td>
        </tr>
        <?php
        
    }
    
    ?>
    <tr>
        <td class="label">Unpaid:</td>
        <td width="1%"></td>
        <td class="total"><?= $transactions->wc_price( $amount_to_pay ); ?></td>
    </tr>
    <?php
    
    if( $amount_overpaid ){
        
        ?>
        <tr>
            <td class="label">Overpaid:</td>
            <td width="1%"></td>
            <td class="total"><?= $transactions->wc_price( $amount_overpaid ); ?></td>
        </tr>
        <?php
        
    }

}, 10);