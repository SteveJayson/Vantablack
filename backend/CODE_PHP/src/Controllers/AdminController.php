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
    
    private function getQuery($request, string $key, $default = null)
    {
        $params = $request->getQueryParams();
        return $params[$key] ?? $default;
    }
        /**
     * POST /api/admin/grant-credits
     * Body: { "adminId": 7, "targetUsername": "vantablack", "amount": 5000, "reason": "Bonus" }
     */
    public function grantCredits($request, $response, $args)
    {
        return $this->adjustCredits($request, $response, $args, false);
    }

    public function removeCredits($request, $response, $args)
    {
        return $this->adjustCredits($request, $response, $args, true);
    }

    private function adjustCredits($request, $response, $args, bool $remove)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $adminId = (int)($body['adminId'] ?? 0);
            $targetUsername = trim($body['targetUsername'] ?? '');
            $amount = (int)($body['amount'] ?? 0);
            $reason = trim($body['reason'] ?? ($remove ? 'Admin removal' : 'Admin grant'));
            
            if (!$adminId || !$targetUsername || !$amount) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'adminId, targetUsername, and amount required');
            }
            
            if ($amount <= 0 || $amount > 1000000) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Amount must be between 1 and 1,000,000');
            }
            
            // Verify admin
            $stmt = $this->db->prepare("SELECT id, name FROM combatants WHERE id = ? AND role = 'admin'");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // Find target by username
            $stmt = $this->db->prepare("
                SELECT u.combatant_id, c.name, c.credits
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.username = ?
                FOR UPDATE
            ");
            $stmt->execute([$targetUsername]);
            $target = $stmt->fetch();
            
            if (!$target) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, "User '{$targetUsername}' not found");
            }
            
            $targetId = (int)$target['combatant_id'];
            $before = (int)$target['credits'];
            if ($remove && $amount > $before) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Amount exceeds the target current balance');
            }

            $after = $remove ? $before - $amount : $before + $amount;
            $loggedAmount = $remove ? -$amount : $amount;
            
            // Update credits
            $stmt = $this->db->prepare("UPDATE combatants SET credits = ? WHERE id = ?");
            $stmt->execute([$after, $targetId]);
            
            // Log grant
            $stmt = $this->db->prepare("
                INSERT INTO admin_grants 
                (admin_id, admin_name, target_id, target_name, amount, reason, credits_before, credits_after)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $adminId,
                $admin['name'],
                $targetId,
                $target['name'],
                $loggedAmount,
                $reason,
                $before,
                $after
            ]);
            
            // Log to transactions for audit
            if (!$remove) {
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO transactions 
                        (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status, notes)
                        VALUES (?, ?, 'admin', 'purchase', 'admin-grant', 'Admin Credit Grant', ?, ?, ?, 'completed', ?)
                    ");
                    $stmt->execute([
                        $targetId,
                        $target['name'],
                        $amount,
                        $before,
                        $after,
                        "Granted by {$admin['name']}: $reason"
                    ]);
                } catch (\Exception $e) {}
            }
            
            $this->db->commit();
            
            $message = $remove
                ? "Removed ₵{$amount} from {$target['name']}"
                : "Granted ₵{$amount} to {$target['name']}";

            return $this->jsonResponse($response, 200, true, $message, [
                'targetId' => $targetId,
                'targetName' => $target['name'],
                'amount' => $amount,
                'operation' => $remove ? 'remove' : 'add',
                'creditsBefore' => $before,
                'creditsAfter' => $after,
                'reason' => $reason
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/grant-history
     */
    public function getGrantHistory($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $limit = (int)$this->getQuery($request, 'limit', 20);
            
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    admin_name as adminName,
                    target_name as targetName,
                    amount,
                    reason,
                    credits_before as creditsBefore,
                    credits_after as creditsAfter,
                    granted_at as grantedAt
                FROM admin_grants
                ORDER BY granted_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $history = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Grant history retrieved', [
                'history' => $history,
                'total' => count($history)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
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
            
            // REGISTERED USERS BY ROLE
            $registered = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM combatants GROUP BY role");
            foreach ($stmt->fetchAll() as $row) {
                if (isset($registered[$row['role']])) {
                    $registered[$row['role']] = (int)$row['count'];
                }
            }
            
            // LOGGED IN USERS BY ROLE
            $loggedIn = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            try {
                $stmt = $this->db->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
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
            
            // PURCHASES PER ROLE
            $purchasesByRole = [
                'civilian' => ['count' => 0, 'total_spent' => 0],
                'hero' => ['count' => 0, 'total_spent' => 0],
                'villain' => ['count' => 0, 'total_spent' => 0],
                'admin' => ['count' => 0, 'total_spent' => 0]
            ];
            try {
                $stmt = $this->db->query("
                    SELECT combatant_role, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_spent
                    FROM transactions
                    WHERE transaction_type = 'purchase' AND status = 'completed'
                    GROUP BY combatant_role
                ");
                foreach ($stmt->fetchAll() as $row) {
                    $role = $row['combatant_role'];
                    if (isset($purchasesByRole[$role])) {
                        $purchasesByRole[$role] = [
                            'count' => (int)$row['count'],
                            'total_spent' => (int)$row['total_spent']
                        ];
                    }
                }
            } catch (\Exception $e) {}
            
            // SELLS PER ROLE
            $sellsByRole = [
                'civilian' => ['count' => 0, 'total_earned' => 0],
                'hero' => ['count' => 0, 'total_earned' => 0],
                'villain' => ['count' => 0, 'total_earned' => 0],
                'admin' => ['count' => 0, 'total_earned' => 0]
            ];
            try {
                $stmt = $this->db->query("
                    SELECT combatant_role, COUNT(*) as count, COALESCE(SUM(amount), 0) as total_earned
                    FROM transactions
                    WHERE transaction_type = 'sell' AND status = 'completed'
                    GROUP BY combatant_role
                ");
                foreach ($stmt->fetchAll() as $row) {
                    $role = $row['combatant_role'];
                    if (isset($sellsByRole[$role])) {
                        $sellsByRole[$role] = [
                            'count' => (int)$row['count'],
                            'total_earned' => (int)$row['total_earned']
                        ];
                    }
                }
            } catch (\Exception $e) {}
            
            // FAILED PER ROLE
            $failedByRole = ['civilian' => 0, 'hero' => 0, 'villain' => 0, 'admin' => 0];
            try {
                $stmt = $this->db->query("
                    SELECT combatant_role, COUNT(*) as count
                    FROM transactions WHERE status = 'failed'
                    GROUP BY combatant_role
                ");
                foreach ($stmt->fetchAll() as $row) {
                    if (isset($failedByRole[$row['combatant_role']])) {
                        $failedByRole[$row['combatant_role']] = (int)$row['count'];
                    }
                }
            } catch (\Exception $e) {}
            
            // OVERALL TRANSACTION STATS
            $txStats = [
                'total_transactions' => 0, 'total_purchases' => 0, 'total_sells' => 0,
                'completed_transactions' => 0, 'failed_transactions' => 0,
                'total_revenue' => 0, 'total_payouts' => 0
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
            
            // TOP SPENDERS
            $topSpenders = [];
            try {
                $stmt = $this->db->query("
                    SELECT 
                        combatant_id, combatant_name, combatant_role,
                        COUNT(*) as purchase_count, SUM(amount) as total_spent
                    FROM transactions
                    WHERE transaction_type = 'purchase' AND status = 'completed'
                    GROUP BY combatant_id, combatant_name, combatant_role
                    ORDER BY total_spent DESC
                    LIMIT 10
                ");
                $topSpenders = $stmt->fetchAll();
            } catch (\Exception $e) {}
            
            // RECENT TRANSACTIONS
            $recentTransactions = [];
            try {
                $stmt = $this->db->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 20");
                $recentTransactions = $stmt->fetchAll();
            } catch (\Exception $e) {}
            
            // RECENT LOGINS
            $recentLogins = [];
            try {
                $stmt = $this->db->query("
                    SELECT lh.id, lh.username, lh.role, lh.login_at, c.name as combatant_name
                    FROM login_history lh
                    JOIN combatants c ON lh.combatant_id = c.id
                    ORDER BY lh.login_at DESC LIMIT 10
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
                'spending_by_role' => [
                    'civilians' => [
                        'purchase_count' => $purchasesByRole['civilian']['count'],
                        'total_spent' => $purchasesByRole['civilian']['total_spent'],
                        'sell_count' => $sellsByRole['civilian']['count'],
                        'total_earned' => $sellsByRole['civilian']['total_earned'],
                        'failed_transactions' => $failedByRole['civilian']
                    ],
                    'heroes' => [
                        'purchase_count' => $purchasesByRole['hero']['count'],
                        'total_spent' => $purchasesByRole['hero']['total_spent'],
                        'sell_count' => $sellsByRole['hero']['count'],
                        'total_earned' => $sellsByRole['hero']['total_earned'],
                        'failed_transactions' => $failedByRole['hero']
                    ],
                    'villains' => [
                        'purchase_count' => $purchasesByRole['villain']['count'],
                        'total_spent' => $purchasesByRole['villain']['total_spent'],
                        'sell_count' => $sellsByRole['villain']['count'],
                        'total_earned' => $sellsByRole['villain']['total_earned'],
                        'failed_transactions' => $failedByRole['villain']
                    ],
                    'admins' => [
                        'purchase_count' => $purchasesByRole['admin']['count'],
                        'total_spent' => $purchasesByRole['admin']['total_spent'],
                        'sell_count' => $sellsByRole['admin']['count'],
                        'total_earned' => $sellsByRole['admin']['total_earned'],
                        'failed_transactions' => $failedByRole['admin']
                    ]
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
                'top_spenders' => $topSpenders,
                'recent_transactions' => $recentTransactions,
                'recent_logins' => $recentLogins
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/spending-report
     */
    public function getSpendingReport($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $stmt = $this->db->query("
                SELECT 
                    combatant_role, transaction_type,
                    COUNT(*) as transaction_count,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COALESCE(AVG(amount), 0) as avg_amount
                FROM transactions
                WHERE status = 'completed'
                GROUP BY combatant_role, transaction_type
                ORDER BY combatant_role, transaction_type
            ");
            $breakdown = $stmt->fetchAll();
            
            $stmt = $this->db->query("
                SELECT 
                    DATE(created_at) as date, combatant_role, transaction_type,
                    COUNT(*) as count, COALESCE(SUM(amount), 0) as total
                FROM transactions
                WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at), combatant_role, transaction_type
                ORDER BY date DESC
            ");
            $trend = $stmt->fetchAll();
            
            $stmt = $this->db->query("
                SELECT gear_id, gear_name, COUNT(*) as purchase_count, SUM(amount) as total_revenue
                FROM transactions
                WHERE transaction_type = 'purchase' AND status = 'completed'
                GROUP BY gear_id, gear_name
                ORDER BY purchase_count DESC
                LIMIT 10
            ");
            $topGear = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Spending report retrieved', [
                'breakdown' => $breakdown,
                'trend' => $trend,
                'top_gear' => $topGear
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/admin/role-transactions?role=hero
     */
    public function getRoleTransactions($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            $role = $this->getQuery($request, 'role');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $query = "SELECT * FROM transactions WHERE 1=1";
            $params = [];
            
            if ($role && in_array($role, ['civilian', 'hero', 'villain'])) {
                $query .= " AND combatant_role = ?";
                $params[] = $role;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT 100";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $transactions = $stmt->fetchAll();
            
            $totalSpent = 0;
            $totalEarned = 0;
            $purchaseCount = 0;
            $sellCount = 0;
            
            foreach ($transactions as $t) {
                if ($t['transaction_type'] === 'purchase' && $t['status'] === 'completed') {
                    $totalSpent += (int)$t['amount'];
                    $purchaseCount++;
                } elseif ($t['transaction_type'] === 'sell' && $t['status'] === 'completed') {
                    $totalEarned += (int)$t['amount'];
                    $sellCount++;
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'Role transactions retrieved', [
                'role' => $role,
                'summary' => [
                    'total_purchases' => $purchaseCount,
                    'total_spent' => $totalSpent,
                    'total_sells' => $sellCount,
                    'total_earned' => $totalEarned,
                    'net_flow' => $totalSpent - $totalEarned
                ],
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
                u.username, u.login_count as loginCount, u.last_login as lastLogin,
                (SELECT COUNT(*) FROM transactions WHERE combatant_id = c.id AND transaction_type = 'purchase' AND status = 'completed') as total_purchases,
                (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE combatant_id = c.id AND transaction_type = 'purchase' AND status = 'completed') as total_spent,
                (SELECT COUNT(*) FROM transactions WHERE combatant_id = c.id AND transaction_type = 'sell' AND status = 'completed') as total_sells,
                (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE combatant_id = c.id AND transaction_type = 'sell' AND status = 'completed') as total_earned
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
            
            $stmt = $this->db->prepare("SELECT * FROM transactions ORDER BY created_at DESC LIMIT ?");
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
            
            $stmt = $this->db->prepare("
                SELECT c.id, c.name, c.role, u.username
                FROM combatants c
                LEFT JOIN users u ON c.id = u.combatant_id
                WHERE c.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return $this->jsonResponse($response, 404, false, 'User not found');
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM transactions WHERE combatant_id = ? ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $transactions = $stmt->fetchAll();
            
            $totalSpent = 0;
            $totalEarned = 0;
            
            foreach ($transactions as $t) {
                if ($t['status'] === 'completed') {
                    if ($t['transaction_type'] === 'purchase') {
                        $totalSpent += (int)$t['amount'];
                    } else {
                        $totalEarned += (int)$t['amount'];
                    }
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'User transactions retrieved', [
                'user' => $user,
                'summary' => [
                    'total_spent' => $totalSpent,
                    'total_earned' => $totalEarned,
                    'net' => $totalEarned - $totalSpent
                ],
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
                SELECT lh.*, c.name as combatant_name
                FROM login_history lh
                JOIN combatants c ON lh.combatant_id = c.id
                ORDER BY lh.login_at DESC LIMIT ?
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
    
    /**
     * GET /api/admin/charts?adminId=7
     * Returns data formatted for charts (NEW!)
     */
    public function getChartData($request, $response, $args)
    {
        try {
            $adminId = $this->getQuery($request, 'adminId');
            
            if (!$this->isAdmin($adminId)) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // ============================================
            // 1. Daily transactions (last 7 days)
            // ============================================
            $dailyData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dailyData[$date] = ['purchases' => 0, 'sells' => 0, 'revenue' => 0];
            }
            
            $stmt = $this->db->query("
                SELECT 
                    DATE(created_at) as date,
                    transaction_type,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total
                FROM transactions
                WHERE status = 'completed'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at), transaction_type
            ");
            
            foreach ($stmt->fetchAll() as $row) {
                $date = $row['date'];
                if (isset($dailyData[$date])) {
                    if ($row['transaction_type'] === 'purchase') {
                        $dailyData[$date]['purchases'] = (int)$row['count'];
                        $dailyData[$date]['revenue'] = (int)$row['total'];
                    } else {
                        $dailyData[$date]['sells'] = (int)$row['count'];
                    }
                }
            }
            
            // ============================================
            // 2. Users by role
            // ============================================
            $stmt = $this->db->query("
                SELECT role, COUNT(*) as count
                FROM combatants
                GROUP BY role
            ");
            $roleDistribution = [];
            foreach ($stmt->fetchAll() as $row) {
                $roleDistribution[$row['role']] = (int)$row['count'];
            }
            
            // ============================================
            // 3. Top spenders
            // ============================================
            $stmt = $this->db->query("
                SELECT 
                    combatant_name,
                    combatant_role,
                    SUM(amount) as total_spent
                FROM transactions
                WHERE transaction_type = 'purchase' AND status = 'completed'
                GROUP BY combatant_name, combatant_role
                ORDER BY total_spent DESC
                LIMIT 8
            ");
            $topSpenders = $stmt->fetchAll();
            
            foreach ($topSpenders as &$s) {
                $s['total_spent'] = (int)$s['total_spent'];
            }
            
            // ============================================
            // 4. Revenue by role
            // ============================================
            $stmt = $this->db->query("
                SELECT 
                    combatant_role,
                    COALESCE(SUM(amount), 0) as revenue,
                    COUNT(*) as count
                FROM transactions
                WHERE transaction_type = 'purchase' AND status = 'completed'
                GROUP BY combatant_role
            ");
            $revenueByRole = [];
            foreach ($stmt->fetchAll() as $row) {
                $revenueByRole[$row['combatant_role']] = [
                    'revenue' => (int)$row['revenue'],
                    'count' => (int)$row['count']
                ];
            }
            
            // ============================================
            // 5. Gear slot distribution
            // ============================================
            $stmt = $this->db->query("
                SELECT g.slot, COUNT(*) as count
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                GROUP BY g.slot
            ");
            $slotDistribution = [];
            foreach ($stmt->fetchAll() as $row) {
                $slotDistribution[$row['slot']] = (int)$row['count'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Chart data retrieved', [
                'daily' => [
                    'labels' => array_keys($dailyData),
                    'purchases' => array_column($dailyData, 'purchases'),
                    'sells' => array_column($dailyData, 'sells'),
                    'revenue' => array_column($dailyData, 'revenue')
                ],
                'roleDistribution' => $roleDistribution,
                'topSpenders' => $topSpenders,
                'revenueByRole' => $revenueByRole,
                'slotDistribution' => $slotDistribution
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper: Check if user is admin
     */
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
    
    /**
     * Helper: JSON response
     */
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