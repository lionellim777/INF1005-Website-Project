<?php
/**
 * Authentication & Session Helpers
 * Handles login, roles, and access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.inc.php';

function allowDemoAuthFallback(): bool {
    // Enabled by default for local development; set ALLOW_DEMO_AUTH_FALLBACK=0 to disable.
    return getenv('ALLOW_DEMO_AUTH_FALLBACK') !== '0';
}

function getDemoUsers(): array {
    // Hash from db/setup.sql for the password "Password1!"
    $demoHash = '$2y$12$wwTa1eGq8JFwu3/CPdGcLOQ8BbVwv8sn5aDV4wuWNcCzV4vSWSWQ6';

    return [
        [
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@pomegranate.com',
            'password_hash' => $demoHash,
            'role' => 'admin',
            'full_name' => 'System Admin',
            'is_active' => 1,
        ],
        [
            'id' => 2,
            'username' => 'employee1',
            'email' => 'employee@pomegranate.com',
            'password_hash' => $demoHash,
            'role' => 'employee',
            'full_name' => 'Alex Chen',
            'is_active' => 1,
        ],
        [
            'id' => 3,
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password_hash' => $demoHash,
            'role' => 'customer',
            'full_name' => 'John Doe',
            'is_active' => 1,
        ],
        [
            'id' => 4,
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password_hash' => $demoHash,
            'role' => 'customer',
            'full_name' => 'Jane Doe',
            'is_active' => 1,
        ],
    ];
}

function loginWithUserRecord(array $user): array {
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];

    return ['success' => true, 'role' => $user['role']];
}

function loginWithDemoFallback(string $username, string $password): array {
    $needle = strtolower(trim($username));
    if ($needle === '') {
        return ['success' => false, 'error' => 'Invalid username or password.'];
    }

    foreach (getDemoUsers() as $user) {
        $matches = strtolower($user['username']) === $needle || strtolower($user['email']) === $needle;
        if (!$matches) {
            continue;
        }
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Your account has been suspended.'];
        }
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        $_SESSION['demo_mode'] = true;
        return loginWithUserRecord($user);
    }

    return ['success' => false, 'error' => 'Invalid username or password.'];
}

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
    try {
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

        $_SESSION['demo_mode'] = false;
        $result = loginWithUserRecord($user);

        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        return $result;
    } catch (Throwable $e) {
        error_log("Login DB Error: " . $e->getMessage());
        if (allowDemoAuthFallback()) {
            return loginWithDemoFallback($username, $password);
        }

        return ['success' => false, 'error' => 'Login is temporarily unavailable. Please try again later.'];
    }
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
