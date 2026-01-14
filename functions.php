<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// >>> ADDED BY DEJAN

// lets register our custom PHP classes, so that we can load them automatically
spl_autoload_register(function( $class ){
    
    $path = get_stylesheet_directory() . '/src/classes/' . $class . '.php';
    
    if( file_exists( $path ) )
        require_once $path;
    
});

// helper function for getting the current URL
if( ! function_exists('get_current_url') ){
    
    function get_current_url(){
        
        return ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        
    }
    
}

// check if product is configured as trip product - ADDED BY DEJAN
function is_trip_product( $product ){
    
    if( empty( $product ) )
        return false;
    
    if( is_numeric( $product ) )
        return has_term( 'trip-date', 'product_cat', $product );
    elseif( is_a( $product, 'WC_Product') )
        return has_term( 'trip-date', 'product_cat', $product->get_id() );
    else
        return false;
    
}

function aa_get_cart(){
    
    $wc = WC();
    
    if( empty( $wc->cart ) || ! $wc->cart instanceof WC_Cart ) {
    
        try {
            
            // Ensure dependencies are loaded in all contexts.
            include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
            include_once WC_ABSPATH . 'includes/wc-notice-functions.php';

            WC()->initialize_session();
            WC()->initialize_cart();
            
        } catch (Exception $e) {}
    
    }
    
    return $wc->cart ?? false;
    
}

function aa_get_cart_count(){
    
    $cart = aa_get_cart();
    
    return $cart ? $cart->get_cart_contents_count() : 0;
    
}

require_once __DIR__ . '/src/cart.php';
require_once __DIR__ . '/src/checkout.php';
require_once __DIR__ . '/src/stripe.php';
require_once __DIR__ . '/src/products.php';
require_once __DIR__ . '/src/orders.php';
require_once __DIR__ . '/src/order-items.php';
require_once __DIR__ . '/src/order-edit-page.php';
require_once __DIR__ . '/src/emails.php';
require_once __DIR__ . '/src/payment-fixes.php'; // Payment fixes - Added by Ahmed
require_once __DIR__ . '/src/aa-checkout-fixes-v5.php'; // Debug panel v6 - Added by Ahmed

// <<< ADDED BY DEJAN

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

// END ENQUEUE PARENT ACTION

function my_custom_secondary_nav() {
    ?>
    <div class="secondary-nav">
        <div class="sus-claimer">
            <?= wp_get_attachment_image(29, 'thumbnail'); ?>
            <span>The Responsible Adventure Travel Company</span>

            <!-- CHANGED: make it a button-style link with ARIA -->
            <a href="#aboutus-mobile-submenu"
               class="button mobile-only"
               role="button"
               aria-expanded="false"
               aria-controls="aboutus-mobile-submenu"
               aria-label="Open About Us menu">
               &gt;
            </a>

            <div id="strip-menu" class="main-nav">
                <!-- (unchanged) -->
                <ul id="menu-main-nav" class=" menu sf-menu">
                    <li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-has-children menu-item-28">
                        <a href="/about-us/">About Us<span role="presentation" class="dropdown-menu-toggle"><span class="gp-icon icon-arrow"><svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"><path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path></svg></span></span></a>
                        <ul class="sub-menu">
                            <li id="menu-item-6939" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-6939"><a href="/about-us/">Company</a></li>
                            <li id="menu-item-6939" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-6939"><a href="https://www.adventurealternative.com/trip-leaders/">Trip Leaders</a></li>
                            <li id="menu-item-27" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-27"><a href="/reviews/">Reviews</a></li>
                            <li id="menu-item-6940" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-6940"><a href="https://www.adventurealternative.com/sustainable-tourism/">Sustainable tourism</a></li>
                            <li id="menu-item-6941" class="menu-item menu-item-type-custom menu-item-object-custom menu-item-6941"><a href="https://www.adventurealternative.com/blog/">Adventure Blog</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="contact">
            <a class="phoneNumber" href="tel:02870831258">028 7083 1258</a>
            <div class="socials">
                <a href="https://www.instagram.com/adventurealternative" class="social-icon"><?= wp_get_attachment_image(306, 'thumbnail') ?></a>
                <a href="https://www.facebook.com/adventurealternative" class="social-icon"><?= wp_get_attachment_image(307, 'thumbnail') ?></a>
            </div>
        </div>

        <div class="right-section">
            <div class="search-wrapper">
                <?php get_search_form(); ?>
            </div>
            <a href="/account/"><?= wp_get_attachment_image(31, 'thumbnail'); ?></a>
        </div>
    </div>
    <?php
}
add_action( 'generate_before_header', 'my_custom_secondary_nav', 10 );

add_action('wp_footer', function () {
    ?>
    <script>
    (function () {
        var trigger = document.querySelector('.sus-claimer .mobile-only');
        if (!trigger) return;

        var aboutUsSub = document.querySelector('#strip-menu .menu-item-has-children > .sub-menu');
        if (!aboutUsSub) return;

        // Create the mobile panel once
        var panel = document.createElement('div');
        panel.id = 'aboutus-mobile-submenu';
        panel.className = 'aboutus-mobile-submenu';
        panel.setAttribute('hidden', '');
        panel.setAttribute('aria-hidden', 'true');

        // Clone submenu so desktop stays intact
        var clone = aboutUsSub.cloneNode(true);

        // Remove duplicate IDs from cloned items
        clone.querySelectorAll('[id]').forEach(function (el) {
            el.removeAttribute('id');
        });

        // Optional: add a simple heading for screen readers
        var srOnly = document.createElement('span');
        srOnly.className = 'sr-only';
        srOnly.textContent = 'About Us menu';
        panel.appendChild(srOnly);

        panel.appendChild(clone);

        // Insert panel under the header strip (under the sus-claimer block)
        var container = document.querySelector('.sus-claimer');
        if (!container) container = document.querySelector('.secondary-nav');
        if (container) container.parentNode.insertBefore(panel, container.nextSibling);

        // Helpers
        function openPanel() {
            panel.removeAttribute('hidden');
            panel.setAttribute('aria-hidden', 'false');
            trigger.setAttribute('aria-expanded', 'true');
            document.addEventListener('click', onDocClick, { capture: true });
            document.addEventListener('keydown', onKeyDown, { capture: true });
        }
        function closePanel() {
            panel.setAttribute('hidden', '');
            panel.setAttribute('aria-hidden', 'true');
            trigger.setAttribute('aria-expanded', 'false');
            document.removeEventListener('click', onDocClick, { capture: true });
            document.removeEventListener('keydown', onKeyDown, { capture: true });
        }
        function togglePanel(e) {
            // Stop following the link
            if (e) e.preventDefault();
            var isOpen = trigger.getAttribute('aria-expanded') === 'true';
            if (isOpen) closePanel(); else openPanel();
        }
        function onDocClick(e) {
            if (panel.contains(e.target) || trigger.contains(e.target)) return;
            closePanel();
        }
        function onKeyDown(e) {
            if (e.key === 'Escape') closePanel();
        }

        // Make the link behave like a button for a11y
        trigger.addEventListener('click', togglePanel);
        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePanel();
            }
        });

        // Prevent default navigation (since it has an href)
        trigger.addEventListener('mousedown', function (e) {
            // Just in case—avoid focus loss causing accidental navigation
            e.preventDefault();
        });

    })();
    </script>
    <?php
}, 100);


function loadup_scripts(){
    wp_enqueue_script( 'owlcarousel', get_stylesheet_directory_uri() . '/owlcarousel/owl.carousel.min.js', array( 'jquery' ), false, true );
    wp_enqueue_style( 'owlcarousel-style', get_stylesheet_directory_uri() . '/owlcarousel/assets/owl.carousel.min.css' );
}

add_action( 'wp_enqueue_scripts', 'loadup_scripts' );

function filter_trips_callback() {
    $category =  isset($_POST['category']) ? $_POST['category'] : '';;

    $args = array(
        'post_type' => 'trip',
        'posts_per_page' => 15
    );

    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'trip-category',
                'field' => 'slug',
                'terms' => $category
            ),
        );
    }

    $query = new WP_Query($args);
    if ($query->have_posts()) :
        while ($query->have_posts()) : $query->the_post();
            $blurb = get_field('blurb');
            $days = get_field('days');
            $altitude = get_field('altitude');
            $distance = get_field('distance');
            $challenge = get_field('challenge');
            $price_from = get_field('price_from');
            ?>
            <div class="trip-item">
                <div class="trip-item-image">
                    <div class="faux-bg-img">
                    <?php the_post_thumbnail('full'); ?>
                    </div>
                </div>
            <div class="trip-meta">
                <div class="trip-title">
                    <h3><?php the_title(); ?></h3>
                </div>
                <div class="trip-blurb">
                    <?= $blurb ?>
                </div>
                <div class="trip-table">
                    <table>
                        <?php if (!empty($days)) : ?>
                            <tr>
                                <td><span class="trip-meta-icon"><?= wp_get_attachment_image(175, 'full')?></span> Days</td>
                                <td><?= $days ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($altitude)) : ?>
                            <tr>
                                <td><span class="trip-meta-icon"><?= wp_get_attachment_image(173, 'full')?></span> Altitude</td>
                                <td><?= $altitude ?> m</td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($distance)) : ?>
                            <tr>
                                <td><span class="trip-meta-icon"><?= wp_get_attachment_image(176, 'full')?></span> Distance</td>
                                <td><?= $distance ?> km</td>
                            </tr>
                        <?php endif; ?>

                        <?php if (!empty($challenge)) : ?>
                            <tr>
                                <td><span class="trip-meta-icon"><?= wp_get_attachment_image(174, 'full')?></span> Challenge</td>
                                <td><?= $challenge ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
                <div class="price-row">
                    <p>
                        <?= (preg_match('/\d/', $price_from) && !empty($price_from)) ? "Trip Price  $price_from" : "Enquire for Details" ?>
                    </p>
                </div>
                <div class="button-row">
                    <a class="trip-btn" href="<?= get_permalink(); ?>">View this Trip</a>
                </div>
            </div>
        </div>
    <?php 
        endwhile;
        wp_reset_postdata();
    endif;

    die();
}

