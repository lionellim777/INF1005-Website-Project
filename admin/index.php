<?php
// 1. Boot the engine and secure the page
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

// ONLY Admins can view this dashboard
require_role([ROLE_ADMIN]);

$stats = ['total_users'=>0, 'new_users_today'=>0, 'total_revenue'=>0, 'total_orders'=>0, 'total_products'=>0, 'active_employees'=>0];
$recentUsers  = [];
$revenueData  = [];
$orderStats   = [];

// Safely extract the logged-in admin's info from Zebra Session
$admin_fname = $_SESSION['fname'] ?? 'Admin';
$admin_initial = strtoupper(substr($admin_fname, 0, 1));

// 2. Fetch Dashboard Analytics from MySQLi
if (isset($db_conn)) {
    mysqli_report(MYSQLI_REPORT_OFF);

    $res = $db_conn->query("SELECT COUNT(*) AS c FROM users");
    if ($res) $stats['total_users'] = $res->fetch_assoc()['c'];

    $res = $db_conn->query("SELECT COUNT(*) AS c FROM products");
    if ($res) $stats['total_products'] = $res->fetch_assoc()['c'];

    $res = $db_conn->query("SELECT COUNT(*) AS c FROM users WHERE role IN ('employee','admin')");
    if ($res) $stats['active_employees'] = $res->fetch_assoc()['c'];

    // THE FIX: Added created_at back to the SELECT query!
    $res = $db_conn->query("SELECT id, fname, lname, email, role, created_at FROM users ORDER BY id DESC LIMIT 6");
    if ($res) { while ($row = $res->fetch_assoc()) $recentUsers[] = $row; }
}

// 3. Fallback Mock Data 
if (empty($recentUsers)) {
    $stats['total_users']      = $stats['total_users'] ?: 12;
    $stats['total_products']   = $stats['total_products'] ?: 24;
    $stats['active_employees'] = $stats['active_employees'] ?: 4;

    $recentUsers = [
        ['id'=>1, 'fname'=>'Admin','lname'=>'User', 'email'=>'admin@example.com', 'role'=>'admin', 'created_at'=>date('Y-m-d H:i:s')],
    ];
}

$revLabels = json_encode(['Oct','Nov','Dec']);
$revValues = json_encode([12000, 15000, 18000]);
$orderStatusLabels = json_encode(['Delivered', 'Processing']);
$orderStatusCounts = json_encode([25, 8]);
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
    <!-- ── SIDEBAR ── -->
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
            <a href="index.php"    class="sidebar-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="users.php"    class="sidebar-link"><i class="bi bi-people"></i> Users</a>
            <a href="../employee/products.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Products</a>
            <div class="sidebar-section-label">Employee View</div>
            <a href="../employee/index.php" class="sidebar-link"><i class="bi bi-person-badge"></i> Employee Dashboard</a>
            <a href="/catalog.php"          class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout.php" class="sidebar-user">
                <div class="sidebar-avatar"><?= h($admin_initial) ?></div>
                <div class="sidebar-user-info">
                    <div class="name"><?= h($admin_fname) ?></div>
                    <div class="role">Sign out</div>
                </div>
                <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
            </a>
        </div>
    </aside>

    <!-- ── MAIN ── -->
    <div class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Admin Overview</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
                <a href="users.php" class="btn-dash-primary">
                    <i class="bi bi-person-plus"></i> Add User
                </a>
            </div>
        </div>

        <div class="dash-content">

            <!-- Warning banner -->
            <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded-3"
                 style="background:rgba(248,113,113,.07);border:1px solid rgba(248,113,113,.2);">
                <i class="bi bi-shield-exclamation flex-shrink-0" style="color:var(--red);font-size:1.4rem;"></i>
                <div>
                    <div class="fw-semibold text-white small">Admin Access Area</div>
                    <div class="text-white-50" style="font-size:.78rem;">
                        You have full system privileges. All actions are logged and audited.
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($_GET['msg']) ?>
            </div>
            <?php endif; ?>

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-4">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['total_users']) ?></div>
                            <div class="stat-card-label">Total Users</div>
                            <div class="stat-card-change change-up">
                                <i class="bi bi-person-plus"></i> <?= $stats['new_users_today'] ?> today
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($stats['total_revenue'], 0) ?></div>
                            <div class="stat-card-label">Total Revenue</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> All time</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['total_orders']) ?></div>
                            <div class="stat-card-label">Total Orders</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> All time</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card amber">
                        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['total_products']) ?></div>
                            <div class="stat-card-label">Active Products</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['active_employees']) ?></div>
                            <div class="stat-card-label">Staff Members</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">100%</div>
                            <div class="stat-card-label">System Uptime</div>
                            <div class="stat-card-change change-up"><i class="bi bi-check2-circle"></i> All systems OK</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Revenue Trend (Last 7 Months)</span>
                        </div>
                        <div style="height:240px;">
                            <canvas id="revenueChart" data-labels='<?= $revLabels ?>' data-values='<?= $revValues ?>'></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Order Status</span>
                        </div>
                        <div style="height:240px;">
                            <canvas id="ordersChart"
                                    data-labels='<?= $orderLabels ?>'
                                    data-values='<?= $orderValues ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick actions + recent users -->
            <div class="row g-3">
                <!-- Quick actions -->
                <div class="col-12 col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-card-header mb-3">
                            <span class="chart-card-title">Quick Actions</span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a href="users.php"            class="btn-dash-secondary w-100"><i class="bi bi-people"></i>       Manage Users</a>
                            <a href="products.php"         class="btn-dash-secondary w-100"><i class="bi bi-box-seam"></i>      Manage Products</a>
                        </div>
                    </div>
                </div>

                <!-- Users -->
                <div class="col-12 col-lg-8">
                    <div class="dash-table-wrap">
                        <div class="dash-table-header">
                            <span class="dash-table-title">Users</span>
                            <a href="users.php" class="btn-dash-secondary" style="font-size:.75rem;padding:.3rem .7rem;">View all</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentUsers as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="sidebar-avatar" style="width:28px;height:28px;font-size:.75rem;">
                                                    <?= strtoupper(substr($u['username'],0,1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-white small"><?= h($u['full_name'] ?: $u['username']) ?></div>
                                                    <div class="text-white-50" style="font-size:.72rem;">@<?= h($u['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-white-50" style="font-size:.8rem;"><?= h($u['email']) ?></td>
                                        <td class="text-white-50" style="font-size:.8rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                                        <td>
                                            <a href="users.php?edit=<?= $u['id'] ?>" class="btn-icon" title="Edit user">
                                                <i class="bi bi-pencil"></i>
                                            </a>
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
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>