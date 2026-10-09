<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class FactionController
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
     * GET /api/factions/territories
     */
    public function getTerritories($request, $response, $args)
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    id,
                    name,
                    description,
                    controlling_faction as controllingFaction,
                    control_percentage as controlPercentage,
                    defense_power as defensePower,
                    bonus_type as bonusType,
                    bonus_value as bonusValue,
                    region,
                    last_attacked_at as lastAttackedAt
                FROM territories
                ORDER BY id ASC
            ");
            $territories = $stmt->fetchAll();
            
            foreach ($territories as &$t) {
                $t['id'] = (int)$t['id'];
                $t['controlPercentage'] = (int)$t['controlPercentage'];
                $t['defensePower'] = (int)$t['defensePower'];
                $t['bonusValue'] = (int)$t['bonusValue'];
            }
            
            // Calculate faction totals
            $heroCount = 0;
            $villainCount = 0;
            foreach ($territories as $t) {
                if ($t['controllingFaction'] === 'hero') $heroCount++;
                else $villainCount++;
            }
            
            return $this->jsonResponse($response, 200, true, 'Territories retrieved', [
                'territories' => $territories,
                'summary' => [
                    'heroControlled' => $heroCount,
                    'villainControlled' => $villainCount,
                    'total' => count($territories)
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/factions/stats
     */
    public function getFactionStats($request, $response, $args)
    {
        try {
            // Combatant counts
            $stmt = $this->db->query("
                SELECT 
                    faction,
                    COUNT(*) as count
                FROM combatants
                WHERE role IN ('hero', 'villain')
                GROUP BY faction
            ");
            $counts = ['hero' => 0, 'villain' => 0];
            foreach ($stmt->fetchAll() as $row) {
                $counts[$row['faction']] = (int)$row['count'];
            }
            
            // Battle wins
            $stmt = $this->db->query("
                SELECT 
                    c.faction,
                    COUNT(*) as wins
                FROM battle_history bh
                JOIN combatants c ON bh.combatant_id = c.id
                WHERE bh.result = 'win'
                GROUP BY c.faction
            ");
            $wins = ['hero' => 0, 'villain' => 0];
            foreach ($stmt->fetchAll() as $row) {
                $wins[$row['faction']] = (int)$row['wins'];
            }
            
            // Total credits
            $stmt = $this->db->query("
                SELECT 
                    faction,
                    COALESCE(SUM(credits), 0) as total_credits,
                    COALESCE(AVG(credits), 0) as avg_credits
                FROM combatants
                WHERE role IN ('hero', 'villain')
                GROUP BY faction
            ");
            $credits = [
                'hero' => ['total' => 0, 'avg' => 0],
                'villain' => ['total' => 0, 'avg' => 0]
            ];
            foreach ($stmt->fetchAll() as $row) {
                $credits[$row['faction']] = [
                    'total' => (int)$row['total_credits'],
                    'avg' => (int)$row['avg_credits']
                ];
            }
            
            // Territory control
            $stmt = $this->db->query("
                SELECT controlling_faction, COUNT(*) as count
                FROM territories
                GROUP BY controlling_faction
            ");
            $territories = ['hero' => 0, 'villain' => 0];
            foreach ($stmt->fetchAll() as $row) {
                $territories[$row['controlling_faction']] = (int)$row['count'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Faction stats retrieved', [
                'heroes' => [
                    'count' => $counts['hero'],
                    'battleWins' => $wins['hero'],
                    'totalCredits' => $credits['hero']['total'],
                    'avgCredits' => $credits['hero']['avg'],
                    'territories' => $territories['hero'],
                    'power' => $counts['hero'] * 100 + $wins['hero'] * 10 + $territories['hero'] * 500
                ],
                'villains' => [
                    'count' => $counts['villain'],
                    'battleWins' => $wins['villain'],
                    'totalCredits' => $credits['villain']['total'],
                    'avgCredits' => $credits['villain']['avg'],
                    'territories' => $territories['villain'],
                    'power' => $counts['villain'] * 100 + $wins['villain'] * 10 + $territories['villain'] * 500
                ]
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/factions/attack
     * Body: { "combatantId": 1, "territoryId": 3 }
     */
    public function attack($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $territoryId = (int)($body['territoryId'] ?? 0);
            
            if (!$combatantId || !$territoryId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and territoryId required');
            }
            
            // Get combatant
            $stmt = $this->db->prepare("
                SELECT id, name, role, faction, credits, bio_capacity_max
                FROM combatants WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            if (!in_array($combatant['role'], ['hero', 'villain'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Only heroes and villains can attack territories');
            }
            
            // Get territory
            $stmt = $this->db->prepare("SELECT * FROM territories WHERE id = ?");
            $stmt->execute([$territoryId]);
            $territory = $stmt->fetch();
            
            if (!$territory) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Territory not found');
            }
            
            // Check if attacking own faction territory
            if ($territory['controlling_faction'] === $combatant['faction']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Cannot attack your own faction territory');
            }
            
            // ============================================
            // COMBAT CALCULATION
            // ============================================
            
            // Attacker power based on bio capacity + randomness
            $attackerPower = (int)($combatant['bio_capacity_max'] * 0.8) + rand(100, 500);
            
            // Defense power from territory
            $defensePower = (int)$territory['defense_power'] + rand(0, 300);
            
            // Determine result
            $isVictory = $attackerPower > $defensePower;
            
            $log = [
                "⚔️ {$combatant['name']} attacks {$territory['name']}!",
                "📊 Attacker power: $attackerPower | Defense: $defensePower",
            ];
            
            if ($isVictory) {
                // Attacker wins
                $controlChange = rand(15, 30);
                $creditsEarned = 500 + rand(0, 800);
                
                // Update control percentage
                $newControl = max(0, (int)$territory['control_percentage'] - $controlChange);
                
                $log[] = "💥 Attack successful!";
                $log[] = "🎯 Control reduced by {$controlChange}%!";
                $log[] = "💰 Earned ₵{$creditsEarned} credits!";
                
                // If control reaches 0, capture the territory
                if ($newControl <= 0) {
                    $stmt = $this->db->prepare("
                        UPDATE territories 
                        SET controlling_faction = ?, 
                            control_percentage = 100,
                            defense_power = ?,
                            last_attacked_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $combatant['faction'],
                        (int)($combatant['bio_capacity_max'] * 1.5),
                        $territoryId
                    ]);
                    
                    $log[] = "🏴 TERRITORY CAPTURED by " . strtoupper($combatant['faction']) . "!";
                    
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE territories 
                        SET control_percentage = ?,
                            last_attacked_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$newControl, $territoryId]);
                }
                
                // Award credits
                $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$creditsEarned, $combatantId]);
                
                $result = 'victory';
                
            } else {
                // Attacker loses
                $penalty = 200 + rand(0, 300);
                $creditsEarned = -$penalty;
                
                $log[] = "🛡️ Attack repelled!";
                $log[] = "💀 You were defeated!";
                $log[] = "💸 Lost ₵{$penalty} credits";
                
                // Deduct credits
                $stmt = $this->db->prepare("UPDATE combatants SET credits = GREATEST(0, credits - ?) WHERE id = ?");
                $stmt->execute([$penalty, $combatantId]);
                
                // Increase defense power
                $stmt = $this->db->prepare("
                    UPDATE territories 
                    SET defense_power = defense_power + 100,
                        last_attacked_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$territoryId]);
                
                $result = 'defeat';
                $controlChange = 0;
            }
            
            // Log attack
            $stmt = $this->db->prepare("
                INSERT INTO territory_attacks 
                (territory_id, attacker_id, attacker_faction, attacker_power, defense_power, result, control_change, credits_earned, fought_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $territoryId,
                $combatantId,
                $combatant['faction'],
                $attackerPower,
                $defensePower,
                $result,
                $controlChange,
                $creditsEarned
            ]);
            
            // ============================================
            // UPDATE ACTIVE EVENT PARTICIPATION POINTS
            // ============================================
            $eventPointsAwarded = 0;
            try {
                $eventStmt = $this->db->prepare("
                    SELECT ep.id as participation_id, ep.event_id, e.name as event_name, ep.score
                    FROM event_participation ep
                    JOIN events e ON ep.event_id = e.id
                    WHERE ep.combatant_id = ?
                      AND e.is_active = TRUE
                      AND NOW() BETWEEN e.start_date AND e.end_date
                ");
                $eventStmt->execute([$combatantId]);
                $activeParticipations = $eventStmt->fetchAll();

                if (!empty($activeParticipations)) {
                    $eventPointsAwarded = ($isVictory) ? 120 : 40;

                    $updateEventStmt = $this->db->prepare("
                        UPDATE event_participation
                        SET score = score + ?, last_action_at = NOW()
                        WHERE id = ?
                    ");

                    foreach ($activeParticipations as $ap) {
                        $updateEventStmt->execute([$eventPointsAwarded, $ap['participation_id']]);
                    }

                    $log[] = "⭐ +{$eventPointsAwarded} Event Points earned for active events!";
                }
            } catch (\Exception $e) {
                error_log("Faction attack event score update failed: " . $e->getMessage());
            }

            // Get updated balance
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $newBalance = (int)$stmt->fetch()['credits'];
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Attack resolved', [
                'result' => $result,
                'territoryName' => $territory['name'],
                'attackerPower' => $attackerPower,
                'defensePower' => $defensePower,
                'controlChange' => $controlChange,
                'creditsEarned' => $creditsEarned,
                'eventPointsEarned' => $eventPointsAwarded,
                'attackerNewCredits' => $newBalance,
                'newBalance' => $newBalance,
                'log' => $log
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/factions/history
     */
    public function getAttackHistory($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 20);
            
            $stmt = $this->db->prepare("
                SELECT 
                    ta.id,
                    ta.territory_id as territoryId,
                    ta.attacker_id as attackerId,
                    ta.attacker_faction as attackerFaction,
                    ta.attacker_power as attackerPower,
                    ta.defense_power as defensePower,
                    ta.result,
                    ta.control_change as controlChange,
                    ta.credits_earned as creditsEarned,
                    ta.fought_at as foughtAt,
                    t.name as territoryName,
                    c.name as attackerName
                FROM territory_attacks ta
                JOIN territories t ON ta.territory_id = t.id
                JOIN combatants c ON ta.attacker_id = c.id
                ORDER BY ta.fought_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $history = $stmt->fetchAll();
            
            foreach ($history as &$h) {
                $h['id'] = (int)$h['id'];
                $h['attackerPower'] = (int)$h['attackerPower'];
                $h['defensePower'] = (int)$h['defensePower'];
                $h['controlChange'] = (int)$h['controlChange'];
                $h['creditsEarned'] = (int)$h['creditsEarned'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Attack history retrieved', [
                'history' => $history,
                'total' => count($history)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/factions/bonuses/{id}
     * Get active bonuses for a combatant
     */
    public function getActiveBonuses($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            // Get combatant faction
            $stmt = $this->db->prepare("SELECT faction FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Get controlled territories
            $stmt = $this->db->prepare("
                SELECT name, bonus_type as bonusType, bonus_value as bonusValue
                FROM territories
                WHERE controlling_faction = ?
            ");
            $stmt->execute([$combatant['faction']]);
            $territories = $stmt->fetchAll();
            
            // Sum bonuses by type
            $bonuses = [];
            foreach ($territories as $t) {
                if (!isset($bonuses[$t['bonusType']])) {
                    $bonuses[$t['bonusType']] = 0;
                }
                $bonuses[$t['bonusType']] += (int)$t['bonusValue'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Bonuses retrieved', [
                'faction' => $combatant['faction'],
                'territories' => $territories,
                'bonuses' => $bonuses
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