<?php
require_once "inc/auth.inc.php";

// Fetch all active products + categories
$products   = [];
$categories = [];

try {
    $pdo = getDB();

    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

    $catFilter  = $_GET['cat'] ?? 'all';
    $search     = trim($_GET['q'] ?? '');
    $sort       = $_GET['sort'] ?? 'default';

    $sql  = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.is_active=1";
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

<!-- ═══════════════════════════════════════════════════════════
     CATALOG HEADER
═══════════════════════════════════════════════════════════ -->
<section class="catalog-header position-relative overflow-hidden">
    <div class="orb orb-cyan" style="width:400px;height:400px;top:-30%;left:-5%;"></div>
    <div class="orb orb-purple" style="width:350px;height:350px;top:-20%;right:-5%;"></div>

    <div class="position-relative" style="z-index:2;">
        <div class="section-label"><i class="bi bi-grid-3x3-gap"></i> Browse All</div>
        <h1 class="section-heading">Our <span class="text-gradient">Catalog</span></h1>
        <p class="text-white-50 mt-2" style="max-width:480px;margin:auto;">
            Discover the full range of Pomegranate products. Cutting-edge tech, curated for you.
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     FILTERS + GRID
═══════════════════════════════════════════════════════════ -->
<section style="padding:2rem 0 5rem;">
    <div class="container">
        <!-- Filter bar -->
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="filter-bar">
                <button class="filter-btn active" data-filter="all">All</button>
                <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-filter="<?= h($cat['slug']) ?>">
                    <?= h($cat['name']) ?>
                </button>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <button class="filter-btn" data-filter="smartphones">Smartphones</button>
                <button class="filter-btn" data-filter="laptops">Laptops</button>
                <button class="filter-btn" data-filter="accessories">Accessories</button>
                <button class="filter-btn" data-filter="wearables">Wearables</button>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Search -->
                <div class="search-bar-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="product-search" class="form-control-dark"
                           placeholder="Search products…"
                           style="padding-left:2.5rem;width:220px;"
                           value="<?= h($search ?? '') ?>">
                </div>
                <!-- Sort -->
                <select id="sort-select" class="form-control-dark" style="width:auto;cursor:pointer;"
                        onchange="location.href='catalog.php?sort='+this.value+'&cat=<?= h($catFilter??'all') ?>&q=<?= h($search??'') ?>'">
                    <option value="default"    <?= ($sort??'')==='default'   ?'selected':'' ?>>Default</option>
                    <option value="newest"     <?= ($sort??'')==='newest'    ?'selected':'' ?>>Newest</option>
                    <option value="price_asc"  <?= ($sort??'')==='price_asc' ?'selected':'' ?>>Price: Low → High</option>
                    <option value="price_desc" <?= ($sort??'')==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                </select>
            </div>
        </div>

        <?php
        // Fallback placeholder products when DB not connected
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
                <div class="glass-card h-100">
                    <div class="product-img-wrap">
                        <img src="<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" loading="lazy">

                        <?php if ($p['sale_price']): ?>
                            <span class="product-badge badge-sale">Sale</span>
                        <?php elseif ($p['featured']): ?>
                            <span class="product-badge badge-featured">Featured</span>
                        <?php endif; ?>

                        <!-- Stock badge -->
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

                        <?php if ((int)($p['stock'] ?? 1) > 0): ?>
                        <a href="product.php?id=<?= (int)$p['id'] ?>" class="btn-add-cart">
                            <i class="bi bi-bag-plus"></i> Add to Cart
                        </a>
                        <?php else: ?>
                        <button class="btn-add-cart" disabled style="opacity:.5;cursor:not-allowed;">
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

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
