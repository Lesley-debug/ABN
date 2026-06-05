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
        return ['ok' => false, 'message' => 'Could not move uploaded file.'];
    }

    return [
        'ok' => true,
        'media_type' => $mediaType,
        'media_path' => 'uploads/blog/' . $safeName,
        'original_name' => $originalName,
        'full_path' => $destination
    ];
}

verifyCsrfOrStop();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        if ($postId <= 0) {
            $message = 'Invalid post id.';
        } else {
            $select = $conn->prepare('SELECT media_path FROM blog_posts WHERE id = ?');
            $select->bind_param('i', $postId);
            $select->execute();
            $row = $select->get_result()->fetch_assoc();

            $delete = $conn->prepare('DELETE FROM blog_posts WHERE id = ?');
            $delete->bind_param('i', $postId);
            if ($delete->execute()) {
                if (!empty($row['media_path'])) {
                    $oldFile = __DIR__ . '/../' . $row['media_path'];
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $message = 'Post deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Delete failed.';
            }
        }
    }

    if ($action === 'create' || $action === 'update') {
        $postId = (int) ($_POST['post_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $removeMedia = isset($_POST['remove_media']) && $_POST['remove_media'] === '1';

        if ($title === '') {
            $message = 'Title is required.';
        } else {
            $existing = null;
            if ($action === 'update') {
                if ($postId <= 0) {
                    $message = 'Invalid post selected for update.';
                } else {
                    $getExisting = $conn->prepare('SELECT media_type, media_path, original_name FROM blog_posts WHERE id = ?');
                    $getExisting->bind_param('i', $postId);
                    $getExisting->execute();
                    $existing = $getExisting->get_result()->fetch_assoc();
                    if (!$existing) {
                        $message = 'Post not found.';
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
                    $upload = uploadMedia($_FILES['media'], __DIR__ . '/../uploads/blog', $allowedExtensions, $allowedMime);
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
                        $stmt = $conn->prepare('INSERT INTO blog_posts (title, excerpt, content, media_type, media_path, original_name, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bind_param('sssssssi', $title, $excerpt, $content, $mediaType, $mediaPath, $originalName, $status, $createdBy);
                        if ($stmt->execute()) {
                            $message = 'Post created successfully.';
                            $messageType = 'success';
                        } else {
                            $message = 'Database operation failed. Please try again.';
                        }
                    } else {
                        $stmt = $conn->prepare('UPDATE blog_posts SET title = ?, excerpt = ?, content = ?, media_type = ?, media_path = ?, original_name = ?, status = ? WHERE id = ?');
                        $stmt->bind_param('sssssssi', $title, $excerpt, $content, $mediaType, $mediaPath, $originalName, $status, $postId);
                        if ($stmt->execute()) {
                            if ($oldPathToDelete && is_file($oldPathToDelete)) {
                                @unlink($oldPathToDelete);
                            }
                            $message = 'Post updated successfully.';
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
$editingPost = null;
if ($editId > 0) {
    $editStmt = $conn->prepare('SELECT id, title, excerpt, content, media_type, media_path, original_name, status FROM blog_posts WHERE id = ?');
    $editStmt->bind_param('i', $editId);
    $editStmt->execute();
    $editingPost = $editStmt->get_result()->fetch_assoc();
}

$recentPosts = [];
$result = $conn->query('SELECT id, title, media_type, status, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 30');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentPosts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ABN</title>
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
        .admin-head p { margin: 0; font-size: .92rem; color: rgba(255, 255, 255, .82); }
        .actions { display: flex; flex-wrap: wrap; gap: .55rem; }
        .panel { border: 0; border-radius: 14px; box-shadow: 0 10px 26px rgba(0, 19, 48, .08); background: #fff; }
        .panel .panel-title { font-size: 1.02rem; font-weight: 700; margin-bottom: .25rem; }
        .panel .panel-subtitle { color: #58657c; font-size: .9rem; margin-bottom: 1rem; }
        .crumbs { font-size: .83rem; color: #6a758b; margin-bottom: .6rem; }
        .crumbs a { color: #42506c; text-decoration: none; }
        .crumbs a:hover { text-decoration: underline; }
        .badge-pill { border-radius: 999px; padding: .35rem .7rem; font-size: .75rem; }
        .helper-card { border: 1px dashed #cfd8e7; border-radius: 10px; padding: 12px; background: #f9fbff; font-size: .88rem; color: #3b4a64; }
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
            <a class="nav-link-admin active" href="dashboard.php"><i class="bi bi-journal-richtext"></i>Blog Manager</a>
            <a class="nav-link-admin" href="projects.php"><i class="bi bi-building"></i>Projects Manager</a>
            <a class="nav-link-admin" href="../blog.php"><i class="bi bi-eye"></i>View Blog</a>
            <a class="nav-link-admin" href="../projects.php"><i class="bi bi-collection"></i>View Projects</a>
            <a class="nav-link-admin" href="../includes/logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a>
        </aside>

        <main>
            <div class="admin-head d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="crumbs"><a href="../index.php">Site</a> / Admin / Blog Manager</div>
                    <h1>ABN Admin Dashboard</h1>
                    <p>Publish blog posts and media uploads from one place.</p>
                </div>
                <div class="actions">
                    <a href="projects.php" class="btn btn-light btn-sm">Manage Projects</a>
                    <a href="../blog.php" class="btn btn-outline-light btn-sm">View Blog</a>
                    <a href="../includes/logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="panel p-4 h-100">
                        <h2 class="panel-title"><?= $editingPost ? 'Edit Blog Post' : 'Create Blog Post / Upload Drawing or Video' ?></h2>
                        <p class="panel-subtitle">Supports images, videos and PDF drawings. Max file size: 1GB.</p>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="<?= $editingPost ? 'update' : 'create' ?>">
                            <?php if ($editingPost): ?>
                                <input type="hidden" name="post_id" value="<?= (int) $editingPost['id'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editingPost['title'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Short Excerpt</label>
                                <textarea name="excerpt" class="form-control" rows="2" placeholder="Short summary for list cards"><?= htmlspecialchars($editingPost['excerpt'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Content</label>
                                <textarea name="content" class="form-control" rows="5" placeholder="Full post body"><?= htmlspecialchars($editingPost['content'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><?= $editingPost ? 'Replace Media (optional)' : 'Media (optional)' ?></label>
                                <input type="file" name="media" class="form-control" accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov,.pdf">
                                <?php if ($editingPost && !empty($editingPost['media_path'])): ?>
                                    <div class="media-preview">
                                        Current: <a href="../<?= htmlspecialchars($editingPost['media_path']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($editingPost['original_name'] ?: $editingPost['media_path']) ?></a>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_media" value="1" id="removeMedia">
                                        <label class="form-check-label" for="removeMedia">Remove current media</label>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="published" <?= (($editingPost['status'] ?? 'published') === 'published') ? 'selected' : '' ?>>Published</option>
                                    <option value="draft" <?= (($editingPost['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= $editingPost ? 'Update Post' : 'Save Post' ?></button>
                            <?php if ($editingPost): ?>
                                <a href="dashboard.php" class="btn btn-outline-secondary ms-2">Cancel Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="panel p-4 h-100">
                        <h2 class="panel-title">Publishing Notes</h2>
                        <p class="panel-subtitle">Quick checks before uploading.</p>
                        <div class="helper-card mb-2">Use clear titles so posts are easier to search.</div>
                        <div class="helper-card mb-2">Videos can be large; confirm server upload limits are set.</div>
                        <div class="helper-card">Use Edit from the table to update existing posts and media.</div>
                    </div>
                </div>
            </div>

            <div class="panel p-4 mt-3">
                <h2 class="panel-title">Recent Posts</h2>
                <p class="panel-subtitle">Create, edit, or delete blog entries.</p>
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$recentPosts): ?>
                            <tr><td colspan="6" class="text-center text-muted">No posts yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentPosts as $post): ?>
                                <tr>
                                    <td><?= (int) $post['id'] ?></td>
                                    <td><?= htmlspecialchars($post['title']) ?></td>
                                    <td><span class="badge bg-info text-dark badge-pill"><?= htmlspecialchars($post['media_type']) ?></span></td>
                                    <td><span class="badge <?= $post['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?> badge-pill"><?= htmlspecialchars($post['status']) ?></span></td>
                                    <td><?= htmlspecialchars($post['created_at']) ?></td>
                                    <td>
                                        <a href="dashboard.php?edit_id=<?= (int) $post['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
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
