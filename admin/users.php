<?php
// 1. Boot the engine and secure the page
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

// ONLY Admins can manage users
require_role([ROLE_ADMIN]); 

$users = [];
$msg   = $err = '';

// Safely extract the logged-in admin's info from Zebra Session
$admin_id = $_SESSION['userid'] ?? $_SESSION['id'] ?? 0; // Fallbacks depending on your init_session.php
$admin_fname = $_SESSION['fname'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_fname, 0, 1));

// 2. Handle Form Actions using MySQLi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        $uid    = (int)($_POST['user_id'] ?? 0);

        if ($action === 'add') {
            $fn   = sanitize_input($_POST['fname'] ?? '');
            $ln   = sanitize_input($_POST['lname'] ?? '');
            $em   = sanitize_input($_POST['email'] ?? '');
            $pw   = $_POST['password'] ?? '';
            $role = in_array($_POST['role'] ?? '', [ROLE_USER, ROLE_EMPLOYEE, ROLE_ADMIN]) ? $_POST['role'] : ROLE_USER;

            if (!$fn || !$em || strlen($pw) < 8) {
                $err = 'First name, valid email, and a password of at least 8 characters are required.';
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $db_conn->prepare("INSERT INTO users (fname, lname, email, password, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $fn, $ln, $em, $hash, $role);
                
                if ($stmt->execute()) {
                    $msg = "User '{$fn}' created successfully.";
                } else {
                    $err = "Could not create user. Email may already exist.";
                }
                $stmt->close();
            }

        } elseif ($action === 'edit' && $uid) {
            $fn   = sanitize_input($_POST['fname'] ?? '');
            $ln   = sanitize_input($_POST['lname'] ?? '');
            $em   = sanitize_input($_POST['email'] ?? '');
            $role = in_array($_POST['role'] ?? '', [ROLE_USER, ROLE_EMPLOYEE, ROLE_ADMIN]) ? $_POST['role'] : ROLE_USER;

            if ($uid === $admin_id && $role !== ROLE_ADMIN) {
                $err = 'You cannot remove your own admin role.';
            } else {
                $stmt = $db_conn->prepare("UPDATE users SET fname=?, lname=?, email=?, role=? WHERE id=?");
                $stmt->bind_param("ssssi", $fn, $ln, $em, $role, $uid);
                $stmt->execute();
                $stmt->close();

                // Update password only if a new one was provided
                if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 8) {
                    $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt_pw = $db_conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $stmt_pw->bind_param("si", $hash, $uid);
                    $stmt_pw->execute();
                    $stmt_pw->close();
                }
                $msg = 'User updated successfully.';
            }

        } elseif ($action === 'change_role' && $uid) {
            $role = in_array($_POST['role'] ?? '', [ROLE_USER, ROLE_EMPLOYEE, ROLE_ADMIN]) ? $_POST['role'] : ROLE_USER;
            if ($uid === $admin_id && $role !== ROLE_ADMIN) {
                $err = 'You cannot change your own role.';
            } else {
                $stmt = $db_conn->prepare("UPDATE users SET role=? WHERE id=?");
                $stmt->bind_param("si", $role, $uid);
                $stmt->execute();
                $stmt->close();
                $msg = 'Role updated.';
            }

        } elseif ($action === 'delete' && $uid) {
            if ($uid === $admin_id) {
                $err = 'You cannot delete your own account.';
            } else {
                $stmt = $db_conn->prepare("DELETE FROM users WHERE id=?");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $stmt->close();
                $msg = 'User permanently deleted.';
            }
        }

    } catch (Exception $e) {
        $err = 'Database error. Ensure the database is connected.';
    }

    if ($msg) { header("Location: users.php?msg=" . urlencode($msg)); exit; }
}

// 3. Fetch all users
if (isset($db_conn)) {
    // Note: We alias fname and lname to match the UI loop below
    $query = "SELECT id, fname, lname, email, role FROM users ORDER BY id DESC";
    $result = $db_conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} else {
    $err = 'Database connection failed.';
}

$flashMsg = $_GET['msg'] ?? $msg;
$flashErr = $err;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users – Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/user-ui.css">;
    <link rel="stylesheet" href="/css/dashboard.css">
