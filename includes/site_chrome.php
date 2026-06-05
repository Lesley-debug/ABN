<?php
require_once __DIR__ . '/site_head.php';

if (!function_exists('site_is_active')) {
    function site_is_active(string $current, string $target): string
    {
        return $current === $target ? ' active' : '';
    }
}

if (!function_exists('render_site_header')) {
    function render_site_header(array $options = []): void
    {
        $activeNav = (string) ($options['active_nav'] ?? 'home');
        $activeDropdown = (string) ($options['active_dropdown'] ?? '');
        $showSearch = (bool) ($options['show_search'] ?? true);
        ?>
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <div class="site-header-shell">
            <div class="container-fluid sticky-top px-0">
                <nav class="navbar navbar-expand-lg navbar-dark bg-light py-3 px-4">
                    <a href="index.php" class="navbar-brand p-0">
                        <h1 class="text-secondary display-6"><img src="img/logo.png" alt="Logo" width="50" style="margin-right: 15px;">ABN Construction</h1>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto pt-2 pt-lg-0">
                            <a href="index.php" class="nav-item nav-link<?= site_is_active($activeNav, 'home') ?>">Home</a>
                            <a href="about.php" class="nav-item nav-link<?= site_is_active($activeNav, 'about') ?>">About</a>
                            <a href="service.php" class="nav-item nav-link<?= site_is_active($activeNav, 'services') ?>">Services</a>
                            <a href="projects.php" class="nav-item nav-link<?= site_is_active($activeNav, 'projects') ?>">Projects</a>
                            <div class="nav-item dropdown<?= site_is_active($activeNav, 'pages') ?>">
                                <a href="#" role="button" class="nav-link dropdown-toggle text-dark<?= site_is_active($activeNav, 'pages') ?>" data-bs-toggle="dropdown">Pages</a>
                                <div class="dropdown-menu m-lg-0">
                                    <a href="feature.php" class="dropdown-item<?= site_is_active($activeDropdown, 'feature') ?>">Our Features</a>
                                    <a href="service1.php" class="dropdown-item<?= site_is_active($activeDropdown, 'service1') ?>">Service1</a>
                                    <a href="blog.php" class="dropdown-item<?= site_is_active($activeDropdown, 'blog') ?>">Our Blog</a>
                                    <a href="team.php" class="dropdown-item<?= site_is_active($activeDropdown, 'team') ?>">Our Team</a>
                                    <a href="solar-team.php" class="dropdown-item<?= site_is_active($activeDropdown, 'solar-team') ?>">Solar Team</a>
                                    <a href="Files/MY%20ESTIMATE.pdf" class="dropdown-item" target="_blank" rel="noopener">Estimate PDF</a>
                                    <a href="testimonial.php" class="dropdown-item<?= site_is_active($activeDropdown, 'testimonial') ?>">Testimonial</a>
                                </div>
                            </div>
                            <a href="contact.php" class="nav-item nav-link<?= site_is_active($activeNav, 'contact') ?>">Contact</a>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="admin/dashboard.php" class="nav-item nav-link">Admin Dashboard</a>
                                <a href="includes/logout.php" class="nav-item nav-link">Logout</a>
                            <?php elseif (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
                                <a href="user/dashboard.php" class="nav-item nav-link">My Dashboard</a>
                                <a href="includes/logout.php" class="nav-item nav-link">Logout</a>
                            <?php else: ?>
                                <a href="login.php" class="nav-item nav-link">Login</a>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center flex-nowrap pt-3 pt-lg-0 ms-lg-2">
                            <?php if ($showSearch): ?>
                                <button class="btn btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fas fa-search"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                </nav>
            </div>
        </div>

        <?php if ($showSearch): ?>
            <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content rounded-0">
                        <div class="modal-header">
                            <h4 class="modal-title mb-0" id="searchModalLabel">Search by keyword</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body d-flex align-items-center">
                            <form class="input-group w-75 mx-auto d-flex" method="get" action="search.php">
                                <input type="search" class="form-control p-3" name="q" placeholder="Search the site" required>
                                <button type="submit" id="search-icon-1" class="input-group-text p-3 border-0 bg-primary text-white"><i class="fa fa-search"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif;
    }
}

if (!function_exists('render_site_footer')) {
    function render_site_footer(array $options = []): void
    {
        $showBackToTop = (bool) ($options['show_back_to_top'] ?? true);
        ?>
        <div class="container-fluid footer py-5 wow fadeIn" data-wow-delay="0.2s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <div class="footer-item">
                                <h4 class="text-white mb-4">Newsletter</h4>
                                <p class="mb-3">ABN Real Estate Construction delivers trusted building and property development services across Cameroon, with a focus on quality workmanship, safety, and timely delivery.</p>
                                <div class="position-relative mx-auto">
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <a href="admin/dashboard.php" class="btn btn-secondary py-2 px-4">Admin Dashboard</a>
                                    <?php elseif (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user'): ?>
                                        <a href="user/dashboard.php" class="btn btn-secondary py-2 px-4 me-2">My Dashboard</a>
                                        <a href="includes/logout.php" class="btn btn-outline-light py-2 px-4">Logout</a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-secondary py-2 px-4">Login / Sign Up</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Explore</h4>
                            <a href="index.php"><i class="fas fa-angle-right me-2"></i> Home</a>
                            <a href="service.php"><i class="fas fa-angle-right me-2"></i> Services</a>
                            <a href="about.php"><i class="fas fa-angle-right me-2"></i> About Us</a>
                            <a href="projects.php"><i class="fas fa-angle-right me-2"></i> Latest Projects</a>
                            <a href="testimonial.php"><i class="fas fa-angle-right me-2"></i> Testimonial</a>
                            <a href="team.php"><i class="fas fa-angle-right me-2"></i> Our Team</a>
                            <a href="solar-team.php"><i class="fas fa-angle-right me-2"></i> Solar Team</a>
                            <a href="Files/MY%20ESTIMATE.pdf" target="_blank" rel="noopener"><i class="fas fa-angle-right me-2"></i> Estimate PDF</a>
                            <a href="contact.php"><i class="fas fa-angle-right me-2"></i> Contact Us</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Our Services</h4>
                            <a href="service.php#general-construction"><i class="fas fa-angle-right me-2"></i> General Construction</a>
                            <a href="service.php#property-maintenance"><i class="fas fa-angle-right me-2"></i> Property Maintenance</a>
                            <a href="service.php#project-management"><i class="fas fa-angle-right me-2"></i> Project Management</a>
                            <a href="service.php#virtual-design-build"><i class="fas fa-angle-right me-2"></i> Virtual Design & Build</a>
                            <a href="service.php#residential-construction"><i class="fas fa-angle-right me-2"></i> Residential Construction</a>
                            <a href="service.php#preconstruction"><i class="fas fa-angle-right me-2"></i> Preconstruction</a>
                            <a href="service.php#design-build"><i class="fas fa-angle-right me-2"></i> Design Build</a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-xl-3">
                        <div class="footer-item d-flex flex-column">
                            <h4 class="text-white mb-4">Contact Info</h4>
                            <a href="https://maps.google.com/?q=Citychemist+Bamenda+Cameroon" target="_blank" rel="noopener"><i class="fa fa-map-marker-alt me-2"></i> Citychemist, Bamenda, Cameroon</a>
                            <a href="mailto:achablaise7@gmail.com"><i class="fas fa-envelope me-2"></i> achablaise7@gmail.com</a>
                            <a href="tel:+237671697256"><i class="fas fa-phone me-2"></i> (+237) 6 71 69 72 56</a>
                            <a href="tel:+237671697256" class="mb-3"><i class="fas fa-print me-2"></i> (+237) 6 71 69 72 56</a>
                            <div class="footer-btn d-flex align-items-center">
                                <a class="btn btn-secondary btn-md-square me-2" href="https://www.facebook.com/" target="_blank" rel="noopener"><i class="fab fa-facebook-f text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square me-2" href="https://x.com/" target="_blank" rel="noopener"><i class="fab fa-twitter text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square me-2" href="https://www.instagram.com/" target="_blank" rel="noopener"><i class="fab fa-instagram text-white"></i></a>
                                <a class="btn btn-secondary btn-md-square me-0" href="https://www.linkedin.com/" target="_blank" rel="noopener"><i class="fab fa-linkedin-in text-white"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid copyright py-4">
            <div class="container">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center text-md-start mb-md-0">
                        <span class="text-body"><span class="border-bottom text-white"><i class="fas fa-copyright text-light me-2"></i>ABN Construction</span>, All rights reserved.</span>
                    </div>
                    <div class="col-md-6 text-center text-md-end text-body">
                        Designed By <a class="border-bottom text-white" href="https://lesleydesigns.wuaze.com/" target="_blank" rel="noopener">Lesley Designs</a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($showBackToTop): ?>
            <a href="#" class="btn btn-secondary btn-lg-square back-to-top"><i class="fa fa-arrow-up"></i></a>
        <?php endif;
    }
}

if (!function_exists('render_site_scripts')) {
    function render_site_scripts(): void
    {
        ?>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="lib/wow/wow.min.js"></script>
        <script src="lib/easing/easing.min.js"></script>
        <script src="lib/waypoints/waypoints.min.js"></script>
        <script src="lib/counterup/counterup.min.js"></script>
        <script src="lib/owlcarousel/owl.carousel.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/js/lightbox.min.js"></script>
        <script src="js/main.js?v=20260305a"></script>
        <?php
    }
}
