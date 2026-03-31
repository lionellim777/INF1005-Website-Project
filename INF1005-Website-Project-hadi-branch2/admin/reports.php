<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Reports';
$stubKey = 'reports';
$stubDescription = 'Reports is now reachable. Build exportable operational and financial reports in this page.';

require_once "../inc/admin_stub_page.inc.php";
