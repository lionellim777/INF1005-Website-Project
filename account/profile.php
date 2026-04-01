<?php
/**
 * User Dashboard
 * Profile overview, settings, purchase history.
 */

// 1. Boot your engine
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

// 2. Lock the door: Only logged-in users
require_login();

// 3. Fetch latest user data directly from MySQL using ID
$userId = $_SESSION['user_id'] ?? $_SESSION['userid'] ?? $_SESSION['id'] ?? 0;

$stmt = $db_conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();

if (!$dbUser) {
    header('Location: ' . app_url('account/logout.php'));
    exit;
}

// 4. Map DB fields to expected HTML keys
$user = [
    'user_id'       => $dbUser['id'],
    'first_name'    => $dbUser['fname'],
    'last_name'     => $dbUser['lname'],
    'email'         => $dbUser['email'],
    'phone'         => $dbUser['phone'] ?? '',
    'password_hash' => $dbUser['password'],
    'role_name'     => $dbUser['role'],
    'created_at'    => $dbUser['created_at'] ?? date('Y-m-d H:i:s'),
    'last_login'    => $dbUser['last_login'] ?? date('Y-m-d H:i:s'),
];

$isEmployee = ($user['role_name'] === 'employee');
$activeTab = 'overview'; // Default JS tab

$successMsg = '';
$errorMsg = '';

// ---------------------------------------------------------------------------
// HANDLE PROFILE UPDATE
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $activeTab = 'settings'; // Keep them on the settings tab after saving
    $fn    = trim($_POST['first_name'] ?? '');
    $ln    = trim($_POST['last_name']  ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if (strlen($fn) < 2 || strlen($ln) < 2) {
        $errorMsg = "Names must be at least 2 characters long.";
    } else {
        $updateStmt = $db_conn->prepare("UPDATE users SET fname=?, lname=?, email=?, phone=? WHERE id=?");
        $updateStmt->bind_param("ssssi", $fn, $ln, $email, $phone, $user['user_id']);
        if ($updateStmt->execute()) {
            $_SESSION['fname'] = $fn;
            $_SESSION['lname'] = $ln;
            $_SESSION['email'] = $email;
            
            $user['first_name'] = $fn;
            $user['last_name']  = $ln;
            $user['email']      = $email;
            $user['phone']      = $phone;
            
            $successMsg = "Profile updated successfully!";
        } else {
            $errorMsg = "Email might already be in use.";
        }
    }
}

// ---------------------------------------------------------------------------
// HANDLE PASSWORD CHANGE
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $activeTab = 'settings'; // Keep them on the settings tab after saving
    $current = $_POST['current_password'] ?? '';
    $newPw   = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $errorMsg = 'Current password is incorrect.';
    } elseif ($newPw !== $confirm) {
        $errorMsg = 'New passwords do not match.';
    } elseif (strlen($newPw) < 8) {
        $errorMsg = 'Password must be at least 8 characters.';
    } else {
        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $pwStmt = $db_conn->prepare("UPDATE users SET password=? WHERE id=?");
        $pwStmt->bind_param("si", $hash, $user['user_id']);
        if ($pwStmt->execute()) {
            $user['password_hash'] = $hash;
            $successMsg = "Password updated securely!";
        } else {
            $errorMsg = "Failed to update password.";
        }
    }
}

// ---------------------------------------------------------------------------
// FETCH ORDERS FOR DISPLAY
// ---------------------------------------------------------------------------
$orders = [];
if (!$isEmployee) {
    $ordersStmt = $db_conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    if ($ordersStmt) {
        $ordersStmt->bind_param("i", $user['user_id']);
        $ordersStmt->execute();
        $resOrders = $ordersStmt->get_result();
        while ($r = $resOrders->fetch_assoc()) {
            $orders[] = $r;
        }
    }
}

$totalOrders = count($orders);
$totalSpent  = array_sum(array_column($orders, 'total_amount'));
$memberSince = date('M Y', strtotime($user['created_at']));

