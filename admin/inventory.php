<?php
/**
 * Admin – Inventory Management (Phase 3)
 * View stock levels for all products and update quantities.
 * Access: admin only (role_id = 4).
 *
 * Stock is stored in products.stock_quantity.
 * Supports:
 *   - Bulk view of all products with colour-coded stock status
 *   - Inline quick-adjust (+/- buttons via JS, submits one row at a time)
 *   - Manual exact-value input per product
 *   - Filter by stock status (all / low / out of stock)
 *
 * Security: PDO prepared statements, CSRF, htmlspecialchars output escaping.
 */

require_once __DIR__ . '/../auth/auth_helper.php';
initSession();
requireRole(4);  // Admin only

$currentSessionUser = currentUser();
$isOffline          = isOfflineMode();
$pdo                = $isOffline ? null : getDBConnection();

// Ensure offline products are seeded (shares same session key as products.php)
if ($isOffline && !isset($_SESSION['offline_products'])) {
    $_SESSION['offline_products'] = [
        1 => ['id'=>1,'name'=>'Pomegranate Argus',        'category'=>'Smartphones','stock_quantity'=>15,'price'=>1299.99,'image_url'=>'assets/argus.jpg'],
        2 => ['id'=>2,'name'=>'Pomegranate Guinevere',     'category'=>'Smartphones','stock_quantity'=>8, 'price'=>849.00, 'image_url'=>'assets/guinevere.jpg'],
        3 => ['id'=>3,'name'=>'Pomegranate Esmeralda',     'category'=>'Smartphones','stock_quantity'=>12,'price'=>1099.00,'image_url'=>'assets/esmeralda.jpg'],
        4 => ['id'=>4,'name'=>'Pomegranate Sora',          'category'=>'Smartphones','stock_quantity'=>20,'price'=>399.00, 'image_url'=>'assets/sora.jpg'],
        5 => ['id'=>5,'name'=>'Pomegranate Thamuz',        'category'=>'Smartphones','stock_quantity'=>3, 'price'=>999.00, 'image_url'=>'assets/thamuz.jpg'],
        6 => ['id'=>6,'name'=>'Pomegranate Wireless Buds', 'category'=>'Accessories','stock_quantity'=>2, 'price'=>199.99, 'image_url'=>null],
    ];
    $_SESSION['_offline_product_seq'] = 7;
}

// ============================================================
// POST: UPDATE STOCK QUANTITY FOR ONE PRODUCT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . appUrl('/admin/inventory.php'));
        exit;
    }

    $action    = sanitize($_POST['action'] ?? '');
    $productId = (int)($_POST['product_id'] ?? 0);
    $newStock  = $_POST['stock_quantity'] ?? '';

    if ($action === 'update_stock') {

        // Validate inputs
        $errors = [];
        if ($productId <= 0)                                     $errors[] = 'Invalid product.';
        if (!ctype_digit((string)(int)$newStock) || (int)$newStock < 0)
                                                                 $errors[] = 'Stock must be a whole number, 0 or more.';

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . appUrl('/admin/inventory.php'));
            exit;
        }

        $stockVal = (int)$newStock;

        if ($isOffline) {
            if (isset($_SESSION['offline_products'][$productId])) {
                $oldStock = (int)$_SESSION['offline_products'][$productId]['stock_quantity'];
                $_SESSION['offline_products'][$productId]['stock_quantity'] = $stockVal;
                $productName = $_SESSION['offline_products'][$productId]['name'];
            } else {
                setFlash('error', 'Product not found.');
                header('Location: ' . appUrl('/admin/inventory.php'));
                exit;
            }
        } else {
            // Fetch current stock for the log message
            $stmt = $pdo->prepare('SELECT name, stock_quantity FROM products WHERE id = :id');
            $stmt->execute([':id' => $productId]);
            $row = $stmt->fetch();

            if (!$row) {
                setFlash('error', 'Product not found.');
                header('Location: ' . appUrl('/admin/inventory.php'));
                exit;
            }

            $oldStock    = (int)$row['stock_quantity'];
            $productName = $row['name'];

            $pdo->prepare('UPDATE products SET stock_quantity = :stock WHERE id = :id')
                ->execute([':stock' => $stockVal, ':id' => $productId]);
        }

        // Log what changed
        $change = $stockVal - $oldStock;
        $changeStr = $change >= 0 ? "+{$change}" : "{$change}";
        logActivity(
            (int)$currentSessionUser['user_id'],
            'admin_update_stock',
            "Updated stock for \"{$productName}\" (#{$productId}): {$oldStock} → {$stockVal} ({$changeStr})"
        );

        setFlash('success', "Stock updated for \"{$productName}\": {$oldStock} → {$stockVal}.");
        header('Location: ' . appUrl('/admin/inventory.php'));
        exit;
    }
}

// ============================================================
// FETCH PRODUCTS FOR DISPLAY
// ============================================================
$statusFilter = sanitize($_GET['status'] ?? '');   // 'low' | 'out' | '' (all)
$search       = sanitize($_GET['search'] ?? '');

