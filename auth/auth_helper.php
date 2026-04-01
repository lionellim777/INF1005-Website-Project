<?php
/**
 * Authentication & Session Helper
 * Handles session init, CSRF protection, role checks, input sanitization,
 * login/logout, and activity logging.
 */

require_once __DIR__ . '/../config/db_config.php';

// ---------------------------------------------------------------------------
// PATH HELPERS
// ---------------------------------------------------------------------------

/**
 * Resolve the app base path (supports subfolder deployments).
 * Example: '' at domain root, '/INF1005-Website-Project' in a subfolder.
 */
function appBasePath(): string {
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $envBase = getenv('APP_BASE_PATH');
    if ($envBase !== false && trim((string)$envBase) !== '') {
        $envBase = '/' . trim((string)$envBase, '/');
        $base = ($envBase === '/') ? '' : rtrim($envBase, '/');
        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $requestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if ($script === '') {
        $base = '';
        return $base;
    }

    // Prefer URI/script matching when available (covers alias/subfolder deployments).
    if ($requestPath !== '' && str_ends_with($requestPath, $script)) {
        $prefix = substr($requestPath, 0, strlen($requestPath) - strlen($script));
        $prefix = rtrim($prefix, '/');
        if ($prefix !== '' && $prefix !== '/') {
            $base = $prefix;
            return $base;
        }
    }

    // Infer app base from known subfolders first.
    $guess = '';
    $matchedMarker = false;
    foreach (['/auth/', '/admin/', '/inc/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) {
            $guess = substr($script, 0, $pos);
            $matchedMarker = true;
            break;
        }
    }

    if (!$matchedMarker && $script !== '') {
        $dir = dirname($script);
        $guess = ($dir === '.' || $dir === '/') ? '' : $dir;
    }

    $base = rtrim($guess, '/');
    return $base;
}

/**
 * Build a URL anchored to the app base path.
 */
function appUrl(string $path = '/'): string {
    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return $path;
    }

    $base = appBasePath();
    if ($path === '' || $path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . '/' . ltrim($path, '/');
}

// ---------------------------------------------------------------------------
// SESSION BOOTSTRAP
// ---------------------------------------------------------------------------

/**
 * Start a secure session with hardened cookie parameters.
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $lifetime = SESSION_LIFETIME;

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);

    session_start();

    // Regenerate ID every 30 minutes to prevent fixation
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}

// ---------------------------------------------------------------------------
// OFFLINE DEMO MODE (NO SQL REQUIRED)
// ---------------------------------------------------------------------------

/**
 * Returns true when app should use offline session-backed demo data.
 * Priority: APP_OFFLINE_MODE env var -> DB availability auto-detect.
 */
function isOfflineMode(): bool {
    static $offline = null;

    if ($offline !== null) {
        return $offline;
    }

    $env = getenv('APP_OFFLINE_MODE');
    if ($env !== false && $env !== '') {
        $offline = in_array(strtolower(trim((string)$env)), ['1', 'true', 'yes', 'on'], true);
        return $offline;
    }

    $dsn = 'mysql:host=' . DB_HOST
         . ';port=' . DB_PORT
         . ';dbname=' . DB_NAME
         . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE  => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT  => 1,
        ]);
        $pdo = null;
        $offline = false;
    } catch (Throwable $e) {
        $offline = true;
        $verbose = getenv('APP_VERBOSE_OFFLINE_LOG');
        if ($verbose !== false && $verbose !== '' && in_array(strtolower(trim((string)$verbose)), ['1', 'true', 'yes', 'on'], true)) {
            error_log('Offline demo mode enabled: ' . $e->getMessage());
        }
    }

    return $offline;
}

/**
 * Role ID -> role name mapping.
 */
function roleNameFromId(int $roleId): string {
    return match ($roleId) {
        4 => 'admin',
        3 => 'employee',
        2 => 'user',
        default => 'guest',
    };
}

/**
 * Demo role rows for admin UI.
 */
