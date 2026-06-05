<?php
require_once __DIR__ . '/session_bootstrap.php';
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with($scriptName, '/includes/signin.php')) {
    $basePath = rtrim(dirname(dirname($scriptName)), '/');
    header('Location: ' . ($basePath !== '' ? $basePath : '') . '/admin-login.php', true, 301);
    exit;
}

enforceAdminIpAllowlist();
require_once __DIR__ . '/config.php';
$message = '';

if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['admin_login_csrf'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['admin_login_csrf'] ?? '', $csrfToken)) {
        $message = "Invalid form submission. Refresh and try again.";
    } else {
        $email_or_username = trim((string) ($_POST['email_or_username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $identifier = mb_substr($email_or_username, 0, 190);
        $ipAddress = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $lockWindowMinutes = 15;
        $maxAttempts = 5;
        $cutoff = date('Y-m-d H:i:s', time() - ($lockWindowMinutes * 60));

        $conn->query(
            "CREATE TABLE IF NOT EXISTS login_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            identifier VARCHAR(190) NOT NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_login_ip_time (ip_address, attempted_at),
            KEY idx_login_identifier_time (identifier, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $stmtLock = $conn->prepare(
            'SELECT (SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at >= ?) AS ip_hits,
                (SELECT COUNT(*) FROM login_attempts WHERE identifier = ? AND attempted_at >= ?) AS id_hits'
        );
        if ($stmtLock === false) {
            error_log('Admin login lock check prepare failed: ' . $conn->error);
            $message = "Unable to complete login right now. Please try again later.";
        } else {
            $stmtLock->bind_param('ssss', $ipAddress, $cutoff, $identifier, $cutoff);
            $stmtLock->execute();
            $lockResult = $stmtLock->get_result();
            if ($lockResult === false) {
                error_log('Admin login lock check get_result failed: ' . $stmtLock->error);
                $message = "Unable to complete login right now. Please try again later.";
            } else {
                $lockCounts = $lockResult->fetch_assoc();
                $ipHits = (int) ($lockCounts['ip_hits'] ?? 0);
                $idHits = (int) ($lockCounts['id_hits'] ?? 0);

                if ($ipHits >= $maxAttempts || $idHits >= $maxAttempts) {
                    $message = "Too many login attempts. Try again in 15 minutes.";
                } else {
                    $isAuthenticated = false;

                    $stmt = $conn->prepare("SELECT id, username, password FROM admin WHERE username = ? LIMIT 1");
                    if ($stmt === false) {
                        error_log('Admin login SELECT prepare failed: ' . $conn->error);
                        $message = "Unable to complete login right now. Please try again later.";
                    } else {
                        $stmt->bind_param("s", $email_or_username);
                        $stmt->execute();
                        $adminResult = $stmt->get_result();

                        if ($adminResult !== false && $adminResult->num_rows === 1) {
                            $admin = $adminResult->fetch_assoc();
                            if (password_verify($password, $admin['password'])) {
                                session_regenerate_id(true);
                                $_SESSION['user_id'] = (int) $admin['id'];
                                $_SESSION['username'] = $admin['username'];
                                $_SESSION['role'] = 'admin';
                                $_SESSION['logged_in_at'] = time();
                                $isAuthenticated = true;

                                $clearStmt = $conn->prepare('DELETE FROM login_attempts WHERE ip_address = ? OR identifier = ?');
                                if ($clearStmt !== false) {
                                    $clearStmt->bind_param('ss', $ipAddress, $identifier);
                                    $clearStmt->execute();
                                }

                                header("Location: ../admin/dashboard.php");
                                exit;
                            }
                        } elseif ($adminResult === false) {
                            error_log('Admin login SELECT get_result failed: ' . $stmt->error);
                            $message = "Unable to complete login right now. Please try again later.";
                        }

                        if ($message === "") {
                            $failStmt = $conn->prepare('INSERT INTO login_attempts (ip_address, identifier) VALUES (?, ?)');
                            if ($failStmt !== false) {
                                $failStmt->bind_param('ss', $ipAddress, $identifier);
                                $failStmt->execute();
                            }
                            $message = "Invalid credentials.";
                        }
                    }
                }
            }
        }
    }
}
?>
<style>
    /* Base Theme Colors */
    :root {
        --primary-blue: rgba(0, 18, 72, 0.7);
        --accent-orange: rgba(255, 94, 21, 1);
        --text-dark: #001248;
        --text-light: #ffffff;
        --bg-light: #f4f4f7;
        --bg-dark: #0a0f24;
    }

    /* Light Mode Defaults */
    body {
        font-family: "Poppins", sans-serif;
        margin: 0;
        background: var(--bg-light);
        color: var(--text-dark);
        overflow-x: hidden;
    }

    /* 🌙 Auto Dark Mode */
    @media (prefers-color-scheme: dark) {
        body {
            background: var(--bg-dark);
            color: var(--text-light);
        }

        .auth-box {
            background: rgba(0, 18, 72, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text {
            color: white !important;
        }
    }

    /* Main wrapper */
    .auth-container {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
        min-height: 100vh;
    }

    /* Text Class (Title Fix) */
    .text {
        color: var(--text-light);
    }

    /* ✨ ANIMATIONS */

    /* Fade + Slide Down */
    @keyframes fadeSlide {
        0% {
            opacity: 0;
            transform: translateY(-25px) scale(0.95);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Gentle floating hover animation */
    @keyframes floatBox {
        0% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-6px);
        }

        100% {
            transform: translateY(0);
        }
    }

    /* Form Box */
    .auth-box {
        width: 100%;
        max-width: 450px;
        background: var(--primary-blue);
        padding: 35px;
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(0, 18, 72, 0.25);
        backdrop-filter: blur(8px);
        animation: fadeSlide 0.9s ease forwards;
        transition: all 0.3s ease-in-out;
    }

    /* Hover floating animation */
    .auth-box:hover {
        animation: floatBox 3s ease-in-out infinite;
    }

    /* Title */
    .auth-box h2 {
        text-align: center;
        font-size: 1.8rem;
        margin-bottom: 20px;
        font-weight: 700;
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        font-size: 0.95rem;
        font-weight: 600;
        color: white;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        margin-top: 8px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-size: 1rem;
        outline: none;
        transition: 0.2s;
        background: rgba(255, 255, 255, 0.15);
        color: white;
    }

    .form-group input::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    .form-group input:focus {
        border-color: var(--accent-orange);
        box-shadow: 0 0 6px rgba(255, 94, 21, 0.5);
        background: rgba(255, 255, 255, 0.25);
    }

    /* Button */
    .btn-submit {
        width: 100%;
        background: var(--accent-orange);
        color: white;
        padding: 14px;
        font-size: 1.1rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.25s;
    }

    .btn-submit:hover {
        background: rgba(255, 94, 21, 0.9);
        transform: translateY(-3px);
    }

    /* Error Message */
    .error-message {
        background: rgb(255, 215, 215);
        color: #a30000;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
    }

    /* Extra Text for Company */
    .brand-info {
        margin-top: 25px;
        text-align: center;
        font-size: 0.9rem;
        opacity: 0.9;
        color: white;
    }

    .brand-info strong {
        color: var(--accent-orange);
    }
</style>

<main class="auth-container">
    <div class="auth-box">
        <h2 class="text">Admin Login</h2>

        <?php if ($message): ?>
            <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="email_or_username" required placeholder="Enter admin username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>

            <button type="submit" class="btn-submit">Login</button>
        </form>

        <div class="brand-info">
            Powered by <strong>ABN Building Construction, Cameroon</strong><br>
            Building the future — one project at a time.
        </div>
    </div>
</main>