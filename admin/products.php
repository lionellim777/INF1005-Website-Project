<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Products';
$stubKey = 'products';
$stubDescription = 'Product management is now reachable from the admin sidebar. This is ready for full CRUD implementation.';

require_once "../inc/admin_stub_page.inc.php";