add_action('wp_ajax_filter_trips', 'filter_trips_callback');
add_action('wp_ajax_nopriv_filter_trips', 'filter_trips_callback');

function register_my_footer_menu() {
    register_nav_menus(
        array(
            'footer-menu' => __( 'Footer Menu' ),
        )
    );
}
add_action( 'init', 'register_my_footer_menu' );

function filter_reviews_ajax() {
    $category = isset($_POST['category']) ? $_POST['category'] : '';

    $args = array(
        'post_type' => 'testimonial',
        'posts_per_page' => -1,
    );

    if (!empty($category)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'testimonial-category',
                'field' => 'slug',
                'terms' => $category,
            ),
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $reviewer_img = get_field('reviewer_image');
            $reviewer_name = get_field('reviewer_name');
            $reviewer_destination = get_field('reviewer_destination');
            $stars = get_field('stars');
            $full_stars = floor($stars);
            $half_stars = ceil($stars - $full_stars);
            $empty_stars = 5 - $full_stars - $half_stars;
            $review_content = get_field('review_content');
            ?>
            <div class="review-item" data-category="<?php echo get_the_terms(get_the_ID(), 'testimonial-category')[0]->slug; ?>">
                <div class="review-meta">
                    <div class="reviewer">
                        <?php if($reviewer_img): ?>
                            <div class="reviewer-img">
                                <div class="faux-bg-img">
                                    <?= wp_get_attachment_image($reviewer_img, 'thumbnail'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="reviewer-details">
                            <div class="reviewer-name">
                                <?= $reviewer_name; ?>
                            </div>
                            <?php if($reviewer_destination): ?>
                                <div class="reviewer-destination">
                                    <?= $reviewer_destination; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($stars != 0): ?>
                        <span class="reviewer-stars">
                            <?php
                            for ($i=0; $i < $full_stars; $i++) { 
                                echo '<img src="/wp-content/uploads/2024/02/Star.svg" alt="star">';
                            }
                            if( $half_stars ){
                                echo '<img src="/wp-content/uploads/2024/02/HalfStar.svg" alt="star">';
                            }
                            for ($i=0; $i < $empty_stars; $i++) { 
                                echo '<img src="/wp-content/uploads/2024/02/EmptyStar.svg" alt="star">';
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="review-content">
                    <?php
                    $maxLength = 600;
                    if (strlen($review_content) > $maxLength) {
                        $displayContent = substr($review_content, 0, $maxLength) . '...';
                        echo '<div class="short-content">' . $displayContent . '</div>';
                        echo '<div class="full-content hidden">' . $review_content . '</div>';
                        echo '<button class="read-more-btn">Read More</button>';
                    } else {
                        echo $review_content;
                    }
                    ?>
                </div>
            </div>
            <?php 
        }
    } else {
        echo '<p>No reviews found.</p>';
    }

    wp_reset_postdata();
    wp_die(); // This is required to terminate immediately and return a proper response
}

add_action('wp_ajax_filter_reviews', 'filter_reviews_ajax');
add_action('wp_ajax_nopriv_filter_reviews', 'filter_reviews_ajax');

function filter_trips_ajax() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ajax_nonce')) {
        wp_die('Nonce validation failed');
    }

    $trip_type = isset($_POST['trip_type']) ? sanitize_text_field($_POST['trip_type']) : '';
    $current_country_id = isset($_POST['current_country_id']) ? absint($_POST['current_country_id']) : 0;

    $args = array(
        'post_type' => 'trip',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'country',
                'value' => $current_country_id,
                'compare' => '=',
            ),
        ),
    );

    if (!empty($trip_type)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'trip-type',
                'field' => 'slug',
                'terms' => $trip_type,
            ),
        );
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()):
            $query->the_post();
            $blurb = get_field('blurb');
            $days = get_field('days');
            $altitude = get_field('altitude');
            $distance = get_field('distance');
            $challenge = get_field('challenge');
            $price_from = get_field('price_from');
            ?>
            <div class="trip-item" data-category="<?php echo get_the_terms(get_the_ID(), 'trip-category')[0]->slug; ?>">
                <div class="trip-item-image">
                    <div class="faux-bg-img">
                        <?php the_post_thumbnail('full'); ?>
                    </div>
                </div>
                <div class="trip-meta">
                    <div class="trip-title">
                        <h3><?php the_title(); ?></h3>
                    </div>
                    <div class="trip-blurb">
                        <?= $blurb ?>
                    </div>
                    <div class="trip-table">
                        <table>
                            <tr><td><span class="trip-meta-icon"><?= wp_get_attachment_image(175, 'full')?></span> Days</td><td><?= $days ?></td></tr>
                            <tr><td><span class="trip-meta-icon"><?= wp_get_attachment_image(173, 'full')?></span> Altitude</td><td><?= $altitude ?> m</td></tr>
                            <tr><td><span class="trip-meta-icon"><?= wp_get_attachment_image(176, 'full')?></span> Distance</td><td><?= $distance ?> km</td></tr>
                            <tr><td><span class="trip-meta-icon"><?= wp_get_attachment_image(174, 'full')?></span> Challenge</td><td><?= $challenge ?></td></tr>
                        </table>
                    </div>
                    <div class="price-row">
                        <p>
                            <?= (preg_match('/\d/', $price_from) && !empty($price_from)) ? "Trip Price  $price_from" : "Enquire for Details" ?>
                        </p>
                    </div>
                    <div class="button-row">
                        <a class="trip-btn" href="<?= get_permalink(); ?>">View this Trip</a>
                    </div>
                </div>
            </div>
            <?php 
        endwhile;
    } else {
        echo '<p>No trips found.</p>';
    }

    wp_reset_postdata();
    wp_die();
}

add_action('wp_ajax_filter_trips_by_country_and_type', 'filter_trips_ajax');
add_action('wp_ajax_nopriv_filter_trips_by_country_and_type', 'filter_trips_ajax');

