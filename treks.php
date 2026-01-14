<?php
/**
 * Template Name: Treks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
get_header();
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
<?php
$intro = get_field('intro_text');
?>
<section id="trips">
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
               Our Treks
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
			$paragraphs = substr_count($intro, '</p>');

			// Extract the first paragraph
			$first_paragraph = nl2br(strip_tags(mb_substr($intro, 0, strpos($intro, '</p>') + 4)));
			?>

			<div class="content-truncated">
				<?= $first_paragraph; ?>
			</div>

			<?php if ($paragraphs > 1): ?>
				<div class="content-full" style="display: none;">
					<?= $intro; ?>
				</div>
				<button class="read-more-btn">Read More</button>
			<?php endif; ?>
		</div>
        <div class="trips-grid">
           <?php 
            $args = array(
                'post_type' => 'trek',
                'posts_per_page' => -1,
            );
            $trips = new WP_Query($args);
            if ($trips->have_posts()) : 
                while ($trips->have_posts()) : $trips->the_post(); 
					$intro = get_field('intro_g');
					$blurb = $intro['content'];
					$blurb = strip_tags($blurb);
					$blurb = mb_substr($blurb, 0, 122) . (strlen($blurb) > 122 ? '...' : '');
					$hero = get_field('hero_g');
					$destination_hero_image = $hero['image'];
                    ?>
                    <div class="trip-item country">
						<div class="trip-item-image">
							<div class="faux-bg-img">
								<?= wp_get_attachment_image($destination_hero_image, 'full'); ?>
							</div>
						</div>
						<div class="trip-meta">
							<div class="trip-title">
								<h3><?php the_title(); ?></h3>
							</div>
							<div class="trip-blurb">
								<?= $blurb ?>
							</div>
							<div class="button-row">
								<a class="trip-btn" href="<?= get_permalink(); ?>">View these Treks</a>
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
$content_r = get_field('content_r');
if( $content_r ): ?>
    <?php foreach( $content_r as $row ): ?>
    <section id="alternating-content">
        <div class="container">
            <h2 class="subheading">
                <?php echo esc_html($row['title']); ?>
            </h2>
            <div class="content-wysiwyg">
                <?php echo wp_kses_post($row['content']); ?>
            </div>
        </div>
    </section>
    <?php endforeach;
	endif; 
?>
<?php
$posts_g = get_field('posts_g');
$posts_title = $posts_g['title'];
$posts_content = $posts_g['content'];
$blogposts = new WP_Query(
	array(
		'showposts'      => 3,
		'post_status'    => 'publish',
		'category_name'  => 'treks',
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
</script>
<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action( 'generate_after_primary_content_area' );

get_footer();