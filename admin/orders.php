<?php
/**
 * Admin – Order Management (Phase 4)
 * View all orders and update their statuses.
 * Access: role_id >= 3 (employee AND admin).
 *   - Employees: can update order status only.
 *   - Admins: same access here, plus full dashboard.
 *
 * Security: PDO prepared statements, CSRF, htmlspecialchars output escaping.
 */

require_once __DIR__ . '/../auth/auth_helper.php';
initSession();
requireRole(3);  // Employee (3) and Admin (4) both allowed

$currentSessionUser = currentUser();
$isAdmin            = (int)$currentSessionUser['role_id'] >= 4;
$isOffline          = isOfflineMode();
$pdo                = $isOffline ? null : getDBConnection();

// Valid statuses — matches the ENUM in the orders table
const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// Offline demo orders (keyed by order_id)
if ($isOffline && !isset($_SESSION['offline_orders'])) {
    $_SESSION['offline_orders'] = [
        1001 => ['order_id'=>1001,'user_id'=>3,'first_name'=>'John', 'last_name'=>'Doe',      'email'=>'user@pomegranate.com',     'total_amount'=>1299.99,'status'=>'processing','created_at'=>'2025-03-10 09:00:00','items'=>'Pomegranate Argus'],
        1002 => ['order_id'=>1002,'user_id'=>3,'first_name'=>'John', 'last_name'=>'Doe',      'email'=>'user@pomegranate.com',     'total_amount'=>849.00, 'status'=>'shipped',   'created_at'=>'2025-02-20 14:15:00','items'=>'Pomegranate Guinevere'],
        1003 => ['order_id'=>1003,'user_id'=>6,'first_name'=>'Test', 'last_name'=>'User One', 'email'=>'qa.user1@pomegranate.com', 'total_amount'=>1299.99,'status'=>'shipped',   'created_at'=>'2025-03-05 16:45:00','items'=>'Pomegranate Argus'],
        1004 => ['order_id'=>1004,'user_id'=>3,'first_name'=>'John', 'last_name'=>'Doe',      'email'=>'user@pomegranate.com',     'total_amount'=>1299.99,'status'=>'delivered', 'created_at'=>'2025-01-15 10:30:00','items'=>'Pomegranate Argus'],
        1005 => ['order_id'=>1005,'user_id'=>7,'first_name'=>'Test', 'last_name'=>'User Two', 'email'=>'qa.user2@pomegranate.com', 'total_amount'=>549.00, 'status'=>'delivered', 'created_at'=>'2025-02-01 11:00:00','items'=>'Pomegranate Watch SE'],
        1006 => ['order_id'=>1006,'user_id'=>6,'first_name'=>'Test', 'last_name'=>'User One', 'email'=>'qa.user1@pomegranate.com', 'total_amount'=>199.99, 'status'=>'pending',   'created_at'=>'2025-03-12 08:30:00','items'=>'Pomegranate Wireless Buds'],
        1007 => ['order_id'=>1007,'user_id'=>7,'first_name'=>'Test', 'last_name'=>'User Two', 'email'=>'qa.user2@pomegranate.com', 'total_amount'=>399.00, 'status'=>'cancelled', 'created_at'=>'2025-03-01 11:20:00','items'=>'Pomegranate Sora'],
    ];
}

// ============================================================
// POST: UPDATE ORDER STATUS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . appUrl('/admin/orders.php'));
        exit;
    }

    $action    = sanitize($_POST['action'] ?? '');
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = sanitize($_POST['new_status'] ?? '');

    if ($action === 'update_status') {

        $errors = [];
        if ($orderId <= 0)                           $errors[] = 'Invalid order.';
        if (!in_array($newStatus, ORDER_STATUSES, true)) $errors[] = 'Invalid status.';

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . appUrl('/admin/orders.php'));
            exit;
        }

        if ($isOffline) {
            if (!isset($_SESSION['offline_orders'][$orderId])) {
                setFlash('error', 'Order not found.');
                header('Location: ' . appUrl('/admin/orders.php'));
                exit;
            }
            $oldStatus = $_SESSION['offline_orders'][$orderId]['status'];
            $_SESSION['offline_orders'][$orderId]['status'] = $newStatus;
        } else {
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE order_id = :id');
            $stmt->execute([':id' => $orderId]);
            $row = $stmt->fetch();

            if (!$row) {
                setFlash('error', 'Order not found.');
                header('Location: ' . appUrl('/admin/orders.php'));
                exit;
            }

            $oldStatus = $row['status'];
            $pdo->prepare('UPDATE orders SET status = :status WHERE order_id = :id')
                ->execute([':status' => $newStatus, ':id' => $orderId]);
        }

        logActivity(
            (int)$currentSessionUser['user_id'],
            'admin_update_order_status',
            "Order #{$orderId} status: {$oldStatus} → {$newStatus}"
        );
        setFlash('success', "Order #{$orderId} updated to \"" . ucfirst($newStatus) . "\".");
        header('Location: ' . appUrl('/admin/orders.php'));
        exit;
    }
}

