<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;
use PDOException;
use Exception;

class AnalyticsController
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * GET /api/analytics/{id}/summary
     */
    public function getSummary($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id, name, faction, credits, bio_capacity_max FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $stats = $this->getOrCreateStats($combatantId);
            $achievements = $this->getAchievementsData($combatantId);
            $activity = $this->getRecentActivity($combatantId);
            $battleStats = $this->getBattleStats($combatantId);
            $netWorth = $this->calculateNetWorth($combatantId);
            $winRate = $this->calculateWinRate($stats);
            
            return $this->jsonResponse($response, 200, true, 'Analytics retrieved', [
                'combatant' => [
                    'id' => (int)$combatant['id'],
                    'name' => $combatant['name'],
                    'faction' => $combatant['faction'],
                    'bio_capacity' => (int)$combatant['bio_capacity_max']
                ],
                'summary' => [
                    'total_credits_earned' => (int)($stats['total_credits_earned'] ?? 0),
                    'total_credits_spent' => (int)($stats['total_credits_spent'] ?? 0),
                    'current_credits' => (int)$combatant['credits'],
                    'net_worth' => $netWorth,
                    'total_missions_completed' => (int)($stats['total_missions_completed'] ?? 0),
                    'total_bounties_collected' => (int)($stats['total_bounties_collected'] ?? 0),
                    'total_battles_won' => (int)($stats['total_battles_won'] ?? 0),
                    'total_battles_lost' => (int)($stats['total_battles_lost'] ?? 0),
                    'total_battles_drawn' => (int)($stats['total_battles_drawn'] ?? 0),
                    'win_rate' => $winRate,
                    'current_win_streak' => (int)($stats['current_win_streak'] ?? 0),
                    'longest_win_streak' => (int)($stats['longest_win_streak'] ?? 0),
                    'total_gear_owned' => (int)($stats['total_gear_owned'] ?? 0),
                    'total_tier5_gear' => (int)($stats['total_tier5_gear'] ?? 0),
                    'total_legendary_gear' => (int)($stats['total_legendary_gear'] ?? 0),
                    'total_hours_played' => (int)($stats['total_hours_played'] ?? 0),
                    'global_rank' => $stats['global_rank'] ?? null,
                    'faction_rank' => $stats['faction_rank'] ?? null,
                ],
                'achievements' => $achievements,
                'recent_activity' => $activity,
                'battle_stats' => $battleStats
            ]);
            
        } catch (PDOException $e) {
            return $this->jsonResponse($response, 500, false, 'Database error: ' . $e->getMessage());
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/analytics/{id}/achievements
     */
    public function getAchievements($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $achievements = $this->getAchievementsData($combatantId);
            
            return $this->jsonResponse($response, 200, true, 'Achievements retrieved', $achievements);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/analytics/{id}/battle-history
     */
    public function getBattleHistory($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        // ✅ FIXED: Use getQueryParams() for Slim 4
        $queryParams = $request->getQueryParams();
        $limit = (int)($queryParams['limit'] ?? 20);
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    id, opponent_name, battle_type, result,
                    credits_earned, gear_dropped,
                    damage_dealt, damage_taken,
                    battle_duration_seconds, fought_at
                FROM battle_history
                WHERE combatant_id = ?
                ORDER BY fought_at DESC
                LIMIT ?
            ");
            $stmt->execute([$combatantId, $limit]);
            $history = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Battle history retrieved', [
                'history' => $history,
                'total' => count($history)
            ]);
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/analytics/{id}/sync
     */
    public function syncStats($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $this->updateCombatantStats($combatantId);
            
            return $this->jsonResponse($response, 200, true, 'Stats synchronized successfully');
            
        } catch (Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // PRIVATE METHODS
    // ============================================
    
    private function getOrCreateStats(int $combatantId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM combatant_stats WHERE combatant_id = ?");
        $stmt->execute([$combatantId]);
        $stats = $stmt->fetch();
        
        if (!$stats) {
            $stmt = $this->db->prepare("INSERT INTO combatant_stats (combatant_id) VALUES (?)");
            $stmt->execute([$combatantId]);
            
            $stmt = $this->db->prepare("SELECT * FROM combatant_stats WHERE combatant_id = ?");
            $stmt->execute([$combatantId]);
            $stats = $stmt->fetch();
        }
        
        return $stats;
    }
    
    private function getAchievementsData(int $combatantId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.name, a.description, a.category, a.points, a.badge_icon,
                COALESCE(ca.is_completed, 0) as is_completed,
                COALESCE(ca.progress, 0) as progress,
                ca.unlocked_at, a.unlock_condition
            FROM achievements a
            LEFT JOIN combatant_achievements ca 
                ON a.id = ca.achievement_id AND ca.combatant_id = ?
            ORDER BY a.category, a.points ASC
        ");
        $stmt->execute([$combatantId]);
        $achievements = $stmt->fetchAll();
        
        $totalPoints = 0;
        $completed = 0;
        
        foreach ($achievements as &$ach) {
            $ach['is_completed'] = (bool)$ach['is_completed'];
            $ach['progress'] = (int)$ach['progress'];
            $ach['unlock_condition'] = json_decode($ach['unlock_condition'] ?? '{}', true);
            
            if ($ach['is_completed']) {
                $totalPoints += (int)$ach['points'];
                $completed++;
            }
        }
        
        return [
            'list' => $achievements,
            'total_points' => $totalPoints,
            'total_achievements' => count($achievements),
            'completed_achievements' => $completed,
            'completion_percentage' => count($achievements) > 0 
                ? round(($completed / count($achievements)) * 100) 
                : 0
        ];
    }
    
    private function getRecentActivity(int $combatantId, int $limit = 10): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT activity_type, details, credits_change, logged_at
                FROM activity_log
                WHERE combatant_id = ?
                ORDER BY logged_at DESC
                LIMIT ?
            ");
            $stmt->execute([$combatantId, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function calculateNetWorth(int $combatantId): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(g.price), 0) as gear_value
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                WHERE i.combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $result = $stmt->fetch();
            $gearValue = (int)($result['gear_value'] ?? 0);
            
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            $credits = (int)($combatant['credits'] ?? 0);
            
            return $gearValue + $credits;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getBattleStats(int $combatantId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_battles,
                    SUM(CASE WHEN result = 'win' THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN result = 'loss' THEN 1 ELSE 0 END) as losses,
                    SUM(CASE WHEN result = 'draw' THEN 1 ELSE 0 END) as draws,
                    COALESCE(AVG(damage_dealt), 0) as avg_damage_dealt,
                    COALESCE(AVG(damage_taken), 0) as avg_damage_taken
                FROM battle_history WHERE combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $stats = $stmt->fetch();
            
            return [
                'total_battles' => (int)($stats['total_battles'] ?? 0),
                'wins' => (int)($stats['wins'] ?? 0),
                'losses' => (int)($stats['losses'] ?? 0),
                'draws' => (int)($stats['draws'] ?? 0),
                'win_rate' => ($stats['total_battles'] ?? 0) > 0 
                    ? round((($stats['wins'] ?? 0) / ($stats['total_battles'] ?? 0)) * 100, 1)
                    : 0,
                'avg_damage_dealt' => round($stats['avg_damage_dealt'] ?? 0, 1),
                'avg_damage_taken' => round($stats['avg_damage_taken'] ?? 0, 1)
            ];
        } catch (Exception $e) {
            return [
                'total_battles' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0,
                'win_rate' => 0, 'avg_damage_dealt' => 0, 'avg_damage_taken' => 0
            ];
        }
    }
    
    private function calculateWinRate(array $stats): float
    {
        $total = ($stats['total_battles_won'] ?? 0) + ($stats['total_battles_lost'] ?? 0) + ($stats['total_battles_drawn'] ?? 0);
        return $total > 0 ? round((($stats['total_battles_won'] ?? 0) / $total) * 100, 1) : 0;
    }
    
    private function updateCombatantStats(int $combatantId): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE combatant_stats cs
                SET 
                    total_gear_owned = (SELECT COUNT(*) FROM inventory WHERE combatant_id = ?),
                    total_tier5_gear = (
                        SELECT COUNT(*) FROM inventory i
                        JOIN gear_items g ON i.gear_id = g.id
                        WHERE i.combatant_id = ? AND g.tier = 5
                    ),
                    total_legendary_gear = (
                        SELECT COUNT(*) FROM inventory i
                        JOIN gear_items g ON i.gear_id = g.id
                        WHERE i.combatant_id = ? AND g.is_legendary = 1
                    )
                WHERE cs.combatant_id = ?
            ");
            $stmt->execute([$combatantId, $combatantId, $combatantId, $combatantId]);
        } catch (Exception $e) {
            // Silently fail
        }
    }
    
    private function jsonResponse($response, int $status, bool $success, string $message, array $data = [])
    {
        $payload = ['status' => $status, 'success' => $success, 'message' => $message, 'data' => $data];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}