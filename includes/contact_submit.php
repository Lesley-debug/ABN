<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/session_bootstrap.php';
require_once __DIR__ . '/config.php';
// Load Composer autoload if available
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    error_log('Missing Composer autoload: ' . $composerAutoload);
}

function expectsJson(): bool
{
    $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    return $requestedWith === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

function respond(bool $ok, string $message, int $httpCode = 200): void
{
    if (expectsJson()) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => $ok,
            'message' => $message,
        ]);
        exit;
    }

    $status = $ok ? 'success' : 'error';
    header('Location: ../contact.php?contact=' . $status);
    exit;
}

function isLocalRequest(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
}

function rateLimitExceeded(mysqli $conn, string $actionKey, string $ipAddress, int $maxAttempts, int $windowMinutes): bool
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

    // Best-effort cleanup of stale rows.
    $cleanupStmt = $conn->prepare('DELETE FROM request_limits WHERE requested_at < ?');
    $cleanupStmt->bind_param('s', $cutoff);
    $cleanupStmt->execute();

    return $hits >= $maxAttempts;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.', 405);
}

$token = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string) ($_SESSION['contact_form_csrf'] ?? '');
$cookieToken = (string) ($_COOKIE['contact_form_csrf'] ?? '');
$isSessionValid = ($sessionToken !== '' && $token !== '' && hash_equals($sessionToken, $token));
$isCookieValid = ($cookieToken !== '' && $token !== '' && hash_equals($cookieToken, $token));
if (!$isSessionValid && !$isCookieValid) {
    respond(false, 'Invalid form submission. Refresh and try again.', 403);
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$project = trim((string)($_POST['project'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    respond(false, 'Please fill all required fields.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please provide a valid email address.', 422);
}

$ipAddress = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
if (rateLimitExceeded($conn, 'contact_form', $ipAddress, 8, 15)) {
    respond(false, 'Too many requests. Please try again in a few minutes.', 429);
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

$userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

$savedToDb = false;
$stmt = $conn->prepare(
    'INSERT INTO contact_messages (name, email, phone, project, subject, message, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
if ($stmt) {
    $stmt->bind_param(
        'ssssssss',
        $name,
        $email,
        $phone,
        $project,
        $subject,
        $message,
        $ipAddress,
        $userAgent
    );
    $savedToDb = $stmt->execute();
}

$smtpHost = envValue('SMTP_HOST', 'smtp.gmail.com') ?? 'smtp.gmail.com';
$smtpPort = (int) (envValue('SMTP_PORT', '587') ?? '587');
$smtpUser = envValue('SMTP_USER', '') ?? '';
$smtpPass = envValue('SMTP_PASS', '') ?? '';
$smtpFrom = envValue('SMTP_FROM', $smtpUser) ?? $smtpUser;
$contactTo = envValue('CONTACT_TO', $smtpUser) ?? $smtpUser;
$smtpPass = str_replace(' ', '', trim($smtpPass));

$mailSent = false;
$mailConfigured = ($smtpUser !== '' && $smtpPass !== '' && $smtpFrom !== '' && $contactTo !== '');
$mailError = '';
if ($mailConfigured) {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;

            $mail->setFrom($smtpFrom, 'ABN Website Contact');
            $mail->addAddress($contactTo);
            $mail->addReplyTo($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'New Contact Message: ' . $subject;
            $mail->Body = sprintf(
                '<h3>New Message from ABN Contact Form</h3>
                <p><strong>Name:</strong> %s</p>
                <p><strong>Email:</strong> %s</p>
                <p><strong>Phone:</strong> %s</p>
                <p><strong>Project:</strong> %s</p>
                <p><strong>Subject:</strong> %s</p>
                <p><strong>Message:</strong><br>%s</p>',
                htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($project !== '' ? $project : '-', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
                nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'))
            );
            $mail->AltBody = "New Message from ABN Contact Form\n"
                . "Name: {$name}\nEmail: {$email}\nPhone: " . ($phone !== '' ? $phone : '-') . "\n"
                . "Project: " . ($project !== '' ? $project : '-') . "\nSubject: {$subject}\n\nMessage:\n{$message}\n";
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailSent = false;
            $mailError = $e->getMessage();
            error_log('Contact SMTP send failed: ' . $e->getMessage());
        }
    } else {
        error_log('PHPMailer not available; cannot send contact email.');
        $mailConfigured = false;
    }
}

if ($mailSent) {
    respond(true, 'Message sent successfully. Check your inbox.');
}

if ($savedToDb) {
    if (!$mailConfigured) {
        respond(true, 'Message saved successfully. Email settings are missing on this server.');
    }

    if (isLocalRequest() && $mailError !== '') {
        respond(false, 'SMTP error: ' . $mailError, 500);
    }

    respond(true, 'Message saved successfully, but email delivery failed. Please check SMTP settings.');
}

respond(false, 'Could not process your message right now. Please try again.', 500);
