<?php
require_once "../inc/auth.inc.php";
requireEmployee();
<<<<<<< Updated upstream
header('Location: /index.php?msg=' . urlencode('Employee dashboard access is disabled in this branch.'));
exit;
=======

$customers      = [];
$totalCustomers = 0;
$totalRevenue   = 0;
$avgSpend       = 0;
$search = trim($_GET['q'] ?? '');

try {
    $pdo = getDB();

    $sql = "
        SELECT u.id, u.username, u.full_name, u.email, u.created_at, u.last_login,
               COUNT(DISTINCT o.id)        AS order_count,
               COALESCE(SUM(o.total), 0)   AS total_spent,
               COALESCE(AVG(o.total), 0)   AS avg_order,
               MAX(o.created_at)           AS last_order_at
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        WHERE u.role = 'customer'
    ";
    $params = [];
    if ($search) {
        $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " GROUP BY u.id ORDER BY total_spent DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    $totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
    $totalRevenue   = $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders")->fetchColumn();
    $avgSpend       = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

} catch (Exception $e) {
    $customers = [
        ['id'=>3,'username'=>'johndoe','full_name'=>'John Doe','email'=>'john@example.com','created_at'=>'2025-02-10 00:00:00','last_login'=>'2025-03-28 14:00:00','order_count'=>3,'total_spent'=>2036.00,'avg_order'=>678.67,'last_order_at'=>'2025-03-28 14:22:00'],
        ['id'=>4,'username'=>'janedoe','full_name'=>'Jane Doe','email'=>'jane@example.com','created_at'=>'2025-02-15 00:00:00','last_login'=>'2025-03-26 17:00:00','order_count'=>2,'total_spent'=>2797.00,'avg_order'=>1398.50,'last_order_at'=>'2025-03-26 17:44:00'],
    ];
    $totalCustomers = 2;
    $totalRevenue   = 4833;
    $avgSpend       = 2416.5;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers – Employee – Pomegranate</title>
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
            <div class="sidebar-role-badge role-employee">
                <i class="bi bi-person-badge"></i> Employee
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a href="index.php"    class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="products.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Products</a>
            <a href="orders.php"   class="sidebar-link"><i class="bi bi-receipt"></i> Orders</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> Sales Analytics</a>
            <a href="customers.php" class="sidebar-link active"><i class="bi bi-people"></i> Customers</a>

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

    <div class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Customers</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Customer Spending</h2>
                <p class="text-white-50 small mb-0">View customer accounts and their order history.</p>
            </div>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-4">
                    <div class="stat-card cyan">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value"><?= number_format($totalCustomers) ?></div>
                            <div class="stat-card-label">Total Customers</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card green">
                        <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($totalRevenue, 0) ?></div>
                            <div class="stat-card-label">Total Revenue</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-4">
                    <div class="stat-card purple">
                        <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
                        <div class="stat-body">
                            <div class="stat-card-value">$<?= number_format($avgSpend, 0) ?></div>
                            <div class="stat-card-label">Avg Spend / Customer</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer table -->
            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title">
                        All Customers
                        <span class="text-white-50 fw-normal ms-1" style="font-size:.8rem;">(<?= count($customers) ?>)</span>
                    </span>
                    <form method="GET" action="customers.php" class="d-flex gap-2 align-items-center">
                        <div class="search-bar-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" class="form-control-dark"
                                   placeholder="Search customers…"
                                   style="padding-left:2.25rem;width:200px;font-size:.82rem;"
                                   value="<?= h($search) ?>">
                        </div>
                        <button type="submit" class="btn-dash-secondary" style="font-size:.8rem;padding:.38rem .75rem;">Go</button>
                    </form>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Avg Order</th>
                                <th>Last Order</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                            <tr><td colspan="7" class="text-center text-white-50 py-4">No customers found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="sidebar-avatar" style="width:30px;height:30px;font-size:.8rem;">
                                            <?= strtoupper(substr($c['username'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-white small"><?= h($c['full_name'] ?: $c['username']) ?></div>
                                            <div class="text-white-50" style="font-size:.72rem;">@<?= h($c['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-white-50" style="font-size:.8rem;"><?= h($c['email']) ?></td>
                                <td class="fw-semibold text-white"><?= (int)$c['order_count'] ?></td>
                                <td><span class="fw-bold" style="color:#34d399;">$<?= number_format($c['total_spent'], 2) ?></span></td>
                                <td class="text-white-50" style="font-size:.82rem;">$<?= number_format($c['avg_order'], 2) ?></td>
                                <td class="text-white-50" style="font-size:.8rem;">
                                    <?= $c['last_order_at'] ? date('d M Y', strtotime($c['last_order_at'])) : '—' ?>
                                </td>
                                <td class="text-white-50" style="font-size:.8rem;"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
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
<script src="../js/dashboard.js"></script>
</body>
</html>
>>>>>>> Stashed changes