</head>

<body>
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="dash-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="/index.php">
                <img src="/assets/logo.png" alt="Logo" height="28" class="rounded-2">
                Pomegranate
            </a>
            <div class="sidebar-role-badge role-admin">
                <i class="bi bi-shield-lock"></i> Admin
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a href="index.php"    class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="users.php"    class="sidebar-link active"><i class="bi bi-people"></i> Users</a>
            <div class="sidebar-section-label">Store</div>
            <a href="../employee/index.php" class="sidebar-link"><i class="bi bi-person-badge"></i> Employee Dashboard</a>
            <a href="/catalog.php"          class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
        </nav>
        <div class="sidebar-footer">
            <a href="/logout.php" class="sidebar-user">
                <div class="sidebar-avatar" style="background:linear-gradient(135deg,#f87171,#818cf8);">
                    <?= h($admin_initial) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="name"><?= h($admin_fname) ?></div>
                    <div class="role">Sign out</div>
                </div>
                <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
            </a>
        </div>
    </aside>

    <div class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <span class="page-title">User Management</span>
            </div>
            <button class="btn-dash-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
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

            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title"><?= count($users) ?> Users</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table" id="users-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="text-white-50"><?= h($u['id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar" style="width:32px;height:32px;font-size:.8rem;<?= $u['role']==='admin'?'background:linear-gradient(135deg,#f87171,#818cf8);':'' ?>">
                                            <?= h(strtoupper(substr($u['fname'], 0, 1))) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-white small"><?= h($u['fname'] . ' ' . $u['lname']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-white-50" style="font-size:.8rem;"><?= h($u['email']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"  value="change_role">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <select name="role" class="form-control-dark status-badge status-<?= h($u['role']) ?>"
                                                onchange="this.form.submit()"
                                                style="cursor:pointer;font-size:.72rem;padding:.25rem .5rem;width:auto;border:none;background:transparent;">
                                            <option value="user"      <?= $u['role']==='user'     ?'selected':'' ?>>Customer</option>
                                            <option value="employee"  <?= $u['role']==='employee' ?'selected':'' ?>>Employee</option>
                                            <option value="admin"     <?= $u['role']==='admin'    ?'selected':'' ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn-icon" title="Edit user"
                                                onclick="openEditUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if ($u['id'] !== $admin_id): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action"  value="delete">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <button type="submit" class="btn-icon danger"
                                                    data-confirm="Permanently delete user '<?= h($u['fname']) ?>'? This cannot be undone.">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <button class="btn-icon" disabled title="Cannot delete yourself" style="opacity:.35;">
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

<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-person-plus me-2" style="color:var(--cyan);"></i>Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label text-white-50 small fw-semibold">First Name *</label>
                            <input type="text" name="fname" class="form-control-dark" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-white-50 small fw-semibold">Last Name *</label>
                            <input type="text" name="lname" class="form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Email *</label>
                            <input type="email" name="email" class="form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Password * (min 8 chars)</label>
                            <input type="password" name="password" class="form-control-dark" required minlength="8">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Role</label>
                            <select name="role" class="form-control-dark" style="cursor:pointer;">
                                <option value="user">Customer</option>
                                <option value="employee">Employee</option>
                                <option value="admin">Admin</option>
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

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-pencil me-2" style="color:var(--purple);"></i>Edit User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="users.php" id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_uid">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label text-white-50 small fw-semibold">First Name</label>
                            <input type="text" name="fname" id="edit_fname" class="form-control-dark" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-white-50 small fw-semibold">Last Name</label>
                            <input type="text" name="lname" id="edit_lname" class="form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control-dark" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small fw-semibold">Role</label>
                            <select name="role" id="edit_role" class="form-control-dark" style="cursor:pointer;">
                                <option value="user">Customer</option>
                                <option value="employee">Employee</option>
                                <option value="admin">Admin</option>
                            </select>
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
    document.getElementById('edit_uid').value    = u.id;
    document.getElementById('edit_fname').value  = u.fname || '';
    document.getElementById('edit_lname').value  = u.lname || '';
    document.getElementById('edit_email').value  = u.email || '';
    document.getElementById('edit_role').value   = u.role || 'user';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>
</body>
</html>