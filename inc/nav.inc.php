<?php
// Safely extract session variables from your existing engine
$isLoggedIn = $_SESSION['loggedin'] ?? false;
$fname = $_SESSION['fname'] ?? 'Guest';
$lname = $_SESSION['lname'] ?? '';
$email = $_SESSION['email'] ?? '';
$role = strtolower($_SESSION['role'] ?? 'user');

// Generate the initials for the teammate's cool avatar UI
$initials = strtoupper(substr($fname, 0, 1) . substr($lname, 0, 1));
if (empty(trim($initials))) $initials = 'U';
?>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark">
    <div class="container">
        <a href="/index.php" class="navbar-brand d-flex align-items-center gap-2">
            <img src="/assets/logo.png" alt="Logo" height="40">
            <span class="fw-bold text-white">Pomegranate</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/catalog.php">Catalog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/contact.php">Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a class="nav-link position-relative text-white me-2" href="/cart.php" title="Cart">
                    <i class="bi bi-bag fs-5"></i>
                </a>

                <?php if ($isLoggedIn): ?>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold"
                                  style="width:32px;height:32px;background-color:rgba(255,255,255,0.2);font-size:0.85rem;">
                                <?= h($initials) ?>
                            </span>
                            <span class="d-none d-md-inline">Hi, <?= h($fname) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    Signed in as <strong><?= h($email) ?></strong>
                                </span>
                            </li>
                            <li>
                                <span class="dropdown-item-text text-muted small text-uppercase fw-bold" style="letter-spacing: 0.05em;">
                                    Role: <?= h(ucfirst($role)) ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <?php if ($role === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="/admin/index.php">
                                        <i class="bi bi-shield-lock text-danger"></i> Admin Dashboard
                                    </a>
                                </li>
                            <?php elseif ($role === 'employee'): ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="/employee/index.php">
                                        <i class="bi bi-person-badge text-warning"></i> Employee Dashboard
                                    </a>
                                </li>
                            <?php else: ?>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2" href="/orders.php">
                                        <i class="bi bi-receipt text-secondary"></i> My Orders
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Log out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-link text-decoration-none text-white-50">Log in</a>
                    <a href="/signup.php" class="btn text-white px-4 fw-semibold hover-lift" style="background-color: #3f7f8b;">Get started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>