function getOfflineRoles(): array {
    return [
        ['role_id' => 1, 'role_name' => 'guest', 'description' => 'Temporary guest with read-only access'],
        ['role_id' => 2, 'role_name' => 'user', 'description' => 'Registered logged-in user'],
        ['role_id' => 3, 'role_name' => 'employee', 'description' => 'Employee with limited edit privileges'],
        ['role_id' => 4, 'role_name' => 'admin', 'description' => 'Site administrator with full access'],
    ];
}

/**
 * Seed users used in offline mode.
 */
function offlineSeedUsers(): array {
    $users = [
        1 => [
            'user_id' => 1,
            'first_name' => 'Site',
            'last_name' => 'Admin',
            'email' => 'admin@pomegranate.com',
            'phone' => '90001001',
            'password_hash' => '$2b$12$WCPUTspgnsK59gFYDYkigOOvCDhdplriSjQTIrlmzclzQTx0eA34q',
            'role_id' => 4,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-01-01 10:00:00',
            'updated_at' => '2025-01-01 10:00:00',
        ],
        2 => [
            'user_id' => 2,
            'first_name' => 'Jane',
            'last_name' => 'Staff',
            'email' => 'employee@pomegranate.com',
            'phone' => '90001002',
            'password_hash' => '$2b$12$Q3UXBU1wS5iNJeHEZ0Z3xOv4DFEtKuJgszK6aTi1JZ1QGskoqYjZ.',
            'role_id' => 3,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-01-05 10:00:00',
            'updated_at' => '2025-01-05 10:00:00',
        ],
        3 => [
            'user_id' => 3,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'user@pomegranate.com',
            'phone' => '90001003',
            'password_hash' => '$2b$12$ino28wavFU76uoep7GZ2gepq.Mdo0jul1dve5u.rfLYmQEqB0BSUO',
            'role_id' => 2,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-01-10 10:00:00',
            'updated_at' => '2025-01-10 10:00:00',
        ],
        4 => [
            'user_id' => 4,
            'first_name' => 'QA',
            'last_name' => 'Admin',
            'email' => 'qa.admin@pomegranate.com',
            'phone' => '90000001',
            'password_hash' => '$2y$12$ZpV6KilC/oiz.NwYDchNB..4nlvuE/PblabLjmpepLX4s23FO6xQu',
            'role_id' => 4,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-02-01 09:00:00',
            'updated_at' => '2025-02-01 09:00:00',
        ],
        5 => [
            'user_id' => 5,
            'first_name' => 'QA',
            'last_name' => 'Employee',
            'email' => 'qa.employee@pomegranate.com',
            'phone' => '90000002',
            'password_hash' => '$2y$12$diHxnM8BtcSriZXGPkVvcedGI45MCte.1oPwSEwer8F0v5QTqF56a',
            'role_id' => 3,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-02-02 09:00:00',
            'updated_at' => '2025-02-02 09:00:00',
        ],
        6 => [
            'user_id' => 6,
            'first_name' => 'Test',
            'last_name' => 'User One',
            'email' => 'qa.user1@pomegranate.com',
            'phone' => '90000003',
            'password_hash' => '$2y$12$30uZPG5mOecjUiwnplnqieoB8BKBkvFrP.9ACh.hquJnPmVGmDvMC',
            'role_id' => 2,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-02-03 09:00:00',
            'updated_at' => '2025-02-03 09:00:00',
        ],
        7 => [
            'user_id' => 7,
            'first_name' => 'Test',
            'last_name' => 'User Two',
            'email' => 'qa.user2@pomegranate.com',
            'phone' => '90000004',
            'password_hash' => '$2y$12$KbgHqm3bJOIy.GKXZUY2e.49VRX3cIvvpksxwTXOgEqqBvgowG.ye',
            'role_id' => 2,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-02-04 09:00:00',
            'updated_at' => '2025-02-04 09:00:00',
        ],
        8 => [
            'user_id' => 8,
            'first_name' => 'Test',
            'last_name' => 'User Three',
            'email' => 'qa.user3@pomegranate.com',
            'phone' => '90000005',
            'password_hash' => '$2y$12$vxg3mZnduWOsKG8wsdHEcOHCFqcMy0OWyZoyv3YFFv.HDqHSdDyxy',
            'role_id' => 2,
            'avatar_url' => null,
            'is_active' => 1,
            'email_verified' => 1,
            'last_login' => null,
            'failed_logins' => 0,
            'locked_until' => null,
            'created_at' => '2025-02-05 09:00:00',
            'updated_at' => '2025-02-05 09:00:00',
        ],
    ];

    foreach ($users as &$user) {
        $user['role_name'] = roleNameFromId((int)$user['role_id']);
    }
    unset($user);

    return $users;
}

