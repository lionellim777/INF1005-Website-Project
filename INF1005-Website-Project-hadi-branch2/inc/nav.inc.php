<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.inc.php';

$role       = getRole();
$loggedIn   = isLoggedIn();
$username   = getUsername();
$cartCount  = getCartCount();
$isEmp      = isEmployee();
$isAdm      = isAdmin();

// Determine active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark">
    <div class="container">
        <!-- Brand -->
        <a href="/index.php" class="navbar-brand d-flex align-items-center gap-2">
            <img src="/assets/logo.png" alt="Pomegranate Logo" height="36" class="rounded-2">
            <span class="fw-bold text-white">Pomegranate</span>
        </a>

        <!-- Hamburger toggler -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">

            <!-- Left nav links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='index.php'?'active':'' ?>" href="/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='catalog.php'?'active':'' ?>" href="/catalog.php">Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='about.php'?'active':'' ?>" href="/about.php">About</a>
                </li>

                <?php if ($isEmp): ?>
                <!-- Employee / Admin nav links -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1 <?= strpos($_SERVER['PHP_SELF'],'/employee/')!==false?'active':'' ?>"
                       href="/employee/index.php">
                        <i class="bi bi-speedometer2" style="font-size:.85rem;"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1" href="/employee/products.php">
                        <i class="bi bi-box-seam" style="font-size:.85rem;"></i> Products
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($isAdm): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1 <?= strpos($_SERVER['PHP_SELF'],'/admin/')!==false?'active':'' ?>"
                       href="/admin/index.php">
                        <i class="bi bi-shield-lock" style="font-size:.85rem;"></i> Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Right side actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($loggedIn): ?>
                    <?php if ($role === 'customer'): ?>
                    <!-- Cart icon for customers -->
                    <a href="/cart.php" class="btn btn-ghost cart-badge position-relative"
                       aria-label="Cart">
                        <i class="bi bi-bag fs-5"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="badge-dot"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>

                    <!-- User dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-ghost d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-flex align-items-center justify-content-center rounded-circle"
                                  style="width:30px;height:30px;background:linear-gradient(135deg,#22d3ee,#818cf8);font-size:.8rem;font-weight:700;">
                                <?= strtoupper(substr($username, 0, 1)) ?>
                            </span>
                            <span class="d-none d-md-inline text-white-50 small"><?= h($username) ?></span>
                            <i class="bi bi-chevron-down small text-white-50"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end"
                            style="background:#0c1220;border:1px solid rgba(255,255,255,.1);min-width:180px;">
                            <?php if ($role === 'customer'): ?>
                            <li>
                                <a class="dropdown-item text-white-50 d-flex align-items-center gap-2" href="/profile.php">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-white-50 d-flex align-items-center gap-2" href="/orders.php">
                                    <i class="bi bi-receipt"></i> My Orders
                                </a>
                            </li>
                            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.08);"></li>
                            <?php endif; ?>
                            <li>
                                <span class="dropdown-item text-muted small" style="font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;">
                                    <?= ucfirst($role) ?>
                                </span>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2"
                                   href="/logout.php"
                                   style="color:#f87171;">
                                    <i class="bi bi-box-arrow-right"></i> Log out
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>
                    <a href="/login.php" class="btn btn-ghost">Log in</a>
                    <a href="/signup.php" class="btn btn-glow">Get started</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</nav>
