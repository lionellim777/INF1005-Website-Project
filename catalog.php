<?php
require_once __DIR__ . '/auth/auth_helper.php';
initSession();

$catalogUser       = isLoggedIn() ? currentUser() : null;
$canManageCatalog  = $catalogUser && (int)$catalogUser['role_id'] >= 3; // employee/admin
$isEmployee        = $catalogUser && (int)$catalogUser['role_id'] === 3;
$canPurchase       = !$isEmployee;

//MOCK DATA
$all_products = [
    [
        'id'        => 1,
        'name'      => 'Wireless Headphones',
        'desc'      => 'Test',
        'price'     => 299.00,
        'old_price' => 349.00,
        'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&q=80',
        'category'  => 'Guinevere',
        'badge'     => 'Sale',
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 2,
        'name'      => 'Minimalist Leather Watch',
        'desc'      => 'Test',
        'price'     => 189.00,
        'old_price' => null,
        'image_url' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&q=80',
        'category'  => 'Guinevere',
        'badge'     => 'New',
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 3,
        'name'      => 'Pour-Over Coffee Set',
        'desc'      => 'Test',
        'price'     => 64.00,
        'old_price' => null,
        'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&q=80',
        'category'  => 'Thamuz',
        'badge'     => null,
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 4,
        'name'      => 'Linen Throw Blanket',
        'desc'      => 'Test',
        'price'     => 95.00,
        'old_price' => 120.00,
        'image_url' => 'https://images.unsplash.com/photo-1580301762395-21ce84d00bc6?w=400&q=80',
        'category'  => 'Thamuz',
        'badge'     => 'Sale',
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 5,
        'name'      => 'Mechanical Keyboard TKL',
        'desc'      => 'Test',
        'price'     => 145.00,
        'old_price' => null,
        'image_url' => 'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?w=400&q=80',
        'category'  => 'Esmeralda',
        'badge'     => 'New',
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 6,
        'name'      => 'Stoneware Dinner Set',
        'desc'      => 'Test',
        'price'     => 112.00,
        'old_price' => null,
        'image_url' => 'https://images.unsplash.com/photo-1603199506016-b9a594b593c0?w=400&q=80',
        'category'  => 'Esmeralda',
        'badge'     => null,
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 8,
        'name'      => 'Portable Bluetooth Speaker',
        'desc'      => 'Test',
        'price'     => 79.00,
        'old_price' => 99.00,
        'image_url' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=400&q=80',
        'category'  => 'Sora',
        'badge'     => 'Sale',
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 9,
        'name'      => 'Bamboo Desk Organiser',
        'desc'      => 'Test',
        'price'     => 52.00,
        'old_price' => null,
        'image_url' => 'https://images.unsplash.com/photo-1593642632559-0c6d3fc62b89?w=400&q=80',
        'category'  => 'Argus',
        'badge'     => null,
        'model'     =>'models/product.glb'
    ],
    [
        'id'        => 10,
        'name'      => 'Running Shoes Trail Pro',
        'desc'      => 'Test',
        'price'     => 220.00,
        'old_price' => 260.00,
        'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80',
        'category'  => 'Argus',
        'badge'     => 'Sale',
        'model'     =>'models/product.glb'
    ],
];

$all_reviews = [
    [
        'product_id' => 1,
        'initial'    => 'JM',
        'name'       => 'Jamie M.',
        'stars'      => 5,
        'date'       => '2025-03-01',
        'body'       => 'Absolutely love it. Build quality is exceptional.'
    ],
    [
        'product_id' => 1,
        'initial'    => 'SR',
        'name'       => 'Sara R.',
        'stars'      => 4,
        'date'       => '2025-02-01',
        'body'       => 'Great product. Slight delay but worth it.'
    ],
    [
        'product_id' => 2,
        'initial'    => 'TK',
        'name'       => 'Tom K.',
        'stars'      => 4,
        'date'       => '2025-01-01',
        'body'       => 'Solid craftsmanship.'
    ],
];

