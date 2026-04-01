<?php
/**
 * Admin – User Management Panel
 * View, search, edit roles, activate/deactivate users.
 * Accessible only to admin.
 */
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

require_role(['admin']);

// Get the current logged-in admin (using Zebra Session keys)
$currentSessionUser = [
    'user_id'    => $_SESSION['userid'] ?? $_SESSION['id'] ?? 0,
    'first_name' => $_SESSION['fname'] ?? 'Admin',
    'last_name'  => $_SESSION['lname'] ?? '',
    'email'      => $_SESSION['email'] ?? '',
    'role'       => $_SESSION['role']  ?? 'admin'
];

$successMsg = '';
$errorMsg   = '';

// ---------------------------------------------------------------------------
// HANDLE ROLE CHANGE
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_role') {
    $targetId = (int)($_POST['target_user_id'] ?? 0);
    $newRole  = strtolower(trim($_POST['new_role'] ?? ''));

    if ($targetId === (int)$currentSessionUser['user_id']) {
        $errorMsg = 'You cannot change your own role.';
    } elseif (!in_array($newRole, ['user', 'employee', 'admin'])) {
        $errorMsg = 'Invalid role selected.';
    } else {
        $stmt = $db_conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param("si", $newRole, $targetId);
        if ($stmt->execute()) {
            $successMsg = 'User role updated successfully.';
        } else {
            $errorMsg = 'Failed to update user role.';
        }
    }
}

// ---------------------------------------------------------------------------
// HANDLE STATUS TOGGLE (Active/Inactive)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $targetId = (int)($_POST['target_user_id'] ?? 0);

    if ($targetId === (int)$currentSessionUser['user_id']) {
        $errorMsg = 'You cannot deactivate yourself.';
    } else {
        // Toggle the is_active column directly in MySQL
        $stmt = $db_conn->prepare('UPDATE users SET is_active = IF(is_active=1, 0, 1) WHERE id = ?');
        $stmt->bind_param("i", $targetId);
        if ($stmt->execute()) {
            $successMsg = 'User status updated successfully.';
        } else {
            $errorMsg = 'Failed to update status. Check your database connection.';
        }
    }
}

// ---------------------------------------------------------------------------
// FETCH USERS
// ---------------------------------------------------------------------------
$search = $_GET['search'] ?? '';

// Fetch the is_active column alongside everything else
$sql = 'SELECT id as user_id, fname as first_name, lname as last_name, email, role as role_name, created_at, last_login, is_active FROM users WHERE 1=1';
$params = [];
$types = "";

if ($search) {
    $sql .= ' AND (fname LIKE ? OR lname LIKE ? OR email LIKE ?)';
    $like = "%{$search}%";
    $params = [$like, $like, $like];
    $types .= "sss";
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $db_conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function roleBadgeClass(string $role): string {
    return match (strtolower($role)) {
        'admin'    => 'bg-danger',
        'employee' => 'bg-info text-dark',
        default    => 'bg-secondary',
    };
}

$currentPage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users – Admin – Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body" style="background: var(--bg-primary);">
<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="admin-content dash-main">
        <div class="admin-topbar">
            <div>
                <h4 class="page-title mb-0"><i class="bi bi-people me-2"></i>User Management</h4>
                <small class="page-text">Manage roles and account access</small>
            </div>
        </div>

        <div class="dash-content">
            <?php if ($successMsg): ?><div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

            <div class="dash-table-wrap shadow-sm">
                <div class="dash-table-header d-flex justify-content-between align-items-center">
                    <h6 class="dash-table-title mb-0">Registered Users (<?= count($allUsers) ?>)</h6>
                    <form method="GET" action="" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th class="text-end">Manage Role</th>
                                <th class="text-end pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $u): 
                                // If the DB returns null or 1, they are active. Only explicitly 0 is inactive.
                                $isActive = !isset($u['is_active']) || $u['is_active'] == 1; 
                            ?>
                            <tr class="<?= !$isActive ? 'opacity-50' : '' ?>">
                                <td>
                                    <div class="fw-semibold text-white d-flex align-items-center gap-2">
                                        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                        <?php if (!$isActive): ?><span class="badge bg-danger ms-1" style="font-size:0.6rem;">Inactive</span><?php endif; ?>
                                    </div>
                                    <div class="text-white small"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td><span class="badge <?= roleBadgeClass($u['role_name']) ?>"><?= ucfirst(htmlspecialchars($u['role_name'])) ?></span></td>
                                <td class="text-white small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-white small"><?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?></td>
                                
                                <td class="text-end">
                                    <?php if ($u['user_id'] != $currentSessionUser['user_id']): ?>
                                        <form method="POST" action="" class="d-flex justify-content-end gap-2">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm w-auto bg-dark text-white border-secondary">
                                                <option value="user" <?= $u['role_name'] === 'user' ? 'selected' : '' ?>>User</option>
                                                <option value="employee" <?= $u['role_name'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                                                <option value="admin" <?= $u['role_name'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-white small">You</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-3">
                                    <?php if ($u['user_id'] != $currentSessionUser['user_id']): ?>
                                        <form method="POST" action="" onsubmit="return confirm('<?= $isActive ? "Deactivate" : "Reactivate" ?> this account?');">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                                <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-white small">—</span>
                                    <?php endif; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>