<?php
/* Template Name: Registration */
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
        <h1 class="title">
            Create an <i>Account</i>
        </h1>
        <div class="contact-details-wrapper">
            <div class="contact-details">
                <h2 class="subheading">Enter Your Details</h2>
                <p>Enter your desired details in the form below to create your account.</p>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Sanitize input
                    $username = sanitize_user($_POST['username']);
                    $email = sanitize_email($_POST['email']);
                    $password = $_POST['password']; 
                    $password_confirmation = $_POST['password_confirmation']; // The confirmation password

                    // Validate the fields
                    if (empty($username) || empty($email) || empty($password) || empty($password_confirmation)) {
                        echo '<p style="color: red;">All fields are required.</p>';
                    } elseif ($password !== $password_confirmation) {
                        echo '<p style="color: red;">Passwords do not match. Please try again.</p>';
                    } else {
                        // Check if username or email is already registered
                        if (username_exists($username) || email_exists($email)) {
                            echo '<p style="color: red;">Username or email already exists.</p>';
                        } else {
                            // Prepare user data for insertion
                            $user_data = array(
                                'user_login' => $username,
                                'user_email' => $email,
                                'user_pass' => $password,  // The password entered by the user
                                'role' => 'subscriber', // You can define the role here
                            );

                            // Insert the new user into the database
                            $user_id = wp_insert_user($user_data);

                            // On successful registration
                            if (!is_wp_error($user_id)) {
                                echo '<p style="color: green;">Registration successful! You can now <a href="/user-login">log in</a>.</p>';
                            } else {
                                echo '<p style="color: red;">Error: ' . $user_id->get_error_message() . '</p>';
                            }
                        }
                    }
                }
                ?>
                <form method="post">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" required>

                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>

                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>

                    <label for="password_confirmation">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required>

                    <input type="submit" value="Register">
                </form>
            </div>
        </div>
    </div>
</section>

<?php
get_footer();
?>
