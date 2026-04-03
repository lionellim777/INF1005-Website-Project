<footer>
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-lg-3">
                <a href="/index.php" class="text-decoration-none text-white fw-bold fs-5 d-flex align-items-center gap-2 mb-3">
                    <img src="/assets/logo.png" alt="Logo" height="28" class="rounded-2">
                    Pomegranate
                </a>
                <p class="text-white-50 small" style="line-height:1.7;">
                    Pushing the boundaries of technology. Innovation isn't just what we do - it's who we are.
                </p>
            </div>
            <div class="col-6 col-md-2 ms-auto">
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/shop/catalog.php" class="text-white-50 text-decoration-none small">Catalog</a></li>
                    <li class="mb-2"><a href="/about.php" class="text-white-50 text-decoration-none small">About Us</a></li>
                    <li class="mb-2"><a href="/contact.php" class="text-white-50 text-decoration-none small">Contact Us</a></li>
                </ul>
            </div>
            <div>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="text-white-50 fs-5" aria-label="Twitter/X"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="text-white-50 fs-5" aria-label="GitHub"><i class="bi bi-github"></i></a>
                    <a href="#" class="text-white-50 fs-5" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="text-white-50 fs-5" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="border-top border-white border-opacity-25">
        <div class="container py-3 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <p class="text-white small mb-0">
                &copy; <span id="year"></span> Pomegranate, Inc. All rights reserved.
            </p>
        </div>
    </div>
</footer>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>
