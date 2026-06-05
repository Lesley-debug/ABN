<?php
mysqli_report(MYSQLI_REPORT_OFF);
/**
 * Minimal .env loader (project root .env).
 * Values already present in environment variables are not overwritten.
 */
function loadLocalEnv(string $filePath): void
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

        @putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string) $_ENV[$key];
    }

    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string) $_SERVER[$key];
    }

    return $default;
}

function loadPrimaryEnv(): void
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
    $candidates[] = __DIR__ . '/../app.env';
    $candidates[] = __DIR__ . '/../env.txt';

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            loadLocalEnv($candidate);
            break;
        }
    }
}

loadPrimaryEnv();

$host = envValue('DB_HOST', '127.0.0.1');
$user = envValue('DB_USER', 'root');
$pass = envValue('DB_PASS', '');
$db   = envValue('DB_NAME', 'building_blog');
$port = (int) envValue('DB_PORT', '3306');

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    error_log('Database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    die('Service temporarily unavailable.');
}

$conn->set_charset('utf8mb4');
?>
