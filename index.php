<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_chrome.php';

$homeProjects = [];
$projectsQuery = $conn->query(
    "SELECT id, title, summary, description, category, media_type, media_path, created_at
     FROM projects
     WHERE status = 'published'
     ORDER BY created_at DESC
     LIMIT 4"
);
if ($projectsQuery) {
    while ($row = $projectsQuery->fetch_assoc()) {
        $homeProjects[] = $row;
    }
}

$homeBlogPosts = [];
$blogQuery = $conn->query(
    "SELECT id, title, excerpt, content, media_type, media_path, created_at
     FROM blog_posts
     WHERE status = 'published'
     ORDER BY created_at DESC
     LIMIT 3"
);
if ($blogQuery) {
    while ($row = $blogQuery->fetch_assoc()) {
        $homeBlogPosts[] = $row;
    }
}

$homeTeamPhotos = [];
$teamPatterns = [
    'img/team-gallery/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP,AVIF}',
    'img/team-gallery/*.{gif,GIF}'
];
foreach ($teamPatterns as $pattern) {
    $matches = glob($pattern, GLOB_BRACE) ?: [];
    foreach ($matches as $match) {
        if (is_file($match)) {
            $homeTeamPhotos[] = $match;
        }
    }
}
$homeTeamPhotos = array_values(array_unique($homeTeamPhotos));
natsort($homeTeamPhotos);
$homeTeamPhotos = array_values($homeTeamPhotos);

if (!$homeTeamPhotos) {
    $homeTeamPhotos = ['img/boss.jpg', 'img/bless.jpg', 'img/aa.jpg', 'img/ad.jpg'];
}

$homeTeamPhotos = array_slice($homeTeamPhotos, 0, 8);

function home_excerpt(?string $text, int $length = 130): string
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

function home_project_image(array $project, int $index): string
{
    if (($project['media_type'] ?? '') === 'image' && !empty($project['media_path'])) {
        return (string) $project['media_path'];
    }

    $fallbacks = ['img/project-1.jpg', 'img/project-2.jpg', 'img/project-3.jpg', 'img/project-4.jpg'];
    return $fallbacks[$index % count($fallbacks)];
}

