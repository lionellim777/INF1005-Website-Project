<?php

$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isHomePage = $currentScript === '' || $currentScript === 'index.php';
$isContactPage = $currentScript === 'contact.php';
?>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark">
    <div class="container">
        <a href="<?= h(app_url('index.php')) ?>" class="navbar-brand d-flex align-items-center gap-2">
            <img src="<?= h(app_url('assets/logo.png')) ?>" alt="Logo" height="40">
            <span class="fw-bold text-white">Pomegranate</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a
                        class="nav-link<?= $isHomePage ? ' active' : '' ?>"
                        <?= $isHomePage ? 'aria-current="page"' : '' ?>
                        href="<?= h(app_url('index.php')) ?>"
                    >Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= h(app_url('index.php#collections')) ?>">Catalog</a>
                </li>
                <li class="nav-item">
                    <a
                        class="nav-link<?= $isContactPage ? ' active' : '' ?>"
                        <?= $isContactPage ? 'aria-current="page"' : '' ?>
                        href="<?= h(app_url('contact.php')) ?>"
                    >Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= h(app_url('contact.php')) ?>" class="btn btn-dark px-4">Send Feedback</a>
            </div>
        </div>
    </div>
</nav>
