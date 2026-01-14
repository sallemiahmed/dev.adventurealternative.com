<?php
/**
 * Template Name: About Us
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$hero_g = get_field('hero_g');
$hero_title = $hero_g['title'];
$hero_content = $hero_g['content'];
$hero_image = $hero_g['img'];
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
	</div>
</section>
<section id="mininav">
	<div class="container">
		<div class="nav-links">
			<a href="#background" class="nav-link smooth-scroll">
				<div class="link-text">
					Background
				</div>
			</a>
			<a href="#ethics" class="nav-link smooth-scroll">
				<div class="link-text">
					Ethics
				</div>
			</a>
			<a href="#moving_mountains" class="nav-link smooth-scroll">
				<div class="link-text">
					Moving Mountains
				</div>
			</a>
			<a href="#safety_and_care" class="nav-link gallery smooth-scroll">
				<div class="link-text">
					Safety &amp; Care
				</div>
			</a>
		</div>
	</div>
</section>
<?php
$background_g = get_field('background_g');
$background_title = $background_g['title'];
$background_content = $background_g['content'];
$mountain_leaders_g = $background_g['mountain_leaders_g'];
$ml_img = $mountain_leaders_g['img'];
$ml_content = $mountain_leaders_g['content'];
?>
<section id="background" class="about-section">
	<div class="container">
		<h2 class="subheading">
			<?= $background_title; ?>
		</h2>
		<div class="content">
			<?= $background_content; ?>
		</div>
		<div class="background-footer">
			<div class="ml-img">
				<?= wp_get_attachment_image($ml_img, 'medium'); ?>
			</div>
			<div class="ml-content">
				<?= $ml_content; ?>
			</div>
		</div>
	</div>
</section>
<?php
$equity_g = get_field('equity_g');
$equity_title = $equity_g['title'];
$equity_content = $equity_g['content'];
?>
<section id="background" class="about-section red-section">
	<div class="container">
		<h2 class="subheading">
			<?= $equity_title; ?>
		</h2>
		<div class="content">
			<?= $equity_content; ?>
		</div>
	</div>
</section>
<?php
$ethics_g = get_field('ethics_g');
$ethics_title = $ethics_g['title'];
$ethics_content = $ethics_g['content'];
?>
<section id="ethics" class="about-section">
	<div class="container">
		<h2 class="subheading">
			<?= $ethics_title; ?>
		</h2>
		<div class="content">
			<?= $ethics_content; ?>
		</div>
	</div>
</section>
<?php
$mm_g = get_field('moving_mountains');
$mm_title = $mm_g['title'];
$mm_content = $mm_g['content'];
?>
<section id="moving_mountains" class="about-section red-section">
	<div class="container">
		<h2 class="subheading">
			<?= $mm_title; ?>
		</h2>
		<div class="content">
			<?= $mm_content; ?>
		</div>
	</div>
</section>
<?php
$safety_g = get_field('safety_g');
$safety_title = $safety_g['title'];
$safety_content = $safety_g['content'];
?>
<section id="safety_and_care" class="about-section">
	<div class="container">
		<h2 class="subheading">
			<?= $safety_title; ?>
		</h2>
		<div class="content">
			<?= $safety_content; ?>
		</div>
	</div>
</section>
<?php
$information_g = get_field('information_g');
$information_title = $information_g['title'];
$policy_links = $information_g['policy_links'];
$sustainability_links = $information_g['sustainability_links'];
?>
<section id="information">
	<div class="container">
		<h2 class="subheading">
			<?= $information_title ?>
		</h2>
		<div class="flex-row">
			<div class="flex-half information-column">
				<h3>
					Policies
				</h3>
				<div class="information-list">
					<?php
					if (!empty($policy_links)) {
						foreach ($policy_links as $row) {
							echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
						}
					}
					?>
				</div>
			</div>
			<div class="flex-half information-column">
				<h3>
					Sustainability
				</h3>
				<div class="information-list">
					<?php
					if (!empty($sustainability_links)) {
						foreach ($sustainability_links as $row) {
							echo '<a class="information-link" href="' . $row['url'] . '">' . $row['link_text'] . '</a>';
						}
					}
					?>
				</div>
			</div>
		</div>
	</div>
</section>
	<?php
	/**
	 * generate_after_primary_content_area hook.
	 *
	 * @since 2.0
	 */
	do_action( 'generate_after_primary_content_area' );

get_footer();