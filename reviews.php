<?php
/**
 * Template Name: Reviews
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$hero_content = $hero_g['content'];
?>
<section id="hero">
	<div class="container">
		<h1 class="title">
			<?= $hero_title; ?>
		</h1>
		<div class="hero-text">
			<?= $hero_content; ?>
		</div>
	</div>
</section>
<section id="reviews">
    <div class="container">
		<div class="breadcrumbs">
			<a href="/">Home</a>
			<p class="sep">
				&gt;
			</p>
			<p class="current">
				<?php the_title(); ?>
			</p>
		</div>
        <div class="title-row">
            <h2 class="subheading">
               <?php the_title(); ?>
            </h2>
            <div class="tripadvisor rating">
                <?= wp_get_attachment_image(5482, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(5483, "full"); ?>
            </div>
        </div>
		<div class="filter-row">
			<div class="filter-label">
				Filter our customer reviews
			</div>
			<?php 
				$categories = get_terms('testimonial-category', array('orderby' => 'name'));

				echo '<a href="#" data-category="" class="filter-link current">All Reviews</a>';

				foreach ($categories as $category) {
					if ($category->slug != $featured_category_slug) {
						echo '<a href="#" data-category="'.$category->slug.'" class="filter-link">'.$category->name.'</a>';
					}
				}
			?>
		</div>
		<div class="trips-loading-overlay" style="display:none;">
			<div class="loading-spinner">
				<img src="/wp-content/uploads/2024/01/Rolling-1s-200px.svg" alt="Loading...">
			</div>
		</div>
        <div class="reviews-grid">
           <?php 
			$args = array(
				'post_type' => 'testimonial',
				'posts_per_page' => 50,
				'tax_query' => array(
					
				)
			);

			// Check for category filter in URL
			if (isset($_GET['testimonial-category']) && !empty($_GET['testimonial-category'])) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'testimonial-category',
						'field'    => 'slug',
						'terms'    => $_GET['testimonial-category'],
					),
				);
			}

			$reviews = new WP_Query($args);
			if ($reviews->have_posts()) : 
				while ($reviews->have_posts()) : $reviews->the_post(); 
					$reviewer_img = get_field('reviewer_image');
					$reviewer_name = get_field('reviewer_name');
					$reviewer_destination = get_field('reviewer_destination');
					$stars = get_field('stars');
					$full_stars = floor($stars);
					$half_stars = ceil($stars - $full_stars);
					$empty_stars = 5 - $full_stars - $half_stars;
					$review_content = get_field('review_content');
					// Clean up and ensure proper nesting of tags
					$allowed_tags = '<p>';
					$review_content = wp_kses($review_content, array(
						'p' => array()
					));
					?>
					<div class="review-item" data-category="<?php echo get_the_terms(get_the_ID(), 'testimonial-category')[0]->slug; ?>">
						<div class="review-meta">
							<div class="reviewer">
								<?php if($reviewer_img): ?>
									<div class="reviewer-img">
										<div class="faux-bg-img">
											<?= wp_get_attachment_image($reviewer_img, 'thumbnail'); ?>
										</div>
									</div>
								<?php endif; ?>
								<div class="reviewer-details">
									<div class="reviewer-name">
										<?= $reviewer_name; ?>
									</div>
									<?php if($reviewer_destination): ?>
										<div class="reviewer-destination">
											<?= $reviewer_destination; ?>
										</div>
									<?php endif; ?>
								</div>
							</div>
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
						<div class="review-content">
							<?php
								$maxLength = 600;
								if (strlen($review_content) > $maxLength) {
									$displayContent = substr($review_content, 0, $maxLength) . '...';
									echo '<div class="short-content">' . $displayContent . '</div>';
									echo '<div class="full-content hidden">' . $review_content . '</div>';
									echo '<button class="read-more-btn">Read More</button>';
								} else {
									echo $review_content;
								}
							?>
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
$featuredLeader = get_field('leader');
$featuredLeaderInfo = get_field('leader_info_g', $featuredLeader);
$featuredLeaderName = get_the_title($featuredLeader);
$featuredLeaderRole = $featuredLeaderInfo['role'];
$featuredLeaderBackground = $featuredLeaderInfo['background'];
?>
<section id="featuredLeader">
	<div class="container">
		<div class="title-row">
            <h2 class="subheading">
               Speak to an Expert
            </h2>
            <div class="tripadvisor rating">
                <?= wp_get_attachment_image(134, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(133, "full"); ?>
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
			</div>
		</div>
	</div>
</section>
<?php
/* Charity */
$charity = get_field('charity_g', 59);
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
					<a class="charity-btn">Find Out How</a>
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
$leaders = get_field('leaders_g', 59);
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
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
$posts_g = get_field('posts_g', 59);
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
<script>
jQuery(document).ready(function($) {
	var ajax_params = {
        ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('ajax_nonce'); ?>'
    };
	
    $('.filter-link').click(function(e) {
        e.preventDefault();
		$('.filter-link').removeClass('current');
		$(this).addClass('current');
        var category = $(this).data('category');
        
		$('.trips-loading-overlay').show();
        $.ajax({
            type: 'POST',
            url: ajax_params.ajax_url,
            data: {
                action: 'filter_reviews',
                category: category,
                nonce: ajax_params.nonce
            },
            success: function(response) {
                $('.reviews-grid').html(response);
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
<?php endif; ?>
	<?php
	/**
	 * generate_after_primary_content_area hook.
	 *
	 * @since 2.0
	 */
	do_action( 'generate_after_primary_content_area' );

get_footer();