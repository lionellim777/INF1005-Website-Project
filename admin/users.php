<?php
require_once "../inc/auth.inc.php";
requireAdmin();
header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;

$pdo   = null;
$users = [];
$msg   = $err = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireValidCsrf($_POST['csrf_token'] ?? null);

        $pdo    = getDB();
        $action = $_POST['action'] ?? '';
        $uid    = (int)($_POST['user_id'] ?? 0);

        if ($action === 'add') {
            $un   = trim($_POST['username'] ?? '');
            $em   = strtolower(trim((string)($_POST['email'] ?? '')));
            $pw   = $_POST['password'] ?? '';
            $fn   = trim((string)($_POST['full_name'] ?? ''));
            $role = toDbRole((string)($_POST['role'] ?? 'user'));

            if (!$un || !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $un)) {
                $err = 'Username must be 3-50 characters and use only letters, numbers, and underscores.';
            } elseif (!$em || !filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $err = 'A valid email address is required.';
            } elseif (strlen($pw) < 8 || !preg_match('/[A-Z]/', $pw) || !preg_match('/[0-9]/', $pw)) {
                $err = 'Password must be at least 8 characters with one uppercase letter and one number.';
            } elseif ($fn !== '' && strlen($fn) > 100) {
                $err = 'Full name must be 100 characters or fewer.';
            } elseif ($fn !== '' && !preg_match('/^[a-zA-Z0-9 .,\-\'"]+$/', $fn)) {
                $err = 'Full name contains unsupported characters.';
            } else {
                $dupStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
                $dupStmt->execute([$un, $em]);
                if ($dupStmt->fetch()) {
                    $err = 'Username or email is already in use.';
                } else {
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO users (username,email,password_hash,full_name,role) VALUES (?,?,?,?,?)")
                        ->execute([$un,$em,$hash,$fn,$role]);
                    $msg = "User '{$un}' created successfully.";
                }
            }

        } elseif ($action === 'edit' && $uid) {
            $fn      = trim((string)($_POST['full_name'] ?? ''));
            $em      = strtolower(trim((string)($_POST['email'] ?? '')));
            $role    = toDbRole((string)($_POST['role'] ?? 'user'));
            $active  = (int)isset($_POST['is_active']);
            $newPw   = (string)($_POST['new_password'] ?? '');

            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $err = 'A valid email address is required.';
            } elseif ($fn !== '' && strlen($fn) > 100) {
                $err = 'Full name must be 100 characters or fewer.';
            } elseif ($fn !== '' && !preg_match('/^[a-zA-Z0-9 .,\-\'"]+$/', $fn)) {
                $err = 'Full name contains unsupported characters.';
            } elseif ($newPw !== '' && (strlen($newPw) < 8 || !preg_match('/[A-Z]/', $newPw) || !preg_match('/[0-9]/', $newPw))) {
                $err = 'New password must be at least 8 characters with one uppercase letter and one number.';
            } else {
                $dupStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                $dupStmt->execute([$em, $uid]);
                if ($dupStmt->fetch()) {
                    $err = 'That email address is already in use.';
                }
            }

            // Prevent administrator from deactivating themselves
            if (!$err && $uid === getUserId() && !$active) {
                $err = 'You cannot suspend your own account.';
            } elseif (!$err && $uid === getUserId() && normalizeRole($role) !== 'administrator') {
                $err = 'You cannot remove your own administrator role.';
            }

            if (!$err) {
                $stmt = $pdo->prepare("UPDATE users SET full_name=?,email=?,role=?,is_active=? WHERE id=?");
                $stmt->execute([$fn,$em,$role,$active,$uid]);

                // Update password only if provided
                if ($newPw !== '') {
                    $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")
                        ->execute([password_hash($newPw, PASSWORD_DEFAULT), $uid]);
                }
                $msg = 'User updated.';
            }
        } elseif ($action === 'toggle_status' && $uid) {
            if ($uid === getUserId()) {
                $err = 'You cannot suspend your own account.';
            } else {
                $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id=?")->execute([$uid]);
                $msg = 'User status toggled.';
            }

        } elseif ($action === 'delete' && $uid) {
            if ($uid === getUserId()) {
                $err = 'You cannot delete your own account.';
            } else {
                $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
                $msg = 'User deleted.';
            }

        } elseif ($action === 'change_role' && $uid) {
            $role = toDbRole((string)($_POST['role'] ?? 'user'));
            if ($uid === getUserId() && normalizeRole($role) !== 'administrator') {
                $err = 'You cannot change your own role from administrator.';
            } else {
                $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
                $msg = 'Role updated.';
            }
        } else {
            $err = 'Unsupported action.';
        }

    } catch (RuntimeException $e) {
        $err = $e->getMessage();
    } catch (Throwable $e) {
        error_log('Admin users page error: ' . $e->getMessage());
        $err = 'Database error. Ensure the database is connected.';
    }

    if ($msg) { header("Location: users.php?msg=" . urlencode($msg)); exit; }
}

