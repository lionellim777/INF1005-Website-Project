<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$pdo = null;
$products   = [];
$categories = [];
$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo    = getDB();
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $name     = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
            $desc     = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
            $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $saleP    = ($_POST['sale_price'] ?? '') !== '' ? filter_input(INPUT_POST, 'sale_price', FILTER_VALIDATE_FLOAT) : null;
            $stock    = max(0, (int)($_POST['stock'] ?? 0));
            $catId    = (int)($_POST['category_id'] ?? 0) ?: null;
            $featured = isset($_POST['featured']) ? 1 : 0;

            if (!$name || $price === false || $price < 0) {
                $err = 'Product name and a valid price are required.';
            } else {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO products (name,description,price,sale_price,stock,category_id,featured,created_by) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$name,$desc,$price,$saleP,$stock,$catId,$featured,getUserId()]);
                    $msg = 'Product added successfully.';
                } else {
                    $id = (int)($_POST['product_id'] ?? 0);
                    $stmt = $pdo->prepare("UPDATE products SET name=?,description=?,price=?,sale_price=?,stock=?,category_id=?,featured=?,updated_at=NOW() WHERE id=?");
                    $stmt->execute([$name,$desc,$price,$saleP,$stock,$catId,$featured,$id]);
                    $msg = 'Product updated.';
                }
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['product_id'] ?? 0);
            $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
            $msg = 'Product removed.';

        } elseif ($action === 'toggle_stock') {
            $id    = (int)($_POST['product_id'] ?? 0);
            $stock = max(0, (int)($_POST['new_stock'] ?? 0));
            $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$stock,$id]);
            $msg = 'Stock updated.';
        }
    } catch (Exception $e) {
        $err = 'Database error.';
    }
    if ($msg) { header("Location: products.php?msg=".urlencode($msg)); exit; }
}

try {
    if (!$pdo) $pdo = getDB();
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    $products   = $pdo->query("
        SELECT p.*, c.name AS cat_name
        FROM products p LEFT JOIN categories c ON c.id=p.category_id
        WHERE p.is_active=1 ORDER BY p.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $categories = [
        ['id'=>1,'name'=>'Smartphones'],['id'=>2,'name'=>'Laptops'],
        ['id'=>3,'name'=>'Accessories'],['id'=>4,'name'=>'Wearables'],
    ];
    $products = [
        ['id'=>1,'name'=>'NeoPulse X1',   'description'=>'Flagship smartphone.','price'=>1299,'sale_price'=>1099,'stock'=>45,'cat_name'=>'Smartphones','category_id'=>1,'featured'=>1,'image'=>'../assets/phone.jpg'],
        ['id'=>2,'name'=>'UltraBook Pro 16','description'=>'OLED workstation.',  'price'=>2499,'sale_price'=>null, 'stock'=>15,'cat_name'=>'Laptops',     'category_id'=>2,'featured'=>1,'image'=>'../assets/cat.jpg'],
        ['id'=>3,'name'=>'ArcWatch Ultra', 'description'=>'Smartwatch ECG.',     'price'=>599, 'sale_price'=>null, 'stock'=>4, 'cat_name'=>'Wearables',    'category_id'=>4,'featured'=>1,'image'=>'../assets/cat.jpg'],
        ['id'=>4,'name'=>'SoundPods Pro',  'description'=>'True wireless ANC.',  'price'=>299, 'sale_price'=>249,  'stock'=>120,'cat_name'=>'Accessories', 'category_id'=>3,'featured'=>0,'image'=>'../assets/cat.jpg'],
    ];
    $err = 'Demo mode – DB not connected. Changes will not persist.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products – Admin – Pomegranate</title>
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
            <a href="products.php" class="sidebar-link active"><i class="bi bi-box-seam"></i> Products</a>

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
                <span class="page-title">Products</span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn-dash-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-lg"></i> Add Product
                </button>
            </div>
        </div>

        <div class="dash-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Product Management</h2>
                <p class="text-white-50 small mb-0">Add, edit, or remove products from the catalog.</p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($_GET['msg']) ?>
            </div>
            <?php endif; ?>
            <?php if ($err): ?>
            <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i><?= h($err) ?>
            </div>
            <?php endif; ?>

            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title">
                        Active Products
                        <span class="text-white-50 fw-normal ms-1" style="font-size:.8rem;">(<?= count($products) ?>)</span>
                    </span>
                    <div class="search-bar-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control-dark" placeholder="Search…"
                               style="padding-left:2.25rem;width:200px;font-size:.82rem;"
                               data-table-search="products-table">
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table" id="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Sale</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr><td colspan="7" class="text-center text-white-50 py-4">No products found.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold text-white small"><?= h($p['name']) ?></div>
                                    <div class="text-white-50" style="font-size:.72rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= h($p['description'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="text-white-50 small"><?= h($p['cat_name'] ?? '—') ?></td>
                                <td class="text-white fw-semibold">$<?= number_format($p['price'], 2) ?></td>
                                <td class="text-white-50 small">
                                    <?= $p['sale_price'] ? '$'.number_format($p['sale_price'],2) : '—' ?>
                                </td>
                                <td>
                                    <?php $stock = (int)$p['stock']; ?>
                                    <span class="fw-semibold <?= $stock===0?'text-danger':($stock<=5?'text-warning':'text-white') ?>">
                                        <?= $stock ?>
                                    </span>
                                    <?php if ($stock <= 5 && $stock > 0): ?>
                                    <span class="status-badge status-pending ms-1" style="font-size:.65rem;">Low</span>
                                    <?php elseif ($stock === 0): ?>
                                    <span class="status-badge status-cancelled ms-1" style="font-size:.65rem;">Out</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['featured']): ?>
                                    <span class="status-badge status-active" style="font-size:.72rem;">Yes</span>
                                    <?php else: ?>
                                    <span class="text-white-50 small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn-icon" title="Edit"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="products.php" class="d-inline">
                                            <input type="hidden" name="action"     value="delete">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn-icon" style="color:#f87171;" title="Remove"
                                                    data-confirm="Remove '<?= addslashes(h($p['name'])) ?>' from catalog?">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.08);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-plus-circle me-2" style="color:#22d3ee;"></i>Add Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <?php include "../employee/_product_form_fields.php"; ?>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-plus-lg"></i> Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.08);">
                <h5 class="modal-title text-white fw-bold">
                    <i class="bi bi-pencil me-2" style="color:#818cf8;"></i>Edit Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="products.php" id="editForm">
                <input type="hidden" name="action"     value="edit">
                <input type="hidden" name="product_id" id="edit-product-id">
                <div class="modal-body">
                    <?php include "../employee/_product_form_fields.php"; ?>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/dashboard.js"></script>
<script>
function openEditModal(p) {
    document.getElementById('edit-product-id').value     = p.id;
    document.getElementById('editForm').querySelector('[name=name]').value        = p.name || '';
    document.getElementById('editForm').querySelector('[name=description]').value = p.description || '';
    document.getElementById('editForm').querySelector('[name=price]').value       = p.price || '';
    document.getElementById('editForm').querySelector('[name=sale_price]').value  = p.sale_price || '';
    document.getElementById('editForm').querySelector('[name=stock]').value       = p.stock || '0';
    const catSel = document.getElementById('editForm').querySelector('[name=category_id]');
    if (catSel) catSel.value = p.category_id || '';
    const featChk = document.getElementById('editForm').querySelector('[name=featured]');
    if (featChk) featChk.checked = !!parseInt(p.featured);
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>
</body>
</html>
