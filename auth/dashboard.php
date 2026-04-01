<?php
/**
 * User Dashboard
 * Profile overview, settings, purchase history, session management.
 */

require_once __DIR__ . '/auth_helper.php';
initSession();
requireLogin();

$user = currentUser();
if (!$user) {
    header('Location: ' . appUrl('/auth/login.php'));
    exit;
}
$isOffline = isOfflineMode();
$pdo  = $isOffline ? null : getDBConnection();
$isEmployee = (int)$user['role_id'] === 3;

$activeTab = sanitize($_GET['tab'] ?? 'overview');
if ($isEmployee && $activeTab === 'orders') {
    setFlash('warning', 'Employee accounts cannot access purchase history.');
    header('Location: ' . appUrl('/auth/dashboard.php?tab=overview'));
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE PROFILE UPDATE
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid form submission.');
    } else {
        $fn    = sanitize($_POST['first_name'] ?? '');
        $ln    = sanitize($_POST['last_name']  ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = sanitize($_POST['phone'] ?? '');

        $errs = [];
        if (strlen($fn) < 2) $errs[] = 'First name too short.';
        if (strlen($ln) < 2) $errs[] = 'Last name too short.';
        if (!validateEmail($email)) $errs[] = 'Invalid email.';

        // Email uniqueness (if changed)
        if ($email !== $user['email']) {
            if ($isOffline) {
                if (offlineEmailExists($email, (int)$user['user_id'])) {
                    $errs[] = 'Email already in use by another account.';
                }
            } else {
                $chk = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
                $chk->execute([$email, $user['user_id']]);
                if ($chk->fetch()) $errs[] = 'Email already in use by another account.';
            }
        }

        if ($errs) {
            setFlash('error', implode(' ', $errs));
        } else {
            if ($isOffline) {
                updateOfflineUserProfile((int)$user['user_id'], $fn, $ln, $email, $phone ?: null);
            } else {
                $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE user_id=?')
                    ->execute([$fn, $ln, $email, $phone ?: null, $user['user_id']]);
            }
            unset($_SESSION['_user_cache']);
            logActivity($user['user_id'], 'profile_update', 'Profile details updated.');
            setFlash('success', 'Profile updated successfully.');
        }
    }
    header('Location: ' . appUrl('/auth/dashboard.php?tab=settings'));
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE PASSWORD CHANGE
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid form submission.');
    } else {
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            setFlash('error', 'Current password is incorrect.');
        } elseif (strlen($newPw) < 8 || !preg_match('/[A-Z]/', $newPw) || !preg_match('/[\d]/', $newPw) || !preg_match('/[!@#$%^&*()_+\-=\[\]{}|;:,.<>?]/', $newPw)) {
            setFlash('error', 'New password does not meet complexity requirements.');
        } elseif ($newPw !== $confirm) {
            setFlash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            if ($isOffline) {
                updateOfflineUserPassword((int)$user['user_id'], $hash);
            } else {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
                    ->execute([$hash, $user['user_id']]);
            }
            unset($_SESSION['_user_cache']);
            logActivity($user['user_id'], 'password_change', 'Password changed.');
            setFlash('success', 'Password changed successfully.');
        }
    }
    header('Location: ' . appUrl('/auth/dashboard.php?tab=settings'));
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE SESSION TERMINATION
// ---------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'terminate_session') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request.');
    } else {
        $sid = $_POST['session_id'] ?? '';
        if ($sid === session_id()) {
            setFlash('warning', 'You cannot terminate your current session from here. Use log out instead.');
        } else {
            terminateSession($sid);
            logActivity($user['user_id'], 'session_terminate', "Terminated session {$sid}");
            setFlash('success', 'Session terminated.');
        }
    }
    header('Location: ' . appUrl('/auth/dashboard.php?tab=sessions'));
    exit;
}

// ---------------------------------------------------------------------------
// FETCH DATA FOR DISPLAY
// ---------------------------------------------------------------------------
// Refresh user data after any updates
$user = currentUser();

