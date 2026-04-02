<?php
require_once "../inc/auth.inc.php";
requireEmployee();

$pdo = null;
$products   = [];
$categories = [];
$msg = $err = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        requireValidCsrf($_POST['csrf_token'] ?? null);

        $pdo    = getDB();
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $name     = trim((string)($_POST['name'] ?? ''));
            $desc     = trim((string)($_POST['description'] ?? ''));
            $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $saleRaw  = trim((string)($_POST['sale_price'] ?? ''));
            $saleP    = $saleRaw !== '' ? filter_var($saleRaw, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) : null;
            $stock    = (int)($_POST['stock'] ?? 0);
            $catId    = (int)($_POST['category_id'] ?? 0) ?: null;
            $featured = isset($_POST['featured']) ? 1 : 0;

            if ($name === '' || strlen($name) > 100) {
                $err = 'Product name is required and must be 100 characters or fewer.';
            } elseif ($desc !== '' && strlen($desc) > 2000) {
                $err = 'Description must be 2000 characters or fewer.';
            } elseif ($price === false || $price < 0) {
                $err = 'Product name and a valid price are required.';
            } elseif ($saleRaw !== '' && ($saleP === null || $saleP < 0 || $saleP >= $price)) {
                $err = 'Sale price must be a positive value lower than the regular price.';
            } elseif ($stock < 0) {
                $err = 'Stock cannot be negative.';
            } else {
                if ($action === 'add') {
                    $stmt = $pdo->prepare("INSERT INTO products (name,description,price,sale_price,stock,category_id,featured,created_by) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$name,$desc,$price,$saleP,$stock,$catId,$featured,getUserId()]);
                    $msg = 'Product added successfully.';
                } else {
                    $id   = (int)($_POST['product_id'] ?? 0);
                    if ($id <= 0) {
                        $err = 'Invalid product selected for update.';
                    } else {
                    $stmt = $pdo->prepare("UPDATE products SET name=?,description=?,price=?,sale_price=?,stock=?,category_id=?,featured=?,updated_at=NOW() WHERE id=?");
                    $stmt->execute([$name,$desc,$price,$saleP,$stock,$catId,$featured,$id]);
                    $msg = 'Product updated.';
                    }
                }
            }

        } elseif ($action === 'delete') {
            $id   = (int)($_POST['product_id'] ?? 0);
            if ($id <= 0) {
                $err = 'Invalid product selected for deletion.';
            } else {
                $stmt = $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?");
                $stmt->execute([$id]);
                $msg  = 'Product removed from store.';
            }

        } elseif ($action === 'toggle_stock') {
            $id    = (int)($_POST['product_id'] ?? 0);
            $stock = (int)($_POST['new_stock'] ?? 0);
            if ($id <= 0 || $stock < 0) {
                $err = 'Invalid stock update request.';
            } else {
                $pdo->prepare("UPDATE products SET stock=? WHERE id=?")->execute([$stock,$id]);
                $msg = 'Stock updated.';
            }
        } else {
            $err = 'Unsupported action.';
        }
    } catch (RuntimeException $e) {
        $err = $e->getMessage();
    } catch (Throwable $e) {
        error_log('Employee products page error: ' . $e->getMessage());
        $err = 'Database error. Please ensure the database is connected.';
    }
    if ($msg) { header("Location: products.php?msg=" . urlencode($msg)); exit; }
}

// Fetch data
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
        ['id'=>1,'name'=>'NeoPulse X1','description'=>'Flagship smartphone.','price'=>1299,'sale_price'=>1099,'stock'=>45,'cat_name'=>'Smartphones','category_id'=>1,'featured'=>1,'image'=>'../assets/phone.jpg'],
        ['id'=>2,'name'=>'UltraBook Pro 16','description'=>'OLED workstation.','price'=>2499,'sale_price'=>null,'stock'=>15,'cat_name'=>'Laptops','category_id'=>2,'featured'=>1,'image'=>'../assets/cat.jpg'],
        ['id'=>3,'name'=>'ArcWatch Ultra','description'=>'Smartwatch ECG.','price'=>599,'sale_price'=>null,'stock'=>4,'cat_name'=>'Wearables','category_id'=>4,'featured'=>1,'image'=>'../assets/cat.jpg'],
        ['id'=>4,'name'=>'SoundPods Pro','description'=>'True wireless ANC.','price'=>299,'sale_price'=>249,'stock'=>120,'cat_name'=>'Accessories','category_id'=>3,'featured'=>0,'image'=>'../assets/cat.jpg'],
    ];
    $err = 'Demo mode – DB not connected. Changes will not persist.';
}

