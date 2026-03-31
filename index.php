<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';

$pageTitle = 'Pomegranate | Home';
include __DIR__ . '/inc/page-top.inc.php';
?>

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
                <p>"Let's go invent tomorrow instead of worrying about what happened yesterday." - Steve Jobs</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="<?= h(app_url('assets/phone-berries.jpg')) ?>" class="d-block w-100" alt="Phone berries">
            <div class="carousel-caption d-none d-md-block">
                <h5>To Innovate</h5>
                <p>"Innovation is the outcome of a habit, not a random act." - Sukant Ratnakar</p>
            </div>
        </div>
        <div class="carousel-item">
            <img src="<?= h(app_url('assets/phone-blue.jpg')) ?>" class="d-block w-100" alt="Phone blue">
            <div class="carousel-caption d-none d-md-block">
                <h5>To Commemorate</h5>
                <p>"Technology is best when it brings people together." - Matt Mullenweg</p>
            </div>
        </div>
    </div>
</div>

<div id="collections" class="container my-5">
    <div class="text-center py-4">
        <h1 class="fw-bold">Our Collections</h1>
        <p class="text-muted">Discover the latest technological trends and keep up to date</p>
    </div>

    <div class="row justify-content-center g-4">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 1 Series</h5>
                    <p class="card-text">
                        Explore thoughtful device designs, practical performance upgrades, and standout aesthetics in one refined lineup.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 2 Series</h5>
                    <p class="card-text">
                        Compare modern hardware, user-first features, and flexible choices that fit work, study, and everyday life.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex justify-content-center">
            <div class="card w-100 shadow-sm border-0">
                <img src="<?= h(app_url('assets/cat.jpg')) ?>" class="card-img-top" alt="Collection showcase">
                <div class="card-body text-center">
                    <h5 class="card-title fw-bold">The 3 Series</h5>
                    <p class="card-text">
                        Stay close to new launches, customer impressions, and future-ready ideas from across our product family.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/inc/page-bottom.inc.php'; ?>
