<?php
require_once "../inc/auth.inc.php";
requireEmployee();

$stubTitle = 'Customers';
$stubKey = 'customers';
$stubDescription = 'Customers is now reachable. Build customer profiles, activity history, and support insights in this section.';

require_once "../inc/employee_stub_page.inc.php";
