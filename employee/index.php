<?php
require_once "../inc/auth.inc.php";
requireEmployee();

header('Location: /index.php?msg=' . urlencode('Employee dashboard access is disabled in this branch.'));
exit;
