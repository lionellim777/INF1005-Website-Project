<?php
require_once "../inc/auth.inc.php";
requireAdmin();

header('Location: /index.php?msg=' . urlencode('Admin dashboard access is disabled in this branch.'));
exit;
