<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with($scriptName, '/includes/forgot_password.php')) {
    $basePath = rtrim(dirname(dirname($scriptName)), '/');
    header('Location: ' . ($basePath !== '' ? $basePath : '') . '/forgot_password.php', true, 301);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

if (empty($_SESSION['user_forgot_csrf'])) {
    $_SESSION['user_forgot_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['user_forgot_csrf'];

$message = '';
$messageType = 'error';

function forgotRateLimitExceeded(mysqli $conn, string $ipAddress, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS request_limits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action_key VARCHAR(64) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_action_ip_time (action_key, ip_address, requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $actionKey = 'forgot_password';
    $cutoff = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));
    $countStmt = $conn->prepare(
        'SELECT COUNT(*) AS hits FROM request_limits WHERE action_key = ? AND ip_address = ? AND requested_at >= ?'
    );
    $countStmt->bind_param('sss', $actionKey, $ipAddress, $cutoff);
    $countStmt->execute();
    $hits = (int) (($countStmt->get_result()->fetch_assoc()['hits'] ?? 0));

    $insertStmt = $conn->prepare('INSERT INTO request_limits (action_key, ip_address) VALUES (?, ?)');
    $insertStmt->bind_param('ss', $actionKey, $ipAddress);
    $insertStmt->execute();

    $cleanupStmt = $conn->prepare('DELETE FROM request_limits WHERE requested_at < ?');
    $cleanupStmt->bind_param('s', $cutoff);
    $cleanupStmt->execute();

    return $hits >= $maxAttempts;
}

function appBaseUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '/forgot_password.php');
    $basePath = rtrim(str_replace('\\', '/', dirname(dirname($scriptName))), '/');
    return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['user_forgot_csrf'] ?? '', $token)) {
        $message = 'Invalid form submission. Refresh and try again.';
    } else {
        $ipAddress = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        if (forgotRateLimitExceeded($conn, $ipAddress)) {
            $message = 'Too many requests. Try again in 15 minutes.';
        } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please provide a valid email address.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $tokenValue = bin2hex(random_bytes(50));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update = $conn->prepare('UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?');
                $update->bind_param('sss', $tokenValue, $expires, $email);
                $update->execute();

                $resetLink = appBaseUrl() . '/reset_password.php?token=' . urlencode($tokenValue);

                $smtpHost = envValue('SMTP_HOST', 'smtp.gmail.com') ?? 'smtp.gmail.com';
                $smtpPort = (int) (envValue('SMTP_PORT', '587') ?? '587');
                $smtpUser = envValue('SMTP_USER', '') ?? '';
                $smtpPass = envValue('SMTP_PASS', '') ?? '';
                $smtpFrom = envValue('SMTP_FROM', $smtpUser) ?? $smtpUser;
                $smtpPass = str_replace(' ', '', trim($smtpPass));

                if ($smtpUser !== '' && $smtpPass !== '' && $smtpFrom !== '') {
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
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = 'Reset your password';
                        $mail->Body = 'Click this link to reset your password: <a href="' . htmlspecialchars($resetLink, ENT_QUOTES) . '">' . htmlspecialchars($resetLink, ENT_QUOTES) . '</a><br><br>This link expires in 1 hour.';
                        $mail->send();
                    } catch (Exception $e) {
                        error_log('Forgot password SMTP send failed: ' . $e->getMessage());
                    }
                }
            }

            $messageType = 'success';
            $message = 'If an account exists for that email, a reset link has been sent.';
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - ABN Construction</title>
<link rel="icon" type="image/png" href="../img/logo.png">
<style>
:root {
    --primary-blue: rgba(0, 18, 72, 0.78);
    --accent-orange: rgba(255, 94, 21, 1);
    --text-light: #ffffff;
    --bg-light: #f4f4f7;
}
*,
*::before,
*::after {
    box-sizing: border-box;
}
html,
body {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}
body {
    font-family: "Poppins", sans-serif;
    margin: 0;
    background: var(--bg-light);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.auth-site-header {
    width: 100%;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(0, 18, 72, 0.95);
    color: #fff;
}
.auth-site-header .brand {
    color: #fff;
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.auth-site-header .brand img {
    width: 34px;
    height: 34px;
}
.auth-site-header .back-home {
    color: #ffd49a;
    text-decoration: none;
    font-weight: 600;
    font-size: .92rem;
}
.auth-site-footer {
    width: 100%;
    padding: 10px 14px;
    text-align: center;
    color: #dbe5ff;
    background: rgba(0, 18, 72, 0.95);
    font-size: .85rem;
}
.auth-container { display: flex; justify-content: center; align-items: center; flex: 1; padding: 40px; }
.auth-box { width: 100%; max-width: 460px; background: var(--primary-blue); padding: 35px; border-radius: 16px; box-shadow: 0 12px 30px rgba(0, 18, 72, 0.25); }
.auth-box h2 { text-align: center; color: var(--text-light); margin-bottom: 20px; }
.form-group { margin-bottom: 18px; }
.form-group label { color: #fff; font-weight: 600; }
.form-group input { width: 100%; padding: 12px; margin-top: 8px; border: 1px solid rgba(255,255,255,.3); border-radius: 8px; background: rgba(255,255,255,.15); color: #fff; }
.btn-submit { width: 100%; background: var(--accent-orange); color: #fff; padding: 14px; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer; }
.message { padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: 600; }
.message.error { background: #ffd7d7; color: #a30000; }
.message.success { background: #d7ffe0; color: #007a19; }
.helper { color: #fff; text-align: center; margin-top: 14px; font-size: .9rem; }
.helper a { color: var(--accent-orange); text-decoration: none; }
@media (max-width: 640px) {
    .auth-container { padding: 14px 10px; }
    .auth-box { padding: 20px 14px 18px; border-radius: 14px; }
    .form-group input { font-size: 16px; }
    .auth-site-header .brand span {
        max-width: 170px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>
</head>
<body>
<header class="auth-site-header">
    <a class="brand" href="../index.php">
        <img src="../img/logo.png" alt="ABN logo">
        <span>ABN Construction</span>
    </a>
    <a class="back-home" href="../index.php">Back Home</a>
</header>

<main class="auth-container">
    <div class="auth-box">
        <h2>Forgot Password</h2>

        <?php if ($message): ?>
            <p class="message <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Enter your account email">
            </div>
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>

        <p class="helper"><a href="login.php">Back to Login</a></p>
    </div>
</main>
<footer class="auth-site-footer">
    ABN Real Estate Construction, Cameroon
</footer>
</body>
</html>
