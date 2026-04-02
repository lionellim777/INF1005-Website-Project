<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/auth.inc.php';

$role       = getRole();
$loggedIn   = isLoggedIn();
$username   = getUsername();

// Determine active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<a class="skip-link" href="#main-content">Skip to main content</a>
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
                <?php if ($loggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='profile.php'?'active':'' ?>" href="/profile.php">Profile</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='orders.php'?'active':'' ?>" href="/orders.php">Orders</a>
                </li>
                <?php endif; ?>

            </ul>

            <!-- Right side actions -->
            <div class="d-flex align-items-center gap-2">
                <?php if ($loggedIn): ?>
                    <!-- User dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-ghost d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
                            <span class="d-flex align-items-center justify-content-center rounded-circle"
                                  style="width:30px;height:30px;background:linear-gradient(135deg,#22d3ee,#818cf8);font-size:.8rem;font-weight:700;">
                                <?= strtoupper(substr($username ?: 'U', 0, 1)) ?>
                            </span>
                            <span class="d-none d-md-inline text-white-50 small"><?= h($username) ?></span>
                            <i class="bi bi-chevron-down small text-white-50"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end"
                            style="background:#0c1220;border:1px solid rgba(255,255,255,.1);min-width:180px;">
                            <li>
                                <a class="dropdown-item text-white-50 d-flex align-items-center gap-2" href="/profile.php">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-white-50 d-flex align-items-center gap-2" href="/orders.php">
                                    <i class="bi bi-receipt"></i> Purchase History
                                </a>
                            </li>
                            <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.08);"></li>
                            <li>
                                <span class="dropdown-item text-muted small" style="font-size:.75rem;letter-spacing:.05em;text-transform:uppercase;">
                                    <?= h(getRoleLabel($role)) ?>
                                </span>
                            </li>
                            <li>
                                <form method="POST" action="/logout.php" class="m-0">
                                    <?= csrfInput() ?>
                                    <button type="submit"
                                            class="dropdown-item dropdown-item-btn"
                                            style="color:#f87171;">
                                        <i class="bi bi-box-arrow-right"></i> Log out
                                    </button>
                                </form>
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
<script>
window.APP_CSRF_TOKEN = <?= json_encode(getCsrfToken(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
