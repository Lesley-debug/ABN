<?php
require_once __DIR__ . '/../includes/session_bootstrap.php';
enforceAdminIpAllowlist();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../admin-login.php');
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$message = '';
$messageType = 'error';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$allowedExtensions = [
    'image' => ['jpg', 'jpeg', 'png', 'webp'],
    'video' => ['mp4', 'webm', 'mov'],
    'drawing' => ['pdf']
];

$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'video/mp4',
    'video/webm',
    'video/quicktime',
    'application/pdf'
];

function uploadErrorMessage(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE => 'Upload failed: file exceeds server upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE => 'Upload failed: file exceeds form MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL => 'Upload failed: file was only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Upload failed: missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Upload failed: cannot write file to disk.',
        UPLOAD_ERR_EXTENSION => 'Upload failed: blocked by a PHP extension.',
        default => 'Upload failed. Please try again.'
    };
}

function verifyCsrfOrStop(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh and try again.');
    }
}

function uploadMedia(array $file, string $targetDir, array $allowedExtensions, array $allowedMime): array
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => uploadErrorMessage((int) $file['error'])];
    }

    if ($file['size'] > 1024 * 1024 * 1024) {
        return ['ok' => false, 'message' => 'File is too large. Max size is 1GB.'];
    }

    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']) ?: '';

    if (!in_array($mimeType, $allowedMime, true)) {
        return ['ok' => false, 'message' => 'Unsupported file type.'];
    }

    $mediaType = 'none';
    foreach ($allowedExtensions as $type => $extList) {
        if (in_array($extension, $extList, true)) {
            $mediaType = $type;
            break;
        }
    }

    if ($mediaType === 'none') {
        return ['ok' => false, 'message' => 'Unsupported extension.'];
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        return ['ok' => false, 'message' => 'Upload directory cannot be created.'];
    }

    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = rtrim($targetDir, '/') . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'message' => 'Could not save uploaded file.'];
    }

    return [
        'ok' => true,
        'media_type' => $mediaType,
        'media_path' => 'uploads/projects/' . $safeName,
        'original_name' => $originalName,
        'full_path' => $destination
    ];
}

verifyCsrfOrStop();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        if ($projectId > 0) {
            $select = $conn->prepare('SELECT media_path FROM projects WHERE id = ?');
            $select->bind_param('i', $projectId);
            $select->execute();
            $row = $select->get_result()->fetch_assoc();

            $delete = $conn->prepare('DELETE FROM projects WHERE id = ?');
            $delete->bind_param('i', $projectId);

            if ($delete->execute()) {
                if (!empty($row['media_path'])) {
                    $fullPath = __DIR__ . '/../' . $row['media_path'];
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }
                $message = 'Project deleted.';
                $messageType = 'success';
            } else {
                $message = 'Delete failed.';
            }
        } else {
            $message = 'Invalid project id.';
        }
    }

    if ($action === 'create' || $action === 'update') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $removeMedia = isset($_POST['remove_media']) && $_POST['remove_media'] === '1';

        if ($title === '') {
            $message = 'Project title is required.';
        } else {
            $existing = null;
            if ($action === 'update') {
                if ($projectId <= 0) {
                    $message = 'Invalid project selected for update.';
                } else {
                    $getExisting = $conn->prepare('SELECT media_type, media_path, original_name FROM projects WHERE id = ?');
                    $getExisting->bind_param('i', $projectId);
                    $getExisting->execute();
                    $existing = $getExisting->get_result()->fetch_assoc();
                    if (!$existing) {
                        $message = 'Project not found.';
                    }
                }
            }

            if ($message === '') {
                $mediaType = $existing['media_type'] ?? 'none';
                $mediaPath = $existing['media_path'] ?? null;
                $originalName = $existing['original_name'] ?? null;
                $oldPathToDelete = null;

                if ($removeMedia && $action === 'update') {
                    if (!empty($mediaPath)) {
                        $oldPathToDelete = __DIR__ . '/../' . $mediaPath;
                    }
                    $mediaType = 'none';
                    $mediaPath = null;
                    $originalName = null;
                }

                if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload = uploadMedia($_FILES['media'], __DIR__ . '/../uploads/projects', $allowedExtensions, $allowedMime);
                    if (!$upload['ok']) {
                        $message = $upload['message'];
                    } else {
                        if (!empty($mediaPath)) {
                            $oldPathToDelete = __DIR__ . '/../' . $mediaPath;
                        }
                        $mediaType = $upload['media_type'];
                        $mediaPath = $upload['media_path'];
                        $originalName = $upload['original_name'];
                    }
                }

                if ($message === '') {
                    if ($action === 'create') {
                        $createdBy = (int) ($_SESSION['user_id'] ?? 0);
                        $stmt = $conn->prepare('INSERT INTO projects (title, summary, description, category, media_type, media_path, original_name, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bind_param('ssssssssi', $title, $summary, $description, $category, $mediaType, $mediaPath, $originalName, $status, $createdBy);

                        if ($stmt->execute()) {
                            $message = 'Project saved successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Database operation failed. Please try again.';
                        }
                    } else {
                        $stmt = $conn->prepare('UPDATE projects SET title = ?, summary = ?, description = ?, category = ?, media_type = ?, media_path = ?, original_name = ?, status = ? WHERE id = ?');
                        $stmt->bind_param('ssssssssi', $title, $summary, $description, $category, $mediaType, $mediaPath, $originalName, $status, $projectId);

                        if ($stmt->execute()) {
                            if ($oldPathToDelete && is_file($oldPathToDelete)) {
                                @unlink($oldPathToDelete);
                            }
                            $message = 'Project updated successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Database operation failed. Please try again.';
                        }
                    }
                }
            }
        }
    }
}

