<?php
/**
 * The Template for displaying all Trip posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$country = get_field('country');
$current_country_id = $country;
$current_post_id = get_the_ID();
$color = get_field('color', $country);
if ($color) : ?>
    <style>
        :root {
            --default-color: <?= $color; ?>;
        }
    </style>
<?php endif; 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$hero_content = $hero_g['content'];
$hero_image = $hero_g['image'];
$itin = get_field('itinerary_g');
$itin_title = $itin['title'];
$itin_intro = $itin['itinerary_intro'];
$itin_summary = $itin['summary'];
$itin_map = $itin['map_image'];
$itin_map_cap = $itin['map_caption'];
$content_r = get_field('content_r');
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= wp_get_attachment_image($hero_image, 'full'); ?>
	</div>
	<div class="container">
		<h1 class="title">
			<?= $hero_title; ?>
		</h1>
		<div class="hero-text">
			<?= $hero_content; ?>
		</div>
		<div class="button-row">
			<a class="cta-btn" href="#book">
				View Dates &amp; Book Now
			</a>
		</div>
	</div>
</section>
<?php
$days = get_field('days');
$altitude = get_field('altitude');
$distance = get_field('distance');
$challenge = get_field('challenge');
$price = get_field('price_from');
?>
<section id="subnav">
	<div class="container">
		<div class="nav-links">
			<a href="#intro" class="nav-link about smooth-scroll">
				<div class="link-text">
					About
				</div>
			</a>
			<?php if($itin_title): ?>
			<a href="#itinerary" class="nav-link itinerary smooth-scroll">
				<div class="link-text">
					Itinerary
				</div>
			</a>
			<?php endif; ?>
			<a href="#book" class="nav-link dates smooth-scroll">
				<div class="link-text">
					Dates
				</div>
			</a>
			<?php if($content_r): ?>
			<a href="#more" class="nav-link more smooth-scroll">
				<div class="link-text">
					More
				</div>
			</a>
			<?php endif; ?>
			<a href="#keyInfo" class="nav-link extras smooth-scroll">
				<div class="link-text">
					Guides
				</div>
			</a>
			<a href="#tripGallery" class="nav-link gallery smooth-scroll">
				<div class="link-text">
					Galleries
				</div>
			</a>
		</div>
		<div class="nav-data">
			<?php if($days): ?>
			<div class="data-item days">
				<span class="label">Days</span>
				<p>
					<?= $days; ?>
				</p>
			</div>
			<?php endif;
			if($altitude): ?>
			<div class="data-item altitude">
				<span class="label">Altitude</span>
				<p>
					<?= $altitude; ?>m
				</p>
			</div>
			<?php endif;
			if($distance): ?>
			<div class="data-item distance">
				<span class="label">Distance</span>
				<p>
					<?= $distance; ?>km
				</p>
			</div>
			<?php endif;
			if($challenge): ?>
			<div class="data-item challenge">
				<span class="label">Challenge</span>
				<p>
					<?= $challenge; ?>
				</p>
			</div>
			<?php endif ?>
		</div>
		<a class="nav-price smooth-scroll" href="#book">
			<span class="label">Base Trip</span>
			<p>
				<?= $price; ?>
			</p>
		</a>
	</div>
</section>
<?php
/* Intro */
$intro_g = get_field('intro_g');
$intro_title = $intro_g['title'];
$intro_left = $intro_g['content_left_column'];
$intro_testimonial = $intro_g['featured_review'];
$related_destination = get_field('country');
?>
<section id="intro" class="trip-intro">
	<div class="container">
		<div class="breadcrumbs">
			<a href="/">Home</a>
			<p class="sep">
				&gt;
			</p>
			<a href="<?= get_the_permalink($country); ?>"><?= get_the_title($country); ?></a>
			<p class="sep">
				&gt;
			</p>
			<p class="current">
				<?php the_title(); ?>
			</p>
		</div>
		<div class="flex-row">
			<div class="content">
				<h2 class="subheading">
				   <?= $intro_title; ?>
				</h2>
				<?= $intro_left; ?>
			</div>
		</div>
		<div class="intro-footer">
			<a class="trips-button smooth-scroll" href="#book">Dates &amp; Book Now</a>
			<div class="tripadvisor rating">
                <?= wp_get_attachment_image(5482, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(5483, "full"); ?>
            </div>
		</div>
		<?php $post_id = $intro_testimonial;
		if( $post_id ): ?>
		<div class="page-divider">
		</div>
		<div class="intro-testimonial">
			<h2 class="subheading">
			   What Our Adventurers Think:
			</h2>
			<div class="testimonial-wrapper">
			<div class="quote-left">
				<?= wp_get_attachment_image(298, 'full'); ?>
			</div>
			<?php
				$review_content = get_field('review_content', $post_id);
				$reviewer_name = get_field('reviewer_name', $post_id);
				$reviewer_destination = get_field('reviewer_destination', $post_id);
				$stars = get_field('stars', $post_id);
				$reviewer_image = get_field('reviewer_image', $post_id);
				$full_stars = floor($stars);
				$half_stars = ceil($stars - $full_stars);
				$empty_stars = 5 - $full_stars - $half_stars;
				?>
				<div class="testimonial-section">
					<div class="testimonial-content">
						<?php echo $review_content; ?>
					</div>
					<div class="testimonial-meta">
						<?php if( $reviewer_image ){
							echo "<div class='review-image'>" . wp_get_attachment_image($reviewer_image, 'medium') . "</div>";
						} ?>
						<div class="reviewer-info">
							<span class="reviewer-name"><?php echo $reviewer_name; ?></span>
							<?php if($reviewer_destination):?><span class="reviewer-destination"><?php echo $reviewer_destination; ?></span><?php endif; ?>	
							<?php if($stars != 0): ?>
							<span class="reviewer-stars">
								<?php
								for ($i=0; $i < $full_stars; $i++) { 
									echo '<img src="/wp-content/uploads/2024/02/Star.svg" alt="star">';
								}
								if( $half_stars ){
									echo '<img src="/wp-content/uploads/2024/02/HalfStar.svg" alt="star">';
								}
								for ($i=0; $i < $empty_stars; $i++) { 
									echo '<img src="/wp-content/uploads/2024/02/EmptyStar.svg" alt="star">';
								}
								?>
							</span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			
			<div class="quote-right">
				<?= wp_get_attachment_image(296, 'full'); ?>
			</div>
		</div>
		</div>
		<?php endif; ?>	
	</div>
</section>
<?php
$video_section = get_field('video_section');
$display = $video_section['display'];
$video_title = $video_section['title'];
$video_content = $video_section['content'];
$video = $video_section['video'];
if($display):?>
<section id="video">
	<div class="background-graphic">
		<?= wp_get_attachment_image(54, 'full'); ?>
	</div>
	<div class="container">
		<div class="flex-row">
			<div class="video-left">
				<h2 class="subheading">
				   <?= $video_title; ?>
				</h2>
				<div class="content">
					<?= $video_content; ?>
				</div>
				<a class="trips-button smooth-scroll" href="#book">Dates &amp; Book Now</a>
			</div>
			<div class="video-right">
				<?= $video; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>
<?php
if($itin_title):?>
<section id="itinerary">
	<div class="container">
		<h2 class="subheading">
		   <?= $itin_title; ?>
		</h2>
		<div class="content">
			<?= $itin_intro; ?>
		</div>
		<div class="flex-row">
			<?php if (!empty($itin_summary)): ?>
			<div class="itinerary-block">
				<div class="itinerary-headers">
					<div class="day-wide">
						Day
					</div>
					<div class="summary-wide">
						Summary
					</div>
				</div>
				<?php
					foreach ($itin_summary as $row): 
						$day = $row['day'];
						$summary_title = $row['title'];
						$elevation = $row['elevation'];
						$travel_time = $row['travel_time'];
						$content = $row['content'];
						?>
						<div class="itinerary-row">
							<div class="itinerary-title-row">
								<div class="day-wide">
									<?= $day; ?>
								</div>
								<div class="summary-wide">
									<?= $summary_title; ?>
								</div>
								<div class="summary-drop">
									<?= wp_get_attachment_image(652, 'full'); ?>
								</div>
							</div>
							<div class="itinerary-content">
								<div class="day-wide">
									<?php if($elevation): ?>
									<div class="label">
										Elevation:
									</div>
									<div class="value">
										<?= $elevation; ?>
									</div>
									<?php endif; ?>
									<?php if($travel_time): ?>
									<div class="label">
										Travel Time:
									</div>
									<div class="value">
										<?= $travel_time; ?>
									</div>
									<?php endif; ?>
								</div>
								<div class="summary-wide">
									<?= $content; ?>
								</div>
							</div>
						</div>
					<?php 
					endforeach;
				endif;
				?>
			</div>
			<div class="map-block">
				<div class="map">
					<?php
					$full_image_url = wp_get_attachment_url($itin_map);
					if ($full_image_url) {
						echo '<a href="' . esc_url($full_image_url) . '" target="_blank">';
						echo wp_get_attachment_image($itin_map, 'medium_large');
						echo '</a>';
					}
					?>
				</div>
				<div class="map-caption">
					<?= $itin_map_cap; ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php
endif; 
?>
<?php
$book_g = get_field('book_g');
$book_title = $book_g['title'];
$book_content = $book_g['content'];
?>
<section id="book">
	<div class="container">
		<h2 class="subheading center">
		   <?= $book_title; ?>
		</h2>
		<div class="content center">
			<?= $book_content; ?>
		</div>
		<div class="page-divider">
		</div>
		<h2 class="subheading">
		   Fixed Itineraries
		</h2>
		<!-- >>> ADDED BY DEJAN -->
		<?php wc_print_notices(); ?>
		<!-- <<< ADDED BY DEJAN -->
		<div class="booking-table">
			<?php
			$current_trip_id = get_the_ID();
			$today = date('Ymd'); // Get today's date in 'Ymd' format

			$args = array(
				'post_type' => 'product',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => 'itinerary',
						'value' => $current_trip_id,
						'compare' => '=',
					),
					array(
						'key' => 'start_date',
						'value' => $today,
						'compare' => '>',
						'type' => 'DATE', // Treat the value as a date
					),
				),
				'orderby' => array(
					'meta_value' => 'ASC', // Order by the start_date meta key
				),
				'meta_key' => 'start_date', // Ensure this matches the key in the database
			);

			$linked_products_query = new WP_Query($args);

			if ($linked_products_query->have_posts()) :
				// Collect data into an array
				$products = array();
				while ($linked_products_query->have_posts()) :
					$linked_products_query->the_post();
					$product = wc_get_product(get_the_ID());
					$start_date = get_field('start_date');
					$end_date = get_field('end_date');

					// Add product data to the array
					$products[] = array(
						'start_date' => $start_date,
						'end_date' => $end_date,
						'days' => (strtotime(DateTime::createFromFormat('d/m/Y', $end_date)->format('Y-m-d')) - strtotime(DateTime::createFromFormat('d/m/Y', $start_date)->format('Y-m-d'))) / (60 * 60 * 24) + 1,
						'price' => $product->get_price_html(),
						'book_url' => $product->add_to_cart_url(),
					);
				endwhile;

				// Sort the array by start_date
				usort($products, function ($a, $b) {
					return strtotime(DateTime::createFromFormat('d/m/Y', $a['start_date'])->format('Y-m-d')) - strtotime(DateTime::createFromFormat('d/m/Y', $b['start_date'])->format('Y-m-d'));
				});
				?>

				<table class="booking-options-table">
					<thead>
						<tr>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Price (per person)</th>
							<th></th> <!-- Blank header for the Book Now button -->
						</tr>
					</thead>
					<tbody>
						<?php foreach ($products as $product) : ?>
							<tr>
								<td><?= esc_html($product['start_date']); ?></td>
								<td><?= esc_html($product['end_date']); ?></td>
								<td><?= esc_html($product['days']); ?></td>
								<td><?= $product['price']; ?></td>
								<td><a class="trips-button" href="<?= esc_url($product['book_url']); ?>">Book Now</a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php else : ?>
				<p>No upcoming trips found.</p>
			<?php endif;

			// Restore original Post Data
			wp_reset_postdata();
			?>

		</div>
		<div class="page-divider">
		</div>
		<h2 class="subheading">
		   Private Itineraries
		</h2>
		<div class="choose">
			<?php echo do_shortcode('[contact-form-7 id="a744ecc" title="Choose Your Own Dates"]')?>
		</div>
		<div class="page-divider">
		</div>
		<?php 
		$featuredLeader = get_field('featured_leader');
		$featuredLeaderInfo = get_field('leader_info_g', $featuredLeader);
		$featuredLeaderName = get_the_title($featuredLeader);
		$featuredLeaderRole = $featuredLeaderInfo['role'];
		$featuredLeaderBackground = $featuredLeaderInfo['background'];
		if($featuredLeader):?>
			<div class="title-row">
				<h2 class="subheading">
				   Our <?= get_the_title();?> Experts
				</h2>
				<div class="tripadvisor rating">
                    <?= wp_get_attachment_image(5482, "full"); ?>
                </div>
                <div class="googlereviews rating">
                    <?= wp_get_attachment_image(5483, "full"); ?>
                </div>
			</div>
			<div class="leader-row">
				<div class="leader-meta">
					<div class="leader-image">
						<div class="faux-bg-img">
							<?= get_the_post_thumbnail( $featuredLeader, 'full' );?>
						</div>
					</div>
					<div class="leader-details">
						<div class="leader-name">
							<?= $featuredLeaderName; ?>
						</div>
						<div class="leader-role">
							<?= $featuredLeaderRole; ?>
						</div>
						<div class="leader-phone">
							Call on <a href="tel:02870831258">028 7083 1258</a>
						</div>
						<div class="leader-email">
							<a href="mailto:office@adventurealternative.com">office@adventurealternative.com</a>
						</div>
					</div>
				</div>
				<div class="leader-content">
					<div class="content">
						<?php
						if (strlen($featuredLeaderBackground) > 500) {
						  $featuredLeaderBackground = substr($featuredLeaderBackground, 0, 500);
						  $featuredLeaderBackground = substr($featuredLeaderBackground, 0, strrpos($featuredLeaderBackground, ' '));
						  $featuredLeaderBackground .= '...';
						}
						echo $featuredLeaderBackground; ?>
					</div>
					<a class="cta-btn" href="<?= get_the_permalink($featuredLeader); ?>">Read More</a>
				</div>
			<? endif; ?>
		</div>
	</div>
