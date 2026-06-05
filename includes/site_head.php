<?php

if (!function_exists('render_site_head')) {
    function render_site_head(array $options = []): void
    {
        $title = (string) ($options['title'] ?? 'ABN Construction');
        $keywords = (string) ($options['keywords'] ?? '');
        $description = (string) ($options['description'] ?? '');
        ?>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <title><?= htmlspecialchars($title) ?></title>
        <meta content="<?= htmlspecialchars($keywords) ?>" name="keywords">
        <meta content="<?= htmlspecialchars($description) ?>" name="description">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <link href="lib/animate/animate.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.min.css" rel="stylesheet">

        <link rel="icon" type="image/png" href="img/logo.png">
        <link rel="shortcut icon" href="img/logo.png" type="image/png">
        <link rel="apple-touch-icon" href="img/logo.png">

        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="css/style.css?v=20260303g" rel="stylesheet">
        <?php
    }
}
