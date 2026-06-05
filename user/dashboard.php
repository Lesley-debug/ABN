<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/session_bootstrap.php';
require_once __DIR__ . '/../includes/config.php';

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'user' || !isset($_SESSION['user_id'])) {
    header('Location: ../includes/login.php');
    exit;
}

function sendAccountNotice(string $recipientEmail, string $recipientName, string $subject, string $htmlBody, string $altBody): bool
{
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
    $smtpUser = getenv('SMTP_USER') ?: '';
    $smtpPass = getenv('SMTP_PASS') ?: '';
    $smtpFrom = getenv('SMTP_FROM') ?: $smtpUser;

    if ($smtpUser === '' || $smtpPass === '' || $smtpFrom === '') {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtpPort;
        $mail->setFrom($smtpFrom, 'ABN Construction');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function shorten(string $text, int $max = 180): string
{
    $clean = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($clean) <= $max) {
        return $clean;
    }
    return mb_substr($clean, 0, $max - 3) . '...';
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$success = [];

try {
    $columnCheck = $conn->query(
        "SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'profile_photo'"
    );
    $hasPhotoColumn = (int) (($columnCheck ? $columnCheck->fetch_assoc()['total'] : 0) ?? 0) > 0;

    if (!$hasPhotoColumn) {
        $conn->query('ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL');
    }
} catch (mysqli_sql_exception $e) {
    if (stripos($e->getMessage(), 'Duplicate column name') === false) {
        $errors[] = 'Could not prepare user profile storage.';
    }
}

$loadStmt = $conn->prepare('SELECT id, username, email, password, profile_photo, created_at FROM users WHERE id = ? LIMIT 1');
$loadStmt->bind_param('i', $userId);
$loadStmt->execute();
$user = $loadStmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: ../includes/logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'profile_update') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $photoPath = (string) ($user['profile_photo'] ?? '');
        $changes = [];

        if ($username === '' || mb_strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!isset($_FILES['profile_photo'])) {
            $_FILES['profile_photo'] = ['error' => UPLOAD_ERR_NO_FILE];
        }

        if ((int) $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photo = $_FILES['profile_photo'];
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'Profile photo exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'Profile photo exceeds form upload limit.',
                UPLOAD_ERR_PARTIAL => 'Profile photo upload was interrupted.',
                UPLOAD_ERR_NO_TMP_DIR => 'Temporary upload folder is missing.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not save the profile photo.',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension.',
            ];

            if ((int) $photo['error'] !== UPLOAD_ERR_OK) {
                $errors[] = $uploadErrors[(int) $photo['error']] ?? 'Profile photo upload failed.';
            } else {
                $maxSize = 2 * 1024 * 1024;
                if ((int) $photo['size'] > $maxSize) {
                    $errors[] = 'Profile photo must be 2MB or less.';
                } else {
                    $tmp = (string) $photo['tmp_name'];
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = (string) $finfo->file($tmp);
                    $allowed = [
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                    ];

                    if (!isset($allowed[$mime])) {
                        $errors[] = 'Profile photo must be JPG, PNG, or WEBP.';
                    } else {
                        $uploadDir = __DIR__ . '/../uploads/users';
                        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                            $errors[] = 'Could not create profile upload folder.';
                        } else {
                            $fileName = sprintf('user-%d-%d-%s.%s', $userId, time(), str_replace('.', '', uniqid('', true)), $allowed[$mime]);
                            $destination = $uploadDir . '/' . $fileName;
                            if (!move_uploaded_file($tmp, $destination)) {
                                $errors[] = 'Could not save uploaded profile photo.';
                            } else {
                                $photoPath = 'uploads/users/' . $fileName;
                                $changes[] = 'profile photo';
                            }
                        }
                    }
                }
            }
        }

        if (!$errors) {
            $checkStmt = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id <> ? LIMIT 1');
            $checkStmt->bind_param('ssi', $username, $email, $userId);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();

            if ($exists) {
                $errors[] = 'Username or email is already used by another account.';
            } else {
                $updateStmt = $conn->prepare('UPDATE users SET username = ?, email = ?, profile_photo = ? WHERE id = ?');
                $updateStmt->bind_param('sssi', $username, $email, $photoPath, $userId);
                if ($updateStmt->execute()) {
                    if ($username !== (string) $user['username']) {
                        $changes[] = 'username';
                    }
                    if ($email !== (string) $user['email']) {
                        $changes[] = 'email';
                    }

                    $_SESSION['username'] = $username;
                    $oldPhoto = (string) ($user['profile_photo'] ?? '');
                    if ($photoPath !== '' && $oldPhoto !== '' && $photoPath !== $oldPhoto) {
                        $oldPhotoPath = __DIR__ . '/../' . $oldPhoto;
                        if (is_file($oldPhotoPath)) {
                            @unlink($oldPhotoPath);
                        }
                    }

                    $changeText = $changes ? implode(', ', array_unique($changes)) : 'profile details';
                    $success[] = 'Profile updated successfully.';
                    sendAccountNotice(
                        $email,
                        $username,
                        'ABN Account Profile Updated',
                        '<p>Hello ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . ',</p>'
                        . '<p>Your ABN account was updated. Changed: <strong>' . htmlspecialchars($changeText, ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                        . '<p>If this was not you, reset your password immediately.</p>',
                        "Hello {$username},\nYour ABN account was updated. Changed: {$changeText}.\nIf this was not you, reset your password immediately."
                    );
                } else {
                    $errors[] = 'Could not update profile details right now.';
                }
            }
        }
    } elseif ($action === 'password_update') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        }
        if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (!$errors) {
            if (!password_verify($currentPassword, (string) $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $passStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
                $passStmt->bind_param('si', $newHash, $userId);
                if ($passStmt->execute()) {
                    $success[] = 'Password updated successfully.';
                    sendAccountNotice(
                        (string) $user['email'],
                        (string) $user['username'],
                        'ABN Account Password Changed',
                        '<p>Hello ' . htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8') . ',</p>'
                        . '<p>Your ABN account password was changed successfully.</p>'
                        . '<p>If you did not perform this action, contact ABN support immediately.</p>',
                        "Hello {$user['username']},\nYour ABN account password was changed successfully.\nIf you did not perform this action, contact ABN support immediately."
                    );
                } else {
                    $errors[] = 'Could not update password right now.';
                }
            }
        }
    }

    $reloadStmt = $conn->prepare('SELECT id, username, email, password, profile_photo, created_at FROM users WHERE id = ? LIMIT 1');
    $reloadStmt->bind_param('i', $userId);
    $reloadStmt->execute();
    $user = $reloadStmt->get_result()->fetch_assoc() ?: $user;
}

