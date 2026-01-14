<?php
/**
 * The Template for displaying all Country posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$color = get_field('color');
if ($color) : ?>
    <style>
        :root {
            --default-color: <?php echo $color; ?>;
        }
    </style>
<?php endif; 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$hero_image = $hero_g['image'];
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= wp_get_attachment_image($hero_image, 'full'); ?>
	</div>
	<div class="container">
		<h1 class="title">
			<?= $hero_title; ?>
		</h1>
	</div>
</section>
<section id="mininav">
	<div class="container">
		<div class="nav-links">
			<a href="#trips" class="nav-link smooth-scroll">
				<div class="link-text">
					Trips
				</div>
			</a>
			<?php
			$content_r = get_field('content_r');
			if( $content_r ): ?>
			<a href="#about" class="nav-link smooth-scroll">
				<div class="link-text">
					About
				</div>
			</a>
			<?php endif; ?>
			<a href="#information" class="nav-link smooth-scroll">
				<div class="link-text">
					Information
				</div>
			</a>
			<a href="#blogPosts" class="nav-link smooth-scroll">
				<div class="link-text">
					Blog Posts
				</div>
			</a>
		</div>
	</div>
</section>
<?php
$intro = get_field('intro_text');
?>
<section id="trips">
    <div class="container">
        <div class="breadcrumbs">
			<a href="/">Home</a>
			<p class="sep">
				&gt;
			</p>
			<a href="/countries/">Countries</a>
			<p class="sep">
				&gt;
			</p>
			<p class="current">
				<?php the_title(); ?>
			</p>
		</div>
        <div class="title-row">
            <h2 class="subheading">
               Our <?php the_title(); ?> Trips
            </h2>
            <div class="tripadvisor rating">
                <?= wp_get_attachment_image(5482, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(5483, "full"); ?>
            </div>
        </div>
		<div class="content">
			<?php
			// Match all paragraph tags in the intro content
			preg_match_all('/<p[^>]*>(.*?)<\/p>/', $intro, $matches);

			// Check if at least one paragraph exists and extract the first paragraph
			$first_paragraph = isset($matches[0][0]) ? $matches[0][0] : '';

			// Count the number of paragraphs
			$paragraphs = count($matches[0]);
			?>

			<div class="content-truncated">
				<?= $first_paragraph; ?>
			</div>

			<?php if ($paragraphs > 1): ?>
				<div class="content-full" style="display: none;">
					<?= $intro; ?>
				</div>
				<button class="read-more-btn">Read More</button>
			<?php endif; ?>
		</div>
        <div class="filter-row">
            <div class="filter-label">
                Filter our trips
            </div>
            <?php 
                $current_country_id = get_the_ID();
                $trip_types = get_terms('trip-type', array('orderby' => 'name'));
                
                echo '<a href="#" data-trip-type="" class="filter-link current">All Types</a>';

                foreach ($trip_types as $type) {
                    echo '<a href="#" data-trip-type="'.$type->slug.'" class="filter-link">'.$type->name.'</a>';
                }
            ?>
        </div>
		<div class="trips-loading-overlay" style="display:none;">
			<div class="loading-spinner">
				<img src="/wp-content/uploads/2024/01/Rolling-1s-200px.svg" alt="Loading...">
			</div>
		</div>
        <div class="trips-grid">
           <?php 
            $args = array(
                'post_type' => 'trip',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'country',
                        'value' => $current_country_id,
                        'compare' => '=',
                    ),
                ),
            );
            if (isset($_GET['trip-type']) && !empty($_GET['trip-type'])) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'trip-type',
                        'field'    => 'slug',
                        'terms'    => $_GET['trip-type'],
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
</section>
<?php 
$featuredLeader = get_field('featured_leader');
$featuredLeaderInfo = get_field('leader_info_g', $featuredLeader);
$featuredLeaderName = get_the_title($featuredLeader);
$featuredLeaderRole = $featuredLeaderInfo['role'];
$featuredLeaderBackground = $featuredLeaderInfo['background'];
if($featuredLeader):?>
<section id="featuredLeader">
	<div class="background-graphic">
		<?= wp_get_attachment_image(503, 'full'); ?>
	</div>
	<div class="container">
		<div class="title-row">
            <h2 class="subheading">
               Speak to a <?= get_the_title();?> Expert
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
		</div>
	</div>
</section>
<? endif; ?>
<? $post_id = get_field('featured_review');
if( $post_id ):?>
<section id="testimonial">
	<div class="container">
		<h2 class="subheading">
			What Our <?= get_the_title(); ?> Adventurers Think:
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
</section>
<?php endif; ?>
<?php
$content_r = get_field('content_r');
if( $content_r ): ?>
	<span id="about"></span>
    <?php foreach( $content_r as $row ): ?>
    <section id="alternating-content">
        <div class="container">
			<?php if($row['title']): ?>
                <h2 class="subheading">
                    <?php echo esc_html(ucwords(strtolower($row['title']))); ?>
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
$information_g = get_field('information_g');
$information_title = $information_g['title'];
$general_info = $information_g['general_info'];
$further_info = $information_g['further_info'];
$info_img = $information_g['image'];
if($information_title):
?>
<section id="information">
	<div class="container">
		<h2 class="subheading">
			<?= $information_title ?>
		</h2>
		<div class="flex-row">
			<div class="flex-half">
				<div class="charity-content">
					<div class="flex-row sub-row">
						<?php if (!empty($general_info)): ?>
						<div class="flex-half information-column">
							<h3>
								General Information
							</h3>
							<div class="information-list">
								<?php
									foreach ($general_info as $row) {
										echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
									}
								?>
							</div>
						</div>
						<?php endif; ?>
						<?php if (!empty($further_info)): ?>
						<div class="flex-half information-column">
							<h3>
								Further Information
							</h3>
							<div class="information-list">
								<?php
									foreach ($further_info as $row) {
										echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
									}
								?>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="flex-half">
				<div class="charity-content">
					<?= wp_get_attachment_image($info_img, 'full'); ?>
				</div>
			</div>
		</div>
	</div>
</section>
<?php endif;
include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
$posts_g = get_field('posts_g');
$posts_content = $posts_g['content'];
$page_title_slug = sanitize_title(get_the_title());
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
			Our <?= get_the_title(); ?> Articles
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
document.addEventListener('DOMContentLoaded', function () {
	const btn = document.querySelector('.read-more-btn');
	const fullContent = document.querySelector('.content-full');
	const truncatedContent = document.querySelector('.content-truncated');

	btn.addEventListener('click', function () {
		if (fullContent.style.display === 'none') {
			fullContent.style.display = 'block';
			truncatedContent.style.display = 'none';
			btn.textContent = 'Read Less';
		} else {
			fullContent.style.display = 'none';
			truncatedContent.style.display = 'block';
			btn.textContent = 'Read More';
		}
	});
});
jQuery(document).ready(function($) {
	var ajax_params = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('ajax_nonce'); ?>'
    };
    $('.filter-link').click(function(e) {
        e.preventDefault();
		$('.filter-link').removeClass('current');
		$(this).addClass('current');
        var tripType = $(this).data('trip-type');
        var currentCountryId = <?php echo json_encode(get_the_ID()); ?>;
		
		$('.trips-loading-overlay').show();
        $.ajax({
            type: 'POST',
            url: ajax_params.ajax_url,
            data: {
                action: 'filter_trips_by_country_and_type',
                trip_type: tripType,
                current_country_id: currentCountryId,
                nonce: ajax_params.nonce
            },
            success: function(response) {
                $('.trips-grid').html(response);
				$('.trips-loading-overlay').hide();
				return false;
            },
			error: function() {
				$('.trips-loading-overlay').hide();
			} 
        });
    });
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