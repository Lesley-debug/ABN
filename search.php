<?php
declare(strict_types=1);

$query = trim((string)($_GET['q'] ?? ''));
$pages = [
    ['title' => 'Home', 'url' => 'index.php'],
    ['title' => 'About Us', 'url' => 'about.php'],
    ['title' => 'Services', 'url' => 'service.php'],
    ['title' => 'Service Details', 'url' => 'service1.php'],
    ['title' => 'Projects', 'url' => 'projects.php'],
    ['title' => 'Blog', 'url' => 'blog.php'],
    ['title' => 'Team', 'url' => 'team.php'],
    ['title' => 'Testimonials', 'url' => 'testimonial.php'],
    ['title' => 'Features', 'url' => 'feature.php'],
    ['title' => 'Contact', 'url' => 'contact.php'],
];

function findSnippet(string $text, string $query): string
{
    $clean = preg_replace('/\s+/', ' ', trim($text)) ?? '';
    if ($clean === '' || $query === '') {
        return '';
    }

    $lowerText = mb_strtolower($clean);
    $lowerQuery = mb_strtolower($query);
    $pos = mb_stripos($lowerText, $lowerQuery);

    if ($pos === false) {
        return mb_substr($clean, 0, 180) . (mb_strlen($clean) > 180 ? '...' : '');
    }

    $start = max(0, $pos - 70);
    $snippet = mb_substr($clean, $start, 200);
    if ($start > 0) {
        $snippet = '...' . $snippet;
    }
    if ($start + 200 < mb_strlen($clean)) {
        $snippet .= '...';
    }

    return $snippet;
}

$results = [];
if ($query !== '') {
    foreach ($pages as $page) {
        $path = __DIR__ . '/' . $page['url'];
        if (!is_file($path)) {
            continue;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            continue;
        }

        $text = strip_tags($raw);
        $haystack = mb_strtolower($page['title'] . ' ' . $text);
        $needle = mb_strtolower($query);

        if (mb_stripos($haystack, $needle) === false) {
            continue;
        }

        $results[] = [
            'title' => $page['title'],
            'url' => $page['url'],
            'snippet' => findSnippet($text, $query),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Search Results - ABN Construction</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="icon" type="image/png" href="img/logo.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="mb-4">
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Back to Home</a>
        </div>
        <h2 class="mb-3">Search Results</h2>
        <form class="row g-2 mb-4" method="get" action="search.php">
            <div class="col-md-9">
                <input type="search" class="form-control" name="q" placeholder="Search the site" value="<?= htmlspecialchars($query) ?>" required>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
        </form>

        <?php if ($query === ''): ?>
            <div class="alert alert-info">Enter a keyword to search pages, services, projects, and contact information.</div>
        <?php elseif (count($results) === 0): ?>
            <div class="alert alert-warning">No results found for "<strong><?= htmlspecialchars($query) ?></strong>". Try another keyword.</div>
        <?php else: ?>
            <p class="text-muted">Found <?= count($results) ?> result(s) for "<strong><?= htmlspecialchars($query) ?></strong>".</p>
            <div class="list-group">
                <?php foreach ($results as $item): ?>
                    <a class="list-group-item list-group-item-action" href="<?= htmlspecialchars($item['url']) ?>">
                        <h5 class="mb-1"><?= htmlspecialchars($item['title']) ?></h5>
                        <p class="mb-1 text-muted"><?= htmlspecialchars($item['snippet']) ?></p>
                        <small><?= htmlspecialchars($item['url']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
