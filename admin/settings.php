<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Settings';
$stubKey = 'settings';
$stubDescription = 'System settings is now accessible from the sidebar. Configure global preferences and operational controls here.';

require_once "../inc/admin_stub_page.inc.php";