// Handle AJAX request to update WooCommerce user details
function update_woocommerce_user_details() {
    check_ajax_referer('update_woocommerce_user_details_nonce', '_ajax_nonce');

    $current_user = wp_get_current_user();
    if (isset($_POST['billing_first_name'])) {
        update_user_meta($current_user->ID, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        update_user_meta($current_user->ID, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
        update_user_meta($current_user->ID, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
        update_user_meta($current_user->ID, 'billing_address_2', sanitize_text_field($_POST['billing_address_2']));
        update_user_meta($current_user->ID, 'billing_city', sanitize_text_field($_POST['billing_city']));
        update_user_meta($current_user->ID, 'billing_postcode', sanitize_text_field($_POST['billing_postcode']));
        update_user_meta($current_user->ID, 'billing_country', sanitize_text_field($_POST['billing_country']));
        update_user_meta($current_user->ID, 'billing_state', sanitize_text_field($_POST['billing_state']));
        update_user_meta($current_user->ID, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
        
        // Update custom fields
        update_user_meta($current_user->ID, 'dob', sanitize_text_field($_POST['dob']));
        update_user_meta($current_user->ID, 'gender', sanitize_text_field($_POST['gender']));

        // Update user email
        if (!empty($_POST['billing_email']) && is_email($_POST['billing_email'])) {
            wp_update_user(array(
                'ID' => $current_user->ID,
                'user_email' => sanitize_email($_POST['billing_email']),
            ));
        }
        
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_update_woocommerce_user_details', 'update_woocommerce_user_details');


// Add custom fields to user profile
function add_extra_user_profile_fields($user) {
    ?>
    <h3><?php _e('Additional Information', 'textdomain'); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="dob"><?php _e('Date of Birth', 'textdomain'); ?></label></th>
            <td>
                <input type="date" name="dob" id="dob" value="<?php echo esc_attr(get_user_meta($user->ID, 'dob', true)); ?>" class="regular-text" /><br />
                <span class="description"><?php _e('Please enter your date of birth.', 'textdomain'); ?></span>
            </td>
        </tr>
        <tr>
            <th><label for="gender"><?php _e('Gender', 'textdomain'); ?></label></th>
            <td>
                <select name="gender" id="gender">
                    <option value="male" <?php selected(get_user_meta($user->ID, 'gender', true), 'male'); ?>><?php _e('Male', 'textdomain'); ?></option>
                    <option value="female" <?php selected(get_user_meta($user->ID, 'gender', true), 'female'); ?>><?php _e('Female', 'textdomain'); ?></option>
                </select><br />
                <span class="description"><?php _e('Please select your gender.', 'textdomain'); ?></span>
            </td>
        </tr>
    </table>
    <?php
}

// Function to get current cart item quantities
function get_cart_item_quantities() {
    $quantities = array();
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $quantities[$product_id] = $cart_item['quantity'];
    }
    return $quantities;
}

// Output cart item quantities as JavaScript variable
function output_cart_item_quantities() {
    // Only output on relevant pages, for example, the checkout page
    if (is_page('checkout')) {  // Adjust this condition as needed
        $quantities = get_cart_item_quantities();
        ?>
        <script type="text/javascript">
            var cartItemQuantities = <?php echo json_encode($quantities); ?>;
        </script>
        <?php
    }
}
add_action('wp_footer', 'output_cart_item_quantities');


add_action('show_user_profile', 'add_extra_user_profile_fields');
add_action('edit_user_profile', 'add_extra_user_profile_fields');

// Save custom fields in user profile
function save_extra_user_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta($user_id, 'dob', sanitize_text_field($_POST['dob']));
    update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
}

add_action('personal_options_update', 'save_extra_user_profile_fields');
add_action('edit_user_profile_update', 'save_extra_user_profile_fields');

// Handle custom checkout form submission
function handle_custom_checkout() {
    
    // MODIFIED BY DEJAN - we are adding $_POST['woocommerce_pay'] check, because that variable is set only on additional payment checkout pages, on which we don't want to run this code.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty( $_POST['woocommerce_pay'] ) && (isset($_POST['step']) || isset($_POST['target_step']))) {
        
        $current_step = isset($_POST['target_step']) ? intval($_POST['target_step']) : intval($_POST['step']);
        
        // when saving data, we need to use previous step num - ADDED BY DEJAN
        $data_step = isset($_POST['target_step']) ? 0 : max( 0, $current_step - 1 );
        
        
        
        // MOVED UP TO THIS LINE AS PRIORITY BECAUSE SESSION DATA IS CLEARED IF WE NEED TO LOGIN USER ON THIS STEP
        if ($current_step == 2) {
            // Step 1 completed
            
            if (!is_user_logged_in()) {
                // Create user if not logged in
                $billing_email = sanitize_email($_POST['billing_email']);
                $password = sanitize_text_field($_POST['password']);
                $user_id = wp_create_user($billing_email, $password, $billing_email);
                if (is_wp_error($user_id)) {
                    wp_die('Error creating user: ' . $user_id->get_error_message());
                }
                
                $user = wp_signon([
                    'user_login'    => $billing_email,
                    'user_password' => $password,
                    'remember'      => true
                ]);
                
                if (is_wp_error($user)) {
                    wp_die('Login failed: ' . $user->get_error_message());
                }
                
                wp_set_current_user($user_id);
                //wp_set_auth_cookie($user_id);
                
                WC()->session->init_session_cookie();
                
                // Save user meta
                update_user_meta($user_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
                update_user_meta($user_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
                update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
                update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
                update_user_meta($user_id, 'billing_address_2', sanitize_text_field($_POST['billing_address_2']));
                update_user_meta($user_id, 'billing_city', sanitize_text_field($_POST['billing_city']));
                update_user_meta($user_id, 'billing_postcode', sanitize_text_field($_POST['billing_postcode']));
                update_user_meta($user_id, 'billing_country', sanitize_text_field($_POST['billing_country']));
                update_user_meta($user_id, 'billing_state', sanitize_text_field($_POST['billing_state']));
                update_user_meta($user_id, 'dob', sanitize_text_field($_POST['dob']));
                update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
                update_user_meta($user_id, 'passport_number', sanitize_text_field($_POST['passport_number']));
            } else {
                // Update user meta if logged in
                $user_id = get_current_user_id();
                update_user_meta($user_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
                update_user_meta($user_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
                update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
                update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
                update_user_meta($user_id, 'billing_address_2', sanitize_text_field($_POST['billing_address_2']));
                update_user_meta($user_id, 'billing_city', sanitize_text_field($_POST['billing_city']));
                update_user_meta($user_id, 'billing_postcode', sanitize_text_field($_POST['billing_postcode']));
                update_user_meta($user_id, 'billing_country', sanitize_text_field($_POST['billing_country']));
                update_user_meta($user_id, 'billing_state', sanitize_text_field($_POST['billing_state']));
                update_user_meta($user_id, 'dob', sanitize_text_field($_POST['dob']));
                update_user_meta($user_id, 'gender', sanitize_text_field($_POST['gender']));
            }
               
            if( isset( $_POST['num_passengers'] ) )
                AA_Session::set_num_passengers( (int) $_POST['num_passengers'] );
            
            // ensure that we save all billing fields to WC session - ADDED BY DEJAN
            foreach( ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone' ] as $wc_billing_key ){
                
                $checkout_field_name = "billing_{$wc_billing_key}";
                
                if( ! isset( $$checkout_field_name ) )
                    continue;
                
                if( $$checkout_field_name != WC()->session->get( $checkout_field_name, '' ) )
                    WC()->session->set( $checkout_field_name, $$checkout_field_name );
                
            }
            
        }
        
        AA_Session::set( 'checkout_step', $current_step );
        AA_Session::set( 'data_step', $data_step );
        
        // Remove the first (empty) entry from passenger details arrays
        if (isset($_POST['passenger_first_name']) && is_array($_POST['passenger_first_name'])) {
            $_POST['passenger_first_name'] = array_slice($_POST['passenger_first_name'], 1);
            $_POST['passenger_last_name'] = array_slice($_POST['passenger_last_name'], 1);
            $_POST['passenger_gender'] = array_slice($_POST['passenger_gender'], 1);
            $_POST['passenger_dob'] = array_slice($_POST['passenger_dob'], 1);
            $_POST['passenger_email'] = array_slice($_POST['passenger_email'], 1);
        }

        // Save form data to session - MODIFIED BY DEJAN: Do not save to session if $_POST['target_step'] is defined
        if( empty( $_POST['target_step'] ) && ! empty( $data_step ) && is_numeric( $data_step ) )
            AA_Session::set( "steps_data.{$data_step}", $_POST ); // MODIFIED BY DEJAN: use $data_step instead of $current_step
        
        if ($current_step == 3) {
            // Step 2 completed, handle payment details
            
            // MODIFIED BY DEJAN: This code is not needed here. 
            /*if (isset($_POST['payment_option'])) {
                AA_Session::set( "steps_data.3.payment_option", sanitize_text_field($_POST['payment_option']) ); // MODIFIED BY DEJAN
            }
            if (isset($_POST['custom_payment_amount'])) {
                AA_Session::set( "steps_data.3.custom_payment_amount", sanitize_text_field($_POST['custom_payment_amount']) ); // MODIFIED BY DEJAN
            }*/
            
            // Ensure payment details are provided before proceeding - MODIFIED BY DEJAN: This check is done after checkout is submitted
            /*if ($_POST['payment_option'] == 'pay_custom' && empty($_POST['custom_payment_amount'])){
                wp_redirect('/checkout?step=3');
                exit;
            }*/

            // Validate payment option - MODIFIED BY DEJAN - not needed anymore here
            //if ($_POST['payment_option'] == 'pay_deposit' || $_POST['payment_option'] == 'pay_custom' || $_POST['payment_option'] == 'pay_full') {
                // Proceed to WooCommerce payment processing
                //WC()->checkout()->process_checkout();
            //}
        } elseif ($current_step == 4) { // MODIFIED BY DEJAN - payment is automatically processed after submitting step 3 and from there redirected to thank you page, so most of the old logic blocks from here are not needed anymore
            // Step 3 completed, handle confirmation (custom logic needed)
            // Ensure payment has been processed
            
            // >>> if we are submitting from step 3 on checkout, lets put to $_POST all data we collected on checkout, so that WC can process all fields
            
            $merged_data = [];
            
            foreach( AA_Session::get( 'steps_data', [] ) as $step_data ){
                $merged_data = array_merge($merged_data, $step_data);
            }
            
            foreach( $merged_data as $key => $value ){
                
                if( ! isset( $_POST[ $key ] ) )
                    $_POST[ $key ] = $value;
            
            }
            
            // <<< if we are submitting from step 3 on checkout, lets put to $_POST all data we collected on checkout, so that WC can process all fields
            
            // DISABLED BY DEJAN - this is not needed here
            /*if (!isset($_POST['payment_option']) || !isset($_SESSION[3]['payment_option'])) {
                wp_redirect('/checkout?step=3');
                exit;
            }*/

            // Here we would normally handle the payment processing using WooCommerce functions

            // For now, assume payment is processed and create the order

            // Redirect to a thank you or order confirmation page - DISABLED BY DEJAN - this is not needed here
            /*wp_redirect('/order-confirmation');
            exit;*/
        }

        // Redirect to the current step to handle form resubmission issues - MODIFIED BY DEJAN: I don't think this code is needed
        /*if( is_numeric( $current_step ) && $current_step < 4 ){
            
            AA_Session::set( 'checkout_step', $current_step ); // MODIFIED BY DEJAN
            wp_redirect('/checkout');
            exit;
            
        }*/
    }
}
add_action('woocommerce_init', 'handle_custom_checkout'); // MODIFIED BY DEJAN - template_redirect hook is "too late". So we need to use "woocommerce_init" hook instead, because it runs earlier. That is useful so that we can prepare all data before WC starts processing page


// Save passenger details to order meta - MODIFIED BY DEJAN: This is not needed, because all checkout data is processed in /src/checkout.php
/*function save_passenger_details_to_order($order_id, $posted_data) {
    
    $steps_data = AA_Session::get('steps_data'); // MODIFIED BY DEJAN
    
    if (isset($steps_data)) {
        $merged_data = array_merge(...array_values($steps_data));

        $num_passengers = intval($merged_data['num_passengers']);
        $passenger_details = [];
        for ($i = 0; $i < $num_passengers; $i++) {
            $passenger_details[] = [
                'first_name' => sanitize_text_field($merged_data['passenger_first_name'][$i]),
                'last_name' => sanitize_text_field($merged_data['passenger_last_name'][$i]),
                'gender' => sanitize_text_field($merged_data['passenger_gender'][$i]),
                'dob' => sanitize_text_field($merged_data['passenger_dob'][$i]),
                'email' => sanitize_email($merged_data['passenger_email'][$i]),
            ];
        }
        update_post_meta($order_id, '_passenger_details', $passenger_details);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'save_passenger_details_to_order', 10, 2);*/

function display_linked_extras_by_category($category_slug) {
    // Get itinerary IDs from products in the cart
    $itinerary_ids = array();
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $itinerary_id = get_field('itinerary', $product_id);
        if ($itinerary_id) {
            $itinerary_ids[] = $itinerary_id;
        }
    }

    if (empty($itinerary_ids)) {
        echo '<p>No extras available for your trip.</p>';
        return;
    }

    // Fetch extras based on serialized itinerary IDs
    $meta_query = array(
        'relation' => 'OR',
    );
    foreach ($itinerary_ids as $id) {
        $meta_query[] = array(
            'key' => 'relevant_trips',
            'value' => '"' . $id . '"',
            'compare' => 'LIKE',
        );
    }

    // Fetch extras based on itinerary IDs
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        ),
        'meta_query' => $meta_query,
    );

    $products = new WP_Query($args);

    if ($products->have_posts()) :
        echo '<ul class="products-list">';
        while ($products->have_posts()) : $products->the_post();
            $product = wc_get_product(get_the_ID());
            ?>
            <li class="product-item">
                <div class="product-image">
                    <?php echo $product->get_image(); ?>
                </div>
                <div class="product-details">
                    <h3 class="product-title"><?php echo $product->get_name(); ?></h3>
                    <p class="product-price"><?php echo $product->get_price_html(); ?></p>
                    <p class="product-description"><?php echo $product->get_description(); ?></p>
                    <div class="add-to-cart-controls">
                        <?php if ($category_slug == 'equipment-rental') : ?>
                            <button type="button" class="minus-button" data-product-id="<?php echo $product->get_id(); ?>">-</button>
                            <input type="number" class="product-quantity" data-product-id="<?php echo $product->get_id(); ?>" value="0" readonly>
                            <button type="button" class="plus-button" data-product-id="<?php echo $product->get_id(); ?>">+</button>
                        <?php else : ?>
                            <button type="button" class="toggle-cart-button" data-product-id="<?php echo $product->get_id(); ?>">Add</button>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php
        endwhile;
        echo '</ul>';
    else :
        echo '<p>No extras of this type are available for your selected trip. Try another tab or continue to payment.</p>';
    endif;

    wp_reset_postdata();
}


// Handle product removal from cart
function handle_remove_product_from_cart() {
    if (isset($_POST['remove_product']) && isset($_POST['cart_item_key'])) {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        WC()->cart->remove_cart_item($cart_item_key);

        // Redirect to the same page to avoid resubmission
        wp_redirect(wp_get_referer());
        exit;
    }
}
add_action('template_redirect', 'handle_remove_product_from_cart');

add_action('wp_ajax_custom_remove_from_cart', 'custom_remove_from_cart');
add_action('wp_ajax_nopriv_custom_remove_from_cart', 'custom_remove_from_cart');

function custom_remove_from_cart() {
    // Check the nonce
    check_ajax_referer('remove_from_cart_nonce', 'security');

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $cart = WC()->cart->get_cart();

    if (WC()->cart->remove_cart_item($cart_item_key)) {
        wp_send_json_success(array(
            'cart_item_key' => $cart_item_key,
            'cart_count' => aa_get_cart_count()
        ));
    } else {
        wp_send_json_error(array(
            'error' => 'Could not remove product from cart.'
        ));
    }

    wp_die();
}

add_action('wp_ajax_custom_remove_one_from_cart', 'custom_remove_one_from_cart');
add_action('wp_ajax_nopriv_custom_remove_one_from_cart', 'custom_remove_one_from_cart');

function custom_remove_one_from_cart() {
    // Check the nonce
    check_ajax_referer('remove_from_cart_nonce', 'security');

    $product_id = absint($_POST['product_id']);
    $cart = WC()->cart->get_cart();

    foreach ($cart as $cart_item_key => $cart_item) {
        if ($cart_item['product_id'] == $product_id) {
            // Reduce the quantity by 1
            $new_quantity = $cart_item['quantity'] - 1;
            if ($new_quantity > 0) {
                WC()->cart->set_quantity($cart_item_key, $new_quantity);
            } else {
                WC()->cart->remove_cart_item($cart_item_key);
            }
            wp_send_json_success(array(
                'product_id' => $product_id,
                'cart_count' => aa_get_cart_count()
            ));
        }
    }

    wp_send_json_error(array(
        'error' => 'Could not remove product from cart.'
    ));

    wp_die();
}

/*add_action('wp_ajax_update_passenger_count', 'update_passenger_count');
add_action('wp_ajax_nopriv_update_passenger_count', 'update_passenger_count');

function update_passenger_count() {
    check_ajax_referer('update_cart_nonce', 'security');

    $num_passengers = absint($_POST['num_passengers']);

    // Update the session data for step 1 with the number of passengers - MODIFIED BY DEJAN
    if ( AA_Session::get('steps_data.1') === null ) {
        AA_Session::set( 'steps_data.1', [] );
    }
    AA_Session::set( 'steps_data.1.num_passengers', $num_passengers );

    wp_send_json_success(['num_passengers' => $num_passengers]);

    wp_die();
}*/

add_action('wp_ajax_update_basket', 'update_basket');
add_action('wp_ajax_nopriv_update_basket', 'update_basket');

function update_basket() {
    ob_start();
    include get_stylesheet_directory() . '/src/templates/basket.php';
    $basket_html = ob_get_clean();

    wp_send_json_success(array(
        'basket_html' => $basket_html,
        'basket_count' => aa_get_cart_count() // ADDED BY DEJAN
    ));
    wp_die();
}

function my_enqueue_scripts() {
    // Ensure jQuery is enqueued
    wp_enqueue_script('jquery');

    // Enqueue WooCommerce scripts
    wp_enqueue_script('woocommerce');
    wp_enqueue_script('wc-checkout');

    // Enqueue WooCommerce styles
    wp_enqueue_style('woocommerce-general');
    wp_enqueue_style('woocommerce-layout');
    wp_enqueue_style('woocommerce-smallscreen');

    // Localize WooCommerce script for use in your JavaScript
    wp_localize_script('woocommerce', 'wc_add_to_cart_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'add_to_cart_nonce' => wp_create_nonce('add_to_cart_nonce'),
        'remove_from_cart_nonce' => wp_create_nonce('remove_from_cart_nonce'),
        'update_cart_nonce' => wp_create_nonce('update_cart_nonce'),
    ));

}
add_action('wp_enqueue_scripts', 'my_enqueue_scripts');

add_action('wp_ajax_update_trip_quantity', 'update_trip_quantity');
add_action('wp_ajax_nopriv_update_trip_quantity', 'update_trip_quantity');

function update_trip_quantity() {
    check_ajax_referer('update_cart_nonce', 'security');

    $product_id = absint($_POST['product_id']);
    $quantity = absint($_POST['quantity']);
    $cart = WC()->cart->get_cart();

    foreach ($cart as $cart_item_key => $cart_item) {
        
        if ($cart_item['product_id'] == $product_id) {
            
            WC()->cart->set_quantity($cart_item_key, $quantity);
            
            AA_Session::set_num_passengers( $quantity - 1 );
            
            wp_send_json_success(array(
                'product_id' => $product_id,
                'quantity' => $quantity,
                'cart_count' => aa_get_cart_count()
            ));
        }
    }

    wp_send_json_error(array(
        'error' => 'Could not update trip quantity in cart.'
    ));

    wp_die();
}

add_action('wp_ajax_checkout_passengers_changed', 'checkout_passengers_changed');
add_action('wp_ajax_nopriv_checkout_passengers_changed', 'checkout_passengers_changed');

function checkout_passengers_changed() {
    check_ajax_referer('update_cart_nonce', 'security');

    $product_id = absint($_POST['product_id'] ?? 0);
    $quantity = absint($_POST['quantity'] ?? 0);
    $num_passengers = absint($_POST['num_passengers'] ?? 0);
    
    $cart = WC()->cart->get_cart();
    
    if( ! empty( $product_id ) ){
    
        foreach ($cart as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                WC()->cart->set_quantity($cart_item_key, $quantity);
            }
        }
        
    }

    AA_Session::set_num_passengers( $num_passengers );
    
    ob_start();
    include get_stylesheet_directory() . '/src/templates/basket.php';
    $basket_html = ob_get_clean();

    wp_send_json_success(array(
        'basket_html' => $basket_html,
        'basket_count' => aa_get_cart_count() // ADDED BY DEJAN
    ));
    
    wp_die();
    
}

function get_cart_trip_product_id() {
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        // Assuming that the trip products have a specific category or tag to identify them
        if (has_term('trip-date', 'product_cat', $product_id)) {
            return $product_id;
        }
    }
    return null;
}

function maybe_fix_cart_trip_product_quantity( $add_notice = true ) {
    
    $fixes = 0;
            
    $num_passengers = AA_Session::get_num_passengers();
            
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        
        $product_id = $cart_item['product_id'];
        
        if (has_term('trip-date', 'product_cat', $product_id)) {
            
            if( $num_passengers + 1 != $cart_item['quantity'] ){
                WC()->cart->set_quantity( $cart_item_key, $num_passengers + 1 );
                $fixes++;
            }
            
        }
        
    }
    
    if( $fixes > 0 && $add_notice )
        wc_add_notice( 'The cart total has been updated. Please review it before submitting your booking.', 'notice' );
    
    return $fixes;
    
}