// Fetch all users
try {
    if (!$pdo) $pdo = getDB();
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {
    $users = [
        ['id'=>1,'username'=>'admin',    'full_name'=>'System Admin','email'=>'admin@pomegranate.com', 'role'=>'admin',   'is_active'=>1,'created_at'=>'2025-01-01','last_login'=>'2025-04-01 08:00:00'],
        ['id'=>2,'username'=>'employee1','full_name'=>'Alex Chen',   'email'=>'employee@pomegranate.com','role'=>'employee','is_active'=>1,'created_at'=>'2025-01-02','last_login'=>'2025-04-01 07:30:00'],
        ['id'=>3,'username'=>'johndoe',  'full_name'=>'John Doe',    'email'=>'john@example.com',     'role'=>'customer','is_active'=>1,'created_at'=>'2025-02-10','last_login'=>'2025-03-28 14:00:00'],
        ['id'=>4,'username'=>'janedoe',  'full_name'=>'Jane Doe',    'email'=>'jane@example.com',     'role'=>'customer','is_active'=>1,'created_at'=>'2025-02-15','last_login'=>'2025-03-26 17:00:00'],
    ];
    $err = 'Demo mode – DB not connected. Changes will not persist.';
}

$flashMsg = $_GET['msg'] ?? $msg;
$flashErr = $err;

function normalizedRoleForSelect(string $dbRole): string {
    return normalizeRole($dbRole);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users – Administrator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="dash-wrapper">
    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="/index.php">
                <img src="/assets/logo.png" alt="Logo" height="28" class="rounded-2">
                Pomegranate
            </a>
            <div class="sidebar-role-badge role-admin">
                <i class="bi bi-shield-lock"></i> Administrator
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Users</div>
            <a href="users.php"    class="sidebar-link active"><i class="bi bi-people"></i> Users</a>
            <div class="sidebar-section-label">Employee View</div>
            <a href="../employee/products.php" class="sidebar-link"><i class="bi bi-person-badge"></i> Employee Products</a>
            <a href="/catalog.php"          class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="/logout.php" class="m-0">
                <?= csrfInput() ?>
                <button type="submit" class="sidebar-user sidebar-user-btn">
                    <div class="sidebar-avatar" style="background:linear-gradient(135deg,#f87171,#818cf8);">
                        <?= strtoupper(substr(getUsername(),0,1)) ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="name"><?= h(getFullName()) ?></div>
                        <div class="role">Sign out</div>
                    </div>
                    <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ── MAIN ── -->
    <div id="main-content" class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle" type="button" aria-label="Toggle sidebar menu"><i class="bi bi-list"></i></button>
                <span class="page-title">User Management</span>
            </div>
            <button type="button" class="btn-dash-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add User
            </button>
        </div>

        <div class="dash-content">

            <?php if ($flashMsg): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($flashMsg) ?>
            </div>
            <?php endif; ?>
            <?php if ($flashErr): ?>
            <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i><?= h($flashErr) ?>
            </div>
            <?php endif; ?>

            <!-- Users table -->
            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title"><?= count($users) ?> Users</span>
                    <div class="search-bar-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control-dark" placeholder="Search users…"
                               style="padding-left:2.25rem;width:220px;font-size:.82rem;"
                               aria-label="Search users table"
                               data-table-search="users-table">
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table" id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <?php
                                $normalizedRole = normalizedRoleForSelect((string)$u['role']);
                                $roleClass = $normalizedRole === 'administrator'
                                    ? 'status-admin'
                                    : ($normalizedRole === 'employee' ? 'status-employee' : 'status-active');
                            ?>
                            <tr>
                                <td class="text-white-50"><?= $u['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar" style="width:32px;height:32px;font-size:.8rem;<?= $normalizedRole==='administrator'?'background:linear-gradient(135deg,#f87171,#818cf8);':'' ?>">
                                            <?= strtoupper(substr($u['username'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-white small"><?= h($u['full_name'] ?: $u['username']) ?></div>
                                            <div class="text-white-50" style="font-size:.72rem;">@<?= h($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-white-50" style="font-size:.8rem;"><?= h($u['email']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="action"  value="change_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="form-control-dark status-badge <?= $roleClass ?>"
                                                onchange="this.form.submit()"
                                                style="cursor:pointer;font-size:.72rem;padding:.25rem .5rem;width:auto;border:none;background:transparent;">
                                            <option value="user"           <?= $normalizedRole==='user'          ?'selected':'' ?>>User</option>
                                            <option value="employee"       <?= $normalizedRole==='employee'      ?'selected':'' ?>>Employee</option>
                                            <option value="administrator"  <?= $normalizedRole==='administrator' ?'selected':'' ?>>Administrator</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfInput() ?>
                                        <input type="hidden" name="action"  value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="status-badge <?= $u['is_active'] ? 'status-active' : 'status-inactive' ?>"
                                                style="background:none;border:none;cursor:pointer;<?= $u['id']==getUserId()?'opacity:.5;pointer-events:none;':'' ?>"
                                                title="Click to toggle">
                                            <?= $u['is_active'] ? 'Active' : 'Suspended' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-white-50" style="font-size:.78rem;">
                                    <?= $u['last_login'] ? date('d M y H:i', strtotime($u['last_login'])) : '—' ?>
                                </td>
                                <td class="text-white-50" style="font-size:.78rem;">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn-icon" title="Edit user" aria-label="Edit user <?= h($u['username']) ?>"
                                                onclick="openEditUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($u['id'] !== getUserId()): ?>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action"  value="delete">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn-icon danger"
                                                    data-confirm="Permanently delete user '<?= h($u['username']) ?>'? This cannot be undone."
                                                    aria-label="Delete user <?= h($u['username']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <button type="button" class="btn-icon" disabled title="Cannot delete yourself" aria-label="Cannot delete your own account" style="opacity:.35;">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

<!-- ── ADD USER MODAL ── -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-person-plus me-2" style="color:var(--cyan);"></i>Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Full Name</label>
                            <input type="text" name="full_name" class="form-control-dark" placeholder="John Doe" maxlength="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Username *</label>
                            <input type="text" name="username" class="form-control-dark" placeholder="johndoe" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control-dark" placeholder="john@example.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Password * (min 8 chars, uppercase + number)</label>
                            <input type="password" name="password" class="form-control-dark" placeholder="Password" required minlength="8">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Role</label>
                            <select name="role" class="form-control-dark" style="cursor:pointer;">
                                <option value="user">User</option>
                                <option value="employee">Employee</option>
                                <option value="administrator">Administrator</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-person-check"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── EDIT USER MODAL ── -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-pencil me-2" style="color:var(--purple);"></i>Edit User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="users.php" id="editUserForm">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_uid">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control-dark" maxlength="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control-dark">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-white-50 small fw-semibold">Role</label>
                            <select name="role" id="edit_role" class="form-control-dark" style="cursor:pointer;">
                                <option value="user">User</option>
                                <option value="employee">Employee</option>
                                <option value="administrator">Administrator</option>
                            </select>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                                <input type="checkbox" name="is_active" id="edit_active" class="form-check-input" style="width:18px;height:18px;" checked>
                                <span class="text-white-50 small fw-semibold">Account Active</span>
                            </label>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">
                                New Password <span class="text-white-50" style="font-weight:400;">(leave blank to keep current)</span>
                            </label>
                            <input type="password" name="new_password" class="form-control-dark" placeholder="New password (min 8 chars)" minlength="8">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/dashboard.js"></script>
<script>
function openEditUser(u) {
    document.getElementById('edit_uid').value       = u.id;
    document.getElementById('edit_full_name').value = u.full_name || '';
    document.getElementById('edit_email').value     = u.email || '';
    const roleMap = { customer: 'user', user: 'user', employee: 'employee', admin: 'administrator', administrator: 'administrator' };
    document.getElementById('edit_role').value      = roleMap[u.role] || 'user';
    document.getElementById('edit_active').checked  = !!parseInt(u.is_active);
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>
</body>
</html>