</section>
<?php
if( $content_r ): ?>
	<span id="more"></span>
    <?php foreach( $content_r as $row ): ?>
    <section id="alternating-content">
        <div class="container">
			<?php if($row['title']): ?>
            <h2 class="subheading">
                <?php echo esc_html($row['title']); ?>
            </h2>
			<?php endif; ?>
            <?php
                $content_length = strlen($row['content']);
                $style = ($content_length < 400 || $content_length > 3500) ? 'style="columns:1"' : '';
                
                // Define allowed HTML tags
                $allowed_tags = wp_kses_allowed_html( 'post' );
                $allowed_tags['iframe'] = [
                    'src'             => [],
                    'width'           => [],
                    'height'          => [],
                    'frameborder'     => [],
                    'allowfullscreen' => [],
                ];
                $allowed_tags['video'] = [
                    'src' => [],
                    'controls' => [],
                    'autoplay' => [],
                    'loop' => [],
                    'muted' => [],
                    'playsinline' => [],
                    'poster' => [],
                ];
                $allowed_tags['embed'] = [
                    'src' => [],
                    'type' => [],
                    'width' => [],
                    'height' => [],
                    'allowfullscreen' => [],
                ];
            ?>
            <div class="content-wysiwyg" <?php echo $style; ?>>
                <?php echo wp_kses( $row['content'], $allowed_tags ); ?>
            </div>
        </div>
    </section>
    <?php endforeach;
	endif;