// Redirect to Step 4 (Confirmation) after payment
// MODIFIED BY DEJAN - this is not needed anymore because checkout.php file is configured to show thank you page as step 4
/*add_action('woocommerce_thankyou', 'redirect_to_confirmation_page', 10, 1);
function redirect_to_confirmation_page($order_id) {
    wp_redirect('/checkout?step=4');
    exit;
}*/

// MODIFIED BY DEJAN - I don't think this debug code is needed anymore
/*add_action('template_redirect', 'debug_available_payment_gateways');
function debug_available_payment_gateways() {
    if (is_page_template('checkout.php')) {
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        error_log(print_r($available_gateways, true));
    }
}*/

// Add passenger details within the General order_data_column on the WooCommerce order edit page
add_action('woocommerce_admin_order_data_after_order_details', 'display_passenger_details_in_order_editor', 10, 1);

function display_passenger_details_in_order_editor($order) {
    $checkout_data = get_post_meta($order->get_id(), 'aa_checkout_data', true);
    $passenger_details = $checkout_data['passenger_details'] ?? [];

    echo '<h3>' . __('Passenger Details', 'woocommerce') . '</h3>';

    if (empty($passenger_details)) {
        echo '<p>' . __('No passenger details found.', 'woocommerce') . '</p>';
        return;
    }

    foreach ($passenger_details as $index => $passenger_data) {
        echo '<h4>' . sprintf(__('Passenger %d:', 'woocommerce'), $index + 1) . '</h4>';
        echo '<p><label><strong>' . __('First Name:', 'woocommerce') . '</strong></label> ';
        echo '<input type="text" name="passenger_details[' . $index . '][first_name]" value="' . esc_attr($passenger_data['first_name'] ?? '') . '" /></p>';

        echo '<p><label><strong>' . __('Last Name:', 'woocommerce') . '</strong></label> ';
        echo '<input type="text" name="passenger_details[' . $index . '][last_name]" value="' . esc_attr($passenger_data['last_name'] ?? '') . '" /></p>';

        echo '<p><label><strong>' . __('Gender:', 'woocommerce') . '</strong></label> ';
        echo '<input type="text" name="passenger_details[' . $index . '][gender]" value="' . esc_attr($passenger_data['gender'] ?? '') . '" /></p>';

        echo '<p><label><strong>' . __('Date of Birth:', 'woocommerce') . '</strong></label> ';
        echo '<input type="date" name="passenger_details[' . $index . '][dob]" value="' . esc_attr($passenger_data['dob'] ?? '') . '" /></p>';

        echo '<p><label><strong>' . __('Email:', 'woocommerce') . '</strong></label> ';
        echo '<input type="email" name="passenger_details[' . $index . '][email]" value="' . esc_attr($passenger_data['email'] ?? '') . '" /></p>';
        
        // Passport Photo
        if (!empty($passenger_data['passport_photo'])) {
            echo '<p><strong>' . __('Passport Photo:', 'woocommerce') . '</strong> <a href="' . esc_url($passenger_data['passport_photo']) . '" target="_blank">View Photo</a></p>';
        } else {
            echo '<p><strong>' . __('Passport Photo:', 'woocommerce') . '</strong> ' . __('Not Provided', 'woocommerce') . '</p>';
        }

        // Insurance Documents
        echo '<p><strong>' . __('Insurance Documents:', 'woocommerce') . '</strong></p>';
        if (!empty($passenger_data['insurance_documents'])) {
            foreach ($passenger_data['insurance_documents'] as $doc_key => $doc_url) {
                echo '<p><a href="' . esc_url($doc_url) . '" target="_blank">' . __('Document ', 'woocommerce') . ($doc_key + 1) . '</a></p>';
            }
        } else {
            echo '<p>' . __('Not Provided', 'woocommerce') . '</p>';
        }

        // Flight Details
        if (!empty($passenger_data['flight_details'])) {
            echo '<p><strong>' . __('Flight Details:', 'woocommerce') . '</strong> ' . wp_kses_post($passenger_data['flight_details']) . '</p>';
        } else {
            echo '<p><strong>' . __('Flight Details:', 'woocommerce') . '</strong> ' . __('Not Provided', 'woocommerce') . '</p>';
        }

        echo '<hr>';
    }
}

