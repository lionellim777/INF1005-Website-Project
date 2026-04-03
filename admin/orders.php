<?php
/**
 * Admin – Order Management
 * View all orders and update their statuses.
 * Access: employees and admins (role 'admin' or 'employee').
 * Security: MySQLi prepared statements, CSRF, output escaping.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Allow both admin and employee
require_role(['admin', 'employee']);

// ---------------------------------------------------------------------------
// Simple flash messaging (avoids obsolete renderFlash)
// ---------------------------------------------------------------------------
function setOrderFlash(string $type, string $message): void {
    $_SESSION['order_flash'] = ['type' => $type, 'message' => $message];
}
function getOrderFlash(): ?array {
    $flash = $_SESSION['order_flash'] ?? null;
    unset($_SESSION['order_flash']);
    return $flash;
}

// ---------------------------------------------------------------------------
// Database connection (global MySQLi object from bootstrap)
// ---------------------------------------------------------------------------
global $db_conn;
if (!$db_conn || $db_conn->connect_error) {
    die('Database connection failed.');
}

// Current admin/employee info (for logging, optional)
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['role'] ?? '';

// Valid statuses
const ORDER_STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

// ---------------------------------------------------------------------------
// POST: Update order status
// ---------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf(); // from auth_middleware

    $action    = $_POST['action'] ?? '';
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['new_status'] ?? '');

    if ($action === 'update_status') {
        $errors = [];
        if ($orderId <= 0) $errors[] = 'Invalid order.';
        if (!in_array($newStatus, ORDER_STATUSES, true)) $errors[] = 'Invalid status.';

        if ($errors) {
            setOrderFlash('error', implode(' ', $errors));
            header('Location: ' . app_url('/admin/orders.php'));
            exit;
        }

        // Fetch current status
        $stmt = $db_conn->prepare('SELECT status FROM orders WHERE id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            setOrderFlash('error', 'Order not found.');
            header('Location: ' . app_url('/admin/orders.php'));
            exit;
        }

        $oldStatus = $row['status'];

        // Update status
        $updateStmt = $db_conn->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $updateStmt->bind_param('si', $newStatus, $orderId);
        if ($updateStmt->execute()) {
            // Regenerate CSRF token after successful update
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            setOrderFlash('success', "Order #{$orderId} updated to \"" . ucfirst($newStatus) . "\".");
        } else {
            setOrderFlash('error', 'Database error: ' . $db_conn->error);
        }
        $updateStmt->close();

        header('Location: ' . app_url('/admin/orders.php'));
        exit;
    }
}

// ---------------------------------------------------------------------------
// GET: Fetch orders with filters
// ---------------------------------------------------------------------------
$statusFilter = $_GET['status'] ?? '';
$search       = trim($_GET['search'] ?? '');

// Base query: orders + user info + concatenated product names
$sql = "SELECT o.id AS order_id, o.user_id, o.total_amount, o.status, o.created_at,
               u.fname, u.lname, u.email,
               GROUP_CONCAT(oi.product_name ORDER BY oi.id SEPARATOR ', ') AS items
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (u.fname LIKE ? OR u.lname LIKE ? OR u.email LIKE ? OR o.id LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
    $types .= 'ssss';
}
if ($statusFilter !== '' && in_array($statusFilter, ORDER_STATUSES, true)) {
    $sql .= " AND o.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $db_conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$allOrders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary counts for status tabs
$statusCounts = array_fill_keys(ORDER_STATUSES, 0);
$countSql = "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status";
$countResult = $db_conn->query($countSql);
while ($row = $countResult->fetch_assoc()) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['cnt'];
    }
}
$totalOrders = array_sum($statusCounts);

// Helper: badge class
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

// Allowed next statuses (same linear flow + cancellation)
function allowedNextStatuses(string $current): array {
    $flow = ['pending' => 'processing', 'processing' => 'shipped', 'shipped' => 'delivered'];
    $next = [];
    if (isset($flow[$current])) $next[] = $flow[$current];
    if ($current !== 'cancelled' && $current !== 'delivered') $next[] = 'cancelled';
    return $next;
}

$currentPage = 'orders';
$pageTitle   = 'Orders – Pomegranate';
$flash = getOrderFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('/admin/css/admin.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-bag-check me-2"></i>Order Management</h4>
                <small class="text-muted">
                    <?= $totalOrders ?> total order<?= $totalOrders !== 1 ? 's' : '' ?>
                    <?php if ($currentUserRole !== 'admin'): ?>
                        &nbsp;·&nbsp;<span class="badge bg-warning text-dark">Employee view</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="p-4">
            <!-- Flash messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= h($flash['type'] === 'success' ? 'success' : 'danger') ?> alert-dismissible fade show" role="alert">
                    <?= h($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Status filter tabs -->
            <div class="mb-4">
                <ul class="nav nav-pills gap-1 flex-wrap">
                    <li class="nav-item">
                        <a class="nav-link <?= $statusFilter === '' ? 'active' : '' ?>"
                           style="<?= $statusFilter === '' ? 'background-color:#28666e;' : '' ?>"
                           href="<?= app_url('/admin/orders.php') ?>">
                            All
                            <span class="badge bg-white text-dark ms-1"><?= $totalOrders ?></span>
                        </a>
                    </li>
                    <?php foreach (ORDER_STATUSES as $s):
                        $isActive = $statusFilter === $s;
                        $cnt = $statusCounts[$s] ?? 0;
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $isActive ? 'active' : 'text-dark' ?>"
                           style="<?= $isActive ? 'background-color:#28666e;' : '' ?>"
                           href="<?= app_url('/admin/orders.php?status=' . urlencode($s)) ?>">
                            <?= ucfirst($s) ?>
                            <span class="badge bg-secondary ms-1"><?= $cnt ?></span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Search bar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <?php if ($statusFilter): ?>
                            <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
                        <?php endif; ?>
                        <div class="col-md-9">
                            <label for="search" class="form-label">Search Orders</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Customer name, email, or order #..."
                                   value="<?= h($search) ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                            <?php if ($search): ?>
                                <a href="<?= app_url('/admin/orders.php' . ($statusFilter ? '?status=' . urlencode($statusFilter) : '')) ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Horizontal bar chart (dynamic data) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center pt-3">
                    <h6 class="fw-semibold mb-0"><i class="bi bi-bar-chart-horizontal me-2"></i>Order Status Breakdown</h6>
                    <small class="text-muted"><?= $totalOrders ?> total orders</small>
                </div>
                <div class="card-body" style="height:200px;">
                    <canvas id="orderStatusChart" aria-label="Horizontal bar chart showing order counts by status" role="img"></canvas>
                </div>
            </div>

            <!-- Orders table -->
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
                                            <a href="<?= app_url('/admin/orders.php') ?>">Clear filters</a>
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
                                            <?= h($o['fname'] . ' ' . $o['lname']) ?>
                                        </div>
                                        <div class="text-muted small"><?= h($o['email']) ?></div>
                                    </td>
                                    <td class="small text-muted" style="max-width:180px;">
                                        <span class="text-truncate d-block" title="<?= h($o['items'] ?? '') ?>">
                                            <?= h($o['items'] ?? '—') ?>
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
                                            <?= ucfirst(h($o['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <?php if (empty($nextStatuses)): ?>
                                            <span class="text-muted small">
                                                <?= $o['status'] === 'delivered' ? '✓ Complete' : '✕ Cancelled' ?>
                                            </span>
                                        <?php else: ?>
                                            <?php foreach ($nextStatuses as $nextStatus): ?>
                                                <form method="POST" action="" class="d-inline"
                                                      onsubmit="return confirm('Mark order #<?= (int)$o['order_id'] ?> as \'<?= ucfirst($nextStatus) ?>\'?');">
                                                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="order_id" value="<?= (int)$o['order_id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= h($nextStatus) ?>">
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dynamic chart data from PHP
    const statuses = <?= json_encode(array_map('ucfirst', ORDER_STATUSES)) ?>;
    const counts   = <?= json_encode(array_values($statusCounts)) ?>;
    const colors   = [
        'rgba(255, 193, 7, 0.8)',   // pending
        'rgba(13, 202, 240, 0.8)',  // processing
        'rgba(13, 110, 253, 0.8)',  // shipped
        'rgba(25, 135, 84, 0.8)',   // delivered
        'rgba(220, 53, 69, 0.8)'    // cancelled
    ];
    const borders = ['#ffc107','#0dcaf0','#0d6efd','#198754','#dc3545'];

    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: statuses,
            datasets: [{
                label: 'Orders',
                data: counts,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 1,
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            let total = ctx.dataset.data.reduce((a,b) => a + b, 0);
                            let pct = total > 0 ? Math.round((ctx.parsed.x / total) * 100) : 0;
                            return ' ' + ctx.parsed.x + ' orders (' + pct + '%)';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, font: { family: 'Urbanist, sans-serif' } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: {
                    ticks: { font: { family: 'Urbanist, sans-serif' } },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
</body>
</html>