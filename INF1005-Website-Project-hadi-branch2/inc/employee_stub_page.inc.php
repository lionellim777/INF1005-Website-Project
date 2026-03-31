<?php
if (!isset($stubTitle, $stubKey, $stubDescription)) {
    throw new RuntimeException('Employee stub page is missing required variables.');
}

$isDemoMode = !empty($_SESSION['demo_mode']);

function employeeStubActive(string $current, string $key): string {
    return $current === $key ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($stubTitle) ?> - Employee Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<div class="dash-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <a href="/index.php">
                <img src="/assets/logo.png" alt="Logo" height="28" class="rounded-2">
                Pomegranate
            </a>
            <div class="sidebar-role-badge role-employee">
                <i class="bi bi-person-badge"></i> Employee
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section-label">Overview</div>
            <a href="index.php" class="sidebar-link<?= employeeStubActive($stubKey, 'dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="products.php" class="sidebar-link<?= employeeStubActive($stubKey, 'products') ?>"><i class="bi bi-box-seam"></i> Products</a>
            <a href="orders.php" class="sidebar-link<?= employeeStubActive($stubKey, 'orders') ?>"><i class="bi bi-receipt"></i> Orders</a>

            <div class="sidebar-section-label">Analytics</div>
            <a href="analytics.php" class="sidebar-link<?= employeeStubActive($stubKey, 'analytics') ?>"><i class="bi bi-bar-chart-line"></i> Sales Analytics</a>
            <a href="customers.php" class="sidebar-link<?= employeeStubActive($stubKey, 'customers') ?>"><i class="bi bi-people"></i> Customers</a>

            <div class="sidebar-section-label">Store</div>
            <a href="/catalog.php" class="sidebar-link"><i class="bi bi-grid-3x3-gap"></i> View Storefront</a>
            <a href="/index.php" class="sidebar-link"><i class="bi bi-house"></i> Back to Home</a>
        </nav>

        <div class="sidebar-footer">
            <a href="/logout.php" class="sidebar-user">
                <div class="sidebar-avatar"><?= strtoupper(substr(getUsername(), 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <div class="name"><?= h(getFullName()) ?></div>
                    <div class="role">Sign out</div>
                </div>
                <i class="bi bi-box-arrow-right ms-auto text-white-50"></i>
            </a>
        </div>
    </aside>

    <div class="dash-main">
        <div class="dash-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebar-toggle" class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <span class="page-title"><?= h($stubTitle) ?></span>
            </div>
            <a href="index.php" class="btn-dash-secondary">
                <i class="bi bi-speedometer2"></i> Back to Dashboard
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
                    <a href="index.php" class="btn-dash-primary">
                        <i class="bi bi-speedometer2"></i> Employee Overview
                    </a>
                    <a href="products.php" class="btn-dash-secondary">
                        <i class="bi bi-box-seam"></i> Product Management
                    </a>
                    <a href="/catalog.php" class="btn-dash-secondary">
                        <i class="bi bi-grid-3x3-gap"></i> Storefront
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
