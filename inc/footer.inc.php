<footer class="border-top mt-auto">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-lg-3">
                <a href="<?= h(app_url('index.php')) ?>" class="text-decoration-none text-white fw-bold fs-5">Pomegranate</a>
                <p class="text-white-50 small mt-3">
                    Just because something doesn't do what you planned it to do doesn't mean it's useless. - Thomas Edison
                </p>

                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white fs-5" aria-label="Twitter">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <a href="#" class="text-white fs-5" aria-label="GitHub">
                        <i class="bi bi-github"></i>
                    </a>
                    <a href="#" class="text-white fs-5" aria-label="LinkedIn">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="#" class="text-white fs-5" aria-label="Instagram">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>

            <div class="col-6 col-md-2 ms-auto">
                <h6 class="fw-semibold mb-3 text-white">Catalog</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= h(app_url('index.php#collections')) ?>" class="text-white-50 text-decoration-none small">Latest</a></li>
                    <li class="mb-2"><a href="<?= h(app_url('index.php#collections')) ?>" class="text-white-50 text-decoration-none small">Best sellers</a></li>
                    <li class="mb-2"><a href="<?= h(app_url('index.php#collections')) ?>" class="text-white-50 text-decoration-none small">Reviews</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h6 class="fw-semibold mb-3 text-white">Company</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= h(app_url('index.php')) ?>" class="text-white-50 text-decoration-none small">About</a></li>
                    <li class="mb-2"><a href="<?= h(app_url('index.php#contact-support')) ?>" class="text-white-50 text-decoration-none small">Feedback</a></li>
                    <li class="mb-2"><a href="<?= h(app_url('index.php#contact-support')) ?>" class="text-white-50 text-decoration-none small">Contact</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="border-top border-white border-opacity-25">
        <div class="container py-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <p class="text-white small mb-0">
                &copy; <span id="year"></span> Pomegranate, Inc. All rights reserved.
            </p>
            <div class="d-flex gap-3">
                <a href="<?= h(app_url('index.php')) ?>" class="text-white-50 text-decoration-none small">Privacy</a>
                <a href="<?= h(app_url('index.php')) ?>" class="text-white-50 text-decoration-none small">Terms</a>
                <a href="<?= h(app_url('index.php#contact-support')) ?>" class="text-white-50 text-decoration-none small">Cookies</a>
            </div>
        </div>
    </div>
</footer>
