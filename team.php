<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/site_chrome.php';

$teamPhotos = [];
$patterns = [
    'img/team-gallery/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP,AVIF}',
    'img/team-gallery/*.{gif,GIF}'
];

foreach ($patterns as $pattern) {
    $matches = glob($pattern, GLOB_BRACE) ?: [];
    foreach ($matches as $match) {
        if (is_file($match)) {
            $teamPhotos[] = $match;
        }
    }
}

$teamPhotos = array_values(array_unique($teamPhotos));
natsort($teamPhotos);
$teamPhotos = array_values($teamPhotos);

if (!$teamPhotos) {
    $teamPhotos = [
        'img/boss.jpg',
        'img/aa.jpg',
        'img/ad.jpg',
        'img/bless.jpg',
    ];
}

$teamPhotoCount = count($teamPhotos);
$portraitPhotos = [];
$landscapePhotos = [];
foreach ($teamPhotos as $photo) {
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

$heroPhoto = $teamPhotos[0] ?? 'img/boss.jpg';
$heroSlides = array_slice($teamPhotos, 0, 3);
while (count($heroSlides) < 3) {
    $heroSlides[] = $heroPhoto;
}

$introPhotos = array_slice($teamPhotos, 1, 3);
while (count($introPhotos) < 3) {
    $introPhotos[] = $heroPhoto;
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
    <?php render_site_head(['title' => 'ABN Construction - Building Construction Website Template', 'keywords' => '', 'description' => '']); ?>
</head>

    <body>
<?php render_site_header(['active_nav' => 'pages', 'active_dropdown' => 'team', 'show_estimate' => true]); ?>

        <div class="container-fluid solar-page-header" style="background-image: linear-gradient(rgba(0, 18, 72, 0.72), rgba(0, 18, 72, 0.72)), url('<?= htmlspecialchars($heroPhoto) ?>');">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-10 text-center">
                        <p class="solar-header-kicker text-uppercase wow fadeInDown" data-wow-delay="0.1s">ABN Core Team</p>
                        <h1 class="text-white display-4 mb-3 wow fadeInDown" data-wow-delay="0.2s">Our Construction Team</h1>
                        <p class="text-white-50 fs-5 mb-4 wow fadeInDown" data-wow-delay="0.3s">Site-ready professionals delivering reliable work across civil, structural, and finishing operations.</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4 wow fadeInUp" data-wow-delay="0.35s">
                            <span class="solar-header-chip">Field Crew</span>
                            <span class="solar-header-chip">Supervision</span>
                            <span class="solar-header-chip">Site Support</span>
                        </div>
                        <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInUp" data-wow-delay="0.4s">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="index.php">Pages</a></li>
                            <li class="breadcrumb-item active text-secondary">Team</li>
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
                            <p class="text-uppercase text-secondary fs-5 mb-2">ABN Workforce</p>
                            <h2 class="display-5 mb-3">Experienced Team Members Ready For Every Site</h2>
                            <p class="mb-4">Our team handles planning support, on-site execution, safety coordination, and quality delivery across ABN projects.</p>
                            <div class="d-flex flex-wrap gap-3 mb-4">
                                <div class="solar-metric-card">
                                    <h3><?= htmlspecialchars((string) $teamPhotoCount) ?>+</h3>
                                    <p>Team Photos</p>
                                </div>
                                <div class="solar-metric-card">
                                    <h3>01</h3>
                                    <p>Core Team</p>
                                </div>
                                <div class="solar-metric-card">
                                    <h3>100%</h3>
                                    <p>Project Focus</p>
                                </div>
                            </div>
                            <a class="btn btn-secondary py-3 px-5" href="contact.php">Work With Our Team</a>
                        </div>
                    </div>
                    <div class="col-lg-5 wow fadeInRight" data-wow-delay="0.2s">
                        <div class="solar-hero-image-wrap h-100">
                            <div id="teamHeroCarousel" class="carousel slide solar-hero-carousel h-100" data-bs-ride="carousel" data-bs-interval="3200">
                                <div class="carousel-indicators">
                                    <?php foreach ($heroSlides as $idx => $unused): ?>
                                        <button type="button" data-bs-target="#teamHeroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" <?= $idx === 0 ? 'aria-current="true"' : '' ?> aria-label="Slide <?= $idx + 1 ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner h-100">
                                    <?php foreach ($heroSlides as $idx => $heroSlide): ?>
                                        <div class="carousel-item h-100 <?= $idx === 0 ? 'active' : '' ?>">
                                            <img src="<?= htmlspecialchars($heroSlide) ?>" class="solar-hero-image" alt="ABN team hero photo <?= $idx + 1 ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#teamHeroCarousel" data-bs-slide="prev" aria-label="Previous slide">
                                    <span class="solar-hero-control" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#teamHeroCarousel" data-bs-slide="next" aria-label="Next slide">
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
                            <img src="<?= htmlspecialchars($introPhoto) ?>" class="img-fluid solar-intro-photo" alt="ABN team working photo <?= $idx + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mx-auto pb-4 wow fadeInUp" data-wow-delay="0.2s" style="max-width: 900px;">
                    <p class="text-uppercase text-secondary fs-5 mb-0">Team Gallery</p>
                    <h2 class="display-5 text-capitalize mb-3">ABN Team In Action</h2>
                    <p class="mb-0">Upload photos to <code>img/team-gallery/</code> and they appear automatically on this page.</p>
                </div>
                <?php if ($portraitPhotos): ?>
                    <h4 class="solar-gallery-section-title wow fadeInUp" data-wow-delay="0.1s">Portrait Photos</h4>
                    <div class="solar-gallery-grid solar-gallery-grid--portrait mb-5">
                        <?php foreach ($portraitPhotos as $index => $photo): ?>
                            <a href="<?= htmlspecialchars($photo) ?>" data-lightbox="abn-team-gallery" data-title="ABN Team Portrait Photo <?= $index + 1 ?>" class="solar-gallery-item solar-gallery-item--portrait wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + (($index % 6) * 0.1), 1)) ?>s">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="ABN team portrait photo <?= $index + 1 ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($landscapePhotos): ?>
                    <h4 class="solar-gallery-section-title wow fadeInUp" data-wow-delay="0.1s">Landscape Photos</h4>
                    <div class="solar-gallery-grid solar-gallery-grid--landscape">
                        <?php foreach ($landscapePhotos as $index => $photo): ?>
                            <a href="<?= htmlspecialchars($photo) ?>" data-lightbox="abn-team-gallery" data-title="ABN Team Landscape Photo <?= $index + 1 ?>" class="solar-gallery-item solar-gallery-item--landscape wow fadeInUp" data-wow-delay="<?= htmlspecialchars(number_format(0.1 + (($index % 6) * 0.1), 1)) ?>s">
                                <img src="<?= htmlspecialchars($photo) ?>" alt="ABN team landscape photo <?= $index + 1 ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php render_site_footer(); ?>

        <?php render_site_scripts(); ?>
    </body>

</html>
