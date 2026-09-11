<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class AdminController
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * Helper: Get query parameter (Slim 4 compatible)
     */
    private function getQuery($request, string $key, $default = null)
    {
        $params = $request->getQueryParams();
        return $params[$key] ?? $default;
    }
    
    /**
     * GET /api/admin/dashboard
     */
    public function getDashboard($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // REGISTERED USERS BY ROLE (from combatants table)
            $registered = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            
            $stmt = $this->db->query("
                SELECT role, COUNT(*) as count
                FROM combatants
                GROUP BY role
            ");
            
            foreach ($stmt->fetchAll() as $row) {
                if (isset($registered[$row['role']])) {
                    $registered[$row['role']] = (int)$row['count'];
                }
            }
            
            // LOGGED IN USERS BY ROLE (from users table)
            $loggedIn = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            
            try {
                $stmt = $this->db->query("
                    SELECT role, COUNT(*) as count
                    FROM users
                    GROUP BY role
                ");
                
                foreach ($stmt->fetchAll() as $row) {
                    if (isset($loggedIn[$row['role']])) {
                        $loggedIn[$row['role']] = (int)$row['count'];
                    }
                }
            } catch (\Exception $e) {}
            
            // ONLINE NOW
            $online = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            
            try {
                $stmt = $this->db->query("
                    SELECT role, COUNT(*) as count
                    FROM users
                    WHERE is_online = TRUE 
                        AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                    GROUP BY role
                ");
                
                foreach ($stmt->fetchAll() as $row) {
                    if (isset($online[$row['role']])) {
                        $online[$row['role']] = (int)$row['count'];
                    }
                }
            } catch (\Exception $e) {}
            
            // TRANSACTION STATS
            $txStats = [
                'total_transactions' => 0,
                'total_purchases' => 0,
                'total_sells' => 0,
                'completed_transactions' => 0,
                'failed_transactions' => 0,
                'total_revenue' => 0,
                'total_payouts' => 0
            ];
            
            try {
                $stmt = $this->db->query("
                    SELECT 
                        COUNT(*) as total_transactions,
                        SUM(CASE WHEN transaction_type = 'purchase' THEN 1 ELSE 0 END) as total_purchases,
                        SUM(CASE WHEN transaction_type = 'sell' THEN 1 ELSE 0 END) as total_sells,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transactions,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
                        COALESCE(SUM(CASE WHEN transaction_type = 'purchase' AND status = 'completed' THEN amount ELSE 0 END), 0) as total_revenue,
                        COALESCE(SUM(CASE WHEN transaction_type = 'sell' AND status = 'completed' THEN amount ELSE 0 END), 0) as total_payouts
                    FROM transactions
                ");
                $txStats = $stmt->fetch();
            } catch (\Exception $e) {}
            
            // RECENT TRANSACTIONS
            $recentTransactions = [];
            try {
                $stmt = $this->db->query("
                    SELECT * FROM transactions 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $recentTransactions = $stmt->fetchAll();
            } catch (\Exception $e) {}
            
            // RECENT LOGINS
            $recentLogins = [];
            try {
                $stmt = $this->db->query("
                    SELECT 
                        lh.id,
                        lh.username,
                        lh.role,
                        lh.login_at,
                        c.name as combatant_name
                    FROM login_history lh
                    JOIN combatants c ON lh.combatant_id = c.id
                    ORDER BY lh.login_at DESC
                    LIMIT 10
                ");
                $recentLogins = $stmt->fetchAll();
            } catch (\Exception $e) {}
            
            return $this->jsonResponse($response, 200, true, 'Dashboard data retrieved', [
                'registered' => [
                    'civilians' => $registered['civilian'],
                    'heroes' => $registered['hero'],
                    'villains' => $registered['villain'],
                    'admins' => $registered['admin'],
                    'total' => array_sum($registered)
                ],
                'logged_in' => [
                    'civilians' => $loggedIn['civilian'],
                    'heroes' => $loggedIn['hero'],
                    'villains' => $loggedIn['villain'],
                    'admins' => $loggedIn['admin'],
                    'total' => array_sum($loggedIn)
                ],
                'online' => [
                    'civilians' => $online['civilian'],
                    'heroes' => $online['hero'],
                    'villains' => $online['villain'],
                    'admins' => $online['admin'],
                    'total' => array_sum($online)
                ],
                'users' => [
                    'civilians' => $registered['civilian'],
                    'heroes' => $registered['hero'],
                    'villains' => $registered['villain'],
                    'admins' => $registered['admin'],
                    'total' => array_sum($registered)
                ],
                'transactions' => [
                    'total' => (int)($txStats['total_transactions'] ?? 0),
                    'purchases' => (int)($txStats['total_purchases'] ?? 0),
                    'sells' => (int)($txStats['total_sells'] ?? 0),
                    'completed' => (int)($txStats['completed_transactions'] ?? 0),
                    'failed' => (int)($txStats['failed_transactions'] ?? 0),
                    'total_revenue' => (int)($txStats['total_revenue'] ?? 0),
                    'total_payouts' => (int)($txStats['total_payouts'] ?? 0)
                ],
                'recent_transactions' => $recentTransactions,
                'recent_logins' => $recentLogins
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/users
     */
    public function getUsers($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $role = $this->getQuery($request, 'role');
            
            $query = "SELECT 
                c.id, c.name, c.credits, c.faction, c.role,
                c.bio_capacity_max as bioCapacityMax,
                c.clearance_level as clearanceLevel,
                c.created_at as createdAt,
                u.username,
                u.login_count as loginCount,
                u.last_login as lastLogin
            FROM combatants c
            LEFT JOIN users u ON c.id = u.combatant_id";
            
            $params = [];
            if ($role) {
                $query .= " WHERE c.role = ?";
                $params[] = $role;
            }
            
            $query .= " ORDER BY c.role, c.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Users retrieved', [
                'users' => $users,
                'total' => count($users)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/transactions
     */
    public function getTransactions($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $limit = (int)$this->getQuery($request, 'limit', 50);
            
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $transactions = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Transactions retrieved', [
                'transactions' => $transactions,
                'total' => count($transactions)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/users/{id}/transactions
     */
    public function getUserTransactions($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            $userId = $args['id'] ?? null;
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            if (!$userId) {
                return $this->jsonResponse($response, 400, false, 'User ID required');
            }
            
            $stmt = $this->db->prepare("SELECT id, name, role FROM combatants WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return $this->jsonResponse($response, 404, false, 'User not found');
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                WHERE combatant_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'User transactions retrieved', [
                'user' => $user,
                'transactions' => $transactions,
                'total' => count($transactions)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/login-history
     */
    public function getLoginHistory($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $limit = (int)$this->getQuery($request, 'limit', 50);
            
            $stmt = $this->db->prepare("
                SELECT 
                    lh.*,
                    c.name as combatant_name
                FROM login_history lh
                JOIN combatants c ON lh.combatant_id = c.id
                ORDER BY lh.login_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $history = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Login history retrieved', [
                'history' => $history,
                'total' => count($history)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function isAdmin($adminId): bool
    {
        if (!$adminId) return false;
        
        try {
            $stmt = $this->db->prepare("SELECT role FROM combatants WHERE id = ?");
            $stmt->execute([$adminId]);
            $user = $stmt->fetch();
            
            return $user && $user['role'] === 'admin';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function jsonResponse($response, int $status, bool $success, string $message, array $data = [])
    {
        $payload = [
            'status' => $status,
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}