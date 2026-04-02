<?php
require_once "inc/auth.inc.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php');
    exit;
}

try {
    if (isLoggedIn()) {
        requireValidCsrf($_POST['csrf_token'] ?? null);
    }
    logoutUser();
    header('Location: /index.php?bye=1');
    exit;
} catch (RuntimeException) {
    header('Location: /index.php?error=' . urlencode('Invalid logout request.'));
    exit;
}
