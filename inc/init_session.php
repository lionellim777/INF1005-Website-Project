<?php
// 1. Read the secure config file
$config = parse_ini_file('/var/www/private/db-config.ini');
if (!$config) {
    die("Security Error: Failed to read database config file.");
}

// 2. Establish the Database Connection (Zebra_Session requires MySQLi)
$db_conn = new mysqli(
    $config['servername'],
    $config['username'],
    $config['password'],
    $config['dbname']
);

if ($db_conn->connect_error) {
    die("Database Connection failed: " . $db_conn->connect_error);
}

// 3. Include the Zebra_Session library
require_once __DIR__ . '/Zebra_Session.php';

// 4. Initialize the session handler
$session = new Zebra_Session($db_conn, 's3cur1ty_k3y');