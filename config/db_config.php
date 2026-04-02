<?php
putenv('APP_BASE_PATH=INF1005-Website-Project');/**
 * Database Configuration
 * Uses PDO with prepared statements to prevent SQL injection.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'pomegranate_db');
define('DB_USER', 'root');          // Change for production
define('DB_PASS', '');              // Change for production
define('DB_CHARSET', 'utf8mb4');

// Site-wide constants
define('SITE_NAME', 'Pomegranate');
define('SESSION_LIFETIME', 3600);   // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);    // 15 minutes
define('BCRYPT_COST', 12);

/**
 * Get a PDO database connection (singleton pattern).
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error and show a generic message
            error_log('Database connection failed: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }

    return $pdo;
}
