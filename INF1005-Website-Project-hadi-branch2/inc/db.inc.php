<?php
/**
 * Database Connection
 * Uses PDO with prepared statements for SQL injection protection
 */

function dbEnv(string $key, string $default = ''): string {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// Defaults work for most local setups. Override using environment variables if needed.
define('DB_HOST', dbEnv('DB_HOST', 'localhost'));
define('DB_PORT', dbEnv('DB_PORT', ''));
define('DB_NAME', dbEnv('DB_NAME', 'pomegranate'));
define('DB_USER', dbEnv('DB_USER', 'root'));
define('DB_PASS', dbEnv('DB_PASS', ''));
define('DB_CHARSET', dbEnv('DB_CHARSET', 'utf8mb4'));
define('DB_SOCKET', dbEnv('DB_SOCKET', ''));

function buildMysqlDsn(string $host): string {
    if (DB_SOCKET !== '') {
        return "mysql:unix_socket=" . DB_SOCKET . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    }

    $dsn = "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    if (DB_PORT !== '') {
        $dsn .= ";port=" . DB_PORT;
    }
    return $dsn;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $hostsToTry = [DB_HOST];
        if (DB_HOST === 'localhost' && DB_SOCKET === '') {
            $hostsToTry[] = '127.0.0.1';
        }

        $errors = [];
        foreach ($hostsToTry as $host) {
            $dsn = buildMysqlDsn($host);
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                break;
            } catch (PDOException $e) {
                $errors[] = $host . ': ' . $e->getMessage();
            }
        }

        if ($pdo === null) {
            // Log detailed errors without exposing internals to users
            error_log("DB Connection Error: " . implode(' | ', $errors));
            throw new RuntimeException("Database connection failed. Please try again later.");
        }
    }
    return $pdo;
}
