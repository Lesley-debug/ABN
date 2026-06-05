<?php
if (!function_exists('loadSecurityEnv')) {
    function loadSecurityEnv(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

if (!function_exists('loadPrimarySecurityEnv')) {
    function loadPrimarySecurityEnv(): void
    {
        $candidates = [];

        $custom = getenv('ABN_ENV_FILE');
        if (is_string($custom) && trim($custom) !== '') {
            $candidates[] = trim($custom);
        }

        // Prefer env files outside web root.
        $candidates[] = dirname(__DIR__, 3) . '/.abn.env';
        $candidates[] = dirname(__DIR__, 2) . '/.abn.env';

        // Fallback for local/dev only.
        $candidates[] = __DIR__ . '/../.env';

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                loadSecurityEnv($candidate);
                break;
            }
        }
    }
}

loadPrimarySecurityEnv();

if (!function_exists('isHttpsRequest')) {
    function isHttpsRequest(): bool
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        );
    }
}

if (!function_exists('enforceHttpsIfConfigured')) {
    function enforceHttpsIfConfigured(): void
    {
        $forceHttps = strtolower((string) (getenv('FORCE_HTTPS') ?: '0'));
        if (!in_array($forceHttps, ['1', 'true', 'yes', 'on'], true)) {
            return;
        }

        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host)) {
            return;
        }

        if (isHttpsRequest()) {
            return;
        }

        $target = 'https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $target, true, 301);
        exit;
    }
}

if (!function_exists('enforceAdminIpAllowlist')) {
    function enforceAdminIpAllowlist(): void
    {
        $raw = trim((string) (getenv('ADMIN_ALLOWED_IPS') ?: ''));
        if ($raw === '') {
            return;
        }

        $clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $allowed = array_filter(array_map('trim', explode(',', $raw)));
        if ($clientIp === '' || !in_array($clientIp, $allowed, true)) {
            http_response_code(403);
            die('Forbidden');
        }
    }
}

enforceHttpsIfConfigured();

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://use.fontawesome.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://use.fontawesome.com; script-src 'self' 'unsafe-inline' https://ajax.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src 'self'; frame-src https://www.google.com https://www.google.com/maps/;");
    if (isHttpsRequest()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $isHttps = isHttpsRequest();
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
