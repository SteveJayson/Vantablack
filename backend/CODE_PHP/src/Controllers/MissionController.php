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
     * GET /api/missions
     * Get all available missions
     */
    public function getMissions($request, $response, $args)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    name,
                    description,
                    reward,
                    difficulty,
                    required_clearance
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
     * Get a single mission by ID
     */
    public function getMission($request, $response, $args)
    {
        $id = $args['id'] ?? null;
        
        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Mission ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    name,
                    description,
                    reward,
                    difficulty,
                    required_clearance
                FROM missions
                WHERE id = ?
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
     * Complete a mission and earn credits
     */
    public function completeMission($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['missionId'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid request: combatantId and missionId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $missionId = (int)$body['missionId'];
            
            // Get combatant details with lock
            $stmt = $this->db->prepare("
                SELECT id, name, credits, clearance_level 
                FROM combatants 
                WHERE id = ? FOR UPDATE
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Get mission details
            $stmt = $this->db->prepare("
                SELECT id, name, reward, difficulty, required_clearance
                FROM missions
                WHERE id = ?
            ");
            $stmt->execute([$missionId]);
            $mission = $stmt->fetch();
            
            if (!$mission) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Mission not found');
            }
            
            // Check if combatant already completed this mission
            $stmt = $this->db->prepare("
                SELECT id FROM mission_completions
                WHERE combatant_id = ? AND mission_id = ?
            ");
            $stmt->execute([$combatantId, $missionId]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Mission already completed');
            }
            
            // Check clearance level
            if ($combatant['clearance_level'] < $mission['required_clearance']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Insufficient clearance for this mission', [
                    'required' => $mission['required_clearance'],
                    'current' => $combatant['clearance_level']
                ]);
            }
            
            // Award credits
            $newBalance = $combatant['credits'] + $mission['reward'];
            $stmt = $this->db->prepare("
                UPDATE combatants 
                SET credits = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newBalance, $combatantId]);
            
            // Record mission completion
            $stmt = $this->db->prepare("
                INSERT INTO mission_completions (combatant_id, mission_id, completed_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$combatantId, $missionId]);
            
            // Update combatant stats
            $stmt = $this->db->prepare("
                UPDATE combatant_stats 
                SET 
                    total_missions_completed = total_missions_completed + 1,
                    total_credits_earned = total_credits_earned + ?
                WHERE combatant_id = ?
            ");
            $stmt->execute([$mission['reward'], $combatantId]);
            
            // Log activity
            $stmt = $this->db->prepare("
                INSERT INTO activity_log (combatant_id, activity_type, details, credits_change, logged_at)
                VALUES (?, 'mission', ?, ?, NOW())
            ");
            $stmt->execute([
                $combatantId,
                json_encode(['mission_id' => $missionId, 'mission_name' => $mission['name']]),
                $mission['reward']
            ]);
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Mission completed successfully!', [
                'mission' => [
                    'id' => $mission['id'],
                    'name' => $mission['name'],
                    'reward' => $mission['reward']
                ],
                'combatant' => [
                    'id' => $combatant['id'],
                    'name' => $combatant['name'],
                    'new_balance' => $newBalance
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/missions/leaderboard
     * Get mission completion leaderboard
     */
    public function getLeaderboard($request, $response, $args)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.faction,
                    COUNT(mc.id) as missions_completed,
                    SUM(m.reward) as total_rewards_earned
                FROM combatants c
                LEFT JOIN mission_completions mc ON c.id = mc.combatant_id
                LEFT JOIN missions m ON mc.mission_id = m.id
                GROUP BY c.id, c.name, c.faction
                ORDER BY missions_completed DESC, total_rewards_earned DESC
                LIMIT 10
            ");
            $stmt->execute();
            $leaderboard = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Mission leaderboard retrieved', [
                'leaderboard' => $leaderboard
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/missions/completed/{combatantId}
     * Get completed missions for a combatant
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
                    m.id,
                    m.name,
                    m.reward,
                    m.difficulty,
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