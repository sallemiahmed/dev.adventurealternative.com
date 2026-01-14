<?php

/*
Template for displaying order details
Before including this template, these PHP variables should already be defined: $order, $trip_order
*/

?>
<section id="view-order">
	<div class="container">
		<button class="aa-button-style print-button" onclick="printPage()">Print/Download Order Details</button>
		<h2>Order #<?= $order->get_id(); ?> details</h2>
		<div class="order-details-container">
			<?php include get_stylesheet_directory() . '/src/templates/order-table.php'; ?>
		    <?php include get_stylesheet_directory() . '/src/templates/order-transactions.php'; ?>
		    <?php include get_stylesheet_directory() . '/src/templates/order-billing-details.php'; ?>
		    <?php include get_stylesheet_directory() . '/src/templates/order-passengers-details.php'; ?>
		</div>
	</div>
</section>
<section id="submit-review">
	<div class="container">
		<div class="feedback-form-container">
			<h2>Leave a Review</h2>
			<form id="feedback-form" enctype="multipart/form-data">
				<div class="review-img-field-wrapper">
					<label for="reviewer_image">Your Image (Optional):</label>
					<input type="file" name="reviewer_image" id="reviewer_image">
				</div>
				<div class="review-hidden-field-wrapper">
					<label for="reviewer_name">Your Name:</label>
					<input type="text" name="reviewer_name" id="reviewer_name" value="<?= $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>" readonly>
					<label for="reviewer_destination">Destination:</label>
					<input type="text" name="reviewer_destination" id="reviewer_destination" value="<?php 
						$items = $order->get_items();
						$destination = '';

						foreach ( $items as $item_id => $item ) {
							$product_id = $item->get_product_id();
							$terms = get_the_terms($product_id, 'product_cat');

							if ($terms && !is_wp_error($terms)) {
								foreach ($terms as $term) {
									if ($term->slug === 'trip-date') {
										$itinerary_post = get_field('itinerary', $product_id);
										$destination = get_the_title($itinerary_post);
										break 2; // Exit both loops once the destination is found
									}
								}
							}
						}

						echo esc_html($destination);
					?>" readonly>
				</div>
				<div class="review-stars-field-wrapper">
					<label for="stars">Rating (Required):</label>
					<div id="star-rating">
						<span class="star" data-value="1">★</span>
						<span class="star" data-value="2">★</span>
						<span class="star" data-value="3">★</span>
						<span class="star" data-value="4">★</span>
						<span class="star" data-value="5">★</span>
					</div>
					<input type="range" name="stars" id="stars" min="1" max="5" step="1" required>
				</div>
				<div class="review-content-field-wrapper">	
					<label for="review_content">Your Review:</label>
					<textarea name="review_content" id="review_content" rows="5"></textarea>	
				</div>
				<button type="submit">Submit Review</button>
			</form>
		</div>
	</div>
</section>
<?php
// Ensure you generate the nonce within the PHP file
$ajax_nonce = wp_create_nonce('custom_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>
<script>
jQuery(document).ready(function($){
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var nonce = '<?php echo wp_create_nonce('custom_nonce'); ?>';
	
	// Handle form submission for main traveller details
    $('#main-traveller-details-form').on('submit', function(e) {
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

    // Handle review form submission
    $('#feedback-form').on('submit', function(e) {
        e.preventDefault();

        var reviewContent = tinymce.get('review_content').getContent();

        // Check if the content is empty
        if (!reviewContent || reviewContent.trim() === '') {
            alert('Please fill out your review.');
            tinymce.get('review_content').focus();
            return false;
        }

        var formData = new FormData(this);
        formData.append('action', 'save_feedback_form');
        formData.append('security', nonce);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Review submitted successfully.');
                    $('#feedback-form')[0].reset();
                    tinymce.get('review_content').setContent(''); // Clear TinyMCE content
                } else {
                    alert('Error submitting review: ' + response.data);
                }
            }
        });
    });

	// Star rating system handling
    $('#stars').on('input change', function() {
        var rating = $(this).val();
        $('#star-rating .star').each(function() {
            var starValue = $(this).data('value');
            if (starValue <= rating) {
                $(this).addClass('selected');
            } else {
                $(this).removeClass('selected');
            }
        });
    });

    $('#star-rating .star').on('click', function() {
        var rating = $(this).data('value');
        $('#stars').val(rating).trigger('change');
    });

    // Trigger change event to reflect the initial state of the range
    $('#stars').trigger('change');
	
});
function printPage() {
	window.print();
}
</script>
