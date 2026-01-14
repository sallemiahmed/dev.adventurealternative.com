<?php
/**
 * The template for displaying 404 pages (Not Found).
 *
 * @package GeneratePress
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
        <?= wp_get_attachment_image(1671, 'full'); ?>
    </div>
	<div class="container">
		<h1 class="title">
			<i>404</i> Page Not Found
		</h1>
		<div class="hero-text">
			<p>
				Unfortunately this page does not exist, has been removed, or is temporarily unavailable.
			</p> 
			<a class="cta-btn" href="/">Return to Home</a>
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

	generate_construct_sidebars();

	get_footer();
