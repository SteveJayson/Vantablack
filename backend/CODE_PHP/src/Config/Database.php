<?php

namespace Aegis\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    
    private function __construct() {}
    
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'aegis_db';
            $username = defined('DB_USER') ? DB_USER : 'root';
            $password = defined('DB_PASS') ? DB_PASS : '';
            
            try {
                self::$instance = new PDO(
                    "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
}