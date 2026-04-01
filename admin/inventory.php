<?php
/**
 * Admin – Inventory Management
 * Access: admin only.
 */
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

require_role(['admin', 'employee']);

$successMsg = '';
$errorMsg = '';

// POST: UPDATE STOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_stock') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $newStock  = (int)($_POST['stock_quantity'] ?? 0);

    if ($productId > 0 && $newStock >= 0) {
        $stmt = $db_conn->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
        $stmt->bind_param("ii", $newStock, $productId);
        if ($stmt->execute()) {
            $successMsg = "Stock updated successfully.";
        } else {
            $errorMsg = "Failed to update stock.";
        }
    } else {
        $errorMsg = "Invalid stock amount.";
    }
}

// FETCH PRODUCTS
$statusFilter = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

$sql = 'SELECT id, name, category, stock_quantity, price, image_url FROM products WHERE 1=1';
$params = [];
$types = "";

if ($search) {
    $sql .= ' AND (name LIKE ? OR category LIKE ?)';
    $like = "%{$search}%";
    $params = [$like, $like];
    $types .= "ss";
}
if ($statusFilter === 'out') {
    $sql .= ' AND stock_quantity = 0';
} elseif ($statusFilter === 'low') {
    $sql .= ' AND stock_quantity > 0 AND stock_quantity < 5';
}
$sql .= ' ORDER BY stock_quantity ASC, name ASC';

$stmt = $db_conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Summary counts
$totalItems   = $db_conn->query('SELECT COUNT(*) as c FROM products')->fetch_assoc()['c'] ?? 0;
$outOfStock   = $db_conn->query('SELECT COUNT(*) as c FROM products WHERE stock_quantity = 0')->fetch_assoc()['c'] ?? 0;
$lowStock     = $db_conn->query('SELECT COUNT(*) as c FROM products WHERE stock_quantity > 0 AND stock_quantity < 5')->fetch_assoc()['c'] ?? 0;
$healthyStock = $totalItems - $outOfStock - $lowStock;

function stockStatus(int $qty): array {
    if ($qty === 0)  return ['label' => 'Out of stock', 'class' => 'bg-danger'];
    if ($qty < 5)    return ['label' => 'Low stock',    'class' => 'bg-warning text-dark'];
    return               ['label' => 'In stock',       'class' => 'bg-success'];
}

$currentPage = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory – Admin – Pomegranate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-body" style="background: var(--bg-primary);">
<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>
    <div class="admin-content dash-main">
        <div class="admin-topbar">
            <div>
                <h4 class="page-title mb-0"><i class="bi bi-boxes me-2"></i>Inventory Management</h4>
                <small class="page-text">Monitor and update product stock levels</small>
            </div>
        </div>

        <div class="dash-content">
            <?php if ($successMsg): ?><div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <a href="/admin/inventory.php" class="text-decoration-none">
                        <div class="stat-card green h-100 <?= $statusFilter === '' ? 'border-success' : '' ?>">
                            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                            <div>
                                <div class="stat-card-label">Healthy Stock</div>
                                <div class="stat-card-value"><?= $healthyStock ?></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="/admin/inventory.php?status=low" class="text-decoration-none">
                        <div class="stat-card amber h-100 <?= $statusFilter === 'low' ? 'border-warning' : '' ?>">
                            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                            <div>
                                <div class="stat-card-label">Low Stock (under 5)</div>
                                <div class="stat-card-value"><?= $lowStock ?></div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="/admin/inventory.php?status=out" class="text-decoration-none">
                        <div class="stat-card red h-100 <?= $statusFilter === 'out' ? 'border-danger' : '' ?>">
                            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                            <div>
                                <div class="stat-card-label">Out of Stock</div>
                                <div class="stat-card-value"><?= $outOfStock ?></div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="dash-table-wrap shadow-sm">
                <div class="dash-table-header">
                    <h6 class="dash-table-title mb-0">Products (<?= count($allProducts) ?>)</h6>
                </div>
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Status</th>
                                <th>Stock Level</th>
                                <th class="text-end">Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allProducts as $p): $qty = (int)$p['stock_quantity']; $status = stockStatus($qty); ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-white small"><?= htmlspecialchars($p['category']) ?></div>
                                </td>
                                <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2" style="max-width: 150px;">
                                        <div class="progress flex-grow-1" style="height:6px; background: rgba(255,255,255,0.1);">
                                            <div class="progress-bar <?= $qty === 0 ? 'bg-danger' : ($qty < 5 ? 'bg-warning' : 'bg-success') ?>" style="width:<?= min(100, ($qty / 50) * 100) ?>%"></div>
                                        </div>
                                        <span class="text-white small"><?= $qty ?></span>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex justify-content-end gap-1">
                                        <input type="hidden" name="action" value="update_stock">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustStock(this, -1)"><i class="bi bi-dash"></i></button>
                                        <input type="number" class="form-control form-control-sm text-center stock-input bg-dark text-white border-secondary" name="stock_quantity" value="<?= $qty ?>" min="0" style="width:60px;">
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-2" onclick="adjustStock(this, 1)"><i class="bi bi-plus"></i></button>
                                        <button type="submit" class="btn-dash-primary ms-1"><i class="bi bi-check-lg"></i></button>
                                    </form>
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
<script>
function adjustStock(btn, delta) {
    const form  = btn.closest('form');
    const input = form.querySelector('.stock-input');
    if (input) input.value = Math.max(0, (parseInt(input.value) || 0) + delta);
}
</script>
</body>
</html>