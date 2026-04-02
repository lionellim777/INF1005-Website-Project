<?php
require_once "../inc/auth.inc.php";
requireAdmin();
<<<<<<< Updated upstream
header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;
=======

$recentOrders    = [];
$recentUsers     = [];
$recentProducts  = [];

try {
    $pdo = getDB();

    // Recent orders (last 20)
    $recentOrders = $pdo->query("
        SELECT o.id, o.status, o.total, o.created_at, o.updated_at,
               u.username, u.full_name
        FROM orders o JOIN users u ON u.id=o.user_id
        ORDER BY o.created_at DESC LIMIT 20
    ")->fetchAll();

    // Recently registered users (last 20)
    $recentUsers = $pdo->query("
        SELECT id, username, full_name, email, role, created_at
        FROM users ORDER BY created_at DESC LIMIT 20
    ")->fetchAll();

    // Recently added/updated products (last 20)
    $recentProducts = $pdo->query("
        SELECT p.id, p.name, p.price, p.stock, p.is_active, p.created_at, p.updated_at,
               c.name AS cat_name, u.username AS created_by_user
        FROM products p
        LEFT JOIN categories c ON c.id=p.category_id
        LEFT JOIN users u ON u.id=p.created_by
        ORDER BY GREATEST(p.created_at, COALESCE(p.updated_at, p.created_at)) DESC LIMIT 20
    ")->fetchAll();

} catch (Exception $e) {
    $recentOrders = [
        ['id'=>5,'status'=>'pending',  'total'=>89.00,  'created_at'=>'2025-03-25 11:00:00','updated_at'=>'2025-03-25 11:00:00','username'=>'janedoe','full_name'=>'Jane Doe'],
        ['id'=>4,'status'=>'shipped',  'total'=>299.00, 'created_at'=>'2025-03-24 09:00:00','updated_at'=>'2025-03-26 12:00:00','username'=>'johndoe','full_name'=>'John Doe'],
        ['id'=>3,'status'=>'delivered','total'=>2498.00,'created_at'=>'2025-03-20 14:00:00','updated_at'=>'2025-03-22 10:00:00','username'=>'janedoe','full_name'=>'Jane Doe'],
    ];
    $recentUsers = [
        ['id'=>4,'username'=>'janedoe',  'full_name'=>'Jane Doe',    'email'=>'jane@example.com', 'role'=>'customer', 'created_at'=>'2025-02-15 00:00:00'],
        ['id'=>3,'username'=>'johndoe',  'full_name'=>'John Doe',    'email'=>'john@example.com', 'role'=>'customer', 'created_at'=>'2025-02-10 00:00:00'],
        ['id'=>2,'username'=>'employee1','full_name'=>'Alex Chen',   'email'=>'emp@example.com',  'role'=>'employee', 'created_at'=>'2025-01-02 00:00:00'],
        ['id'=>1,'username'=>'admin',    'full_name'=>'System Admin','email'=>'admin@pom.com',    'role'=>'admin',    'created_at'=>'2025-01-01 00:00:00'],
    ];
    $recentProducts = [
        ['id'=>8,'name'=>'MagCharge 3-in-1','price'=>89, 'stock'=>200,'is_active'=>1,'created_at'=>'2025-03-01 10:00:00','updated_at'=>null,'cat_name'=>'Accessories','created_by_user'=>'admin'],
        ['id'=>7,'name'=>'SoundPods Pro',   'price'=>299,'stock'=>120,'is_active'=>1,'created_at'=>'2025-02-15 10:00:00','updated_at'=>null,'cat_name'=>'Accessories','created_by_user'=>'admin'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs – Admin – Pomegranate</title>
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
            <a href="reports.php"   class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Reports</a>

            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link"><i class="bi bi-gear"></i> Settings</a>
            <a href="logs.php"     class="sidebar-link active"><i class="bi bi-journal-text"></i> Activity Logs</a>

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
                <span class="page-title">Activity Logs</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Activity Logs</h2>
                <p class="text-white-50 small mb-0">Recent orders, user registrations, and product changes.</p>
            </div>

            <!-- Combined activity timeline (orders + users interleaved) -->
            <div class="row g-3 mb-4">

                <!-- Recent Orders Log -->
                <div class="col-12 col-lg-6">
                    <div class="dash-table-wrap h-100">
                        <div class="dash-table-header">
                            <span class="dash-table-title">
                                <i class="bi bi-receipt me-1" style="color:#22d3ee;"></i> Recent Orders
                            </span>
                            <a href="orders.php" class="btn-dash-secondary" style="font-size:.75rem;padding:.3rem .7rem;">View all</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Placed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentOrders)): ?>
                                    <tr><td colspan="5" class="text-center text-white-50 py-3">No orders yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td class="text-white fw-semibold">#<?= $o['id'] ?></td>
                                        <td>
                                            <div class="text-white small"><?= h($o['full_name'] ?: $o['username']) ?></div>
                                        </td>
                                        <td class="text-white fw-semibold">$<?= number_format($o['total'], 2) ?></td>
                                        <td><span class="status-badge status-<?= h($o['status']) ?>" style="font-size:.7rem;"><?= ucfirst(h($o['status'])) ?></span></td>
                                        <td class="text-white-50" style="font-size:.78rem;"><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- User Registrations Log -->
                <div class="col-12 col-lg-6">
                    <div class="dash-table-wrap h-100">
                        <div class="dash-table-header">
                            <span class="dash-table-title">
                                <i class="bi bi-person-plus me-1" style="color:#818cf8;"></i> User Registrations
                            </span>
                            <a href="users.php" class="btn-dash-secondary" style="font-size:.75rem;padding:.3rem .7rem;">View all</a>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="dash-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Role</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentUsers)): ?>
                                    <tr><td colspan="4" class="text-center text-white-50 py-3">No users yet.</td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($recentUsers as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="sidebar-avatar" style="width:26px;height:26px;font-size:.7rem;">
                                                    <?= strtoupper(substr($u['username'],0,1)) ?>
                                                </div>
                                                <div>
                                                    <div class="text-white small fw-semibold"><?= h($u['full_name'] ?: $u['username']) ?></div>
                                                    <div class="text-white-50" style="font-size:.7rem;">@<?= h($u['username']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="status-badge status-<?= h($u['role']) ?>" style="font-size:.7rem;"><?= ucfirst(h($u['role'])) ?></span></td>
                                        <td class="text-white-50" style="font-size:.78rem;"><?= h($u['email']) ?></td>
                                        <td class="text-white-50" style="font-size:.78rem;"><?= date('d M Y H:i', strtotime($u['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Changes -->
            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title">
                        <i class="bi bi-box-seam me-1" style="color:#34d399;"></i> Product Activity
                    </span>
                    <a href="products.php" class="btn-dash-secondary" style="font-size:.75rem;padding:.3rem .7rem;">Manage</a>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Added by</th>
                                <th>Created</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentProducts)): ?>
                            <tr><td colspan="8" class="text-center text-white-50 py-3">No products yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($recentProducts as $p): ?>
                            <tr>
                                <td class="text-white fw-semibold small"><?= h($p['name']) ?></td>
                                <td class="text-white-50 small"><?= h($p['cat_name'] ?? '—') ?></td>
                                <td class="text-white small">$<?= number_format($p['price'], 2) ?></td>
                                <td>
                                    <span class="fw-semibold <?= (int)$p['stock']===0?'text-danger':((int)$p['stock']<=5?'text-warning':'text-white') ?>">
                                        <?= (int)$p['stock'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $p['is_active']?'status-active':'status-inactive' ?>" style="font-size:.7rem;">
                                        <?= $p['is_active']?'Active':'Removed' ?>
                                    </span>
                                </td>
                                <td class="text-white-50 small"><?= h($p['created_by_user'] ?? '—') ?></td>
                                <td class="text-white-50" style="font-size:.78rem;"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                <td class="text-white-50" style="font-size:.78rem;">
                                    <?= $p['updated_at'] ? date('d M Y H:i', strtotime($p['updated_at'])) : '—' ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
>>>>>>> Stashed changes