function roleBadgeClass(string $role): string {
    return match (strtolower($role)) {
        'admin'    => 'bg-danger',
        'employee' => 'bg-info text-dark',
        default    => 'bg-secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile – Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/main.css') ?>">
    <link rel="stylesheet" href="<?= app_url('css/dashboard.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Make sure JS tabs look clickable and sleek */
        .nav-tabs .nav-link { cursor: pointer; transition: all 0.2s ease; border-color: transparent; }
        .nav-tabs .nav-link.active { background-color: var(--bg-card) !important; border-color: var(--border) var(--border) transparent !important; }
        .nav-tabs .nav-link:hover:not(.active) { border-color: transparent transparent var(--border) !important; color: #fff !important; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100" style="background: var(--bg-primary);">
    <?php include dirname(__DIR__) . '/inc/nav.inc.php'; ?>

    <main class="flex-grow-1 py-4">
        <div class="container">
            
            <?php if ($successMsg): ?>
                <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if ($errorMsg): ?>
                <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-3 mb-3 mb-md-0">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold fs-4"
                         style="width:60px;height:60px;background-color:#28666e;">
                        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-white"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                        <span class="badge <?= roleBadgeClass($user['role_name']) ?>"><?= ucfirst($user['role_name']) ?></span>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist" style="border-bottom: 1px solid var(--border);">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'overview' ? 'active text-white' : 'text-white-50' ?>" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                        <i class="bi bi-grid me-1"></i>Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'settings' ? 'active text-white' : 'text-white-50' ?>" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                        <i class="bi bi-gear me-1"></i>Settings
                    </button>
                </li>
                <?php if (!$isEmployee): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $activeTab === 'orders' ? 'active text-white' : 'text-white-50' ?>" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                            <i class="bi bi-bag me-1"></i>Purchase History
                        </button>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="profileTabsContent">

                <div class="tab-pane fade <?= $activeTab === 'overview' ? 'show active' : '' ?>" id="overview" role="tabpanel">
                    <div class="row g-4 mb-4">
                        <div class="col-sm-6 col-lg-4">
                            <div class="stat-card cyan h-100">
                                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                                <div>
                                    <div class="stat-card-value"><?= $totalOrders ?></div>
                                    <div class="stat-card-label">Total Orders</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="stat-card green h-100">
                                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                                <div>
                                    <div class="stat-card-value">$<?= number_format($totalSpent, 2) ?></div>
                                    <div class="stat-card-label">Total Spent</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="stat-card purple h-100">
                                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                                <div>
                                    <div class="stat-card-value"><?= $memberSince ?></div>
                                    <div class="stat-card-label">Member Since</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade <?= $activeTab === 'settings' ? 'show active' : '' ?>" id="settings" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="form-card shadow-sm h-100">
                                <h6 class="form-card-title">Profile Information</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-white-50">First Name</label>
                                            <input type="text" class="form-control bg-dark text-white border-secondary" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-white-50">Last Name</label>
                                            <input type="text" class="form-control bg-dark text-white border-secondary" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white-50">Email</label>
                                        <input type="email" class="form-control bg-dark text-white border-secondary" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-white-50">Phone</label>
                                        <input type="tel" class="form-control bg-dark text-white border-secondary" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                    </div>
                                    <button type="submit" class="btn-dash-primary w-100 justify-content-center">Save Changes</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="form-card shadow-sm h-100">
                                <h6 class="form-card-title">Change Password</h6>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label class="form-label text-white-50">Current Password</label>
                                        <input type="password" class="form-control bg-dark text-white border-secondary" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-white-50">New Password</label>
                                        <input type="password" class="form-control bg-dark text-white border-secondary" name="new_password" required minlength="8">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-white-50">Confirm New Password</label>
                                        <input type="password" class="form-control bg-dark text-white border-secondary" name="confirm_password" required>
                                    </div>
                                    <button type="submit" class="btn-dash-danger w-100 justify-content-center">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$isEmployee): ?>
                <div class="tab-pane fade <?= $activeTab === 'orders' ? 'show active' : '' ?>" id="orders" role="tabpanel">
                    <div class="dash-table-wrap shadow-sm">
                        <div class="dash-table-header">
                            <h6 class="dash-table-title mb-0">Purchase History</h6>
                            <span class="badge bg-secondary"><?= $totalOrders ?> order(s)</span>
                        </div>
                        <div class="table-responsive">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-4">No orders yet. Start shopping!</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="fw-semibold">#<?= htmlspecialchars((string)$order['id']) ?></td>
                                            <td class="fw-semibold">$<?= number_format((float)$order['total_amount'], 2) ?></td>
                                            <td><span class="badge bg-info text-dark"><?= ucfirst(htmlspecialchars($order['status'])) ?></span></td>
                                            <td class="text-muted small"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div> </div>
    </main>

    <?php include dirname(__DIR__) . '/inc/footer.inc.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= app_url('js/main.js') ?>"></script>
    <script>
        // Update nav link styles when Bootstrap JS switches tabs
        const tabElements = document.querySelectorAll('button[data-bs-toggle="tab"]');
        tabElements.forEach(tab => {
            tab.addEventListener('shown.bs.tab', event => {
                tabElements.forEach(t => t.classList.replace('text-white', 'text-white-50'));
                event.target.classList.replace('text-white-50', 'text-white');
            });
        });
    </script>
</body>
</html>