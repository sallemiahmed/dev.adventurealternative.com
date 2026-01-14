<?php
/* Template Name: User Login */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();
?>

<section id="hero">
    <div class="faux-bg-img">
        <?= wp_get_attachment_image(1171, 'full'); ?>
    </div>
    <div class="container">
        <h1 class="title">Log <i>In</i></h1>
        <div class="contact-details-wrapper">
            <div class="contact-details">
                <h2 class="subheading">Access Your Account</h2>

                <!-- Message area for real-time feedback -->
                <div id="email-check-message" style="color: red; display: none;"></div>

                <!-- Display custom error messages -->
                <?php
                if (isset($_GET['login']) && $_GET['login'] == 'failed') {
                    echo '<p style="color: red;">Incorrect email or password. Please try again.</p>';
                } elseif (isset($_GET['login']) && $_GET['login'] == 'empty') {
                    echo '<p style="color: red;">Both fields are required. Please try again.</p>';
                }
                ?>

                <!-- Display the login form -->
                <?php
                wp_login_form(array('redirect' => home_url('/account/')));
                ?>

            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Detect when the user leaves the email field
    $('#user_login').on('blur', function() {
        var email = $(this).val(); // Get the email value
        var messageDiv = $('#email-check-message'); // Get the message div

        // Make sure the email field is not empty before sending the request
        if (email.length > 0) {
            // Send AJAX request to check if the email exists in the historical orders
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'check_historical_email',
                    email: email
                },
                success: function(response) {
                    // Access response.data.exists
                    if (response.data.exists) {
                        // Updated message with links
                        messageDiv.html('We see that you\'ve made a booking with us before. Due to our new site rebuild, however, we will need you to <a href="/register/">Register a new account</a>. Please <a href="/enquiries/">Contact Us</a> if you have questions regarding a past order.');
                        messageDiv.show();
                    } else {
                        // Hide the message if no matching email was found
                        messageDiv.hide();
                    }
                }
            });
        } else {
            messageDiv.hide(); // Hide the message if the field is cleared
        }
    });
});
</script>

<?php
get_footer();
?>
