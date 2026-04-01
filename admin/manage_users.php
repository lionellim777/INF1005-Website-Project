<?php
/**
 * Admin – User Management Panel
 * View, search, edit roles, activate/deactivate users.
 * Accessible only to role_id >= 4 (admin).
 *
 */

require_once __DIR__ . '/../auth/auth_helper.php';
initSession();
requireRole(4);

$user      = currentUser();
$isOffline = isOfflineMode();
$pdo       = $isOffline ? null : getDBConnection();

// ---------------------------------------------------------------------------
// HANDLE ROLE CHANGE
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'change_role') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        $targetId  = (int)($_POST['target_user_id'] ?? 0);
        $newRoleId = (int)($_POST['new_role_id'] ?? 0);

        if ($targetId === $user['user_id']) {
            setFlash('error', 'You cannot change your own role.');
        } elseif ($newRoleId < 1 || $newRoleId > 4) {
            setFlash('error', 'Invalid role selected.');
        } else {
            if ($isOffline) {
                offlineChangeUserRole($targetId, $newRoleId);
            } else {
                $pdo->prepare('UPDATE users SET role_id = ? WHERE user_id = ?')
                    ->execute([$newRoleId, $targetId]);
            }
            logActivity($user['user_id'], 'admin_role_change', "Changed user #{$targetId} to role {$newRoleId}");
            setFlash('success', 'User role updated.');
        }
    }
    header('Location: ' . appUrl('/admin/manage_users.php'));
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE ACCOUNT TOGGLE (activate/deactivate)
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        $targetId = (int)($_POST['target_user_id'] ?? 0);

        if ($targetId === $user['user_id']) {
            setFlash('error', 'You cannot deactivate your own account.');
        } else {
            if ($isOffline) {
                offlineToggleUserActive($targetId);
            } else {
                $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = ?')
                    ->execute([$targetId]);
            }
            logActivity($user['user_id'], 'admin_toggle_account', "Toggled active status for user #{$targetId}");
            setFlash('success', 'Account status updated.');
        }
    }
    header('Location: ' . appUrl('/admin/manage_users.php'));
    exit;
}

// ---------------------------------------------------------------------------
// FETCH USERS
// ---------------------------------------------------------------------------
$search     = sanitize($_GET['search'] ?? '');
$roleFilter = (int)($_GET['role'] ?? 0);

if ($isOffline) {
    $allUsers = getOfflineUsersForAdmin();

    if ($search) {
        $needle   = strtolower($search);
        $allUsers = array_values(array_filter($allUsers, static function (array $u) use ($needle): bool {
            $full  = strtolower(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
            $email = strtolower((string)($u['email'] ?? ''));
            return str_contains($full, $needle) || str_contains($email, $needle);
        }));
    }

    if ($roleFilter) {
        $allUsers = array_values(array_filter($allUsers, static function (array $u) use ($roleFilter): bool {
            return (int)($u['role_id'] ?? 0) === $roleFilter;
        }));
    }

    $roles = getOfflineRoles();
} else {
    $sql    = 'SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE 1=1';
    $params = [];

    if ($search) {
        $sql   .= ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
        $like   = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like]);
    }
    if ($roleFilter) {
        $sql     .= ' AND u.role_id = ?';
        $params[] = $roleFilter;
    }

    $sql  .= ' ORDER BY u.created_at DESC';
    $stmt  = $pdo->prepare($sql);
    $stmt->execute($params);
    $allUsers = $stmt->fetchAll();

    $roles = $pdo->query('SELECT * FROM roles ORDER BY role_id')->fetchAll();
}

$currentPage = 'users';
$pageTitle   = 'Users – Admin – ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= appUrl('/admin/css/admin.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">

        <!-- Top bar -->
        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>User Management</h4>
                <small class="text-muted"><?= count($allUsers) ?> user(s) found</small>
            </div>
        </div>

        <div class="p-4">
            <div class="admin-flash">
                    <?= renderFlash() ?>
                </div>

            <?php if ($isOffline): ?>
                <div class="alert alert-info small">
                    <i class="bi bi-wifi-off me-1"></i>Offline demo mode — changes are simulated in session.
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
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="0">All Roles</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['role_id'] ?>"
                                        <?= $roleFilter === (int)$r['role_id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($r['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <?php if ($search || $roleFilter): ?>
                                <a href="<?= appUrl('/admin/manage_users.php') ?>" class="btn btn-outline-secondary">
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
                                    <td class="ps-3 text-muted small">#<?= $u['user_id'] ?></td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                            <select name="new_role_id"
                                                    class="form-select form-select-sm d-inline-block w-auto"
                                                    onchange="this.form.submit();"
                                                    <?= ($u['user_id'] === $user['user_id']) ? 'disabled' : '' ?>>
                                                <?php foreach ($roles as $r): ?>
                                                    <option value="<?= $r['role_id'] ?>"
                                                        <?= (int)$u['role_id'] === (int)$r['role_id'] ? 'selected' : '' ?>>
                                                        <?= ucfirst($r['role_name']) ?>
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
                                        <?php if ($u['user_id'] !== $user['user_id']): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="target_user_id" value="<?= $u['user_id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                                        onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Reactivate' ?> this account?');">
                                                    <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /p-4 -->
    </div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
