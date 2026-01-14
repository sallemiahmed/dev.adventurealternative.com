<?php
/* Template Name: Frontend Admin */
if ( !is_user_logged_in() ) {
    wp_redirect( home_url('/user-login/') );
    exit; // Always call exit after wp_redirect to prevent further execution
}

get_header();

$current_user = wp_get_current_user();
$billing_first_name = get_user_meta($current_user->ID, 'billing_first_name', true);
$billing_last_name = get_user_meta($current_user->ID, 'billing_last_name', true);
$billing_address_1 = get_user_meta($current_user->ID, 'billing_address_1', true);
$billing_address_2 = get_user_meta($current_user->ID, 'billing_address_2', true);
$billing_city = get_user_meta($current_user->ID, 'billing_city', true);
$billing_postcode = get_user_meta($current_user->ID, 'billing_postcode', true);
$billing_country = get_user_meta($current_user->ID, 'billing_country', true);
$billing_state = get_user_meta($current_user->ID, 'billing_state', true);
$billing_phone = get_user_meta($current_user->ID, 'billing_phone', true);
$billing_email = $current_user->user_email;
$dob = get_user_meta($current_user->ID, 'dob', true);
$gender = get_user_meta($current_user->ID, 'gender', true);
?>
<section id="hero">
    <div class="faux-bg-img">
        <?= wp_get_attachment_image(1171, 'full'); ?>
    </div>
    <div class="container">
        <h1 class="title">
            <?= 'Welcome <i>' . $current_user->display_name . '</i>'; ?>
        </h1>
        <div class="hero-text">
            <p>
                Welcome back. Now that you’re logged in, you’ll be able to view or change any of your personal details, add in extras, view important documents and guides, view your balance, add in extra passengers and more.
            </p>
            <p>
                Take a look around and if you have any questions, get in touch.
            </p>
        </div>
    </div>
</section>
<?php
$current_user_id = get_current_user_id();
$orders_missing_details = get_orders_missing_details($current_user_id);
if (!empty($orders_missing_details)) :
    ?>
    <div class="admin-warning" style="background-color: #ff5e01; text-align: center; color: white;">
        <p>⚠️ We need some more details from you regarding your order(s): 
        <?php 
        foreach ($orders_missing_details as $order) {
            $order_number = $order->get_order_number();
            $order_url = $order->get_view_order_url();
            echo '<a href="' . esc_url($order_url) . '" style="color: white; text-decoration: underline;">#' . esc_html($order_number) . '</a> ';
        }
        ?>
        </p>
    </div>
<?php endif; ?>
<section id="mininav">
    <div class="container">
        <div class="nav-links">
            <a href="<?= wc_get_account_endpoint_url('dashboard'); // ADDED BY DEJAN ?>#personal-info" class="nav-link smooth-scroll">
                <div class="link-text">
                    Personal Information
                </div>
            </a>
            <a href="<?= wc_get_account_endpoint_url('dashboard'); // ADDED BY DEJAN ?>#my-trips" class="nav-link smooth-scroll">
                <div class="link-text">
                    My Trips
                </div>
            </a>
            <a href="#contact-us" class="nav-link smooth-scroll">
                <div class="link-text">
                    Contact Us
                </div>
            </a>
            <a href="<?= wp_logout_url(home_url()); ?>" class="nav-link logout">
                <div class="link-text">
                   <?= wp_get_attachment_image(1187, 'full'); ?> Log Out
                </div>
            </a>
        </div>
    </div>
</section>
<?php if( ! empty( $GLOBALS['wp']->query_vars['view-order'] ) ): ?>
    <?php
    
    $order_id = (int) $GLOBALS['wp']->query_vars['view-order'];
    $order = wc_get_order( $order_id );
    $trip_order = new AA_Trip_Order( $order );
    
    if( ( $_GET['aa-action'] ?? '' ) == 'modify-order-extras' )
        include get_stylesheet_directory() . '/src/templates/order-modify-extras.php';
    else
        include get_stylesheet_directory() . '/src/templates/view-order.php';
    
    ?>