if ($isOffline) {
    $allProducts = array_values($_SESSION['offline_products']);

    // Apply search
    if ($search) {
        $needle = strtolower($search);
        $allProducts = array_values(array_filter($allProducts, function($p) use ($needle) {
            return str_contains(strtolower($p['name']), $needle)
                || str_contains(strtolower($p['category'] ?? ''), $needle);
        }));
    }

    // Apply status filter
    if ($statusFilter === 'out') {
        $allProducts = array_values(array_filter($allProducts, fn($p) => (int)$p['stock_quantity'] === 0));
    } elseif ($statusFilter === 'low') {
        $allProducts = array_values(array_filter($allProducts, fn($p) => (int)$p['stock_quantity'] > 0 && (int)$p['stock_quantity'] < 5));
    }

    // Summary counts from the full unfiltered set
    $allRaw       = array_values($_SESSION['offline_products']);
    $totalItems   = count($allRaw);
    $outOfStock   = count(array_filter($allRaw, fn($p) => (int)$p['stock_quantity'] === 0));
    $lowStock     = count(array_filter($allRaw, fn($p) => (int)$p['stock_quantity'] > 0 && (int)$p['stock_quantity'] < 5));
    $healthyStock = $totalItems - $outOfStock - $lowStock;

} else {
    // Build query
    $sql    = 'SELECT id, name, category, stock_quantity, price, image_url FROM products WHERE 1=1';
    $params = [];

    if ($search) {
        $sql .= ' AND (name LIKE :search OR category LIKE :search2)';
        $params[':search']  = "%{$search}%";
        $params[':search2'] = "%{$search}%";
    }
    if ($statusFilter === 'out') {
        $sql .= ' AND stock_quantity = 0';
    } elseif ($statusFilter === 'low') {
        $sql .= ' AND stock_quantity > 0 AND stock_quantity < 5';
    }

    $sql .= ' ORDER BY stock_quantity ASC, name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allProducts = $stmt->fetchAll();

    // Summary counts (always from full table, ignoring filters)
    $totalItems   = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $outOfStock   = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity = 0')->fetchColumn();
    $lowStock     = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE stock_quantity > 0 AND stock_quantity < 5')->fetchColumn();
    $healthyStock = $totalItems - $outOfStock - $lowStock;
}

// Helper: stock status label + badge class
function stockStatus(int $qty): array {
    if ($qty === 0)  return ['label' => 'Out of stock', 'class' => 'bg-danger'];
    if ($qty < 5)    return ['label' => 'Low stock',    'class' => 'bg-warning text-dark'];
    return               ['label' => 'In stock',       'class' => 'bg-success'];
}

$currentPage = 'inventory';
$pageTitle   = 'Inventory – Admin – ' . SITE_NAME;
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
                <h4 class="fw-bold mb-0"><i class="bi bi-boxes me-2"></i>Inventory Management</h4>
                <small class="text-muted">Monitor and update product stock levels</small>
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
                 SUMMARY STAT CARDS
                 ============================================================ -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <!-- Clicking filters the table -->
                    <a href="<?= appUrl('/admin/inventory.php') ?>" class="text-decoration-none">
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
                    <a href="<?= appUrl('/admin/inventory.php?status=low') ?>" class="text-decoration-none">
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
                    <a href="<?= appUrl('/admin/inventory.php?status=out') ?>" class="text-decoration-none">
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

            <!-- ============================================================
                 STOCK HEALTH DOUGHNUT CHART
                 ============================================================ -->
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
            </div><!-- /stock health chart row -->

            <!-- ============================================================
                 SEARCH BAR
                 ============================================================ -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <!-- Preserve active status filter when searching -->
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
                                <a href="<?= appUrl('/admin/inventory.php') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================================================
                 INVENTORY TABLE
                 ============================================================ -->
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
                                        <a href="<?= appUrl('/admin/inventory.php') ?>">Show all</a>
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
                                                <img src="<?= appUrl('/' . htmlspecialchars($p['image_url'])) ?>"
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
                                        <!-- Visual stock bar + current number -->
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
                                        <!--
                                            Inline update form.
                                            The +/- buttons are handled by JS (adjustStock) which changes
                                            the input value, then the Save button submits the form.
                                        -->
                                        <form method="POST" action="" class="d-flex align-items-center justify-content-end gap-1">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">

                                            <!-- Decrement button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary px-2"
                                                    onclick="adjustStock(this, -1)"
                                                    aria-label="Decrease stock">
                                                <i class="bi bi-dash"></i>
                                            </button>

                                            <!-- Editable stock input -->
                                            <input type="number"
                                                   class="form-control form-control-sm text-center stock-input"
                                                   name="stock_quantity"
                                                   value="<?= $qty ?>"
                                                   min="0" step="1"
                                                   style="width:68px;"
                                                   aria-label="Stock quantity for <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>">

                                            <!-- Increment button -->
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary px-2"
                                                    onclick="adjustStock(this, 1)"
                                                    aria-label="Increase stock">
                                                <i class="bi bi-plus"></i>
                                            </button>

                                            <!-- Save button -->
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

        </div><!-- /p-4 -->
    </div><!-- /admin-content -->
</div><!-- /admin-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
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
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Healthy', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [
                        <?= (int)$healthyStock ?>,
                        <?= (int)$lowStock ?>,
                        <?= (int)$outOfStock ?>
                    ],
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
