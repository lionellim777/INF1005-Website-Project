<?php
/**
 * Admin – User Management Panel
 * - Staff table: always shows admins & employees
 * - User search: search by name/email, edit from results
 * Accessible only to admin.
 */
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

require_role(['admin']);

$currentSessionUser = [
    'user_id' => $_SESSION['user_id'] ?? 0,
    'fname'   => $_SESSION['fname']   ?? 'Admin',
    'lname'   => $_SESSION['lname']   ?? '',
    'email'   => $_SESSION['email']   ?? '',
    'role'    => $_SESSION['role']    ?? 'admin',
];

$successMsg = '';
$errorMsg   = '';

// ---------------------------------------------------------------------------
// POST: CHANGE ROLE
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
        $successMsg = $stmt->execute() ? 'Role updated successfully.' : 'Failed to update role.';
        if (!$stmt->execute()) $errorMsg = 'Failed to update role.';
    }
}

// ---------------------------------------------------------------------------
// POST: DELETE USER
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $targetId = (int)($_POST['target_user_id'] ?? 0);

    if ($targetId === (int)$currentSessionUser['user_id']) {
        $errorMsg = 'You cannot delete your own account.';
    } else {
        $stmt = $db_conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param("i", $targetId);
        if ($stmt->execute()) {
            $successMsg = 'User deleted permanently.';
        } else {
            $errorMsg = 'Failed to delete user. They may have active orders linked to them.';
        }
    }
}

