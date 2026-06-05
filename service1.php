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
<?php render_site_header(['active_nav' => 'pages', 'active_dropdown' => 'service1', 'show_estimate' => true]); ?>

<!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Our Services</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Pages</a></li>
                    <li class="breadcrumb-item active text-secondary">Service</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Service Details Start -->
        <div class="container-fluid py-5">
            <div class="container py-5" id="service-details">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 900px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Detailed Services</p>
                    <h2 class="display-5 text-capitalize mb-3">What ABN Delivers For Every Project</h2>
                    <p class="mb-0">Select a service from the Services page and you will land directly in the matching section below.</p>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
                        <div class="border rounded p-4 h-100" id="general-construction">
                            <h4 class="mb-3">General Construction</h4>
                            <p>ABN handles complete construction delivery from site preparation and structural works to roofing, electrical, plumbing, and final finishes. We coordinate engineers, technicians, and suppliers under one timeline so clients avoid delays and cost overruns.</p>
                            <p>For residential, commercial, and mixed-use projects in Cameroon, we apply practical build methods, strict material checks, and clear reporting at every milestone.</p>
                            <ul class="mb-0">
                                <li>Turnkey execution from foundation to handover.</li>
                                <li>Quality control and site safety supervision.</li>
                                <li>Progress tracking with realistic delivery plans.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.2s">
                        <div class="border rounded p-4 h-100" id="property-embellishments">
                            <h4 class="mb-3">Property Embellishments</h4>
                            <p>ABN upgrades existing properties with modern finishes that improve both appearance and market value. We renovate interiors and exteriors, refresh facades, optimize space usage, and align style choices with local climate and long-term durability.</p>
                            <p>These works are designed for owners who want better rental performance, stronger visual identity, or a full repositioning of their property.</p>
                            <ul class="mb-0">
                                <li>Interior and exterior renovation packages.</li>
                                <li>Facade, paint, ceiling, and flooring enhancement.</li>
                                <li>Value-focused improvements for resale or leasing.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.3s">
                        <div class="border rounded p-4 h-100" id="project-management">
                            <h4 class="mb-3">Project Management</h4>
                            <p>ABN provides end-to-end project management to keep scope, budget, and schedule aligned. We organize approvals, procurement, contractor coordination, and risk control while giving clients transparent visibility into decisions and progress.</p>
                            <p>Our management approach is built to prevent rework, enforce accountability, and protect delivery targets from start to closeout.</p>
                            <ul class="mb-0">
                                <li>Planning, sequencing, and milestone governance.</li>
                                <li>Budget monitoring and contractor coordination.</li>
                                <li>Issue resolution and schedule recovery actions.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.4s">
                        <div class="border rounded p-4 h-100" id="virtual-design-build">
                            <h4 class="mb-3">Virtual Design &amp; Build</h4>
                            <p>ABN uses digital planning workflows to help clients visualize projects before execution. We review layouts, functionality, finish options, and construction sequencing early so decisions are made with confidence and fewer changes happen on site.</p>
                            <p>This service is ideal for clients who want to reduce uncertainty, speed approvals, and control project outcomes before major spend begins.</p>
                            <ul class="mb-0">
                                <li>Pre-build visualization and decision support.</li>
                                <li>Design-to-construction coordination.</li>
                                <li>Reduced change orders during execution.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.45s">
                        <div class="border rounded p-4 h-100" id="residential-construction">
                            <h4 class="mb-3">Residential Construction</h4>
                            <p>ABN develops residential projects from individual homes to multi-unit compounds with a focus on comfort, structural safety, and efficient space planning. We align each build with the client lifestyle, budget, and long-term property value goals.</p>
                            <p>Our residential teams handle foundations, block work, roofing, finishing, and utilities with clear quality checkpoints at every phase.</p>
                            <ul class="mb-0">
                                <li>Custom home and residential block construction.</li>
                                <li>Durable finishes adapted to local conditions.</li>
                                <li>Practical layouts for family living and rental use.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.5s">
                        <div class="border rounded p-4 h-100" id="preconstruction">
                            <h4 class="mb-3">Preconstruction</h4>
                            <p>ABN performs detailed preconstruction studies to de-risk projects before ground is broken. We define scope clearly, estimate costs realistically, map procurement needs, and establish a practical execution strategy aligned with your financing and delivery window.</p>
                            <p>Strong preconstruction planning reduces avoidable surprises and creates predictable project performance.</p>
                            <ul class="mb-0">
                                <li>Scope definition and feasibility planning.</li>
                                <li>Cost estimation and procurement strategy.</li>
                                <li>Site readiness and implementation roadmap.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.6s">
                        <div class="border rounded p-4 h-100" id="design-build">
                            <h4 class="mb-3">Design Build</h4>
                            <p>With ABN design-build delivery, design and construction are managed as one integrated process. This reduces communication gaps, accelerates approvals, and keeps technical decisions tied directly to cost and schedule realities.</p>
                            <p>Clients benefit from a single responsible team focused on faster execution, tighter coordination, and measurable quality outcomes.</p>
                            <ul class="mb-0">
                                <li>Single-team accountability across all phases.</li>
                                <li>Faster delivery through integrated workflows.</li>
                                <li>Consistent quality from concept to handover.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Service Details End -->

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

        <!-- Testimonial Start -->
        <div class="container-fluid testimonial py-5">
            <div class="container py-5">
                <div class="text-center mx-auto pb-5 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 800px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Testimonials</p>
                    <h2 class="display-4 text-capitalize mb-3">Our clients reviews.</h2>
                </div>
                <div class="owl-carousel testimonial-carousel wow fadeInUp" data-wow-delay="0.4s">
                    <div class="testimonial-item bg-light p-4">
                        <div class="position-relative">
                            <i class="fa fa-quote-right fa-2x text-primary position-absolute" style="bottom: 30px; right: 0;"></i>
                            <div class="mb-4 pb-4 border-bottom border-secondary">
                                <p class="mb-0">ABN Real Estate Construction focuses on reliable execution, transparent communication, and high construction standards from start to finish.
                                </p>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap">
                                <div class="me-4">
                                    <img src="img/testimonial-1.jpg" class="img-fluid w-100" style="width: 100px; height: 100px;" alt="">
                                </div>
                                <div class="d-block">
                                    <h4 class="text-dark">Client Name</h4>
                                    <p class="m-0 pb-3">Profession</p>
                                    <div class="d-flex text-secondary pe-5">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item bg-light p-4">
                        <div class="position-relative">
                            <i class="fa fa-quote-right fa-2x text-primary position-absolute" style="bottom: 30px; right: 0;"></i>
                            <div class="mb-4 pb-4 border-bottom border-secondary">
                                <p class="mb-0">ABN Real Estate Construction focuses on reliable execution, transparent communication, and high construction standards from start to finish.
                                </p>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap">
                                <div class="me-4">
                                    <img src="img/testimonial-2.jpg" class="img-fluid w-100" style="width: 100px; height: 100px;" alt="">
                                </div>
                                <div class="d-block">
                                    <h4 class="text-dark">Client Name</h4>
                                    <p class="m-0 pb-3">Profession</p>
                                    <div class="d-flex text-secondary pe-5">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star text-muted"></i>
                                        <i class="fas fa-star text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-item bg-light p-4">
                        <div class="position-relative">
                            <i class="fa fa-quote-right fa-2x text-primary position-absolute" style="bottom: 30px; right: 0;"></i>
                            <div class="mb-4 pb-4 border-bottom border-secondary">
                                <p class="mb-0">ABN Real Estate Construction focuses on reliable execution, transparent communication, and high construction standards from start to finish.
                                </p>
                            </div>
                            <div class="d-flex align-items-center flex-nowrap">
                                <div class="me-4">
                                    <img src="img/testimonial-3.jpg" class="img-fluid w-100" style="width: 100px; height: 100px;" alt="">
                                </div>
                                <div class="d-block">
                                    <h4 class="text-dark">Client Name</h4>
                                    <p class="m-0 pb-3">Profession</p>
                                    <div class="d-flex text-secondary pe-5">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Testimonial End -->
        <?php render_site_footer(); ?>

        <?php render_site_scripts(); ?>
    </body>

</html>
