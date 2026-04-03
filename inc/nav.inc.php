<?php
// Safely extract session variables
$isLoggedIn = $_SESSION['loggedin'] ?? false;
$fname = $_SESSION['fname'] ?? 'Guest';
$lname = $_SESSION['lname'] ?? '';
$email = $_SESSION['email'] ?? '';
$role = strtolower($_SESSION['role'] ?? 'user');

$initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
if (empty(trim($initials))) $initials = 'U';
?>
<style>
    /* ── Navbar Action Button Animations ── */
    .nav-cart-btn {
        transition: transform 0.2s ease, color 0.2s ease;
        display: inline-block;
    }
    .nav-cart-btn:hover {
        transform: scale(1.05) translateY(-2px);
    }

    .nav-login-btn {
        transition: color 0.2s ease, transform 0.2s ease;
        display: inline-block;
    }
    .nav-login-btn:hover {
        color: #ffffff !important;
    }

    .nav-signup-btn {
        transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease !important;
    }
    .nav-signup-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(39, 80, 87, 0.8);
    }
</style>

<nav class="navbar navbar-expand-lg sticky-top navbar-dark">
    <div class="container">
        <a href="/index.php" class="navbar-brand d-flex align-items-center gap-2">
            <img src="/assets/logo.png" alt="Logo" height="40">
            <span class="fw-bold text-white">Pomegranate</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="/index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/shop/catalog.php">Catalog</a></li>
                <li class="nav-item"><a class="nav-link" href="/about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="/contact.php">Contact</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a class="nav-link position-relative text-white me-2 nav-cart-btn" href="/shop/cart.php" title="Cart">
                    <i class="bi bi-bag fs-5"></i>
                </a>

                <?php if ($isLoggedIn): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                  style="width:32px;height:32px;background-color:rgba(255,255,255,0.2);font-size:0.85rem;">
                                <?= h($initials) ?>
                            </span>
                            <span class="d-none d-md-inline">Hi, <?= h($fname) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><span class="dropdown-item-text small text-muted">Signed in as <strong><?= h($email) ?></strong></span></li>
                            <li><span class="dropdown-item-text text-muted small text-uppercase fw-bold">Role: <?= h(ucfirst($role)) ?></span></li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <li><a class="dropdown-item" href="/account/profile.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                            
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item text-danger" href="/admin/index.php"><i class="bi bi-shield-lock me-2"></i> Admin Panel</a></li>
                            <?php elseif ($role === 'employee'): ?>
                                <li><a class="dropdown-item text-info" href="/admin/orders.php"><i class="bi bi-clipboard-data me-2"></i> Staff Dashboard</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item text-secondary" href="/account/profile.php?tab=orders"><i class="bi bi-receipt me-2"></i> My Orders</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/account/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Log out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/account/login.php" class="btn btn-link text-decoration-none text-white-50 nav-login-btn">Log in</a>
                    <a href="/account/signup.php" class="btn text-white px-4 fw-semibold nav-signup-btn" style="background-color: #3f7f8b;">Get started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>