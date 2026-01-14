<?php
/**
 * The Template for displaying all posts.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$hero_g = get_field('hero_g');
$hero_content = $hero_g['content'];
?>
<section id="hero">
	<div class="faux-bg-img">
		<?= the_post_thumbnail( 'full' ); ?>
	</div>
	<div class="container">
		<h1 class="title">
			<?= get_the_title(); ?>
		</h1>
		<div class="hero-text">
			<?= $hero_content; ?>
		</div>
	</div>
</section>
<section id="postContent">
	<div class="container">
		<div class="breadcrumbs">
			<a href="/">Home</a>
			<p class="sep">
				&gt;
			</p>
			<a href="/blog/">Blog</a>
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
		<?php
			$author = get_field('author');
			$author_img = get_the_post_thumbnail_url($author, 'full' );
			$author_name = get_the_title($author);
			$author_info_g = get_field('leader_info_g', $author);
			$author_role = $author_info_g['role'];
			$author_email = $author_info_g['email_address'];
			$author_first_name = explode(' ', trim($author_name))[0];
			$author_bio = $author_info_g['background'];
			$author_socials = $author_info_g['socials']; // Repeater containing "platform" dropdown and "url" text field
		?>
		<div class="author-section">
			<div class="flex-row">
				<div class="author-image">
					<img src="<?= $author_img; ?>" alt="<?= $author_name; ?>">
				</div>
				<div class="author-meta">
					<div class="author-title">
						Written by <?= $author_name; ?>
					</div>
					<div class="author-role">
						<?= $author_role; ?>
					</div>
					<div class="author-bio">
						<?= strlen($author_bio) > 275 ? substr($author_bio, 0, 272) . '...' : $author_bio; ?>
					</div>
					<div class="button-row">
						<a class="cta-btn" href="<?= get_permalink($author); ?>">
							View <?= $author_first_name; ?>'s Profile
						</a>
						<div class="author-socials">
							<?php if($author_email):?>
								<a class="social-link" href="mailto:<?= $author_email; ?>"><?= wp_get_attachment_image(1011, "full"); ?></a>
							<?php endif; ?>
							<?php if( !empty($author_socials) ): ?>
                                <?php foreach( $author_socials as $social ): 
                                    $platform = $social['platform'];
                                    $url = $social['url'];
                                    $icon_id = 0; // Default to 0, adjust as needed
                                    switch ($platform) {
                                        case 'instagram':
                                            $icon_id = 1008;
                                            break;
                                        case 'twitter':
                                            $icon_id = 1009;
                                            break;
                                        case 'facebook':
                                            $icon_id = 1010;
                                            break;
                                        case 'linkedin': // Make sure the case matches exactly how it's stored
                                            $icon_id = 1012;
                                            break;
                                    }
                                    ?>
                                    <a class="social-link" href="<?= $url; ?>"><?= wp_get_attachment_image($icon_id, "full"); ?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
						</div> 
					</div>
					<div class="corner-logo">
						<?= wp_get_attachment_image(15, 'full');?>
					</div>
				</div>
			</div>
		</div>

	</div>
</section>
<?php
$current_post_id = get_the_ID();
$current_post_categories = wp_get_post_categories($current_post_id, array('fields' => 'ids'));
$blogposts = new WP_Query(
	array(
		'category__in'   => $current_post_categories,
		'post__not_in'   => array($current_post_id),
		'showposts'      => 9,
		'post_status'    => 'publish',
		'orderby'        => 'rand'
	)
);
if ($blogposts->have_posts()):
?>
<section id="blogPosts">
	<div class="container">
		<h2 class="subheading">
			Related Articles
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
<?php include get_stylesheet_directory() . '/cta-section.php'; ?>
<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();