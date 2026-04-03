<?php
/**
 * Session Initialization
 */
require_once __DIR__ . '/Zebra_Session.php';

// Use the protected connection from our new DB Manager
$db_conn = DB::conn();

// Initialize Zebra_Session (ensure your security key is unique)
$session = new Zebra_Session($db_conn, 'P0m3granat3_S3cur3_K3y_2026');