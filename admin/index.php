<?php
/**
 * Admin Dashboard – Overview
 * Shows summary stats: total products, low-stock items, pending orders, total users.
 * Access: admin only.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Change 1: Securely enforce the Admin role using our updated framework
require_role(['admin']);

// Fetch basic session user info for the greeting
$currentSessionUser = [
    'first_name' => $_SESSION['fname'] ?? 'Admin',
];

// ============================================================
// FETCH STATS (Live DB Queries via MySQLi)
// ============================================================

// Total products
$res = $db_conn->query('SELECT COUNT(*) AS cnt FROM products');
$totalProducts = $res ? (int)$res->fetch_assoc()['cnt'] : 0;

// Low stock: items with stock < 5
// Change 3: Realigned to use 'stock' instead of 'stock_quantity'
$res = $db_conn->query('SELECT COUNT(*) AS cnt FROM products WHERE stock < 5 AND stock > 0');
$lowStockCount = $res ? (int)$res->fetch_assoc()['cnt'] : 0;

// Pending orders (status = pending OR processing)
$res = $db_conn->query("SELECT COUNT(*) AS cnt FROM orders WHERE status IN ('pending','processing')");
$pendingOrders = $res ? (int)$res->fetch_assoc()['cnt'] : 0;

// Total registered users
$res = $db_conn->query('SELECT COUNT(*) AS cnt FROM users');
$totalUsers = $res ? (int)$res->fetch_assoc()['cnt'] : 0;

// 5 most recent orders with customer name
// Change 2: Realigned joins and used 'AS' to trick HTML into working without edits
$recentOrders = [];
$res = $db_conn->query(
    "SELECT o.id AS order_id, o.status, o.total_amount, o.created_at,
            u.fname AS first_name, u.lname AS last_name
     FROM orders o
     JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
if ($res) {
    $recentOrders = $res->fetch_all(MYSQLI_ASSOC);
}

// Monthly revenue — last 6 months
$revenueRows = [];
$res = $db_conn->query(
    "SELECT DATE_FORMAT(created_at, '%b %Y') AS month,
            DATE_FORMAT(created_at, '%Y-%m') AS sort_key,
            SUM(total_amount) AS revenue
     FROM orders
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
       AND status != 'cancelled' || status != 'failed' || status != 'pending'
     GROUP BY month, sort_key
     ORDER BY sort_key ASC"
);
if ($res) {
    $revenueRows = $res->fetch_all(MYSQLI_ASSOC);
}

// Extract column arrays for the chart
$monthlyLabels  = array_column($revenueRows, 'month');
$monthlyRevenue = array_map(fn($r) => round((float)$r['revenue'], 2), $revenueRows);

// Low stock items (stock < 5), up to 5 rows
// Change 3: Aliased 'stock' back to 'stock_quantity' for the HTML
$lowStockItems = [];
$res = $db_conn->query(
    "SELECT name, stock AS stock_quantity, category 
     FROM products
     WHERE stock < 5
     ORDER BY stock ASC
     LIMIT 5"
);
if ($res) {
    $lowStockItems = $res->fetch_all(MYSQLI_ASSOC);
}

// Helper: map order status to Bootstrap badge colour
function orderStatusBadge(string $status): string {
    return match (strtolower($status)) {
        'pending'    => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-primary',
        'delivered'  => 'bg-success',
        'cancelled'  => 'bg-danger',
        default      => 'bg-secondary',
    };
}

$currentPage = 'dashboard';
$pageTitle   = 'Admin Overview – Pomegranate';
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
    <link rel="stylesheet" href="/admin/css/admin.css">
    <link rel="stylesheet" href="/admin/css/dashboard.css">
</head>
<body class="admin-body">

    <div class="admin-layout">

        <?php include __DIR__ . '/inc/sidebar.php'; ?>

        <div class="admin-content">

            <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
                <div>
                    <h4 class="fw-bold mb-0">Dashboard Overview</h4>
                    <small class="text-muted">Welcome back, <?= htmlspecialchars($currentSessionUser['first_name']) ?>!</small>
                </div>
                <div class="text-muted small">
                    <?= date('l, d F Y') ?>
                </div>
            </div>

            <div class="p-4">

                <div class="row g-4 mb-4">

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
                                <a href="/admin/products.php" class="small text-decoration-none" style="color:#28666e;">
                                    Manage products <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

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
                                <a href="/admin/inventory.php" class="small text-decoration-none" style="color:#28666e;">
                                    View inventory <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

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
                                <a href="/admin/orders.php" class="small text-decoration-none" style="color:#28666e;">
                                    Manage orders <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

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
                                <a href="/admin/manage_users.php" class="small text-decoration-none" style="color:#28666e;">
                                    Manage users <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                </div><div class="row g-4 mb-4">
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
                </div><div class="row g-4">

                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                                <h6 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Orders</h6>
                                <a href="/admin/orders.php" class="btn btn-sm btn-outline-secondary">View all</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-3">Order #</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($recentOrders)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No orders yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td class="ps-3 text-muted">#<?= htmlspecialchars((string)$order['order_id']) ?></td>
                                                <td class="fw-semibold">
                                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?>
                                                </td>
                                                <td>$<?= number_format((float)$order['total_amount'], 2) ?></td>
                                                <td>
                                                    <span class="badge <?= orderStatusBadge($order['status']) ?>">
                                                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
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
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                                <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Low Stock</h6>
                                <a href="/admin/inventory.php" class="btn btn-sm btn-outline-secondary">View all</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($lowStockItems)): ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                                        All stock levels are healthy.
                                    </div>
                                <?php else: ?>
                                    <ul class="list-unstyled mb-0">
                                    <?php foreach ($lowStockItems as $item): ?>
                                        <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-semibold small"><?= htmlspecialchars($item['name']) ?></div>
                                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($item['category'] ?? '') ?></div>
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
                    </div>

                </div></div></div></div><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        var ctx = document.getElementById('revenueChart').getContext('2d');

        // Change 4: Passing the dynamic array values from PHP into JSON for JS
        var labels  = <?= json_encode($monthlyLabels) ?>;
        var revenue = <?= json_encode($monthlyRevenue) ?>;

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
                    tension:         0.35
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>