<?php
/**
 * Admin – User Management Panel
 * View, search, edit roles, activate/deactivate, and delete users.
 * Accessible only to users with role 'admin'.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Enforce admin access (require_role already calls require_login)
require_role(['admin']);

function setAdminFlash(string $type, string $message): void {
    $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function getAdminFlash(): ?array {
    $flash = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Database connection (global MySQLi object from bootstrap)
// ---------------------------------------------------------------------------
global $db_conn;
if (!$db_conn || $db_conn->connect_error) {
    die('Database connection failed.');
}

// Current admin user info
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['role'] ?? '';

// ---------------------------------------------------------------------------
// Handle POST actions: change_role, toggle_active, delete_user
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();  // from auth_middleware

    $action = $_POST['action'] ?? '';

    // -----------------------------------------------------------------------
    // Change user role
    // -----------------------------------------------------------------------
    if ($action === 'change_role') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);
        $newRole  = $_POST['new_role'] ?? '';

        // Validate new role
        $allowedRoles = ['customer', 'admin', 'employee'];
        if (!in_array($newRole, $allowedRoles, true)) {
            setAdminFlash('error', 'Invalid role selected.');
        } elseif ($targetId === $currentUserId) {
            setAdminFlash('error', 'You cannot change your own role.');
        } else {
            $stmt = $db_conn->prepare('UPDATE users SET role = ? WHERE id = ?');
            $stmt->bind_param('si', $newRole, $targetId);
            if ($stmt->execute()) {
                setAdminFlash('success', 'User role updated.');
                // Regenerate CSRF token after successful action
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                setAdminFlash('error', 'Database error: ' . $db_conn->error);
            }
            $stmt->close();
        }
        header('Location: ' . app_url('/admin/manage_users.php'));
        exit;
    }

    // -----------------------------------------------------------------------
    // Toggle user active/inactive
    // -----------------------------------------------------------------------
    if ($action === 'toggle_active') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);

        if ($targetId === $currentUserId) {
            setAdminFlash('error', 'You cannot deactivate your own account.');
        } else {
            // Toggle is_active (0/1)
            $stmt = $db_conn->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ?');
            $stmt->bind_param('i', $targetId);
            if ($stmt->execute()) {
                setAdminFlash('success', 'Account status updated.');
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                setAdminFlash('error', 'Database error: ' . $db_conn->error);
            }
            $stmt->close();
        }
        header('Location: ' . app_url('/admin/manage_users.php'));
        exit;
    }

    // -----------------------------------------------------------------------
    // Delete user (new feature)
    // -----------------------------------------------------------------------
    if ($action === 'delete_user') {
        $targetId = (int)($_POST['target_user_id'] ?? 0);

        if ($targetId === $currentUserId) {
            setAdminFlash('error', 'You cannot delete your own account.');
        } else {
            // Optional: check if user has orders? For now, cascade delete is set in DB (orders.user_id FK ON DELETE CASCADE)
            $stmt = $db_conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $targetId);
            if ($stmt->execute()) {
                setAdminFlash('success', 'User permanently deleted.');
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                setAdminFlash('error', 'Delete failed: ' . $db_conn->error);
            }
            $stmt->close();
        }
        header('Location: ' . app_url('/admin/manage_users.php'));
        exit;
    }
}

// ---------------------------------------------------------------------------
// Fetch users with search & role filters
// ---------------------------------------------------------------------------
$search     = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

$sql = "SELECT id, fname, lname, email, role, is_active, created_at, last_login
        FROM users WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if ($roleFilter !== '' && in_array($roleFilter, ['customer', 'admin', 'employee'], true)) {
    $sql .= " AND role = ?";
    $params[] = $roleFilter;
    $types .= 's';
}
$sql .= " ORDER BY created_at DESC";

$stmt = $db_conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$allUsers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Role options for filter dropdown
$roleOptions = [
    'customer' => 'Customer',
    'admin'    => 'Administrator',
    'employee' => 'Employee'
];

$currentPage = 'users';
$pageTitle   = 'Users – Pomegranate';
$flash = getAdminFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('/admin/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>User Management</h4>
                <small class="text-muted"><?= count($allUsers) ?> user(s) found</small>
            </div>
        </div>

        <div class="p-4">
            <!-- Flash messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type'] === 'success' ? 'success' : 'danger') ?> alert-dismissible fade show auto-dismiss" role="alert">
                    <?= h($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search & Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Name or email..."
                                   value="<?= h($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <?php foreach ($roleOptions as $val => $label): ?>
                                    <option value="<?= h($val) ?>" <?= $roleFilter === $val ? 'selected' : '' ?>>
                                        <?= h($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <?php if ($search || $roleFilter): ?>
                                <a href="<?= app_url('/admin/manage_users.php') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($allUsers as $u): ?>
                                <tr class="<?= $u['is_active'] ? '' : 'table-secondary' ?>">
                                    <td class="ps-3 text-muted small">#<?= (int)$u['id'] ?></td>
                                    <td class="fw-semibold">
                                        <?= h($u['fname'] . ' ' . $u['lname']) ?>
                                    </td>
                                    <td class="small"><?= h($u['email']) ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                                            <select name="new_role"
                                                    class="form-select form-select-sm d-inline-block w-auto"
                                                    onchange="this.form.submit();"
                                                    <?= ((int)$u['id'] === $currentUserId) ? 'disabled' : '' ?>>
                                                <?php foreach ($roleOptions as $val => $label): ?>
                                                    <option value="<?= h($val) ?>" <?= $u['role'] === $val ? 'selected' : '' ?>>
                                                        <?= h($label) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($u['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if ((int)$u['id'] !== $currentUserId): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit"
                                                            class="btn <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-sm"
                                                            onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this account?');">
                                                        <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" action="" class="d-inline ms-1"
                                                      onsubmit="return confirm('Permanently delete this user? This action cannot be undone.');">
                                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="target_user_id" value="<?= (int)$u['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash3"></i> Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss flash messages after 5 seconds
    document.querySelectorAll('.auto-dismiss').forEach(el => {
        setTimeout(() => el.classList.add('fade', 'show'), 3000);
        setTimeout(() => el.remove(), 5000);
    });
</script>
</body>
</html>