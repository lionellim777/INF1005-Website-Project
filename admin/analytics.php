<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Analytics';
$stubKey = 'analytics';
$stubDescription = 'Analytics is now accessible. Add KPI charts and trend reports for admin-level decision making here.';

require_once "../inc/admin_stub_page.inc.php";
