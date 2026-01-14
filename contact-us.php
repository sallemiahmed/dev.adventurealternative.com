<?php
/**
 * Template Name: Contact Us
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$office_locations = $hero_g['office_locations'];
$expert_image = $hero_g['trek_expert_image'];
$hero_image = $hero_g['img'];
?>
<script>
    var officeLocations = <?php echo json_encode($office_locations); ?>;
	console.log(officeLocations);
</script>
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
<?php
$contact_form = get_field('form_code');
?>
<section id="contact_body">
	<div class="background-graphic">
		<?= wp_get_attachment_image(54, 'full'); ?>
	</div>
	<div class="container">
		<div class="contact-form">
			<?= do_shortcode($contact_form); ?>
		</div>
		<div class="page-divider"></div>
		<div class="title-row center">
            <div class="tripadvisor rating">
                <?= wp_get_attachment_image(5482, "full"); ?>
            </div>
            <div class="googlereviews rating">
                <?= wp_get_attachment_image(5483, "full"); ?>
            </div>
        </div>
		<div class="contact-details-wrapper">
			<div class="contact-details">
				<h2 class="subheading">
					AA Offices in our Main Destinations
				</h2>
				<select id="office-location-selector" class="office-location-selector">
					<?php foreach($office_locations as $index => $office_location): ?>
						<option value="<?= $index; ?>" <?= $index === 0 ? 'selected' : ''; ?>>
							<?= esc_html($office_location['office_location']); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="contact-details-row">
					<div class="contact-address">
						
					</div>
					
					<div class="contact-info">
						Hours:
						Hours:
					</div>
				</div>
				<div class="contact-map" style="display:none;"></div>
				<div class="contact-staff-grid">
					<h3>
						Our Office Staff
					</h3>
				</div>
			</div>
			<div class="contact-details">
				<div class="avatar">
					<div class="faux-bg-img">
						<?= wp_get_attachment_image($expert_image, 'full'); ?>
					</div>
				</div>
				<h2 class="subheading trek-expert">
					Speak To an Expert
				</h2>
				<div class="content">
					Call or email us with your enquiry and we will get back to you as soon as possible.
				</div>
				<div class="contact-details-row">
					<a class="contact-button" href='tel:+442870831258'>
						<?= wp_get_attachment_image(800, 'thumbnail'); ?>
						<p>
							Call Us
						</p>
					</a>
					<a class="contact-button" href='mailto:office@adventurealternative.com'>
						<?= wp_get_attachment_image(801, 'thumbnail'); ?>
						<p>
							Email Us
						</p>
					</a>
					<!--<a class="contact-button" href=''>
						<?php //echo wp_get_attachment_image(799, 'thumbnail'); ?>
						<p>
							WhatsApp Us
						</p>
					</a>-->
				</div>
			</div>
		</div>
	</div>
</section>
<?php
$safety_g = get_field('safety_section');
$safety_title = $safety_g['title'];
$safety_content = $safety_g['content'];
$safety_image = $safety_g['image'];
?>
<section id="safety">
	<div class="container">
		<h2 class="subheading">
			<?= $safety_title; ?>
		</h2>
		<div class="flex-row">
			<div class="safety-left">
				<?= $safety_content; ?>
				<a class="trips-button" href="/policies/">View Our Health &amp; safety Policy</a>
			</div>
			<div class="safety-right">
				<div class="safety-img">
					<div class="faux-bg-img">
						<?= wp_get_attachment_image($safety_image, 'medium_large'); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<?php
$other_branches = get_field('other_branches');
$branches_title = $other_branches['title'];
$branches_content = $other_branches['content'];
$branches = $other_branches['branches'];
?>
<section id="otherBranches">
	<div class="container">
		<h2 class="subheading">
			<?= $branches_title; ?>
		</h2>
		<div class="content">
			<?= $branches_content; ?>
		</div>
		<div class="branches">
			<?php
			if (!empty($branches)):
				foreach ($branches as $branch): 
					$branch_img = $branch['logo'];
					$branch_url = $branch['link'];
					?>
					<a class="branch-link" href="<?=$branch_url;?>"><?= wp_get_attachment_image($branch_img, 'full'); ?></a>
				<?php 
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>
<?php
$leaders = get_field('trip_leaders_section');
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
<script>
jQuery(document).ready(function($) {
    const selector = $('#office-location-selector');
    const addressDisplay = $('.contact-address');
    const infoDisplay = $('.contact-info');
    const staffGrid = $('.contact-staff-grid');
    const mapDisplay = $('.contact-map');

    function updateOfficeDetails(index) {
        const location = officeLocations[index];

        // Address
        addressDisplay.html(location.address || '');

        // Contact info
        const hours = location.office_hours ? `Hours: ${location.office_hours}` : '';
        const tel   = location.phone ? ` <br> Tel: ${location.phone}` : '';
        const email = location.email ? ` <br> Email: ${location.email}` : '';
        infoDisplay.html(`${hours}${tel}${email}`);

        // Map (only if provided)
        if (location.map_embed_code) {
            mapDisplay.html(location.map_embed_code).show();
        } else {
            mapDisplay.empty().hide();
        }

        // Staff grid
        staffGrid.html('<h3>Our Office Staff</h3>');
        if (Array.isArray(location.office_staff)) {
            location.office_staff.forEach(staff => {
                staffGrid.append(`
                    <div class="staff-member">
                        <img src="${staff?.image?.url || ''}" alt="${staff?.name || ''}" />
                        <p>${staff?.name || ''}</p>
                        <p>${staff?.role || ''}</p>
                    </div>
                `);
            });
        }

        // Contact buttons
        if (location.phone) $('.contact-button').eq(0).attr('href', `tel:${location.phone}`);
        if (location.email) $('.contact-button').eq(1).attr('href', `mailto:${location.email}`);
        // Only set WhatsApp link if you actually have a 3rd button present:
        // if (location.whatsapp) $('.contact-button').eq(2).attr('href', `https://wa.me/${location.whatsapp}`);
    }

    // Initial update
    updateOfficeDetails(parseInt(selector.val(), 10) || 0);

    // Listen for changes
    selector.on('change', function() {
        updateOfficeDetails(parseInt(this.value, 10));
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