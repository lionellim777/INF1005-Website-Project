<?php
if (!isset($stubTitle, $stubKey, $stubDescription)) {
    throw new RuntimeException('Admin stub page is missing required variables.');
}

$isDemoMode = !empty($_SESSION['demo_mode']);

function adminStubActive(string $current, string $key): string {
    return $current === $key ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($stubTitle) ?> - Administrator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="dash-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="/index.php">
                <img src="/assets/logo.png" alt="Logo" height="28" class="rounded-2">
                Pomegranate
            </a>
            <div class="sidebar-role-badge role-admin">
                <i class="bi bi-shield-lock"></i> Administrator
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Users</div>
            <a href="users.php" class="sidebar-link<?= adminStubActive($stubKey, 'users') ?>"><i class="bi bi-people"></i> Users</a>
            <a href="orders.php" class="sidebar-link<?= adminStubActive($stubKey, 'orders') ?>"><i class="bi bi-receipt"></i> Orders</a>
            <a href="products.php" class="sidebar-link<?= adminStubActive($stubKey, 'products') ?>"><i class="bi bi-box-seam"></i> Products</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link<?= adminStubActive($stubKey, 'analytics') ?>"><i class="bi bi-bar-chart-line"></i> Analytics</a>
            <a href="reports.php" class="sidebar-link<?= adminStubActive($stubKey, 'reports') ?>"><i class="bi bi-file-earmark-text"></i> Reports</a>

            <div class="sidebar-section-label">System</div>
            <a href="settings.php" class="sidebar-link<?= adminStubActive($stubKey, 'settings') ?>"><i class="bi bi-gear"></i> Settings</a>
            <a href="logs.php" class="sidebar-link<?= adminStubActive($stubKey, 'logs') ?>"><i class="bi bi-journal-text"></i> Activity Logs</a>

            <div class="sidebar-section-label">Employee View</div>
            <a href="../employee/products.php" class="sidebar-link"><i class="bi bi-person-badge"></i> Employee Products</a>
            <a href="/catalog.php" class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
        </nav>

        <div class="sidebar-footer">
            <form method="POST" action="/logout.php" class="m-0">
                <?= csrfInput() ?>
                <button type="submit" class="sidebar-user sidebar-user-btn">
                    <div class="sidebar-avatar" style="background:linear-gradient(135deg,#f87171,#818cf8);">
                        <?= strtoupper(substr(getUsername(), 0, 1)) ?>
                    </div>
                    <div class="sidebar-user-info">
                        <div class="name"><?= h(getFullName()) ?></div>
                        <div class="role">Sign out</div>
                    </div>
                    <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
                </button>
            </form>
        </div>
    </aside>

    <div id="main-content" class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle" type="button" aria-label="Toggle sidebar menu"><i class="bi bi-list"></i></button>
                <span class="page-title"><?= h($stubTitle) ?></span>
            </div>
            <a href="users.php" class="btn-dash-secondary">
                <i class="bi bi-house"></i> Back to Admin Home
            </a>
        </div>

        <div class="dash-content">
            <?php if ($isDemoMode): ?>
            <div class="alert-error-dark p-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>Demo mode is active because database connection is unavailable.</span>
            </div>
            <?php endif; ?>

            <div class="form-card">
                <h3 class="form-card-title mb-3"><?= h($stubTitle) ?></h3>
                <p class="text-white-50 mb-4"><?= h($stubDescription) ?></p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="users.php" class="btn-dash-primary">
                        <i class="bi bi-people"></i> User Management
                    </a>
                    <a href="../employee/products.php" class="btn-dash-secondary">
                        <i class="bi bi-person-badge"></i> Employee Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
