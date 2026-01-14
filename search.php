<?php
/**
 * The template for displaying Search Results pages.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header(); ?>
<section id="hero">
    <div class="background-graphic">
        <?= wp_get_attachment_image(54, 'full'); ?>
    </div>
    <div class="container">
        <div class="hero-content">
            <h1 class="title">
                Search <i>Results</i>
            </h1>
            <div class="hero-text">
                <?php
                // Here we output something like "Here's what we found for [user's-search]."
                $search_query = get_search_query();
                echo "Here's what we found for <strong>" . esc_html( $search_query ) . "</strong>.";
                ?>
            </div>
        </div>
    </div>
</section>
    
<?php if ( have_posts() ) : ?>

    <div class="search-results-list">

        <?php while ( have_posts() ) : the_post(); ?>

            <div class="search-result-item">
                <?php
                // Check if the post has a Post Thumbnail assigned to it.
                if ( has_post_thumbnail() ) {
					echo '<div class="search-result-thumbnail-wrapper">';
                   	 the_post_thumbnail( 'thumbnail', array( 'class' => 'search-result-thumbnail' ) );
					echo '</div>';
                } else {
                    // Output a blank rectangle if there's no thumbnail
                    echo '<div class="search-result-thumbnail-wrapper"></div>';
                }
                ?>

                <a href="<?php the_permalink(); ?>" class="search-result-title"><?php the_title(); ?></a>
            </div>

        <?php endwhile; ?>

    </div>

<?php else : ?>
<section id="no-results">
	<div class="container">
		<p><?php _e( "Sorry, but we couldn't find anything matching those search terms. Please try again with some different keywords.", "textdomain" ); ?></p>
	</div>
</section>
<?php endif;

get_footer();