?>
<?php
/* FAQ Section */
$faq_r = get_field('faq_r');
if (!empty($faq_r)):?>
<section id="faq">
	<div class="container">
		<h2 class="heading_2">
			Frequently Asked Questions
		</h2>
		<div class="faq-accordion">
			<?php
				foreach ($faq_r as $index => $row) {
					$question = $row['question'];
					$answer = $row['answer'];
					?>
						<div class="faq-item">
							<div class="faq-question" id="question<?= $index; ?>">
								<?= $question; ?>
							</div>
							<div class="faq-answer" id="answer<?= $index; ?>" style="display: none;">
								<?= $answer; ?>
							</div>
						</div>
					<?php
				}
			?>
		</div>
	</div>
</section>
<script>
(function($) {
	document.addEventListener("DOMContentLoaded", function() {
		const faqQuestions = document.querySelectorAll('.faq-question');
		faqQuestions.forEach(function(question) {
			question.addEventListener('click', function() {
				const answerId = this.id.replace('question', 'answer');
				const answerDiv = document.getElementById(answerId);
				if(answerDiv.style.display === 'block') {
					answerDiv.style.display = 'none';
					return;
				}
				const allAnswers = document.querySelectorAll('.faq-answer');
				allAnswers.forEach(function(answer) {
					answer.style.display = 'none';
				});
				answerDiv.style.display = 'block';
			});
		});
	});
})(jQuery);
</script>
<?php
endif;
?>
<?php
$key_info = get_field('key_info_g');
$health_safety_links = $key_info['health_safety_links'];
$prep_kit_links = $key_info['prep_kit_links'];
$planning_links = $key_info['planning_links'];
$country_information_g = get_field('information_g', $country);
$general_info = $country_information_g['general_info'];
?>
<section id="keyInfo">
	<div class="container">
		<h2 class="subheading">
		   Key Information &amp; Guides
		</h2>
		<div class="flex-row">
			<div class="info-column">
				<h3>
					General <?= get_the_title($country); ?> Information
				</h3>
				<div class="information-list">
					<?php
					if (!empty($general_info)) {
						foreach ($general_info as $row) {
							echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
						}
					}
					?>
				</div>
			</div>
			<?php if($health_safety_links): ?>
			<div class="info-column">
				<h3>
					Health and Safety Guides
				</h3>
				<div class="list">
					<?= $health_safety_links; ?>
				</div>
			</div>
			<?php endif;
			if($prep_kit_links): ?>
			<div class="info-column">
				<h3>
					Preparation &amp; Kit Guides
				</h3>
				<div class="list">
					<?= $prep_kit_links; ?>
				</div>
			</div>
			<?php endif;
			if($planning_links): ?>
			<div class="info-column">
				<h3>
					Planning Your Trip
				</h3>
				<div class="list">
					<?= $planning_links; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<div class="page-divider">
		</div>
	</div>
