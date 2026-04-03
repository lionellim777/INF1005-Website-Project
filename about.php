<?php 
require_once __DIR__ . '/inc/bootstrap.php';

$pageTitle = "Pomegranate | About Us";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php h($pageTitle) ?></title>
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
    <div class="container text-center position-relative" style="z-index:2;">
        <div class="section-label"><i class="bi bi-building"></i> Our Story</div>
        <h1 class="section-heading mb-4">
            From Spec Sheets to <span class="text-gradient">Real-Life Solutions</span>
        </h1>
        <p class="text-white-50 mx-auto" style="max-width:720px;font-size:1.1rem;line-height:1.8;">
            Pomegranate started with a small team of students, builders, and everyday tech users who were tired of confusing product hype.
            We set out to create a store that explains technology clearly, recommends responsibly, and helps people buy what they actually need.
        </p>
        <p class="text-white-50 mx-auto mb-0" style="max-width:720px;font-size:1.02rem;line-height:1.8;">
            Today, we focus on practical devices for study, work, and creative projects, carefully selected, honestly priced,
            and supported by a team that stays with you after checkout.
        </p>
    </div>

    <section class="py-5 bg-light animated">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Why Choose Us?</h2>
                <p class="text-muted">The core principles that drive our selection process.</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-shield-check feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">Quality Assurance</h3>
                        <p class="small text-muted">Regular warehouse maintenance checks ensuring products are kept in a clean, no-risk environment to avoid damages</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-box-seam feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">Global Sourcing</h3>
                        <p class="small text-muted">We partner with top brands to bring you exclusive releases.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-headset feature-icon mb-3"></i>
                        <h3 class="fw-bold h5">24/7 Support</h3>
                        <p class="small text-muted">Our dedicated team is always here to guide and solve.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 text-center bg-dark text-white animated">
        <div class="container">
            <h2 class="fw-bold">Ready to Explore?</h2>
            <p class="mb-4">Check out our latest collection of premium tech.</p>
            <button onclick="window.location.href='/shop/catalog.php'" class="btn btn-outline-light btn-lg px-5">Shop Now</button>
        </div>
    </section>
</main>

<?php include "inc/footer.inc.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
<script async src="https://unpkg.com/es-module-shims@1.8.0/dist/es-module-shims.js"></script>
<script type="importmap">
{
    "imports": {
        "three": "https://unpkg.com/three@0.158.0/build/three.module.js",
        "three/addons/": "https://unpkg.com/three@0.158.0/examples/jsm/"
    }
}
</script>
<script defer type="module" src="js/main.js"></script>
</body>
</html>