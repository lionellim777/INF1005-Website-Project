<?php
/**
 * Logout Handler
 * Destroys session and redirects to login.
 */

require_once __DIR__ . '/auth_helper.php';
initSession();

logout();

setFlash('success', 'You have been logged out successfully.');
header('Location: ' . appUrl('/auth/login.php'));
exit;
