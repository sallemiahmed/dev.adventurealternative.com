<?php
/**
 * The template for displaying the footer.
 *
 * @package GeneratePress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

	</div>
</div>

<?php
/**
 * generate_before_footer hook.
 *
 * @since 0.1
 */
do_action( 'generate_before_footer' );
$f_options = get_field('footer_g', 59);
$f_content = $f_options['content'];
$f_form_code = $f_options['code'];
$f_socials_r = $f_options['socials'];
$f_awards_r = $f_options['awards'];
$f_address = $f_options['address'];
?>
<div id="floating-box" class="floating-box">
    <div id="ask-question-button" class="ask-question">
        Ask Us a Question...
    </div>
    <div id="form-container" class="form-container">
        <button id="close-form" class="close-btn">&#10005;</button>
		<div class="form-title">
			<p>
				Ask Us a Question...
			</p>
		</div>
		<div class="form-content">
			<p>
				Call us on <a href="tel:+442870831258">028 7083 1258</a> or fill out the form below.
			</p>
		</div>
        <div class="contact-form">
            <?php echo do_shortcode('[contact-form-7 id="7dc75e8" title="Ask Us A Question"]'); ?>
        </div>
    </div>
</div>

<footer id="footer">
	<div class="footer-main">
		<div class="container">
			<div class="footer-content">
				<?= $f_content; ?>
			</div>
			<div class="footer-network">
				<div class="footer-form-wrapper">
					<div class="footer-heading">
						Adventure Newsletter
					</div>
					<div class="footer-subheading">
						Sign up for news on upcoming adventures!
					</div>
					<div class="footer-form">
						<?= do_shortcode($f_form_code); ?>
					</div>
				</div>
				<div class="footer-social-wrapper">
					<div class="footer-heading">
						Follow Us
					</div>
					<div class="footer-subheading">
						See our adventures on the socials.
					</div>
					<div class="social-links">
						<?php foreach( $f_socials_r as $social ){
							echo '<a class="social-link" href="' .  $social['url'] .'">';
								echo '<div class="icon">' . wp_get_attachment_image($social['icon'], 'medium') . '</div>';
								$displayUrl = str_replace(array('https://', 'http://', 'www.'), '', $social['url']);
        						echo $displayUrl;
							echo '</a>';
						} ?>
					</div>
				</div>
			</div>
			<div class="footer-menu">
				<?php if ( has_nav_menu( 'footer-menu' ) ) {
					wp_nav_menu( 
						array(
							'theme_location' => 'footer-menu',
							'container'      => 'nav',
							'container_class'=> 'footer-nav'
						)
					);
				} ?>
			</div>
		</div>
	</div>
	<div class="footer-banner">
		<div class="container">
			<div class="banner-row">
				<div class="banner-badges">
					<?php foreach( $f_awards_r as $award ){
						echo '<div class="badge-wrapper">';
							echo '<p>' . $award['label'] . '</p>';
							echo '<a class="graphic" href="' .  $award['url'] .'">';
								echo wp_get_attachment_image($award['graphic'], 'medium');
							echo '</a>';
						echo '</div>';
					} ?>
				</div>
				<div class="footer-address">
				    <p>© Adventure Alternative Ltd <?php echo date('Y'); ?></p>
					<?= $f_address; ?>
				</div>
			</div>
		</div>
	</div>
</footer>

<?php
/**
 * generate_after_footer hook.
 *
 * @since 2.1
 */
do_action( 'generate_after_footer' );

