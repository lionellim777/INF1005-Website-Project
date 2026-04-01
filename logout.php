<?php
require_once __DIR__ . '/inc/bootstrap.php';

// If using Zebra Session, calling stop() safely destroys the session
if (isset($session)) {
    $session->stop();
} else {
    session_destroy();
}
// Redirect to homepage
header("Location: /index.php");
exit;
?>