$conn->query(
    "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(60) DEFAULT NULL,
        project VARCHAR(190) DEFAULT NULL,
        subject VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$messageQuery = trim((string) ($_GET['mq'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;
$userEmail = (string) $user['email'];

if ($messageQuery !== '') {
    $like = '%' . $messageQuery . '%';
    $countStmt = $conn->prepare(
        'SELECT COUNT(*) AS total
         FROM contact_messages
         WHERE email = ? AND (subject LIKE ? OR project LIKE ? OR message LIKE ?)'
    );
    $countStmt->bind_param('ssss', $userEmail, $like, $like, $like);
    $countStmt->execute();
    $totalMessages = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

    $msgStmt = $conn->prepare(
        'SELECT id, subject, project, message, created_at
         FROM contact_messages
         WHERE email = ? AND (subject LIKE ? OR project LIKE ? OR message LIKE ?)
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );
    $msgStmt->bind_param('ssssii', $userEmail, $like, $like, $like, $perPage, $offset);
} else {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM contact_messages WHERE email = ?');
    $countStmt->bind_param('s', $userEmail);
    $countStmt->execute();
    $totalMessages = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

    $msgStmt = $conn->prepare(
        'SELECT id, subject, project, message, created_at
         FROM contact_messages
         WHERE email = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );
    $msgStmt->bind_param('sii', $userEmail, $perPage, $offset);
}

$totalPages = max(1, (int) ceil($totalMessages / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$msgStmt->execute();
$msgResult = $msgStmt->get_result();
$messages = [];
while ($row = $msgResult->fetch_assoc()) {
    $messages[] = $row;
}

$photoUrl = !empty($user['profile_photo']) ? '../' . ltrim((string) $user['profile_photo'], '/') : '../img/team-1.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ABN Construction</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .profile-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <p class="text-uppercase text-secondary fs-6 mb-1">ABN User Portal</p>
                <h2 class="mb-0">My Dashboard</h2>
            </div>
            <div class="d-flex gap-2">
                <a href="../index.php" class="btn btn-outline-secondary">Home</a>
                <a href="../includes/logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php foreach ($success as $note): ?>
                    <div><?= htmlspecialchars($note) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= htmlspecialchars($photoUrl) ?>" class="profile-avatar" alt="Profile photo">
                    <div>
                        <h4 class="mb-1"><?= htmlspecialchars((string) $user['username']) ?></h4>
                        <p class="mb-0 text-muted"><?= htmlspecialchars((string) $user['email']) ?></p>
                    </div>
                </div>
                <h4 class="mb-3">Account Details</h4>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 220px;">User ID</th>
                                <td><?= htmlspecialchars((string) $user['id']) ?></td>
                            </tr>
                            <tr>
                                <th>Username</th>
                                <td><?= htmlspecialchars((string) $user['username']) ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= htmlspecialchars((string) $user['email']) ?></td>
                            </tr>
                            <tr>
                                <th>Account Created</th>
                                <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime((string) $user['created_at']))) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row mt-4 g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Edit Profile</h4>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="profile_update">
                            <div class="mb-3">
                                <label class="form-label" for="username">Username</label>
                                <input class="form-control" id="username" name="username" value="<?= htmlspecialchars((string) $user['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars((string) $user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="profile_photo">Profile Photo (JPG/PNG/WEBP, max 2MB)</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Change Password</h4>
                        <form method="post">
                            <input type="hidden" name="action" value="password_update">
                            <div class="mb-3">
                                <label class="form-label" for="current_password">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h4 class="mb-0">My Messages</h4>
                    <form method="get" class="d-flex gap-2">
                        <input type="search" class="form-control" name="mq" placeholder="Search messages..." value="<?= htmlspecialchars($messageQuery) ?>">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                    </form>
                </div>

                <?php if (!$messages): ?>
                    <p class="mb-0 text-muted">No contact messages found for your email yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 180px;">Date</th>
                                    <th style="width: 220px;">Subject</th>
                                    <th style="width: 180px;">Project</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('F j, Y g:i A', strtotime((string) $msg['created_at']))) ?></td>
                                        <td><?= htmlspecialchars((string) $msg['subject']) ?></td>
                                        <td><?= htmlspecialchars((string) (($msg['project'] ?? '') !== '' ? $msg['project'] : '-')) ?></td>
                                        <td><?= htmlspecialchars(shorten((string) $msg['message'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($totalMessages > 0): ?>
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <small class="text-muted">
                            Showing page <?= (int) $page ?> of <?= (int) $totalPages ?> (<?= (int) $totalMessages ?> message<?= $totalMessages === 1 ? '' : 's' ?>)
                        </small>
                        <nav aria-label="Message pagination">
                            <ul class="pagination mb-0">
                                <?php
                                $baseParams = [];
                                if ($messageQuery !== '') {
                                    $baseParams['mq'] = $messageQuery;
                                }
                                ?>
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <?php $prevParams = $baseParams + ['page' => max(1, $page - 1)]; ?>
                                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($prevParams)) ?>">Previous</a>
                                </li>
                                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                    <?php $pageParams = $baseParams + ['page' => $p]; ?>
                                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= htmlspecialchars(http_build_query($pageParams)) ?>"><?= (int) $p ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                    <?php $nextParams = $baseParams + ['page' => min($totalPages, $page + 1)]; ?>
                                    <a class="page-link" href="?<?= htmlspecialchars(http_build_query($nextParams)) ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