add_action('woocommerce_process_shop_order_meta', 'save_passenger_details_in_order_editor', 10, 1);
function save_passenger_details_in_order_editor($order_id) {
    if (isset($_POST['passenger_details'])) {
        $passenger_details = array_map('sanitize_passenger_data', $_POST['passenger_details']);
        $checkout_data = get_post_meta($order_id, 'aa_checkout_data', true);
        $checkout_data['passenger_details'] = $passenger_details;
        update_post_meta($order_id, 'aa_checkout_data', $checkout_data);
    }
}

// Helper function to sanitize passenger data
function sanitize_passenger_data($data) {
    return [
        'first_name' => sanitize_text_field($data['first_name'] ?? ''),
        'last_name'  => sanitize_text_field($data['last_name'] ?? ''),
        'gender'     => sanitize_text_field($data['gender'] ?? ''),
        'dob'        => sanitize_text_field($data['dob'] ?? ''),
        'email'      => sanitize_email($data['email'] ?? ''),
    ];
}

// Add some custom CSS to style the passenger details section within the General column
add_action('admin_head', 'style_passenger_details_in_general_column');
function style_passenger_details_in_general_column() {
    echo '<style>
        .order_data_column h3 {
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 8px;
        }
        .order_data_column h4 {
            margin-top: 15px;
            font-size: 14px;
            font-weight: bold;
        }
        .order_data_column p {
            margin: 5px 0;
            font-size: 14px;
        }
        .order_data_column hr {
            margin: 10px 0;
            border-top: 1px solid #ddd;
        }
        #order_data .order_data_column .form-field-wide.wc-customer-user{
            margin-bottom:18px;
        }
    </style>';
}

add_action('init', 'process_passenger_details_form_submission');

function process_passenger_details_form_submission() {
    if (isset($_POST['save_passenger_details'])) {
        // Get the order ID
        $order_id = intval($_POST['order_id']);
        if (!$order_id) {
            error_log('Order ID is missing or invalid.');
            return;
        }

        // Ensure the wp_handle_upload() function is available
        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $checkout_data = get_post_meta($order_id, 'aa_checkout_data', true) ?: [];
        $passenger_details = $checkout_data['passenger_details'] ?? [];

        foreach ($passenger_details as $index => &$passenger_data) {
            $passenger_id = 'passenger_' . $index;

            // Update editable fields
            $passenger_data['first_name'] = sanitize_text_field($_POST[$passenger_id]['first_name'] ?? $passenger_data['first_name']);
            $passenger_data['last_name'] = sanitize_text_field($_POST[$passenger_id]['last_name'] ?? $passenger_data['last_name']);
            $passenger_data['gender'] = sanitize_text_field($_POST[$passenger_id]['gender'] ?? $passenger_data['gender']);
            $passenger_data['dob'] = sanitize_text_field($_POST[$passenger_id]['dob'] ?? $passenger_data['dob']);
            $passenger_data['email'] = sanitize_email($_POST[$passenger_id]['email'] ?? $passenger_data['email']);

            // Handle passport photo upload
            if (!empty($_FILES[$passenger_id . '_passport_photo']['name'])) {
                $upload = wp_handle_upload($_FILES[$passenger_id . '_passport_photo'], array('test_form' => false));
                if (!isset($upload['error']) && isset($upload['url'])) {
                    $passenger_data['passport_photo'] = $upload['url'];
                    error_log('Passport photo uploaded for ' . $passenger_id);
                } else {
                    error_log('Passport photo upload failed for ' . $passenger_id . ': ' . $upload['error']);
                }
            }

            // Handle insurance documents upload
            if (!empty($_FILES[$passenger_id . '_insurance_documents']['name'][0])) {
                $insurance_documents = $passenger_data['insurance_documents'] ?? [];
                foreach ($_FILES[$passenger_id . '_insurance_documents']['name'] as $key => $value) {
                    if ($_FILES[$passenger_id . '_insurance_documents']['name'][$key]) {
                        $file = array(
                            'name'     => $_FILES[$passenger_id . '_insurance_documents']['name'][$key],
                            'type'     => $_FILES[$passenger_id . '_insurance_documents']['type'][$key],
                            'tmp_name' => $_FILES[$passenger_id . '_insurance_documents']['tmp_name'][$key],
                            'error'    => $_FILES[$passenger_id . '_insurance_documents']['error'][$key],
                            'size'     => $_FILES[$passenger_id . '_insurance_documents']['size'][$key]
                        );
                        $upload = wp_handle_upload($file, array('test_form' => false));
                        if (!isset($upload['error']) && isset($upload['url'])) {
                            $insurance_documents[] = $upload['url'];
                            error_log('Insurance document uploaded for ' . $passenger_id);
                        } else {
                            error_log('Insurance document upload failed for ' . $passenger_id . ': ' . $upload['error']);
                        }
                    }
                }
                $passenger_data['insurance_documents'] = $insurance_documents;
            }

            // Save flight details from WYSIWYG editor
            $passenger_data['flight_details'] = wp_kses_post($_POST[$passenger_id . '_flight_details']);
            error_log('Flight details saved for ' . $passenger_id);
        }

        $checkout_data['passenger_details'] = $passenger_details;
        update_post_meta($order_id, 'aa_checkout_data', $checkout_data);

        error_log('Passenger details updated for order ' . $order_id);

        // Redirect to the same page to avoid resubmission
        wp_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}


add_action('wp_ajax_save_main_traveller_details', 'save_main_traveller_details_to_order');

function save_main_traveller_details_to_order() {
    check_ajax_referer('custom_nonce', 'security');

    // Ensure order_id is passed
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
    } else {
        wp_send_json_error('Order ID is missing.');
        return;
    }

    // Handle passport photo upload
    if ( ! empty( $_FILES['main_traveller_passport_photo']['name'] ) ) {
        $upload = wp_handle_upload( $_FILES['main_traveller_passport_photo'], array('test_form' => false) );
        if ( ! isset( $upload['error'] ) && isset( $upload['url'] ) ) {
            update_post_meta($order_id, 'main_traveller_passport_photo', $upload['url']);
        } else {
            wp_send_json_error('Passport photo upload failed.');
            return;
        }
    }

    // Handle insurance documents upload
    if ( ! empty( $_FILES['main_traveller_insurance_documents']['name'][0] ) ) {
        $insurance_documents = [];
        foreach ( $_FILES['main_traveller_insurance_documents']['name'] as $key => $value ) {
            if ( $_FILES['main_traveller_insurance_documents']['name'][$key] ) {
                $file = array(
                    'name'     => $_FILES['main_traveller_insurance_documents']['name'][$key],
                    'type'     => $_FILES['main_traveller_insurance_documents']['type'][$key],
                    'tmp_name' => $_FILES['main_traveller_insurance_documents']['tmp_name'][$key],
                    'error'    => $_FILES['main_traveller_insurance_documents']['error'][$key],
                    'size'     => $_FILES['main_traveller_insurance_documents']['size'][$key]
                );
                $upload = wp_handle_upload( $file, array('test_form' => false) );
                if ( ! isset( $upload['error'] ) && isset( $upload['url'] ) ) {
                    $insurance_documents[] = $upload['url'];
                } else {
                    wp_send_json_error('Insurance document upload failed.');
                    return;
                }
            }
        }
        update_post_meta($order_id, 'main_traveller_insurance_documents', $insurance_documents);
    }

    // Save flight details
    if ( isset( $_POST['main_traveller_flight_details'] ) ) {
        update_post_meta($order_id, 'main_traveller_flight_details', wp_kses_post($_POST['main_traveller_flight_details']));
    }

    wp_send_json_success('Details saved successfully.');
}



