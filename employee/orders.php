<?php
require_once "../inc/auth.inc.php";
requireEmployee();

$stubTitle = 'Orders';
$stubKey = 'orders';
$stubDescription = 'Orders is now reachable from the employee dashboard. Use this section to track and update order status.';

require_once "../inc/employee_stub_page.inc.php";
