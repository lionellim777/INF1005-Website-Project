<?php
require_once __DIR__ . '/bootstrap.php';

/**
 * Enforces that a user must be logged in to access the page.
 * Redirects to the login page if not authenticated.
 */
function require_login() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Optional UX boost: Store where they were trying to go so the login page can redirect them back later
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        
        header("Location: /account/login.php");
        exit;
    }
}

/**
 * Enforces that a logged-in user must have specific privileges to access the page.
 * * @param array $allowed_roles Array of roles (e.g., ['admin', 'employee'])
 */
function require_role($allowed_roles) {
    // First, guarantee they are actually logged in
    require_login();
    
    // Safely grab their role, defaulting to a standard 'user' if something went wrong
    $user_role = $_SESSION['role'] ?? 'user';
    
    // If their role is not in the allowed list, kick them out
    if (!in_array($user_role, $allowed_roles)) {
        // Redirect to home with an error flag
        header("Location: /index.php?error=unauthorized");
        exit;
    }
}

/**
 * A simple helper function for your frontend developers.
 * They can use this to toggle the "Login" / "Logout" buttons in the navigation bar.
 * * @return boolean
 */
function is_logged_in() {
    return (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true);
}
?>