<?php
require_once "inc/auth.inc.php";

$canManage = isEmployee() || isAdmin();
$canShop   = !isEmployee() && !isAdmin();

// Handle employee/admin product actions (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManage) {
    $action = $_POST['action'] ?? '';
    try {
        $pdo = getDB();

        if ($action === 'add_product') {
            $name     = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
            $desc     = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
            $price    = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
            $saleP    = ($_POST['sale_price'] ?? '') !== '' ? filter_input(INPUT_POST, 'sale_price', FILTER_VALIDATE_FLOAT) : null;
            $stock    = max(0, (int)($_POST['stock'] ?? 0));
            $catId    = (int)($_POST['category_id'] ?? 0) ?: null;
            $featured = isset($_POST['featured']) ? 1 : 0;

            if ($name && $price !== false && $price >= 0) {
                $stmt = $pdo->prepare("INSERT INTO products (name,description,price,sale_price,stock,category_id,featured,created_by) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$name, $desc, $price, $saleP, $stock, $catId, $featured, getUserId()]);
                header('Location: catalog.php?ok=added');
            } else {
                header('Location: catalog.php?err=invalid');
            }
            exit;

        } elseif ($action === 'delete_product') {
            $id = (int)($_POST['product_id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("UPDATE products SET is_active=0 WHERE id=?")->execute([$id]);
            }
            header('Location: catalog.php?ok=deleted');
            exit;
        }

    } catch (Exception $e) {
        header('Location: catalog.php?err=db');
        exit;
    }
}

// Fetch data
$products   = [];
$categories = [];

try {
    $pdo = getDB();
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

    $catFilter = $_GET['cat']  ?? 'all';
    $search    = trim($_GET['q']   ?? '');
    $sort      = $_GET['sort'] ?? 'default';

    $sql    = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1";
    $params = [];

    if ($catFilter !== 'all') {
        $sql .= " AND c.slug = ?";
        $params[] = $catFilter;
    }
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $orderMap = [
        'price_asc'  => 'COALESCE(p.sale_price,p.price) ASC',
        'price_desc' => 'COALESCE(p.sale_price,p.price) DESC',
        'newest'     => 'p.created_at DESC',
        'default'    => 'p.featured DESC, p.created_at DESC',
    ];
    $sql .= " ORDER BY " . ($orderMap[$sort] ?? $orderMap['default']);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch (Exception $e) {
    // DB not connected – will show placeholder UI
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalog – Pomegranate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<?php include "inc/nav.inc.php"; ?>

<main id="main-content">
<!-- ═══════════════════════════════════════════════════════════
     CATALOG HEADER
═══════════════════════════════════════════════════════════ -->
<section class="catalog-header position-relative overflow-hidden">
    <div class="orb orb-cyan" style="width:400px;height:400px;top:-30%;left:-5%;"></div>
    <div class="orb orb-purple" style="width:350px;height:350px;top:-20%;right:-5%;"></div>

    <div class="position-relative" style="z-index:2;">
        <?php if ($canManage): ?>
        <div class="section-label"><i class="bi bi-pencil-square"></i> Product Management</div>
        <h1 class="section-heading">Manage <span class="text-gradient">Catalog</span></h1>
        <p class="text-white-50 mt-2" style="max-width:480px;margin:auto;">
            Add, update, or remove products from the storefront.
        </p>
        <div class="d-flex justify-content-center gap-2 mt-4">
            <button type="button" class="btn-primary-glow" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg"></i> Add New Product
            </button>
            <a href="<?= isAdmin() ? '/admin/index.php' : '/employee/index.php' ?>" class="btn btn-ghost">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </div>
        <?php else: ?>
        <div class="section-label"><i class="bi bi-grid-3x3-gap"></i> Browse All</div>
        <h1 class="section-heading">Our <span class="text-gradient">Catalog</span></h1>
        <p class="text-white-50 mt-2" style="max-width:480px;margin:auto;">
            Discover the full range of Pomegranate products. Cutting-edge tech, curated for you.
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     ALERTS
═══════════════════════════════════════════════════════════ -->
<?php if (isset($_GET['ok'])): ?>
<div class="container mt-3">
    <div class="alert-success-dark p-3 auto-dismiss d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i>
        <?= $_GET['ok'] === 'added' ? 'Product added to catalog.' : 'Product removed from catalog.' ?>
    </div>
</div>
<?php elseif (isset($_GET['err'])): ?>
<div class="container mt-3">
    <div class="alert-error-dark p-3 auto-dismiss d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= $_GET['err'] === 'invalid' ? 'Invalid product data. Please check all fields.' : 'A database error occurred.' ?>
    </div>
</div>
<?php endif; ?>

<!-- Employee/admin restriction notice -->
<?php if ($canManage): ?>
<div class="container mt-2 mb-0">
    <div class="d-flex align-items-center gap-2 p-2 px-3 rounded-3" style="background:rgba(34,211,238,.06);border:1px solid rgba(34,211,238,.18);font-size:.82rem;">
        <i class="bi bi-info-circle" style="color:#22d3ee;"></i>
        <span class="text-white-50">
            Staff view — shopping cart is disabled. Use the product controls below to manage inventory.
        </span>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     FILTERS + GRID
═══════════════════════════════════════════════════════════ -->
<section style="padding:2rem 0 5rem;">
    <div class="container">
        <!-- Filter bar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="filter-bar">
                <button type="button" class="filter-btn active" data-filter="all">All</button>
                <?php foreach ($categories as $cat): ?>
                <button type="button" class="filter-btn" data-filter="<?= h($cat['slug']) ?>">
                    <?= h($cat['name']) ?>
                </button>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <button type="button" class="filter-btn" data-filter="smartphones">Smartphones</button>
                <button type="button" class="filter-btn" data-filter="laptops">Laptops</button>
                <button type="button" class="filter-btn" data-filter="accessories">Accessories</button>
                <button type="button" class="filter-btn" data-filter="wearables">Wearables</button>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="search-bar-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="product-search" class="form-control-dark"
                           placeholder="Search products…"
                           style="padding-left:2.5rem;width:220px;"
                           aria-label="Search products"
                           value="<?= h($search ?? '') ?>">
                </div>
                <select id="sort-select" class="form-control-dark" style="width:auto;cursor:pointer;"
                        aria-label="Sort catalog products"
                        onchange="location.href='catalog.php?sort='+this.value+'&cat=<?= h($catFilter??'all') ?>&q=<?= h($search??'') ?>'">
                    <option value="default"    <?= ($sort??'')==='default'   ?'selected':'' ?>>Default</option>
                    <option value="newest"     <?= ($sort??'')==='newest'    ?'selected':'' ?>>Newest</option>
                    <option value="price_asc"  <?= ($sort??'')==='price_asc' ?'selected':'' ?>>Price: Low → High</option>
                    <option value="price_desc" <?= ($sort??'')==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                </select>
            </div>
        </div>

        <?php
        if (empty($products)) {
            $products = [
                ['id'=>1,'name'=>'NeoPulse X1','description'=>'Flagship smartphone with 200MP camera and 5G connectivity.','price'=>1299.00,'sale_price'=>1099.00,'stock'=>45,'cat_name'=>'Smartphones','cat_slug'=>'smartphones','image'=>'assets/phone.jpg','featured'=>1],
                ['id'=>2,'name'=>'NeoPulse X1 Pro','description'=>'Pro-grade titanium smartphone with satellite connectivity.','price'=>1599.00,'sale_price'=>null,'stock'=>20,'cat_name'=>'Smartphones','cat_slug'=>'smartphones','image'=>'assets/phone-berries.jpg','featured'=>1],
                ['id'=>3,'name'=>'NeoPulse A5','description'=>'Mid-range flagship features at a great price point.','price'=>699.00,'sale_price'=>599.00,'stock'=>80,'cat_name'=>'Smartphones','cat_slug'=>'smartphones','image'=>'assets/phone-blue.jpg','featured'=>0],
                ['id'=>4,'name'=>'UltraBook Pro 16','description'=>'16" OLED workstation laptop with Intel Core Ultra 9.','price'=>2499.00,'sale_price'=>null,'stock'=>15,'cat_name'=>'Laptops','cat_slug'=>'laptops','image'=>'assets/cat.jpg','featured'=>1],
                ['id'=>5,'name'=>'SlimAir 13','description'=>'Ultra-thin 13" laptop at just 890g.','price'=>1199.00,'sale_price'=>999.00,'stock'=>30,'cat_name'=>'Laptops','cat_slug'=>'laptops','image'=>'assets/cat.jpg','featured'=>0],
                ['id'=>6,'name'=>'ArcWatch Ultra','description'=>'Smartwatch with ECG and 14-day battery.','price'=>599.00,'sale_price'=>null,'stock'=>60,'cat_name'=>'Wearables','cat_slug'=>'wearables','image'=>'assets/cat.jpg','featured'=>1],
                ['id'=>7,'name'=>'SoundPods Pro','description'=>'True wireless ANC earbuds — 36h total battery.','price'=>299.00,'sale_price'=>249.00,'stock'=>120,'cat_name'=>'Accessories','cat_slug'=>'accessories','image'=>'assets/cat.jpg','featured'=>0],
                ['id'=>8,'name'=>'MagCharge 3-in-1','description'=>'Wireless charging pad for phone, watch, and earbuds.','price'=>89.00,'sale_price'=>null,'stock'=>200,'cat_name'=>'Accessories','cat_slug'=>'accessories','image'=>'assets/cat.jpg','featured'=>0],
            ];
        }
        ?>

        <!-- Product grid -->
        <div class="row g-4" id="product-grid">
            <?php foreach ($products as $i => $p):
                $displayPrice = $p['sale_price'] ? $p['sale_price'] : $p['price'];
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 product-card-wrap reveal delay-<?= min(($i%4)+1,5) ?>"
                 data-category="<?= h($p['cat_slug'] ?? '') ?>"
                 data-name="<?= strtolower(h($p['name'])) ?>">
                <div class="glass-card h-100 position-relative">

                    <?php if ($canManage): ?>
                    <!-- Employee/admin delete overlay -->
                    <div class="position-absolute top-0 end-0 p-2" style="z-index:10;">
                        <form method="POST" action="/catalog.php"
                              onsubmit="return confirm('Remove \'<?= addslashes(h($p['name'])) ?>\' from the catalog?')">
                            <input type="hidden" name="action"     value="delete_product">
                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-sm"
                                    title="Remove product" aria-label="Remove product <?= h($p['name']) ?>"
                                    style="background:rgba(248,113,113,.18);border:1px solid rgba(248,113,113,.35);color:#f87171;border-radius:.5rem;padding:.25rem .5rem;line-height:1;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="product-img-wrap">
                        <img src="<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy">

                        <?php if ($p['sale_price']): ?>
                            <span class="product-badge badge-sale">Sale</span>
                        <?php elseif ($p['featured']): ?>
                            <span class="product-badge badge-featured">Featured</span>
                        <?php endif; ?>

                        <?php if ((int)($p['stock']) === 0): ?>
                        <span class="product-badge" style="top:auto;bottom:.75rem;left:.75rem;background:rgba(248,113,113,.2);color:#f87171;border:1px solid rgba(248,113,113,.3);">Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-body">
                        <div class="product-category"><?= h($p['cat_name'] ?? '') ?></div>
                        <div class="product-name"><?= h($p['name']) ?></div>
                        <div class="product-desc"><?= h($p['description']) ?></div>
                        <div class="product-price-row">
                            <span class="price-current">$<?= number_format((float)$displayPrice, 2) ?></span>
                            <?php if ($p['sale_price']): ?>
                            <span class="price-original">$<?= number_format((float)$p['price'], 2) ?></span>
                            <span class="gap-pill" style="font-size:.7rem;color:#34d399;border-color:rgba(52,211,153,.3);">
                                -<?= round((1 - $p['sale_price']/$p['price'])*100) ?>%
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($canManage): ?>
                        <!-- Staff: show stock count + edit link instead of cart -->
                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <span class="text-white-50" style="font-size:.78rem;">
                                <i class="bi bi-archive" style="color:#22d3ee;"></i>
                                Stock: <strong class="text-white"><?= (int)$p['stock'] ?></strong>
                            </span>
                            <a href="<?= isAdmin() ? '/admin/products.php' : '/employee/products.php' ?>?edit=<?= (int)$p['id'] ?>"
                               class="btn btn-sm"
                               style="background:rgba(129,140,248,.15);border:1px solid rgba(129,140,248,.3);color:#818cf8;border-radius:.5rem;font-size:.75rem;padding:.25rem .6rem;">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </div>
                        <?php elseif ((int)($p['stock'] ?? 1) > 0): ?>
                        <a href="product.php?id=<?= (int)$p['id'] ?>" class="btn-add-cart">
                            <i class="bi bi-bag-plus"></i> Add to Cart
                        </a>
                        <?php else: ?>
                        <button type="button" class="btn-add-cart" disabled style="opacity:.5;cursor:not-allowed;">
                            <i class="bi bi-slash-circle"></i> Out of Stock
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-search text-white-50" style="font-size:3rem;"></i>
            <p class="text-white-50 mt-3">No products found matching your search.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
</main>

<?php include "inc/footer.inc.php"; ?>

<!-- ═══════════════════════════════════════════════════════════
     ADD PRODUCT MODAL (employees/admins only)
═══════════════════════════════════════════════════════════ -->
<?php if ($canManage): ?>
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#0c1220;border:1px solid rgba(255,255,255,.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.08);">
                <h5 class="modal-title text-white fw-bold" id="addProductModalLabel">
                    <i class="bi bi-plus-circle me-2" style="color:#22d3ee;"></i>Add New Product
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="/catalog.php">
                <input type="hidden" name="action" value="add_product">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="catalog_name" class="form-label text-white-50 small fw-semibold">Product Name *</label>
                            <input id="catalog_name" type="text" name="name" class="form-control-dark" placeholder="e.g. NeoPulse X2" required>
                        </div>
                        <div class="col-12">
                            <label for="catalog_description" class="form-label text-white-50 small fw-semibold">Description</label>
                            <textarea id="catalog_description" name="description" class="form-control-dark" rows="3"
                                      placeholder="Brief product description…"
                                      style="resize:vertical;"></textarea>
                        </div>
                        <div class="col-sm-4">
                            <label for="catalog_price" class="form-label text-white-50 small fw-semibold">Price ($) *</label>
                            <input id="catalog_price" type="number" name="price" class="form-control-dark"
                                   placeholder="0.00" min="0" step="0.01" required>
                        </div>
                        <div class="col-sm-4">
                            <label for="catalog_sale_price" class="form-label text-white-50 small fw-semibold">Sale Price ($)</label>
                            <input id="catalog_sale_price" type="number" name="sale_price" class="form-control-dark"
                                   placeholder="Leave blank if none" min="0" step="0.01">
                        </div>
                        <div class="col-sm-4">
                            <label for="catalog_stock" class="form-label text-white-50 small fw-semibold">Stock</label>
                            <input id="catalog_stock" type="number" name="stock" class="form-control-dark"
                                   placeholder="0" min="0" value="0">
                        </div>
                        <div class="col-sm-8">
                            <label for="catalog_category_id" class="form-label text-white-50 small fw-semibold">Category</label>
                            <select id="catalog_category_id" name="category_id" class="form-control-dark">
                                <option value="">— No category —</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"><?= h($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-4 d-flex align-items-end pb-1">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredCheck" value="1">
                                <label class="form-check-label text-white-50 small" for="featuredCheck">Featured product</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.08);">
                    <button type="button" class="btn-dash-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-glow" style="padding:.5rem 1.25rem;">
                        <i class="bi bi-plus-lg"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Auto-dismiss alerts
document.querySelectorAll('.auto-dismiss').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    }, 4000);
});
</script>
</body>
</html>
