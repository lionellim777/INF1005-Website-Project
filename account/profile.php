<?php
/**
 * Customer Profile Dashboard
 * View stats, update account details, view order history, and logout.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// 1. Ensure user is logged in
require_login();
global $session;

$userId = $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0;

// Fetch current user data from the live database
$stmt = $db_conn->prepare("SELECT id, fname, lname, email, role, password, created_at, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: /login.php');
    exit;
}

$activeTab = htmlspecialchars($_GET['tab'] ?? 'overview');

// ---------------------------------------------------------------------------
// POST ACTIONS (Update Profile, Change Password, Logout)
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $session->set_flashdata('error', 'Invalid form submission.');
        header("Location: /account/profile.php?tab={$activeTab}");
        exit;
    }

    $action = $_POST['action'] ?? '';

    // -- LOGOUT --
    if ($action === 'logout') {
        session_destroy();
        header('Location: /login.php');
        exit;
    }

    // -- UPDATE PROFILE --
    if ($action === 'update_profile') {
        $fname = trim($_POST['first_name'] ?? '');
        $lname = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));

        $errs = [];
        if (strlen($fname) < 2) $errs[] = 'First name must be at least 2 characters.';
        if (strlen($lname) < 2) $errs[] = 'Last name must be at least 2 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'Invalid email address.';

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
            $upd = $db_conn->prepare('UPDATE users SET fname=?, lname=?, email=? WHERE id=?');
            $upd->bind_param("sssi", $fname, $lname, $email, $user['id']);
            $upd->execute();
            $upd->close();
            
            $_SESSION['fname'] = $fname; // Update session variables
            $_SESSION['lname'] = $lname;
            
            $session->set_flashdata('success', 'Profile updated successfully.');
        }
        header('Location: /account/profile.php?tab=settings');
        exit;
    }

    // -- CHANGE PASSWORD --
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $newPw   = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $complexityOk = strlen($newPw) >= 8
            && preg_match('/[A-Z]/', $newPw)
            && preg_match('/[a-z]/', $newPw)
            && preg_match('/[0-9]/', $newPw)
            && preg_match('/[\W_]/', $newPw);

        if (!password_verify($current, $user['password'])) {
            $session->set_flashdata('error', 'Current password is incorrect.');
        } elseif (!$complexityOk) {
            $session->set_flashdata('error', 'New password must be at least 8 characters, with 1 uppercase, 1 lowercase, 1 number, and 1 special character.');
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
        header('Location: /account/profile.php?tab=settings');
        exit;
    }
}

// ---------------------------------------------------------------------------
// FETCH DATA FOR DISPLAY
// ---------------------------------------------------------------------------
// 1. Fetch Orders securely mapped from ecommerce.sql
$ordersStmt = $db_conn->prepare("
    SELECT o.id AS order_id, o.total_amount, o.status, o.created_at, 
           GROUP_CONCAT(oi.product_name SEPARATOR ', ') AS items 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC
");
$ordersStmt->bind_param("i", $user['id']);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$ordersStmt->close();

// 2. Stats Calculations
$totalOrders = count($orders);
$totalSpent  = array_sum(array_column($orders, 'total_amount'));
$memberSince = date('M Y', strtotime($user['created_at']));

// Helper: safe rendering of session flashes
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <?php include __DIR__ . '/../inc/nav.inc.php'; ?>

    <main class="flex-grow-1 py-5">
        <div class="container" style="max-width: 1000px;">
            <?= renderFlash() ?>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-3 shadow-sm"
                         style="width:75px;height:75px;background-color:#28666e;">
                        <?= strtoupper(substr($user['fname'], 0, 1) . substr($user['lname'], 0, 1)) ?>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-0"><?= htmlspecialchars($user['fname'] . ' ' . $user['lname']) ?></h3>
                        <span class="badge bg-secondary">Customer</span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-danger fw-bold">
                        <i class="bi bi-box-arrow-right me-1"></i>Log Out
                    </button>
                </form>
            </div>

            <ul class="nav nav-tabs mb-4 border-bottom-0" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-semibold <?= $activeTab === 'overview' ? 'active shadow-sm border-0 rounded-top text-dark' : 'text-muted' ?>"
                       href="?tab=overview"><i class="bi bi-grid me-1"></i>Overview</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-semibold <?= $activeTab === 'settings' ? 'active shadow-sm border-0 rounded-top text-dark' : 'text-muted' ?>"
                       href="?tab=settings"><i class="bi bi-gear me-1"></i>Settings</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-semibold <?= $activeTab === 'orders' ? 'active shadow-sm border-0 rounded-top text-dark' : 'text-muted' ?>"
                       href="?tab=orders"><i class="bi bi-bag me-1"></i>Order History</a>
                </li>
            </ul>

            <?php if ($activeTab === 'overview'): ?>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-bag-check fs-3"></i>
                            </div>
                            <h2 class="fw-bold mb-0"><?= $totalOrders ?></h2>
                            <span class="text-muted small fw-semibold text-uppercase tracking-wide">Total Orders</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-currency-dollar fs-3"></i>
                            </div>
                            <h2 class="fw-bold mb-0">$<?= number_format($totalSpent, 2) ?></h2>
                            <span class="text-muted small fw-semibold text-uppercase tracking-wide">Total Spent</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4">
                        <div class="card-body text-center p-4">
                            <div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="bi bi-calendar-check fs-3"></i>
                            </div>
                            <h2 class="fw-bold mb-0"><?= $memberSince ?></h2>
                            <span class="text-muted small fw-semibold text-uppercase tracking-wide">Member Since</span>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($activeTab === 'settings'): ?>
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                            <h5 class="fw-bold mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label fw-semibold">First Name</label>
                                        <input type="text" class="form-control bg-light border-0" id="first_name" name="first_name"
                                               value="<?= htmlspecialchars($user['fname']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label fw-semibold">Last Name</label>
                                        <input type="text" class="form-control bg-light border-0" id="last_name" name="last_name"
                                               value="<?= htmlspecialchars($user['lname']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label fw-semibold">Email Address</label>
                                    <input type="email" class="form-control bg-light border-0" id="email" name="email"
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>

                                <button type="submit" class="btn text-white w-100 fw-bold py-2" style="background-color:#28666e;">
                                    Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                            <h5 class="fw-bold mb-0">Security</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label for="current_password" class="form-label fw-semibold">Current Password</label>
                                    <input type="password" class="form-control bg-light border-0" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label fw-semibold">New Password</label>
                                    <input type="password" class="form-control bg-light border-0" id="new_password" name="new_password" required>
                                    <small class="form-text text-muted">Min 8 chars, 1 upper, 1 lower, 1 number, 1 special.</small>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                                    <input type="password" class="form-control bg-light border-0" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-dark w-100 fw-bold py-2">
                                    Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php elseif ($activeTab === 'orders'): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Order History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-bag-x fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bold text-dark">No orders yet</h5>
                            <p class="text-muted">Looks like you haven't made a purchase yet.</p>
                            <a href="/shop/catalog.php" class="btn text-white px-4" style="background-color:#28666e;">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase tracking-wide">
                                    <tr>
                                        <th class="ps-4 py-3">Order #</th>
                                        <th class="py-3">Items</th>
                                        <th class="py-3">Total</th>
                                        <th class="py-3">Status</th>
                                        <th class="pe-4 py-3 text-end">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <?php
                                        $statusCls = match(strtolower($order['status'])) {
                                            'delivered'  => 'bg-success',
                                            'shipped'    => 'bg-primary',
                                            'processing' => 'bg-info text-dark',
                                            'cancelled'  => 'bg-danger',
                                            default      => 'bg-secondary',
                                        };
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">#<?= htmlspecialchars($order['order_id']) ?></td>
                                        <td class="text-muted small" style="max-width:300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($order['items'] ?? '—') ?>
                                        </td>
                                        <td class="fw-bold">$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><span class="badge rounded-pill <?= $statusCls ?>"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></td>
                                        <td class="pe-4 text-muted small text-end"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
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