// Active sessions
$sessions = getUserSessions($user['user_id']);

if ($isOffline) {
    $orders = $isEmployee ? [] : getOfflineOrdersForUser((int)$user['user_id']);
    $activityLog = getOfflineActivityLog((int)$user['user_id'], 10);
} else {
    if ($isEmployee) {
        $orders = [];
    } else {
        // Purchase history
        $ordersStmt = $pdo->prepare('
            SELECT o.*, GROUP_CONCAT(oi.product_name SEPARATOR ", ") AS items
            FROM orders o
            LEFT JOIN order_items oi ON o.order_id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.order_id
            ORDER BY o.created_at DESC
            LIMIT 20
        ');
        $ordersStmt->execute([$user['user_id']]);
        $orders = $ordersStmt->fetchAll();
    }

    // Activity log (last 10)
    $logStmt = $pdo->prepare('SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
    $logStmt->execute([$user['user_id']]);
    $activityLog = $logStmt->fetchAll();
}

// Stats
$totalOrders = count($orders);
$totalSpent  = array_sum(array_column($orders, 'total_amount'));
$memberSince = date('M Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../inc/nav.inc.php'; ?>

    <main class="flex-grow-1 py-4">
        <div class="container">
            <?= renderFlash() ?>
            <?php if ($isOffline): ?>
                <div class="alert alert-info small">
                    Offline demo mode is active. Data on this page is simulated and stored in session.
                </div>
            <?php endif; ?>

            <!-- Dashboard Header -->
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4"
                         style="width:60px;height:60px;background-color:#28666e;">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                        <span class="badge <?= roleBadgeClass($user['role_name']) ?>"><?= ucfirst($user['role_name']) ?></span>
                    </div>
                </div>
                <?php if (hasRole(4)): ?>
                    <a href="<?= appUrl('/admin/manage_users.php') ?>" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-shield-lock me-1"></i>Admin Panel
                    </a>
                <?php endif; ?>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-4" id="dashTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>"
                       href="?tab=overview" role="tab"><i class="bi bi-grid me-1"></i>Overview</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'settings' ? 'active' : '' ?>"
                       href="?tab=settings" role="tab"><i class="bi bi-gear me-1"></i>Settings</a>
                </li>
                <?php if (!$isEmployee): ?>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?= $activeTab === 'orders' ? 'active' : '' ?>"
                           href="?tab=orders" role="tab"><i class="bi bi-bag me-1"></i>Purchase History</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'sessions' ? 'active' : '' ?>"
                       href="?tab=sessions" role="tab"><i class="bi bi-laptop me-1"></i>Sessions</a>
                </li>
            </ul>

            <!-- ================================================================ -->
            <!-- OVERVIEW TAB -->
            <!-- ================================================================ -->
            <?php if ($activeTab === 'overview'): ?>
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-bag-check fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?= $totalOrders ?></h3>
                            <small class="text-muted">Total Orders</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0">$<?= number_format($totalSpent, 2) ?></h3>
                            <small class="text-muted">Total Spent</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-laptop fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?= count($sessions) ?></h3>
                            <small class="text-muted">Active Sessions</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?= $memberSince ?></h3>
                            <small class="text-muted">Member Since</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="fw-bold mb-0">Recent Activity</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($activityLog)): ?>
                        <p class="text-muted p-4 mb-0">No recent activity.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr><th>Action</th><th>Details</th><th>IP</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLog as $log): ?>
                                    <tr>
                                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($log['action']) ?></span></td>
                                        <td class="text-muted small"><?= htmlspecialchars($log['details']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($log['ip_address']) ?></td>
                                        <td class="text-muted small"><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================================================================ -->
            <!-- SETTINGS TAB -->
            <!-- ================================================================ -->
            <?php elseif ($activeTab === 'settings'): ?>
            <div class="row g-4">
                <!-- Profile Info -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">Profile Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name"
                                               value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name"
                                               value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>

                                <button type="submit" class="btn text-white" style="background-color:#28666e;">
                                    Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">Change Password</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="changePasswordForm" novalidate>
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password"
                                           name="current_password" required autocomplete="current-password">
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password"
                                           name="new_password" required minlength="8" autocomplete="new-password">
                                    <div class="form-text">Min 8 chars, 1 uppercase, 1 number, 1 special character.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                           name="confirm_password" required autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn text-white" style="background-color:#28666e;">
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Account Info Card -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">Account Details</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <span class="text-muted">Role:</span>
                                    <span class="badge <?= roleBadgeClass($user['role_name']) ?> ms-1"><?= ucfirst($user['role_name']) ?></span>
                                </li>
                                <li class="mb-2">
                                    <span class="text-muted">Account Created:</span>
                                    <span><?= date('d M Y', strtotime($user['created_at'])) ?></span>
                                </li>
                                <li class="mb-0">
                                    <span class="text-muted">Last Login:</span>
                                    <span><?= $user['last_login'] ? date('d M Y, H:i', strtotime($user['last_login'])) : 'N/A' ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================ -->
            <!-- ORDERS TAB -->
            <!-- ================================================================ -->
            <?php elseif ($activeTab === 'orders'): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Purchase History</h6>
                    <span class="badge bg-secondary"><?= $totalOrders ?> order(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag-x fs-1 text-muted"></i>
                            <p class="text-muted mt-2">No orders yet. Start shopping!</p>
                            <a href="<?= appUrl('/index.php') ?>" class="btn btn-sm text-white" style="background-color:#28666e;">Browse Products</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php
                                        $statusCls = match($order['status']) {
                                            'delivered'  => 'text-bg-success',
                                            'shipped'    => 'text-bg-primary',
                                            'processing' => 'text-bg-warning',
                                            'cancelled'  => 'text-bg-danger',
                                            default      => 'text-bg-secondary',
                                        };
                                    ?>
                                    <tr>
                                        <td class="fw-semibold">#<?= $order['order_id'] ?></td>
                                        <td class="small text-muted" style="max-width:250px;"><?= htmlspecialchars($order['items'] ?? '—') ?></td>
                                        <td class="fw-semibold">$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><span class="badge <?= $statusCls ?>"><?= ucfirst($order['status']) ?></span></td>
                                        <td class="text-muted small"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ================================================================ -->
            <!-- SESSIONS TAB -->
            <!-- ================================================================ -->
            <?php elseif ($activeTab === 'sessions'): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="fw-bold mb-0">Active Sessions</h6>
                    <small class="text-muted">Manage your active login sessions across devices.</small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($sessions)): ?>
                        <p class="text-muted p-4 mb-0">No active sessions found.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($sessions as $sess): ?>
                            <?php $isCurrent = ($sess['session_id'] === session_id()); ?>
                            <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between py-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-light" style="width:42px;height:42px;">
                                        <?php
                                        $icon = 'bi-laptop';
                                        if (str_contains($sess['device_label'] ?? '', 'iPhone') || str_contains($sess['device_label'] ?? '', 'Android')) {
                                            $icon = 'bi-phone';
                                        }
                                        ?>
                                        <i class="bi <?= $icon ?> fs-5" style="color:#28666e;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($sess['device_label'] ?? 'Unknown Device') ?>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-success ms-1">Current</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block">IP: <?= htmlspecialchars($sess['ip_address']) ?></small>
                                        <small class="text-muted d-block">Last active: <?= date('d M Y, H:i', strtotime($sess['last_active'])) ?></small>
                                    </div>
                                </div>
                                <?php if (!$isCurrent): ?>
                                <form method="POST" action="" class="mt-2 mt-md-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="terminate_session">
                                    <input type="hidden" name="session_id" value="<?= htmlspecialchars($sess['session_id']) ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Terminate this session?');">
                                        <i class="bi bi-x-circle me-1"></i>Revoke
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include __DIR__ . '/../inc/footer.inc.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <script src="<?= appUrl('/js/auth.js') ?>"></script>
</body>
</html>
