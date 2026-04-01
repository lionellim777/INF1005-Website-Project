<?php
/**
 * Session-aware navigation bar.
 * Shows login/signup for guests, profile dropdown for authenticated users.
 */
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../auth/auth_helper.php';
    initSession();
}

$navUser    = isLoggedIn() ? currentUser() : null;
$isEmployee = $navUser && (int)$navUser['role_id'] >= 3;
$isAdmin    = $navUser && (int)$navUser['role_id'] >= 4;
?>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark" role="navigation" aria-label="Main navigation">
    <div class="container">
        <a href="<?= appUrl('/index.php') ?>" class="navbar-brand d-flex align-items-center gap-2">
            <img src="<?= appUrl('/assets/logo.png') ?>" alt="Pomegranate Logo" height="40">
            <span class="fw-bold text-white">Pomegranate</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= appUrl('/index.php') ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= appUrl('/catalog.php') ?>">Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= appUrl('/about.php') ?>">About</a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= appUrl('/admin/manage_users.php') ?>">
                        <i class="bi bi-shield-lock me-1"></i>Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($navUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-dark dropdown-toggle d-flex align-items-center gap-2 px-3"
                                type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                  style="width:28px;height:28px;background-color:rgba(255,255,255,0.2);font-size:0.75rem;">
                                <?= strtoupper(substr($navUser['first_name'], 0, 1) . substr($navUser['last_name'], 0, 1)) ?>
                            </span>
                            <span class="d-none d-md-inline"><?= htmlspecialchars($navUser['first_name']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    Signed in as <strong><?= htmlspecialchars($navUser['email']) ?></strong>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= appUrl('/auth/dashboard.php') ?>"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= appUrl('/auth/dashboard.php?tab=settings') ?>"><i class="bi bi-gear me-2"></i>Profile Settings</a></li>
                            <?php if (!$isEmployee): ?>
                                <li><a class="dropdown-item" href="<?= appUrl('/auth/dashboard.php?tab=orders') ?>"><i class="bi bi-bag me-2"></i>Purchase History</a></li>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= appUrl('/admin/manage_users.php') ?>"><i class="bi bi-shield-lock me-2"></i>Manage Users</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= appUrl('/auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= appUrl('/auth/login.php') ?>" class="btn btn-link text-decoration-none text-white-50">Log in</a>
                    <a href="<?= appUrl('/auth/register.php') ?>" class="btn btn-dark px-4">Get started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
