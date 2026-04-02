<?php
/**
 * Admin/Employee – Product Management & File Uploads
 */
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/auth_middleware.php';

require_role(['admin', 'employee']);

$successMsg  = '';
$errorMsg    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_product') {
        $id = (int)($_POST['product_id'] ?? 0);
        $stmt = $db_conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) $successMsg = "Product deleted successfully.";
        else $errorMsg = "Failed to delete product.";
    } 
    elseif ($action === 'add_product' || $action === 'edit_product') {
        $id          = (int)($_POST['product_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $desc        = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $oldPrice    = (float)($_POST['old_price'] ?? 0) ?: null;
        $category    = trim($_POST['category'] ?? '');
        $badge       = trim($_POST['badge'] ?? '') ?: null;
        $stock       = (int)($_POST['stock'] ?? 0);

        // --- FILE UPLOAD LOGIC ---
        // 1. Handle Image Upload
        $imageUrl = $_POST['existing_image_url'] ?? '';
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
            $filename = 'prod_' . time() . '_' . rand(100,999) . '.' . $ext;
            $dest = dirname(__DIR__) . '/assets/' . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                $imageUrl = 'assets/' . $filename;
            }
        }

        // 2. Handle 3D GLB Upload
        $modelPath = $_POST['existing_model'] ?? null;
        if (isset($_FILES['model_file']) && $_FILES['model_file']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['model_file']['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) === 'glb') {
                $filename = 'model_' . time() . '_' . rand(100,999) . '.glb';
                $dest = dirname(__DIR__) . '/models/' . $filename;
                if (move_uploaded_file($_FILES['model_file']['tmp_name'], $dest)) {
                    $modelPath = 'models/' . $filename;
                }
            } else {
                $errorMsg = "3D Model must be a .glb file.";
            }
        }

        if (strlen($name) < 2 || $price <= 0) {
            $errorMsg = "Invalid input: Name must be >= 2 chars, Price > 0.";
        } elseif (!$errorMsg) {
            if ($action === 'add_product') {
                $stmt = $db_conn->prepare("INSERT INTO products (name, description, price, old_price, image_url, category, badge, model, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssddssssi", $name, $desc, $price, $oldPrice, $imageUrl, $category, $badge, $modelPath, $stock);
                if ($stmt->execute()) $successMsg = "Product added successfully.";
                else $errorMsg = "Failed to add product.";
            } else {
                $stmt = $db_conn->prepare("UPDATE products SET name=?, description=?, price=?, old_price=?, image_url=?, category=?, badge=?, model=?, stock=? WHERE id=?");
                $stmt->bind_param("ssddssssii", $name, $desc, $price, $oldPrice, $imageUrl, $category, $badge, $modelPath, $stock, $id);
                if ($stmt->execute()) $successMsg = "Product updated successfully.";
                else $errorMsg = "Failed to update product.";
            }
        }
    }
}

// FETCH ALL PRODUCTS
$search = $_GET['search'] ?? '';
$sql = 'SELECT * FROM products WHERE 1=1';
$params = [];
$types = "";

if ($search) {
    $sql .= ' AND (name LIKE ? OR category LIKE ?)';
    $like = "%{$search}%";
    $params = [$like, $like];
    $types .= "ss";
}
$sql .= ' ORDER BY id DESC';