if (!isset($_SESSION['catalog_custom_products']) || !is_array($_SESSION['catalog_custom_products'])) {
    $_SESSION['catalog_custom_products'] = [];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (string)($_POST['action'] ?? '') === 'add_catalog_item') {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid form submission.');
    } elseif (!$canManageCatalog) {
        setFlash('error', 'You do not have permission to add catalog items.');
    } else {
        $name      = trim((string)($_POST['name'] ?? ''));
        $category  = trim((string)($_POST['category'] ?? ''));
        $desc      = trim((string)($_POST['desc'] ?? ''));
        $imageUrl  = trim((string)($_POST['image_url'] ?? ''));
        $priceRaw  = trim((string)($_POST['price'] ?? ''));
        $oldRaw    = trim((string)($_POST['old_price'] ?? ''));
        $badgeRaw  = trim((string)($_POST['badge'] ?? ''));

        $price = is_numeric($priceRaw) ? (float)$priceRaw : null;
        $oldPrice = ($oldRaw === '') ? null : (is_numeric($oldRaw) ? (float)$oldRaw : 'invalid');
        $badge = in_array($badgeRaw, ['New', 'Sale'], true) ? $badgeRaw : null;

        $errors = [];
        if ($name === '' || strlen($name) < 2) $errors[] = 'Product name must be at least 2 characters.';
        if ($category === '' || strlen($category) < 2) $errors[] = 'Category must be at least 2 characters.';
        if ($price === null || $price <= 0) $errors[] = 'Price must be a positive number.';
        if ($oldPrice === 'invalid' || ($oldPrice !== null && $oldPrice < 0)) $errors[] = 'Old price must be empty or a non-negative number.';
        if ($desc === '') $desc = 'Newly added item.';
        if ($imageUrl === '') $imageUrl = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&q=80';

        if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://') && !str_starts_with($imageUrl, 'assets/')) {
            $errors[] = 'Image URL must start with http://, https://, or assets/.';
        }

        if ($errors) {
            setFlash('error', implode(' ', $errors));
        } else {
            $allCurrent = array_merge($all_products, $_SESSION['catalog_custom_products']);
            $ids = array_column($allCurrent, 'id');
            $nextId = empty($ids) ? 1 : (max($ids) + 1);

            $_SESSION['catalog_custom_products'][] = [
                'id'        => $nextId,
                'name'      => $name,
                'desc'      => $desc,
                'price'     => $price,
                'old_price' => ($oldPrice === null ? null : (float)$oldPrice),
                'image_url' => $imageUrl,
                'category'  => $category,
                'badge'     => $badge,
                'model'     => 'models/product.glb',
            ];

            logActivity((int)$catalogUser['user_id'], 'catalog_add_item', "Added catalog item: {$name}");
            setFlash('success', 'Catalog item added successfully.');
        }
    }

    header('Location: ' . appUrl('/catalog.php'));
    exit;
}

$all_products = array_merge($all_products, $_SESSION['catalog_custom_products']);