$flashMsg = $_GET['msg'] ?? $msg;
$flashErr = $err;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products – Employee</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="dash-wrapper">
    <!-- ── SIDEBAR ── -->
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
            <div class="sidebar-section-label">Store Ops</div>
            <a href="products.php" class="sidebar-link active"><i class="bi bi-box-seam"></i> Products</a>
            <div class="sidebar-section-label">Store</div>
            <a href="/catalog.php" class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
            <a href="/index.php"   class="sidebar-link"><i class="bi bi-house"></i> Back to Home</a>
        </nav>
        <div class="sidebar-footer">
            <form method="POST" action="/logout.php" class="m-0">
                <?= csrfInput() ?>
                <button type="submit" class="sidebar-user sidebar-user-btn">
                    <div class="sidebar-avatar"><?= strtoupper(substr(getUsername(), 0, 1)) ?></div>
                    <div class="sidebar-user-info">
                        <div class="name"><?= h(getFullName()) ?></div>
                        <div class="role">Sign out</div>
                    </div>
                    <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- ── MAIN ── -->
    <div id="main-content" class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle" type="button" aria-label="Toggle sidebar menu"><i class="bi bi-list"></i></button>
                <span class="page-title">Products</span>
            </div>
            <button type="button" class="btn-dash-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>

        <div class="dash-content">

            <?php if ($flashMsg): ?>
            <div class="alert-success-dark p-3 mb-4 auto-dismiss d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill"></i><?= h($flashMsg) ?>
            </div>
            <?php endif; ?>
            <?php if ($flashErr): ?>
            <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i><?= h($flashErr) ?>
            </div>
            <?php endif; ?>

            <!-- Product table -->
            <div class="dash-table-wrap">
                <div class="dash-table-header">
                    <span class="dash-table-title"><?= count($products) ?> Products</span>
                    <div class="search-bar-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" class="form-control-dark" placeholder="Search products…"
                               style="padding-left:2.25rem;width:220px;font-size:.82rem;"
                               aria-label="Search products table"
                               data-table-search="products-table">
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="dash-table" id="products-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p):
                                $stockClass = $p['stock'] <= 0 ? 'status-cancelled' : ($p['stock'] <= 5 ? 'status-pending' : 'status-active');
                            ?>
                            <tr>
                                <td class="text-white-50"><?= $p['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= h(str_replace('../', '/', $p['image'] ?? '../assets/cat.jpg')) ?>"
                                             alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid rgba(255,255,255,.08);">
                                        <div>
                                            <div class="fw-semibold text-white small"><?= h($p['name']) ?></div>
                                            <?php if ($p['sale_price']): ?>
                                            <div class="status-badge status-processing" style="font-size:.65rem;padding:.15rem .45rem;">On Sale</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= h($p['cat_name'] ?? '—') ?></td>
                                <td>
                                    <div class="fw-semibold text-white">$<?= number_format($p['price'], 2) ?></div>
                                    <?php if ($p['sale_price']): ?>
                                    <div style="font-size:.75rem;color:var(--green);">$<?= number_format($p['sale_price'], 2) ?> sale</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $stockClass ?>">
                                        <?= $p['stock'] ?> units
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['featured']): ?>
                                    <i class="bi bi-star-fill" style="color:var(--amber);"></i>
                                    <?php else: ?>
                                    <i class="bi bi-star text-white-50"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn-icon" title="Edit" aria-label="Edit product <?= h($p['name']) ?>"
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <?= csrfInput() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-icon danger"
                                                    data-confirm="Remove '<?= h($p['name']) ?>' from the store?"
                                                    aria-label="Remove product <?= h($p['name']) ?>"
                                                    title="Remove">
                                                <i class="bi bi-trash"></i>
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

<!-- ── ADD PRODUCT MODAL ── -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white" id="addProductModalLabel">
                    <i class="bi bi-plus-circle me-2" style="color:var(--cyan);"></i>Add New Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="products.php">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <?php include '_product_form_fields.php'; ?>
                </div>
                <div class="modal-footer" style="border-color:rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary"><i class="bi bi-plus-lg"></i> Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── EDIT PRODUCT MODAL ── -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-color:rgba(255,255,255,.08);">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-pencil me-2" style="color:var(--purple);"></i>Edit Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="products.php" id="editProductForm">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-body" id="editModalBody">
                    <?php include '_product_form_fields.php'; ?>
                </div>
                <div class="modal-footer" style="border-color:rgba(255,255,255,.08);">
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
function openEditModal(product) {
    document.getElementById('edit_product_id').value  = product.id;
    // Populate form fields in the edit modal
    const form = document.getElementById('editProductForm');
    const set  = (name, val) => { const el = form.querySelector(`[name="${name}"]`); if (el) el.value = val ?? ''; };
    set('name',        product.name);
    set('description', product.description);
    set('price',       product.price);
    set('sale_price',  product.sale_price ?? '');
    set('stock',       product.stock);
    set('category_id', product.category_id ?? '');
    const featured = form.querySelector('[name="featured"]');
    if (featured) featured.checked = !!product.featured;

    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}
</script>
</body>
</html>