</section>
<?php
$gallery_g = get_field('gallery_g');
$gallery_title = $gallery_g['title'];
$galleries = $gallery_g['galleries'];
?>
<section id="tripGallery">
    <div class="container">
        <h2 class="subheading"><?= $gallery_title; ?></h2>
        <div class="gallery-grid">
            <?php if($galleries): ?>
                <?php 
                $counter = 0; // Initialize counter
                foreach($galleries as $gallery_id): 
                    $images = get_field('images', $gallery_id);
                    // Every three items, close the previous div and open a new one
                    if ($counter % 3 == 0): 
                        if ($counter != 0): // Close previous div if not the first iteration
                            echo '</div>';
                        endif;
                        echo '<div class="gallery-row">'; // Open a new div for each row of 3 items
                    endif;
                    ?>
                    <div class="gallery-item" data-gallery-id="<?= $gallery_id; ?>" data-images="<?= esc_attr(json_encode(array_map(function($image_id) { return wp_get_attachment_url($image_id); }, $images))); ?>">
                        <div class="gallery-thumbnail">
                            <?= get_the_post_thumbnail($gallery_id, 'medium_large'); ?>
                        </div>
                        <div class="gallery-meta">
                            <h3 class="gallery-title"><?= get_the_title($gallery_id); ?></h3>
                            <p class="count">
                                <?= is_array($images) ? count($images) . ' Photos' : 'No Photos'; ?>
                            </p>
                        </div>
                    </div>
                    <?php
                    $counter++;
                    if ($counter == count($galleries)): // Close the last row div if it's the last item
                        echo '</div>';
                    endif;
                endforeach;
                ?>
            <?php endif; ?>
        </div>
		<a class="trips-button" target="_blank" href="https://www.instagram.com/adventurealternative/?hl=en">More on Instagram</a>
    </div>
