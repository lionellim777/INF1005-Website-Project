<?php
/**
 * Admin – Product Management
 * Full CRUD: view all products, add new, edit existing, delete.
 * Access: admin only.
 */

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/auth_middleware.php';

// Access: admin only
require_role(['admin']);
global $session;

// ============================================================
// HELPER: fetch a single product by ID
// ============================================================
function getProductById(int $id): ?array {
    global $db_conn;
    $stmt = $db_conn->prepare('SELECT id, name, description, price, old_price, image_url, category, badge, model, stock AS stock_quantity FROM products WHERE id = ?');
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close();
    return $product ?: null;
}

// ============================================================
// POST HANDLER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !csrf_verify($_POST['csrf_token'])) {
        $session->set_flashdata('error', 'Invalid request. Please try again.');
        header('Location: /admin/products.php');
        exit;
    }

    $action = trim($_POST['action'] ?? '');

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
        if (!is_numeric($price) || (float)$price < 0)                          $errors[] = 'Price must be a positive number.';
        if ($oldPrice !== '' && (!is_numeric($oldPrice) || (float)$oldPrice < 0)) $errors[] = 'Old price must be a positive number.';
        if (!ctype_digit((string)(int)$stock) || (int)$stock < 0)              $errors[] = 'Stock must be 0 or more.';

        if ($errors) {
            $session->set_flashdata('error', implode(' ', $errors));
            header('Location: /admin/products.php?modal=add');
            exit;
        }

        $priceVal    = round((float)$price, 2);
        $oldPriceVal = ($oldPrice !== '') ? round((float)$oldPrice, 2) : null;
        $stockVal    = max(0, (int)$stock);
        $imageVal    = $imageUrl  !== '' ? $imageUrl  : null;
        $categoryVal = $category  !== '' ? $category  : null;
        $badgeVal    = $badge     !== '' ? $badge     : null;
        $modelVal    = $model     !== '' ? $model     : null;

        $stmt = $db_conn->prepare(
            'INSERT INTO products (name, description, price, old_price, image_url, category, badge, model, stock)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param("ssddssssi", $name, $desc, $priceVal, $oldPriceVal, $imageVal, $categoryVal, $badgeVal, $modelVal, $stockVal);
        $stmt->execute();
        $stmt->close();

        $session->set_flashdata('success', "Product \"{$name}\" added successfully.");
        header('Location: /admin/products.php');
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
        if ($name === '')                                                      $errors[] = 'Product name is required.';
        if (!is_numeric($price) || (float)$price < 0)                          $errors[] = 'Price must be a positive number.';
        if ($oldPrice !== '' && (!is_numeric($oldPrice) || (float)$oldPrice < 0)) $errors[] = 'Old price must be a positive number.';
        if (!ctype_digit((string)(int)$stock) || (int)$stock < 0)              $errors[] = 'Stock must be 0 or more.';

        if ($errors) {
            $session->set_flashdata('error', implode(' ', $errors));
            header("Location: /admin/products.php?modal=edit&id={$id}");
            exit;
        }

        $priceVal    = round((float)$price, 2);
        $oldPriceVal = ($oldPrice !== '') ? round((float)$oldPrice, 2) : null;
        $stockVal    = max(0, (int)$stock);
        $imageVal    = $imageUrl  !== '' ? $imageUrl  : null;
        $categoryVal = $category  !== '' ? $category  : null;
        $badgeVal    = $badge     !== '' ? $badge     : null;
        $modelVal    = $model     !== '' ? $model     : null;

        $stmt = $db_conn->prepare(
            'UPDATE products
             SET name=?, description=?, price=?, old_price=?, image_url=?, category=?, badge=?, model=?, stock=?
             WHERE id=?'
        );
        $stmt->bind_param("ssddssssii", $name, $desc, $priceVal, $oldPriceVal, $imageVal, $categoryVal, $badgeVal, $modelVal, $stockVal, $id);
        $stmt->execute();
        $stmt->close();

        $session->set_flashdata('success', "Product \"{$name}\" updated successfully.");
        header('Location: /admin/products.php');
        exit;
    }

    // ----------------------------------------------------------
    // DELETE PRODUCT
    // ----------------------------------------------------------
    if ($action === 'delete_product') {
        $id = (int)($_POST['product_id'] ?? 0);

        if ($id <= 0) {
            $session->set_flashdata('error', 'Invalid product.');
            header('Location: /admin/products.php');
            exit;
        }

        $product     = getProductById($id);
        $productName = $product['name'] ?? "#{$id}";

        $stmt = $db_conn->prepare('DELETE FROM products WHERE id = ?');
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        $session->set_flashdata('success', "Product \"{$productName}\" deleted.");
        header('Location: /admin/products.php');
        exit;
    }
}

// ============================================================
// FETCH ALL PRODUCTS FOR DISPLAY
// ============================================================
$search    = htmlspecialchars($_GET['search'] ?? '');
$catFilter = htmlspecialchars($_GET['category'] ?? '');

$sql = 'SELECT id, name, description, price, old_price, image_url, category, badge, model, stock AS stock_quantity FROM products WHERE 1=1';
$types = "";
$params = [];

if ($search) {
    $sql .= ' AND (name LIKE ? OR model LIKE ?)';
    $types .= "ss";
    $searchParam = "%{$search}%";
    $params[] = &$searchParam;
    $params[] = &$searchParam;
}
if ($catFilter) {
    $sql .= ' AND category = ?';
    $types .= "s";
    $params[] = &$catFilter;
}
$sql .= ' ORDER BY id DESC';

$stmt = $db_conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch distinct categories
$catRes = $db_conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$allCategories = [];
if ($catRes) {
    while ($row = $catRes->fetch_assoc()) {
        $allCategories[] = $row['category'];
    }
}

// For re-opening a modal after a validation error redirect
$modalMode   = htmlspecialchars($_GET['modal'] ?? '');
$editId      = (int)($_GET['id'] ?? 0);
$editProduct = ($modalMode === 'edit' && $editId > 0) ? getProductById($editId) : null;

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

$currentPage  = 'products';
$pageTitle    = 'Products – Pomegranate';
$badgeOptions = ['', 'New', 'Sale', 'Best Seller', 'Limited'];
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
</head>
<body class="admin-body">

<div class="admin-layout">
    <?php include __DIR__ . '/inc/sidebar.php'; ?>

    <div class="admin-content">

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
                                <a href="/admin/products.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

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
                                            <a href="/admin/products.php">Clear filters</a>
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
                                                <img src="<?= h($p['image_url']) ?>"
                                                     alt="<?= h($p['name']) ?>"
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

                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

        </div></div></div><div class="modal fade" id="addProductModal" tabindex="-1"
     aria-labelledby="addProductModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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


<div class="modal fade" id="editProductModal" tabindex="-1"
     aria-labelledby="editProductModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * Pre-fill the Edit Product modal using data-* attributes from the clicked Edit button.
 */
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editProductModal');

    editModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;

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
    new bootstrap.Modal(document.getElementById('addProductModal')).show();
    <?php elseif ($modalMode === 'edit' && $editProduct): ?>
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