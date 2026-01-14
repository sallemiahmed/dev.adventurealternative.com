<?php
/**
 * Template Name: Homepage
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$hero_content = $hero_g['content'];
$isp_r = $hero_g['isp_r'];
$background = $hero_g['background'];
?>
<section id="hero">
	<div class="background-graphic">
		<?= wp_get_attachment_image(54, 'full'); ?>
	</div>
	<div class="container">
		<div class="hero-content">
			<h1 class="title">
				<?= $hero_title; ?>
			</h1>
			<div class="hero-text">
				<?= $hero_content; ?>
			</div>
			<div class="isp-row">
				<?php
				if (!empty($isp_r)) {
					foreach ($isp_r as $row) {
						echo '<div class="isp-tile">';
							echo '<div class="isp-icon">' . wp_get_attachment_image($row['icon'], 'full') . '</div>';
							echo '<div class="isp-content">' . $row['details'] . '</div>';
						echo '</div>';
					}
				}
				?>
			</div>
		</div>
	</div>
</section>
<section id="countries">
    <div class="countries-grid owl-carousel">
        <?php
        $featured_countries = get_field('featured_countries');

        if ($featured_countries) :
            $args = array(
                'post_type' => 'country',
                'post__in' => $featured_countries,
                'posts_per_page' => -1,
                'orderby' => 'post__in'
            );
            $countries_query = new WP_Query($args);

            if ($countries_query->have_posts()) :
                while ($countries_query->have_posts()) : $countries_query->the_post();
                    $featured_image = get_the_post_thumbnail(get_the_ID(), 'full');
                    $color = get_field('color');
       				?>
                    <a class="column" href="<?= get_permalink(); ?>">
                        <div class="bar" style="background:<?= $color; ?>"></div>
                        <div class="title">Adventures in<br><?php the_title(); ?></div>
                        <div class="graphic">
                            <div class="faux-bg-img">
                                <?php echo $featured_image; ?>
                            </div>
                        </div>
                    </a>
        <?php
                endwhile;
            endif;
            wp_reset_postdata();
        endif;
        ?>
    </div>
</section>
<div class="seal-wrapper">
	<div class="seal">
		<?= wp_get_attachment_image(135, "full"); ?>
	</div>
</div>
<section id="tours">
    <div class="container">
        <div class="title-row">
            <h2 class="subheading">
                Our Popular Tours &amp; Trips
            </h2>
            <div class="tripadvisor rating">
                <?= wp_get_attachment_image(5482, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(5483, "full"); ?>
            </div>
        </div>
		<div class="filter-carousel-wrapper">
			<div class="filter-carousel">
				<?php 
					$categories = get_terms('trip-category', array('orderby' => 'name'));
					$featured_category_slug = 'featured-trips';

					echo '<a href="#" data-category="'.$featured_category_slug.'" class="filter-link current">Featured Trips</a>';

					foreach ($categories as $category) {
						if ($category->slug != $featured_category_slug) {
							echo '<a href="#" data-category="'.$category->slug.'" class="filter-link">'.$category->name.'</a>';
						}
					}
				?>
			</div>
		</div>
		<div class="trips-loading-overlay" style="display:none;">
			<div class="loading-spinner">
				<img src="/wp-content/uploads/2024/01/Rolling-1s-200px.svg" alt="Loading...">
			</div>
		</div>
        <div class="trips-carousel-wrapper">
            <div class="trips-carousel">
                <?php 
                    $args = array(
						'post_type' => 'trip',
						'posts_per_page' => 15,
						'tax_query' => array(
							array(
								'taxonomy' => 'trip-category',
								'field' => 'slug',
								'terms' => $category
							)
						)
					);

					// Check for category filter in URL
					if (isset($_GET['trip-category']) && !empty($_GET['trip-category'])) {
						$args['tax_query'] = array(
							array(
								'taxonomy' => 'trip-category',
								'field'    => 'slug',
								'terms'    => $_GET['trip-category'],
							),
						);
					}

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
    </div>
</section>
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
/* Charity */
$charity = get_field('charity_g');
$charity_title = $charity['title'];
$charity_content = $charity['content'];
$thumbnail = $charity['thumbnail'];
$video = $charity['video'];
?>
<section id="charity">
	<div class="stack">
		<div class="container">
			<div class="flex-row">
				<div class="flex-half">
					<h2 class="subheading">
						<?= $charity_title ?>
					</h2>
					<div class="charity-content">
						<?= $charity_content ?>
					</div>
					<a class="charity-btn" href="/about-us/#moving_mountains">Find Out How</a>
				</div>
				<div class="flex-half">
					
				</div>
			</div>
		</div>
	</div>
	<div class="flex-row">
		<div class="flex-half">
			
		</div>
		<div class="flex-half">
			<div class="video-container">
				<div class="play-button">
					<?= wp_get_attachment_image(232, 'full'); ?>
				</div>
				<div class="faux-bg-img">
					<?= wp_get_attachment_image($thumbnail, 'full'); ?>
				</div>
			</div>
		</div>
	</div>
