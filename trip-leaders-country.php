<?php
/**
 * Template Name: Trip Leaders Country
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$current_country_id = get_field('country');
$postIconMap = array(
    97 => 1097,
    103 => 1155,
    206 => 1098,
    104 => 1099,
    102 => 1100,
    254 => 1101
);
$country_icon_id = 0;
if (array_key_exists($current_country_id, $postIconMap)) {
    $country_icon_id = $postIconMap[$current_country_id];
} else {
    //Do Nothing
} 
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= the_post_thumbnail( 'full' ); ?>
	</div>
	<div class="container">
		<h1 class="title">
			Adventure Alternative <br>
			<i><?= get_the_title($current_country_id); ?></i>
		</h1>
	</div>
</section>
<section id="mininav">
	<div class="container">
		<div class="nav-links">
			<a href="/trip-leaders/uk-ireland/" class="nav-link smooth-scroll">
				<div class="link-text">
					UK &amp; Ireland
				</div>
			</a>
			<a href="/trip-leaders/kenya/" class="nav-link smooth-scroll">
				<div class="link-text">
					Kenya
				</div>
			</a>
			<a href="/trip-leaders/borneo/" class="nav-link smooth-scroll">
				<div class="link-text">
					Borneo
				</div>
			</a>
			<a href="/trip-leaders/nepal/" class="nav-link smooth-scroll">
				<div class="link-text">
					Nepal
				</div>
			</a>
			<a href="/trip-leaders/tanzania/" class="nav-link smooth-scroll">
				<div class="link-text">
					Tanzania
				</div>
			</a>
			<a href="/trip-leaders/morocco/" class="nav-link smooth-scroll">
				<div class="link-text">
					Morocco
				</div>
			</a>
		</div>
	</div>
</section>
<section id="leader-details" class="leader-section">
	<div class="container">
		<div class="breadcrumbs">
            <a href="/">Home</a>
            <p class="sep">&gt;</p>
            <a href="/about-us/">About Us</a>
			<p class="sep">&gt;</p>
            <a href="/trip-leaders/">Our Trip Leaders</a>
            <p class="sep">&gt;</p>
            <p class="current"><?= get_the_title();?></p>
        </div>
		<h2 class="subheading">
			Our <?= get_the_title(); ?>
		</h2>
		<?php
		$args = array(
			'post_type' => 'trip-leader',
			'posts_per_page' => -1,
			'order' => 'ASC',
		);
		$trip_leaders = new WP_Query($args);
		if ($trip_leaders->have_posts()) : while ($trip_leaders->have_posts()) : $trip_leaders->the_post();

			$leader_info = get_field('leader_info_g');
			$country = $leader_info['country'];
			$author_only = get_field('author_only');

			if ($author_only || $country != $current_country_id) continue;

			$role = $leader_info['role'];
			$email = $leader_info['email_address'];
			$background = $leader_info['background'];
			$notable_traits = $leader_info['notable_traits'];

			?>
			<div class="flex-row leader-row">
				<div class="background-graphic">
					<?= wp_get_attachment_image($country_icon_id, 'full'); ?>
				</div>
				<div class="leader-sidebar">
					<div class="leader-avatar">
						<?php if (has_post_thumbnail()): ?>
							<?php the_post_thumbnail('thumbnail'); ?>
						<?php endif; ?>
					</div>
					<?php if (!empty($leader_info['socials']) || !empty($email)): ?>
                        <div class="leader-sidebar-title">
                            <p>Contact <?php the_title(); ?></p>
                        </div>
                        <div class="leader-socials">
							<?php if ($email): ?>
								<a class="social-link" href="mailto:<?= $email; ?>"><?= wp_get_attachment_image(1118, "full"); ?></a>
							<?php endif; ?>
							<?php if (!empty($leader_info['socials'])): ?>
								<?php foreach ($leader_info['socials'] as $social):
									$platform = $social['platform'];
									$url = $social['url'];
									$icon_id = 0; // Adjust as needed.
									switch ($platform) {
										case 'instagram':
											$icon_id = 1120;
											break;
										case 'twitter':
											$icon_id = 1116;
											break;
										case 'facebook':
											$icon_id = 1117;
											break;
										case 'linkedin':
											$icon_id = 1119;
											break;
									}
									?>
									<a class="social-link" target="_blank" href="<?php echo esc_url($url); ?>">
										<?php echo wp_get_attachment_image($icon_id, "full"); ?>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
                        	</div>
						<?php endif; ?>
					<div class="notable-traits">
						<?php echo wp_kses_post($notable_traits); ?>
					</div>
				</div>
				<div class="leader-content">
					<div class="leader-title-row">
						<div class="leader-meta">
							<h3><?php the_title(); ?></h3>
							<p><?php echo esc_html($role); ?></p>
						</div>
						<div class="country-meta">
							<p>
								<?= get_the_title($current_country_id); ?>
							</p>
							<?php echo wp_get_attachment_image($country_icon_id, "full"); ?>
						</div>
					</div>
					<div class="background">
						<h4 class="background-title">Background</h4>
						<div class="background-content">
							<?php echo wp_kses_post($background); ?>
						</div>
					</div>
					<?php if (!empty($leader_info['other_sections'])): ?>
						<div class="accordion-container">
							<?php foreach ($leader_info['other_sections'] as $section): ?>
								<div class="accordion-item">
									<div class="accordion-title" onclick="toggleAccordion(this)">
										<?php echo esc_html($section['title']); ?>
									</div>
									<div class="accordion-content" style="display: none;">
										<?php echo wp_kses_post($section['content']); ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
					<button class="read-more-btn">Read More</button>
				</div>
			</div>
		<?php
			endwhile; wp_reset_postdata(); endif;
		?>
	</div>
</section>
<section id="other-countries">
	<div class="container">
		<div class="cta-links">
			<h2 class="subheading center">
				Discover our Trip Leaders in other Countries
			</h2>
			<div class="button-row">
				<?php
				$featured_countries = array(103, 97, 104, 102, 206, 254);
				$args = array(
					'post_type' => 'country',
					'post__in' => $featured_countries,
					'posts_per_page' => -1
				);
				$countries_query = new WP_Query($args);

				if ($countries_query->have_posts()) :
					while ($countries_query->have_posts()) : $countries_query->the_post();
						if (get_the_title() !== $country_to_skip) {
							$featured_image = get_the_post_thumbnail(get_the_ID(), 'full');
							$color = get_field('color');
							 if (!function_exists('get_brightness')) {
								function get_brightness($hex) {
									$hex = str_replace('#', '', $hex);
									$r = hexdec(substr($hex, 0, 2));
									$g = hexdec(substr($hex, 2, 2));
									$b = hexdec(substr($hex, 4, 2));
									return sqrt($r * $r * .241 + $g * $g * .691 + $b * $b * .068);
								}
							}
							$textColor = (get_brightness($color) > 130) ? '#000' : '#fff';
							$custom_url = "/trip-leaders/" . get_post_field('post_name', get_the_ID());
				?>
							<a class="country-button" style="background:<?= $color; ?>; color:<?= $textColor; ?>" href="<?= $custom_url; ?>">
								Leaders in <?php the_title(); ?>
							</a>
				<?php
						}
					endwhile;
				endif;
				wp_reset_postdata();
				?>
			</div>
		</div>
	</div>
</section>
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<script>
function toggleAccordion(element) {
    const content = element.nextElementSibling;
    if (content.style.display === "block") {
        content.style.display = "none";
    } else {
        content.style.display = "block";
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const readMoreButtons = document.querySelectorAll('.read-more-btn');

    readMoreButtons.forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('.leader-row').forEach(row => {
                row.classList.remove('active');
            });

            this.closest('.leader-row').classList.add('active');
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