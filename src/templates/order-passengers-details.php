<?php

/*
Template for displaying order's passengers details
Before including this template, these PHP variables should already be defined: $order
*/

if( ! isset( $trip_order ) )
    $trip_order = new AA_Trip_Order( $order );

$additional_passengers = $trip_order->get_additional_passengers();

if( empty( $additional_passengers ) )
    return;

echo '<form method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="order_id" value="' . esc_attr($order->get_id()) . '">';
echo '<div class="passengers-details-container aa-data-fields"><h3>Additional Passenger Details</h3>';

foreach( $additional_passengers as $p_key => $passenger_data ) {
	echo "<div class='passenger-wrap'>";
    $passenger_id = 'passenger_' . $p_key;

    echo sprintf( '<p class="sub-title">Passenger %d:</p>', $p_key + 1 );
    echo sprintf( '<p class="aa-data-field">First Name: <input type="text" name="%s[first_name]" value="%s" /></p>', esc_attr( $passenger_id ), esc_attr( $passenger_data['first_name'] ?? '' ) );
    echo sprintf( '<p class="aa-data-field">Last Name: <input type="text" name="%s[last_name]" value="%s" /></p>', esc_attr( $passenger_id ), esc_attr( $passenger_data['last_name'] ?? '' ) );
    echo sprintf( '<p class="aa-data-field">Gender: <input type="text" name="%s[gender]" value="%s" /></p>', esc_attr( $passenger_id ), esc_attr( $passenger_data['gender'] ?? '' ) );
    echo sprintf( '<p class="aa-data-field">Date of Birth: <input type="date" name="%s[dob]" value="%s" /></p>', esc_attr( $passenger_id ), esc_attr( $passenger_data['dob'] ?? '' ) );
    echo sprintf( '<p class="aa-data-field">Email: <input type="email" name="%s[email]" value="%s" /></p>', esc_attr( $passenger_id ), esc_attr( $passenger_data['email'] ?? '' ) );

    // Passport Photo Upload
    $passport_photo_url = $passenger_data['passport_photo'] ?? '';
    if ( $passport_photo_url ) {
        echo sprintf( '<p class="aa-data-field print-hide">Passport Photo: <a href="%s" target="_blank">View Photo</a> <button type="button" class="remove-passport-photo" data-passenger-id="%s">Remove</button></p>', esc_url( $passport_photo_url ), $passenger_id );
    } else {
        echo sprintf( '<p class="aa-data-field print-hide">Copy of Passport: <input type="file" name="%s_passport_photo" accept="image/*"></p>', esc_attr( $passenger_id ) );
    }

    // Insurance Documents Upload
    $insurance_documents = $passenger_data['insurance_documents'] ?? [];
    if ( ! empty( $insurance_documents ) ) {
        foreach ( $insurance_documents as $doc_key => $doc_url ) {
            echo sprintf( '<p class="aa-data-field print-hide">Insurance Document %d: <a href="%s" target="_blank">View Document</a> <button type="button" class="remove-insurance-doc" data-passenger-id="%s" data-doc-key="%d">Remove</button></p>', $doc_key + 1, esc_url( $doc_url ), $passenger_id, $doc_key );
        }
    }
    echo sprintf( '<p class="aa-data-field print-hide">Add Insurance Document(s): <input type="file" name="%s_insurance_documents[]" multiple accept="application/pdf, image/*"></p>', esc_attr( $passenger_id ) );

    // Flight Details WYSIWYG Editor
    $flight_details = $passenger_data['flight_details'] ?? '';
    echo sprintf( '<p class="aa-data-field print-hide">Flight Details:</p>' );
    wp_editor( $flight_details, $passenger_id . '_flight_details', array(
        'textarea_name' => $passenger_id . '_flight_details', // Use the dynamic textarea name based on the passenger ID
        'editor_height' => 200, // Set height to 200 pixels
        'media_buttons' => false, // Disable media buttons
        'teeny'         => true,  // Use a simplified version of the editor
        'quicktags'     => false, // Disable the Text (HTML) tab
    ));
	echo '<button type="button" class="remove-flight-details" data-passenger-id="<?php echo esc_attr( $passenger_id ); ?>">Clear Flight Details</button>';
	echo "</div>";
}
echo '<div class="button-wrap print-hide"><button type="submit" class="save-passenger-details" name="save_passenger_details">Save Passenger Details</button></div>';
echo '</div>';
echo '</form>';

?>