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
include __DIR__ . '/inc/page-top.inc.php';
?>

<link rel="stylesheet" href="css/form.css">

    <div id="carouselExampleCaptions" class="carousel slide mb-4" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active"> 
                <img src="<?= h(app_url('assets/phone.jpg')) ?>" class="d-block w-100" alt="Phone">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Inspire</h5>
                    <p>“Let’s go invent tomorrow instead of worrying about what happened yesterday.” – Steve Jobs</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?= h(app_url('assets/phone-berries.jpg')) ?>" class="d-block w-100" alt="Phone-berries">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Innovate</h5>
                    <p>“Innovation is the outcome of a habit, not a random act.” – Sukant Ratnakar</p>
                </div>
            </div>
            <div class="carousel-item">
                <img src="<?= h(app_url('assets/phone-blue.jpg')) ?>" class="d-block w-100" alt="Phone-blue">
                <div class="carousel-caption d-none d-md-block">
                    <h5>To Commemorate</h5>
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
                        <div class="card w-100 shadow-sm hover-lift">
                            <img src="<?= h($product['image_url']) ?>" class="card-img-top" alt="<?= h($product['name']) ?>" style="object-fit: cover; height: 200px;">
                            <div class="card-body text-center d-flex flex-column">
                                <h5 class="card-title fw-bold"><?= h($product['name']) ?></h5>
                                <p class="card-text text-truncate"><?= h($product['description']) ?></p>
                                <h6 class="text-success mb-3">$<?= number_format($product['price'], 2) ?></h6>
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

<?php

include __DIR__ . '/inc/page-bottom.inc.php'; 

?>