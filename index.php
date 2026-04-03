<?php
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/support.inc.php';

$featured_products = [];
if (isset($db_conn)) {
    $query = "SELECT id, name, `description`, price, image_url FROM products LIMIT 4";
    $result = $db_conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $featured_products[] = $row;
        }
    }
}

$pageTitle = 'Pomegranate | Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?></title>
    <link rel="preload" href="<?= h(app_url('assets/phone.jpg')) ?>" as="image" fetchpriority="high">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/form.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/inc/nav.inc.php'; ?>

<main>
    <div id="carouselExampleCaptions" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="<?= h(app_url('assets/phone.jpg')) ?>" class="d-block w-100" alt="Smartphone showcasing design" fetchpriority="high" loading="eager">
                <div class="carousel-caption d-none d-md-block">
                    <div class="h5">To Inspire</div>
                    <p>“Let’s go invent tomorrow instead of worrying about what happened yesterday.” – Steve Jobs</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?= h(app_url('assets/phone-berries.jpg')) ?>" class="d-block w-100" alt="Phone with berry accents" loading="lazy">
                <div class="carousel-caption d-none d-md-block">
                    <div class="h5">To Innovate</div>
                    <p>“Innovation is the outcome of a habit, not a random act.” – Sukant Ratnakar</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?= h(app_url('assets/phone-blue.jpg')) ?>" class="d-block w-100" alt="Blue smartphone" loading="lazy">
                <div class="carousel-caption d-none d-md-block">
                    <div class="h5">To Commemorate</div>
                    <p>“Technology is best when it brings people together.” – Matt Mullenweg</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="text-center py-4">
            <h1 class="fw-bold">Our Collections</h1>
            <p class="text-muted">Discover the latest technological trends and keep up to date</p>
        </div>

        <div class="row justify-content-center g-4">
            <?php if (!empty($featured_products)): ?>
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
                        <div class="card w-100 shadow-sm">
                            <img src="<?= h($product['image_url']) ?>" class="card-img-top" alt="<?= h($product['name']) ?>" style="object-fit: cover; height: 200px;" loading="lazy">
                            <div class="card-body text-center d-flex flex-column">
                                <h2 class="card-title fw-bold h5"><?= h($product['name']) ?></h2>
                                <p class="card-text text-truncate"><?= h($product['description']) ?></p>
                                <div class="text-success mb-3 fw-semibold">$<?= number_format($product['price'], 2) ?></div>
                                <button class="btn btn-outline-dark mt-auto" onclick="window.location.href='/shop/catalog.php'">View in Catalog</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No products available at the moment. Please check back later!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/inc/footer.inc.php'; ?>

</body>
</html>