$selected = isset($_GET['category']) ? $_GET['category'] : 'all';
$categories = array_unique(array_column($all_products, 'category'));
sort($categories);

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
    <title>Assignment 2 Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    />
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php
        include "inc/nav.inc.php";
    ?>

    <div class="hero mb-0">
        <img src="<?= appUrl('/assets/esmeralda.jpg') ?>" class="hero-img" alt="Esmeralda">
        <div class="hero-overlay position-absolute text-center">
            <h1 class="display-4 fw-bold text-white text-uppercase">Our Products</h1>
            <p class="text-white">Discover the latest technological trends and keep up to date</p>
        </div>
    </div>

    <div class="container mt-4">
        <?= renderFlash() ?>
    </div>

    <?php if ($canManageCatalog): ?>
    <div class="container-fluid px-4 mt-2">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0">Add New Catalog Item</h6>
                <small class="text-muted">Employee/Admin tool (session-based in offline mode)</small>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= appUrl('/catalog.php') ?>" class="row g-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_catalog_item">

                    <div class="col-md-4">
                        <label class="form-label" for="name">Product Name</label>
                        <input id="name" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="category">Category</label>
                        <input id="category" name="category" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="price">Price</label>
                        <input id="price" name="price" type="number" min="0.01" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="old_price">Old Price</label>
                        <input id="old_price" name="old_price" type="number" min="0" step="0.01" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" for="badge">Badge</label>
                        <select id="badge" name="badge" class="form-select">
                            <option value="">None</option>
                            <option value="New">New</option>
                            <option value="Sale">Sale</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="image_url">Image URL</label>
                        <input id="image_url" name="image_url" class="form-control" placeholder="https://... or assets/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="desc">Description</label>
                        <input id="desc" name="desc" class="form-control" placeholder="Short description">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn text-white" style="background-color:#28666e;">
                            <i class="bi bi-plus-circle me-1"></i>Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="filter-bar px-4 py-3 mb-4">
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
            <span class="filter-label text-muted">Filters</span>

            <div class="d-flex gap-2 flex-wrap justify-content-center">
                <button class="rounded-pill active" data-filter="all">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="rounded-pill" data-filter="<?= htmlspecialchars($cat) ?>">
                        <?= htmlspecialchars($cat) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="ms-auto">
                <select id="sortSelect" class="form-select form-select-sm">
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
            <div class="col animated" data-category="<?= htmlspecialchars($product['category']) ?>"
                data-price="<?= $product['price'] ?>">
                <div class="card h-100 border-0 shadow-sm" >

                    <div class="position-relative">
                        <img src="<?=htmlspecialchars($product['image_url']) ?>"
                                class="card-img-top object-fit-cover"
                                alt="<?=htmlspecialchars($product['name']) ?>">
                        <?php if ($product['badge']): ?>
                            <span class="badge position-absolute top-0 end-0
                                <?=$product['badge']==='New' ? 'bg-success' : 'bg-danger' ?>">
                                <?=htmlspecialchars($product['badge']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <small class="card-text-category text-uppercase text-muted fw-semibold">
                            <?= htmlspecialchars($product['category']) ?>
                        </small>
                        <h6 class="card-title mt-1 fw-semibold">
                            <?= htmlspecialchars($product['name']) ?>
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
                            data-can-purchase="<?= $canPurchase ? '1' : '0' ?>"
                            data-name="<?= htmlspecialchars($product['name']) ?>"
                            data-price="<?= number_format($product['price'], 2) ?>"
                            data-oldprice="<?= $product['old_price'] ? number_format($product['old_price'], 2) : '' ?>"
                            data-category="<?= htmlspecialchars($product['category']) ?>"
                            data-image="<?= htmlspecialchars($product['image_url']) ?>"
                            data-desc="<?= htmlspecialchars($product['desc']) ?>"
                            data-model="<?= htmlspecialchars($product['model']) ?>">
                            <i class="bi bi-bag me-1"></i> View
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
        include "inc/footer.inc.php";
    ?>

    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="height:90vh;">
            <div class="modal-content border-0 rounded-4 overflow-hidden h-100">
                <div class="row g-0 h-100">

                    <!-- Three.js -->
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
                            <span class="input-group-text bg-white justify-content-center flex-grow-1" id="qtyValue">1</span>
                            <?php if ($canPurchase): ?>
                                <button id="purchaseBtn" class="modal-btn btn btn-dark flex-grow-1"><i class="bi bi-bag me-1"></i> Add to Cart</button>
                            <?php else: ?>
                                <button id="purchaseBtn" class="modal-btn btn btn-secondary flex-grow-1" disabled title="Employees cannot purchase items">
                                    <i class="bi bi-ban me-1"></i> Purchase Disabled
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($isEmployee): ?>
                            <small class="text-danger">Employee accounts can add catalog items but cannot purchase products.</small>
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
                
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm"
        crossorigin="anonymous">
    </script>
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
    <script defer type="module" src="<?= appUrl('/js/main.js') ?>"></script>
</body>
</html>
