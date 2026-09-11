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
     * GET /api/admin/dashboard
     * Get all statistics (admin only)
     */
    public function getDashboard($request, $response, $args)
    {
        try {
            $adminId = $request->getQueryParam('adminId');
            
            // Verify admin
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // Get user counts by role
            $stmt = $this->db->prepare("
                SELECT 
                    role,
                    COUNT(*) as count
                FROM combatants
                GROUP BY role
            ");
            $stmt->execute();
            $roleCounts = [];
            foreach ($stmt->fetchAll() as $row) {
                $roleCounts[$row['role']] = (int)$row['count'];
            }
            
            // Get transaction stats
            $stmt = $this->db->query("SELECT * FROM v_admin_dashboard");
            $stats = $stmt->fetch();
            
            // Get recent transactions
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmt->execute();
            $recentTransactions = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Dashboard data retrieved', [
                'users' => [
                    'civilians' => $roleCounts['civilian'] ?? 0,
                    'heroes' => $roleCounts['hero'] ?? 0,
                    'villains' => $roleCounts['villain'] ?? 0,
                    'admins' => $roleCounts['admin'] ?? 0,
                    'total' => array_sum($roleCounts)
                ],
                'transactions' => [
                    'total' => (int)$stats['total_transactions'],
                    'purchases' => (int)$stats['total_purchases'],
                    'sells' => (int)$stats['total_sells'],
                    'completed' => (int)$stats['completed_transactions'],
                    'failed' => (int)$stats['failed_transactions'],
                    'total_revenue' => (int)$stats['total_revenue'],
                    'total_payouts' => (int)$stats['total_payouts']
                ],
                'inventory' => [
                    'total_gear_items' => (int)$stats['total_gear_items'],
                    'total_inventory_items' => (int)$stats['total_inventory_items']
                ],
                'recent_transactions' => $recentTransactions
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/transactions
     * Get all transactions with filtering
     */
    public function getTransactions($request, $response, $args)
    {
        try {
            $adminId = $request->getQueryParam('adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $role = $request->getQueryParam('role');
            $type = $request->getQueryParam('type');
            $status = $request->getQueryParam('status');
            $limit = (int)$request->getQueryParam('limit', 50);
            
            $query = "SELECT * FROM transactions WHERE 1=1";
            $params = [];
            
            if ($role) {
                $query .= " AND combatant_role = ?";
                $params[] = $role;
            }
            
            if ($type) {
                $query .= " AND transaction_type = ?";
                $params[] = $type;
            }
            
            if ($status) {
                $query .= " AND status = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
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
     * GET /api/admin/users
     * Get all users with filtering
     */
    public function getUsers($request, $response, $args)
    {
        try {
            $adminId = $request->getQueryParam('adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $role = $request->getQueryParam('role');
            
            $query = "SELECT 
                id, name, credits, faction, role,
                bio_capacity_max as bioCapacityMax,
                clearance_level as clearanceLevel,
                created_at as createdAt
            FROM combatants";
            
            $params = [];
            if ($role) {
                $query .= " WHERE role = ?";
                $params[] = $role;
            }
            
            $query .= " ORDER BY role, name";
            
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
     * GET /api/admin/users/{id}/transactions
     * Get transaction history for a specific user
     */
    public function getUserTransactions($request, $response, $args)
    {
        try {
            $adminId = $request->getQueryParam('adminId');
            $userId = $args['id'] ?? null;
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            if (!$userId) {
                return $this->jsonResponse($response, 400, false, 'User ID required');
            }
            
            // Get user info
            $stmt = $this->db->prepare("SELECT id, name, role FROM combatants WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return $this->jsonResponse($response, 404, false, 'User not found');
            }
            
            // Get user's transactions
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
    
    private function isAdmin($adminId): bool
    {
        if (!$adminId) return false;
        
        $stmt = $this->db->prepare("SELECT role FROM combatants WHERE id = ?");
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();
        
        return $user && $user['role'] === 'admin';
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