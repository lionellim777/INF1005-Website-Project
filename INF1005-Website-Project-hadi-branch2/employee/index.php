<?php
require_once "../inc/auth.inc.php";
requireEmployee();

$pdo = null;
$stats = ['total_products' => 0, 'low_stock' => 0, 'total_orders' => 0, 'revenue_today' => 0];
$recentOrders = [];
$topProducts  = [];
$revenueData  = [];

try {
    $pdo = getDB();

    // Stats
    $stats['total_products'] = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
    $stats['low_stock']      = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND is_active=1")->fetchColumn();
    $stats['total_orders']   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['revenue_today']  = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();

    // Revenue last 7 months
    $revenueStmt = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%b') AS month, COALESCE(SUM(total),0) AS revenue
        FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 MONTH)
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY created_at ASC
    ");
    $revenueData = $revenueStmt->fetchAll();

    // Recent orders (last 10)
    $recentOrders = $pdo->query("
        SELECT o.id, u.username, u.full_name, o.total, o.status, o.created_at
        FROM orders o JOIN users u ON u.id=o.user_id
        ORDER BY o.created_at DESC LIMIT 10
    ")->fetchAll();

    // Top products by revenue
    $topProducts = $pdo->query("
        SELECT p.name, SUM(oi.quantity) AS units, SUM(oi.quantity*oi.price) AS revenue
        FROM order_items oi JOIN products p ON p.id=oi.product_id
        GROUP BY p.id ORDER BY revenue DESC LIMIT 5
    ")->fetchAll();

} catch (Exception $e) {
    // DB not connected – show sample data
    $stats = ['total_products'=>8,'low_stock'=>1,'total_orders'=>5,'revenue_today'=>0];
    $recentOrders = [
        ['id'=>1,'username'=>'johndoe','full_name'=>'John Doe','total'=>1348.00,'status'=>'delivered','created_at'=>'2025-03-28 14:22:00'],
        ['id'=>2,'username'=>'johndoe','full_name'=>'John Doe','total'=>599.00, 'status'=>'shipped',  'created_at'=>'2025-03-27 09:11:00'],
        ['id'=>3,'username'=>'janedoe','full_name'=>'Jane Doe','total'=>2498.00,'status'=>'processing','created_at'=>'2025-03-26 17:44:00'],
        ['id'=>4,'username'=>'janedoe','full_name'=>'Jane Doe','total'=>299.00, 'status'=>'pending',  'created_at'=>'2025-03-25 11:00:00'],
        ['id'=>5,'username'=>'johndoe','full_name'=>'John Doe','total'=>89.00,  'status'=>'delivered','created_at'=>'2025-03-24 08:33:00'],
    ];
    $topProducts = [
        ['name'=>'NeoPulse X1',    'units'=>145,'revenue'=>159355.00],
        ['name'=>'ArcWatch Ultra', 'units'=>203,'revenue'=>121597.00],
        ['name'=>'SoundPods Pro',  'units'=>178,'revenue'=> 44322.00],
        ['name'=>'UltraBook Pro',  'units'=> 89,'revenue'=>222411.00],
        ['name'=>'SlimAir 13',     'units'=> 61,'revenue'=> 60939.00],
    ];
    $revenueData = [
        ['month'=>'Oct','revenue'=>12400],
        ['month'=>'Nov','revenue'=>19800],
        ['month'=>'Dec','revenue'=>15600],
        ['month'=>'Jan','revenue'=>24200],
        ['month'=>'Feb','revenue'=>21000],
        ['month'=>'Mar','revenue'=>28900],
        ['month'=>'Apr','revenue'=>31500],
    ];
}

$revLabels = json_encode(array_column($revenueData, 'month'));
$revValues = json_encode(array_column($revenueData, 'revenue'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
            <div class="sidebar-role-badge role-employee">
                <i class="bi bi-person-badge"></i> Employee
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a href="index.php"    class="sidebar-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="products.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Products</a>
            <a href="orders.php"   class="sidebar-link"><i class="bi bi-receipt"></i> Orders</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> Sales Analytics</a>
            <a href="customers.php" class="sidebar-link"><i class="bi bi-people"></i> Customers</a>

            <div class="sidebar-section-label">Store</div>
            <a href="/catalog.php" class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
            <a href="/index.php"   class="sidebar-link"><i class="bi bi-house"></i> Back to Home</a>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout.php" class="sidebar-user">
                <div class="sidebar-avatar"><?= strtoupper(substr(getUsername(), 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <div class="name"><?= h(getFullName()) ?></div>
                    <div class="role">Sign out</div>
                </div>
                <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
            </a>
        </div>
    </aside>

    <!-- ── MAIN ── -->
    <div class="dash-main">
        <!-- Topbar -->
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Dashboard</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 small d-none d-md-inline">
                    <?= date('D, d M Y') ?>
                </span>
                <a href="products.php" class="btn-dash-primary">
                    <i class="bi bi-plus-lg"></i> Add Product
                </a>
            </div>
        </div>

        <div class="dash-content">

            <!-- Welcome -->
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Welcome back, <?= h(getFullName()) ?> 👋</h2>
                <p class="text-white-50 small mb-0">Here's what's happening with the store today.</p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($_GET['msg']) ?>
            </div>
            <?php endif; ?>

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['total_products']) ?></div>
                            <div class="stat-card-label">Active Products</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> 3 this month</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card amber">
                        <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['low_stock']) ?></div>
                            <div class="stat-card-label">Low Stock</div>
                            <?php if ($stats['low_stock'] > 0): ?>
                            <div class="stat-card-change change-down"><i class="bi bi-arrow-down-short"></i> Needs restock</div>
                            <?php else: ?>
                            <div class="stat-card-change change-up"><i class="bi bi-check2"></i> All stocked</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($stats['total_orders']) ?></div>
                            <div class="stat-card-label">Total Orders</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> 12% vs last week</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($stats['revenue_today'], 0) ?></div>
                            <div class="stat-card-label">Revenue Today</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> Live</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts row -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-8">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Revenue (Last 7 Months)</span>
                            <span class="gap-pill"><i class="bi bi-circle-fill" style="color:#22d3ee;font-size:.5rem;"></i> Revenue</span>
                        </div>
                        <div style="height:240px;">
                            <canvas id="revenueChart" data-labels='<?= $revLabels ?>' data-values='<?= $revValues ?>'></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-4">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Orders by Status</span>
                        </div>
                        <div style="height:240px;">
                            <canvas id="ordersChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent orders + top products -->
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="dash-table-wrap">
                        <div class="dash-table-header">
                            <span class="dash-table-title">Recent Orders</span>
                            <div class="search-bar-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control-dark" placeholder="Search…"
                                       style="padding-left:2.25rem;width:180px;font-size:.82rem;"
                                       data-table-search="recent-orders-table">
                            </div>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table" id="recent-orders-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td class="text-white fw-semibold">#<?= $o['id'] ?></td>
                                        <td><?= h($o['full_name'] ?: $o['username']) ?></td>
                                        <td class="text-white fw-semibold">$<?= number_format($o['total'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= h($o['status']) ?>">
                                                <?= ucfirst(h($o['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <a href="orders.php?id=<?= $o['id'] ?>" class="btn-icon" title="View order">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="chart-card h-100">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Top Products</span>
                            <a href="products.php" class="btn-dash-secondary" style="font-size:.75rem;padding:.3rem .7rem;">View all</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($topProducts as $i => $p): ?>
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center justify-content-center rounded-2 fw-bold"
                                     style="width:28px;height:28px;background:rgba(255,255,255,.05);font-size:.75rem;color:var(--text-muted);flex-shrink:0;">
                                    <?= $i+1 ?>
                                </div>
                                <div class="flex-1 min-width-0" style="flex:1;min-width:0;">
                                    <div class="fw-semibold text-white small text-truncate"><?= h($p['name']) ?></div>
                                    <div class="text-white-50" style="font-size:.72rem;"><?= number_format($p['units']) ?> units</div>
                                </div>
                                <div class="fw-bold text-white small flex-shrink-0">
                                    $<?= number_format($p['revenue'], 0) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.dash-content -->
    </div><!-- /.dash-main -->
</div><!-- /.dash-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
