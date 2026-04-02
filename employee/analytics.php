<?php
require_once "../inc/auth.inc.php";
requireEmployee();

$stubTitle = 'Sales Analytics';
$stubKey = 'analytics';
$stubDescription = 'Sales Analytics is now reachable. Add trend charts, conversion metrics, and category performance insights here.';

require_once "../inc/employee_stub_page.inc.php";
