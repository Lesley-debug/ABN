<?php
require_once __DIR__ . '/includes/session_bootstrap.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/site_chrome.php';

$category = trim($_GET['category'] ?? '');
$search = trim($_GET['q'] ?? '');

$sql = "SELECT id, title, summary, description, category, media_type, media_path, created_at FROM projects WHERE status='published'";
$params = [];
$types = '';

if ($category !== '') {
    $sql .= ' AND category = ?';
    $types .= 's';
    $params[] = $category;
}

if ($search !== '') {
    $sql .= ' AND (title LIKE ? OR summary LIKE ? OR description LIKE ?)';
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

$projects = [];
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}

$categories = [];
$catResult = $conn->query("SELECT DISTINCT category FROM projects WHERE status='published' AND category IS NOT NULL AND category <> '' ORDER BY category ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

function project_excerpt(array $item): string
{
    $base = trim((string) ($item['summary'] ?: $item['description'] ?: ''));
    if ($base === '') {
        return '';
    }
    return mb_strlen($base) > 180 ? mb_substr($base, 0, 179) . '…' : $base;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php render_site_head(['title' => 'ABN Projects', 'keywords' => 'ABN projects, construction portfolio', 'description' => 'Current and completed ABN construction projects.']); ?>
    <style>
        .projects-hero {
            background: linear-gradient(rgba(0,18,72,.75), rgba(0,18,72,.75)), url('img/project-4.jpg') center/cover no-repeat;
            padding: 5rem 0 3.5rem;
        }
        .project-card { border: 0; border-radius: 14px; box-shadow: 0 12px 24px rgba(0,0,0,.08); overflow: hidden; height: 100%; }
        .project-preview { height: 230px; background: #f2f4f8; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .project-preview img, .project-preview video { width: 100%; height: 100%; object-fit: cover; }
        .chip { display: inline-block; padding: .35rem .8rem; border: 1px solid #d8dce5; border-radius: 999px; text-decoration: none; color: #1f2937; margin-right: .4rem; margin-bottom: .5rem; }
        .chip.active { background: #ff5e15; border-color: #ff5e15; color: #fff; }
    </style>
</head>
<body>
<?php render_site_header(['active_nav' => 'projects', 'active_dropdown' => '', 'show_estimate' => false]); ?>

<section class="projects-hero text-center text-white">
    <div class="container">
        <p class="text-uppercase fs-5 mb-1">ABN Portfolio</p>
        <h2 class="display-5 mb-3 text-white">Current and Completed Projects</h2>
        <p class="mb-0">Residential, commercial, and drawing-backed project documentation.</p>
    </div>
</section>

<section class="container py-5">
    <form method="GET" class="row g-2 mb-4">
        <div class="col-md-8">
            <input type="text" name="q" class="form-control" placeholder="Search projects" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-4 d-grid">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
        <div class="col-12 mt-2">
            <a class="chip <?= $category === '' ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query(['q' => $search])) ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a class="chip <?= $category === $cat ? 'active' : '' ?>" href="?<?= htmlspecialchars(http_build_query(['category' => $cat, 'q' => $search])) ?>"><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="row g-4">
        <?php if (!$projects): ?>
            <div class="col-12"><div class="alert alert-info">No published projects yet.</div></div>
        <?php endif; ?>

        <?php foreach ($projects as $item): ?>
            <div class="col-md-6 col-lg-4">
                <article class="card project-card">
                    <div class="project-preview">
                        <?php if ($item['media_type'] === 'image' && !empty($item['media_path'])): ?>
                            <img src="<?= htmlspecialchars($item['media_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <?php elseif ($item['media_type'] === 'video' && !empty($item['media_path'])): ?>
                            <video controls preload="metadata"><source src="<?= htmlspecialchars($item['media_path']) ?>"></video>
                        <?php elseif ($item['media_type'] === 'drawing' && !empty($item['media_path'])): ?>
                            <div class="text-center">
                                <div class="display-4 text-danger mb-2"><i class="far fa-file-pdf"></i></div>
                                <a href="<?= htmlspecialchars($item['media_path']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Open Drawing</a>
                            </div>
                        <?php else: ?>
                            <div class="text-muted">No media</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="small text-muted mb-1"><?= htmlspecialchars(date('F j, Y', strtotime($item['created_at']))) ?><?= $item['category'] ? ' | ' . htmlspecialchars($item['category']) : '' ?></div>
                        <h3 class="h5"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="mb-0"><?= htmlspecialchars(project_excerpt($item)) ?></p>
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
