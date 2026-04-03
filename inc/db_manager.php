<?php
/**
 * DB Manager - Secure Registry for MySQLi
 */
class DB {
    private static $mysqli = null;

    public static function initialize() {
        if (self::$mysqli !== null) return;

        $config = parse_ini_file('/var/www/private/db-config.ini');
        if (!$config) {
            header('HTTP/1.1 500 Internal Server Error');
            die("Security Error: Configuration inaccessible.");
        }

        self::$mysqli = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        if (self::$mysqli->connect_error) {
            die("Database Connection Error.");
        }
        
        self::$mysqli->set_charset("utf8mb4");
    }

    public static function conn(): mysqli {
        if (self::$mysqli === null) self::initialize();
        return self::$mysqli;
    }
}