// ============================================================
// FETCH ORDERS FOR DISPLAY
// ============================================================
$statusFilter = sanitize($_GET['status'] ?? '');
$search       = sanitize($_GET['search'] ?? '');

if ($isOffline) {
    $allOrders = array_values($_SESSION['offline_orders']);

    // Sort newest first
    usort($allOrders, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

    if ($search) {
        $needle = strtolower($search);
        $allOrders = array_values(array_filter($allOrders, function($o) use ($needle) {
            $name = strtolower($o['first_name'] . ' ' . $o['last_name']);
            return str_contains($name, $needle)
                || str_contains(strtolower($o['email']), $needle)
                || str_contains((string)$o['order_id'], $needle);
        }));
    }

    if ($statusFilter && in_array($statusFilter, ORDER_STATUSES, true)) {
        $allOrders = array_values(array_filter($allOrders, fn($o) => $o['status'] === $statusFilter));
    }

    // Summary counts from full unfiltered set
    $allRaw = array_values($_SESSION['offline_orders']);
    $statusCounts = array_fill_keys(ORDER_STATUSES, 0);
    foreach ($allRaw as $o) {
        $statusCounts[$o['status']] = ($statusCounts[$o['status']] ?? 0) + 1;
    }
    $totalOrders = count($allRaw);

} else {
    // Build query with optional filters
    $sql    = 'SELECT o.order_id, o.user_id, o.total_amount, o.status, o.created_at,
                      u.first_name, u.last_name, u.email,
                      GROUP_CONCAT(oi.product_name ORDER BY oi.item_id SEPARATOR ", ") AS items
               FROM orders o
               JOIN users u ON o.user_id = u.user_id
               LEFT JOIN order_items oi ON o.order_id = oi.order_id
               WHERE 1=1';
    $params = [];

    if ($search) {
        $sql .= ' AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2
                       OR u.email LIKE :s3 OR o.order_id LIKE :s4)';
        $like = "%{$search}%";
        $params[':s1'] = $like; $params[':s2'] = $like;
        $params[':s3'] = $like; $params[':s4'] = $like;
    }

    if ($statusFilter && in_array($statusFilter, ORDER_STATUSES, true)) {
        $sql .= ' AND o.status = :status';
        $params[':status'] = $statusFilter;
    }

    $sql .= ' GROUP BY o.order_id ORDER BY o.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allOrders = $stmt->fetchAll();

    // Summary counts
    $totalOrders  = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $statusCounts = array_fill_keys(ORDER_STATUSES, 0);
    $rows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
    foreach ($rows as $r) {
        $statusCounts[$r['status']] = (int)$r['cnt'];
    }
}

// ============================================================
// HELPERS
// ============================================================
function orderBadge(string $status): string {
    return match ($status) {
        'pending'    => 'bg-warning text-dark',
        'processing' => 'bg-info text-dark',
        'shipped'    => 'bg-primary',
        'delivered'  => 'bg-success',
        'cancelled'  => 'bg-danger',
        default      => 'bg-secondary',
    };
}

// What statuses can this order be moved to from its current one?
// Employees and admins follow the same linear flow; cancellation is always available.
function allowedNextStatuses(string $current): array {
    $flow = ['pending' => 'processing', 'processing' => 'shipped', 'shipped' => 'delivered'];
    $next = [];
    if (isset($flow[$current])) $next[] = $flow[$current];
    if ($current !== 'cancelled' && $current !== 'delivered') $next[] = 'cancelled';
    return $next;
}

