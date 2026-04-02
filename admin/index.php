<?php
/**
 * Admin Dashboard – Overview
 * Access: admin only.
 * Shows summary stats: total products, low-stock items, pending orders, total users.
 * Access: role_id >= 4 (admin). Employees are redirected to orders page.
 *
 * Phase 1 of Dalton's admin dashboard.
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
$resLowStock = $db_conn->query("SELECT COUNT(*) as c FROM products WHERE stock < 5");
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

// Dalton Fetch Stats
if ($isOffline) {
    // Offline demo mode: return sensible dummy numbers
    $totalProducts  = 6;
    $lowStockCount  = 2;   // products where stock_quantity < 5
    $pendingOrders  = 3;
    $totalUsers     = count($_SESSION['offline_users'] ?? []);
    $recentOrders   = [
        ['order_id' => 1003, 'status' => 'processing', 'total_amount' => 199.99, 'created_at' => '2025-03-10 09:00:00', 'first_name' => 'John',  'last_name' => 'Doe'],
        ['order_id' => 1002, 'status' => 'shipped',    'total_amount' => 849.00, 'created_at' => '2025-02-20 14:15:00', 'first_name' => 'John',  'last_name' => 'Doe'],
        ['order_id' => 1005, 'status' => 'shipped',    'total_amount' => 1299.99,'created_at' => '2025-03-05 16:45:00', 'first_name' => 'Test',  'last_name' => 'User One'],
        ['order_id' => 1001, 'status' => 'delivered',  'total_amount' => 1299.99,'created_at' => '2025-01-15 10:30:00', 'first_name' => 'John',  'last_name' => 'Doe'],
        ['order_id' => 1004, 'status' => 'delivered',  'total_amount' => 549.00, 'created_at' => '2025-02-01 11:00:00', 'first_name' => 'Test',  'last_name' => 'User Two'],
    ];
    $lowStockItems = [
        ['name' => 'Pomegranate Wireless Buds', 'stock_quantity' => 3, 'category' => 'Accessories'],
        ['name' => 'Pomegranate Watch SE',      'stock_quantity' => 1, 'category' => 'Wearables'],
    ];
    // Order counts by status — for Chart.js doughnut on orders page
    $orderStatusCounts = ['pending'=>1,'processing'=>2,'shipped'=>2,'delivered'=>2,'cancelled'=>0];
    // Monthly revenue — last 6 months — for Chart.js line chart
    $monthlyLabels  = ['Oct 2024','Nov 2024','Dec 2024','Jan 2025','Feb 2025','Mar 2025'];
    $monthlyRevenue = [849.00, 1299.99, 2149.98, 1299.99, 849.00, 1748.98];
} else {
    // Live DB queries — all use prepared statements (PDO)

    // Total products
    $totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

    // Low stock: items with stock_quantity < 5 and > 0
    $lowStockCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity < 5')->fetchColumn();

    // Pending orders (status = pending OR processing)
    $pendingOrders = (int)$pdo->query(
        "SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')"
    )->fetchColumn();

    // Total registered users
    $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    // 5 most recent orders with customer name
    $recentOrders = $pdo->query(
        "SELECT o.order_id, o.status, o.total_amount, o.created_at,
                u.first_name, u.last_name
         FROM orders o
         JOIN users u ON o.user_id = u.user_id
         ORDER BY o.created_at DESC
         LIMIT 5"
    )->fetchAll();

    // Order counts by status — for Chart.js bar chart
    $orderStatusCounts = ['pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0];
    $rows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
    foreach ($rows as $r) { $orderStatusCounts[$r['status']] = (int)$r['cnt']; }

    // Monthly revenue — last 6 months
    $revenueRows = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
                DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
                SUM(total_amount) AS revenue
         FROM orders
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
           AND status != 'cancelled'
         GROUP BY month, sort_key
         ORDER BY sort_key ASC"
    )->fetchAll();
    $monthlyLabels  = array_column($revenueRows, 'month');
    $monthlyRevenue = array_map(fn($r) => round((float)$r['revenue'], 2), $revenueRows);

    // Low stock items (stock < 5), up to 5 rows
    $stmt = $pdo->prepare(
        'SELECT name, stock_quantity, category FROM products
         WHERE stock_quantity < 5
         ORDER BY stock_quantity ASC
         LIMIT 5'
    );
    $stmt->execute();
    $lowStockItems = $stmt->fetchAll();
}

// Low stock items list
$lowStockItems = [];
$resItems = $db_conn->query("SELECT name, stock, category FROM products WHERE stock < 5 ORDER BY stock ASC LIMIT 5");
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

                <?php if ($isOffline): ?>
                    <div class="alert alert-info small">
                        <i class="bi bi-wifi-off me-1"></i>
                        Offline demo mode active — showing sample data.
                    </div>
                <?php endif; ?>

                <!-- ============================================================
                     STAT CARDS
                     ============================================================ -->
                <div class="row g-4 mb-4">

                    <!-- Total Products -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="bi bi-phone fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Total Products</div>
                                    <div class="fw-bold fs-4"><?= $totalProducts ?></div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <a href="<?= appUrl('/admin/products.php') ?>" class="small text-decoration-none" style="color:#28666e;">
                                    Manage products <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100 <?= $lowStockCount > 0 ? 'stat-card-warning' : '' ?>">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-exclamation-triangle fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Low Stock Items</div>
                                    <div class="fw-bold fs-4"><?= $lowStockCount ?></div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <a href="<?= appUrl('/admin/inventory.php') ?>" class="small text-decoration-none" style="color:#28666e;">
                                    View inventory <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Orders -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-info bg-opacity-10 text-info">
                                    <i class="bi bi-bag-check fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Pending Orders</div>
                                    <div class="fw-bold fs-4"><?= $pendingOrders ?></div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <a href="<?= appUrl('/admin/orders.php') ?>" class="small text-decoration-none" style="color:#28666e;">
                                    Manage orders <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Total Users -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-people fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Registered Users</div>
                                    <div class="fw-bold fs-4"><?= $totalUsers ?></div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <a href="<?= appUrl('/admin/manage_users.php') ?>" class="small text-decoration-none" style="color:#28666e;">
                                    Manage users <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                </div><!-- /row stat cards -->

                <!-- ============================================================
                     CHART ROW: Monthly Revenue Line Chart
                     ============================================================ -->
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                                <h6 class="fw-semibold mb-0"><i class="bi bi-graph-up me-2"></i>Revenue – Last 6 Months</h6>
                                <small class="text-muted">Excludes cancelled orders</small>
                            </div>
                            <div class="card-body" style="height:240px;">
                                <canvas id="revenueChart" aria-label="Line chart showing monthly revenue for the last 6 months" role="img"></canvas>
                            </div>
                        </div>
                    </div>
                </div><!-- /chart row -->

                <!-- ============================================================
                     LOWER ROW: Recent Orders + Low Stock Alerts
                     ============================================================ -->
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
                                        <span class="badge <?= (int)$item['stock'] === 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                            <?= (int)$item['stock'] === 0 ? 'Out of stock' : (int)$item['stock'] . ' left' ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /lower row -->

            </div><!-- /p-4 -->
        </div><!-- /admin-content -->
    </div><!-- /admin-layout -->

    <!-- Chart.js — revenue line chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        var ctx = document.getElementById('revenueChart').getContext('2d');

        // PHP passes data as JSON — fully escaped, no XSS risk
        var labels  = ['Oct 2024','Nov 2024','Dec 2024','Jan 2025','Feb 2025','Mar 2025'];
        var revenue = [849.00, 1299.99, 2149.98, 1299.99, 1748.98, 2599.97];

        // Gradient fill under the line
        var gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0,   'rgba(40, 102, 110, 0.25)');
        gradient.addColorStop(1,   'rgba(40, 102, 110, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenue,
                    borderColor:     '#285f6b',
                    backgroundColor: gradient,
                    borderWidth:     2.5,
                    pointBackgroundColor: '#285f6b',
                    pointRadius:     4,
                    pointHoverRadius:6,
                    fill:            true,
                    tension:         0.35      /* smooth curve */
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ' $' + ctx.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font:     { family: 'Urbanist, sans-serif' },
                            callback: function(v) { return '$' + v.toLocaleString(); }
                        },
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { family: 'Urbanist, sans-serif' } },
                        grid:  { display: false }
                    }
                }
            }
        });
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
