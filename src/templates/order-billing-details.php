<?php

/*
Template for displaying order's billing details
Before including this template, these PHP variables should already be defined: $order
*/

$customer_id = $order->get_user_id();
$dob = get_user_meta($customer_id, 'dob', true);
$gender = get_user_meta($customer_id, 'gender', true);
$passport_number = get_user_meta($customer_id, 'passport_number', true);
$passport_photo = get_post_meta($order->get_id(), 'main_traveller_passport_photo', true);
$insurance_documents = get_post_meta($order->get_id(), 'main_traveller_insurance_documents', true);
$flight_details = get_post_meta($order->get_id(), 'main_traveller_flight_details', true);

?>
<form id="main-traveller-details-form" method="post" enctype="multipart/form-data">
    <div class="billing-details-container aa-data-fields">
        <h3>Main Traveller Details</h3>
        <p class="aa-data-field">First Name: <span><?php echo esc_html( $order->get_billing_first_name() ); ?></span></p>
        <p class="aa-data-field">Last Name: <span><?php echo esc_html( $order->get_billing_last_name() ); ?></span></p>
        <p class="aa-data-field">Email: <span><?php echo esc_html( $order->get_billing_email() ); ?></span></p>
        <p class="aa-data-field">Phone: <span><?php echo esc_html( $order->get_billing_phone() ); ?></span></p>
        <p class="aa-data-field">Company: <span><?php echo esc_html( $order->get_billing_company() ); ?></span></p>
        <p class="aa-data-field">Address 1: <span><?php echo esc_html( $order->get_billing_address_1() ); ?></span></p>
        <p class="aa-data-field">Address 2: <span><?php echo esc_html( $order->get_billing_address_2() ); ?></span></p>
        <p class="aa-data-field">City: <span><?php echo esc_html( $order->get_billing_city() ); ?></span></p>
        <p class="aa-data-field">State/County: <span><?php echo esc_html( $order->get_billing_state() ); ?></span></p>
        <p class="aa-data-field">Postcode/ZIP: <span><?php echo esc_html( $order->get_billing_postcode() ); ?></span></p>
        <p class="aa-data-field">Country: <span><?php echo esc_html( WC()->countries->countries[ $order->get_billing_country() ] ); ?></span></p>
        <p class="aa-data-field">Date of Birth: <span><?php echo esc_html( $dob ); ?></span></p>
        <p class="aa-data-field">Gender: <span><?php echo esc_html( ucfirst($gender) ); ?></span></p>

        <!-- Passport Photo Upload -->
        <?php if ($passport_photo): ?>
            <p class="aa-data-field print-hide">Passport Photo: <a href="<?php echo esc_url($passport_photo); ?>" target="_blank">View Photo</a> <button type="button" class="remove-passport-photo" data-passenger-id="main_traveller">Remove</button></p>
        <?php else: ?>
            <p class="aa-data-field print-hide">Passport Photo: <input type="file" name="main_traveller_passport_photo" accept="image/*"></p>
        <?php endif; ?>

        <!-- Insurance Documents Upload -->
        <?php if ($insurance_documents && is_array($insurance_documents)): ?>
            <?php foreach ($insurance_documents as $key => $doc_url): ?>
                <p class="aa-data-field print-hide">Document <?php echo $key + 1; ?>: <a href="<?php echo esc_url($doc_url); ?>" target="_blank">View Document</a> <button type="button" class="remove-insurance-doc" data-passenger-id="main_traveller" data-doc-key="<?php echo $key; ?>">Remove</button></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <p class="aa-data-field print-hide">Add Insurance Document(s): <input type="file" name="main_traveller_insurance_documents[]" multiple accept="application/pdf, image/*"></p>

        <!-- Flight Details -->
        <p class="aa-data-field print-hide">Flight Details:</p>
        <?php
		wp_editor($flight_details, 'main_traveller_flight_details', array(
			'textarea_name' => 'main_traveller_flight_details',
			'editor_height' => 200,
			'media_buttons' => false,
			'teeny'         => true,
			'quicktags'     => false
		)); ?>
		 <button type="button" class="remove-flight-details" data-passenger-id="main_traveller">Clear Flight Details</button>

        <!-- Save Details Button -->
        <button type="submit" id="save-main-traveller-details-btn" class="print-hide">Save Details</button>
    </div>
</form>
