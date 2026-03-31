<?php
require_once "../inc/auth.inc.php";
requireAdmin();

$stubTitle = 'Activity Logs';
$stubKey = 'logs';
$stubDescription = 'Activity Logs is now accessible. Add audit events and security trail visibility for administrative actions here.';

require_once "../inc/admin_stub_page.inc.php";