$stmt = $db_conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$allProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$currentPage = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products – Admin – Pomegranate</title>
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
                <h4 class="page-title mb-0"><i class="bi bi-phone me-2"></i>Product Catalog</h4>
                <small class="page-text">Manage store listings and upload files</small>
            </div>
            <button class="btn-dash-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="prepareAdd()">
                <i class="bi bi-plus-lg"></i> Add Product
            </button>
        </div>

        <div class="dash-content">
            <?php if ($successMsg): ?><div class="alert alert-success border-0 shadow-sm"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
            <?php if ($errorMsg): ?><div class="alert alert-danger border-0 shadow-sm"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

            <div class="dash-table-wrap shadow-sm">
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name & Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>3D Model</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allProducts as $p): ?>
                                <tr>
                                    <td>
                                        <div style="width:48px;height:48px;border-radius:8px;overflow:hidden;background:#fff;">
                                            <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="img" style="width:100%;height:100%;object-fit:cover;">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white d-flex align-items-center gap-2">
                                            <?= htmlspecialchars($p['name']) ?>
                                            <?php if (!empty($p['badge'])): ?><span class="badge bg-danger" style="font-size:0.6rem;"><?= htmlspecialchars($p['badge']) ?></span><?php endif; ?>
                                        </div>
                                        <div class="text-white small"><?= htmlspecialchars($p['category']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-white">$<?= number_format((float)$p['price'], 2) ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= (int)$p['stock'] ?> in stock</span></td>
                                    <td>
                                        <?php if (!empty($p['model'])): ?>
                                            <span class="badge bg-info text-dark"><i class="bi bi-box"></i> Included</span>
                                        <?php else: ?>
                                            <span class="text-white small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button class="btn btn-sm btn-outline-info me-1" onclick="openEditModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Delete <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">    
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--bg-secondary); border: 1px solid var(--border);">
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title fw-bold text-white" id="modalTitle">Add Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" id="modalAction" value="add_product">
                <input type="hidden" name="product_id" id="modalProductId" value="">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Product Name *</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="name" id="modalName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Category *</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="category" id="modalCategory" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50 small">Price ($) *</label>
                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" name="price" id="modalPrice" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50 small">Old Price ($)</label>
                            <input type="number" step="0.01" class="form-control bg-dark text-white border-secondary" name="old_price" id="modalOldPrice">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50 small">Stock Quantity *</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="stock" id="modalStock" required value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50 small">Description</label>
                            <textarea class="form-control bg-dark text-white border-secondary" name="description" id="modalDesc" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Badge Text (Optional)</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="badge" id="modalBadge" placeholder="e.g. New, Sale">
                        </div>

                        <div class="col-12 mt-4"><hr class="border-secondary"></div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">Product Image (JPG/PNG)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="image_file" accept="image/*">
                            <input type="hidden" name="existing_image_url" id="existingImage">
                            <div class="text-white small mt-1" id="currentImageText"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">3D Model (.GLB Only)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="model_file" accept=".glb">
                            <input type="hidden" name="existing_model" id="existingModel">
                            <div class="text-white small mt-1" id="currentModelText"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-dash-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    
    function prepareAdd() {
        document.getElementById('modalTitle').textContent = 'Add Product';
        document.getElementById('modalAction').value = 'add_product';
        document.getElementById('modalProductId').value = '';
        document.getElementById('modalName').value = '';
        document.getElementById('modalCategory').value = '';
        document.getElementById('modalPrice').value = '';
        document.getElementById('modalOldPrice').value = '';
        document.getElementById('modalStock').value = '0';
        document.getElementById('modalDesc').value = '';
        document.getElementById('modalBadge').value = '';
        
        document.getElementById('existingImage').value = '';
        document.getElementById('currentImageText').textContent = 'Required for new products.';
        
        document.getElementById('existingModel').value = '';
        document.getElementById('currentModelText').textContent = 'Optional.';
    }

    function openEditModal(product) {
        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('modalAction').value = 'edit_product';
        document.getElementById('modalProductId').value = product.id;
        document.getElementById('modalName').value = product.name;
        document.getElementById('modalCategory').value = product.category;
        document.getElementById('modalPrice').value = product.price;
        document.getElementById('modalOldPrice').value = product.old_price || '';
        document.getElementById('modalStock').value = product.stock;
        document.getElementById('modalDesc').value = product.description;
        document.getElementById('modalBadge').value = product.badge || '';
        
        document.getElementById('existingImage').value = product.image_url;
        document.getElementById('currentImageText').textContent = product.image_url ? 'Current: ' + product.image_url + ' (Upload new to replace)' : '';
        
        document.getElementById('existingModel').value = product.model || '';
        document.getElementById('currentModelText').textContent = product.model ? 'Current: ' + product.model + ' (Upload new to replace)' : '';
        
        modal.show();
    }
</script>
</body>
</html>