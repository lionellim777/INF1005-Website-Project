<?php
require_once "../inc/auth.inc.php";
requireAdmin();
<<<<<<< Updated upstream
header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;
=======

$msg = $err = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPwd  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        try {
            $pdo  = getDB();
            $user = $pdo->prepare("SELECT password_hash FROM users WHERE id=?")->execute([getUserId()]) ? null : null;
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
            $stmt->execute([getUserId()]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $err = 'Current password is incorrect.';
            } elseif (strlen($newPwd) < 8) {
                $err = 'New password must be at least 8 characters.';
            } elseif (!preg_match('/[A-Z]/', $newPwd)) {
                $err = 'New password must contain an uppercase letter.';
            } elseif (!preg_match('/[0-9]/', $newPwd)) {
                $err = 'New password must contain a number.';
            } elseif ($newPwd !== $confirm) {
                $err = 'Passwords do not match.';
            } else {
                $hash = password_hash($newPwd, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, getUserId()]);
                $msg = 'Password updated successfully.';
            }
        } catch (Exception $e) {
            $err = 'Database error.';
        }
        $qs = $msg ? '?msg='.urlencode($msg) : '?err='.urlencode($err);
        header('Location: settings.php'.$qs);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings – Admin – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
            <a href="users.php"    class="sidebar-link"><i class="bi bi-people"></i> Users</a>
            <a href="orders.php"   class="sidebar-link"><i class="bi bi-receipt"></i> Orders</a>
            <a href="products.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Products</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="reports.php"   class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Reports</a>

            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link active"><i class="bi bi-gear"></i> Settings</a>
            <a href="logs.php"     class="sidebar-link"><i class="bi bi-journal-text"></i> Activity Logs</a>

            <div class="sidebar-section-label">Employee View</div>
            <a href="../employee/index.php" class="sidebar-link"><i class="bi bi-person-badge"></i> Employee Dashboard</a>
            <a href="/catalog.php"          class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
        </nav>
        <div class="sidebar-footer">
            <a href="/logout.php" class="sidebar-user">
                <div class="sidebar-avatar" style="background:linear-gradient(135deg,#f87171,#818cf8);">
                    <?= strtoupper(substr(getUsername(), 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <div class="name"><?= h(getFullName()) ?></div>
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
                <span class="page-title">Settings</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Settings</h2>
                <p class="text-white-50 small mb-0">Manage your admin account and system preferences.</p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($_GET['msg']) ?>
            </div>
            <?php elseif (isset($_GET['err'])): ?>
            <div class="alert-error-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i><?= h($_GET['err']) ?>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Account info -->
                <div class="col-12 col-lg-6">
                    <div class="form-card">
                        <h5 class="fw-bold text-white mb-1">Account Information</h5>
                        <p class="text-white-50 small mb-4">Your current admin account details.</p>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                                 style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                <span class="text-white-50 small">Full Name</span>
                                <span class="text-white fw-semibold small"><?= h(getFullName()) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                                 style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                <span class="text-white-50 small">Username</span>
                                <span class="text-white fw-semibold small">@<?= h(getUsername()) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                                 style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                <span class="text-white-50 small">Role</span>
                                <span class="status-badge status-admin">Admin</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="users.php?edit=<?= getUserId() ?>" class="btn-dash-secondary w-100">
                                <i class="bi bi-pencil"></i> Edit Profile via User Management
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Change password -->
                <div class="col-12 col-lg-6">
                    <div class="form-card">
                        <h5 class="fw-bold text-white mb-1">Change Password</h5>
                        <p class="text-white-50 small mb-4">Update your admin account password.</p>
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="change_password">
                            <div class="d-flex flex-column gap-3">
                                <div>
                                    <label class="form-label text-white-50 small fw-semibold">Current Password</label>
                                    <input type="password" name="current_password" class="form-control-dark"
                                           placeholder="Enter current password" required autocomplete="current-password">
                                </div>
                                <div>
                                    <label class="form-label text-white-50 small fw-semibold">New Password</label>
                                    <input type="password" name="new_password" class="form-control-dark"
                                           placeholder="Min. 8 chars, 1 uppercase, 1 number" required autocomplete="new-password">
                                </div>
                                <div>
                                    <label class="form-label text-white-50 small fw-semibold">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control-dark"
                                           placeholder="Repeat new password" required autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn-dash-primary w-100">
                                    <i class="bi bi-lock"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System info -->
                <div class="col-12">
                    <div class="form-card">
                        <h5 class="fw-bold text-white mb-1">System Information</h5>
                        <p class="text-white-50 small mb-4">Current environment details.</p>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                    <div class="text-white-50 small mb-1">PHP Version</div>
                                    <div class="text-white fw-semibold"><?= PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION ?></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                    <div class="text-white-50 small mb-1">Server Time</div>
                                    <div class="text-white fw-semibold"><?= date('H:i:s T') ?></div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                                    <div class="text-white-50 small mb-1">Today's Date</div>
                                    <div class="text-white fw-semibold"><?= date('d M Y') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
>>>>>>> Stashed changes
