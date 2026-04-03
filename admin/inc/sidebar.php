<?php
/**
 * Admin Sidebar Navigation
 * Included by all admin dashboard pages.
 *
 * Requires $currentPage to be set before including:
 *   $currentPage = 'dashboard' | 'products' | 'inventory' | 'orders'
 *
 * Also requires auth_helper.php to already be loaded (requireRole handles this).
 */

// Determine the current user's role from session (aligned with require_role function)
// Default to 'user' if not set; admin/employee strings come from login process.
$userRole   = $_SESSION['role'] ?? 'user';
$_isAdmin   = ($userRole === 'admin');
$_isEmployee = ($userRole === 'admin' || $userRole === 'employee');

// Get user's full name from session (set during login)
$userFullName = htmlspecialchars($_SESSION['fname'] . ' ' . $_SESSION['lname'] ?? 'User');

// Helper: returns 'active' class string if $page matches $currentPage
function sidebarActive(string $page): string {
    global $currentPage;
    return ($currentPage === $page) ? 'active' : '';
}
?>

<link rel="stylesheet" href="/css/main.css">
<link rel="stylesheet" href="/css/admin.css">

<div class="admin-sidebar d-flex flex-column p-3">

    <!-- Brand -->
    <a href="<?= app_url('/admin/index.php') ?>"
       class="d-flex align-items-center gap-2 mb-4 text-decoration-none">
        <img src="<?= app_url('/assets/logo.png') ?>" alt="Pomegranate" height="32">
        <span class="fw-bold text-white fs-6">Admin Panel</span>
    </a>

    <!-- Role badge -->
    <div class="mb-3">
        <span class="badge <?= $_isAdmin ? 'bg-danger' : 'bg-warning text-dark' ?> small">
            <?= $_isAdmin ? 'Administrator' : 'Employee' ?>
        </span>
        <div class="text-white-50 small mt-1">
            <?= $userFullName ?>
        </div>
    </div>

    <hr class="border-white border-opacity-25 my-2">

    <!-- Navigation links -->
    <nav aria-label="Admin navigation">
        <ul class="nav flex-column gap-1">

            <!-- Dashboard overview — visible to all admin/employee -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= app_url('/admin/index.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('dashboard') ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Overview
                </a>
            </li>
            <?php endif; ?>

            <!-- Product management — admin only (add/edit/delete) -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= app_url('/admin/products.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('products') ?>">
                    <i class="bi bi-phone me-2"></i>Products
                </a>
            </li>
            <?php endif; ?>

            <!-- Inventory — admin only -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= app_url('/admin/inventory.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('inventory') ?>">
                    <i class="bi bi-boxes me-2"></i>Inventory
                </a>
            </li>
            <?php endif; ?>

            <!-- Orders — admin AND employee can access -->
            <li class="nav-item">
                <a href="<?= app_url('/admin/orders.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('orders') ?>">
                    <i class="bi bi-bag-check me-2"></i>Orders
                </a>
            </li>

            <!-- User management — admin only -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= app_url('/admin/manage_users.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('users') ?>">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            </li>
            <?php endif; ?>

        </ul>
    </nav>

    <!-- Spacer pushes the bottom links down -->
    <div class="mt-auto">
        <hr class="border-white border-opacity-25">
        <a href="<?= app_url('/index.php') ?>"
           class="nav-link admin-nav-link small">
            <i class="bi bi-house me-2"></i>Back to Site
        </a>
        <!-- FIXED: logout link now points to correct /account/logout.php -->
        <a href="<?= app_url('/account/logout.php') ?>"
           class="nav-link admin-nav-link admin-nav-link-danger small">
            <i class="bi bi-box-arrow-right me-2"></i>Log Out
        </a>
    </div>

</div>