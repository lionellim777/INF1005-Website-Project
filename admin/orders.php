<?php
require_once "../inc/auth.inc.php";
requireAdmin();
<<<<<<< Updated upstream
header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;
=======

$msg = $err = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $pdo = getDB();
        if ($action === 'update_status') {
            $id     = (int)($_POST['order_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $allowed = ['pending','processing','shipped','delivered','cancelled'];
            if ($id > 0 && in_array($status, $allowed, true)) {
                $pdo->prepare("UPDATE orders SET status=?, updated_at=NOW() WHERE id=?")->execute([$status, $id]);
                $msg = 'Order #'.$id.' status updated to '.ucfirst($status).'.';
            } else {
                $err = 'Invalid status or order ID.';
            }
        }
    } catch (Exception $e) {
        $err = 'Database error.';
    }
    $qs = $msg ? '?msg='.urlencode($msg) : '?err='.urlencode($err);
    header('Location: orders.php'.$qs);
    exit;
}

$orders       = [];
$statusCounts = [];
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['q'] ?? '');

try {
    $pdo = getDB();

    $sql    = "SELECT o.id, u.username, u.full_name, u.email, o.total, o.status, o.shipping_address, o.notes, o.created_at, o.updated_at FROM orders o JOIN users u ON u.id=o.user_id WHERE 1=1";
    $params = [];

    if ($statusFilter !== 'all') {
        $sql .= " AND o.status = ?";
        $params[] = $statusFilter;
    }
    if ($search) {
        $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR CAST(o.id AS CHAR) LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY o.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $rows = $pdo->query("SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status")->fetchAll();
    foreach ($rows as $r) $statusCounts[$r['status']] = $r['cnt'];

} catch (Exception $e) {
    $orders = [
        ['id'=>1,'username'=>'johndoe','full_name'=>'John Doe','email'=>'john@example.com','total'=>1348.00,'status'=>'delivered','shipping_address'=>'123 Main St','notes'=>'','created_at'=>'2025-03-28 14:22:00','updated_at'=>'2025-03-30 10:00:00'],
        ['id'=>2,'username'=>'johndoe','full_name'=>'John Doe','email'=>'john@example.com','total'=>599.00, 'status'=>'shipped',  'shipping_address'=>'123 Main St','notes'=>'','created_at'=>'2025-03-27 09:11:00','updated_at'=>'2025-03-29 08:00:00'],
        ['id'=>3,'username'=>'janedoe','full_name'=>'Jane Doe','email'=>'jane@example.com','total'=>2498.00,'status'=>'processing','shipping_address'=>'456 Oak Ave','notes'=>'Leave at door','created_at'=>'2025-03-26 17:44:00','updated_at'=>'2025-03-27 09:00:00'],
        ['id'=>4,'username'=>'janedoe','full_name'=>'Jane Doe','email'=>'jane@example.com','total'=>299.00, 'status'=>'pending',  'shipping_address'=>'456 Oak Ave','notes'=>'','created_at'=>'2025-03-25 11:00:00','updated_at'=>'2025-03-25 11:00:00'],
        ['id'=>5,'username'=>'johndoe','full_name'=>'John Doe','email'=>'john@example.com','total'=>89.00,  'status'=>'delivered','shipping_address'=>'123 Main St','notes'=>'','created_at'=>'2025-03-24 08:33:00','updated_at'=>'2025-03-26 12:00:00'],
    ];
    $statusCounts = ['pending'=>1,'processing'=>1,'shipped'=>1,'delivered'=>2];
}

$statuses = ['all','pending','processing','shipped','delivered','cancelled'];
$totalAll = array_sum($statusCounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – Admin – Pomegranate</title>
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
            <a href="orders.php"   class="sidebar-link active"><i class="bi bi-receipt"></i> Orders</a>
            <a href="products.php" class="sidebar-link"><i class="bi bi-box-seam"></i> Products</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link"><i class="bi bi-bar-chart-line"></i> Analytics</a>
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
                <span class="page-title">Orders</span>
            </div>
            <span class="text-white-50 small d-none d-md-inline"><?= date('D, d M Y') ?></span>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Order Management</h2>
                <p class="text-white-50 small mb-0">View and update the status of all customer orders.</p>
            </div>

            <?php if ($msg): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($msg) ?>
            </div>
            <?php elseif ($err): ?>
            <div class="alert-error-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i><?= h($err) ?>
            </div>
            <?php endif; ?>

            <!-- Status tabs -->
            <div class="d-flex flex-wrap gap-2 mb-4">
                <?php foreach ($statuses as $s): ?>
                <?php $count = $s === 'all' ? $totalAll : ($statusCounts[$s] ?? 0); ?>
                <a href="orders.php?status=<?= $s ?>&q=<?= urlencode($search) ?>"
                   class="btn btn-sm <?= $statusFilter===$s ? 'btn-dash-primary' : 'btn-dash-secondary' ?>"
                   style="font-size:.78rem;">
                    <?= ucfirst($s) ?>
                    <?php if ($count > 0): ?>
                    <span class="ms-1 px-1 rounded-1" style="background:rgba(255,255,255,.1);font-size:.72rem;"><?= $count ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title">
                        <?= $statusFilter === 'all' ? 'All Orders' : ucfirst($statusFilter).' Orders' ?>
                        <span class="text-white-50 fw-normal ms-1" style="font-size:.8rem;">(<?= count($orders) ?>)</span>
                    </span>
                    <form method="GET" action="orders.php" class="d-flex gap-2 align-items-center">
                        <input type="hidden" name="status" value="<?= h($statusFilter) ?>">
                        <div class="search-bar-wrap">
                            <i class="bi bi-search"></i>
                            <input type="text" name="q" class="form-control-dark"
                                   placeholder="Search orders…"
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
                                <th>#</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Placed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center text-white-50 py-4">No orders found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="text-white fw-semibold">#<?= $o['id'] ?></td>
                                <td>
                                    <div class="fw-semibold text-white small"><?= h($o['full_name'] ?: $o['username']) ?></div>
                                    <div class="text-white-50" style="font-size:.72rem;"><?= h($o['email']) ?></div>
                                </td>
                                <td class="text-white fw-semibold">$<?= number_format($o['total'], 2) ?></td>
                                <td><span class="status-badge status-<?= h($o['status']) ?>"><?= ucfirst(h($o['status'])) ?></span></td>
                                <td class="text-white-50" style="font-size:.8rem;"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-1 align-items-center">
                                        <form method="POST" action="orders.php" class="d-flex gap-1 align-items-center">
                                            <input type="hidden" name="action"   value="update_status">
                                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                            <select name="status" class="form-control-dark"
                                                    style="width:auto;font-size:.75rem;padding:.2rem .5rem;">
                                                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                                <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-icon" title="Update status">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="btn-icon" title="View details"
                                                onclick="showOrderDetails(<?= htmlspecialchars(json_encode($o), ENT_QUOTES) ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
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

<!-- Order detail modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.08);">
                <h5 class="modal-title text-white fw-bold" id="orderDetailTitle">Order Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/dashboard.js"></script>
<script>
function showOrderDetails(o) {
    document.getElementById('orderDetailTitle').textContent = 'Order #' + o.id;
    document.getElementById('orderDetailBody').innerHTML = `
        <div class="d-flex flex-column gap-3">
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Customer</span>
                <span class="text-white fw-semibold small">${o.full_name || o.username}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Email</span>
                <span class="text-white-50 small">${o.email}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Total</span>
                <span class="text-white fw-bold">$${parseFloat(o.total).toFixed(2)}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Status</span>
                <span class="status-badge status-${o.status}">${o.status.charAt(0).toUpperCase()+o.status.slice(1)}</span>
            </div>
            ${o.shipping_address ? `<div class="d-flex justify-content-between">
                <span class="text-white-50 small">Shipping</span>
                <span class="text-white small text-end" style="max-width:60%;">${o.shipping_address}</span>
            </div>` : ''}
            ${o.notes ? `<div class="d-flex justify-content-between">
                <span class="text-white-50 small">Notes</span>
                <span class="text-white-50 small text-end" style="max-width:60%;">${o.notes}</span>
            </div>` : ''}
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Placed</span>
                <span class="text-white-50 small">${o.created_at}</span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-white-50 small">Updated</span>
                <span class="text-white-50 small">${o.updated_at}</span>
            </div>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
}
</script>
</body>
</html>
>>>>>>> Stashed changes
