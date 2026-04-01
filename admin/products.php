<?php
/**
 * Admin – Product Management
 * Full CRUD: view all products, add new, edit existing, delete.
 * Access: admin only (role_id = 4).
 *
 * All DB operations use PDO prepared statements (SQL injection prevention).
 * All output is escaped with htmlspecialchars() (XSS prevention).
 * All POST actions verified with CSRF token.
 */

require_once __DIR__ . '/../auth/auth_helper.php';
initSession();
requireRole(4);  // Admin only

$currentSessionUser = currentUser();
$isOffline          = isOfflineMode();
$pdo                = $isOffline ? null : getDBConnection();

// ============================================================
// OFFLINE DEMO: seed products into session if not present
// ============================================================
if ($isOffline) {
    if (!isset($_SESSION['offline_products'])) {
        $_SESSION['offline_products'] = [
            1 => ['id'=>1,'name'=>'Pomegranate Argus',        'description'=>'Flagship smartphone with triple camera system and 6.7" AMOLED display.','price'=>1299.99,'old_price'=>null,   'image_url'=>'assets/argus.jpg',    'category'=>'Smartphones','badge'=>'New',        'model'=>'Argus',    'stock_quantity'=>15],
            2 => ['id'=>2,'name'=>'Pomegranate Guinevere',     'description'=>'Mid-range powerhouse with 5000mAh battery and 6.4" display.',           'price'=>849.00, 'old_price'=>999.00, 'image_url'=>'assets/guinevere.jpg','category'=>'Smartphones','badge'=>'Sale',       'model'=>'Guinevere','stock_quantity'=>8],
            3 => ['id'=>3,'name'=>'Pomegranate Esmeralda',     'description'=>'Compact flagship with 6.1" Super Retina display and ceramic build.',    'price'=>1099.00,'old_price'=>null,   'image_url'=>'assets/esmeralda.jpg','category'=>'Smartphones','badge'=>'Best Seller','model'=>'Esmeralda','stock_quantity'=>12],
            4 => ['id'=>4,'name'=>'Pomegranate Sora',          'description'=>'Entry-level smartphone with long battery life and durable design.',      'price'=>399.00, 'old_price'=>null,   'image_url'=>'assets/sora.jpg',     'category'=>'Smartphones','badge'=>null,         'model'=>'Sora',     'stock_quantity'=>20],
            5 => ['id'=>5,'name'=>'Pomegranate Thamuz',        'description'=>'Gaming-focused phone with 144Hz display and advanced cooling system.',   'price'=>999.00, 'old_price'=>1199.00,'image_url'=>'assets/thamuz.jpg',   'category'=>'Smartphones','badge'=>'Sale',       'model'=>'Thamuz',   'stock_quantity'=>3],
            6 => ['id'=>6,'name'=>'Pomegranate Wireless Buds', 'description'=>'True wireless earbuds with active noise cancellation.',                  'price'=>199.99, 'old_price'=>null,   'image_url'=>null,                  'category'=>'Accessories','badge'=>'New',        'model'=>null,       'stock_quantity'=>2],
        ];
        $_SESSION['_offline_product_seq'] = 7;
    }
}