$editId = (int) ($_GET['edit_id'] ?? 0);
$editingProject = null;
if ($editId > 0) {
    $editStmt = $conn->prepare('SELECT id, title, summary, description, category, media_type, media_path, original_name, status FROM projects WHERE id = ?');
    $editStmt->bind_param('i', $editId);
    $editStmt->execute();
    $editingProject = $editStmt->get_result()->fetch_assoc();
}

$projects = [];
$result = $conn->query('SELECT id, title, category, media_type, status, created_at FROM projects ORDER BY created_at DESC LIMIT 40');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Projects - ABN</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { margin: 0; background: linear-gradient(180deg, #eff4fb 0%, #f9fbff 100%); font-family: "Segoe UI", Arial, sans-serif; color: #12213a; }
        .admin-shell { max-width: 1220px; margin: 28px auto 40px; padding: 0 14px; }
        .admin-layout { display: grid; grid-template-columns: 260px 1fr; gap: 16px; align-items: start; }
        .sidebar { background: #0f1f3b; border-radius: 14px; color: #fff; padding: 16px 14px; box-shadow: 0 12px 28px rgba(5, 18, 45, .2); position: sticky; top: 16px; }
        .brand { font-size: 1rem; font-weight: 700; margin-bottom: 2px; }
        .brand-sub { font-size: .82rem; color: rgba(255,255,255,.75); margin-bottom: 14px; }
        .nav-link-admin { display: block; text-decoration: none; color: rgba(255,255,255,.88); padding: .58rem .72rem; border-radius: 10px; margin-bottom: 6px; border: 1px solid transparent; }
        .nav-link-admin i { margin-right: .45rem; width: 1rem; display: inline-block; }
        .nav-link-admin:hover { color: #fff; background: rgba(255,255,255,.1); }
        .nav-link-admin.active { color: #0f1f3b; background: #fff; font-weight: 600; }
        .admin-head { background: #0f1f3b; color: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 12px 28px rgba(5, 18, 45, .2); margin-bottom: 16px; }
        .admin-head h1 { margin: 0 0 4px 0; font-size: 1.2rem; font-weight: 700; }
        .admin-head p { margin: 0; font-size: .92rem; color: rgba(255,255,255,.82); }
        .actions { display: flex; flex-wrap: wrap; gap: .55rem; }
        .panel { border: 0; border-radius: 14px; box-shadow: 0 10px 24px rgba(0, 19, 48, .08); background: #fff; }
        .panel .panel-title { font-size: 1.02rem; font-weight: 700; margin-bottom: .25rem; }
        .panel .panel-subtitle { color: #58657c; font-size: .9rem; margin-bottom: 1rem; }
        .crumbs { font-size: .83rem; color: #6a758b; margin-bottom: .6rem; }
        .crumbs a { color: #42506c; text-decoration: none; }
        .crumbs a:hover { text-decoration: underline; }
        .media-preview { margin-top: .45rem; font-size: .85rem; }
        @media (max-width: 768px) { .admin-layout { grid-template-columns: 1fr; } .sidebar { position: static; } .admin-shell { margin-top: 16px; } .admin-head { padding: 14px; } }
    </style>
</head>
<body>
<div class="admin-shell">
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="brand">ABN Admin</div>
            <div class="brand-sub">Content management panel</div>
            <a class="nav-link-admin" href="dashboard.php"><i class="bi bi-journal-richtext"></i>Blog Manager</a>
            <a class="nav-link-admin active" href="projects.php"><i class="bi bi-building"></i>Projects Manager</a>
            <a class="nav-link-admin" href="../blog.php"><i class="bi bi-eye"></i>View Blog</a>
            <a class="nav-link-admin" href="../projects.php"><i class="bi bi-collection"></i>View Projects</a>
            <a class="nav-link-admin" href="../includes/logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </aside>

        <main>
            <div class="admin-head d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="crumbs"><a href="../index.php">Site</a> / Admin / Projects Manager</div>
                    <h1>Projects Manager</h1>
                    <p>Add and manage portfolio projects with drawings and videos.</p>
                </div>
                <div class="actions">
                    <a href="dashboard.php" class="btn btn-light btn-sm">Back to Blog Manager</a>
                    <a href="../projects.php" class="btn btn-outline-light btn-sm">View Projects Page</a>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="panel p-4 mb-3">
                <h2 class="panel-title"><?= $editingProject ? 'Edit Project' : 'Add Project' ?></h2>
                <p class="panel-subtitle">Upload media and publish it directly to the public projects page. Max file size: 1GB.</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="<?= $editingProject ? 'update' : 'create' ?>">
                    <?php if ($editingProject): ?>
                        <input type="hidden" name="project_id" value="<?= (int) $editingProject['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editingProject['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g. Residential" value="<?= htmlspecialchars($editingProject['category'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Summary</label>
                            <textarea name="summary" rows="2" class="form-control"><?= htmlspecialchars($editingProject['summary'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="5" class="form-control"><?= htmlspecialchars($editingProject['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label"><?= $editingProject ? 'Replace Media (optional)' : 'Media (optional)' ?></label>
                            <input type="file" name="media" class="form-control" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.pdf">
                            <?php if ($editingProject && !empty($editingProject['media_path'])): ?>
                                <div class="media-preview">
                                    Current: <a href="../<?= htmlspecialchars($editingProject['media_path']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($editingProject['original_name'] ?: $editingProject['media_path']) ?></a>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_media" value="1" id="removeProjectMedia">
                                    <label class="form-check-label" for="removeProjectMedia">Remove current media</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="published" <?= (($editingProject['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= (($editingProject['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3"><?= $editingProject ? 'Update Project' : 'Save Project' ?></button>
                    <?php if ($editingProject): ?>
                        <a href="projects.php" class="btn btn-outline-secondary mt-3 ms-2">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="panel p-4">
                <h2 class="panel-title">Recent Projects</h2>
                <p class="panel-subtitle">Latest project entries in your database.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$projects): ?>
                            <tr><td colspan="7" class="text-center text-muted">No projects yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $item): ?>
                                <tr>
                                    <td><?= (int) $item['id'] ?></td>
                                    <td><?= htmlspecialchars($item['title']) ?></td>
                                    <td><?= htmlspecialchars($item['category'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($item['media_type']) ?></td>
                                    <td><?= htmlspecialchars($item['status']) ?></td>
                                    <td><?= htmlspecialchars($item['created_at']) ?></td>
                                    <td>
                                        <a href="projects.php?edit_id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this project?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="project_id" value="<?= (int) $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
