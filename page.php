<?php
/**
 * Template Name: Misc Text
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
<section id="postContent">
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
					<? $excerpt = strip_tags($post->post_content);
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