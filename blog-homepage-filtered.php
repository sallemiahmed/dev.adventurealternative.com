<?php
/**
 * Template Name: Pre-filtered Blog Homepage
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
get_header(); 
$blog_homepage_id = 866;
$filter = get_field('filter');
$category = get_category($filter);
$category_name = $category ? $category->name : 'Blog';
$hero_g = get_field('hero_g', $blog_homepage_id);
$hero_title = $hero_g['title'];
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
		<div class="search-container">
			<h2 class="search-title">
				Looking for Something Specific?
			</h2>
			<div class="search-wrapper">
				<?php get_search_form(); ?>
			</div>
		</div>
	</div>
</section>
<?php
$submenu_items = get_field('submenu_g', $blog_homepage_id);
?>
<section id="mininav">
	<div class="container">
		<div class="nav-links">
			<a href="<?= get_permalink($blog_homepage_id); ?>#postGrid" class="nav-link extras smooth-scroll">
				<div class="link-text">
					All Posts
				</div>
			</a>
			<?php
			if (!empty($submenu_items)):
				foreach ($submenu_items as $submenu_item): 
					$item_label = $submenu_item['label'];
					$item_link = $submenu_item['page'];
					?>
					<a href="<?= get_permalink($item_link); ?>#postGrid" class="nav-link extras smooth-scroll">
						<div class="nav-icon">
							<?= wp_get_attachment_image($item_icon, 'full');?>
						</div>
						<div class="link-text">
							<?= $item_label; ?>
						</div>
					</a>
				<?php 
				endforeach;
			endif;
			?>
		</div>
	</div>
</section>
<section id="postGrid">
	<div class="container">
		<h2 class="subheading">
			Our <?= $category_name; ?> Posts
		</h2>
		<div class="post-grid">
            <?php
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => 16,
                'paged' => $paged,
                'cat' => $filter,
            );
            $query = new WP_Query($args);
            if ($query->have_posts()): 
                while ($query->have_posts()): $query->the_post(); ?>
                    <a class="grid-item post-item" href="<?php the_permalink(); ?>">
                        <div class="post-item-photo">
                            <?php if (has_post_thumbnail()): ?>
                                <div class="faux-bg-img">
                                    <?php the_post_thumbnail('medium-large'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3 class="grid-item-title">
                            <?php the_title(); ?>
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
                        <p class="grid-item-excerpt">
                            <?php echo $excerpt ?>
                        </p>
                        <div class="btn">Read More</div>
                    </a>
                <?php endwhile;
                wp_reset_postdata();
            else: ?>
                <p>No posts found.</p>
            <?php endif; ?>
        </div>
        <!-- Pagination -->
        <?php if (function_exists('wp_pagenavi')): ?>
            <div class="pagination">
                <?php wp_pagenavi(array('query' => $query)); ?>
            </div>
        <?php endif; ?>
	</div>
	<div class="page-divider">
	</div>
</section>
<?php
$country_specific_g = get_field('country_blogs_g', $blog_homepage_id);
$country_specific_title = $country_specific_g['title'];
$country_specific_content = $country_specific_g['content'];
$country_specific_links = $country_specific_g['links'];
?>
<section id="cta" class="country-specific-blog-links">
	<div class="container">
		<div class="cta-links">
			<h2 class="subheading">
				<?= $country_specific_title; ?>
			</h2>
			<div class="content">
				<?= $country_specific_content; ?>
			</div>
			<div class="button-row">
				<?php
				if (!empty($country_specific_links)):
					foreach ($country_specific_links as $link): 
						$link_text = $link['link_text'];
						$color = $link['button_color'];
						$textColor = $link['text_color'];
						$link_url = $link['page'];
						?>
						<a class="country-button" style="background:<?= $color; ?>; color:<?= $textColor; ?>" href="<?= get_permalink($link_url); ?>">
							<?= $link_text ?>
						</a>
					<?php 
					endforeach;
				endif;
				?>
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