</section>
<div id="galleryModal" style="display:none;">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="gallery-carousel owl-theme"></div>
    </div>
	<span class="close-button"></span>
</div>
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
$other_trips = get_field('other_trips_g');
$other_title = $other_trips['title'];
$other_content = $other_trips['content'];
?>
<section id="trips">
    <div class="container">
		<h2 class="subheading center">
		   <?= $other_title; ?>
		</h2>
		<div class="content">
			<?= $other_content; ?>
		</div>
        <div class="trips-grid">
           <?php 
            $args = array(
                'post_type' => 'trip',
                'posts_per_page' => 3,
				'post__not_in' => array($current_post_id),
                'meta_query' => array(
                    array(
                        'key' => 'country',
                        'value' => $current_country_id,
                        'compare' => '=',
                    ),
                ),
            );

            $trips = new WP_Query($args);
            if ($trips->have_posts()) : 
                while ($trips->have_posts()) : $trips->the_post(); 
					$blurb = get_field('blurb');
					$days = get_field('days');
					$altitude = get_field('altitude');
					$distance = get_field('distance');
					$challenge = get_field('challenge');
					$price_from = get_field('price_from');
                    ?>
                    <div class="trip-item" data-category="<?php echo get_the_terms(get_the_ID(), 'trip-category')[0]->slug; ?>">
						<div class="trip-item-image">
							<div class="faux-bg-img">
								<?php the_post_thumbnail('full'); ?>
							</div>
						</div>
						<div class="trip-meta">
							<div class="trip-title">
								<h3><?php the_title(); ?></h3>
							</div>
							<div class="trip-blurb">
								<?= $blurb ?>
							</div>
							<div class="trip-table">
								<table>
									<?php if (!empty($days)) : ?>
										<tr>
											<td><span class="trip-meta-icon"><?= wp_get_attachment_image(175, 'full')?></span> Days</td>
											<td><?= $days ?></td>
										</tr>
									<?php endif; ?>

									<?php if (!empty($altitude)) : ?>
										<tr>
											<td><span class="trip-meta-icon"><?= wp_get_attachment_image(173, 'full')?></span> Altitude</td>
											<td><?= $altitude ?> m</td>
										</tr>
									<?php endif; ?>

									<?php if (!empty($distance)) : ?>
										<tr>
											<td><span class="trip-meta-icon"><?= wp_get_attachment_image(176, 'full')?></span> Distance</td>
											<td><?= $distance ?> km</td>
										</tr>
									<?php endif; ?>

									<?php if (!empty($challenge)) : ?>
										<tr>
											<td><span class="trip-meta-icon"><?= wp_get_attachment_image(174, 'full')?></span> Challenge</td>
											<td><?= $challenge ?></td>
										</tr>
									<?php endif; ?>
								</table>
							</div>
							<div class="price-row">
								<p>
									<?= (preg_match('/\d/', $price_from) && !empty($price_from)) ? "Trip Price  $price_from" : "Enquire for Details" ?>
								</p>
							</div>
							<div class="button-row">
								<a class="trip-btn" href="<?= get_permalink(); ?>">View this Trip</a>
							</div>
						</div>
					</div>
                    <?php 
                endwhile;
            endif;
            wp_reset_postdata();
            ?>
        </div>
    </div>