/**
 * Ensure offline session-backed storage exists.
 */
function ensureOfflineState(): void {
    initSession();

    if (!isset($_SESSION['offline_users']) || !is_array($_SESSION['offline_users'])) {
        $_SESSION['offline_users'] = offlineSeedUsers();
    }

    if (!isset($_SESSION['offline_activity']) || !is_array($_SESSION['offline_activity'])) {
        $_SESSION['offline_activity'] = [];
    }

    if (!isset($_SESSION['offline_sessions']) || !is_array($_SESSION['offline_sessions'])) {
        $_SESSION['offline_sessions'] = [];
    }

    if (!isset($_SESSION['_offline_user_seq']) || !is_int($_SESSION['_offline_user_seq'])) {
        $maxId = 0;
        foreach ($_SESSION['offline_users'] as $u) {
            $maxId = max($maxId, (int)$u['user_id']);
        }
        $_SESSION['_offline_user_seq'] = $maxId + 1;
    }
}

/**
 * @return array<int,array>
 */
function getOfflineUsers(): array {
    ensureOfflineState();
    return $_SESSION['offline_users'];
}

/**
 * @param array<int,array> $users
 */
function saveOfflineUsers(array $users): void {
    $_SESSION['offline_users'] = $users;
}

/**
 * Find an offline user by email.
 */
function offlineFindUserByEmail(string $email): ?array {
    $needle = strtolower(trim($email));
    foreach (getOfflineUsers() as $u) {
        if (strtolower((string)$u['email']) === $needle) {
            return $u;
        }
    }
    return null;
}

/**
 * Find an offline user by ID.
 */
function offlineFindUserById(int $userId): ?array {
    $users = getOfflineUsers();
    return $users[$userId] ?? null;
}

/**
 * Persist an offline user row.
 */
