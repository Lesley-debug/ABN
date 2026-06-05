<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/site_chrome.php';

$solarPhotos = [];
$patterns = [
    'img/solar-team/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP,AVIF}',
    'img/solar-team/*.{gif,GIF}'
];

foreach ($patterns as $pattern) {
    $matches = glob($pattern, GLOB_BRACE) ?: [];
    foreach ($matches as $match) {
        if (is_file($match)) {
            $solarPhotos[] = $match;
        }
    }
}

$solarPhotos = array_values(array_unique($solarPhotos));
natsort($solarPhotos);
$solarPhotos = array_values($solarPhotos);

if (!$solarPhotos) {
    $solarPhotos = [
        'img/WhatsApp Image 2025-11-08 at 06.49.25_d25ab518.jpg',
        'img/team-1.jpg',
        'img/team-2.jpg',
        'img/team-3.jpg',
        'img/team-4.jpg',
        'img/aa.jpg',
        'img/ad.jpg',
        'img/bless.jpg',
        'img/boss.jpg',
    ];
}

$solarPhotoCount = count($solarPhotos);
$portraitPhotos = [];
$landscapePhotos = [];
foreach ($solarPhotos as $photo) {
    $orientation = 'landscape';
    $size = @getimagesize($photo);
    if (is_array($size)) {
        $width = (int) ($size[0] ?? 0);
        $height = (int) ($size[1] ?? 0);
        if ($width > 0 && $height > 0 && $height > ($width * 1.05)) {
            $orientation = 'portrait';
        }
    }

    if ($orientation === 'portrait') {
        $portraitPhotos[] = $photo;
    } else {
        $landscapePhotos[] = $photo;
    }
}

$heroPhoto = $solarPhotos[0] ?? 'img/solar-team/IMG-20251110-WA0000.jpg';
$heroSlides = array_slice($solarPhotos, 0, 3);
while (count($heroSlides) < 3) {
    $heroSlides[] = $heroPhoto;
}
$headerPhoto = $heroSlides[0];
$introPhotos = array_slice($solarPhotos, 1, 3);
while (count($introPhotos) < 3) {
    $introPhotos[] = $heroPhoto;
}

$solarServices = [
    [
        'icon' => 'fa-solar-panel',
        'title' => 'Panel Installation',
        'description' => 'Safe structural mounting with correct orientation and spacing for strong day-long production.',
    ],
    [
        'icon' => 'fa-bolt',
        'title' => 'Electrical Integration',
        'description' => 'Clean cabling, inverter connection, and system checks that align with site safety practices.',
    ],
    [
        'icon' => 'fa-tools',
        'title' => 'Maintenance & Support',
        'description' => 'Routine inspection, cleaning, and fast response for repairs to keep output stable.',
    ],
];

