<?php
/**
 * Admin – Inventory Management
 * View stock levels for all products and update quantities.
 * Access: admin and employee.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Access: admin and employee
require_role(['admin', 'employee']);
global $session;

// ============================================================
// POST: UPDATE STOCK QUANTITY FOR ONE PRODUCT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $session->set_flashdata('error', 'Invalid request. Please try again.');
        header('Location: /admin/inventory.php');
        exit;
    }

    $action    = trim($_POST['action'] ?? '');
    $productId = (int)($_POST['product_id'] ?? 0);
    $newStock  = $_POST['stock_quantity'] ?? '';

    if ($action === 'update_stock') {

        // Validate inputs
        $errors = [];
        if ($productId <= 0) {
            $errors[] = 'Invalid product.';
        }
        if (!ctype_digit((string)(int)$newStock) || (int)$newStock < 0) {
            $errors[] = 'Stock must be a whole number, 0 or more.';
        }

        if ($errors) {
            $session->set_flashdata('error', implode(' ', $errors));
            header('Location: /admin/inventory.php');
            exit;
        }

        $stockVal = (int)$newStock;

        // Fetch current stock from live DB
        $stmt = $db_conn->prepare('SELECT name, stock FROM products WHERE id = ?');
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            $session->set_flashdata('error', 'Product not found.');
            header('Location: /admin/inventory.php');
            exit;
        }

        $oldStock    = (int)$row['stock'];
        $productName = $row['name'];

        // Update the database
        $upd = $db_conn->prepare('UPDATE products SET stock = ? WHERE id = ?');
        $upd->bind_param("ii", $stockVal, $productId);
        $upd->execute();
        $upd->close();

        $session->set_flashdata('success', "Stock updated for \"{$productName}\": {$oldStock} → {$stockVal}.");
        header('Location: /admin/inventory.php');
        exit;
    }
}

// ============================================================
// FETCH PRODUCTS FOR DISPLAY
// ============================================================
$statusFilter = htmlspecialchars($_GET['status'] ?? '');   // 'low' | 'out' | '' (all)
$search       = htmlspecialchars($_GET['search'] ?? '');

// Build query
// Using AS to map DB columns to the UI variables perfectly
$sql = 'SELECT id, name, category, stock AS stock_quantity, price, image_url FROM products WHERE 1=1';
$types = "";
$params = [];

if ($search) {
    $sql .= ' AND (name LIKE ? OR category LIKE ?)';
    $types .= "ss";
    $searchParam = "%{$search}%";
    $params[] = &$searchParam;
    $params[] = &$searchParam;
}
if ($statusFilter === 'out') {
    $sql .= ' AND stock = 0';
} elseif ($statusFilter === 'low') {
    $sql .= ' AND stock > 0 AND stock < 5';
}

$sql .= ' ORDER BY stock ASC, name ASC';

$stmt = $db_conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Summary counts (always from full table, ignoring filters)
$resTotal = $db_conn->query('SELECT COUNT(*) AS cnt FROM products');
$totalItems = $resTotal ? (int)$resTotal->fetch_assoc()['cnt'] : 0;

$resOut = $db_conn->query('SELECT COUNT(*) AS cnt FROM products WHERE stock = 0');
$outOfStock = $resOut ? (int)$resOut->fetch_assoc()['cnt'] : 0;

$resLow = $db_conn->query('SELECT COUNT(*) AS cnt FROM products WHERE stock > 0 AND stock < 5');
$lowStock = $resLow ? (int)$resLow->fetch_assoc()['cnt'] : 0;

$healthyStock = $totalItems - $outOfStock - $lowStock;

// Helper: stock status label + badge class
function stockStatus(int $qty): array {
    if ($qty === 0)  return ['label' => 'Out of stock', 'class' => 'bg-danger'];
    if ($qty < 5)    return ['label' => 'Low stock',    'class' => 'bg-warning text-dark'];
    return               ['label' => 'In stock',       'class' => 'bg-success'];
}

// Helper: Flash render wrapper
function renderFlash() {
    global $session;
    $html = '';
    if ($err = $_SESSION['error']) {
        $html .= '<div class="alert alert-danger shadow-sm border-0">'.htmlspecialchars($err).'</div>';
    }
    if ($suc = $_SESSION['success']) {
        $html .= '<div class="alert alert-success shadow-sm border-0">'.htmlspecialchars($suc).'</div>';
    }
    return $html;
}

$currentPage = 'inventory';
$pageTitle   = 'Inventory – Pomegranate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">

        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-boxes me-2"></i>Inventory Management</h4>
                <small class="text-muted">Monitor and update product stock levels</small>
            </div>
        </div>

        <div class="p-4">
            <div class="admin-flash">
                    <?= renderFlash() ?>
                </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <a href="/admin/inventory.php" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 <?= $statusFilter === '' ? 'stat-card-active' : '' ?>">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-check-circle fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Healthy Stock</div>
                                    <div class="fw-bold fs-4"><?= $healthyStock ?></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="/admin/inventory.php?status=low" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 <?= $statusFilter === 'low' ? 'stat-card-warning' : '' ?>">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="bi bi-exclamation-triangle fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Low Stock <span class="text-muted small">(under 5)</span></div>
                                    <div class="fw-bold fs-4"><?= $lowStock ?></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-4">
                    <a href="/admin/inventory.php?status=out" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 <?= $statusFilter === 'out' ? 'stat-card-danger' : '' ?>">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                    <i class="bi bi-x-circle fs-4"></i>
                                </div>
                                <div>
                                    <div class="text-muted small">Out of Stock</div>
                                    <div class="fw-bold fs-4"><?= $outOfStock ?></div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-pie-chart me-2"></i>Stock Health Overview</h6>
                        </div>
                        <div class="card-body d-flex align-items-center justify-content-center" style="height:220px;">
                            <canvas id="stockHealthChart" aria-label="Doughnut chart showing stock health breakdown" role="img"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pt-3">
                            <h6 class="fw-semibold mb-0"><i class="bi bi-info-circle me-2"></i>Stock Summary</h6>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-center gap-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#198754;"></span>
                                    <span class="small">Healthy Stock (&ge;5 units)</span>
                                </div>
                                <span class="fw-bold fs-5"><?= $healthyStock ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#ffc107;"></span>
                                    <span class="small">Low Stock (1–4 units)</span>
                                </div>
                                <span class="fw-bold fs-5"><?= $lowStock ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:#dc3545;"></span>
                                    <span class="small">Out of Stock (0 units)</span>
                                </div>
                                <span class="fw-bold fs-5"><?= $outOfStock ?></span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Total Products</span>
                                <span class="fw-bold"><?= $totalItems ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div><div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <?php if ($statusFilter): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                        <?php endif; ?>
                        <div class="col-md-9">
                            <label for="search" class="form-label">Search Products</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Product name or category..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                            <?php if ($search || $statusFilter): ?>
                                <a href="/admin/inventory.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <?php if ($statusFilter === 'low'): ?>
                            <i class="bi bi-exclamation-triangle text-warning me-1"></i>Low Stock Items
                        <?php elseif ($statusFilter === 'out'): ?>
                            <i class="bi bi-x-circle text-danger me-1"></i>Out of Stock Items
                        <?php else: ?>
                            <i class="bi bi-list-ul me-1"></i>All Products
                        <?php endif; ?>
                        <span class="text-muted fw-normal small ms-1">(<?= count($allProducts) ?>)</span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Product</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th style="width:220px;">Stock Level</th>
                                    <th class="text-end pe-3">Update</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($allProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No products match your filter.
                                        <a href="/admin/inventory.php">Show all</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProducts as $p):
                                    $qty    = (int)$p['stock_quantity'];
                                    $status = stockStatus($qty);
                                ?>
                                <tr class="<?= $qty === 0 ? 'table-danger bg-opacity-25' : ($qty < 5 ? 'table-warning bg-opacity-10' : '') ?>">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($p['image_url'])): ?>
                                                <img src="<?= '/' . htmlspecialchars($p['image_url']) ?>"
                                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                                     class="rounded" width="40" height="40"
                                                     style="object-fit:cover;">
                                            <?php else: ?>
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                                     style="width:40px;height:40px;">
                                                    <i class="bi bi-image text-muted small"></i>
                                                </div>
                                            <?php endif; ?>
                                            <span class="fw-semibold"><?= htmlspecialchars($p['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge <?= $status['class'] ?>">
                                            <?= $status['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="flex-grow-1">
                                                <?php
                                                    // Bar fills to max 50 units for visual purposes
                                                    $barPct = min(100, ($qty / 50) * 100);
                                                    $barColour = $qty === 0 ? 'bg-danger' : ($qty < 5 ? 'bg-warning' : 'bg-success');
                                                ?>
                                                <div class="progress" style="height:8px;" role="progressbar"
                                                     aria-valuenow="<?= $qty ?>" aria-valuemin="0" aria-valuemax="50"
                                                     aria-label="Stock level: <?= $qty ?> units">
                                                    <div class="progress-bar <?= $barColour ?>"
                                                         style="width:<?= $barPct ?>%"></div>
                                                </div>
                                            </div>
                                            <span class="text-muted small" style="min-width:28px;"><?= $qty ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <form method="POST" action="" class="d-flex align-items-center justify-content-end gap-1">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary px-2"
                                                    onclick="adjustStock(this, -1)"
                                                    aria-label="Decrease stock">
                                                <i class="bi bi-dash"></i>
                                            </button>

                                            <input type="number"
                                                   class="form-control form-control-sm text-center stock-input"
                                                   name="stock_quantity"
                                                   value="<?= $qty ?>"
                                                   min="0" step="1"
                                                   style="width:68px;"
                                                   aria-label="Stock quantity for <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>">

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary px-2"
                                                    onclick="adjustStock(this, 1)"
                                                    aria-label="Increase stock">
                                                <i class="bi bi-plus"></i>
                                            </button>

                                            <button type="submit" class="btn btn-sm text-white ms-1"
                                                    style="background-color:#28666e;"
                                                    aria-label="Save stock for <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * adjustStock(btn, delta)
 * Finds the stock number input in the same form as the clicked +/- button
 * and increments or decrements its value, clamped to a minimum of 0.
 */
function adjustStock(btn, delta) {
    // Walk up to the parent <form>, then find the stock input inside it
    const form  = btn.closest('form');
    const input = form.querySelector('.stock-input');
    if (!input) return;

    const current = parseInt(input.value, 10) || 0;
    const updated = Math.max(0, current + delta);
    input.value   = updated;
}
</script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var ctx = document.getElementById('stockHealthChart').getContext('2d');
        
        // Dynamically inject PHP variables for the doughnut chart
        var healthyCount = <?= $healthyStock ?>;
        var lowCount     = <?= $lowStock ?>;
        var outCount     = <?= $outOfStock ?>;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [healthyCount, lowCount, outCount],
                    backgroundColor: [
                        'rgba(25,  135, 84,  0.85)',
                        'rgba(255, 193, 7,   0.85)',
                        'rgba(220, 53,  69,  0.85)'
                    ],
                    borderColor: ['#198754','#ffc107','#dc3545'],
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                cutout:              '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a,b){ return a+b; }, 0);
                                var pct   = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>