<?php
/* Template Name: Forgot Password */
if (!defined('ABSPATH')) exit;

$submitted = false;
$generic_msg = 'If an account exists with that email, we\'ve sent a reset link. Please check your inbox.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fp_nonce']) && wp_verify_nonce($_POST['fp_nonce'], 'fp_nonce')) {
    // Optional: simple rate limit (IP-based)
    $ip_key = 'fp_rl_' . md5($_SERVER['REMOTE_ADDR']);
    if (get_transient($ip_key)) {
        $submitted = true; // still show generic success
    } else {
        set_transient($ip_key, 1, MINUTE_IN_SECONDS * 2);

        $login = sanitize_text_field($_POST['user_login'] ?? '');
        if ($login) {
            // Accept email or username, but never disclose validity
            $user = strpos($login, '@') !== false
                ? get_user_by('email', $login)
                : get_user_by('login', $login);

            if ($user && !is_wp_error($user)) {
                $key = get_password_reset_key($user);
                if (!is_wp_error($key)) {
                    $reset_url = add_query_arg([
                        'key'   => rawurlencode($key),
                        'login' => rawurlencode($user->user_login),
                    ], home_url('/reset-password/'));

                    // Build a simple email (customize HTML as you like)
                    $subject = sprintf(__('Reset your password on %s'), get_bloginfo('name'));
                    $message = "Hello,\n\nWe received a request to reset your password.\n\nReset link:\n$reset_url\n\nIf you didn’t request this, you can ignore this email.";
                    wp_mail($user->user_email, $subject, $message);
                }
            }
        }
        $submitted = true;
    }
}

get_header(); ?>
<section id="hero">
    <div class="faux-bg-img">
        <?= wp_get_attachment_image(1171, 'full'); ?>
    </div>
    <div class="container">
        <h1 class="title">
            Forgot Your <i>Password?</i>
        </h1>
        <div class="contact-details-wrapper">
            <div class="contact-details">
                <h2 class="subheading">Request a Reset</h2>
                <p>Enter your email address below and we'll send you instructions for resetting.</p>

                <?php if ($submitted): ?>
					<p><?= esc_html($generic_msg); ?></p>
				  <?php else: ?>
					<form method="post">
					  <?php wp_nonce_field('fp_nonce', 'fp_nonce'); ?>
					  <label for="user_login">Email address</label>
					  <input type="email" id="user_login" name="user_login" required>
					  <button type="submit">Send reset link</button>
					</form>
				  <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php get_footer();