<?php else: ?>
    <section id="personal-info">
        <div class="container">
            <div id="personal-info-fields">
                <form id="personal-info-form" method="post">
                    <div class="form-title-row">
                        <h2>Personal Information</h2>
                        <button type="button" id="edit-details-btn">Click Here to Edit Your Details</button>
                        <button type="submit" id="save-details-btn" style="display:none;">Save Changes</button>
                    </div>
                    <div class="form-flex">
                        <div class="form-group">
                            <label for="billing_first_name">First Name</label>
                            <input type="text" id="billing_first_name" name="billing_first_name" value="<?php echo esc_attr($billing_first_name); ?>" placeholder="First Name" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_last_name">Last Name</label>
                            <input type="text" id="billing_last_name" name="billing_last_name" value="<?php echo esc_attr($billing_last_name); ?>" placeholder="Last Name" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_email">Email</label>
                            <input type="email" id="billing_email" name="billing_email" value="<?php echo esc_attr($billing_email); ?>" placeholder="Email" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_phone">Phone</label>
                            <input type="text" id="billing_phone" name="billing_phone" value="<?php echo esc_attr($billing_phone); ?>" placeholder="Phone" readonly />
                        </div>
    					<div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?php echo esc_attr($dob); ?>" readonly />
                        </div>
    					<div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" disabled>
                                <option value="male" <?php selected($gender, 'male'); ?>>Male</option>
                                <option value="female" <?php selected($gender, 'female'); ?>>Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="billing_address_1">Address Line 1</label>
                            <input type="text" id="billing_address_1" name="billing_address_1" value="<?php echo esc_attr($billing_address_1); ?>" placeholder="Address Line 1" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_address_2">Address Line 2</label>
                            <input type="text" id="billing_address_2" name="billing_address_2" value="<?php echo esc_attr($billing_address_2); ?>" placeholder="Address Line 2" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_city">City</label>
                            <input type="text" id="billing_city" name="billing_city" value="<?php echo esc_attr($billing_city); ?>" placeholder="City" readonly />
                        </div>
                        <div class="form-group">
                            <label for="billing_postcode">Postcode / ZIP</label>
                            <input type="text" id="billing_postcode" name="billing_postcode" value="<?php echo esc_attr($billing_postcode); ?>" placeholder="Postcode / ZIP" readonly />
                        </div>
                        <?php woocommerce_form_field( 'billing_country', [
                            'type'  => 'country',
                            'label' => __('Country', 'woocommerce'),
                            'value' => $billing_country,
                            'class' => [ 'form-group', 'select2-field-container' ],
                            'required' => true
                        ], $billing_country ); // ADDED BY DEJAN - javascript code is executed from /wp-content/plugins/woocommerce/assets/js/frontend/country-select.min.js ?>
                        <?php if( false ): ?>
                        <div class="form-group">
                            <label for="billing_country">Country / Region</label>
                            <select id="billing_country" name="billing_country" disabled>
                                <?php
                                $countries = WC()->countries->get_countries();
                                foreach ($countries as $country_code => $country_name) {
                                    echo '<option value="' . esc_attr($country_code) . '" ' . selected($billing_country, $country_code, false) . '>' . esc_html($country_name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php woocommerce_form_field( 'billing_state', [
                            'type'  => 'state',
                            'label' => __('State / County', 'woocommerce'),
                            'value' => $billing_state,
                            'class' => [ 'form-group', 'select2-field-container' ],
                            'required' => true
                        ], $billing_state ); // ADDED BY DEJAN - javascript code is executed from /wp-content/plugins/woocommerce/assets/js/frontend/country-select.min.js ?>
                        <?php if( false ): // Connor to remove this old code ?>
                        <div class="form-group">
                            <label for="billing_state">State / County</label>
                            <input type="text" id="billing_state" name="billing_state" value="<?php echo esc_attr($billing_state); ?>" placeholder="State / County" readonly />
                        </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php include get_stylesheet_directory() . '/src/templates/account-my-trips.php'; // ADDED BY DEJAN ?>
	<section id="cta" class="admin-contact-cta">
    <div id="contact-us" class="container">
        <div class="cta-content">
            <h2 class="subheading">
                Contact Us
            </h2>
            <div class="content">
                If you need anything at all, please reach out to us via one of the options below.
            </div>
            <div class="button-row">
				<a class="cta-btn call" href="tel:+442870831258">
                   <?= wp_get_attachment_image(800, 'thumbnail'); ?> Call Us
                </a>
				<a class="cta-btn email" href="mailto:office@adventurealternative.com">
                   <?= wp_get_attachment_image(801, 'thumbnail'); ?> Email Us
                </a>
                <a class="cta-btn" href="/enquiries/">
                    See all our Offices
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const editButton = document.getElementById('edit-details-btn');
        const saveButton = document.getElementById('save-details-btn');
        const fields = document.querySelectorAll('#personal-info-form input, #personal-info-form select');
        const select2_containers = document.querySelectorAll('#personal-info-form .select2-field-container'); // ADDED BY DEJAN

        function setFieldsState(readOnly) {
            fields.forEach(field => {
                if (field.tagName === 'SELECT') {
                    field.disabled = readOnly;
                } else {
                    if (readOnly) {
                        field.setAttribute('readonly', 'readonly');
                    } else {
                        field.removeAttribute('readonly');
                    }
                }
                field.classList.toggle('editable', !readOnly);
            });
            select2_containers.forEach(field => {
                field.classList.toggle('select2-editable', !readOnly);
            });
        }

        editButton.addEventListener('click', function () {
            setFieldsState(false);
            editButton.style.display = 'none';
            saveButton.style.display = 'block';
        });

        saveButton.addEventListener('click', function (e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById('personal-info-form'));
            formData.append('action', 'update_woocommerce_user_details');
            formData.append('_ajax_nonce', '<?php echo wp_create_nonce("update_woocommerce_user_details_nonce"); ?>');

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    setFieldsState(true);
                    editButton.style.display = 'block';
                    saveButton.style.display = 'none';
                    alert('Details updated successfully');
                } else {
                    alert('There was an error updating your details');
                }
            });
        });

        // Initialize fields as readonly/disabled
        setFieldsState(true);
    });
</script>


<?php
get_footer();