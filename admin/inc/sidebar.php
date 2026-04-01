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

// Determine the current user's role for conditional sidebar items
$_sidebarUser = currentUser();
$_isAdmin     = $_sidebarUser && (int)$_sidebarUser['role_id'] >= 4;  // role 4 = admin
$_isEmployee  = $_sidebarUser && (int)$_sidebarUser['role_id'] >= 3;  // role 3+ = employee

// Helper: returns 'active' class string if $page matches $currentPage
function sidebarActive(string $page): string {
    global $currentPage;
    return ($currentPage === $page) ? 'active' : '';
}
?>

<div class="admin-sidebar d-flex flex-column p-3">

    <!-- Brand -->
    <a href="<?= appUrl('/admin/index.php') ?>"
       class="d-flex align-items-center gap-2 mb-4 text-decoration-none">
        <img src="<?= appUrl('/assets/logo.png') ?>" alt="Pomegranate" height="32">
        <span class="fw-bold text-white fs-6">Admin Panel</span>
    </a>

    <!-- Role badge -->
    <div class="mb-3">
        <span class="badge <?= $_isAdmin ? 'bg-danger' : 'bg-warning text-dark' ?> small">
            <?= $_isAdmin ? 'Administrator' : 'Employee' ?>
        </span>
        <div class="text-white-50 small mt-1">
            <?= htmlspecialchars($_sidebarUser['first_name'] . ' ' . $_sidebarUser['last_name']) ?>
        </div>
    </div>

    <hr class="border-white border-opacity-25 my-2">

    <!-- Navigation links -->
    <nav aria-label="Admin navigation">
        <ul class="nav flex-column gap-1">

            <!-- Dashboard overview — visible to all admin/employee -->
            <li class="nav-item">
                <a href="<?= appUrl('/admin/index.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('dashboard') ?>">
                    <i class="bi bi-speedometer2 me-2"></i>Overview
                </a>
            </li>

            <!-- Product management — admin only (add/edit/delete) -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= appUrl('/admin/products.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('products') ?>">
                    <i class="bi bi-phone me-2"></i>Products
                </a>
            </li>
            <?php endif; ?>

            <!-- Inventory — admin only -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= appUrl('/admin/inventory.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('inventory') ?>">
                    <i class="bi bi-boxes me-2"></i>Inventory
                </a>
            </li>
            <?php endif; ?>

            <!-- Orders — admin AND employee can access -->
            <li class="nav-item">
                <a href="<?= appUrl('/admin/orders.php') ?>"
                   class="nav-link admin-nav-link <?= sidebarActive('orders') ?>">
                    <i class="bi bi-bag-check me-2"></i>Orders
                </a>
            </li>

            <!-- User management — admin only, links to Hadi's page -->
            <?php if ($_isAdmin): ?>
            <li class="nav-item">
                <a href="<?= appUrl('/admin/manage_users.php') ?>"
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
        <a href="<?= appUrl('/index.php') ?>"
           class="nav-link admin-nav-link small">
            <i class="bi bi-house me-2"></i>Back to Site
        </a>
        <a href="<?= appUrl('/auth/logout.php') ?>"
           class="nav-link admin-nav-link admin-nav-link-danger small">
            <i class="bi bi-box-arrow-right me-2"></i>Log Out
        </a>
    </div>

</div>