// ============================================================
// HELPER: fetch a single product by ID
// ============================================================
function getProductById(int $id): ?array {
    global $pdo, $isOffline;
    if ($isOffline) {
        return $_SESSION['offline_products'][$id] ?? null;
    }
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

// ============================================================
// POST HANDLER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrf()) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . appUrl('/admin/products.php'));
        exit;
    }

    $action = sanitize($_POST['action'] ?? '');

    // ----------------------------------------------------------
    // ADD PRODUCT
    // ----------------------------------------------------------
    if ($action === 'add_product') {
        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = $_POST['price'] ?? '';
        $oldPrice = trim($_POST['old_price'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $badge    = trim($_POST['badge'] ?? '');
        $model    = trim($_POST['model'] ?? '');
        $stock    = $_POST['stock_quantity'] ?? '0';

        $errors = [];
        if ($name === '')                                                      $errors[] = 'Product name is required.';
        if (!is_numeric($price) || (float)$price < 0)                         $errors[] = 'Price must be a positive number.';
        if ($oldPrice !== '' && (!is_numeric($oldPrice) || (float)$oldPrice < 0)) $errors[] = 'Old price must be a positive number.';
        if (!ctype_digit((string)(int)$stock) || (int)$stock < 0)             $errors[] = 'Stock must be 0 or more.';

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . appUrl('/admin/products.php?modal=add'));
            exit;
        }

        $priceVal    = round((float)$price, 2);
        $oldPriceVal = ($oldPrice !== '') ? round((float)$oldPrice, 2) : null;
        $stockVal    = max(0, (int)$stock);
        $imageVal    = $imageUrl  !== '' ? $imageUrl  : null;
        $categoryVal = $category  !== '' ? $category  : null;
        $badgeVal    = $badge     !== '' ? $badge     : null;
        $modelVal    = $model     !== '' ? $model     : null;

        if ($isOffline) {
            $newId = (int)$_SESSION['_offline_product_seq']++;
            $_SESSION['offline_products'][$newId] = compact('name','desc','priceVal','oldPriceVal','imageVal','categoryVal','badgeVal','modelVal','stockVal');
            $_SESSION['offline_products'][$newId]['id']             = $newId;
            $_SESSION['offline_products'][$newId]['description']    = $desc;
            $_SESSION['offline_products'][$newId]['price']          = $priceVal;
            $_SESSION['offline_products'][$newId]['old_price']      = $oldPriceVal;
            $_SESSION['offline_products'][$newId]['image_url']      = $imageVal;
            $_SESSION['offline_products'][$newId]['category']       = $categoryVal;
            $_SESSION['offline_products'][$newId]['badge']          = $badgeVal;
            $_SESSION['offline_products'][$newId]['model']          = $modelVal;
            $_SESSION['offline_products'][$newId]['stock_quantity'] = $stockVal;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, description, price, old_price, image_url, category, badge, model, stock_quantity)
                 VALUES (:name, :desc, :price, :old_price, :image_url, :category, :badge, :model, :stock)'
            );
            $stmt->execute([
                ':name'      => $name,
                ':desc'      => $desc ?: null,
                ':price'     => $priceVal,
                ':old_price' => $oldPriceVal,
                ':image_url' => $imageVal,
                ':category'  => $categoryVal,
                ':badge'     => $badgeVal,
                ':model'     => $modelVal,
                ':stock'     => $stockVal,
            ]);
        }

        logActivity((int)$currentSessionUser['user_id'], 'admin_add_product', "Added product: {$name}");
        setFlash('success', "Product \"{$name}\" added successfully.");
        header('Location: ' . appUrl('/admin/products.php'));
        exit;
    }

    // ----------------------------------------------------------
    // EDIT PRODUCT
    // ----------------------------------------------------------
    if ($action === 'edit_product') {
        $id       = (int)($_POST['product_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = $_POST['price'] ?? '';
        $oldPrice = trim($_POST['old_price'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $badge    = trim($_POST['badge'] ?? '');
        $model    = trim($_POST['model'] ?? '');
        $stock    = $_POST['stock_quantity'] ?? '0';

        $errors = [];
        if ($id <= 0)                                                          $errors[] = 'Invalid product.';
        if ($name === '')                                                       $errors[] = 'Product name is required.';
        if (!is_numeric($price) || (float)$price < 0)                         $errors[] = 'Price must be a positive number.';
        if ($oldPrice !== '' && (!is_numeric($oldPrice) || (float)$oldPrice < 0)) $errors[] = 'Old price must be a positive number.';
        if (!ctype_digit((string)(int)$stock) || (int)$stock < 0)             $errors[] = 'Stock must be 0 or more.';

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            header('Location: ' . appUrl("/admin/products.php?modal=edit&id={$id}"));
            exit;
        }

        $priceVal    = round((float)$price, 2);
        $oldPriceVal = ($oldPrice !== '') ? round((float)$oldPrice, 2) : null;
        $stockVal    = max(0, (int)$stock);
        $imageVal    = $imageUrl  !== '' ? $imageUrl  : null;
        $categoryVal = $category  !== '' ? $category  : null;
        $badgeVal    = $badge     !== '' ? $badge     : null;
        $modelVal    = $model     !== '' ? $model     : null;

        if ($isOffline) {
            if (isset($_SESSION['offline_products'][$id])) {
                $_SESSION['offline_products'][$id] = array_merge(
                    $_SESSION['offline_products'][$id],
                    [
                        'name'           => $name,
                        'description'    => $desc,
                        'price'          => $priceVal,
                        'old_price'      => $oldPriceVal,
                        'image_url'      => $imageVal,
                        'category'       => $categoryVal,
                        'badge'          => $badgeVal,
                        'model'          => $modelVal,
                        'stock_quantity' => $stockVal,
                    ]
                );
            }
        } else {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET name=:name, description=:desc, price=:price, old_price=:old_price,
                     image_url=:image_url, category=:category, badge=:badge,
                     model=:model, stock_quantity=:stock
                 WHERE id=:id'
            );
            $stmt->execute([
                ':name'      => $name,
                ':desc'      => $desc ?: null,
                ':price'     => $priceVal,
                ':old_price' => $oldPriceVal,
                ':image_url' => $imageVal,
                ':category'  => $categoryVal,
                ':badge'     => $badgeVal,
                ':model'     => $modelVal,
                ':stock'     => $stockVal,
                ':id'        => $id,
            ]);
        }

        logActivity((int)$currentSessionUser['user_id'], 'admin_edit_product', "Edited product #{$id}: {$name}");
        setFlash('success', "Product \"{$name}\" updated successfully.");
        header('Location: ' . appUrl('/admin/products.php'));
        exit;
    }

    // ----------------------------------------------------------
    // DELETE PRODUCT
    // ----------------------------------------------------------
    if ($action === 'delete_product') {
        $id = (int)($_POST['product_id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid product.');
            header('Location: ' . appUrl('/admin/products.php'));
            exit;
        }

        $product     = getProductById($id);
        $productName = $product['name'] ?? "#{$id}";

        if ($isOffline) {
            unset($_SESSION['offline_products'][$id]);
        } else {
            $pdo->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
        }

        logActivity((int)$currentSessionUser['user_id'], 'admin_delete_product', "Deleted product #{$id}: {$productName}");
        setFlash('success', "Product \"{$productName}\" deleted.");
        header('Location: ' . appUrl('/admin/products.php'));
        exit;
    }
}

// ============================================================
// FETCH ALL PRODUCTS FOR DISPLAY
// ============================================================
$search    = sanitize($_GET['search'] ?? '');
$catFilter = sanitize($_GET['category'] ?? '');

if ($isOffline) {
    $allProducts = array_values($_SESSION['offline_products']);
    if ($search) {
        $needle = strtolower($search);
        $allProducts = array_values(array_filter($allProducts, function($p) use ($needle) {
            return str_contains(strtolower($p['name']), $needle)
                || str_contains(strtolower($p['model'] ?? ''), $needle);
        }));
    }
    if ($catFilter) {
        $allProducts = array_values(array_filter($allProducts, function($p) use ($catFilter) {
            return ($p['category'] ?? '') === $catFilter;
        }));
    }
    $allCategories = array_unique(array_filter(array_column($_SESSION['offline_products'], 'category')));
} else {
    $sql    = 'SELECT * FROM products WHERE 1=1';
    $params = [];
    if ($search) {
        $sql .= ' AND (name LIKE :search OR model LIKE :search2)';
        $params[':search']  = "%{$search}%";
        $params[':search2'] = "%{$search}%";
    }
    if ($catFilter) {
        $sql .= ' AND category = :category';
        $params[':category'] = $catFilter;
    }
    $sql .= ' ORDER BY id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allProducts = $stmt->fetchAll();

    $allCategories = $pdo->query('SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category')
                         ->fetchAll(PDO::FETCH_COLUMN);
}

// For re-opening a modal after a validation error redirect
$modalMode   = sanitize($_GET['modal'] ?? '');
$editId      = (int)($_GET['id'] ?? 0);
$editProduct = ($modalMode === 'edit' && $editId > 0) ? getProductById($editId) : null;

$currentPage  = 'products';
$pageTitle    = 'Products – Admin – ' . SITE_NAME;
$badgeOptions = ['', 'New', 'Sale', 'Best Seller', 'Limited'];
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
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">

        <!-- Top bar -->
        <div class="admin-topbar d-flex align-items-center justify-content-between px-4 py-3">
            <div>
                <h4 class="fw-bold mb-0"><i class="bi bi-phone me-2"></i>Product Management</h4>
                <small class="text-muted"><?= count($allProducts) ?> product(s) found</small>
            </div>
            <button type="button" class="btn text-white" style="background-color:#28666e;"
                    data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg me-1"></i>Add Product
            </button>
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

            <!-- Search & Filter -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Product name or model..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"
                                        <?= $catFilter === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn text-white flex-grow-1" style="background-color:#28666e;">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <?php if ($search || $catFilter): ?>
                                <a href="<?= appUrl('/admin/products.php') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Badge</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($allProducts)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No products found.
                                        <?php if ($search || $catFilter): ?>
                                            <a href="<?= appUrl('/admin/products.php') ?>">Clear filters</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProducts as $p): ?>
                                <tr>
                                    <td class="ps-3 text-muted small">#<?= (int)$p['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($p['image_url'])): ?>
                                                <img src="<?= appUrl('/' . htmlspecialchars($p['image_url'])) ?>"
                                                     alt="<?= htmlspecialchars($p['name']) ?>"
                                                     class="rounded" width="44" height="44"
                                                     style="object-fit:cover;">
                                            <?php else: ?>
                                                <div class="rounded bg-light d-flex align-items-center justify-content-center"
                                                     style="width:44px;height:44px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                                                <?php if (!empty($p['model'])): ?>
                                                    <div class="text-muted small"><?= htmlspecialchars($p['model']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                                    <td>
                                        <div class="fw-semibold">$<?= number_format((float)$p['price'], 2) ?></div>
                                        <?php if (!empty($p['old_price'])): ?>
                                            <div class="text-muted text-decoration-line-through small">
                                                $<?= number_format((float)$p['old_price'], 2) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php $stock = (int)$p['stock_quantity']; ?>
                                        <span class="badge <?= $stock === 0 ? 'bg-danger' : ($stock < 5 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                            <?= $stock === 0 ? 'Out of stock' : $stock . ' in stock' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['badge'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($p['badge']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <!-- Edit button — passes all data as escaped data-* attributes -->
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary me-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editProductModal"
                                                data-id="<?= (int)$p['id'] ?>"
                                                data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                                                data-description="<?= htmlspecialchars($p['description'] ?? '', ENT_QUOTES) ?>"
                                                data-price="<?= htmlspecialchars((string)$p['price'], ENT_QUOTES) ?>"
                                                data-old_price="<?= htmlspecialchars((string)($p['old_price'] ?? ''), ENT_QUOTES) ?>"
                                                data-image_url="<?= htmlspecialchars($p['image_url'] ?? '', ENT_QUOTES) ?>"
                                                data-category="<?= htmlspecialchars($p['category'] ?? '', ENT_QUOTES) ?>"
                                                data-badge="<?= htmlspecialchars($p['badge'] ?? '', ENT_QUOTES) ?>"
                                                data-model="<?= htmlspecialchars($p['model'] ?? '', ENT_QUOTES) ?>"
                                                data-stock="<?= (int)$p['stock_quantity'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>

                                        <!-- Delete form -->
                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Delete
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


<!-- ============================================================
     ADD PRODUCT MODAL
     ============================================================ -->
<div class="modal fade" id="addProductModal" tabindex="-1"
     aria-labelledby="addProductModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_product">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addProductModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="add-name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add-name" name="name"
                                   placeholder="e.g. Pomegranate Argus Pro" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="add-model" class="form-label fw-semibold">Model</label>
                            <input type="text" class="form-control" id="add-model" name="model"
                                   placeholder="e.g. Argus Pro" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="add-category" class="form-label fw-semibold">Category</label>
                            <input type="text" class="form-control" id="add-category" name="category"
                                   placeholder="e.g. Smartphones" list="category-suggestions" maxlength="100">
                            <datalist id="category-suggestions">
                                <option value="Smartphones">
                                <option value="Accessories">
                                <option value="Wearables">
                                <option value="Tablets">
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label for="add-description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="add-description" name="description"
                                      rows="3" placeholder="Brief product description..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="add-price" class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add-price" name="price"
                                   placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="add-old_price" class="form-label fw-semibold">
                                Old Price ($)
                                <span class="text-muted small fw-normal">(optional)</span>
                            </label>
                            <input type="number" class="form-control" id="add-old_price" name="old_price"
                                   placeholder="0.00" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="add-stock" class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="add-stock" name="stock_quantity"
                                   placeholder="0" min="0" step="1" value="0" required>
                        </div>
                        <div class="col-md-8">
                            <label for="add-image_url" class="form-label fw-semibold">
                                Image URL
                                <span class="text-muted small fw-normal">(relative path, e.g. assets/argus.jpg)</span>
                            </label>
                            <input type="text" class="form-control" id="add-image_url" name="image_url"
                                   placeholder="assets/photo.jpg" maxlength="500">
                        </div>
                        <div class="col-md-4">
                            <label for="add-badge" class="form-label fw-semibold">Badge</label>
                            <select class="form-select" id="add-badge" name="badge">
                                <option value="">None</option>
                                <option value="New">New</option>
                                <option value="Sale">Sale</option>
                                <option value="Best Seller">Best Seller</option>
                                <option value="Limited">Limited</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background-color:#28666e;">
                        <i class="bi bi-plus-lg me-1"></i>Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ============================================================
     EDIT PRODUCT MODAL
     ============================================================ -->
<div class="modal fade" id="editProductModal" tabindex="-1"
     aria-labelledby="editProductModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="product_id" id="edit-id">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editProductModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit-name" class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit-name" name="name"
                                   placeholder="e.g. Pomegranate Argus Pro" required maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-model" class="form-label fw-semibold">Model</label>
                            <input type="text" class="form-control" id="edit-model" name="model"
                                   placeholder="e.g. Argus Pro" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-category" class="form-label fw-semibold">Category</label>
                            <input type="text" class="form-control" id="edit-category" name="category"
                                   placeholder="e.g. Smartphones" list="edit-category-suggestions" maxlength="100">
                            <datalist id="edit-category-suggestions">
                                <option value="Smartphones">
                                <option value="Accessories">
                                <option value="Wearables">
                                <option value="Tablets">
                            </datalist>
                        </div>
                        <div class="col-12">
                            <label for="edit-description" class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" id="edit-description" name="description"
                                      rows="3" placeholder="Brief product description..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit-price" class="form-label fw-semibold">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit-price" name="price"
                                   placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label for="edit-old_price" class="form-label fw-semibold">
                                Old Price ($)
                                <span class="text-muted small fw-normal">(optional)</span>
                            </label>
                            <input type="number" class="form-control" id="edit-old_price" name="old_price"
                                   placeholder="0.00" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="edit-stock" class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit-stock" name="stock_quantity"
                                   placeholder="0" min="0" step="1" required>
                        </div>
                        <div class="col-md-8">
                            <label for="edit-image_url" class="form-label fw-semibold">
                                Image URL
                                <span class="text-muted small fw-normal">(relative path, e.g. assets/argus.jpg)</span>
                            </label>
                            <input type="text" class="form-control" id="edit-image_url" name="image_url"
                                   placeholder="assets/photo.jpg" maxlength="500">
                        </div>
                        <div class="col-md-4">
                            <label for="edit-badge" class="form-label fw-semibold">Badge</label>
                            <select class="form-select" id="edit-badge" name="badge">
                                <option value="">None</option>
                                <option value="New">New</option>
                                <option value="Sale">Sale</option>
                                <option value="Best Seller">Best Seller</option>
                                <option value="Limited">Limited</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background-color:#28666e;">
                        <i class="bi bi-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
<script>
/**
 * Pre-fill the Edit Product modal using data-* attributes from the clicked Edit button.
 * All values were PHP-escaped before being written into data attributes, so this is safe.
 */
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editProductModal');

    editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;

        // Map: data attribute name -> edit modal input ID
        const fields = {
            'id':          'edit-id',
            'name':        'edit-name',
            'description': 'edit-description',
            'price':       'edit-price',
            'old_price':   'edit-old_price',
            'image_url':   'edit-image_url',
            'category':    'edit-category',
            'badge':       'edit-badge',
            'model':       'edit-model',
            'stock':       'edit-stock',
        };

        Object.entries(fields).forEach(function([dataAttr, inputId]) {
            const val = btn.getAttribute('data-' + dataAttr) || '';
            const el  = document.getElementById(inputId);
            if (el) el.value = val;
        });
    });

    <?php if ($modalMode === 'add'): ?>
    // Re-open Add modal after a server-side validation error
    new bootstrap.Modal(document.getElementById('addProductModal')).show();
    <?php elseif ($modalMode === 'edit' && $editProduct): ?>
    // Re-open Edit modal after a server-side validation error, pre-filled
    document.getElementById('edit-id').value          = <?= (int)$editProduct['id'] ?>;
    document.getElementById('edit-name').value        = <?= json_encode($editProduct['name']) ?>;
    document.getElementById('edit-description').value = <?= json_encode($editProduct['description'] ?? '') ?>;
    document.getElementById('edit-price').value       = <?= json_encode((string)$editProduct['price']) ?>;
    document.getElementById('edit-old_price').value   = <?= json_encode((string)($editProduct['old_price'] ?? '')) ?>;
    document.getElementById('edit-image_url').value   = <?= json_encode($editProduct['image_url'] ?? '') ?>;
    document.getElementById('edit-category').value    = <?= json_encode($editProduct['category'] ?? '') ?>;
    document.getElementById('edit-badge').value       = <?= json_encode($editProduct['badge'] ?? '') ?>;
    document.getElementById('edit-model').value       = <?= json_encode($editProduct['model'] ?? '') ?>;
    document.getElementById('edit-stock').value       = <?= (int)$editProduct['stock_quantity'] ?>;
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
    <?php endif; ?>
});
</script>

</body>
</html>