// Handle removing passport photo via AJAX
add_action('wp_ajax_remove_passport_photo', 'remove_passport_photo_via_ajax');
function remove_passport_photo_via_ajax() {
    check_ajax_referer('custom_nonce', 'security');

    $passenger_id = sanitize_text_field($_POST['passenger_id']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;

    if ($passenger_id === 'main_traveller') {
        delete_post_meta($order_id, 'main_traveller_passport_photo');
    } else {
        // Handle removing passport photo for additional passengers
        $checkout_data = get_post_meta($order_id, 'aa_checkout_data', true);
        $passenger_index = intval(str_replace('passenger_', '', $passenger_id));
        $passenger_details = $checkout_data['passenger_details'] ?? [];

        if (isset($passenger_details[$passenger_index]['passport_photo'])) {
            unset($passenger_details[$passenger_index]['passport_photo']);
            $checkout_data['passenger_details'] = $passenger_details;
            update_post_meta($order_id, 'aa_checkout_data', $checkout_data);
        }
    }

    wp_send_json_success();
}

// Handle removing insurance document via AJAX
add_action('wp_ajax_remove_insurance_doc', 'remove_insurance_doc_via_ajax');
function remove_insurance_doc_via_ajax() {
    check_ajax_referer('custom_nonce', 'security');

    $passenger_id = sanitize_text_field($_POST['passenger_id']);
    $doc_key = intval($_POST['doc_key']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;

    if ($passenger_id === 'main_traveller') {
        $insurance_documents = get_post_meta($order_id, 'main_traveller_insurance_documents', true);

        if (isset($insurance_documents[$doc_key])) {
            unset($insurance_documents[$doc_key]);
            update_post_meta($order_id, 'main_traveller_insurance_documents', array_values($insurance_documents));
        }
    } else {
        // Handle removing insurance documents for additional passengers
        $checkout_data = get_post_meta($order_id, 'aa_checkout_data', true);
        $passenger_index = intval(str_replace('passenger_', '', $passenger_id));
        $passenger_details = $checkout_data['passenger_details'] ?? [];

        if (isset($passenger_details[$passenger_index]['insurance_documents'][$doc_key])) {
            unset($passenger_details[$passenger_index]['insurance_documents'][$doc_key]);
            $passenger_details[$passenger_index]['insurance_documents'] = array_values($passenger_details[$passenger_index]['insurance_documents']);
            $checkout_data['passenger_details'] = $passenger_details;
            update_post_meta($order_id, 'aa_checkout_data', $checkout_data);
        }
    }

    wp_send_json_success();
}

// Handle removing flight details via AJAX
add_action('wp_ajax_remove_flight_details', 'remove_flight_details_via_ajax');
function remove_flight_details_via_ajax() {
    check_ajax_referer('custom_nonce', 'security');

    $passenger_id = sanitize_text_field($_POST['passenger_id']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : null;

    if ($passenger_id === 'main_traveller') {
        update_post_meta($order_id, 'main_traveller_flight_details', '');
    } else {
        // Handle removing flight details for additional passengers
        $checkout_data = get_post_meta($order_id, 'aa_checkout_data', true);
        $passenger_index = intval(str_replace('passenger_', '', $passenger_id));
        $passenger_details = $checkout_data['passenger_details'] ?? [];

        if (isset($passenger_details[$passenger_index]['flight_details'])) {
            $passenger_details[$passenger_index]['flight_details'] = '';
            $checkout_data['passenger_details'] = $passenger_details;
            update_post_meta($order_id, 'aa_checkout_data', $checkout_data);
        }
    }

    wp_send_json_success();
}




// Add custom fields (DOB, Gender, Passport Number, Passport Photo, Insurance Documents, Flight Details) to the Order Edit page
add_action('woocommerce_admin_order_data_after_billing_address', 'add_combined_custom_order_fields_in_admin', 10, 1);

function add_combined_custom_order_fields_in_admin($order){
    $customer_id = $order->get_user_id();
    
    // Get the custom fields
    $dob = get_user_meta($customer_id, 'dob', true);
    $gender = get_user_meta($customer_id, 'gender', true);
    $passport_number = get_user_meta($customer_id, 'passport_number', true);
    $main_traveller_passport_photo = get_post_meta($order->get_id(), 'main_traveller_passport_photo', true);
    $main_traveller_insurance_documents = get_post_meta($order->get_id(), 'main_traveller_insurance_documents', true);
    $main_traveller_flight_details = get_post_meta($order->get_id(), 'main_traveller_flight_details', true);

    echo '<div class="custom-billing-details">';
    echo '<h3>' . __('Additional Main Traveller Details', 'woocommerce') . '</h3>';
    
    // Date of Birth
    echo '<p><strong>' . __('Date of Birth:', 'woocommerce') . '</strong> ' . esc_html($dob) . '</p>';
    
    // Gender
    echo '<p><strong>' . __('Gender:', 'woocommerce') . '</strong> ' . esc_html(ucfirst($gender)) . '</p>';
    
    // Passport Photo
    if ($main_traveller_passport_photo) {
        echo '<p><strong>' . __('Passport Photo:', 'woocommerce') . '</strong> <a href="' . esc_url($main_traveller_passport_photo) . '" target="_blank">View Photo</a></p>';
    } else {
        echo '<p><strong>' . __('Passport Photo:', 'woocommerce') . '</strong> Not provided</p>';
    }

    // Insurance Documents
    if ($main_traveller_insurance_documents && is_array($main_traveller_insurance_documents)) {
        echo '<p><strong>' . __('Insurance Documents:', 'woocommerce') . '</strong></p>';
        foreach ($main_traveller_insurance_documents as $key => $doc_url) {
            echo '<p><a href="' . esc_url($doc_url) . '" target="_blank">Document ' . ($key + 1) . '</a></p>';
        }
    } else {
        echo '<p><strong>' . __('Insurance Documents:', 'woocommerce') . '</strong> Not provided</p>';
    }

    // Flight Details
    if ($main_traveller_flight_details) {
        echo '<p><strong>' . __('Flight Details:', 'woocommerce') . '</strong> ' . wp_kses_post($main_traveller_flight_details) . '</p>';
    } else {
        echo '<p><strong>' . __('Flight Details:', 'woocommerce') . '</strong> Not provided</p>';
    }
    
    echo '</div>';
}

// Add a custom column "Trip Details" to the WooCommerce My Account Orders table
add_filter('woocommerce_account_orders_columns', function( $columns ){
    $columns['trip-details'] = __( 'Trip Details', 'woocommerce' );
    return $columns;
}, 10);

// Populate the "Trip Details" column in the WooCommerce My Account Orders table
add_action('woocommerce_my_account_my_orders_column_trip-details', function( $order ) {
    // Retrieve order meta data
    $checkout_data = get_post_meta($order->get_id(), 'aa_checkout_data', true);

    // Check main traveller details
    $main_traveller_photo = get_post_meta($order->get_id(), 'main_traveller_passport_photo', true);
    $main_traveller_insurance = get_post_meta($order->get_id(), 'main_traveller_insurance_documents', true);
    $main_traveller_flight_details = get_post_meta($order->get_id(), 'main_traveller_flight_details', true);

    $main_traveller_provided = !empty($main_traveller_photo) && !empty($main_traveller_insurance) && !empty($main_traveller_flight_details);

    // Check passenger details
    $passengers_provided = true;
    if (!empty($checkout_data['passenger_details'])) {
        foreach ($checkout_data['passenger_details'] as $passenger_data) {
            if (empty($passenger_data['passport_photo']) || empty($passenger_data['insurance_documents']) || empty($passenger_data['flight_details'])) {
                $passengers_provided = false;
                break;
            }
        }
    }

    // Display the appropriate message
    if ($main_traveller_provided && $passengers_provided) {
        echo '✅ ' . __( 'All details provided', 'woocommerce' );
    } else {
        echo '⚠️ ' . __( 'Details Required!', 'woocommerce' );
    }
}, 10, 1);

function get_orders_missing_details($user_id) {
    $orders_missing_details = [];

    $orders = wc_get_orders([
        'customer' => $user_id,
        'status' => 'any', // Check orders with any status
        'limit' => -1, // Get all orders for the user
    ]);

    foreach ($orders as $order) {
        $checkout_data = get_post_meta($order->get_id(), 'aa_checkout_data', true);
        $main_traveller_provided = true;

        // Check main traveller details
        $main_traveller_photo = get_user_meta($order->get_user_id(), 'passport_photo', true);
        $main_traveller_insurance = get_user_meta($order->get_user_id(), 'insurance_documents', true);
        $main_traveller_flight_details = get_user_meta($order->get_user_id(), 'flight_details', true);

        if (empty($main_traveller_photo) || empty($main_traveller_insurance) || empty($main_traveller_flight_details)) {
            $main_traveller_provided = false;
        }

        // Check passenger details
        $passengers_provided = true;
        if (!empty($checkout_data['passenger_details'])) {
            foreach ($checkout_data['passenger_details'] as $passenger_data) {
                if (empty($passenger_data['passport_photo']) || empty($passenger_data['insurance_documents']) || empty($passenger_data['flight_details'])) {
                    $passengers_provided = false;
                    break;
                }
            }
        }

        // If any details are missing, add the order to the list
        if (!$main_traveller_provided || !$passengers_provided) {
            $orders_missing_details[] = $order;
        }
    }

    return $orders_missing_details;
}

function handle_feedback_form_submission() {
    check_ajax_referer('custom_nonce', 'security');

    $reviewer_image = $_FILES['reviewer_image'];
    $reviewer_name = sanitize_text_field($_POST['reviewer_name']);
    $reviewer_destination = sanitize_text_field($_POST['reviewer_destination']);
    $stars = intval($_POST['stars']);
    $review_content = wp_kses_post($_POST['review_content']);

    // Upload the image
    $upload = wp_handle_upload($reviewer_image, ['test_form' => false]);

    if (isset($upload['file'])) {
        // Get the file type from the uploaded file
        $wp_filetype = wp_check_filetype($upload['file']);

        // Prepare an array of post data for the attachment
        $attachment = array(
            'guid' => $upload['url'], 
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($reviewer_image['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        );

        // Insert the attachment into the WordPress Media Library and get the attachment ID
        $attachment_id = wp_insert_attachment($attachment, $upload['file']);

        // Include the image.php file which has the wp_generate_attachment_metadata function
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Generate metadata for the attachment and update the database record
        $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);

    } else {
        wp_send_json_error('Image upload failed.');
    }


    // Create a new testimonial post
    $testimonial_id = wp_insert_post([
        'post_title'   => $reviewer_name . ', ' . $reviewer_destination,
        'post_status'  => 'draft',
        'post_type'    => 'testimonial',
    ]);

    if ($testimonial_id) {
        // Save the ACF fields
        update_field('reviewer_image', $attachment_id, $testimonial_id);
        update_field('reviewer_name', $reviewer_name, $testimonial_id);
        update_field('reviewer_destination', $reviewer_destination, $testimonial_id);
        update_field('stars', $stars, $testimonial_id);
        update_field('review_content', $review_content, $testimonial_id);

        wp_send_json_success('Review submitted successfully.');
    } else {
        wp_send_json_error('Failed to create testimonial.');
    }
}

add_action('wp_ajax_save_feedback_form', 'handle_feedback_form_submission');
add_action('wp_ajax_nopriv_save_feedback_form', 'handle_feedback_form_submission');

function enqueue_mountain_posts_block() {
    wp_enqueue_script(
        'mountain-posts-block',
        get_stylesheet_directory_uri() . '/blocks/mountain-posts.js', // Make sure this path is correct
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
        filemtime(get_stylesheet_directory() . '/blocks/mountain-posts.js'), // Correct file versioning
        true // Ensure the script is loaded in the footer
    );
}
add_action('enqueue_block_editor_assets', 'enqueue_mountain_posts_block');

function register_mountain_posts_block() {
    register_block_type('custom/mountain-posts', array(
        'render_callback' => 'render_mountain_posts_block',
        'attributes' => array(
            'selectedMountain' => array(
                'type' => 'string',
                'default' => '',
            ),
        ),
    ));
}
add_action('init', 'register_mountain_posts_block');

function render_mountain_posts_block($attributes) {
    $current_mountain_ID = $attributes['selectedMountain'];

    if (empty($current_mountain_ID)) {
        return '<p>No mountain selected.</p>';
    }

    ob_start();
    ?>
    <div class="trips-grid">
        <?php 
        $args = array(
            'post_type' => 'trip',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'mountain',
                    'value' => $current_mountain_ID,
                    'compare' => '=',
                ),
            ),
        );

        $trips = new WP_Query($args);
        if ($trips->have_posts()) : 
            while ($trips->have_posts()) : $trips->the_post(); 
                $blurb = get_field('blurb');
                $days = get_field('days');
                $altitude = get_field('altitude');
                $distance = get_field('distance');
                $challenge = get_field('challenge');
                $price_from = get_field('price_from');
                ?>
                <div class="trip-item" data-category="<?php echo get_the_terms(get_the_ID(), 'trip-category')[0]->slug; ?>">
                    <div class="trip-item-image">
                        <div class="faux-bg-img">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                    </div>
                    <div class="trip-meta">
                        <div class="trip-title">
                            <h3><?php the_title(); ?></h3>
                        </div>
                        <div class="trip-blurb">
                            <?= $blurb ?>
                        </div>
                        <div class="trip-table">
                            <table>
                                <?php if (!empty($days)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(175, 'full')?></span> Days</td>
                                        <td><?= $days ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($altitude)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(173, 'full')?></span> Altitude</td>
                                        <td><?= $altitude ?> m</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($distance)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(176, 'full')?></span> Distance</td>
                                        <td><?= $distance ?> km</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($challenge)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(174, 'full')?></span> Challenge</td>
                                        <td><?= $challenge ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="price-row">
                            <p>
                                <?= (preg_match('/\d/', $price_from) && !empty($price_from)) ? "Trip Price  $price_from" : "Enquire for Details" ?>
                            </p>
                        </div>
                        <div class="button-row">
                            <a class="trip-btn" href="<?= get_permalink(); ?>">View this Trip</a>
                        </div>
                    </div>
                </div>
                <?php
            endwhile;
        endif;
        wp_reset_postdata();
        ?>
    </div>
    <?php

    return ob_get_clean();
}


function fetch_mountain_posts() {
    $mountains = get_posts(array(
        'post_type' => 'mountain',
        'numberposts' => 3,
    ));

    $mountain_list = array();
    if (!empty($mountains)) {
        foreach ($mountains as $mountain) {
            $mountain_list[] = array(
                'id' => $mountain->ID,
                'title' => $mountain->post_title,
            );
        }
    }

    wp_send_json($mountain_list);
}

add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/mountains', array(
        'methods' => 'GET',
        'callback' => 'fetch_mountain_posts',
        'permission_callback' => '__return_true',
    ));
});

