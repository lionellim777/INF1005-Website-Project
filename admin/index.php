<?php
/**
 * Admin Dashboard – Overview
 * Access: admin only.
 */

// 1. Boot your engine and secure the page
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

// 2. ONLY Admins can view this dashboard
require_role(['admin', 'employee']);

// 3. Extract safe variables for the UI
$currentSessionUser = [
    'first_name' => $_SESSION['fname'] ?? 'Admin',
    'last_name'  => $_SESSION['lname'] ?? '',
    'email'      => $_SESSION['email'] ?? '',
    'role'       => $_SESSION['role']  ?? 'admin'
];

// ============================================================
// REAL MYSQL FETCH STATS
// ============================================================

// Total products
$resProducts = $db_conn->query("SELECT COUNT(*) as c FROM products");
$totalProducts = $resProducts ? $resProducts->fetch_assoc()['c'] : 0;

// Low stock items (< 5)
$resLowStock = $db_conn->query("SELECT COUNT(*) as c FROM products WHERE stock_quantity < 5");
$lowStockCount = $resLowStock ? $resLowStock->fetch_assoc()['c'] : 0;

// Pending orders
$resPending = $db_conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('pending','processing')");
$pendingOrders = $resPending ? $resPending->fetch_assoc()['c'] : 0;

// Total users
$resUsers = $db_conn->query("SELECT COUNT(*) as c FROM users");
$totalUsers = $resUsers ? $resUsers->fetch_assoc()['c'] : 0;

// Recent orders (Join orders and users tables, map to Dalton's expected keys)
$recentOrders = [];
$resRecent = $db_conn->query("
    SELECT o.id AS order_id, o.status, o.total_amount, o.created_at, 
           u.fname AS first_name, u.lname AS last_name
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC LIMIT 5
");
if ($resRecent) {
    while ($row = $resRecent->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

// Low stock items list
$lowStockItems = [];
$resItems = $db_conn->query("SELECT name, stock_quantity, category FROM products WHERE stock_quantity < 5 ORDER BY stock_quantity ASC LIMIT 5");
if ($resItems) {
    while ($row = $resItems->fetch_assoc()) {
        $lowStockItems[] = $row;
    }
}

// Helper: map order status to Bootstrap badge colour
function orderStatusBadge(string $status): string {
    return match (strtolower($status)) {
        'pending'    => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-primary',
        'delivered'  => 'bg-success',
        'cancelled', 'failed' => 'bg-danger',
        default      => 'bg-secondary',
    };
}

$currentPage = 'dashboard';
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
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body" style="background: var(--bg-primary);">

    <div class="admin-layout">
        <?php include __DIR__ . '/inc/sidebar.php'; ?>

        <div class="admin-content dash-main">
            <div class="admin-topbar">
                <div>
                    <h4 class="page-title mb-0">Dashboard Overview</h4>
                    <small class="page-text">Welcome back, <?= htmlspecialchars($currentSessionUser['first_name']) ?>!</small>
                </div>
                <div class="page-text small">
                    <?= date('l, d F Y') ?>
                </div>
            </div>

            <div class="dash-content">
                <div class="row g-4 mb-4">
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card cyan h-100">
                            <div class="stat-icon"><i class="bi bi-phone"></i></div>
                            <div>
                                <div class="stat-card-value"><?= $totalProducts ?></div>
                                <div class="stat-card-label">Total Products</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card amber h-100">
                            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <div>
                                <div class="stat-card-value"><?= $lowStockCount ?></div>
                                <div class="stat-card-label">Low Stock Items</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card purple h-100">
                            <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                            <div>
                                <div class="stat-card-value"><?= $pendingOrders ?></div>
                                <div class="stat-card-label">Pending Orders</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-card green h-100">
                            <div class="stat-icon"><i class="bi bi-people"></i></div>
                            <div>
                                <div class="stat-card-value"><?= $totalUsers ?></div>
                                <div class="stat-card-label">Registered Users</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="dash-table-wrap h-100">
                            <div class="dash-table-header">
                                <h6 class="dash-table-title mb-0"><i class="bi bi-clock-history me-2"></i>Recent Orders</h6>
                                <a href="/admin/orders.php" class="btn-dash-secondary btn-sm">View all</a>
                            </div>
                            <div class="table-responsive">
                                <table class="dash-table">
                                    <thead alignment=center>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                        <tr><td colspan="5" class="text-center text-white py-4">No orders yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td class="text-white">#<?= htmlspecialchars((string)$order['order_id']) ?></td>
                                            <td class="fw-semibold">
                                                <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                            </td>
                                            <td>$<?= number_format((float)$order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge <?= orderStatusBadge($order['status']) ?>">
                                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-white small">
                                                <?= date('d M Y', strtotime($order['created_at'])) ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="form-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="form-card-title mb-0 border-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock</h6>
                                <a href="/admin/inventory.php" class="btn-dash-secondary btn-sm">View all</a>
                            </div>
                            <?php if (empty($lowStockItems)): ?>
                                <div class="text-center text-white py-3">
                                    <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                                    All stock levels are healthy.
                                </div>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0">
                                <?php foreach ($lowStockItems as $item): ?>
                                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary">
                                        <div>
                                            <div class="fw-semibold small text-white"><?= htmlspecialchars($item['name']) ?></div>
                                            <div class="text-white" style="font-size:0.75rem;"><?= htmlspecialchars($item['category']) ?></div>
                                        </div>
                                        <span class="badge <?= (int)$item['stock_quantity'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                            <?= (int)$item['stock_quantity'] === 0 ? 'Out of stock' : (int)$item['stock_quantity'] . ' left' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div></div></div></div></body>
</html>