<?php
declare(strict_types=1);

// 1. Send Security Headers immediately to address Audit Baseline
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https://images.unsplash.com; frame-ancestors 'none';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// 2. Load Core Components
require_once __DIR__ . '/db_manager.php';
DB::initialize();
$db_conn = DB::conn(); // Global fallback for existing scripts

require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/security_utils.php';

// 3. System Constants
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');
define('ROLE_USER', 'customer');
define('SITE_NAME', 'Pomegranate Tech');

// 4. Standard Helper Functions
function h(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_url(string $path = ''): string {
    // Force HTTPS for all generated URLs to kill redirect chains
    $protocol = 'https://';
    $host = $_SERVER['HTTP_HOST'] ?? 'pomeshop.duckdns.org';
    return $protocol . $host . '/' . ltrim($path, '/');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify($provided_token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$provided_token);
}

function renderFlash() {
    $html = '';
    $types = ['error' => 'danger', 'success' => 'success', 'warning' => 'warning'];
    foreach ($types as $key => $bootstrapClass) {
        if (!empty($_SESSION[$key])) {
            $html .= '<div class="alert alert-'.$bootstrapClass.' shadow-sm border-0 alert-dismissible fade show">'.h($_SESSION[$key]).'<button type="button" class="btn-close" data-bs-dismiss=\"alert\"></button></div>';
            unset($_SESSION[$key]);
        }
    }
    return $html;
}