function enqueue_trips_block() {
    wp_enqueue_script(
        'trips-block',
        get_stylesheet_directory_uri() . '/blocks/trips-block.js', // Make sure this path is correct
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
        filemtime(get_stylesheet_directory() . '/blocks/trips-block.js'), // Correct file versioning
        true // Ensure the script is loaded in the footer
    );
}
add_action('enqueue_block_editor_assets', 'enqueue_trips_block');

function register_trips_block() {
    register_block_type('custom/trips-block', array(
        'render_callback' => 'render_trips_block',
        'attributes' => array(
            'selectedTrips' => array(
                'type' => 'array',
                'default' => [],
            ),
        ),
    ));
}
add_action('init', 'register_trips_block');

function render_trips_block($attributes) {
    $selected_trip_ids = $attributes['selectedTrips'];

    if (empty($selected_trip_ids)) {
        return '<p>No trips selected.</p>';
    }

    ob_start();
    ?>
    <div class="trips-grid">
        <?php
        $args = array(
            'post_type' => 'trip',
            'posts_per_page' => 3,
            'post__in' => $selected_trip_ids, // Use the selected trips' IDs
        );

        $trips = new WP_Query($args);
        if ($trips->have_posts()) : 
            while ($trips->have_posts()) : $trips->the_post(); 
                $blurb = get_field('blurb');
                $days = get_field('days');
                $altitude = get_field('altitude');
                $distance = get_field('distance');
                $challenge = get_field('challenge');
                $price_from = get_field('price_from');
                ?>
                <div class="trip-item" data-category="<?php echo get_the_terms(get_the_ID(), 'trip-category')[0]->slug; ?>">
                    <div class="trip-item-image">
                        <div class="faux-bg-img">
                            <?php the_post_thumbnail('full'); ?>
                        </div>
                    </div>
                    <div class="trip-meta">
                        <div class="trip-title">
                            <h3><?php the_title(); ?></h3>
                        </div>
                        <div class="trip-blurb">
                            <?= $blurb ?>
                        </div>
                        <div class="trip-table">
                            <table>
                                <?php if (!empty($days)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(175, 'full')?></span> Days</td>
                                        <td><?= $days ?></td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($altitude)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(173, 'full')?></span> Altitude</td>
                                        <td><?= $altitude ?> m</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($distance)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(176, 'full')?></span> Distance</td>
                                        <td><?= $distance ?> km</td>
                                    </tr>
                                <?php endif; ?>

                                <?php if (!empty($challenge)) : ?>
                                    <tr>
                                        <td><span class="trip-meta-icon"><?= wp_get_attachment_image(174, 'full')?></span> Challenge</td>
                                        <td><?= $challenge ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="price-row">
                            <p>
                                <?= (preg_match('/\d/', $price_from) && !empty($price_from)) ? "Trip Price  $price_from" : "Enquire for Details" ?>
                            </p>
                        </div>
                        <div class="button-row">
                            <a class="trip-btn" href="<?= get_permalink(); ?>">View this Trip</a>
                        </div>
                    </div>
                </div>
                <?php
            endwhile;
        endif;
        wp_reset_postdata();
        ?>
    </div>
    <?php

    return ob_get_clean();
}

function fetch_trip_posts() {
    $trips = get_posts(array(
        'post_type' => 'trip',
        'numberposts' => -1,
    ));

    $trip_list = array();
    if (!empty($trips)) {
        foreach ($trips as $trip) {
            $trip_list[] = array(
                'id' => $trip->ID,
                'title' => $trip->post_title,
            );
        }
    }

    wp_send_json($trip_list);
}

add_action('rest_api_init', function() {
    register_rest_route('custom/v1', '/trips', array(
        'methods' => 'GET',
        'callback' => 'fetch_trip_posts',
        'permission_callback' => '__return_true',
    ));
});

// Prevent WordPress from redirecting to wp-login.php on failed login
function custom_login_failed() {
    $referrer = wp_get_referer(); // Get the referring URL
    if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
        if (!is_user_logged_in()) {
            // Append the login failed query param
            wp_safe_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }
}
add_action('wp_login_failed', 'custom_login_failed');

// Handle non-existent user error (no redirection loop)
function handle_nonexistent_user($user, $username, $password) {
    if (is_wp_error($user) && $user->get_error_code() === 'invalid_username') {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            // Append the login failed query param for non-existent user
            wp_safe_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }
    return $user;
}
add_filter('authenticate', 'handle_nonexistent_user', 100, 3);

// Prevent redirect for empty fields and avoid redirect loop for non-existent users
function prevent_redirect_on_empty_fields($user, $username, $password) {
    // Check if username or password fields are empty
    if (empty($username) || empty($password)) {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            // Append the login empty query param
            wp_safe_redirect(add_query_arg('login', 'empty', $referrer));
            exit;
        }
    }
    return $user; // Proceed with authentication if fields are filled
}
add_filter('authenticate', 'prevent_redirect_on_empty_fields', 1, 3);

// Redirect to a custom page after successful login
function custom_login_redirect($redirect_to, $requested_redirect_to, $user) {
    // Only perform redirect if the login was successful and there's no error
    if (!is_wp_error($user) && is_user_logged_in()) {
        return home_url('/account'); // Redirect to the account page after successful login
    }
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// Handle AJAX request to check if the email exists in historical orders
function check_historical_email_callback() {
    global $wpdb;
    
    // Get the email from the AJAX request
    $email = sanitize_email($_POST['email']);

    // Query the growthre_historical_orders.bookings table for the email
    $email_exists_in_history = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT email FROM historical_bookings WHERE email = %s", 
            $email
        )
    );

    // Return a JSON response indicating if the email exists
    if ($email_exists_in_history) {
        wp_send_json_success(array('exists' => true)); // Email exists
    } else {
        wp_send_json_success(array('exists' => false)); // Email does not exist
    }
    
    wp_die(); // Close the request
}
add_action('wp_ajax_check_historical_email', 'check_historical_email_callback');
add_action('wp_ajax_nopriv_check_historical_email', 'check_historical_email_callback');

