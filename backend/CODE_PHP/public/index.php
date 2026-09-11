<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * GET /api/admin/dashboard
     */
    public function getDashboard(Request $request, Response $response, array $args): Response
    {
        try {
            $adminId = $request->getQueryParam('adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // Get user counts by role
            $stmt = $this->db->prepare("
                SELECT role, COUNT(*) as count
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
                    'total' => (int)($stats['total_transactions'] ?? 0),
                    'purchases' => (int)($stats['total_purchases'] ?? 0),
                    'sells' => (int)($stats['total_sells'] ?? 0),
                    'completed' => (int)($stats['completed_transactions'] ?? 0),
                    'failed' => (int)($stats['failed_transactions'] ?? 0),
                    'total_revenue' => (int)($stats['total_revenue'] ?? 0),
                    'total_payouts' => (int)($stats['total_payouts'] ?? 0)
                ],
                'inventory' => [
                    'total_gear_items' => (int)($stats['total_gear_items'] ?? 0),
                    'total_inventory_items' => (int)($stats['total_inventory_items'] ?? 0)
                ],
                'recent_transactions' => $recentTransactions
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/transactions
     */
    public function getTransactions(Request $request, Response $response, array $args): Response
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
     */
    public function getUsers(Request $request, Response $response, array $args): Response
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
     */
    public function getUserTransactions(Request $request, Response $response, array $args): Response
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
    
    private function jsonResponse(Response $response, int $status, bool $success, string $message, array $data = []): Response
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