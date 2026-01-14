<section id="cta">
    <div class="container">
        <div class="cta-content">
            <h2 class="subheading">
                Book Your Adventure of a Lifetime Now
            </h2>
            <div class="content">
                Here at Adventure Alternative we pride ourselves on making your adventure experience unforgettable. If you still can’t decide where to go, feel free to drop us a line, we would be happy to help you plan your perfect adventure.
            </div>
            <div class="button-row">
                <a class="cta-btn" href="/contact-us/">
                    Speak to an Expert
                </a>
            </div>
        </div>
        <div class="cta-links">
            <h2 class="subheading">
                Discover our trips to other Countries
            </h2>
            <div class="button-row">
                <?php
                $featured_countries = get_field('featured_countries', 59);
                $args = array(
                    'post_type' => 'country',
                    'post__in' => $featured_countries,
                    'posts_per_page' => -1,
                    'orderby' => 'post__in'
                );
                $countries_query = new WP_Query($args);

                if ($countries_query->have_posts()) :
                    $countries_to_skip = array("");
                    while ($countries_query->have_posts()) : $countries_query->the_post();
                        $current_country = html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8');
                        if (!in_array($current_country, $countries_to_skip)) {
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
                ?>
                            <a class="country-button" style="background:<?= $color; ?>; color:<?= $textColor; ?>" href="<?= get_permalink(); ?>">
                                Discover <?php the_title(); ?>
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