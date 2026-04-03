<?php
require_once dirname(__DIR__) . '/inc/bootstrap.php';
require_once dirname(__DIR__) . '/inc/security_utils.php';

// 1. Define roles for the UI
$role = strtolower($_SESSION['role'] ?? 'guest');
$isEmployee = ($role === 'employee' || $role === 'admin');
$canPurchase = !$isEmployee; // Employees/Admins test the site but shouldn't buy things

$all_products = [];
$all_reviews = [];

// 2. Fetch Products from MySQL
if (isset($db_conn)) {
    // Note: We select `desc` specifically since it's a reserved keyword
    $prod_result = $db_conn->query("SELECT id, name, `description`, price, old_price, image_url, category, badge, model FROM products");
    if ($prod_result) {
        while ($row = $prod_result->fetch_assoc()) {
            $all_products[] = $row;
        }
    }

    // 3. Fetch Reviews from MySQL (Needed for the JS Modal)
    $rev_result = $db_conn->query("SELECT product_id, initial, name, stars, date, body FROM reviews");
    if ($rev_result) {
        while ($row = $rev_result->fetch_assoc()) {
            // Ensure stars is an integer so the JS renders the correct number of stars
            $row['stars'] = (int)$row['stars']; 
            $all_reviews[] = $row;
        }
    }
}

// 4. Category Filtering
$selected = isset($_GET['category']) ? sanitize_input($_GET['category']) : 'all';

// Extract unique categories safely
$categories = array_unique(array_column($all_products, 'category'));
sort($categories);

// Filter the products array if a specific category was clicked
$products = $selected === 'all'
    ? $all_products
    : array_filter($all_products, fn($p) => $p['category'] === $selected);

$products = array_values($products); 
$total = count($products);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pomegranate | Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= app_url('css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include dirname(__DIR__) . "/inc/nav.inc.php"; ?>

    <div class="hero mb-0">
        <img src="<?= app_url('assets/esmeralda.jpg') ?>" class="hero-img" alt="Esmeralda">
        <div class="hero-overlay position-absolute text-center">
            <h1 class="display-4 fw-bold text-white text-uppercase">Our Products</h1>
            <p class="text-white">Discover the latest technological trends and keep up to date</p>
        </div>
    </div>

    <div class="filter-bar px-4 py-3 mb-4 mt-4">
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
            <span class="filter-label text-muted">Filters</span>

            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <button class="rounded-pill active" data-filter="all">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="rounded-pill" data-filter="<?= h($cat) ?>">
                        <?= h($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="ms-auto">
                <label for="sortSelect" class="visually-hidden">Sort products</label>
                <select id="sortSelect" class="form-select form-select-sm" aria-label="Sort products">
                    <option value="default">Sort: Default</option>
                    <option value="ascending">Price: Low → High</option>
                    <option value="descending">Price: High → Low</option>
                </select>
            </div>
        </div>
    </div>

    <div class="container-fluid px-4 py-3 mb-4">
        <p class="text-muted mb-3" id="productCount">
            Showing <strong><?= count($all_products) ?></strong> products
        </p>

        <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3" id="productGrid">
            <?php foreach ($all_products as $product): ?>
            <div class="col animated" data-category="<?= h($product['category']) ?>" data-price="<?= $product['price'] ?>">
                <div class="card h-100 border-0 shadow-sm" >

                    <div class="position-relative">
                        <img src="<?= $product['image_url'] ?>" class="card-img-top object-fit-cover" alt="<?=h($product['name']) ?>">
                        <?php if ($product['badge']): ?>
                            <span class="badge position-absolute top-0 end-0 <?= $product['badge'] === 'New' ? 'bg-success' : 'bg-danger' ?>">
                                <?=h($product['badge']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <small class="card-text-category text-uppercase text-muted fw-semibold">
                            <?= h($product['category']) ?>
                        </small>
                        <h6 class="card-title mt-1 fw-semibold">
                            <?= h($product['name']) ?>
                        </h6>

                        <div class="mt-auto d-flex align-items-baseline gap-2">
                            <span class="fw-bold">$<?= number_format($product['price'], 2) ?></span>
                            <?php if ($product['old_price']): ?>
                                <small class="text-muted text-decoration-line-through">
                                    $<?= number_format($product['old_price'], 2) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-0 pb-3 px-3">
                        <button class="card-btn btn btn-dark btn-sm w-100 view-product"
                            data-bs-toggle="modal"
                            data-bs-target="#productModal"
                            data-id="<?= $product['id'] ?>"
                            data-name="<?= h($product['name']) ?>"
                            data-price="<?= number_format($product['price'], 2) ?>"
                            data-oldprice="<?= $product['old_price'] ? number_format($product['old_price'], 2) : '' ?>"
                            data-category="<?= h($product['category']) ?>"
                            data-image="<?= h($product['image_url']) ?>"
                            data-desc="<?= h($product['desc']) ?>"
                            data-model="<?= h(app_url($product['model'])) ?>"
                            data-can-purchase="<?= $canPurchase ? '1' : '0' ?>">
                            <i class="bi bi-bag me-1"></i> View
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include dirname(__DIR__) . "/inc/footer.inc.php"; ?>

    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="height:90vh;">
            <div class="modal-content border-0 rounded-4 overflow-hidden h-100">
                <div class="row g-0 h-100">

                    <div class="col-md-7 bg-dark">
                        <div id="modelShowcase" style="width:100%; height:100%;"></div>
                    </div>

                    <div class="modalRight col-md-5 d-flex flex-column overflow-y-auto p-4 bg-white position-relative">
                        <button class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>

                        <p id="modalCategory" class="text-muted text-uppercase small fw-semibold mb-1"></p>
                        <h4 id="modalName" class="fw-bold mb-2"></h4>

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span id="modalPrice" class="fs-5 fw-bold"></span>
                        </div>

                        <p id="modalDesc" class="text-muted small"></p>

                        <div class="d-flex gap-3 mt-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-grow-1 border rounded px-2">
                                <button class="btn btn-sm p-0" id="qtyMinus">−</button>
                                <span class="flex-grow-1 text-center" id="qtyValue">1</span>
                                <button class="btn btn-sm p-0" id="qtyPlus">+</button>
                            </div>
                            <button id="purchaseBtn" class="modal-btn btn btn-dark flex-grow-1"><i class="bi bi-bag me-1"></i> Add to Cart</button>
                        </div>
                        <?php if ($isEmployee): ?>
                            <small class="text-danger">Employee accounts cannot purchase products.</small>
                        <?php endif; ?>

                        <hr>
                        <h6 class="fw-bold mb-3">Customer Reviews</h6>
                        <div id="reviewSummary" class="mb-3"></div>
                        <div id="reviewList" class="d-flex flex-column gap-3"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
                
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script async src="https://unpkg.com/es-module-shims@1.8.0/dist/es-module-shims.js"></script>
    <script type="importmap">
    {
        "imports": {
            "three": "https://unpkg.com/three@0.158.0/build/three.module.js",
            "three/addons/": "https://unpkg.com/three@0.158.0/examples/jsm/"
        }
    }
    </script>
    <script>
        const ALL_REVIEWS = <?=json_encode($all_reviews)?>;
    </script>
    <script defer type="module" src="<?= app_url('js/catalog-3d.js') ?>"></script>
    <script src="<?= app_url('js/cart.js') ?>"></script>
    <script src="<?= app_url('js/main.js') ?>"></script>
</body>
</html>
