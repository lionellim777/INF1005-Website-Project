<?php
/**
 * Authentication & Session Helpers
 * Handles login, roles, and access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.inc.php';

// ── Getters ─────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getRole(): string {
    return $_SESSION['role'] ?? 'guest';
}

function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getUsername(): string {
    return $_SESSION['username'] ?? '';
}

function getFullName(): string {
    return $_SESSION['full_name'] ?? getUsername();
}

// ── Access control ───────────────────────────────────────────
function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $url = '/login.php';
        if ($redirect) $url .= '?redirect=' . urlencode($redirect);
        header('Location: ' . $url);
        exit;
    }
}

function requireEmployee(): void {
    requireLogin($_SERVER['REQUEST_URI']);
    if (!in_array(getRole(), ['employee', 'admin'])) {
        header('Location: /?error=unauthorized');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin($_SERVER['REQUEST_URI']);
    if (getRole() !== 'admin') {
        header('Location: /?error=unauthorized');
        exit;
    }
}

function isEmployee(): bool {
    return in_array(getRole(), ['employee', 'admin']);
}

function isAdmin(): bool {
    return getRole() === 'admin';
}

// ── Login / Logout ───────────────────────────────────────────
function loginUser(string $username, string $password): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, role, full_name, is_active FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }
    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Your account has been suspended.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['full_name']= $user['full_name'] ?? $user['username'];

    // Update last login
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    return ['success' => true, 'role' => $user['role']];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// ── Registration ─────────────────────────────────────────────
function registerUser(string $username, string $email, string $password, string $fullName = ''): array {
    $pdo = getDB();

    // Check for duplicates
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Username or email already taken.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
    $stmt->execute([$username, $email, $hash, $fullName]);

    return ['success' => true];
}

// ── Helpers ──────────────────────────────────────────────────
/** Sanitise output for HTML context (XSS prevention) */
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Get cart item count for current user */
function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
        $stmt->execute([getUserId()]);
        return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
