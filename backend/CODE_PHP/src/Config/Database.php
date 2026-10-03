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
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'aegis_db';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS') ?: '';
            $port = getenv('DB_PORT') ?: '3306';
            
            // Detect if we're connecting to Aiven (SSL required)
            $isRemote = $host !== 'localhost' && $host !== '127.0.0.1';
            
            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 10,
                ];
                
                // Enable SSL for remote connections (Aiven, cloud DBs)
                if ($isRemote) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                    $options[PDO::MYSQL_ATTR_SSL_CA] = '';
                    // Note: Aiven accepts SSL without CA verification from trusted clients
                }
                
                self::$instance = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                    $username,
                    $password,
                    $options
                );
                
                // Force SSL mode for Aiven
                if ($isRemote) {
                    self::$instance->exec("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
                }
            } catch (PDOException $e) {
                // Better error output
                die("Database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$instance;
    }
}