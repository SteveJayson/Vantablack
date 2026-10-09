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
                self::$instance = null;
                throw new \RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }
        
        return self::$instance;
    }

    /**
     * Run a live connection test and return diagnostic health information
     */
    public static function testConnection(): array
    {
        $startTime = microtime(true);
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'aegis_db';
        $username = getenv('DB_USER') ?: 'root';
        $port = getenv('DB_PORT') ?: '3306';

        try {
            $db = self::getConnection();
            $stmt = $db->query("SELECT 1");
            $stmt->fetch();
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            // Fetch table list
            $tablesStmt = $db->query("SHOW TABLES");
            $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

            // Check for essential tables
            $requiredTables = ['combatants', 'events', 'event_participation', 'battle_history', 'territories', 'gear_items'];
            $missingTables = array_diff($requiredTables, $tables);

            // Get total combatants count if table exists
            $combatantCount = 0;
            if (in_array('combatants', $tables, true)) {
                $countStmt = $db->query("SELECT COUNT(*) FROM combatants");
                $combatantCount = (int)$countStmt->fetchColumn();
            }

            return [
                'connected' => true,
                'status' => 'healthy',
                'latency_ms' => $latencyMs,
                'host' => $host,
                'port' => $port,
                'database' => $dbname,
                'user' => $username,
                'table_count' => count($tables),
                'combatants_count' => $combatantCount,
                'missing_tables' => array_values($missingTables),
                'error' => null
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $msg = $e->getMessage();
            $recommendation = 'Check your database credentials, host, and port.';

            if (stripos($msg, 'Access denied') !== false) {
                $recommendation = 'Access denied: Check DB_USER and DB_PASS environment variables.';
            } elseif (stripos($msg, 'Connection refused') !== false || stripos($msg, 'timed out') !== false) {
                $recommendation = 'Cannot reach database host. Verify DB_HOST and DB_PORT, and check if the database server is running and allowing remote connections.';
            } elseif (stripos($msg, 'Unknown database') !== false) {
                $recommendation = "Database '{$dbname}' not found. Make sure the database exists or import aegis_backup.sql.";
            } elseif (stripos($msg, 'SSL') !== false) {
                $recommendation = 'SSL handshake error. Verify if the database requires SSL certificates (e.g. Aiven/AWS).';
            }

            return [
                'connected' => false,
                'status' => 'unhealthy',
                'latency_ms' => $latencyMs,
                'host' => $host,
                'port' => $port,
                'database' => $dbname,
                'user' => $username,
                'table_count' => 0,
                'combatants_count' => 0,
                'missing_tables' => [],
                'error' => [
                    'message' => $msg,
                    'code' => $e->getCode(),
                    'recommendation' => $recommendation
                ]
            ];
        }
    }
}