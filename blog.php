<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_chrome.php';

$typeFilter = $_GET['type'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$allowedTypes = ['all', 'image', 'video', 'drawing'];
if (!in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = 'all';
}

$sql = "SELECT id, title, excerpt, content, media_type, media_path, original_name, created_at FROM blog_posts WHERE status = 'published'";
$params = [];
$types = '';

if ($typeFilter !== 'all') {
    $sql .= ' AND media_type = ?';
    $types .= 's';
    $params[] = $typeFilter;
}

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR excerpt LIKE ? OR content LIKE ?)';
    $types .= 'sss';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= ' ORDER BY created_at DESC LIMIT 24';
$stmt = $conn->prepare($sql);
if ($stmt && $types !== '') {
    $stmt->bind_param($types, ...$params);
}

$posts = [];
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

function shortText(?string $text, int $length = 160): string
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php render_site_head(['title' => 'ABN Blog & Media', 'keywords' => 'ABN blog, ABN media, construction stories', 'description' => 'Architectural drawings, site videos, and project stories from ABN.']); ?>
    <style>
        .hero-media {
            background: linear-gradient(rgba(0, 18, 72, 0.72), rgba(0, 18, 72, 0.72)), url('img/about-bg.png') center/cover no-repeat;
            padding: 5rem 0 3.5rem;
        }
        .filter-chip {
            border: 1px solid #d9dfe8;
            border-radius: 999px;
            padding: .4rem 1rem;
            text-decoration: none;
            color: #001248;
            background: #fff;
            display: inline-block;
            margin: 0 .4rem .6rem 0;
        }
        .filter-chip.active {
            background: #ff5e15;
            border-color: #ff5e15;
            color: #fff;
        }
        .media-card {
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 12px 24px rgba(0,0,0,.08);
            height: 100%;
        }
        .media-card .preview {
            background: #f2f4f8;
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .media-card img,
        .media-card video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pdf-preview {
            font-size: 3rem;
            color: #ff5e15;
        }
        .meta {
            font-size: .85rem;
            color: #667085;
        }
    </style>
</head>
<body>
<?php render_site_header(['active_nav' => 'pages', 'active_dropdown' => 'blog', 'show_estimate' => false]); ?>

<section class="hero-media text-white text-center">
    <div class="container">
        <p class="text-uppercase fs-5 mb-1">ABN Media Hub</p>
        <h2 class="display-5 mb-3 text-white">Architectural Drawings, Site Videos, and Project Stories</h2>
        <p class="mb-0">Client content is now publishable from the admin dashboard.</p>
    </div>
</section>

<section class="container py-5">
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Search posts, drawings, and videos" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4 d-grid">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
        <div class="col-12 mt-2">
            <?php
            $chips = ['all' => 'All', 'image' => 'Images', 'video' => 'Videos', 'drawing' => 'Drawings'];
            foreach ($chips as $key => $label):
                $query = http_build_query(['type' => $key, 'q' => $search]);
            ?>
                <a class="filter-chip <?= $typeFilter === $key ? 'active' : '' ?>" href="?<?= htmlspecialchars($query) ?>"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="row g-4">
        <?php if (!$posts): ?>
            <div class="col-12">
                <div class="alert alert-info">No published posts found yet. Upload content from <a href="admin/dashboard.php">admin dashboard</a>.</div>
            </div>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card media-card">
                    <div class="preview">
                        <?php if ($post['media_type'] === 'image' && !empty($post['media_path'])): ?>
                            <img src="<?= htmlspecialchars($post['media_path']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                        <?php elseif ($post['media_type'] === 'video' && !empty($post['media_path'])): ?>
                            <video controls preload="metadata">
                                <source src="<?= htmlspecialchars($post['media_path']) ?>">
                            </video>
                        <?php elseif ($post['media_type'] === 'drawing' && !empty($post['media_path'])): ?>
                            <div class="text-center">
                                <div class="pdf-preview"><i class="far fa-file-pdf"></i></div>
                                <a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($post['media_path']) ?>" target="_blank" rel="noopener">Open Drawing</a>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">No media</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="meta mb-2"><?= htmlspecialchars(date('F j, Y', strtotime($post['created_at']))) ?> | <?= htmlspecialchars(strtoupper($post['media_type'])) ?></div>
                        <h3 class="h5"><?= htmlspecialchars($post['title']) ?></h3>
                        <?php $summary = $post['excerpt'] ?: $post['content']; ?>
                        <p class="mb-0"><?= htmlspecialchars(shortText($summary)) ?></p>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php render_site_footer(); ?>

<?php render_site_scripts(); ?>
</body>
</html>
