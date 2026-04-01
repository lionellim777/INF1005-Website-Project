<?php
require_once __DIR__ . '/auth/auth_helper.php';
initSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About – <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= appUrl('/css/main.css') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/inc/nav.inc.php'; ?>

    <main class="flex-grow-1 py-5">
        <div class="container">
            <h1 class="fw-bold mb-3">About Pomegranate</h1>
            <p class="text-muted mb-5">We design products that focus on clarity, reliability, and everyday usefulness.</p>

            <section id="reviews" class="mb-5">
                <h2 class="h4 fw-semibold">Latest Reviews</h2>
                <p class="text-muted mb-0">Customer review highlights are shown in our catalog product modal and dashboard activity feed.</p>
            </section>

            <section id="blog" class="mb-5">
                <h2 class="h4 fw-semibold">Blog</h2>
                <p class="text-muted mb-0">Engineering notes and feature updates will be published here in future iterations.</p>
            </section>

            <section id="careers" class="mb-5">
                <h2 class="h4 fw-semibold">Careers</h2>
                <p class="text-muted mb-0">Interested in joining us? Reach out through the contact page with your portfolio.</p>
            </section>

            <section id="privacy" class="mb-4">
                <h2 class="h5 fw-semibold">Privacy</h2>
                <p class="text-muted mb-0">This is a student project environment. No production data policy applies.</p>
            </section>

            <section id="terms" class="mb-4">
                <h2 class="h5 fw-semibold">Terms</h2>
                <p class="text-muted mb-0">This website is for coursework and testing purposes only.</p>
            </section>

            <section id="cookies">
                <h2 class="h5 fw-semibold">Cookies</h2>
                <p class="text-muted mb-0">Session cookies are used for authentication state during testing.</p>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/inc/footer.inc.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>
</body>
</html>
