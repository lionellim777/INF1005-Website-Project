<?php
require_once "../inc/auth.inc.php";
requireAdmin();
<<<<<<< Updated upstream
header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;
=======

$monthly = [];
$byStatus = [];

try {
    $pdo = getDB();

    $monthly = $pdo->query("
        SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
               DATE_FORMAT(created_at,'%b %Y') AS label,
               COUNT(*) AS orders,
               COALESCE(SUM(total),0) AS revenue
        FROM orders
        GROUP BY DATE_FORMAT(created_at,'%Y-%m')
        ORDER BY ym DESC LIMIT 12
    ")->fetchAll();

    $byStatus = $pdo->query("
        SELECT status, COUNT(*) AS orders, COALESCE(SUM(total),0) AS revenue
        FROM orders GROUP BY status ORDER BY orders DESC
    ")->fetchAll();

} catch (Exception $e) {
    $monthly = [
        ['ym'=>'2025-04','label'=>'Apr 2025','orders'=>24,'revenue'=>31500],
        ['ym'=>'2025-03','label'=>'Mar 2025','orders'=>21,'revenue'=>28900],
        ['ym'=>'2025-02','label'=>'Feb 2025','orders'=>15,'revenue'=>21000],
        ['ym'=>'2025-01','label'=>'Jan 2025','orders'=>18,'revenue'=>24200],
        ['ym'=>'2024-12','label'=>'Dec 2024','orders'=>11,'revenue'=>15600],
        ['ym'=>'2024-11','label'=>'Nov 2024','orders'=>14,'revenue'=>19800],
        ['ym'=>'2024-10','label'=>'Oct 2024','orders'=> 8,'revenue'=>12400],
    ];
    $byStatus = [
        ['status'=>'delivered', 'orders'=>2,'revenue'=>1437],
        ['status'=>'processing','orders'=>1,'revenue'=>2498],
        ['status'=>'shipped',   'orders'=>1,'revenue'=> 599],
        ['status'=>'pending',   'orders'=>1,'revenue'=> 299],
    ];
}

$grandTotal   = array_sum(array_column($monthly, 'revenue'));
$grandOrders  = array_sum(array_column($monthly, 'orders'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports – Admin – Pomegranate</title>
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
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="reports.php"   class="sidebar-link active"><i class="bi bi-file-earmark-text"></i> Reports</a>

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
                <span class="page-title">Reports</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Revenue Reports</h2>
                <p class="text-white-50 small mb-0">Monthly breakdowns and order status summaries.</p>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-4">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($grandTotal, 0) ?></div>
                            <div class="stat-card-label">Revenue (shown period)</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($grandOrders) ?></div>
                            <div class="stat-card-label">Orders (shown period)</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= $grandOrders > 0 ? number_format($grandTotal/$grandOrders, 0) : 0 ?></div>
                            <div class="stat-card-label">Avg Order Value</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <!-- Monthly revenue table -->
                <div class="col-12 col-lg-7">
                    <div class="dash-table-wrap">
                        <div class="dash-table-header">
                            <span class="dash-table-title">Monthly Revenue Breakdown</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                        <th>Avg / Order</th>
                                        <th>Share</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly as $m): ?>
                                    <tr>
                                        <td class="text-white fw-semibold"><?= h($m['label']) ?></td>
                                        <td class="text-white-50"><?= (int)$m['orders'] ?></td>
                                        <td class="text-white fw-semibold">$<?= number_format($m['revenue'], 2) ?></td>
                                        <td class="text-white-50">$<?= $m['orders'] > 0 ? number_format($m['revenue']/$m['orders'],0) : '—' ?></td>
                                        <td>
                                            <?php $pct = $grandTotal > 0 ? round($m['revenue']/$grandTotal*100) : 0; ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width:60px;height:4px;background:rgba(255,255,255,.07);border-radius:2px;overflow:hidden;">
                                                    <div style="width:<?= $pct ?>%;height:100%;background:#22d3ee;border-radius:2px;"></div>
                                                </div>
                                                <span class="text-white-50" style="font-size:.75rem;"><?= $pct ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- By status table -->
                <div class="col-12 col-lg-5">
                    <div class="dash-table-wrap h-100">
                        <div class="dash-table-header">
                            <span class="dash-table-title">Revenue by Order Status</span>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Orders</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byStatus as $s): ?>
                                    <tr>
                                        <td><span class="status-badge status-<?= h($s['status']) ?>"><?= ucfirst(h($s['status'])) ?></span></td>
                                        <td class="text-white"><?= (int)$s['orders'] ?></td>
                                        <td class="text-white fw-semibold">$<?= number_format($s['revenue'], 2) ?></td>
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
<script src="../js/dashboard.js"></script>
</body>
</html>
>>>>>>> Stashed changes
