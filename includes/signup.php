<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with($scriptName, '/includes/signup.php')) {
    $basePath = rtrim(dirname(dirname($scriptName)), '/');
    header('Location: ' . ($basePath !== '' ? $basePath : '') . '/signup.php', true, 301);
    exit;
}

if (empty($_SESSION['user_signup_csrf'])) {
    $_SESSION['user_signup_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['user_signup_csrf'];

$message = '';
$messageType = 'error';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['user_signup_csrf'] ?? '', $token)) {
        $message = 'Invalid form submission. Refresh and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($username === '' || $email === '' || $password === '') {
            $message = 'All fields are required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
            $message = 'Username must be 3-30 chars (letters, numbers, ., _, -).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email address.';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
        } else {
            $check = $conn->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $check->bind_param('ss', $email, $username);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;

            if ($exists) {
                $message = 'Username or email is already in use.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $username, $email, $passwordHash);

                if ($stmt->execute()) {
                    $_SESSION['user_login_success'] = 'Account created successfully. You can login now.';
                    header('Location: login.php');
                    exit;
                }

                $message = 'Could not create account right now.';
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
<title>User Signup - ABN Construction</title>
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
.auth-container { display: grid; place-items: center; flex: 1; padding: 32px 18px; }
.signup-shell {
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
.brand-kicker { margin: 0 0 8px; font-size: .82rem; letter-spacing: .08em; text-transform: uppercase; opacity: .88; }
.brand-title { margin: 0 0 10px; font-size: 2rem; line-height: 1.1; font-family: "DM Serif Display", serif; }
.brand-text { margin: 0; color: rgba(255, 255, 255, .82); max-width: 30ch; line-height: 1.5; }
.auth-box { background: rgba(10, 42, 94, 0.93); padding: 44px 34px 34px; color: var(--white); }
.auth-box h2 { margin: 0 0 6px; font-size: 1.55rem; letter-spacing: -.01em; }
.sub { margin: 0 0 20px; color: rgba(255, 255, 255, .82); font-size: .95rem; }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 14px; }
.form-group label { color: #fff; font-weight: 600; font-size: .9rem; }
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
.form-group input:focus { outline: none; border-color: rgba(245, 158, 11, .9); background: rgba(255,255,255,.18); }
.password-wrap { position: relative; }
.toggle-eye {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: rgba(255,255,255,.8);
    cursor: pointer;
    font-size: .85rem;
}
.hint { margin-top: 8px; font-size: .78rem; color: rgba(255,255,255,.75); }
.btn-submit {
    width: 100%;
    background: linear-gradient(90deg, var(--accent), var(--accent-2));
    color: #fff;
    padding: 14px;
    border: 0;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 6px;
    transition: transform .18s ease, filter .18s ease;
}
.btn-submit:hover { transform: translateY(-1px); filter: brightness(1.02); }
.message { padding: 10px 12px; border-radius: 10px; margin-bottom: 15px; text-align: left; font-weight: 600; font-size: .9rem; }
.message.error { background: rgba(255, 125, 125, 0.18); border: 1px solid rgba(255, 165, 165, 0.55); color: #ffe4e4; }
.message.success { background: rgba(125, 255, 169, 0.16); border: 1px solid rgba(166, 255, 191, 0.5); color: #dcffe8; }
.helper { color: rgba(255, 255, 255, .9); margin-top: 10px; font-size: .88rem; }
.helper a { color: #ffd49a; text-decoration: none; border-bottom: 1px dotted rgba(255, 212, 154, 0.65); }
@keyframes pageIn { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
@media (max-width: 992px) {
    .signup-shell { grid-template-columns: 1fr; max-width: 680px; }
    .brand-pane { padding: 28px 24px 20px; }
    .brand-title { font-size: 1.55rem; }
    .auth-box { padding: 26px 24px 24px; }
}
@media (max-width: 640px) {
    .auth-container { padding: 14px 10px; }
    .signup-shell { border-radius: 14px; max-width: 100%; }
    .brand-pane { display: none; }
    .auth-box { padding: 20px 14px 18px; }
    .auth-box h2 { font-size: 1.25rem; }
    .sub { font-size: .9rem; }
    .form-group input { font-size: 16px; padding: 13px 12px; }
    .btn-submit { font-size: 1rem; padding: 13px; }
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
    <section class="signup-shell">
        <aside class="brand-pane">
            <p class="brand-kicker">ABN Client Portal</p>
            <h1 class="brand-title">Create Account</h1>
            <p class="brand-text">Set up your client profile to receive project updates, documents, and communication in one place.</p>
        </aside>
        <div class="auth-box">
        <h2>Create User Account</h2>
        <p class="sub">Fill in your details to get started.</p>

        <?php if ($message): ?>
            <p class="message <?= $messageType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($username) ?>" placeholder="e.g. john_doe">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" required>
                        <button type="button" class="toggle-eye" data-target="password">Show</button>
                    </div>
                    <div class="hint">Use at least 8 characters.</div>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrap">
                        <input id="confirm_password" type="password" name="confirm_password" required>
                        <button type="button" class="toggle-eye" data-target="confirm_password">Show</button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <p class="helper">Already have an account? <a href="login.php">Login</a></p>
        <p class="helper"><a href="../index.php">Back to Home</a></p>
        </div>
    </section>
</main>
<footer class="auth-site-footer">
    ABN Real Estate Construction, Cameroon
</footer>

<script>
document.querySelectorAll('.toggle-eye').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = document.getElementById(btn.getAttribute('data-target'));
        if (!target) return;
        var nextType = target.type === 'password' ? 'text' : 'password';
        target.type = nextType;
        btn.textContent = nextType === 'password' ? 'Show' : 'Hide';
    });
});
</script>
</body>
</html>
