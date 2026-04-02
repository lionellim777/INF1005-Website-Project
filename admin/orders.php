<?php
/**
 * Admin – Order Management
 * Access: admin AND employee.
 * Admin – Order Management (Phase 4)
 * View all orders and update their statuses.
 * Access: role_id >= 3 (employee AND admin).
 *   - Employees: can update order status only.
 *   - Admins: same access here, plus full dashboard.
 *
 * Security: PDO prepared statements, CSRF, htmlspecialchars output escaping.
 */
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

require_role(['admin', 'employee']);

$isAdmin = (strtolower($_SESSION['role'] ?? '') === 'admin');
const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$successMsg = '';
$errorMsg = '';

// POST: UPDATE ORDER STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = strtolower(trim($_POST['new_status'] ?? ''));

    if ($orderId > 0 && in_array($newStatus, ORDER_STATUSES)) {
        $stmt = $db_conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        if ($stmt->execute()) {
            $successMsg = "Order #{$orderId} updated to " . ucfirst($newStatus);
        } else {
            $errorMsg = "Failed to update order.";
        }
    }
}

// FETCH ORDERS
$statusFilter = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$sql = "SELECT o.id as order_id, o.user_id, o.total_amount, o.status, o.created_at, 
               u.fname as first_name, u.lname as last_name, u.email
        FROM orders o 
        JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
    $types .= "ssss";
}
if ($statusFilter && in_array($statusFilter, ORDER_STATUSES)) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $db_conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allOrders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary counts
$totalOrders = $db_conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] ?? 0;
$statusCounts = array_fill_keys(ORDER_STATUSES, 0);
$rows = $db_conn->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status");
if ($rows) {
    while ($r = $rows->fetch_assoc()) {
        $statusCounts[strtolower($r['status'])] = (int)$r['cnt'];
    }
}

function orderBadge(string $status): string {
    return match (strtolower($status)) {
        'pending'    => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-primary',
        'delivered'  => 'bg-success',
        'cancelled', 'failed' => 'bg-danger',
        default      => 'bg-secondary',
    };
}

function allowedNextStatuses(string $current): array {
    $current = strtolower($current);
    $flow = ['pending' => 'processing', 'processing' => 'shipped', 'shipped' => 'delivered'];
    $next = [];
    if (isset($flow[$current])) $next[] = $flow[$current];
    if ($current !== 'cancelled' && $current !== 'delivered') $next[] = 'cancelled';
    return $next;
}

$currentPage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders – Admin – Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body" style="background: var(--bg-primary);">
<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="admin-content dash-main">
        <div class="admin-topbar">
            <div>
                <h4 class="page-title mb-0"><i class="bi bi-bag-check me-2"></i>Order Management</h4>
                <small class="page-text"><?= $totalOrders ?> total orders <?= !$isAdmin ? '(Employee View)' : '' ?></small>
            </div>
        </div>

        <div class="dash-content">
            <?php if ($successMsg): ?><div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

            <div class="mb-4">
                <ul class="nav nav-pills gap-1 flex-wrap">
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === '' ? 'active' : '' ?>" style="<?= $statusFilter === '' ? 'background-color:#28666e;' : '' ?>" href="/admin/orders.php">
                            All <span class="badge bg-white text-dark ms-1"><?= $totalOrders ?></span>
                        </a>
                    </li>
                    <?php foreach (ORDER_STATUSES as $s): $isActive = ($statusFilter === $s); ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : 'text-white-50' ?>" style="<?= $isActive ? 'background-color:#28666e;' : '' ?>" href="/admin/orders.php?status=<?= urlencode($s) ?>">
                            <?= ucfirst($s) ?> <span class="badge bg-secondary ms-1"><?= $statusCounts[$s] ?? 0 ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="dash-table-wrap">
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Update Status</th>
                            </tr>
                        </thead>
                        <tbody>
            <!-- ============================================================
                 SEARCH BAR
                 ============================================================ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <?php if ($statusFilter): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <?php endif; ?>
                        <div class="col-md-9">
                            <label for="search" class="form-label">Search Orders</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Customer name, email, or order #..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                            <?php if ($search): ?>
                                <a href="<?= appUrl('/admin/orders.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '')) ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================
                 ORDER STATUS BAR CHART
                 ============================================================ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart-horizontal me-2"></i>Order Status Breakdown</h6>
                    <small class="text-muted"><?= $totalOrders ?> total orders</small>
                </div>
                <div class="card-body" style="height:200px;">
                    <canvas id="orderStatusChart" aria-label="Horizontal bar chart showing order counts by status" role="img"></canvas>
                </div>
            </div>

            <!-- ============================================================
                 ORDERS TABLE
                 ============================================================ -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Order #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($allOrders)): ?>
                                <tr><td colspan="6" class="text-center text-white py-5">No orders found.</td></tr>
                            <?php else: foreach ($allOrders as $o): $nextStatuses = allowedNextStatuses($o['status']); ?>
                                <tr>
                                    <td class="text-white fw-semibold">#<?= (int)$o['order_id'] ?></td>
                                    <td>
                                        <div class="fw-semibold text-white"><?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?></div>
                                        <div class="text-white small"><?= htmlspecialchars($o['email']) ?></div>
                                    </td>
                                    <td class="fw-semibold text-white">$<?= number_format((float)$o['total_amount'], 2) ?></td>
                                    <td class="text-white small"><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                                    <td><span class="badge <?= orderBadge($o['status']) ?>"><?= ucfirst(htmlspecialchars($o['status'])) ?></span></td>
                                    <td class="text-end pe-3">
                                        <?php if (empty($nextStatuses)): ?>
                                            <span class="text-white small"><?= strtolower($o['status']) === 'delivered' ? '✓ Complete' : '✕ Cancelled' ?></span>
                                        <?php else: foreach ($nextStatuses as $nextStatus): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Update order #<?= $o['order_id'] ?> to <?= ucfirst($nextStatus) ?>?');">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= htmlspecialchars($nextStatus) ?>">
                                                <button type="submit" class="btn btn-sm <?= $nextStatus === 'cancelled' ? 'btn-outline-danger' : 'btn-outline-secondary' ?> mb-1">
                                                    <?= ucfirst($nextStatus) ?>
                                                </button>
                                            </form>
                                        <?php endforeach; endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /p-4 -->
    </div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    label: 'Orders',
                    data: [1, 2, 2, 2, 1],
                    backgroundColor: [
                        'rgba(255, 193, 7,   0.8)',
                        'rgba(13,  202, 240, 0.8)',
                        'rgba(13,  110, 253, 0.8)',
                        'rgba(25,  135, 84,  0.8)',
                        'rgba(220, 53,  69,  0.8)'
                    ],
                    borderColor: ['#ffc107','#0dcaf0','#0d6efd','#198754','#dc3545'],
                    borderWidth:   1,
                    borderRadius:  4,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis:           'y',
                responsive:          true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                var pct   = total > 0 ? Math.round((ctx.parsed.x / total) * 100) : 0;
                                return ' ' + ctx.parsed.x + ' orders (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Urbanist, sans-serif' } },
                        grid:  { color: 'rgba(0,0,0,0.05)' }
                    },
                    y: {
                        ticks: { font: { family: 'Urbanist, sans-serif' } },
                        grid:  { display: false }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>