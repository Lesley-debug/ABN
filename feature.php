<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/site_chrome.php';
?>
<!DOCTYPE html>
<html lang="en">

    <head>
    <?php render_site_head(['title' => 'ABN Construction - Building Construction Website Template', 'keywords' => '', 'description' => '']); ?>
</head>

    <body>
<?php render_site_header(['active_nav' => 'pages', 'active_dropdown' => 'feature', 'show_estimate' => true]); ?>

<!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Our Features</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Pages</a></li>
                    <li class="breadcrumb-item active text-secondary">Feature</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Features Start -->
        <div class="container-fluid feature bg-light py-5">
            <div class="container py-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Why Us</p>
                    <h2 class="display-4 text-capitalize mb-3">Why Choose Us</h2>
                </div>
                <div class="row g-4">
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="feature-item text-center border p-5">
                            <div class="feature-img bg-secondary d-inline-flex p-4">
                                <i class="fas fa-city text-primary fa-5x"></i>
                            </div>
                            <a href="about.php" class="h4 d-block my-4">Expert Engineer</a>
                            <p class="mb-0">ABN combines site expertise, strong project planning, and quality materials to deliver dependable results for clients in Cameroon.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="feature-item text-center border p-5">
                            <div class="feature-img bg-secondary d-inline-flex p-4">
                                <i class="fas fa-funnel-dollar text-primary fa-5x"></i>
                            </div>
                            <a href="contact.php" class="h4 d-block my-4">Free Estimates</a>
                            <p class="mb-0">ABN combines site expertise, strong project planning, and quality materials to deliver dependable results for clients in Cameroon.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="feature-item text-center border p-5">
                            <div class="feature-img bg-secondary d-inline-flex p-4">
                                <i class="fas fa-tools text-primary fa-5x"></i>
                            </div>
                            <a href="service.php" class="h4 d-block my-4">Quality Materials</a>
                            <p class="mb-0">ABN combines site expertise, strong project planning, and quality materials to deliver dependable results for clients in Cameroon.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Features End -->
        <?php render_site_footer(); ?>

        <?php render_site_scripts(); ?>
    </body>

</html>
