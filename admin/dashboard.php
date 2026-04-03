<?php
/**
 * User Dashboard
 * Profile overview, settings, purchase history.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Force login using our established middleware
require_login();
global $session;

// Retrieve user ID from Zebra_Session
$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

// Fetch current user data from the live database
$stmt = $db_conn->prepare("SELECT id, fname, lname, email, role, password, created_at, last_login, is_active FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: /login.php');
    exit;
}

// Map database columns to the HTML's expected variables
$user['user_id'] = $user['id'];
$user['first_name'] = $user['fname'];
$user['last_name'] = $user['lname'];
$user['role_name'] = ($user['role'] === 'user') ? 'customer' : $user['role']; 
$user['password_hash'] = $user['password'];

$isEmployee = in_array($user['role'], ['admin', 'employee']);
$activeTab = htmlspecialchars($_GET['tab'] ?? 'overview');

if ($isEmployee && $activeTab === 'orders') {
    $session->set_flashdata('warning', 'Employee accounts cannot access purchase history.');
    header('Location: dashboard.php?tab=overview');
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE PROFILE UPDATE
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $session->set_flashdata('error', 'Invalid form submission.');
    } else {
        $fn    = trim($_POST['first_name'] ?? '');
        $ln    = trim($_POST['last_name']  ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        $errs = [];
        if (strlen($fn) < 2) $errs[] = 'First name too short.';
        if (strlen($ln) < 2) $errs[] = 'Last name too short.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'Invalid email.';

        // Email uniqueness (if changed)
        if ($email !== $user['email']) {
            $chk = $db_conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $chk->bind_param("si", $email, $user['id']);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) $errs[] = 'Email already in use by another account.';
            $chk->close();
        }

        if ($errs) {
            $session->set_flashdata('error', implode(' ', $errs));
        } else {
            // Update without phone column
            $upd = $db_conn->prepare('UPDATE users SET fname=?, lname=?, email=? WHERE id=?');
            $upd->bind_param("sssi", $fn, $ln, $email, $user['id']);
            $upd->execute();
            $upd->close();
            
            $session->set_flashdata('success', 'Profile updated successfully.');
        }
    }
    header('Location: dashboard.php?tab=settings');
    exit;
}

// ---------------------------------------------------------------------------
// HANDLE PASSWORD CHANGE
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $session->set_flashdata('error', 'Invalid form submission.');
    } else {
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $session->set_flashdata('error', 'Current password is incorrect.');
        } elseif (strlen($newPw) < 8) {
            $session->set_flashdata('error', 'New password must be at least 8 characters.');
        } elseif ($newPw !== $confirm) {
            $session->set_flashdata('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $upd = $db_conn->prepare('UPDATE users SET password = ? WHERE id = ?');
            $upd->bind_param("si", $hash, $user['id']);
            $upd->execute();
            $upd->close();
            
            $session->set_flashdata('success', 'Password changed successfully.');
        }
    }
    header('Location: dashboard.php?tab=settings');
    exit;
}

// ---------------------------------------------------------------------------
// FETCH DATA FOR DISPLAY
// ---------------------------------------------------------------------------
$orders = [];
if (!$isEmployee) {
    // Join our actual orders and order_items tables
    $ordersStmt = $db_conn->prepare('
        SELECT o.id AS order_id, o.*, GROUP_CONCAT(oi.product_name SEPARATOR ", ") AS items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 20
    ');
    $ordersStmt->bind_param("i", $user['id']);
    $ordersStmt->execute();
    $orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ordersStmt->close();
}

// Stats
$totalOrders = count($orders);
$totalSpent  = array_sum(array_column($orders, 'total_amount'));
$memberSince = date('M Y', strtotime($user['created_at']));

// Helper to render Zebra_Session flash messages seamlessly
function renderFlash() {
    $html = '';
    
    // Check for error messages
    if (!empty($_SESSION['error'])) {
        $html .= '<div class="alert alert-danger shadow-sm border-0">'.htmlspecialchars($_SESSION['error']).'</div>';
        unset($_SESSION['error']); // Clear it so it doesn't persist
    }
    
    // Check for success messages
    if (!empty($_SESSION['success'])) {
        $html .= '<div class="alert alert-success shadow-sm border-0">'.htmlspecialchars($_SESSION['success']).'</div>';
        unset($_SESSION['success']);
    }
    
    // Check for warnings (used in the dashboard for employees)
    if (!empty($_SESSION['warning'])) {
        $html .= '<div class="alert alert-warning shadow-sm border-0">'.htmlspecialchars($_SESSION['warning']).'</div>';
        unset($_SESSION['warning']);
    }
    
    return $html;
}

// Ensure the badge class helper exists
if (!function_exists('roleBadgeClass')) {
    function roleBadgeClass(string $role): string {
        return match (strtolower($role)) {
            'admin'    => 'bg-danger',
            'employee' => 'bg-info text-dark',
            default    => 'bg-secondary',
        };
    }
}

$pageTitle   = 'Admin Dashboard – Pomegranate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/dashboard.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../inc/nav.inc.php'; ?>

    <main class="flex-grow-1 py-4">
        <div class="container">
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
                <?php if ($user['role'] === 'admin'): ?>
                    <a href="/admin/manage_users.php" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-shield-lock me-1"></i>Admin Panel
                    </a>
                <?php endif; ?>
            </div>

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
            </ul>

            <?php if ($activeTab === 'overview'): ?>
            <div class="row g-4 mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-bag-check fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?= $totalOrders ?></h3>
                            <small class="text-muted">Total Orders</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0">$<?= number_format($totalSpent, 2) ?></h3>
                            <small class="text-muted">Total Spent</small>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check fs-2" style="color:#28666e;"></i>
                            <h3 class="fw-bold mt-2 mb-0"><?= $memberSince ?></h3>
                            <small class="text-muted">Member Since</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($activeTab === 'settings'): ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">Profile Information</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

                                <button type="submit" class="btn text-white" style="background-color:#28666e;">
                                    Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">Change Password</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="changePasswordForm" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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
                                    <div class="form-text">Min 8 chars required.</div>
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
                            <a href="/catalog.php" class="btn btn-sm text-white" style="background-color:#28666e;">Browse Products</a>
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
                                        $statusCls = match(strtolower($order['status'])) {
                                            'delivered'  => 'text-bg-success',
                                            'shipped'    => 'text-bg-primary',
                                            'processing' => 'text-bg-warning',
                                            'cancelled'  => 'text-bg-danger',
                                            default      => 'text-bg-secondary',
                                        };
                                    ?>
                                    <tr>
                                        <td class="fw-semibold">#<?= htmlspecialchars($order['order_id']) ?></td>
                                        <td class="small text-muted" style="max-width:250px;"><?= htmlspecialchars($order['items'] ?? '—') ?></td>
                                        <td class="fw-semibold">$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><span class="badge <?= $statusCls ?>"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></td>
                                        <td class="text-muted small"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <?php include __DIR__ . '/../inc/footer.inc.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>