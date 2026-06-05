<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with($scriptName, '/includes/login.php')) {
    $basePath = rtrim(dirname(dirname($scriptName)), '/');
    header('Location: ' . ($basePath !== '' ? $basePath : '') . '/login.php', true, 301);
    exit;
}

$message = '';
$success = '';

if (empty($_SESSION['user_login_csrf'])) {
    $_SESSION['user_login_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['user_login_csrf'];

if (!empty($_SESSION['user_login_success'])) {
    $success = (string) $_SESSION['user_login_success'];
    unset($_SESSION['user_login_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['user_login_csrf'] ?? '', $csrfToken)) {
        $message = 'Invalid form submission. Refresh and try again.';
    } else {
    $emailOrUsername = trim((string) ($_POST['email_or_username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $identifier = 'user:' . mb_substr($emailOrUsername, 0, 180);
    $ipAddress = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
    $lockWindowMinutes = 15;
    $maxAttempts = 5;
    $cutoff = date('Y-m-d H:i:s', time() - ($lockWindowMinutes * 60));

    $stmtLock = $conn->prepare(
        'SELECT (SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?) AS ip_hits,
                (SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at >= ?) AS id_hits'
    );
    $stmtLock->bind_param('ssss', $ipAddress, $cutoff, $identifier, $cutoff);
    $stmtLock->execute();
    $lockCounts = $stmtLock->get_result()->fetch_assoc();

    if ((int) ($lockCounts['ip_hits'] ?? 0) >= $maxAttempts || (int) ($lockCounts['id_hits'] ?? 0) >= $maxAttempts) {
        $message = 'Too many login attempts. Try again in 15 minutes.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password FROM users WHERE email = ? OR username = ? LIMIT 1');
        $stmt->bind_param('ss', $emailOrUsername, $emailOrUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = 'user';
                $_SESSION['logged_in_at'] = time();

                $clearStmt = $conn->prepare('DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?');
                $clearStmt->bind_param('ss', $ipAddress, $identifier);
                $clearStmt->execute();

                header('Location: ../index.php');
                exit;
            }
        }

        $failStmt = $conn->prepare('INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)');
        $failStmt->bind_param('ss', $ipAddress, $identifier);
        $failStmt->execute();
        $message = 'Invalid credentials.';
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Login - ABN Construction</title>
<link rel="icon" type="image/png" href="../img/logo.png">
<style>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=DM+Serif+Display:ital@0;1&display=swap');

:root {
    --ink: #0f172a;
    --ink-soft: #334155;
    --paper: #f6f7f4;
    --panel: #0a2a5e;
    --panel-2: #123d82;
    --accent: #ea580c;
    --accent-2: #f59e0b;
    --line: rgba(255, 255, 255, 0.22);
    --white: #ffffff;
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
    margin: 0;
    min-height: 100vh;
    color: var(--ink);
    background:
        radial-gradient(circle at 12% 15%, rgba(245, 158, 11, 0.26), transparent 34%),
        radial-gradient(circle at 88% 80%, rgba(234, 88, 12, 0.2), transparent 36%),
        linear-gradient(135deg, #eef2ff 0%, #f8fafc 45%, #f6f7f4 100%);
    font-family: "Space Grotesk", sans-serif;
    position: relative;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
}
.auth-site-header {
    width: 100%;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: rgba(10, 42, 94, 0.95);
    color: var(--white);
}
.auth-site-header .brand {
    color: var(--white);
    text-decoration: none;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.auth-site-header .brand img {
    width: 34px;
    height: 34px;
    object-fit: contain;
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
    background: rgba(10, 42, 94, 0.95);
    font-size: .85rem;
}
.auth-container {
    display: grid;
    place-items: center;
    flex: 1;
    padding: 32px 18px;
    width: 100%;
    max-width: 100%;
}
.login-shell {
    width: 100%;
    max-width: 980px;
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.16);
    animation: pageIn .55s ease;
}
.brand-pane {
    background: linear-gradient(160deg, var(--panel) 0%, var(--panel-2) 100%);
    color: var(--white);
    padding: 44px 34px;
    position: relative;
}
.brand-pane::after {
    content: "";
    position: absolute;
    width: 170px;
    height: 170px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.2), transparent 70%);
    right: -45px;
    top: -45px;
}
.brand-kicker {
    margin: 0 0 8px;
    font-size: .82rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .88;
}
.brand-title {
    margin: 0 0 10px;
    font-size: 2rem;
    line-height: 1.1;
    font-family: "DM Serif Display", serif;
}
.brand-text {
    margin: 0;
    color: rgba(255, 255, 255, .82);
    max-width: 28ch;
    line-height: 1.5;
}
.auth-box {
    background: rgba(10, 42, 94, 0.93);
    padding: 44px 34px 34px;
    color: var(--white);
}
.auth-box h2 {
    margin: 0 0 6px;
    font-size: 1.55rem;
    letter-spacing: -.01em;
}
.sub {
    margin: 0 0 20px;
    color: rgba(255, 255, 255, .82);
    font-size: .95rem;
}
.form-group { margin-bottom: 16px; }
.form-group label {
    color: #fff;
    font-weight: 600;
    font-size: .9rem;
}
.form-group input {
    width: 100%;
    padding: 12px 13px;
    margin-top: 8px;
    border: 1px solid var(--line);
    border-radius: 12px;
    background: rgba(255,255,255,.12);
    color: #fff;
    transition: border-color .2s ease, background-color .2s ease;
}
.form-group input:focus {
    outline: none;
    border-color: rgba(245, 158, 11, .9);
    background: rgba(255,255,255,.18);
}
.btn-submit {
    width: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-2));
    color: #fff;
    padding: 14px;
    border: 0;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .18s ease, filter .18s ease;
}
.btn-submit:hover {
    transform: translateY(-1px);
    filter: brightness(1.02);
}
.error-message {
    background: rgba(255, 125, 125, 0.18);
    border: 1px solid rgba(255, 165, 165, 0.55);
    color: #ffe4e4;
    padding: 10px 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    text-align: left;
    font-weight: 600;
    font-size: .9rem;
}
.success-message {
    background: rgba(125, 255, 169, 0.16);
    border: 1px solid rgba(166, 255, 191, 0.5);
    color: #dcffe8;
    padding: 10px 12px;
    border-radius: 10px;
    margin-bottom: 15px;
    text-align: left;
    font-weight: 600;
    font-size: .9rem;
}
.helper {
    color: rgba(255, 255, 255, .9);
    margin-top: 10px;
    font-size: .88rem;
}
.helper a {
    color: #ffd49a;
    text-decoration: none;
    border-bottom: 1px dotted rgba(255, 212, 154, 0.65);
}
@keyframes pageIn {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 992px) {
    .login-shell {
        grid-template-columns: 1fr;
        max-width: 680px;
    }
    .brand-pane {
        padding: 28px 24px 20px;
    }
    .brand-title {
        font-size: 1.55rem;
    }
    .auth-box {
        padding: 26px 24px 24px;
    }
}
@media (max-width: 640px) {
    .auth-container {
        padding: 14px 10px;
    }
    .login-shell {
        border-radius: 14px;
        max-width: 100%;
    }
    .brand-pane {
        display: none;
    }
    .auth-box {
        padding: 20px 14px 18px;
    }
    .auth-box h2 {
        font-size: 1.25rem;
    }
    .sub {
        font-size: .9rem;
    }
    .form-group input {
        font-size: 16px;
        padding: 13px 12px;
    }
    .btn-submit {
        font-size: 1rem;
        padding: 13px;
    }
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
    <section class="login-shell">
        <aside class="brand-pane">
            <p class="brand-kicker">ABN Client Portal</p>
            <h1 class="brand-title">Welcome Back</h1>
            <p class="brand-text">Sign in to review updates, track project communication, and access your client area.</p>
        </aside>
        <div class="auth-box">
        <h2>User Login</h2>
        <p class="sub">Use your email or username to continue.</p>

        <?php if ($message): ?>
            <p class="error-message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p class="success-message"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label>Email or Username</label>
                <input type="text" name="email_or_username" required placeholder="Enter your email or username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>

        <p class="helper">No account yet? <a href="signup.php">Create account</a></p>
        <p class="helper"><a href="forgot_password.php">Forgot password?</a></p>
        <p class="helper"><a href="../index.php">Back to Home</a></p>
        </div>
    </section>
</main>
<footer class="auth-site-footer">
    ABN Real Estate Construction, Cameroon
</footer>
</body>
</html>
