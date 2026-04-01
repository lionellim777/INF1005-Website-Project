<?php
/**
 * Admin Sidebar Navigation
 */
$role = strtolower($_SESSION['role'] ?? 'user');
$_isAdmin     = ($role === 'admin');
$_isEmployee  = ($role === 'employee' || $role === 'admin');

function sidebarActive(string $page): string {
    global $currentPage;
    return ($currentPage === $page) ? 'active' : '';
}
?>
<div class="admin-sidebar d-flex flex-column p-3">
    <a href="/admin/index.php" class="d-flex align-items-center gap-2 mb-4 text-decoration-none">
        <img src="/assets/logo.png" alt="Pomegranate" height="32">
        <span class="fw-bold text-white fs-6">Admin Panel</span>
    </a>

    <div class="mb-3">
        <span class="badge <?= $_isAdmin ? 'bg-danger' : 'bg-info text-dark' ?> small">
            <?= $_isAdmin ? 'Administrator' : 'Employee' ?>
        </span>
        <div class="text-white-50 small mt-1">
            <?= htmlspecialchars(($_SESSION['fname'] ?? 'User') . ' ' . ($_SESSION['lname'] ?? '')) ?>
        </div>
    </div>
    <hr class="border-white border-opacity-25 my-2">

    <nav aria-label="Admin navigation">
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a href="/admin/index.php" class="nav-link admin-nav-link <?= sidebarActive('dashboard') ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Overview
                </a>
            </li>
            <?php if ($_isAdmin || $_isEmployee): ?>
            <li class="nav-item">
                <a href="/admin/products.php" class="nav-link admin-nav-link <?= sidebarActive('products') ?>">
                    <i class="bi bi-phone me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/inventory.php" class="nav-link admin-nav-link <?= sidebarActive('inventory') ?>">
                    <i class="bi bi-boxes me-2"></i>Inventory
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="/admin/orders.php" class="nav-link admin-nav-link <?= sidebarActive('orders') ?>">
                    <i class="bi bi-bag-check me-2"></i>Orders
                </a>
            </li>
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="/admin/manage_users.php" class="nav-link admin-nav-link <?= sidebarActive('users') ?>">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="mt-auto">
        <hr class="border-white border-opacity-25">
        <a href="/index.php" class="nav-link admin-nav-link small"><i class="bi bi-house me-2"></i>Back to Site</a>
        <a href="/account/logout.php" class="nav-link admin-nav-link admin-nav-link-danger small"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a>
    </div>
</div>