$currentPage = 'orders';
$pageTitle   = 'Orders – Admin – ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= appUrl('/admin/css/admin.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">

        <!-- Top bar -->
        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-bag-check me-2"></i>Order Management</h4>
                <small class="text-muted">
                    <?= $totalOrders ?> total order<?= $totalOrders !== 1 ? 's' : '' ?>
                    <?php if (!$isAdmin): ?>
                        &nbsp;·&nbsp;<span class="badge bg-warning text-dark">Employee view</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="p-4">
            <div class="admin-flash">
                    <?= renderFlash() ?>
                </div>

            <?php if ($isOffline): ?>
                <div class="alert alert-info small">
                    <i class="bi bi-wifi-off me-1"></i>Offline demo mode — changes are stored in session only.
                </div>
            <?php endif; ?>

            <!-- ============================================================
                 STATUS FILTER TABS
                 ============================================================ -->
            <div class="mb-4">
                <ul class="nav nav-pills gap-1 flex-wrap">
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === '' ? 'active' : '' ?>"
                           style="<?= $statusFilter === '' ? 'background-color:#28666e;' : '' ?>"
                           href="<?= appUrl('/admin/orders.php') ?>">
                            All
                            <span class="badge bg-white text-dark ms-1"><?= $totalOrders ?></span>
                        </a>
                    </li>
                    <?php foreach (ORDER_STATUSES as $s):
                        $isActive = $statusFilter === $s;
                        $cnt      = $statusCounts[$s] ?? 0;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : 'text-dark' ?>"
                           style="<?= $isActive ? 'background-color:#28666e;' : '' ?>"
                           href="<?= appUrl('/admin/orders.php?status=' . urlencode($s)) ?>">
                            <?= ucfirst($s) ?>
                            <span class="badge bg-secondary ms-1"><?= $cnt ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

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
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No orders found.
                                        <?php if ($search || $statusFilter): ?>
                                            <a href="<?= appUrl('/admin/orders.php') ?>">Clear filters</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allOrders as $o):
                                    $nextStatuses = allowedNextStatuses($o['status']);
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted small fw-semibold">
                                        #<?= (int)$o['order_id'] ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($o['first_name'] . ' ' . $o['last_name']) ?>
                                        </div>
                                        <div class="text-muted small"><?= htmlspecialchars($o['email']) ?></div>
                                    </td>
                                    <td class="small text-muted" style="max-width:180px;">
                                        <span class="text-truncate d-block" title="<?= htmlspecialchars($o['items'] ?? '') ?>">
                                            <?= htmlspecialchars($o['items'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold">
                                        $<?= number_format((float)$o['total_amount'], 2) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d M Y', strtotime($o['created_at'])) ?>
                                        <div><?= date('H:i', strtotime($o['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= orderBadge($o['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($o['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if (empty($nextStatuses)): ?>
                                            <!-- No further actions possible (delivered or cancelled) -->
                                            <span class="text-muted small">
                                                <?= $o['status'] === 'delivered' ? '✓ Complete' : '✕ Cancelled' ?>
                                            </span>
                                        <?php else: ?>
                                            <!--
                                                One button per valid next status.
                                                Each submits its own small inline form.
                                                confirm() prevents accidental clicks.
                                            -->
                                            <?php foreach ($nextStatuses as $nextStatus): ?>
                                                <form method="POST" action="" class="d-inline"
                                                      onsubmit="return confirm('Mark order #<?= (int)$o['order_id'] ?> as \'<?= ucfirst($nextStatus) ?>\'?');">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= htmlspecialchars($nextStatus) ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm <?= $nextStatus === 'cancelled' ? 'btn-outline-danger' : 'btn-outline-secondary' ?> mb-1">
                                                        <?php if ($nextStatus === 'processing'): ?>
                                                            <i class="bi bi-arrow-right me-1"></i>Processing
                                                        <?php elseif ($nextStatus === 'shipped'): ?>
                                                            <i class="bi bi-truck me-1"></i>Shipped
                                                        <?php elseif ($nextStatus === 'delivered'): ?>
                                                            <i class="bi bi-check-circle me-1"></i>Delivered
                                                        <?php elseif ($nextStatus === 'cancelled'): ?>
                                                            <i class="bi bi-x-circle me-1"></i>Cancel
                                                        <?php endif; ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                    data: [
                        <?= (int)($statusCounts['pending']    ?? 0) ?>,
                        <?= (int)($statusCounts['processing'] ?? 0) ?>,
                        <?= (int)($statusCounts['shipped']    ?? 0) ?>,
                        <?= (int)($statusCounts['delivered']  ?? 0) ?>,
                        <?= (int)($statusCounts['cancelled']  ?? 0) ?>
                    ],
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
