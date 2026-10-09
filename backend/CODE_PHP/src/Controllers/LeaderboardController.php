<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class LeaderboardController
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
     * GET /api/leaderboards/fighters
     */
    public function getTopFighters($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    c.faction,
                    COALESCE(cs.total_battles_won, 0) as wins,
                    COALESCE(cs.total_battles_lost, 0) as losses,
                    COALESCE(cs.current_win_streak, 0) as currentStreak,
                    COALESCE(cs.longest_win_streak, 0) as longestStreak,
                    CASE 
                        WHEN (cs.total_battles_won + cs.total_battles_lost) > 0 
                        THEN ROUND(cs.total_battles_won * 100.0 / (cs.total_battles_won + cs.total_battles_lost), 1)
                        ELSE 0
                    END as winRate
                FROM combatants c
                LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                WHERE c.role IN ('hero', 'villain')
                ORDER BY COALESCE(cs.total_battles_won, 0) DESC,
                         COALESCE(cs.current_win_streak, 0) DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            foreach ($data as &$row) {
                $row['id'] = (int)$row['id'];
                $row['wins'] = (int)$row['wins'];
                $row['losses'] = (int)$row['losses'];
                $row['currentStreak'] = (int)$row['currentStreak'];
                $row['longestStreak'] = (int)$row['longestStreak'];
                $row['winRate'] = (float)$row['winRate'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Top fighters retrieved', ['data' => $data]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/earners
     */
    public function getTopEarners($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    c.faction,
                    c.credits as currentCredits,
                    COALESCE(cs.total_credits_earned, 0) as totalEarned,
                    COALESCE(cs.total_credits_spent, 0) as totalSpent,
                    COALESCE((SELECT SUM(g.price) FROM inventory i JOIN gear_items g ON i.gear_id = g.id WHERE i.combatant_id = c.id), 0) as gearValue,
                    c.credits + COALESCE((SELECT SUM(g.price) FROM inventory i JOIN gear_items g ON i.gear_id = g.id WHERE i.combatant_id = c.id), 0) as netWorth
                FROM combatants c
                LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                WHERE c.role IN ('hero', 'villain')
                ORDER BY totalEarned DESC, netWorth DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            foreach ($data as &$row) {
                $row['id'] = (int)$row['id'];
                $row['currentCredits'] = (int)$row['currentCredits'];
                $row['totalEarned'] = (int)$row['totalEarned'];
                $row['totalSpent'] = (int)$row['totalSpent'];
                $row['gearValue'] = (int)$row['gearValue'];
                $row['netWorth'] = (int)$row['netWorth'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Top earners retrieved', ['data' => $data]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/collectors
     */
    public function getTopCollectors($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    c.faction,
                    COUNT(i.id) as gearCount,
                    SUM(CASE WHEN g.is_legendary = 1 THEN 1 ELSE 0 END) as legendaryCount,
                    SUM(CASE WHEN g.tier = 5 THEN 1 ELSE 0 END) as tier5Count,
                    COALESCE(SUM(g.price), 0) as totalGearValue
                FROM combatants c
                LEFT JOIN inventory i ON c.id = i.combatant_id
                LEFT JOIN gear_items g ON i.gear_id = g.id
                WHERE c.role IN ('hero', 'villain')
                GROUP BY c.id, c.name, c.role, c.faction
                ORDER BY gearCount DESC, legendaryCount DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            foreach ($data as &$row) {
                $row['id'] = (int)$row['id'];
                $row['gearCount'] = (int)$row['gearCount'];
                $row['legendaryCount'] = (int)$row['legendaryCount'];
                $row['tier5Count'] = (int)$row['tier5Count'];
                $row['totalGearValue'] = (int)$row['totalGearValue'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Top collectors retrieved', ['data' => $data]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/achievements
     */
    public function getAchievementLeaders($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    c.faction,
                    COUNT(ca.id) as achievementCount,
                    COALESCE(SUM(a.points), 0) as totalPoints
                FROM combatants c
                LEFT JOIN combatant_achievements ca ON c.id = ca.combatant_id AND ca.is_completed = TRUE
                LEFT JOIN achievements a ON ca.achievement_id = a.id
                WHERE c.role IN ('hero', 'villain')
                GROUP BY c.id, c.name, c.role, c.faction
                ORDER BY totalPoints DESC, achievementCount DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            foreach ($data as &$row) {
                $row['id'] = (int)$row['id'];
                $row['achievementCount'] = (int)$row['achievementCount'];
                $row['totalPoints'] = (int)$row['totalPoints'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Achievement leaders retrieved', ['data' => $data]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/factions
     */
    public function getFactionRankings($request, $response, $args)
    {
        try {
            $result = [];
            
            foreach (['hero', 'villain'] as $faction) {
                // Combatants count
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM combatants WHERE faction = ? AND role IN ('hero', 'villain')");
                $stmt->execute([$faction]);
                $memberCount = (int)$stmt->fetch()['count'];
                
                // Total wins
                $stmt = $this->db->prepare("
                    SELECT COALESCE(SUM(cs.total_battles_won), 0) as wins
                    FROM combatant_stats cs
                    JOIN combatants c ON cs.combatant_id = c.id
                    WHERE c.faction = ?
                ");
                $stmt->execute([$faction]);
                $totalWins = (int)$stmt->fetch()['wins'];
                
                // Total credits
                $stmt = $this->db->prepare("
                    SELECT COALESCE(SUM(credits), 0) as credits, COALESCE(AVG(credits), 0) as avg_credits
                    FROM combatants WHERE faction = ? AND role IN ('hero', 'villain')
                ");
                $stmt->execute([$faction]);
                $creditsData = $stmt->fetch();
                
                // Territories
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM territories WHERE controlling_faction = ?");
                $stmt->execute([$faction]);
                $territories = (int)$stmt->fetch()['count'];
                
                // Achievements
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count
                    FROM combatant_achievements ca
                    JOIN combatants c ON ca.combatant_id = c.id
                    WHERE c.faction = ? AND ca.is_completed = TRUE
                ");
                $stmt->execute([$faction]);
                $achievements = (int)$stmt->fetch()['count'];
                
                // Calculate power score
                $powerScore = ($memberCount * 10) + ($totalWins * 5) + ($territories * 100) + ($achievements * 2);
                
                $result[$faction] = [
                    'faction' => $faction,
                    'memberCount' => $memberCount,
                    'totalWins' => $totalWins,
                    'totalCredits' => (int)$creditsData['credits'],
                    'avgCredits' => (int)$creditsData['avg_credits'],
                    'territories' => $territories,
                    'achievements' => $achievements,
                    'powerScore' => $powerScore
                ];
            }
            
            // Determine winner
            $leader = $result['hero']['powerScore'] > $result['villain']['powerScore'] ? 'hero' : 'villain';
            $result['leader'] = $leader;
            $result['difference'] = abs($result['hero']['powerScore'] - $result['villain']['powerScore']);
            
            return $this->jsonResponse($response, 200, true, 'Faction rankings retrieved', $result);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/global
     * Global rankings with combined score
     */
    public function getGlobalRankings($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 20);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    c.faction,
                    c.credits,
                    c.bio_capacity_max as bioCapacity,
                    COALESCE(cs.total_battles_won, 0) as wins,
                    COALESCE(cs.total_battles_lost, 0) as losses,
                    COALESCE(cs.current_win_streak, 0) as winStreak,
                    COALESCE(cs.longest_win_streak, 0) as longestStreak,
                    COALESCE(cs.total_credits_earned, 0) as totalEarned,
                    COALESCE((SELECT COUNT(*) FROM inventory WHERE combatant_id = c.id), 0) as gearCount,
                    COALESCE((SELECT COUNT(*) FROM combatant_achievements WHERE combatant_id = c.id AND is_completed = TRUE), 0) as achievementCount,
                    COALESCE((SELECT SUM(points) FROM combatant_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.combatant_id = c.id AND ca.is_completed = TRUE), 0) as achievementPoints,
                    -- Global Score calculation
                    (
                        COALESCE(cs.total_battles_won, 0) * 10 +
                        COALESCE(cs.current_win_streak, 0) * 20 +
                        COALESCE((SELECT COUNT(*) FROM inventory WHERE combatant_id = c.id), 0) * 5 +
                        COALESCE((SELECT SUM(points) FROM combatant_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.combatant_id = c.id AND ca.is_completed = TRUE), 0) * 3 +
                        FLOOR(c.credits / 100)
                    ) as globalScore
                FROM combatants c
                LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                WHERE c.role IN ('hero', 'villain')
                ORDER BY globalScore DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $data = $stmt->fetchAll();
            
            foreach ($data as &$row) {
                $row['id'] = (int)$row['id'];
                $row['credits'] = (int)$row['credits'];
                $row['bioCapacity'] = (int)$row['bioCapacity'];
                $row['wins'] = (int)$row['wins'];
                $row['losses'] = (int)$row['losses'];
                $row['winStreak'] = (int)$row['winStreak'];
                $row['longestStreak'] = (int)$row['longestStreak'];
                $row['totalEarned'] = (int)$row['totalEarned'];
                $row['gearCount'] = (int)$row['gearCount'];
                $row['achievementCount'] = (int)$row['achievementCount'];
                $row['achievementPoints'] = (int)$row['achievementPoints'];
                $row['globalScore'] = (int)$row['globalScore'];
                $row['winRate'] = ($row['wins'] + $row['losses']) > 0 
                    ? round(($row['wins'] / ($row['wins'] + $row['losses'])) * 100, 1) 
                    : 0;
            }
            
            return $this->jsonResponse($response, 200, true, 'Global rankings retrieved', ['data' => $data]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/leaderboards/my-rank/{id}
     * Get a specific combatant's rank in all categories
     */
    public function getMyRank($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            // Fighter rank
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as rank FROM combatant_stats
                WHERE total_battles_won > (
                    SELECT COALESCE(total_battles_won, 0) FROM combatant_stats WHERE combatant_id = ?
                )
            ");
            $stmt->execute([$combatantId]);
            $fighterRank = (int)$stmt->fetch()['rank'];
            
            // Earner rank
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as rank FROM (
                    SELECT
                        c.id,
                        COALESCE(cs.total_credits_earned, 0) as totalEarned,
                        c.credits + COALESCE((SELECT SUM(g.price) FROM inventory i JOIN gear_items g ON i.gear_id = g.id WHERE i.combatant_id = c.id), 0) as netWorth
                    FROM combatants c
                    LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                    WHERE c.role IN ('hero', 'villain')
                ) as earners
                WHERE earners.totalEarned > (
                    SELECT COALESCE(cs2.total_credits_earned, 0)
                    FROM combatants c2
                    LEFT JOIN combatant_stats cs2 ON c2.id = cs2.combatant_id
                    WHERE c2.id = ?
                )
                OR (
                    earners.totalEarned = (
                        SELECT COALESCE(cs2.total_credits_earned, 0)
                        FROM combatants c2
                        LEFT JOIN combatant_stats cs2 ON c2.id = cs2.combatant_id
                        WHERE c2.id = ?
                    )
                    AND earners.netWorth > (
                        SELECT c2.credits + COALESCE((SELECT SUM(g2.price) FROM inventory i2 JOIN gear_items g2 ON i2.gear_id = g2.id WHERE i2.combatant_id = c2.id), 0)
                        FROM combatants c2 WHERE c2.id = ?
                    )
                )
            ");
            $stmt->execute([$combatantId, $combatantId, $combatantId]);
            $earnerRank = (int)$stmt->fetch()['rank'];
            
            // Collector rank
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as rank FROM (
                    SELECT
                        c.id,
                        COUNT(i.id) as cnt,
                        SUM(CASE WHEN g.is_legendary = 1 THEN 1 ELSE 0 END) as legendaryCount
                    FROM combatants c
                    LEFT JOIN inventory i ON c.id = i.combatant_id
                    LEFT JOIN gear_items g ON i.gear_id = g.id
                    WHERE c.role IN ('hero', 'villain')
                    GROUP BY c.id
                ) as t
                WHERE t.cnt > (SELECT COUNT(*) FROM inventory WHERE combatant_id = ?)
                OR (
                    t.cnt = (SELECT COUNT(*) FROM inventory WHERE combatant_id = ?)
                    AND t.legendaryCount > (
                        SELECT COALESCE(SUM(CASE WHEN g.is_legendary = 1 THEN 1 ELSE 0 END), 0)
                        FROM inventory i
                        JOIN gear_items g ON i.gear_id = g.id
                        WHERE i.combatant_id = ?
                    )
                )
            ");
            $stmt->execute([$combatantId, $combatantId, $combatantId]);
            $collectorRank = (int)$stmt->fetch()['rank'];
            
            // Achievement rank
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as rank FROM (
                    SELECT c.id, COALESCE(SUM(a.points), 0) as pts
                    FROM combatants c
                    LEFT JOIN combatant_achievements ca ON c.id = ca.combatant_id AND ca.is_completed = TRUE
                    LEFT JOIN achievements a ON ca.achievement_id = a.id
                    WHERE c.role IN ('hero', 'villain')
                    GROUP BY c.id
                ) as t
                WHERE t.pts > (
                    SELECT COALESCE(SUM(a2.points), 0)
                    FROM combatant_achievements ca2
                    JOIN achievements a2 ON ca2.achievement_id = a2.id
                    WHERE ca2.combatant_id = ? AND ca2.is_completed = TRUE
                )
            ");
            $stmt->execute([$combatantId]);
            $achievementRank = (int)$stmt->fetch()['rank'];
            
            // Global rank
            $stmt = $this->db->prepare("
                SELECT COUNT(*) + 1 as rank FROM (
                    SELECT 
                        c.id,
                        (
                            COALESCE(cs.total_battles_won, 0) * 10 +
                            COALESCE(cs.current_win_streak, 0) * 20 +
                            COALESCE((SELECT COUNT(*) FROM inventory WHERE combatant_id = c.id), 0) * 5 +
                            COALESCE((SELECT SUM(points) FROM combatant_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.combatant_id = c.id AND ca.is_completed = TRUE), 0) * 3 +
                            FLOOR(c.credits / 100)
                        ) as score
                    FROM combatants c
                    LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id
                    WHERE c.role IN ('hero', 'villain')
                ) as t
                WHERE t.score > (
                    SELECT 
                        (
                            COALESCE(cs2.total_battles_won, 0) * 10 +
                            COALESCE(cs2.current_win_streak, 0) * 20 +
                            COALESCE((SELECT COUNT(*) FROM inventory WHERE combatant_id = c2.id), 0) * 5 +
                            COALESCE((SELECT SUM(points) FROM combatant_achievements ca2 JOIN achievements a2 ON ca2.achievement_id = a2.id WHERE ca2.combatant_id = c2.id AND ca2.is_completed = TRUE), 0) * 3 +
                            FLOOR(c2.credits / 100)
                        )
                    FROM combatants c2
                    LEFT JOIN combatant_stats cs2 ON c2.id = cs2.combatant_id
                    WHERE c2.id = ?
                )
            ");
            $stmt->execute([$combatantId]);
            $globalRank = (int)$stmt->fetch()['rank'];
            
            return $this->jsonResponse($response, 200, true, 'Your rankings retrieved', [
                'fighterRank' => $fighterRank,
                'earnerRank' => $earnerRank,
                'collectorRank' => $collectorRank,
                'achievementRank' => $achievementRank,
                'globalRank' => $globalRank
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