wp_footer();
?>
<script>
$(document).ready(function(){
	(function ($) {
		$(".countries-grid").owlCarousel({
			margin: 0,
			nav: true,
			loop: true,
			responsiveClass: true,
			responsive: {
				0: {
					items: 1
				},
				600: {
					items: 2
				},
				1000: {
					items: 4
				}
			}
		});
		$(".filter-carousel").owlCarousel({
			margin: 20,
			nav: true,
			loop: true,
			responsiveClass: true,
			responsive: {
				0: {
					items: 2, margin: 5
				},
				767: {
					items: 3
				},
				1000: {
					items: 4
				},
				1240: {
					items: 5
				}
			}
		});
		function initTripCarousel() {
			$(".trips-carousel").owlCarousel({
				margin: 18,
				nav: true,
				loop: true,
				responsiveClass: true,
				responsive: {
					0: { items: 1 },
					600: { items: 2 },
					1000: { items: 3 }
				}
			});
		}
		initTripCarousel();
		function loadCategory(categorySlug) {
			$('.trips-loading-overlay').show();
			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'POST',
				data: {
					action: 'filter_trips',
					category: categorySlug
				},
				success: function(response) {
					$('.trips-carousel').html(response);
					$('.trips-carousel').trigger('destroy.owl.carousel');
					initTripCarousel();
					$('.trips-loading-overlay').hide();
				},
				error: function() {
					$('.trips-loading-overlay').hide();
				}
			});
		}
		if ($('.trips-carousel').length > 0) {
			$('.filter-link').on('click', function(e) {
				e.preventDefault();
				$('.filter-link').removeClass('current');
				$(this).addClass('current');
				var categorySlug = $(this).data('category');
				loadCategory(categorySlug);
			});
			loadCategory('featured-trips');
		}
		var modal = document.getElementById("videoModal");
		var playButton = document.querySelector(".play-button");
		var closeButton = document.getElementsByClassName("close-modal")[0];

		if (playButton) {
			playButton.onclick = function() {
				if (modal) modal.style.display = "block";
			};
		}

		if (closeButton) {
			closeButton.onclick = function() {
				if (modal) modal.style.display = "none";
			};
		}

		window.onclick = function(event) {
			if (event.target == modal) {
				modal.style.display = "none";
			}
		}
		var readMoreBtns = document.querySelectorAll('.read-more-btn');
		readMoreBtns.forEach(function(btn) {
			btn.addEventListener('click', function() {
				var parent = this.parentElement;
				var fullContent = parent.querySelector('.full-content');
				var shortContent = parent.querySelector('.short-content');
				if (fullContent.classList.contains('hidden')) {
					fullContent.classList.remove('hidden');
					shortContent.style.display = 'none';
					this.textContent = 'Read Less';
				} else {
					fullContent.classList.add('hidden');
					shortContent.style.display = 'block';
					this.textContent = 'Read More';
				}
			});
		});

    })(jQuery);
});
document.addEventListener('DOMContentLoaded', function () {
    var askButton = document.getElementById('ask-question-button');
    var formContainer = document.getElementById('form-container');
    var closeButton = document.getElementById('close-form');

    // Show form when the ask button is clicked
    askButton.addEventListener('click', function () {
        formContainer.style.display = 'block';
        askButton.style.display = 'none';
    });

    // Close form when the close button is clicked
    closeButton.addEventListener('click', function () {
        formContainer.style.display = 'none';
        askButton.style.display = 'block';
    });
});
</script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){
	dataLayer.push(arguments);
}
gtag('consent', 'default', {
	'ad_storage': 'denied',
	'ad_user_data': 'denied',
	'ad_personalization': 'denied',
	'analytics_storage': 'denied',
	'functionality_storage': 'denied',
	'personalization_storage': 'denied',
	'security_storage': 'granted'
});
</script>

<script async src="https://www.googletagmanager.com/gtag/js?id=GTM-WB8RMCKV"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}

	gtag('js', new Date());
	gtag('config', 'GTM-WB8RMCKV');
</script>

<!-- Cookie Consent by TermsFeed https://www.TermsFeed.com -->
<script type="text/javascript" src="//www.termsfeed.com/public/cookie-consent/4.1.0/cookie-consent.js" charset="UTF-8"></script>
<script type="text/javascript" charset="UTF-8">
document.addEventListener('DOMContentLoaded', function () {
    // Ensure dataLayer is initialized
    window.dataLayer = window.dataLayer || [];
	dataLayer.push({'event': 'consentUpdate'});
    cookieconsent.run({
        "notice_banner_type": "interstitial",
        "consent_type": "express",
        "palette": "light",
        "language": "en",
        "page_load_consent_levels": ["strictly-necessary"],
        "notice_banner_reject_button_hide": false,
        "preferences_center_close_button_hide": false,
        "page_refresh_confirmation_buttons": false,
        "website_privacy_policy_url": "https://www.adventurealternative.com/policies/website-cookies/",
        "callbacks": {
            "scripts_specific_loaded": (level) => {
                switch (level) {
					case 'targeting':
                        gtag('consent', 'update', {
                            'ad_storage': 'granted',
                            'ad_user_data': 'granted',
                            'ad_personalization': 'granted',
							'security_storage': 'granted'
                        });
						dataLayer.push({'event': 'consentTargetingUpdate'});
                        break;
                    case 'tracking':
                        gtag('consent', 'update', {
                            'analytics_storage': 'granted',
							'security_storage': 'granted'
                        });
						dataLayer.push({'event': 'consentAnalyticsUpdate'});
                        break;
					case 'functionality':
                        gtag('consent', 'update', {
							'functionality_storage': 'granted',
							'personalization_storage': 'granted',
							'security_storage': 'granted'
                        });
						dataLayer.push({'event': 'consentFunctionalityUpdate'});
                        break;
                }
            }
        },
        "callbacks_force": true
    });
});
</script>
<!-- End Cookie Consent by TermsFeed https://www.TermsFeed.com -->
</body>
</html>
