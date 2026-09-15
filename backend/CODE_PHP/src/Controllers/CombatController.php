<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class CombatController
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
     * GET /api/combat/opponents?combatantId=1
     * Get potential opponents for combat
     */
    public function getOpponents($request, $response, $args)
    {
        try {
            $combatantId = $this->getQuery($request, 'combatantId');
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'combatantId required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id, 
                    c.name, 
                    c.role, 
                    c.faction,
                    c.bio_capacity_max as bioCapacityMax,
                    c.base_recovery as baseRecovery,
                    c.base_risk as baseRisk,
                    c.credits,
                    c.clearance_level as clearanceLevel,
                    COALESCE(cs.total_battles_won * 10 + c.credits / 100, 0) as powerScore,
                    COALESCE(cs.total_battles_won, 0) as wins,
                    COALESCE(cs.total_battles_lost, 0) as losses,
                    COALESCE(cs.current_win_streak, 0) as currentStreak
                FROM combatants c
                LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                WHERE c.id != ? 
                    AND c.role IN ('hero', 'villain')
                ORDER BY RAND()
                LIMIT 10
            ");
            $stmt->execute([$combatantId]);
            $opponents = $stmt->fetchAll();
            
            // Convert numeric strings to proper types
            foreach ($opponents as &$opp) {
                $opp['id'] = (int)$opp['id'];
                $opp['bioCapacityMax'] = (int)$opp['bioCapacityMax'];
                $opp['credits'] = (int)$opp['credits'];
                $opp['powerScore'] = round((float)$opp['powerScore'], 1);
                $opp['wins'] = (int)$opp['wins'];
                $opp['losses'] = (int)$opp['losses'];
                $opp['currentStreak'] = (int)$opp['currentStreak'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Opponents retrieved', [
                'opponents' => $opponents,
                'total' => count($opponents)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/combat/simulate
     * Simulate a battle between two combatants
     */
    public function simulate($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $attackerId = (int)($body['attackerId'] ?? 0);
            $defenderId = (int)($body['defenderId'] ?? 0);
            
            if (!$attackerId || !$defenderId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Both attackerId and defenderId required');
            }
            
            if ($attackerId === $defenderId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Cannot fight yourself');
            }
            
            // Fetch both combatants
            $stmt = $this->db->prepare("
                SELECT 
                    c.id, 
                    c.name, 
                    c.role, 
                    c.faction,
                    c.bio_capacity_max as bioCapacityMax,
                    c.base_recovery as baseRecovery,
                    c.base_risk as baseRisk,
                    c.credits,
                    COALESCE(cs.total_battles_won, 0) as wins,
                    COALESCE(cs.total_battles_lost, 0) as losses,
                    COALESCE(cs.current_win_streak, 0) as currentStreak
                FROM combatants c
                LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                WHERE c.id IN (?, ?)
            ");
            $stmt->execute([$attackerId, $defenderId]);
            $rows = $stmt->fetchAll();
            
            if (count($rows) !== 2) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'One or both combatants not found');
            }
            
            // Identify attacker and defender
            $attacker = null;
            $defender = null;
            foreach ($rows as $row) {
                if ((int)$row['id'] === $attackerId) $attacker = $row;
                if ((int)$row['id'] === $defenderId) $defender = $row;
            }
            
            // ============================================
            // COMBAT CALCULATION
            // ============================================
            
            $attackerBasePower = $attacker['bioCapacityMax'] * 0.7;
            $defenderBasePower = $defender['bioCapacityMax'] * 0.7;
            
            $attackerTotal = $attacker['wins'] + $attacker['losses'];
            $defenderTotal = $defender['wins'] + $defender['losses'];
            
            $attackerWinRate = $attackerTotal > 0 ? $attacker['wins'] / $attackerTotal : 0.5;
            $defenderWinRate = $defenderTotal > 0 ? $defender['wins'] / $defenderTotal : 0.5;
            
            $attackerPower = $attackerBasePower * (0.8 + $attackerWinRate * 0.4) + rand(0, 300);
            $defenderPower = $defenderBasePower * (0.8 + $defenderWinRate * 0.4) + rand(0, 300);
            
            $attackerWins = $attackerPower > $defenderPower;
            $winner = $attackerWins ? $attacker : $defender;
            $loser = $attackerWins ? $defender : $attacker;
            
            $damageDealt = rand(150, 500);
            $damageTaken = rand(100, 450);
            $creditsEarned = 500 + rand(0, 1000);
            
            // Build battle log
            $log = [
                "⚔️ {$attacker['name']} challenges {$defender['name']}!",
                "📊 {$attacker['name']} power: " . round($attackerPower) . " | {$defender['name']} power: " . round($defenderPower),
                "💥 {$attacker['name']} strikes first dealing {$damageDealt} damage!",
                "🛡️ {$defender['name']} counters for {$damageTaken} damage!",
                "🔥 The battle rages for several intense rounds...",
                "🎯 {$winner['name']} lands a critical blow!",
                "🏆 {$winner['name']} WINS THE BATTLE!",
                "💰 {$winner['name']} earns ₵{$creditsEarned} credits!"
            ];
            
            // ============================================
            // UPDATE STATS
            // ============================================
            
            if ($attackerWins) {
                // Attacker wins
                $stmt = $this->db->prepare("
                    UPDATE combatant_stats 
                    SET total_battles_won = total_battles_won + 1,
                        current_win_streak = current_win_streak + 1,
                        longest_win_streak = GREATEST(longest_win_streak, current_win_streak + 1),
                        total_credits_earned = total_credits_earned + ?
                    WHERE combatant_id = ?
                ");
                $stmt->execute([$creditsEarned, $attackerId]);
                
                // Defender loses
                $stmt = $this->db->prepare("
                    UPDATE combatant_stats 
                    SET total_battles_lost = total_battles_lost + 1,
                        current_win_streak = 0
                    WHERE combatant_id = ?
                ");
                $stmt->execute([$defenderId]);
                
                // Award credits
                $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$creditsEarned, $attackerId]);
                
                // Deduct penalty
                $deduct = (int)($creditsEarned * 0.1);
                $stmt = $this->db->prepare("UPDATE combatants SET credits = GREATEST(0, credits - ?) WHERE id = ?");
                $stmt->execute([$deduct, $defenderId]);
                
                $result = 'win';
                $actualCreditsEarned = $creditsEarned;
                
            } else {
                // Defender wins
                $stmt = $this->db->prepare("
                    UPDATE combatant_stats 
                    SET total_battles_won = total_battles_won + 1,
                        current_win_streak = current_win_streak + 1,
                        longest_win_streak = GREATEST(longest_win_streak, current_win_streak + 1)
                    WHERE combatant_id = ?
                ");
                $stmt->execute([$defenderId]);
                
                $stmt = $this->db->prepare("
                    UPDATE combatant_stats 
                    SET total_battles_lost = total_battles_lost + 1,
                        current_win_streak = 0
                    WHERE combatant_id = ?
                ");
                $stmt->execute([$attackerId]);
                
                // Attacker penalty
                $penalty = (int)($creditsEarned * 0.5);
                $stmt = $this->db->prepare("UPDATE combatants SET credits = GREATEST(0, credits - ?) WHERE id = ?");
                $stmt->execute([$penalty, $attackerId]);
                
                $result = 'loss';
                $actualCreditsEarned = -$penalty;
            }
            
            // ============================================
            // LOG BATTLE HISTORY
            // ============================================
            
            $stmt = $this->db->prepare("
                INSERT INTO battle_history 
                (combatant_id, opponent_id, opponent_name, battle_type, result, loadout_used, credits_earned, damage_dealt, damage_taken, fought_at)
                VALUES (?, ?, ?, 'pvp', ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $attackerId,
                $defenderId,
                $defender['name'],
                $result,
                json_encode([
                    'attacker_power' => round($attackerPower),
                    'defender_power' => round($defenderPower)
                ]),
                $actualCreditsEarned,
                $damageDealt,
                $damageTaken
            ]);
            
            // Log activity
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO activity_log (combatant_id, activity_type, details, credits_change, logged_at)
                    VALUES (?, 'battle', ?, ?, NOW())
                ");
                $stmt->execute([
                    $attackerId,
                    json_encode([
                        'opponent' => $defender['name'],
                        'result' => $result,
                        'damage_dealt' => $damageDealt
                    ]),
                    $actualCreditsEarned
                ]);
            } catch (\Exception $e) {
                // Activity log optional
            }
            
            // Fetch updated attacker credits
$stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
$stmt->execute([$attackerId]);
$updatedCredits = (int)$stmt->fetch()['credits'];

$this->db->commit();

return $this->jsonResponse($response, 200, true, 'Battle complete', [
    'winner' => (int)$winner['id'],
    'winnerName' => $winner['name'],
    'loser' => (int)$loser['id'],
    'loserName' => $loser['name'],
    'result' => $result,
    'log' => $log,
    'creditsEarned' => $actualCreditsEarned,
    'damageDealt' => $damageDealt,
    'damageTaken' => $damageTaken,
    'attackerPower' => round($attackerPower),
    'defenderPower' => round($defenderPower),
    'attackerNewCredits' => $updatedCredits,  // ← NEW
    'foughtAt' => date('Y-m-d H:i:s')
]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/combat/history/{id}?limit=10
     * Get battle history for a combatant
     */
    public function getHistory($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    opponent_id as opponentId,
                    opponent_name,
                    battle_type as battleType,
                    result,
                    credits_earned,
                    damage_dealt,
                    damage_taken,
                    battle_duration_seconds,
                    fought_at
                FROM battle_history
                WHERE combatant_id = ?
                ORDER BY fought_at DESC
                LIMIT ?
            ");
            $stmt->execute([$combatantId, $limit]);
            $history = $stmt->fetchAll();
            
            foreach ($history as &$h) {
                $h['id'] = (int)$h['id'];
                $h['credits_earned'] = (int)$h['credits_earned'];
                $h['damage_dealt'] = (int)$h['damage_dealt'];
                $h['damage_taken'] = (int)$h['damage_taken'];
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_battles,
                    SUM(CASE WHEN result = 'win' THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN result = 'loss' THEN 1 ELSE 0 END) as losses,
                    COALESCE(SUM(credits_earned), 0) as total_credits
                FROM battle_history
                WHERE combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $stats = $stmt->fetch();
            
            return $this->jsonResponse($response, 200, true, 'Battle history retrieved', [
                'history' => $history,
                'total' => count($history),
                'stats' => [
                    'totalBattles' => (int)($stats['total_battles'] ?? 0),
                    'wins' => (int)($stats['wins'] ?? 0),
                    'losses' => (int)($stats['losses'] ?? 0),
                    'totalCredits' => (int)($stats['total_credits'] ?? 0)
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/combat/leaderboard
     * Get top fighters by wins
     */
    public function getLeaderboard($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    cs.total_battles_won as wins,
                    cs.total_battles_lost as losses,
                    cs.current_win_streak as currentStreak,
                    cs.longest_win_streak as longestStreak,
                    CASE 
                        WHEN (cs.total_battles_won + cs.total_battles_lost) > 0 
                        THEN ROUND(cs.total_battles_won * 100.0 / (cs.total_battles_won + cs.total_battles_lost), 1)
                        ELSE 0
                    END as winRate
                FROM combatant_stats cs
                JOIN combatants c ON cs.combatant_id = c.id
                WHERE cs.total_battles_won > 0
                ORDER BY cs.total_battles_won DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $leaderboard = $stmt->fetchAll();
            
            foreach ($leaderboard as &$l) {
                $l['id'] = (int)$l['id'];
                $l['wins'] = (int)$l['wins'];
                $l['losses'] = (int)$l['losses'];
                $l['currentStreak'] = (int)$l['currentStreak'];
                $l['longestStreak'] = (int)$l['longestStreak'];
                $l['winRate'] = (float)$l['winRate'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Leaderboard retrieved', [
                'leaderboard' => $leaderboard
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
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