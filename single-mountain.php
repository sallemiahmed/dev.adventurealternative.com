<?php
/**
 * The Template for displaying all Mountain posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$current_mountain_ID = get_the_ID();
$country = get_field('country');
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
			$video_section = get_field('video_section_g');
			$video_display = $video_section['display'];
			if($video_display):?>
			<a href="#video" class="nav-link smooth-scroll">
				<div class="link-text">
					Video
				</div>
			</a>
			<?php endif; ?>
			<?php
			$route_map_g = get_field('route_map_g');
			$map_display = $route_map_g['display'];
			if ($map_display): ?>
			<a href="#route-map" class="nav-link smooth-scroll">
				<div class="link-text">
					Route Map
				</div>
			</a>
			<?php endif; ?>
			<?php
			$experienced_g = get_field('experienced_g');
			$experienced_display = $experienced_g['display'];
			if( $experienced_display ): ?>
			<a href="#experienced-operator" class="nav-link smooth-scroll">
				<div class="link-text">
					Experienced Operator
				</div>
			</a>
			<?php endif; ?>
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
			<?php
			$maps_g = get_field('maps_g');
			$maps_display = $maps_g['display'];
			if( $maps_display ): ?>
			<a href="#mountain-maps" class="nav-link smooth-scroll">
				<div class="link-text">
					Maps
				</div>
			</a>
			<?php endif; ?>
			<?php $faq_r = get_field('faq');
			if (!empty($faq_r)):?>
			<a href="#faq" class="nav-link smooth-scroll">
				<div class="link-text">
					FAQ
				</div>
			</a>
			<?php endif; ?>
			<a href="#blogPosts" class="nav-link smooth-scroll">
				<div class="link-text">
					Blog Posts
				</div>
			</a>
		</div>
	</div>
</section>
<?php
/* Intro */
$intro_g = get_field('intro_g');
$intro_title = $intro_g['title'];
$intro_content = $intro_g['content'];
?>
<section id="trips">
    <div class="container">
        <div class="breadcrumbs">
			<a href="/">Home</a>
			<p class="sep">
				&gt;
			</p>
			<a href="/mountain-holidays/">Mountains</a>
			<p class="sep">
				&gt;
			</p>
			<p class="current">
				<?php the_title(); ?>
			</p>
		</div>
        <div class="title-row">
            <h2 class="subheading">
               <?= $intro_title; ?>
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
			// Count the number of paragraphs in the intro content
			$paragraphs = substr_count($intro_content, '</p>');

			// Extract the first paragraph
			$first_paragraph = nl2br(strip_tags(mb_substr($intro_content, 0, strpos($intro_content, '</p>') + 4)));
			?>

			<div class="content-truncated">
				<?= $first_paragraph; ?>
			</div>

			<?php if ($paragraphs > 1): ?>
				<div class="content-full" style="display: none;">
					<?= $intro_content; ?>
				</div>
				<button class="read-more-btn">Read More</button>
			<?php endif; ?>
		</div>
        <div class="trips-grid">
           <?php 
           $args = array(
				'post_type'      => 'trip',
				'posts_per_page' => -1,
				'no_found_rows'  => true, // minor perf win
				'meta_query'     => array(
					'relation' => 'OR',
					// Legacy: when the field was a single value (stored as plain ID)
					array(
						'key'     => 'mountain',
						'value'   => $current_mountain_ID,
						'compare' => '=',
					),
					// New: when the field allows multiple values (stored as serialized array)
					array(
						'key'     => 'mountain',
						'value'   => '"' . $current_mountain_ID . '"',
						'compare' => 'LIKE',
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
<?php
$video_section = get_field('video_section_g');
$video_display = $video_section['display'];
$video_title = $video_section['title'];
$video_content = $video_section['content'];
$video = $video_section['video'];
if($video_display):?>
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
			</div>
			<div class="video-right">
				<?= $video; ?>
			</div>
		</div>
	</div>
</section>
<?php endif; ?>
<?php
$route_map_g = get_field('route_map_g');
$map_display = $route_map_g['display'];
$route_map_title = $route_map_g['title'];
$map_image = $route_map_g['map_image'];
$earth_link = $route_map_g['earth_link'];
?>
<?php if ($map_display): ?>
    <section id="route-map">
        <div class="container">
            <h2 class="subheading">
                <?php echo esc_html($route_map_title); ?>
            </h2>
            <div class="route-map-img">
                <?php echo wp_get_attachment_image($map_image, 'full'); ?>
            </div>
            <?php if (!empty($earth_link)): ?>
                <div class="earth-link-row">
                    <div class="earth-icon">
                        <?php echo wp_get_attachment_image(1375, 'full'); ?>
                    </div>
                    <a class="cta-btn" href="<?= esc_url($earth_link); ?>">
                        View Routes on Google Earth
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
<?php
$experienced_g = get_field('experienced_g');
$experienced_display = $experienced_g['display'];
$experienced_title = $experienced_g['title'];
$experienced_content = $experienced_g['content'];
$experienced_links = $experienced_g['experienced_links'];
if( $experienced_display ): ?>
    <section id="experienced-operator">
        <div class="container">
            <h2 class="subheading">
                <?php echo esc_html($experienced_title); ?>
            </h2>
			<div class="col-2">
				<div class="content-wysiwyg">
					<?php echo wp_kses_post($experienced_content); ?>
				</div>
				<div class="rating-row">
					<div class="tripadvisor rating">
                        <?= wp_get_attachment_image(5482, "full"); ?>
                    </div>
                    <div class="googlereviews rating">
                        <?= wp_get_attachment_image(5483, "full"); ?>
                    </div>
				</div>
				<?php if (!empty($experienced_links)): ?>
					<div class="experienced-links">
						<?php foreach ($experienced_links as $row): ?>
							<div class="link-set">
								<div class="link-image">
									<?= wp_get_attachment_image($row['image'], 'medium'); ?>
								</div>
								<a class="cta-btn" href="<?= esc_url($row['button_url']); ?>"><?= esc_html($row['button_text']); ?></a>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
        </div>
    </section>
    <?php
	endif; 
?>
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
$information_g = get_field('key_information_g');
$country_information_g = get_field('information_g', $country);
$information_title = $information_g['title'];
$general_info = $country_information_g['general_info'];
$general_info_2 = $country_information_g['further_info'];
$further_info = $information_g['further_information'];
?>
<section id="information">
	<div class="container">
		<h2 class="subheading">
			<?= $information_title ?>
		</h2>
		<div class="flex-row">
			<div class="flex-half information-column">
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
					<?php
					if (!empty($general_info_2)) {
						foreach ($general_info_2 as $row) {
							echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
						}
					}
					?>
				</div>
			</div>
			<div class="flex-half information-column">
				<?php if(!empty($further_info)):?>
				<h3>
					Further Information
				</h3>
				<div class="information-list">
					<?php
					if (!empty($further_info)) {
						foreach ($further_info as $row) {
							echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
						}
					}
					?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
<?php
$maps_g = get_field('maps_g');
$maps_display = $maps_g['display'];
$maps_title = $maps_g['title'];
$maps_maps = $maps_g['maps'];
if( $maps_display ): ?>
    <section id="mountain-maps">
        <div class="container">
            <h2 class="subheading">
                <?php echo esc_html($maps_title); ?>
            </h2>
			<?php if (!empty($maps_maps)): ?>
				<div class="mt-maps">
					<?php foreach ($maps_maps as $row): ?>
						<div class="link-set">
							<div class="map-image">
								<?= wp_get_attachment_image($row['map_image'], 'medium'); ?>
							</div>
							<a class="cta-btn" target="_blank" href="<?= wp_get_attachment_url($row['map_image']); ?>">View Our <?= esc_html($row['map_name']); ?> Map</a>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
    </section>
    <?php
	endif; 
?>
<?php
/* FAQ Section */
$faq_r = get_field('faq');
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
<?php
endif;
?>
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
$posts_g = get_field('posts_g');
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
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();