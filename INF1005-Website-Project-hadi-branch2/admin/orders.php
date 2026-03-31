<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Orders';
$stubKey = 'orders';
$stubDescription = 'Order management is now reachable from the sidebar. Use this section to review and manage customer orders.';

require_once "../inc/admin_stub_page.inc.php";
