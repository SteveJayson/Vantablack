<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class AuthController
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
     * POST /api/auth/register
     */
    public function register($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $username = trim($body['username'] ?? '');
            $password = $body['password'] ?? '';
            $role = $body['role'] ?? 'civilian';
            $name = trim($body['name'] ?? $username);
            
            if (empty($username) || empty($password)) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Username and password required');
            }
            
            if (strlen($username) < 3) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Username must be at least 3 characters');
            }
            
            if (strlen($password) < 6) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Password must be at least 6 characters');
            }
            
            if (!in_array($role, ['civilian', 'hero', 'villain', 'admin'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Invalid role');
            }
            
            // Check username
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Username already taken');
            }
            
            $roleConfig = $this->getRoleConfig($role);
            
            // Create combatant
            $stmt = $this->db->prepare("
                INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $roleConfig['bio_capacity'], $roleConfig['recovery'],
                $roleConfig['risk'], $roleConfig['credits'], $roleConfig['faction'],
                $role, $roleConfig['clearance']
            ]);
            
            $combatantId = (int)$this->db->lastInsertId();
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));
            
            // Create user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, combatant_id, role, session_token, last_login, login_count, is_online)
                VALUES (?, ?, ?, ?, ?, NOW(), 1, TRUE)
            ");
            $stmt->execute([$username, $passwordHash, $combatantId, $role, $token]);
            
            $userId = (int)$this->db->lastInsertId();
            
            $this->logLogin($userId, $combatantId, $username, $role);
            
            // Create stats
            try {
                $stmt = $this->db->prepare("INSERT INTO combatant_stats (combatant_id) VALUES (?)");
                $stmt->execute([$combatantId]);
            } catch (\Exception $e) {}
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Registration successful!', [
                'token' => $token,
                'user' => [
                    'id' => $combatantId,
                    'username' => $username,
                    'name' => $name,
                    'role' => $role,
                    'credits' => $roleConfig['credits'],
                    'bioCapacityMax' => $roleConfig['bio_capacity']
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/auth/login
     */
    public function login($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $username = trim($body['username'] ?? '');
            $password = $body['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                return $this->jsonResponse($response, 400, false, 'Username and password required');
            }
            
            $stmt = $this->db->prepare("
                SELECT u.*, c.name, c.credits, c.bio_capacity_max, c.faction
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password_hash'])) {
                return $this->jsonResponse($response, 401, false, 'Invalid username or password');
            }
            
            $token = bin2hex(random_bytes(32));
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET session_token = ?, last_login = NOW(), 
                    login_count = login_count + 1, is_online = TRUE, last_activity = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$token, $user['id']]);
            
            $this->logLogin($user['id'], $user['combatant_id'], $user['username'], $user['role']);
            
            return $this->jsonResponse($response, 200, true, 'Login successful!', [
                'token' => $token,
                'user' => [
                    'id' => (int)$user['combatant_id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'credits' => (int)$user['credits'],
                    'bioCapacityMax' => (int)$user['bio_capacity_max'],
                    'faction' => $user['faction']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/auth/logout
     */
    public function logout($request, $response, $args)
    {
        try {
            $token = $this->getToken($request);
            
            if ($token) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE session_token = ?");
                $stmt->execute([$token]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $stmt = $this->db->prepare("
                        UPDATE users SET session_token = NULL, is_online = FALSE, last_activity = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$user['id']]);
                    
                    $stmt = $this->db->prepare("
                        UPDATE login_history SET logout_at = NOW() 
                        WHERE user_id = ? AND logout_at IS NULL
                        ORDER BY login_at DESC LIMIT 1
                    ");
                    $stmt->execute([$user['id']]);
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'Logged out successfully');
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/auth/me
     */
    public function me($request, $response, $args)
    {
        try {
            $token = $this->getToken($request);
            
            if (!$token) {
                return $this->jsonResponse($response, 401, false, 'No token provided');
            }
            
            $stmt = $this->db->prepare("
                SELECT u.*, c.name, c.credits, c.bio_capacity_max, c.faction
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.session_token = ?
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return $this->jsonResponse($response, 401, false, 'Invalid token');
            }
            
            $stmt = $this->db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return $this->jsonResponse($response, 200, true, 'User found', [
                'user' => [
                    'id' => (int)$user['combatant_id'],
                    'username' => $user['username'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'credits' => (int)$user['credits'],
                    'bioCapacityMax' => (int)$user['bio_capacity_max'],
                    'faction' => $user['faction']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/auth/roles
     */
    public function getRoles($request, $response, $args)
    {
        return $this->jsonResponse($response, 200, true, 'Roles retrieved', [
            'roles' => [
                ['id' => 'civilian', 'name' => 'Civilian', 'icon' => '👤', 'color' => '#ffaa00'],
                ['id' => 'hero', 'name' => 'Hero', 'icon' => '🦸', 'color' => '#00f0ff'],
                ['id' => 'villain', 'name' => 'Villain', 'icon' => '🦹', 'color' => '#ff0044'],
                ['id' => 'admin', 'name' => 'Admin', 'icon' => '👑', 'color' => '#ff00ff']
            ]
        ]);
    }
    
    private function logLogin(int $userId, int $combatantId, string $username, string $role): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt = $this->db->prepare("
                INSERT INTO login_history (user_id, combatant_id, username, role, ip_address)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $combatantId, $username, $role, $ip]);
        } catch (\Exception $e) {}
    }
    
    private function getRoleConfig(string $role): array
    {
        $configs = [
            'civilian' => ['credits' => 500, 'bio_capacity' => 100, 'recovery' => 1, 'risk' => 5, 'faction' => 'hero', 'clearance' => 1],
            'hero' => ['credits' => 5000, 'bio_capacity' => 1200, 'recovery' => 3, 'risk' => 10, 'faction' => 'hero', 'clearance' => 2],
            'villain' => ['credits' => 5000, 'bio_capacity' => 1100, 'recovery' => 4, 'risk' => 15, 'faction' => 'villain', 'clearance' => 2],
            'admin' => ['credits' => 999999, 'bio_capacity' => 0, 'recovery' => 0, 'risk' => 0, 'faction' => 'hero', 'clearance' => 5]
        ];
        
        return $configs[$role] ?? $configs['civilian'];
    }
    
    private function getToken($request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
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