// ---------------------------------------------------------------------------
// FETCH STAFF (admins + employees only)
// ---------------------------------------------------------------------------
$staffStmt = $db_conn->prepare("
    SELECT id as user_id, fname as first_name, lname as last_name,
           email, role as role_name, created_at, last_login, is_active
    FROM users
    WHERE role IN ('admin', 'employee')
    ORDER BY role ASC, fname ASC
");
$staffStmt->execute();
$staffUsers = $staffStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ---------------------------------------------------------------------------
// FETCH USERS (search only)
// ---------------------------------------------------------------------------
$search      = trim($_GET['search'] ?? '');
$searchResults = [];

if ($search !== '') {
    $like = "%{$search}%";
    $searchStmt = $db_conn->prepare("
        SELECT id as user_id, fname, lname, email, role as role_name, created_at, last_login, is_active
        FROM users
        WHERE role = 'customer'
          AND (fname LIKE ? OR lname LIKE ? OR email LIKE ?)
        ORDER BY fname ASC
    ");
    $searchStmt->bind_param("sss", $like, $like, $like);
    $searchStmt->execute();
    $searchResults = $searchStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ---------------------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------------------
function roleBadgeClass(string $role): string {
    return match (strtolower($role)) {
        'admin'    => 'bg-danger',
        'employee' => 'bg-info text-dark',
        default    => 'bg-secondary',
    };
}

function isActive(array $u): bool {
    return !isset($u['is_active']) || $u['is_active'] == 1;
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
                <small class="page-text">Manage staff roles and customer accounts</small>
            </div>
        </div>

        <div class="dash-content">

            <?php if ($successMsg): ?>
                <div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <!-- ============================================================
                 SECTION 1: STAFF (Admins & Employees)
            ============================================================ -->
            <div class="dash-table-wrap shadow-sm mb-4">
                <div class="dash-table-header">
                    <h6 class="dash-table-title mb-0">
                        <i class="bi bi-shield-lock me-2"></i>Staff — Admins & Employees (<?= count($staffUsers) ?>)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th class="text-end">Change Role</th>
                                <th class="text-end pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffUsers as $u):
                                $active = isActive($u);
                            ?>
                            <tr class="<?= !$active ? 'opacity-50' : '' ?>">
                                <td>
                                    <div class="fw-semibold text-white d-flex align-items-center gap-2">
                                        <?= htmlspecialchars($u['fname'] . ' ' . $u['lname']) ?>
                                        <?php if (!$active): ?>
                                            <span class="badge bg-danger ms-1" style="font-size:0.6rem;">Inactive</span>
                                        <?php endif; ?>
                                        <?php if ($u['user_id'] == $currentSessionUser['user_id']): ?>
                                            <span class="badge bg-secondary ms-1" style="font-size:0.6rem;">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-white small"><?= htmlspecialchars($u['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= roleBadgeClass($u['role_name']) ?>">
                                        <?= ucfirst(htmlspecialchars($u['role_name'])) ?>
                                    </span>
                                </td>
                                <td class="text-white small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-white small">
                                    <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
                                </td>

                                <!-- Change Role -->
                                <td class="text-end">
                                    <?php if ($u['user_id'] != $currentSessionUser['user_id']): ?>
                                        <form method="POST" class="d-flex justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm w-auto bg-dark text-white border-secondary">
                                                <option value="user"     <?= $u['role_name'] === 'user'     ? 'selected' : '' ?>>User</option>
                                                <option value="employee" <?= $u['role_name'] === 'employee' ? 'selected' : '' ?>>Employee</option>
                                                <option value="admin"    <?= $u['role_name'] === 'admin'    ? 'selected' : '' ?>>Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-white small">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Delete Staff -->
                                <td class="text-end pe-3">
                                    <?php if ($u['user_id'] != $currentSessionUser['user_id']): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure? This will permanently remove this user.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Delete
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

            <!-- ============================================================
                 SECTION 2: CUSTOMER SEARCH
            ============================================================ -->
            <div class="dash-table-wrap shadow-sm">
                <div class="dash-table-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="dash-table-title mb-0">
                        <i class="bi bi-person-circle me-2"></i>Customer Accounts
                    </h6>
                    <form method="GET" action="" class="d-flex gap-2">
                        <input type="text" name="search"
                               class="form-control form-control-sm bg-dark text-white border-secondary"
                               placeholder="Search by name or email…"
                               value="<?= htmlspecialchars($search) ?>"
                               style="min-width: 240px;">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <?php if ($search): ?>
                            <a href="/admin/manage_users.php" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if ($search === ''): ?>
                    <div class="text-center py-5 text-white">
                        <i class="bi bi-search" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mt-2 mb-0" style="opacity: 0.5;">Search for a customer by name or email to manage their account.</p>
                    </div>

                <?php elseif (empty($searchResults)): ?>
                    <div class="text-center py-5 text-white">
                        <i class="bi bi-person-x" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mt-2 mb-0" style="opacity: 0.5;">No customers found for "<?= htmlspecialchars($search) ?>".</p>
                    </div>

                <?php else: ?>
                    <div class="table-responsive">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th class="text-end">Promote to Staff</th>
                                    <th class="text-end pe-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($searchResults as $u):
                                    $active = isActive($u);
                                ?>
                                <tr class="<?= !$active ? 'opacity-50' : '' ?>">
                                    <td>
                                        <div class="fw-semibold text-white d-flex align-items-center gap-2">
                                            <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                            <?php if (!$active): ?>
                                                <span class="badge bg-danger ms-1" style="font-size:0.6rem;">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-white small"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td class="text-white small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                    <td class="text-white small">
                                        <?= $u['last_login'] ? date('d M Y, H:i', strtotime($u['last_login'])) : 'Never' ?>
                                    </td>

                                    <!-- Promote to staff -->
                                    <td class="text-end">
                                        <form method="POST" class="d-flex justify-content-end gap-2">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <select name="new_role" class="form-select form-select-sm w-auto bg-dark text-white border-secondary">
                                                <option value="user"     selected>User</option>
                                                <option value="employee">Employee</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                        </form>
                                    </td>

                                    <!-- Delete Customer -->
                                    <td class="text-end pe-3">
                                        <form method="POST" onsubmit="return confirm('Are you sure? This will permanently remove this customer account.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /.dash-content -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>