<?php
require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with($scriptName, '/includes/reset_password.php')) {
    $basePath = rtrim(dirname(dirname($scriptName)), '/');
    $tokenParam = isset($_GET['token']) ? ('?token=' . urlencode((string) $_GET['token'])) : '';
    header('Location: ' . ($basePath !== '' ? $basePath : '') . '/reset_password.php' . $tokenParam, true, 301);
    exit;
}

if (empty($_SESSION['user_reset_csrf'])) {
    $_SESSION['user_reset_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['user_reset_csrf'];

$message = '';
$messageType = 'error';
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$validToken = false;

if ($token !== '') {
    $check = $conn->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
    if ($check === false) {
        error_log('Reset password token check prepare failed: ' . $conn->error);
    } else {
        $check->bind_param('s', $token);
        $check->execute();
        $checkResult = $check->get_result();
        if ($checkResult === false) {
            error_log('Reset password token check get_result failed: ' . $check->error);
        } else {
            $validToken = $checkResult->num_rows === 1;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['user_reset_csrf'] ?? '', $csrfToken)) {
        $message = 'Invalid form submission. Refresh and try again.';
    } elseif (!$validToken) {
        $message = 'Token is invalid or has expired.';
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?');
            if ($update === false) {
                error_log('Reset password update prepare failed: ' . $conn->error);
                $message = 'Could not reset password right now.';
            } else {
                $update->bind_param('ss', $hash, $token);
                if ($update->execute()) {
                    $_SESSION['user_login_success'] = 'Password reset successful. Please login.';
                    header('Location: login.php');
                    exit;
                }
                error_log('Reset password update execute failed: ' . $update->error);
                $message = 'Could not reset password right now.';
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
    <title>Reset Password - ABN Construction</title>
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

        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 40px;
        }

        .auth-box {
            width: 100%;
            max-width: 460px;
            background: var(--primary-blue);
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(0, 18, 72, 0.25);
        }

        .auth-box h2 {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            color: #fff;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: 8px;
            background: rgba(255, 255, 255, .15);
            color: #fff;
        }

        .btn-submit {
            width: 100%;
            background: var(--accent-orange);
            color: #fff;
            padding: 14px;
            border: 0;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }

        .message.error {
            background: #ffd7d7;
            color: #a30000;
        }

        .helper {
            color: #fff;
            text-align: center;
            margin-top: 14px;
            font-size: .9rem;
        }

        .helper a {
            color: var(--accent-orange);
            text-decoration: none;
        }

        @media (max-width: 640px) {
            .auth-container {
                padding: 14px 10px;
            }

            .auth-box {
                padding: 20px 14px 18px;
                border-radius: 14px;
            }

            .form-group input {
                font-size: 16px;
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
        <div class="auth-box">
            <h2>Reset Password</h2>

            <?php if ($message): ?>
                <p class="message error"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn-submit">Reset Password</button>
                </form>
            <?php else: ?>
                <p class="helper">This reset link is invalid or expired.</p>
                <p class="helper"><a href="forgot_password.php">Request another reset link</a></p>
            <?php endif; ?>

            <p class="helper"><a href="login.php">Back to Login</a></p>
        </div>
    </main>
    <footer class="auth-site-footer">
        ABN Real Estate Construction, Cameroon
    </footer>
</body>

</html>