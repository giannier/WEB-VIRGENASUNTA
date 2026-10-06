<?php
/**
 * Shared bootstrap: session, config loading, CSRF helpers, small utilities.
 * Included by partials/header.php and by api/complaint-submit.php so both
 * sides of the form share one source of truth for config and CSRF tokens.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

if (!defined('VA_ROOT')) {
    define('VA_ROOT', dirname(__DIR__));
}

/**
 * Load config/config.php if present, otherwise fall back to the example
 * template (placeholders only) so the site keeps rendering before the
 * client hands over real credentials.
 */
function va_config(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $real = VA_ROOT . '/config/config.php';
    $example = VA_ROOT . '/config/config.example.php';

    if (file_exists($real)) {
        $config = require $real;
    } else {
        // TODO: copy config/config.example.php to config/config.php and fill
        // in real values (RUC, razón social, complaints email, Turnstile
        // keys) before this site goes live.
        $config = file_exists($example) ? require $example : [];
        error_log('[virgenasunta] WARNING: config/config.php missing, using placeholder config.example.php');
    }

    return $config;
}

function va_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function va_csrf_verify(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function va_site_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'www.virgenasunta.pe';
    return $scheme . '://' . $host;
}

function va_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
