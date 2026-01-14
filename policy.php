<?php
/**
 * Template Name: Policy Page
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= the_post_thumbnail( 'full' ); ?>
	</div>
	<div class="container">
		<h1 class="title">
			<?= get_the_title(); ?>
		</h1>
	</div>
</section>
<section id="mininav">
	<div class="container">
		<div class="nav-links">
			<a href="/policies/" class="nav-link smooth-scroll">
				<div class="link-text">
					Policies &amp; standards
				</div>
			</a>
			<a href="/policies/terms-and-conditions/" class="nav-link smooth-scroll">
				<div class="link-text">
					T&amp;Cs
				</div>
			</a>
			<a href="/policies/financial-protection/" class="nav-link smooth-scroll">
				<div class="link-text">
					Financial Protection
				</div>
			</a>
			<a href="/policies/data-protection/" class="nav-link smooth-scroll">
				<div class="link-text">
					Data Protection
				</div>
			</a>
			<a href="/policies/package-travel-law/" class="nav-link smooth-scroll">
				<div class="link-text">
					Package Travel Law
				</div>
			</a>
			<a href="/policies/complaints/" class="nav-link smooth-scroll">
				<div class="link-text">
					Complaints
				</div>
			</a>
			<a href="/policies/standards/" class="nav-link smooth-scroll">
				<div class="link-text">
					Standards
				</div>
			</a>
			<a href="/policies/health-and-safety-policy/" class="nav-link smooth-scroll">
				<div class="link-text">
					Health &amp; Safety
				</div>
			</a>
			<a href="/policies/safety-on-youth-trips/" class="nav-link smooth-scroll">
				<div class="link-text">
					Safety on Youth Trips
				</div>
			</a>
			<a href="/policies/website-cookies/" class="nav-link smooth-scroll">
				<div class="link-text">
					Website Cookies
				</div>
			</a>
		</div>
	</div>
</section>
<section id="postContent">
	<div class="container">
		<div class="pdf-button">
			<button onclick="printCurrentPage()">
				<div class="download-icon">
					<?= wp_get_attachment_image(990, 'full'); ?>
				</div>
				Download PDF
			</button>
		</div>
		<?php
			$content = apply_filters( 'the_content', get_the_content() );
			echo $content;
		?>
	</div>
</section>
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
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
			Adventure Alternative Articles
		</h2>
		<div class="content">
			We're dedicated to helping you make the most of your next adventure trekking holiday. That's why we've created our travel blog full of in-depth trekking guides, travel inspiration and other fantastic information. Having done all of these climbs many times already, we want to pass on our wealth of trekking wisdom to you.
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
function printCurrentPage() {
    window.print();
}
</script>
<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();