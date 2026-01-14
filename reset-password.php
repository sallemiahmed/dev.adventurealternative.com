<?php
/* Template Name: Reset Password */
if (!defined('ABSPATH')) exit;

// Optional: discourage caching of this sensitive page
nocache_headers();

$status = null;   // null | 'invalid_or_expired' | 'missing_params' | 'mismatch' | 'weak'
$user   = null;

// Get key/login from URL for initial load
$key   = isset($_GET['key'])   ? sanitize_text_field(wp_unslash($_GET['key']))   : '';
$login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

// Validate incoming key/login on first render
if ($key && $login) {
    $user = check_password_reset_key($key, $login); // WP_User | WP_Error
    if (is_wp_error($user)) {
        $status = 'invalid_or_expired';
        $user = null;
    }
} else {
    $status = 'missing_params';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rp_nonce']) && wp_verify_nonce($_POST['rp_nonce'], 'rp_nonce')) {
    $pass1 = isset($_POST['pass1']) ? (string) $_POST['pass1'] : '';
    $pass2 = isset($_POST['pass2']) ? (string) $_POST['pass2'] : '';

    // Re-validate key/login from hidden inputs
    $login = isset($_POST['login']) ? sanitize_text_field(wp_unslash($_POST['login'])) : '';
    $key   = isset($_POST['key'])   ? sanitize_text_field(wp_unslash($_POST['key']))   : '';

    $user = ($key && $login) ? check_password_reset_key($key, $login) : new WP_Error('invalid', 'Invalid request');

    if (!is_wp_error($user)) {
        if ($pass1 !== $pass2) {
            $status = 'mismatch';
        } elseif (strlen($pass1) < 8) {
            // Optional: enforce a simple minimum; adjust to your policy or remove
            $status = 'weak';
        } else {
            // Perform the reset
            reset_password($user, $pass1);

            // Redirect to your custom login with a success flag
            wp_safe_redirect(home_url('/user-login/?reset=success'));
            exit;
        }
    } else {
        $status = 'invalid_or_expired';
    }
}

get_header(); ?>
<section id="hero">
    <div class="faux-bg-img">
        <?= wp_get_attachment_image(1171, 'full'); ?>
    </div>
    <div class="container">
        <h1 class="title">
            Reset Your <i>Password</i>
        </h1>
        <div class="contact-details-wrapper">
            <div class="contact-details">
                <h2 class="subheading">Choose a New Password</h2>
                <p>Enter and confirm your new password below.</p>

                <?php if ($status === 'invalid_or_expired'): ?>
                    <p style="color: red;">That reset link is invalid or has expired. Please <a href="<?= esc_url(home_url('/forgot-password/')); ?>">request a new link</a>.</p>
                <?php elseif ($status === 'missing_params'): ?>
                    <p style="color: red;">We couldn’t validate your request. Please <a href="<?= esc_url(home_url('/forgot-password/')); ?>">start again</a>.</p>
                <?php elseif ($status === 'mismatch'): ?>
                    <p style="color: red;">Passwords didn’t match. Please try again.</p>
                <?php elseif ($status === 'weak'): ?>
                    <p style="color: red;">Please choose a stronger password (at least 8 characters).</p>
                <?php endif; ?>

                <?php if (!$status || $status === 'mismatch' || $status === 'weak'): ?>
                    <form method="post" autocomplete="off">
                        <?php wp_nonce_field('rp_nonce', 'rp_nonce'); ?>

                        <!-- Preserve validated login/key -->
                        <input type="hidden" name="login" value="<?= esc_attr($login); ?>">
                        <input type="hidden" name="key" value="<?= esc_attr($key); ?>">

                        <label for="pass1">New password</label>
                        <input
                            type="password"
                            id="pass1"
                            name="pass1"
                            required
                            minlength="8"
                            autocomplete="new-password"
                            aria-describedby="password-hint"
                        >

                        <label for="pass2">Confirm new password</label>
                        <input
                            type="password"
                            id="pass2"
                            name="pass2"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >

                        <p id="password-hint">Use at least 8 characters. A mix of letters, numbers and symbols is recommended.</p>

                        <button type="submit">Reset password</button>

                        <p style="margin-top: 1rem;"><a href="<?= esc_url(home_url('/user-login/')); ?>">Back to Login</a></p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function(){
    // Simple client-side confirmation check (non-blocking — server still validates)
    var form  = document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', function(e){
        var p1 = document.getElementById('pass1').value;
        var p2 = document.getElementById('pass2').value;
        if (p1 !== p2) {
            e.preventDefault();
            alert('Passwords did not match. Please try again.');
        }
    });
});
</script>

<?php get_footer();
