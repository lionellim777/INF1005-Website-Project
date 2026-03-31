<?php 

require_once __DIR__ . '/inc/bootstrap.php'; 

include __DIR__ . '/inc/page-top.inc.php';
?>

    <div class="hero mb-0">
        <img src="assets/esmeralda.jpg" class="hero-img" alt="Esmeralda">
        <div class="hero-overlay position-absolute text-center">
            <h1 class="display-4 fw-bold text-white text-uppercase">Our Story</h1>
            <p class="text-white">It all started with...</p>
        </div>
    </div>

    
    <div class="container py-5 bg-white animated">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="fw-bold mb-4">The spark that started it all</h2>
                <p class="text-muted mb-4">
                    We started this project as university undergraduates, where we were given Values-In-Action (VIA) overseas assignments as
                    our university modules. At first, most of us thought it was not the best use of time as there were more important
                    concepts and theories to learn. However, as we made our way and explored, we saw a glimpse of 
                    unprecedented promise of solving our most complex challenges, turning what once seemed impossible into reality.
                </p>
                <p class="text-muted">
                    Through this we have realised that Technology is far more than just circuits, code, and silicon; 
                    it is the ultimate amplifier of human imagination and a bridge connecting people across the globe
                </p>
            </div>
            <div class="col-md-6">
                <img src="assets/team.jpg" alt="The team" class="img-fluid rounded-4 shadow">
            </div>
        </div>
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
                        <h5 class="fw-bold">Quality Assurance</h5>
                        <p class="small text-muted">Regular warehouse maintenance checks ensuring products are kept in a clean, no-risk environment to avoid damages</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-box-seam feature-icon mb-3"></i>
                        <h5 class="fw-bold">Global Sourcing</h5>
                        <p class="small text-muted">We partner with top brands to bring you exclusive releases.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="bi bi-headset feature-icon mb-3"></i>
                        <h5 class="fw-bold">24/7 Support</h5>
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
            <button onclick="window.location.href='catalog.php'" class="btn btn-outline-light btn-lg px-5">Shop Now</button>
        </div>
    </section>

    <?php
        include "inc/footer.inc.php";
    ?>

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
    <script defer type="module" src="js/main.js"></script>
</body>
</html>