function saveOfflineUser(array $user): void {
    $users = getOfflineUsers();
    $userId = (int)($user['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $user['role_name'] = roleNameFromId((int)$user['role_id']);
    $user['updated_at'] = date('Y-m-d H:i:s');

    $users[$userId] = $user;
    saveOfflineUsers($users);
}

/**
 * True if email already exists in offline users.
 */
function offlineEmailExists(string $email, ?int $excludeUserId = null): bool {
    $needle = strtolower(trim($email));
    foreach (getOfflineUsers() as $u) {
        if ($excludeUserId !== null && (int)$u['user_id'] === $excludeUserId) {
            continue;
        }
        if (strtolower((string)$u['email']) === $needle) {
            return true;
        }
    }
    return false;
}

/**
 * Create a new offline demo user and return user ID.
 */
function createOfflineUser(string $firstName, string $lastName, string $email, ?string $phone, string $password, int $roleId = 2): int {
    ensureOfflineState();

    $newId = (int)$_SESSION['_offline_user_seq'];
    $_SESSION['_offline_user_seq'] = $newId + 1;

    $row = [
        'user_id' => $newId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => strtolower(trim($email)),
        'phone' => $phone ?: null,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
        'role_id' => $roleId,
        'role_name' => roleNameFromId($roleId),
        'avatar_url' => null,
        'is_active' => 1,
        'email_verified' => 1,
        'last_login' => null,
        'failed_logins' => 0,
        'locked_until' => null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $users = getOfflineUsers();
    $users[$newId] = $row;
    saveOfflineUsers($users);

    return $newId;
}

/**
 * Update profile data for an offline user.
 */
function updateOfflineUserProfile(int $userId, string $firstName, string $lastName, string $email, ?string $phone): bool {
    $user = offlineFindUserById($userId);
    if (!$user) {
        return false;
    }

    $user['first_name'] = $firstName;
    $user['last_name']  = $lastName;
    $user['email']      = strtolower(trim($email));
    $user['phone']      = $phone ?: null;
    saveOfflineUser($user);
    return true;
}

/**
 * Update password hash for an offline user.
 */
function updateOfflineUserPassword(int $userId, string $passwordHash): bool {
    $user = offlineFindUserById($userId);
    if (!$user) {
        return false;
    }

    $user['password_hash'] = $passwordHash;
    saveOfflineUser($user);
    return true;
}

/**
 * Change role for an offline user.
 */
function offlineChangeUserRole(int $targetUserId, int $newRoleId): bool {
    $user = offlineFindUserById($targetUserId);
    if (!$user) {
        return false;
    }

    $user['role_id'] = $newRoleId;
    $user['role_name'] = roleNameFromId($newRoleId);
    saveOfflineUser($user);

    return true;
}

/**
 * Toggle active state for an offline user.
 */
function offlineToggleUserActive(int $targetUserId): bool {
    $user = offlineFindUserById($targetUserId);
    if (!$user) {
        return false;
    }

    $user['is_active'] = empty($user['is_active']) ? 1 : 0;
    saveOfflineUser($user);
    return true;
}

/**
 * Returns all offline users sorted by created date desc.
 */
function getOfflineUsersForAdmin(): array {
    $users = array_values(getOfflineUsers());
    usort($users, static function (array $a, array $b): int {
        return strcmp((string)$b['created_at'], (string)$a['created_at']);
    });
    return $users;
}

/**
 * Returns demo orders in same shape as dashboard DB query.
 */
function getOfflineOrdersForUser(int $userId): array {
    $samples = [
        1 => [
            ['order_id' => 1001, 'total_amount' => 1299.99, 'status' => 'delivered', 'created_at' => '2025-01-15 10:30:00', 'items' => 'Pomegranate Phone Pro'],
            ['order_id' => 1002, 'total_amount' => 849.00,  'status' => 'shipped',   'created_at' => '2025-02-20 14:15:00', 'items' => 'Pomegranate Tablet Air'],
            ['order_id' => 1003, 'total_amount' => 199.99,  'status' => 'processing','created_at' => '2025-03-10 09:00:00', 'items' => 'Pomegranate Wireless Buds'],
        ],
        2 => [
            ['order_id' => 1101, 'total_amount' => 549.00,  'status' => 'delivered', 'created_at' => '2025-02-01 11:00:00', 'items' => 'Pomegranate Watch SE'],
        ],
        3 => [
            ['order_id' => 1201, 'total_amount' => 1299.99, 'status' => 'shipped',   'created_at' => '2025-03-05 16:45:00', 'items' => 'Pomegranate Phone Pro'],
        ],
        4 => [
            ['order_id' => 1301, 'total_amount' => 59.90,   'status' => 'delivered', 'created_at' => '2025-03-11 12:00:00', 'items' => 'QA Accessory Pack'],
        ],
        5 => [
            ['order_id' => 1401, 'total_amount' => 89.00,   'status' => 'processing','created_at' => '2025-03-12 16:20:00', 'items' => 'Staff Demo Kit'],
        ],
    ];

    return $samples[$userId] ?? [];
}

/**
 * Get recent offline activity for a given user.
 */
function getOfflineActivityLog(int $userId, int $limit = 10): array {
    ensureOfflineState();
    $all = array_reverse($_SESSION['offline_activity']);
    $filtered = [];

    foreach ($all as $row) {
        if ((int)($row['user_id'] ?? 0) === $userId) {
            $filtered[] = $row;
        }
        if (count($filtered) >= $limit) {
            break;
        }
    }

    return $filtered;
}

// ---------------------------------------------------------------------------
// CSRF PROTECTION
// ---------------------------------------------------------------------------

/**
 * Generate or retrieve the CSRF token for the current session.
 */
function csrfToken(): string {
    initSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Return a hidden input field containing the CSRF token.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Validate the submitted CSRF token.
 */
function verifyCsrf(): bool {
    initSession();
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ---------------------------------------------------------------------------
// INPUT SANITIZATION
// ---------------------------------------------------------------------------

/**
 * Sanitize a string: trim, strip tags, encode special chars.
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim(strip_tags($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate an email address.
 */
function validateEmail(string $email): string|false {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

// ---------------------------------------------------------------------------
// AUTHENTICATION
// ---------------------------------------------------------------------------

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    initSession();
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user's data, or null.
 *
 * @return array|null
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    // Cache in session to avoid repeated queries
    if (!empty($_SESSION['_user_cache']) && $_SESSION['_user_cache']['user_id'] === $_SESSION['user_id']) {
        return $_SESSION['_user_cache'];
    }

    if (isOfflineMode()) {
        $user = offlineFindUserById((int)$_SESSION['user_id']);

        if (!$user || empty($user['is_active'])) {
            // Account deactivated or deleted — force logout
            logout();
            return null;
        }

        $user['role_name'] = roleNameFromId((int)$user['role_id']);
        $_SESSION['_user_cache'] = $user;
        return $user;
    }

    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('
        SELECT u.*, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.user_id = :uid AND u.is_active = 1
    ');
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // Account deactivated or deleted — force logout
        logout();
        return null;
    }

    $_SESSION['_user_cache'] = $user;
    return $user;
}

/**
 * Require the user to be logged in; redirect to login page otherwise.
 */
function requireLogin(string $redirect = '/auth/login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . appUrl($redirect));
        exit;
    }
}

/**
 * Require a minimum role level: guest(1) < user(2) < employee(3) < admin(4).
 */
function requireRole(int $minRoleId, string $redirect = '/index.php'): void {
    requireLogin();
    $user = currentUser();
    if (!$user || (int)$user['role_id'] < $minRoleId) {
        $_SESSION['flash_error'] = 'You do not have permission to access that page.';
        header('Location: ' . appUrl($redirect));
        exit;
    }
}

/**
 * Check whether the current user has at least the given role level.
 */
function hasRole(int $minRoleId): bool {
    $user = currentUser();
    return $user && (int)$user['role_id'] >= $minRoleId;
}

/**
 * Get the display-friendly role name.
 */
function roleBadgeClass(string $roleName): string {
    return match ($roleName) {
        'admin'    => 'bg-danger',
        'employee' => 'bg-warning text-dark',
        'user'     => 'bg-info',
        default    => 'bg-secondary',
    };
}

// ---------------------------------------------------------------------------
// LOGIN / LOGOUT
// ---------------------------------------------------------------------------

/**
 * Attempt to log a user in. Returns an error message on failure, or null on success.
 */
function loginUser(string $email, string $password): ?string {
    if (isOfflineMode()) {
        $user = offlineFindUserByEmail($email);

        if (!$user) {
            return 'Invalid email or password.';
        }

        // Check account lock
        if (!empty($user['locked_until']) && strtotime((string)$user['locked_until']) > time()) {
            $remaining = ceil((strtotime((string)$user['locked_until']) - time()) / 60);
            return "Account locked. Try again in {$remaining} minute(s).";
        }

        if (empty($user['is_active'])) {
            return 'This account has been deactivated. Please contact support.';
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            $attempts = (int)$user['failed_logins'] + 1;
            $user['failed_logins'] = $attempts;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $user['locked_until'] = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            }
            saveOfflineUser($user);

            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                return 'Too many failed attempts. Account locked for 15 minutes.';
            }
            return 'Invalid email or password.';
        }

        // Successful login — reset failed attempts, set last login
        $user['failed_logins'] = 0;
        $user['locked_until']  = null;
        $user['last_login']    = date('Y-m-d H:i:s');
        saveOfflineUser($user);

        session_regenerate_id(true);
        $_SESSION['user_id']   = (int)$user['user_id'];
        $_SESSION['role_id']   = (int)$user['role_id'];
        $_SESSION['role_name'] = roleNameFromId((int)$user['role_id']);
        $_SESSION['_created']  = time();
        unset($_SESSION['_user_cache']);

        recordSession((int)$user['user_id']);
        logActivity((int)$user['user_id'], 'login', 'User logged in (offline mode).');

        return null;
    }

    $pdo = getDBConnection();

    $stmt = $pdo->prepare('
        SELECT u.*, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE u.email = :email
    ');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return 'Invalid email or password.';
    }

    // Check account lock
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return "Account locked. Try again in {$remaining} minute(s).";
    }

    if (!$user['is_active']) {
        return 'This account has been deactivated. Please contact support.';
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment failed attempts
        $attempts = (int)$user['failed_logins'] + 1;
        $lock     = ($attempts >= MAX_LOGIN_ATTEMPTS)
            ? ", locked_until = DATE_ADD(NOW(), INTERVAL " . LOCKOUT_DURATION . " SECOND)"
            : "";
        $pdo->prepare("UPDATE users SET failed_logins = ? {$lock} WHERE user_id = ?")
            ->execute([$attempts, $user['user_id']]);

        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            return 'Too many failed attempts. Account locked for 15 minutes.';
        }
        return 'Invalid email or password.';
    }

    // Successful login — reset failed attempts, set last login
    $pdo->prepare('UPDATE users SET failed_logins = 0, locked_until = NULL, last_login = NOW() WHERE user_id = ?')
        ->execute([$user['user_id']]);

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['role_id']   = $user['role_id'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['_created']  = time();

    // Record session in DB
    recordSession($user['user_id']);

    // Log activity
    logActivity($user['user_id'], 'login', 'User logged in.');

    return null; // success
}

/**
 * Log the user out and destroy the session.
 */
function logout(): void {
    initSession();

    if (!empty($_SESSION['user_id'])) {
        if (isOfflineMode()) {
            terminateSession(session_id());
            logActivity((int)$_SESSION['user_id'], 'logout', 'User logged out (offline mode).');
        } else {
            // Mark DB session as inactive
            $pdo = getDBConnection();
            $pdo->prepare('UPDATE user_sessions SET is_active = 0 WHERE session_id = ?')
                ->execute([session_id()]);
            logActivity($_SESSION['user_id'], 'logout', 'User logged out.');
        }
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

// ---------------------------------------------------------------------------
// SESSION TRACKING (DB-backed with offline fallback)
// ---------------------------------------------------------------------------

/**
 * Record or update the current session in the database.
 */
function recordSession(int $userId): void {
    if (isOfflineMode()) {
        ensureOfflineState();

        $sid = session_id();
        $now = date('Y-m-d H:i:s');
        $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $existing = $_SESSION['offline_sessions'][$sid] ?? null;
        $_SESSION['offline_sessions'][$sid] = [
            'session_id'   => $sid,
            'user_id'      => $userId,
            'ip_address'   => $ip,
            'user_agent'   => $ua,
            'device_label' => parseDeviceLabel($ua),
            'created_at'   => $existing['created_at'] ?? $now,
            'last_active'  => $now,
            'expires_at'   => date('Y-m-d H:i:s', time() + SESSION_LIFETIME),
            'is_active'    => 1,
        ];

        return;
    }

    $pdo = getDBConnection();
    $sid = session_id();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Derive a human-readable device label
    $device = parseDeviceLabel($ua);

    $stmt = $pdo->prepare('
        INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, device_label, expires_at)
        VALUES (:sid, :uid, :ip, :ua, :device, DATE_ADD(NOW(), INTERVAL :lifetime SECOND))
        ON DUPLICATE KEY UPDATE last_active = NOW(), is_active = 1
    ');
    $stmt->execute([
        ':sid'      => $sid,
        ':uid'      => $userId,
        ':ip'       => $ip,
        ':ua'       => $ua,
        ':device'   => $device,
        ':lifetime' => SESSION_LIFETIME,
    ]);
}

/**
 * Parse user agent into a short device description.
 */
function parseDeviceLabel(string $ua): string {
    $os      = 'Unknown OS';
    $browser = 'Unknown Browser';

    if (preg_match('/Windows/i', $ua))       $os = 'Windows';
    elseif (preg_match('/Macintosh/i', $ua)) $os = 'macOS';
    elseif (preg_match('/Linux/i', $ua))     $os = 'Linux';
    elseif (preg_match('/Android/i', $ua))   $os = 'Android';
    elseif (preg_match('/iPhone/i', $ua))    $os = 'iPhone';

    if (preg_match('/Chrome\/[\d.]+/i', $ua) && !preg_match('/Edg/i', $ua)) $browser = 'Chrome';
    elseif (preg_match('/Firefox/i', $ua))   $browser = 'Firefox';
    elseif (preg_match('/Safari/i', $ua) && !preg_match('/Chrome/i', $ua)) $browser = 'Safari';
    elseif (preg_match('/Edg/i', $ua))       $browser = 'Edge';

    return "$browser on $os";
}

/**
 * Terminate a specific session for the current user.
 */
function terminateSession(string $sessionId): bool {
    $user = currentUser();
    if (!$user) {
        return false;
    }

    if (isOfflineMode()) {
        ensureOfflineState();
        if (empty($_SESSION['offline_sessions'][$sessionId])) {
            return false;
        }

        $sess = $_SESSION['offline_sessions'][$sessionId];
        if ((int)$sess['user_id'] !== (int)$user['user_id']) {
            return false;
        }

        $_SESSION['offline_sessions'][$sessionId]['is_active'] = 0;
        $_SESSION['offline_sessions'][$sessionId]['last_active'] = date('Y-m-d H:i:s');
        return true;
    }

    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('UPDATE user_sessions SET is_active = 0 WHERE session_id = ? AND user_id = ?');
    $stmt->execute([$sessionId, $user['user_id']]);
    return $stmt->rowCount() > 0;
}

/**
 * Get all active sessions for a user.
 */
function getUserSessions(int $userId): array {
    if (isOfflineMode()) {
        ensureOfflineState();

        // Keep current session heartbeat fresh.
        recordSession($userId);

        $rows = [];
        $now = time();
        foreach ($_SESSION['offline_sessions'] as $sess) {
            if ((int)$sess['user_id'] !== $userId) {
                continue;
            }
            if (empty($sess['is_active'])) {
                continue;
            }
            if (strtotime((string)$sess['expires_at']) <= $now) {
                continue;
            }
            $rows[] = $sess;
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string)$b['last_active'], (string)$a['last_active']);
        });

        return $rows;
    }

    $pdo  = getDBConnection();
    $stmt = $pdo->prepare('
        SELECT * FROM user_sessions
        WHERE user_id = :uid AND is_active = 1 AND expires_at > NOW()
        ORDER BY last_active DESC
    ');
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------------
// ACTIVITY LOGGING
// ---------------------------------------------------------------------------

function logActivity(?int $userId, string $action, string $details = ''): void {
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (isOfflineMode()) {
        ensureOfflineState();
        $_SESSION['offline_activity'][] = [
            'log_id'      => count($_SESSION['offline_activity']) + 1,
            'user_id'     => $userId,
            'action'      => $action,
            'details'     => $details,
            'ip_address'  => $ip,
            'created_at'  => date('Y-m-d H:i:s'),
        ];
        return;
    }

    $pdo = getDBConnection();
    $pdo->prepare('INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $action, $details, $ip]);
}

// ---------------------------------------------------------------------------
// FLASH MESSAGES
// ---------------------------------------------------------------------------

/**
 * Set a flash message (survives one redirect).
 */
function setFlash(string $type, string $message): void {
    initSession();
    $_SESSION["flash_{$type}"] = $message;
}

/**
 * Get and clear a flash message.
 */
function getFlash(string $type): ?string {
    initSession();
    $msg = $_SESSION["flash_{$type}"] ?? null;
    unset($_SESSION["flash_{$type}"]);
    return $msg;
}

/**
 * Render Bootstrap alerts for flash messages.
 */
function renderFlash(): string {
    $html = '';
    foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'] as $key => $cls) {
        $msg = getFlash($key);
        if ($msg) {
            $safe = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
            $html .= "<div class=\"alert alert-{$cls} alert-dismissible fade show\" role=\"alert\">{$safe}
                <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button></div>";
        }
    }
    return $html;
}
