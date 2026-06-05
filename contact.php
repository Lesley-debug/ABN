<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/site_chrome.php';

if (empty($_SESSION['contact_form_csrf'])) {
    $_SESSION['contact_form_csrf'] = bin2hex(random_bytes(32));
}
$contactCsrf = $_SESSION['contact_form_csrf'];

$cookieSecure = function_exists('isHttpsRequest') ? isHttpsRequest() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie('contact_form_csrf', $contactCsrf, [
    'expires' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $cookieSecure,
    'httponly' => false,
    'samesite' => 'Lax',
]);
?>
<!DOCTYPE html>
<html lang="en">

    <head>
    <?php render_site_head(['title' => 'ABN Construction - Building Construction Website Template', 'keywords' => '', 'description' => '']); ?>
</head>

    <body>
<?php render_site_header(['active_nav' => 'contact', 'active_dropdown' => '', 'show_estimate' => true]); ?>

<!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Contact Us</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Pages</a></li>
                    <li class="breadcrumb-item active text-secondary">Contact</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Contact Start -->
        <div class="container-fluid contact bg-light py-5">
            <div class="container py-5">
                <div class="text-center mx-auto pb-5" style="max-width: 820px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Let’s Connect</p>
                    <h2 class="display-4 text-capitalize mb-3">Send Your Message</h2>
                    <p class="mb-0">Send us your project details and the ABN team will get back to you with guidance, estimates, and next steps.</p>
                </div>

                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-7 wow fadeInLeft" data-wow-delay="0.2s">
                        <div class="contact-form-shell">
                            <p class="mb-0">Send us your project details and the ABN team will get back to you with guidance, estimates, and next steps.</p>
                            <div id="contact-status" class="my-3"></div>
                            <form id="contact-form" action="includes/contact_submit.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($contactCsrf) ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <input type="text" class="form-control contact-input" id="name" name="name" placeholder="Your Name" required>
                                            <label for="name">Full Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <input type="email" class="form-control contact-input" id="email" name="email" placeholder="Your Email" required>
                                            <label for="email">Email Address</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <input type="tel" class="form-control contact-input" id="phone" name="phone" placeholder="Phone">
                                            <label for="phone">Phone Number</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <input type="text" class="form-control contact-input" id="project" name="project" placeholder="Project">
                                            <label for="project">Project Type</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <input type="text" class="form-control contact-input" id="subject" name="subject" placeholder="Subject" required>
                                            <label for="subject">Subject</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-floating border border-secondary rounded-2">
                                            <textarea class="form-control contact-input" placeholder="Leave a message here" id="message" name="message" style="height: 170px" required></textarea>
                                            <label for="message">Message</label>
                                        </div>
                                    </div>
                                    <div class="col-12 contact-submit-wrap">
                                        <button type="submit" class="btn btn-primary w-100 py-3">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-5 wow fadeInRight" data-wow-delay="0.4s">
                        <div class="contact-side-card mb-4">
                            <h4 class="mb-3">Contact Information</h4>
                            <div class="contact-mini-list">
                                <p><i class="fas fa-map-marker-alt text-secondary me-2"></i>Citychemist, Bamenda, Cameroon</p>
                                <p><i class="fas fa-envelope text-secondary me-2"></i>achablaise7@gmail.com</p>
                                <p><i class="fa fa-phone-alt text-secondary me-2"></i>(+237) 6 71 69 72 56</p>
                                <p class="mb-0"><i class="fas fa-clock text-secondary me-2"></i>Mon - Sat 8:00 - 17:30</p>
                            </div>
                        </div>
                        <div class="contact-map h-100 w-100">
                            <iframe 
                                class="h-100 w-100"
                                style="height: 360px;"
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3975.720274181564!2d10.149104474972988!3d5.963148794214425!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x10610df2b018fc1f%3A0x2e5b6c7e8ec3dd0!2sCity%20Chemist%20Roundabout!5e0!3m2!1sen!2scm!4v1730908500000!5m2!1sen!2scm"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->
        <?php render_site_footer(); ?>

        <?php render_site_scripts(); ?>
    </body>

</html>
