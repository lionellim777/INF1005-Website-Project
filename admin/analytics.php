<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$revenueData  = [];
$categoryData = [];
$topProducts  = [];
$summary      = ['total_orders'=>0,'total_revenue'=>0,'avg_order'=>0,'total_customers'=>0];

try {
    $pdo = getDB();

    $revenueData = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%b %Y') AS label,
               COALESCE(SUM(total),0) AS revenue,
               COUNT(*) AS orders
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY MIN(created_at) ASC
    ")->fetchAll();

    $categoryData = $pdo->query("
        SELECT c.name, SUM(oi.quantity*oi.price) AS revenue, SUM(oi.quantity) AS units
        FROM order_items oi
        JOIN products p   ON p.id=oi.product_id
        JOIN categories c ON c.id=p.category_id
        GROUP BY c.id ORDER BY revenue DESC
    ")->fetchAll();

    $topProducts = $pdo->query("
        SELECT p.name, c.name AS cat_name, SUM(oi.quantity) AS units, SUM(oi.quantity*oi.price) AS revenue
        FROM order_items oi
        JOIN products p   ON p.id=oi.product_id
        LEFT JOIN categories c ON c.id=p.category_id
        GROUP BY p.id ORDER BY revenue DESC LIMIT 10
    ")->fetchAll();

    $row = $pdo->query("
        SELECT COUNT(*) AS total_orders,
               COALESCE(SUM(total),0) AS total_revenue,
               COALESCE(AVG(total),0) AS avg_order
        FROM orders
    ")->fetch();
    $summary = array_merge($summary, $row);
    $summary['total_customers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();

} catch (Exception $e) {
    $revenueData = [
        ['label'=>'Oct 2024','revenue'=>12400,'orders'=>8],
        ['label'=>'Nov 2024','revenue'=>19800,'orders'=>14],
        ['label'=>'Dec 2024','revenue'=>15600,'orders'=>11],
        ['label'=>'Jan 2025','revenue'=>24200,'orders'=>18],
        ['label'=>'Feb 2025','revenue'=>21000,'orders'=>15],
        ['label'=>'Mar 2025','revenue'=>28900,'orders'=>21],
        ['label'=>'Apr 2025','revenue'=>31500,'orders'=>24],
    ];
    $categoryData = [
        ['name'=>'Smartphones','revenue'=>95000,'units'=>62],
        ['name'=>'Laptops',    'revenue'=>74000,'units'=>30],
        ['name'=>'Wearables',  'revenue'=>36000,'units'=>60],
        ['name'=>'Accessories','revenue'=>19000,'units'=>300],
    ];
    $topProducts = [
        ['name'=>'NeoPulse X1',   'cat_name'=>'Smartphones','units'=>145,'revenue'=>159355],
        ['name'=>'ArcWatch Ultra','cat_name'=>'Wearables',  'units'=>203,'revenue'=>121597],
        ['name'=>'UltraBook Pro', 'cat_name'=>'Laptops',    'units'=> 89,'revenue'=>222411],
        ['name'=>'SoundPods Pro', 'cat_name'=>'Accessories','units'=>178,'revenue'=> 44322],
        ['name'=>'SlimAir 13',    'cat_name'=>'Laptops',    'units'=> 61,'revenue'=> 60939],
    ];
    $summary = ['total_orders'=>5,'total_revenue'=>4833,'avg_order'=>966.6,'total_customers'=>2];
}

$revLabels  = json_encode(array_column($revenueData, 'label'));
$revValues  = json_encode(array_column($revenueData, 'revenue'));
$catLabels  = json_encode(array_column($categoryData, 'name'));
$catValues  = json_encode(array_column($categoryData, 'revenue'));
$prodLabels = json_encode(array_column($topProducts, 'name'));
$prodValues = json_encode(array_column($topProducts, 'units'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics – Admin – Pomegranate</title>
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
            <a href="analytics.php" class="sidebar-link active"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="reports.php"   class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Reports</a>

            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear"></i> Settings</a>
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
                <span class="page-title">Analytics</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Business Analytics</h2>
                <p class="text-white-50 small mb-0">Full revenue and customer intelligence overview.</p>
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($summary['total_revenue'], 0) ?></div>
                            <div class="stat-card-label">Total Revenue</div>
                            <div class="stat-card-change change-up"><i class="bi bi-arrow-up-short"></i> All time</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($summary['total_orders']) ?></div>
                            <div class="stat-card-label">Total Orders</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($summary['avg_order'], 0) ?></div>
                            <div class="stat-card-label">Avg Order Value</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card amber">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($summary['total_customers']) ?></div>
                            <div class="stat-card-label">Customers</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue chart -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="chart-card">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Monthly Revenue (Last 12 Months)</span>
                            <span class="gap-pill"><i class="bi bi-circle-fill" style="color:#22d3ee;font-size:.5rem;"></i> Revenue</span>
                        </div>
                        <div style="height:280px;">
                            <canvas id="revenueChart"
                                    data-labels='<?= $revLabels ?>'
                                    data-values='<?= $revValues ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-5">
                    <div class="chart-card h-100">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Revenue by Category</span>
                        </div>
                        <div style="height:260px;">
                            <canvas id="categoryChart"
                                    data-labels='<?= $catLabels ?>'
                                    data-values='<?= $catValues ?>'></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-7">
                    <div class="chart-card h-100">
                        <div class="chart-card-header">
                            <span class="chart-card-title">Top Products by Units Sold</span>
                        </div>
                        <div style="height:260px;">
                            <canvas id="productsChart"
                                    data-labels='<?= $prodLabels ?>'
                                    data-values='<?= $prodValues ?>'></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top products table -->
            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title">Top Products Detail</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Avg / Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $i => $p): ?>
                            <tr>
                                <td class="text-white fw-semibold"><?= $i+1 ?></td>
                                <td class="text-white fw-semibold"><?= h($p['name']) ?></td>
                                <td><span class="status-badge status-active" style="font-size:.72rem;"><?= h($p['cat_name'] ?? '—') ?></span></td>
                                <td class="text-white"><?= number_format($p['units']) ?></td>
                                <td class="text-white fw-semibold">$<?= number_format($p['revenue'], 2) ?></td>
                                <td class="text-white-50"><?= $p['units'] > 0 ? '$'.number_format($p['revenue']/$p['units'], 2) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../js/dashboard.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const catCanvas = document.getElementById('categoryChart');
    if (catCanvas && typeof Chart !== 'undefined') {
        new Chart(catCanvas, {
            type: 'doughnut',
            data: {
                labels: JSON.parse(catCanvas.dataset.labels),
                datasets: [{
                    data: JSON.parse(catCanvas.dataset.values),
                    backgroundColor: ['#22d3ee','#818cf8','#34d399','#fbbf24'],
                    borderColor: '#0c1220', borderWidth: 3, hoverOffset: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '65%',
                plugins: {
                    legend: { position:'bottom', labels:{ color:'rgba(255,255,255,.5)', padding:14, font:{size:11}, boxWidth:10, usePointStyle:true } },
                    tooltip: { backgroundColor:'#0c1220', borderColor:'rgba(255,255,255,.08)', borderWidth:1,
                               titleColor:'#fff', bodyColor:'rgba(255,255,255,.6)',
                               callbacks:{ label: ctx => ' $' + ctx.raw.toLocaleString() } }
                }
            }
        });
    }
});
</script>
</body>
</html>
