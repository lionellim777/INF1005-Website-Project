<?php

declare(strict_types=1);

// bootstrap now uses our init_session for safety
require_once __DIR__ . '/init_session.php';

// Fixed System Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');
define('ROLE_USER', 'user');

define('APP_ROOT', dirname(__DIR__));

function app_config(): array
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalize_slashes(string $path): string
{
    return str_replace('\\', '/', $path);
}

function strip_leading_slash(string $path): string {
    $path = trim($path);
    if (str_starts_with($path, '/')) {
        return substr($path, 1);
    }
    return $path;
}

function app_base_path(): string
{
    static $basePath;

    if ($basePath !== null) {
        return $basePath;
    }

    $scriptName = normalize_slashes($_SERVER['SCRIPT_NAME'] ?? '');
    $directory = normalize_slashes(dirname($scriptName));

    $subdirs = ['/admin', '/shop', '/account', '/employee'];
    foreach ($subdirs as $subdir) {
        if (str_ends_with($directory, $subdir)) {
            $directory = substr($directory, 0, -strlen($subdir));
            break;
        }
    }

    if ($directory === '/' || $directory === '.') {
        $directory = '';
    }

    $basePath = rtrim($directory, '/');

    return $basePath;
}

function app_url(string $path = ''): string
{
    $basePath = app_base_path();
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $basePath !== '' ? $basePath . '/' : '/';
    }

    return ($basePath !== '' ? $basePath : '') . '/' . $trimmedPath;
}

function redirect_to(string $path): never
{
    header('Location: ' . app_url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_verify($provided_token) {
    // If either token is missing, fail immediately
    if (empty($_SESSION['csrf_token']) || empty($provided_token)) {
        return false;
    }
    
    // We use hash_equals instead of "==" to prevent timing attacks!
    return hash_equals($_SESSION['csrf_token'], $provided_token);
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}