</section>
<div class="video-modal" id="videoModal">
    <div class="video-modal-content">
        <span class="close-modal">&times;</span>
        <div class="video-embed">
            <?= $video; // This will embed the video ?>
        </div>
    </div>
</div>
<?php
$leaders = get_field('leaders_g');
$leaders_title = $leaders['title'];
$leaders_content = $leaders['content'];
?>
<section id="tripLeaders">
	<div class="container">
		<h2 class="subheading">
			<?= $leaders_title ?>
		</h2>
		<div class="content">
			<?= $leaders_content; ?>
		</div>
		<div class="leaders-row">
			<?php
				$args = array(
					'post_type' => 'trip-leader',
					'posts_per_page' => -1,
					'order' => 'ASC'
				);

				$trip_leaders_query = new WP_Query($args);
				$leaders_by_country = array();

				if ($trip_leaders_query->have_posts()) {
					while ($trip_leaders_query->have_posts()) {
						$trip_leaders_query->the_post();

						$author_only = get_field('author_only');
						if ($author_only === true) {
							continue; // Skip to the next post in the loop.
						}

						$leader_info = get_field('leader_info_g');
						$country_post_id = $leader_info['country'];
						if ($country_post_id) {
							$country_post = get_post($country_post_id); // Retrieve the post object using the ID
							$country_name = $country_post->post_name;
							$country_color = get_field('color', $country_post_id); // Use the ID to get the field
							if (!isset($leaders_by_country[$country_name])) {
								$leaders_by_country[$country_name] = array(
									'leader_id' => get_the_ID(),
									'image_url' => get_the_post_thumbnail_url(),
									'country_slug' => $country_name,
									'country_color' => $country_color,
								);
							}
						}
					}
				}
				wp_reset_postdata();
				echo '<div class="countries-container">';
				foreach ($leaders_by_country as $country_slug => $info) {
					$button_style = $info['country_color'] ? ' style="background-color:' . esc_attr($info['country_color']) . '; border:1px solid ' . esc_attr($info['country_color']) . ';"' : '';
					echo '<div class="country-column">';
					echo '<div class="leader-img"><img src="' . esc_url($info['image_url']) . '" alt=""></div>';
					echo '<a href="/trip-leaders/' . esc_attr($country_slug) . '" class="button"' . $button_style . '>' . esc_html(ucwords(str_replace('-', ' ', $country_slug))) . '</a>';
					echo '</div>';
				}
				echo '</div>';
			?>
		</div>
	</div>
</section>
<section id="testimonial">
	<div class="container">
		<h2 class="subheading">
			What Our Customers Say
		</h2>
		<div class="testimonial-wrapper">
			<div class="quote-left">
				<?= wp_get_attachment_image(298, 'full'); ?>
			</div>
			<?php
			$post_id = get_field('featured_review');
			if( $post_id ):
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
			<?php endif; ?>	
			<div class="quote-right">
				<?= wp_get_attachment_image(296, 'full'); ?>
			</div>
		</div>
	</div>
</section>
<?php
$posts_g = get_field('posts_g');
$posts_title = $posts_g['title'];
$posts_content = $posts_g['content'];
$blogposts = new WP_Query(
	array(
		'showposts'      => 3,
		'post_status'    => 'publish',
		'orderby'        => 'rand'
	)
);
if ($blogposts->have_posts()):
?>
<section id="blogPosts">
	<div class="container">
		<h2 class="subheading">
			<?= $posts_title; ?>
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
	<?php
	/**
	 * generate_after_primary_content_area hook.
	 *
	 * @since 2.0
	 */
	do_action( 'generate_after_primary_content_area' );

get_footer();