/*add_action( 'woocommerce_email_actions', 'remove_default_woocommerce_email_triggers', 10 );
function remove_default_woocommerce_email_triggers() {
    // Unhook the default new order email
    remove_action( 'woocommerce_new_order', array( WC()->mailer()->emails['WC_Email_New_Order'], 'trigger' ), 10 );
    
    // Unhook the default customer processing order email
    remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( WC()->mailer()->emails['WC_Email_Customer_Processing_Order'], 'trigger' ), 10 );
}

// Hook to custom action after the checkout data has been saved and post-processing is completed
add_action( 'woocommerce_checkout_order_created', 'schedule_custom_email_sending', 100, 1 );
function schedule_custom_email_sending( $order ) {
    // Schedule the email to be sent after 60 seconds
    $order_id = $order->get_id();
    if ( ! wp_next_scheduled( 'send_scheduled_emails', array( $order_id ) ) ) {
        wp_schedule_single_event( time() + 60, 'send_scheduled_emails', array( $order_id ) );
    }
}

// Function to trigger emails after the delay
add_action( 'send_scheduled_emails', 'send_delayed_order_emails', 10, 1 );
function send_delayed_order_emails( $order_id ) {
    $order = wc_get_order( $order_id );

    // Ensure that the order is valid and exists
    if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        return;
    }

    // Initialize WooCommerce email system
    $mailer = WC()->mailer();
    if ( ! isset( $mailer->emails ) || ! is_array( $mailer->emails ) ) {
        $mailer->init_transactional_emails(); // Ensure emails are initialized
    }

    // Trigger the New Order email
    if ( isset( $mailer->emails['WC_Email_New_Order'] ) ) {
        $mailer->emails['WC_Email_New_Order']->trigger( $order_id );
    }

    // Trigger the Customer Processing Order email
    if ( isset( $mailer->emails['WC_Email_Customer_Processing_Order'] ) ) {
        $mailer->emails['WC_Email_Customer_Processing_Order']->trigger( $order_id );
    }
}

// Optional: Cancel the scheduled emails if the order is cancelled
add_action( 'woocommerce_order_status_cancelled', 'cancel_scheduled_emails', 10, 1 );
function cancel_scheduled_emails( $order_id ) {
    if ( wp_next_scheduled( 'send_scheduled_emails', array( $order_id ) ) ) {
        wp_clear_scheduled_hook( 'send_scheduled_emails', array( $order_id ) );
    }
}
*/

function exclude_woocommerce_products_from_search( $query ) {
    if ( !is_admin() && $query->is_search() && $query->is_main_query() ) {
        $query->set( 'post_type', array( 'post', 'page' ) );
    }
}
add_action( 'pre_get_posts', 'exclude_woocommerce_products_from_search' );

function include_and_prioritize_trip_post_type_in_search( $query ) {
    if ( !is_admin() && $query->is_search() && $query->is_main_query() ) {
        
        // Modify the query to include both 'trip' and other post types
        $query->set( 'post_type', array( 'trip', 'post', 'page' ) );
        
        // Prioritize 'trip' post type by altering the orderby clause
        add_filter( 'posts_clauses', 'prioritize_trip_clauses', 10, 2 );
    }
}
add_action( 'pre_get_posts', 'include_and_prioritize_trip_post_type_in_search' );

function prioritize_trip_clauses( $clauses, $query ) {
    global $wpdb;

    // Modify the ORDER BY clause to prioritize 'trip' post type
    $clauses['orderby'] = "
        CASE WHEN {$wpdb->posts}.post_type = 'trip' THEN 0 ELSE 1 END, 
        {$wpdb->posts}.post_date DESC
    ";

    return $clauses;
}

add_filter( 'posts_clauses', 'prioritize_trip_clauses', 10, 2 );

add_action('template_redirect', function() {
    if (is_singular('product')) {
        wp_redirect(home_url());
        exit;
    }
});

function custom_wp_mail_smtp( $phpmailer ) {
    if ( strpos( $phpmailer->From, 'enquiries@adventurealternative.com' ) !== false ) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'adventurealternative.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = 465;
        $phpmailer->SMTPSecure = 'ssl';
        $phpmailer->Username   = 'enquiries@adventurealternative.com';
        $phpmailer->Password   = 'rJX]=QWhX?64';
    } else {
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'adventurealternative.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = 465;
        $phpmailer->SMTPSecure = 'ssl';
        $phpmailer->Username   = 'bookings@adventurealternative.com';
        $phpmailer->Password   = 'rJX]=QWhX?64';
    }
}
add_action( 'phpmailer_init', 'custom_wp_mail_smtp' );

require_once get_stylesheet_directory() . '/src/checkout-currency.php';
add_action('woocommerce_before_order_object_save', function($order) {
    if (!is_admin() || !$order instanceof WC_Order) return;

    $order_id = $order->get_id();
    $currency = get_post_meta($order_id, 'aa_currency', true) ?: strtolower($order->get_currency());

    $currencies = function_exists('aa_get_currencies') ? aa_get_currencies() : [];
    $rate = $currencies[strtolower($currency)]['exchange_rate'] ?? 1;

    if (!is_numeric($rate) || $rate === 1) return;

    $log = "--- Hook: woocommerce_before_order_object_save ---\n";
    $log .= "Order ID: {$order_id}, Currency: {$currency}, Rate: {$rate}\n";

    foreach ($order->get_items('line_item') as $item_id => $item) {
        if (!$item instanceof WC_Order_Item_Product) continue;

        // Skip already converted items
        if ($item->get_meta('converted_from_gbp')) {
            $log .= "❌ Item {$item_id} already converted. Skipping.\n";
            continue;
        }

        $product = $item->get_product();
        if (!$product) continue;

        $base_price = $product->get_price();
        $converted_price = round($base_price * $rate, 2);

        $item->set_total($converted_price * $item->get_quantity());
        $item->set_subtotal($converted_price * $item->get_quantity());
        $item->add_meta_data('converted_from_gbp', $base_price, true);
        $item->add_meta_data('converted_to_currency', strtoupper($currency), true);
        $item->save();

        $log .= "✔ Item {$item_id} converted: {$base_price} → {$converted_price}\n";
    }

    file_put_contents(ABSPATH . '/my-log.txt', $log, FILE_APPEND);

}, 20);

// Force all "lost password" links to your front-end page
add_filter('lostpassword_url', function($url, $redirect) {
    return home_url('/forgot-password/');
}, 10, 2);

add_filter('login_form_bottom', function($content, $args) {
    $url = home_url('/forgot-password/');
    return $content . '<p class="lost-password"><a href="'. esc_url($url) .'">Forgot your password?</a></p>';
}, 10, 2);

// TEMPORARY DEBUG - Stripe Payment Flow Tracer
add_action('wp_footer', function() {
    if (is_checkout()) {
        ?>
        <script>
        console.log('%c=== STRIPE PAYMENT DEBUGGER ENABLED ===', 'background: #635bff; color: white; padding: 5px;');
        
        // Intercept fetch requests
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const url = typeof args[0] === 'string' ? args[0] : args[0].url;
            if (url && (url.includes('stripe') || url.includes('payment') || url.includes('checkout') || url.includes('wc-ajax'))) {
                console.log('%c[FETCH REQUEST]', 'color: blue; font-weight: bold;', url);
                if (args[1] && args[1].body) {
                    try {
                        console.log('%c[REQUEST BODY]', 'color: purple;', args[1].body);
                    } catch(e) {}
                }
            }
            return originalFetch.apply(this, args).then(response => {
                if (url && (url.includes('stripe') || url.includes('payment') || url.includes('wc-ajax'))) {
                    response.clone().text().then(text => {
                        console.log('%c[RESPONSE]', 'color: green; font-weight: bold;', url.substring(0,50));
                        try {
                            console.log(JSON.parse(text));
                        } catch(e) {
                            console.log(text.substring(0, 500));
                        }
                    }).catch(() => {});
                }
                return response;
            }).catch(err => {
                console.log('%c[FETCH ERROR]', 'color: red; font-weight: bold;', url, err);
                throw err;
            });
        };

        // Intercept XHR
        const originalXHR = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function(method, url) {
            this._url = url;
            if (url && (url.includes('stripe') || url.includes('payment') || url.includes('wc-ajax'))) {
                console.log('%c[XHR]', 'color: orange;', method, url);
            }
            return originalXHR.apply(this, arguments);
        };

        // Watch for errors
        window.addEventListener('error', e => console.log('%c[JS ERROR]', 'color: red; font-size: 14px;', e.message, e.filename, e.lineno));
        
        // Stripe specific
        if (typeof Stripe !== 'undefined') {
            console.log('%c[STRIPE]', 'color: #635bff;', 'Stripe.js loaded');
        }
        
        console.log('%c Ready - use declined card: 4000 0000 0000 0002', 'background: yellow; color: black; padding: 3px;');
        </script>
        <?php
    }
});


// Dynamic URL replacement filter for dev/prod
add_filter('the_content', 'aa_dynamic_url_replace', 999);
add_filter('the_permalink', 'aa_dynamic_url_replace', 999);
add_filter('post_link', 'aa_dynamic_url_replace', 999);
add_filter('page_link', 'aa_dynamic_url_replace', 999);
add_filter('wp_nav_menu', 'aa_dynamic_url_replace', 999);
add_filter('widget_text', 'aa_dynamic_url_replace', 999);
add_filter('script_loader_src', 'aa_dynamic_url_replace', 999);
add_filter('style_loader_src', 'aa_dynamic_url_replace', 999);

function aa_dynamic_url_replace($content) {
    $current_host = $_SERVER['HTTP_HOST'] ?? '';

    if (strpos($current_host, 'dev.') !== false) {
        // On dev - replace production URLs with dev
        $content = str_replace(
            ['https://www.adventurealternative.com', 'https://adventurealternative.com'],
            'https://dev.adventurealternative.com',
            $content
        );
    }
    return $content;
}

// Also filter final output buffer
add_action('template_redirect', function() {
    $current_host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($current_host, 'dev.') !== false) {
        ob_start(function($html) {
            return str_replace(
                ['https://www.adventurealternative.com', 'https://adventurealternative.com'],
                'https://dev.adventurealternative.com',
                $html
            );
        });
    }
});



// Enqueue 3DS checkout fix JS - Added by Ahmed
add_action( 'wp_enqueue_scripts', function() {
    if ( is_checkout() ) {
        wp_enqueue_script( 'aa-checkout-3ds', get_stylesheet_directory_uri() . '/assets/js/aa-checkout-3ds.js', array('jquery'), '2.3.0', true );
    }
});