$solarSteps = [
    ['step' => '01', 'title' => 'Site Assessment', 'text' => 'We evaluate roof or ground conditions, sunlight exposure, and client energy goals.'],
    ['step' => '02', 'title' => 'System Design', 'text' => 'Our team defines panel layout, routing, and installation sequence for each site.'],
    ['step' => '03', 'title' => 'Installation', 'text' => 'Panels, supports, inverter, and wiring are installed with strict on-site safety control.'],
    ['step' => '04', 'title' => 'Testing & Handover', 'text' => 'The system is tested and delivered with guidance on operation and maintenance.'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php render_site_head(['title' => 'ABN Construction - Solar Panel Team', 'keywords' => 'ABN solar team, solar panel installation team, ABN partner team', 'description' => 'Meet the ABN solar panel team working alongside ABN Construction.']); ?>
</head>

<body>
    <?php render_site_header(['active_nav' => 'pages', 'active_dropdown' => 'solar-team', 'show_estimate' => false]); ?>

    <div class="container-fluid solar-page-header" style="background-image: linear-gradient(rgba(0, 18, 72, 0.72), rgba(0, 18, 72, 0.72)), url('<?= htmlspecialchars($headerPhoto) ?>');">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <p class="solar-header-kicker text-uppercase wow fadeInDown" data-wow-delay="0.1s">ABN Construction Partner Unit</p>
                    <h1 class="text-white display-4 mb-3 wow fadeInDown" data-wow-delay="0.2s">ABN Solar Panel Team</h1>
                    <p class="text-white-50 fs-5 mb-4 wow fadeInDown" data-wow-delay="0.3s">On-site experts for panel installation, electrical integration, commissioning, and maintenance support.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4 wow fadeInUp" data-wow-delay="0.35s">
                        <span class="solar-header-chip">Residential</span>
                        <span class="solar-header-chip">Commercial</span>
                        <span class="solar-header-chip">Maintenance</span>
                    </div>
                    <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.4s">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="index.php">Pages</a></li>
                        <li class="breadcrumb-item active text-secondary">Solar Team</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid solar-hero py-5">
        <div class="container py-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7 wow fadeInLeft" data-wow-delay="0.1s">
                    <div class="solar-hero-content h-100">
                        <p class="text-uppercase text-secondary fs-5 mb-2">ABN + Solar Experts</p>
                        <h2 class="display-5 mb-3">Power Projects With Our Dedicated Solar Panel Team</h2>
                        <p class="mb-4">ABN works with a field-ready solar crew handling panel mounting, electrical integration, system setup, and maintenance support for both homes and business sites.</p>
                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <div class="solar-metric-card">
                                <h3><?= htmlspecialchars((string) $solarPhotoCount) ?>+</h3>
                                <p>Team Photos</p>
                            </div>
                            <div class="solar-metric-card">
                                <h3>01</h3>
                                <p>Dedicated Team</p>
                            </div>
                            <div class="solar-metric-card">
                                <h3>24/7</h3>
                                <p>Support Line</p>
                            </div>
                        </div>
                        <a class="btn btn-secondary py-3 px-5" href="contact.php">Start Your Solar Project</a>
                    </div>
                </div>
                <div class="col-lg-5 wow fadeInRight" data-wow-delay="0.2s">
                    <div class="solar-hero-image-wrap h-100">
                        <div id="solarHeroCarousel" class="carousel slide solar-hero-carousel h-100" data-bs-ride="carousel" data-bs-interval="3000">
                            <div class="carousel-indicators">
                                <?php foreach ($heroSlides as $idx => $unused): ?>
                                    <button type="button" data-bs-target="#solarHeroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" <?= $idx === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= $idx + 1 ?>"></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-inner h-100">
                                <?php foreach ($heroSlides as $idx => $heroSlide): ?>
                                    <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($heroSlide) ?>" class="solar-hero-image" alt="ABN solar panel team hero photo <?= $idx + 1 ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#solarHeroCarousel" data-bs-slide="prev" aria-label="Previous slide">
                                <span class="solar-hero-control" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#solarHeroCarousel" data-bs-slide="next" aria-label="Next slide">
                                <span class="solar-hero-control" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-5">
        <div class="container py-5">
            <div class="row g-4 align-items-center pb-4">
                <?php foreach ($introPhotos as $idx => $introPhoto): ?>
                    <div class="col-md-4 wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + ($idx * 0.1), 1)) ?>s">
                        <img src="<?= htmlspecialchars($introPhoto) ?>" class="img-fluid solar-intro-photo" alt="ABN solar team working photo <?= $idx + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mx-auto py-4 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 900px;">
                <p class="text-uppercase text-secondary fs-5 mb-0">What We Handle</p>
                <h2 class="display-5 text-capitalize mb-3">Complete Solar Team Operations</h2>
                <p class="mb-0">Our collaboration model keeps construction and solar execution aligned from planning to handover.</p>
            </div>
            <div class="row g-4 mb-5">
                <?php foreach ($solarServices as $index => $service): ?>
                    <div class="col-lg-4 wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + ($index * 0.1), 1)) ?>s">
                        <div class="solar-service-card h-100">
                            <i class="fas <?= htmlspecialchars($service['icon']) ?> solar-service-icon"></i>
                            <h4><?= htmlspecialchars($service['title']) ?></h4>
                            <p class="mb-0"><?= htmlspecialchars($service['description']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mx-auto pb-4 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 900px;">
                <p class="text-uppercase text-secondary fs-5 mb-0">How We Work</p>
                <h2 class="display-5 text-capitalize mb-3">ABN Solar Delivery Flow</h2>
            </div>
            <div class="row g-4 mb-5">
                <?php foreach ($solarSteps as $index => $step): ?>
                    <div class="col-md-6 col-lg-3 wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + ($index * 0.1), 1)) ?>s">
                        <div class="solar-step-card h-100">
                            <span class="solar-step-badge"><?= htmlspecialchars($step['step']) ?></span>
                            <h5><?= htmlspecialchars($step['title']) ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($step['text']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mx-auto pb-4 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 900px;">
                <p class="text-uppercase text-secondary fs-5 mb-0">Site Gallery</p>
                <h2 class="display-5 text-capitalize mb-3">Our Team In Action</h2>
            </div>
            <?php if ($portraitPhotos): ?>
                <h4 class="solar-gallery-section-title wow fadeInUp" data-wow-delay="0.1s">Portrait Photos</h4>
                <div class="solar-gallery-grid solar-gallery-grid--portrait mb-5">
                    <?php foreach ($portraitPhotos as $index => $photo): ?>
                        <a href="<?= htmlspecialchars($photo) ?>" data-lightbox="abn-solar-team" data-title="ABN Solar Team Portrait Photo <?= $index + 1 ?>" class="solar-gallery-item solar-gallery-item--portrait wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + (($index % 6) * 0.1), 1)) ?>s">
                            <img src="<?= htmlspecialchars($photo) ?>" alt="ABN solar team portrait photo <?= $index + 1 ?>" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($landscapePhotos): ?>
                <h4 class="solar-gallery-section-title wow fadeInUp" data-wow-delay="0.1s">Landscape Photos</h4>
                <div class="solar-gallery-grid solar-gallery-grid--landscape">
                    <?php foreach ($landscapePhotos as $index => $photo): ?>
                        <a href="<?= htmlspecialchars($photo) ?>" data-lightbox="abn-solar-team" data-title="ABN Solar Team Landscape Photo <?= $index + 1 ?>" class="solar-gallery-item solar-gallery-item--landscape wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + (($index % 6) * 0.1), 1)) ?>s">
                            <img src="<?= htmlspecialchars($photo) ?>" alt="ABN solar team landscape photo <?= $index + 1 ?>" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-5 wow fadeInUp" data-wow-delay="0.2s">
                <p class="mb-3">Add your photos to <code>img/solar-team/</code> and they will appear automatically on this page.</p>
                <a class="btn btn-secondary py-3 px-5" href="contact.php">Work With Our Solar Team</a>
            </div>
        </div>
    </div>
    <?php render_site_footer(); ?>

    <?php render_site_scripts(); ?>
</body>

</html>
