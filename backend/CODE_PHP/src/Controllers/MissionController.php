<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class MissionController
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
     * GET /api/missions
     */
    public function getMissions($request, $response, $args)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, reward, difficulty, required_clearance
                FROM missions
                ORDER BY required_clearance, reward
            ");
            $stmt->execute();
            $missions = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Missions retrieved', ['missions' => $missions]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/missions/{id}
     */
    public function getMission($request, $response, $args)
    {
        $id = $args['id'] ?? null;
        
        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Mission ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, description, reward, difficulty, required_clearance
                FROM missions WHERE id = ?
            ");
            $stmt->execute([$id]);
            $mission = $stmt->fetch();
            
            if (!$mission) {
                return $this->jsonResponse($response, 404, false, 'Mission not found');
            }
            
            return $this->jsonResponse($response, 200, true, 'Mission retrieved', ['mission' => $mission]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/missions/complete
     */
    public function completeMission($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['missionId'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and missionId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $missionId = (int)$body['missionId'];
            
            $stmt = $this->db->prepare("SELECT id, name, credits, clearance_level FROM combatants WHERE id = ? FOR UPDATE");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $stmt = $this->db->prepare("SELECT id, name, reward, difficulty, required_clearance FROM missions WHERE id = ?");
            $stmt->execute([$missionId]);
            $mission = $stmt->fetch();
            
            if (!$mission) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Mission not found');
            }
            
            // Check if already completed
            $stmt = $this->db->prepare("SELECT id FROM mission_completions WHERE combatant_id = ? AND mission_id = ?");
            $stmt->execute([$combatantId, $missionId]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Mission already completed');
            }
            
            // Check clearance
            if ($combatant['clearance_level'] < $mission['required_clearance']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Insufficient clearance level');
            }
            
            $newBalance = $combatant['credits'] + $mission['reward'];
            
            // Award credits
            $stmt = $this->db->prepare("UPDATE combatants SET credits = ? WHERE id = ?");
            $stmt->execute([$newBalance, $combatantId]);
            
            // Record completion
            $stmt = $this->db->prepare("INSERT INTO mission_completions (combatant_id, mission_id, completed_at) VALUES (?, ?, NOW())");
            $stmt->execute([$combatantId, $missionId]);
            
            // Update stats
            $stmt = $this->db->prepare("
                UPDATE combatant_stats 
                SET total_missions_completed = total_missions_completed + 1,
                    total_credits_earned = total_credits_earned + ?
                WHERE combatant_id = ?
            ");
            $stmt->execute([$mission['reward'], $combatantId]);
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Mission completed!', [
                'mission' => $mission,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/missions/leaderboard
     */
    public function getLeaderboard($request, $response, $args)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id, c.name, c.faction, c.role,
                    COUNT(mc.id) as missions_completed,
                    COALESCE(SUM(m.reward), 0) as total_rewards
                FROM combatants c
                LEFT JOIN mission_completions mc ON c.id = mc.combatant_id
                LEFT JOIN missions m ON mc.mission_id = m.id
                GROUP BY c.id, c.name, c.faction, c.role
                ORDER BY missions_completed DESC, total_rewards DESC
                LIMIT 10
            ");
            $stmt->execute();
            $leaderboard = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Leaderboard retrieved', ['leaderboard' => $leaderboard]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/missions/completed/{id}
     */
    public function getCompletedMissions($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    m.id, m.name, m.reward, m.difficulty,
                    mc.completed_at
                FROM mission_completions mc
                JOIN missions m ON mc.mission_id = m.id
                WHERE mc.combatant_id = ?
                ORDER BY mc.completed_at DESC
            ");
            $stmt->execute([$combatantId]);
            $completed = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Completed missions retrieved', [
                'completed' => $completed,
                'total' => count($completed)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
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