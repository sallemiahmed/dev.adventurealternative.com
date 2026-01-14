<?php
/**
 * The Template for displaying all *INSERT POST TYPE NAME HERE* posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header();
$postIconMap = array(
    97 => 1097,
    103 => 1155,
    206 => 1098,
    104 => 1099,
    102 => 1100,
    254 => 1101
);
$leader_info_g = get_field('leader_info_g');
$parent_destination = $leader_info_g['country'];
$country_icon_id = 0;
if (array_key_exists($parent_destination, $postIconMap)) {
    $country_icon_id = $postIconMap[$parent_destination];
} else {
    //Do Nothing
} 
$gallery = $leader_info_g['gallery'];
if (!empty($gallery)) {
	$hero_image = $gallery[0];
} else {
	$hero_image = 1047;
}
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= wp_get_attachment_image($hero_image, 'full'); ?>
	</div>
	<div class="container">
		<h1 class="title">
			Meet <br>
			<i><?= get_the_title(); ?></i>
		</h1>
	</div>
</section>
<section id="leader-details" class="leader-section">
	<div class="background-graphic">
		<?= wp_get_attachment_image($country_icon_id, 'full'); ?>
	</div>
    <div class="container">
        <div class="breadcrumbs">
            <a href="/">Home</a>
            <p class="sep">&gt;</p>
            <a href="/about-us/">About Us</a>
			<p class="sep">&gt;</p>
            <a href="/trip-leaders/">Our Trip Leaders</a>
            <p class="sep">&gt;</p>
			<?php $upper_url = "/trip-leaders/" . get_post_field('post_name', $parent_destination); ?>
			<a href="<?= $upper_url; ?>"><?= get_the_title($parent_destination); ?> Trip Leaders</a>
			<p class="sep">&gt;</p>
            <p class="current"><?= get_the_title();?></p>
        </div>
        <h2 class="subheading">Our Trip Leaders</h2>
        <?php
        $trip_leader_id = get_the_ID(); // Replace this with the actual trip leader's ID.
        $current_country_icon = $country_icon_id; // Assuming this is a constant, or fetch as needed.
        
        // Fetch the trip leader's post data.
        $trip_leader_post = get_post($trip_leader_id);
        if ($trip_leader_post !== null) {
            setup_postdata($GLOBALS['post'] =& $trip_leader_post);
            
            $leader_info = get_field('leader_info_g', $trip_leader_id);
            $country = $leader_info['country'];
            $author_only = get_field('author_only', $trip_leader_id);
            
            if (!$author_only) { // Include this check if needed.
                $role = $leader_info['role'];
                $email = $leader_info['email_address'];
                $background = $leader_info['background'];
                $notable_traits = $leader_info['notable_traits'];
                ?>
                <div class="flex-row leader-row">
                    <div class="leader-sidebar">
                        <div class="leader-avatar">
                            <?php if (has_post_thumbnail($trip_leader_id)): ?>
                                <?php echo get_the_post_thumbnail($trip_leader_id, 'thumbnail'); ?>
                            <?php endif; ?>
                        </div>
						<div class="sidebar-content">
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
									<a class="social-link" href="<?php echo esc_url($url); ?>">
										<?php echo wp_get_attachment_image($icon_id, "full"); ?>
									</a>
								<?php endforeach; ?>
							<?php endif; ?>
                        	</div>
						<?php endif; ?>
							<div class="notable-traits">
								<?php echo wp_kses_post($notable_traits); ?>
							</div>
							<?php if (!empty($gallery)): ?>
								<?php
								$image_urls = array_map(function ($image_id) {
									return wp_get_attachment_url($image_id);
								}, $gallery);
								?>
								<button class="view-gallery-btn" data-gallery='<?= esc_attr(json_encode($image_urls)); ?>'>View Gallery</button>
							<?php endif; ?>
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
									<?= get_the_title($parent_destination); ?>
								</p>
								<?php echo wp_get_attachment_image($current_country_icon, "full"); ?>
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
            }
            wp_reset_postdata();
        }
        ?>
    </div>
</section>
<div id="galleryModal" style="display:none;">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <div class="gallery-carousel owl-theme"></div>
    </div>
	<span class="close-button"></span>
</div>
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
        element.parentElement.style.opacity = 0.5;
    } else {
        content.style.display = "block";
        element.parentElement.style.opacity = 1;
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
$(document).ready(function(){
    (function ($) {
        var modal = $('#galleryModal');
        var carouselContainer = $('.gallery-carousel');

        $('.view-gallery-btn').on('click', function() {
            var images = JSON.parse($(this).attr('data-gallery'));
            carouselContainer.empty(); // Clear previous images

            images.forEach(function(imageUrl) {
                var div = $('<div></div>');
                var img = $('<img>').attr('src', imageUrl); // Use URL directly
                div.append(img);
                carouselContainer.append(div);
            });

            $(carouselContainer).owlCarousel({
                items: 1,
                loop: true,
                nav: true,
                dots: true
            });

            modal.show();
        });

        $('.close-button').on('click', function() {
            modal.hide();
            $(carouselContainer).trigger('destroy.owl.carousel');
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