</section>
<?php
$posts_g = get_field('posts_g');
$posts_title = $posts_g['title'];
$posts_content = $posts_g['content'];
$page_title_slug = sanitize_title(get_the_title($country));
$blogposts = new WP_Query(
	array(
		'showposts'      => 6,
		'post_status'    => 'publish',
		'category_name'  => $page_title_slug,
		'orderby'        => 'rand'
	)
);
if ($blogposts->have_posts()):
?>
<section id="blogPosts">
	<div class="container">
		<h2 class="subheading">
			Our <?= get_the_title($country); ?> Posts
		</h2>
		<div class="content">
			<?= $posts_content; ?>
		</div>
		<div class="post-row">
			<?php while ($blogposts->have_posts()) : $blogposts->the_post(); ?>
				<a class="grid-item post-item" href="<?php echo get_permalink(); ?>">
					<div class="post-item-photo">
						<div class="faux-bg-img">
							<?php the_post_thumbnail( 'medium-large' ); ?>
						</div>
					</div>
					<h3 class="grid-item-title">
						<?php echo get_the_title(); ?>
					</h3>
					<?php
						$excerpt = $post->post_content;
						$excerpt = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/', '', $excerpt);
						$excerpt = strip_tags($excerpt);
						if (strlen($excerpt) > 150) {
							$excerpt = substr($excerpt, 0, 150);
							$excerpt = substr($excerpt, 0, strrpos($excerpt, ' '));
							$excerpt .= '...';
						}
					?>
					<p class="grid-item-excerpt"><?php echo $excerpt ?></p>
					<div class="btn">Read More</div>
				</a>
			<?php endwhile;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var titles = document.querySelectorAll('.itinerary-title-row');

    titles.forEach(function(title) {
        title.addEventListener('click', function() {
            var parentRow = this.parentElement;

            if (parentRow.classList.contains('active')) {
                parentRow.classList.remove('active');
            } else {
                document.querySelectorAll('.itinerary-row').forEach(function(row) {
                    row.classList.remove('active');
                });
                parentRow.classList.add('active');
            }
        });
    });
});
$(document).ready(function(){
	(function ($) {
		var galleryItems = document.querySelectorAll('.gallery-item');
		var modal = document.getElementById('galleryModal');
		var closeBtn = document.querySelector('.close-button');
		var carouselContainer = document.querySelector('.gallery-carousel');

		closeBtn.addEventListener('click', function() {
			modal.style.display = "none";
			$(carouselContainer).trigger('destroy.owl.carousel');
			carouselContainer.innerHTML = '';
		});

		galleryItems.forEach(function(item) {
			item.addEventListener('click', function() {
				var images = JSON.parse(this.getAttribute('data-images'));
				images.forEach(function(imageUrl) {
					var div = document.createElement('div');
					var img = document.createElement('img');
					img.src = imageUrl;
					div.appendChild(img);
					carouselContainer.appendChild(div);
				});

				$(carouselContainer).owlCarousel({
					items: 1,
					loop: true,
					nav: true,
					dots: true,
				});

				modal.style.display = "block";
			});
		});
	})(jQuery);
});
</script>
<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();