function home_blog_image(array $post, int $index): string
{
    if (($post['media_type'] ?? '') === 'image' && !empty($post['media_path'])) {
        return (string) $post['media_path'];
    }

    $fallbacks = ['img/blog-1.jpg', 'img/blog-2.jpg', 'img/blog-3.jpg'];
    return $fallbacks[$index % count($fallbacks)];
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
    <?php render_site_head(['title' => 'ABN Construction - Building Construction Website Template', 'keywords' => '', 'description' => '']); ?>
</head>

    <body>
<?php render_site_header(['active_nav' => 'home', 'active_dropdown' => '', 'show_estimate' => false]); ?>

<!-- Carousel Start -->
        <div class="container-fluid overflow-hidden px-0">
            <div id="carouselId" class="carousel slide" data-bs-ride="carousel">
                <ol class="carousel-indicators fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1s" style="animation-delay: 1s;">
                    <li data-bs-target="#carouselId" data-bs-slide-to="0" class="active" aria-current="true" aria-label="First slide"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="1" aria-label="Second slide"></li>
                    <li data-bs-target="#carouselId" data-bs-slide-to="2" aria-label="Third slide"></li>
                </ol>
                <div class="carousel-inner" role="listbox">
                    <div class="carousel-item active">
                        <img src="img/3.jpeg" class="img-fluid w-100" alt="First slide"/>
                        <div class="carousel-caption">
                            <p class="text-uppercase text-secondary fs-4 mb-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1s" style="animation-delay: 1s;">Construction Business</p>
                            <h1 class="display-1 text-capitalize text-white mb-4 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.3s" style="animation-delay: 1.3s;">We build strong and consistent results.</h1>
                            <p class="mb-5 fs-5 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.5s;">ABN Building Project, also known as ABN Construction Services and ABN Contracting Consultation Services, is a private construction firm operating in Cameroon. 
                            </p>
                            <div class="d-flex justify-content-center">
                                <a class="btn btn-primary d-flex py-3 px-5 me-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="contact.php">Apply Now</a>
                                <a class="btn btn-secondary d-inline-block py-3 px-5 ms-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="blog.php">Read More</a>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/1.avif" class="img-fluid w-100" alt="Second slide"/>
                        <div class="carousel-caption">
                            <p class="text-uppercase text-secondary fs-4 mb-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1s" style="animation-delay: 1s;">Construction Business</p>
                            <h1 class="display-1 text-capitalize text-white mb-4 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.3s" style="animation-delay: 1.3s;">We build strong and consistent results.</h1>
                            <p class="mb-5 fs-5 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.5s;">ABN Real Estate Construction is committed to building homes, commercial spaces, and infrastructure that support growing communities across Cameroon.</p>
                            <div class="d-flex justify-content-center">
                                <a class="btn btn-primary d-flex py-3 px-5 me-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="contact.php">Apply Now</a>
                                <a class="btn btn-secondary d-inline-block py-3 px-5 ms-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="about.php">Read More</a>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="img/2.avif" class="img-fluid w-100" alt="Third slide"/>
                        <div class="carousel-caption">
                            <p class="text-uppercase text-secondary fs-4 mb-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1s" style="animation-delay: 1s;">Construction Business</p>
                            <h1 class="display-1 text-capitalize text-white mb-4 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.3s" style="animation-delay: 1.3s;">We build strong and consistent results.</h1>
                            <p class="mb-5 fs-5 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.5s;">ABN Real Estate Construction is committed to building homes, commercial spaces, and infrastructure that support growing communities across Cameroon.</p>
                            <div class="d-flex justify-content-center">
                                <a class="btn btn-primary d-flex py-3 px-5 me-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="contact.php">Apply Now</a>
                                <a class="btn btn-secondary d-inline-block py-3 px-5 ms-2 flex-shrink-0 fadeInUp animate__animated" data-animation="fadeInUp" data-delay="1.5s" style="animation-delay: 1.7s;" href="about.php">Read More</a>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselId" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon btn-lg-square fadeInLeft animate__animated" aria-hidden="true" data-animation="fadeInLeft" data-delay="1.1s" style="animation-delay: 1.3s;"><i class="fas fa-chevron-left fa-2x"></i></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselId" data-bs-slide="next">
                    <span class="carousel-control-next-icon btn-lg-square fadeInRight animate__animated" aria-hidden="true" data-animation="fadeInRight" data-delay="1.1s" style="animation-delay: 1.3s;"><i class="fas fa-chevron-right fa-2x"></i></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
        <!-- Carousel End -->

        <!-- About Start -->
        <div class="container-fluid about py-5">
            <div class="container py-5">
                <div class="row g-5 align-items-center">
                    <div class="col-xl-6 wow fadeInLeft" data-wow-delay="0.1s">
                        <div class="about-item-image d-flex">
                            <img src="img/4.jpg" class="img-1 img-fluid w-50"  alt="">
                            <img src="img/1.avif" class="img-2 img-fluid w-50"  alt="">
                            <div class="about-item-image-content">
                                <img src="img/main flyer.jpg" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="">
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 wow fadeInRight" data-wow-delay="0.1s">
                        <div class="about-item-content">
                            <p class="text-uppercase text-secondary fs-5 mb-0">WE ARE CONSTRUCTION COMPANY</p>
                            <h2 class="display-4 text-capitalize mb-3">Making your vision come true at the basics.</h2>
                            <p class="mb-4 fs-5">ABN Real Estate Construction is committed to building homes, commercial spaces, and infrastructure that support growing communities across Cameroon.</p>
                            <div class="pb-4 mb-4 border-bottom">
                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="about-item-content-img">
                                            <img src="img/main.jpg" class="img-fluid w-00" style="height: 200px; object-fit: cover;" alt="">

                                        </div>
                                    </div>
                                    <div class="col-lg-8">
                                        <div class="d-flex mb-4">
                                            <div class="text-secondary">
                                                <i class="fas fa-user-shield fa-3x"></i>
                                            </div>
                                            <h4 class="ms-3">Building quality standards</h4>
                                        </div>
                                        <div class="d-flex">
                                            <div class="text-secondary">
                                                <i class="fas fa-users-cog fa-3x"></i>
                                            </div>
                                            <h4 class="ms-3">Certified engineer’s team</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row gy-0 gx-4 justify-content-between pb-4">
                                <div class="col-lg-6">
                                    <p class="text-dark"><i class="fas fa-check text-secondary me-1"></i> 100% Satisfaction</p>
                                    <p class="text-dark"><i class="fas fa-check text-secondary me-1"></i> Trained Employees</p>
                                </div>
                                <div class="col-lg-6">
                                    <p class="text-dark"><i class="fas fa-check text-secondary me-1"></i> Annual Pass Programs</p>
                                    <p class="text-dark mb-0"><i class="fas fa-check text-secondary me-1"></i> Flexible and cost effective</p>
                                </div>
                            </div>
                            <a class="btn btn-secondary d-inline-block py-3 px-5 me-2 flex-shrink-0 wow fadeInUp" data-wow-delay="0.1s" href="projects.php">Discover More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->

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
                            <p class="mb-0">Our expert engineers are highly skilled professionals responsible for the meticulous planning, innovative design, and flawless execution of your construction project.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="feature-item text-center border p-5">
                            <div class="feature-img bg-secondary d-inline-flex p-4">
                                <i class="fas fa-funnel-dollar text-primary fa-5x"></i>
                            </div>
                            <a href="contact.php" class="h4 d-block my-4">Free Estimates</a>
                            <p class="mb-0">To help you begin planning, we offer a free estimate as an initial, no-cost approximation of your project's potential scope, cost, and timeline.</p>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="feature-item text-center border p-5">
                            <div class="feature-img bg-secondary d-inline-flex p-4">
                                <i class="fas fa-tools text-primary fa-5x"></i>
                            </div>
                            <a href="service.php" class="h4 d-block my-4">Quality Materials</a>
                            <p class="mb-0">Our commitment to using only quality materials is a cornerstone of our construction philosophy, ensuring the longevity and integrity of your project.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Features End -->


        <!-- Services Start -->
        <div class="container-fluid service bg-light pb-5">
            <div class="container pb-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Our Services</p>
                    <h2 class="display-4 text-capitalize mb-3">Our services are creative and dependable</h2>
                </div>
                <div class="row g-4">
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/service-1.jpg" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 120px; height: 120px;">
                                    <i class="fas fa-home text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">General Construction</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center ps-4 w-100">
                                    <h4>General Construction</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-home text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/maintenanc1.jpg" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 120px; height: 120px;">
                                    <i class="fas fa-users-cog text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">Property Maintenance</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center justify-content-start ps-4 w-100">
                                    <h4>Property Maintenance</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-users-cog text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/consultant2.png" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 120px; height: 120px;">
                                    <i class="fas fa-hospital-user text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">Project Management</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center justify-content-start ps-4 w-100">
                                    <h4>Project Management</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-hospital-user text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/consultant1.jpg" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 100px; height: 100px;">
                                    <i class="fas fa-file-invoice-dollar text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">Virtual Design & Build</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center justify-content-start ps-4 w-100">
                                    <h4>Virtual Design & Build</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-file-invoice-dollar text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/service-5.jpg" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 100px; height: 100px;">
                                    <i class="fas fa-cogs text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">Preconstruction</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center justify-content-start ps-4 w-100">
                                    <h4>Preconstruction</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-cogs text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="service-item">
                            <div class="service-img">
                                <img src="img/2.avif" class="img-fluid w-100" alt="Image">
                            </div>
                            <div class="service-content text-center p-4">
                                <div class="bg-secondary btn-xl-square mx-auto" style="width: 100px; height: 100px;">
                                    <i class="fas fa-sitemap text-primary fa-4x"></i>
                                </div>
                                <a href="service.php" class="d-block fs-4 my-4">Design Build</a>
                                <p class="text-white mb-4">From foundation to finishing, ABN provides practical construction solutions that match your budget, timeline, and project goals.</p>
                                <a class="btn btn-secondary py-2 px-4" href="service.php">Read More</a>
                            </div>
                            <div class="service-tytle">
                                <div class="d-flex align-items-center justify-content-start ps-4 w-100">
                                    <h4>Design Build</h4>
                                </div>
                                <div class="btn-xl-square bg-secondary p-4" style="width: 80px; height: 80px;">
                                    <i class="fas fa-sitemap text-primary fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 text-center wow fadeInUp" data-wow-delay="0.2s">
                        <a class="btn btn-secondary py-3 px-5 mt-4" href="service.php">More Services</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Services End -->

        <!-- Fact Counter -->
        <div class="container-fluid counter py-5">
            <div class="container py-5">
                <div class="row g-4">
                    <div class="col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="counter-box">
                            <div class="counter-item">
                                <div class="counter-item-style"></div>
                                <div class="counter-item-inner p-5">
                                    <i class="fas fa-thumbs-up fa-4x text-secondary"></i>
                                    <h4 class="text-dark my-4">Completed Projects</h4>
                                    <div class="counter-counting">
                                        <span class="text-secondary fs-2 fw-bold" data-toggle="counter-up">456</span>
                                        <span class="h1 fw-bold text-secondary">+</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="counter-box">
                            <div class="counter-item">
                                <div class="counter-item-style"></div>
                                <div class="counter-item-inner p-5">
                                    <i class="fas fa-users fa-4x text-secondary"></i>
                                    <h4 class="text-dark my-4">Happy Customers</h4>
                                    <div class="counter-counting">
                                        <span class="text-secondary fs-2 fw-bold" data-toggle="counter-up">513</span>
                                        <span class="h1 fw-bold text-secondary">+</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="counter-box">
                            <div class="counter-item">
                                <div class="counter-item-style"></div>
                                <div class="counter-item-inner p-5">
                                    <i class="fas fa-user fa-4x text-secondary"></i>
                                    <h4 class="text-dark my-4">Qualified Engineers</h4>
                                    <div class="counter-counting">
                                        <span class="text-secondary fs-2 fw-bold" data-toggle="counter-up">53</span>
                                        <span class="h1 fw-bold text-secondary">+</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-xl-3 wow fadeInUp" data-wow-delay="0.8s">
                        <div class="counter-box">
                            <div class="counter-item">
                                <div class="counter-item-style"></div>
                                <div class="counter-item-inner p-5">
                                    <i class="fas fa-heart fa-4x text-secondary"></i>
                                    <h4 class="text-dark my-4">Years Experience</h4>
                                    <div class="counter-counting">
                                        <span class="text-secondary fs-2 fw-bold" data-toggle="counter-up">17</span>
                                        <span class="h1 fw-bold text-secondary">+</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 text-center pt-4 wow fadeInUp" data-wow-delay="0.9s">
                        <a class="counter-btn btn btn-secondary py-3 px-5" href="contact.php">Join With Us</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Fact Counter -->

        <!-- Projects Start -->
        <div class="container-fluid project py-5">
            <div class="container py-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Our Projects</p>
                    <h2 class="display-4 text-capitalize mb-3">Recent Completed Projects</h2>
                </div>
                <div class="row g-5">
                    <?php if (!$homeProjects): ?>
                        <div class="col-12">
                            <div class="alert alert-info">No published projects yet. Admin can publish projects from the dashboard.</div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($homeProjects as $index => $project): ?>
                        <?php $delay = ($index % 2 === 0) ? '0.2s' : '0.4s'; ?>
                        <div class="col-lg-6 wow fadeInUp" data-wow-delay="<?= $delay ?>">
                            <div class="project-item">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="project-img">
                                            <img src="<?= htmlspecialchars(home_project_image($project, $index)) ?>" class="img-fluid w-100 pt-3 ps-3" alt="<?= htmlspecialchars($project['title']) ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="project-content mb-4">
                                            <p class="fs-5 text-secondary mb-2"><?= htmlspecialchars($project['category'] ?: 'Project') ?></p>
                                            <a href="projects.php" class="h4"><?= htmlspecialchars($project['title']) ?></a>
                                            <p class="mb-0 mt-3"><?= htmlspecialchars(home_excerpt($project['summary'] ?: $project['description'])) ?></p>
                                        </div>
                                        <a class="btn btn-primary py-2 px-4" href="projects.php">Read More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-12 text-center wow fadeInUp" data-wow-delay="0.2s">
                        <a class="btn btn-secondary py-3 px-5" href="projects.php">More Projects</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Projects End -->

        <!-- Team Start -->
        <div class="container-fluid team pb-5">
            <div class="container pb-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Our Team</p>
                    <h2 class="display-4 text-capitalize mb-3">Expert team members.</h2>
                </div>
                <div class="row g-4">
                    <?php foreach ($homeTeamPhotos as $idx => $photo): ?>
                        <div class="col-sm-6 col-lg-3 wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.2 + (($idx % 4) * 0.2), 1)) ?>s">
                            <div class="team-item border border-primary p-1">
                                <div class="team-border-style-1"></div>
                                <div class="team-border-style-2"></div>
                                <div class="team-border-style-3"></div>
                                <div class="team-border-style-4"></div>
                                <div class="team-img">
                                    <img src="<?= htmlspecialchars($photo) ?>" class="img-fluid w-100 team-photo-img" alt="ABN team member photo <?= $idx + 1 ?>">
                                </div>
                                <div class="text-center border border-top-0 bg-white py-3">
                                    <h4 class="mb-0">ABN Team Member</h4>
                                    <p class="mb-0">Site Crew</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center pt-4 wow fadeInUp" data-wow-delay="0.2s">
                    <a class="btn btn-secondary py-3 px-5" href="team.php">View Full Team Gallery</a>
                </div>
            </div>
        </div>
        <!-- Team End -->

        <!-- Solar Team Highlight Start -->
        <div class="container-fluid solar-highlight py-5">
            <div class="container py-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.2s">
                        <p class="text-uppercase text-secondary fs-5 mb-1">ABN Partner Team</p>
                        <h2 class="display-5 text-capitalize mb-3">Our Solar Panel Team</h2>
                        <p class="mb-4">ABN works with a dedicated solar installation team to deliver safe and reliable panel mounting, wiring, and maintenance services for homes and businesses.</p>
                        <a class="btn btn-secondary py-3 px-5" href="solar-team.php">View Solar Team Photos</a>
                    </div>
                    <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.3s">
                        <div class="row g-3">
                            <div class="col-6">
                                <img src="img/solar-team/IMG-20251110-WA0000.jpg" class="img-fluid solar-highlight-img" alt="ABN solar panel team at work">
                            </div>
                            <div class="col-6">
                                <img src="img/solar-team/IMG-20251110-WA0005.jpg" class="img-fluid solar-highlight-img" alt="ABN solar team member">
                            </div>
                            <div class="col-12">
                                <img src="img/solar-team/IMG-20251110-WA0012.jpg" class="img-fluid solar-highlight-img" alt="ABN solar team project">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Solar Team Highlight End -->

        <!-- Blog Start -->
        <div class="container-fluid blog pb-5">
            <div class="container pb-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">News & Blog</p>
                    <h2 class="display-4 text-capitalize mb-3">Our latest news posts and articles</h2>
                </div>
                <div class="row g-4">
                    <?php if (!$homeBlogPosts): ?>
                        <div class="col-12">
                            <div class="alert alert-info">No published blog posts yet. Admin can publish posts from the dashboard.</div>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($homeBlogPosts as $index => $post): ?>
                        <?php
                            $delays = ['0.2s', '0.4s', '0.6s'];
                            $delay = $delays[$index % count($delays)];
                            $summary = $post['excerpt'] ?: $post['content'];
                        ?>
                        <div class="col-lg-4 wow fadeInUp" data-wow-delay="<?= $delay ?>">
                            <div class="blog-item h-100">
                                <div class="blog-img">
                                    <img src="<?= htmlspecialchars(home_blog_image($post, $index)) ?>" class="img-fluid w-100" alt="<?= htmlspecialchars($post['title']) ?>">
                                </div>
                                <div class="blog-content p-4">
                                    <div class="d-flex justify-content-between mb-3">
                                        <p class="mb-0"><i class="fa fa-calendar-check text-secondary me-1"></i> <?= htmlspecialchars(date('d M Y', strtotime($post['created_at']))) ?></p>
                                        <p class="mb-0"><i class="fa fa-user text-secondary me-1"></i> Admin</p>
                                    </div>
                                    <a href="blog.php" class="h4 d-block mb-4"><?= htmlspecialchars($post['title']) ?></a>
                                    <p class="mb-3"><?= htmlspecialchars(home_excerpt($summary, 95)) ?></p>
                                    <a class="btn btn-secondary py-2 px-4" href="blog.php">Read More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <!-- Blog End -->

        <!-- Testimonial Start -->
        <!-- Testimonial End -->
        <?php render_site_footer(); ?>

        <?php render_site_scripts(); ?>
    </body>

</html>
