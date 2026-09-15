<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class ReplayController
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
     * POST /api/replays/save
     * Save a battle replay
     */
    public function saveReplay($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $battleData = $body['battle'] ?? null;
            
            if (!$combatantId || !$battleData) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and battle data required');
            }
            
            // Build replay steps
            $steps = [];
            
            // Step 1: Battle start
            $steps[] = [
                'step' => 1,
                'type' => 'start',
                'message' => "⚔️ {$battleData['attackerName']} challenges {$battleData['defenderName']}!",
                'attackerHP' => 100,
                'defenderHP' => 100,
                'timestamp' => 0
            ];
            
            // Step 2: Power reveal
            $steps[] = [
                'step' => 2,
                'type' => 'reveal',
                'message' => "📊 Power: {$battleData['attackerName']} ({$battleData['attackerPower']}) vs {$battleData['defenderName']} ({$battleData['defenderPower']})",
                'attackerHP' => 100,
                'defenderHP' => 100,
                'timestamp' => 1500
            ];
            
            // Step 3-N: Battle actions
            $stepNum = 3;
            $attackerHP = 100;
            $defenderHP = 100;
            $timestamp = 3000;
            
            // Attack 1
            if (isset($battleData['damageDealt'])) {
                $defenderHP -= min(50, $battleData['damageDealt'] / 10);
                $steps[] = [
                    'step' => $stepNum++,
                    'type' => 'attack',
                    'attacker' => 'user',
                    'message' => "💥 {$battleData['attackerName']} strikes for {$battleData['damageDealt']} damage!",
                    'attackerHP' => max(0, round($attackerHP)),
                    'defenderHP' => max(0, round($defenderHP)),
                    'damage' => $battleData['damageDealt'],
                    'timestamp' => $timestamp
                ];
                $timestamp += 1500;
            }
            
            // Counter attack
            if (isset($battleData['damageTaken'])) {
                $attackerHP -= min(50, $battleData['damageTaken'] / 10);
                $steps[] = [
                    'step' => $stepNum++,
                    'type' => 'counter',
                    'attacker' => 'opponent',
                    'message' => "🛡️ {$battleData['defenderName']} counters for {$battleData['damageTaken']} damage!",
                    'attackerHP' => max(0, round($attackerHP)),
                    'defenderHP' => max(0, round($defenderHP)),
                    'damage' => $battleData['damageTaken'],
                    'timestamp' => $timestamp
                ];
                $timestamp += 1500;
            }
            
            // Final blow
            $steps[] = [
                'step' => $stepNum++,
                'type' => 'critical',
                'message' => "🎯 {$battleData['winnerName']} lands a critical blow!",
                'attackerHP' => max(0, round($attackerHP)),
                'defenderHP' => $battleData['winnerId'] == $combatantId ? 10 : 0,
                'timestamp' => $timestamp
            ];
            $timestamp += 1500;
            
            // Victory
            $steps[] = [
                'step' => $stepNum++,
                'type' => 'victory',
                'message' => "🏆 {$battleData['winnerName']} WINS THE BATTLE!",
                'attackerHP' => $battleData['winnerId'] == $combatantId ? 30 : 0,
                'defenderHP' => $battleData['winnerId'] == $combatantId ? 0 : 30,
                'timestamp' => $timestamp
            ];
            $timestamp += 1500;
            
            // Rewards
            $steps[] = [
                'step' => $stepNum++,
                'type' => 'reward',
                'message' => "💰 Credits: " . ($battleData['creditsEarned'] >= 0 ? '+' : '') . "₵{$battleData['creditsEarned']}",
                'attackerHP' => $battleData['winnerId'] == $combatantId ? 30 : 0,
                'defenderHP' => $battleData['winnerId'] == $combatantId ? 0 : 30,
                'timestamp' => $timestamp
            ];
            
            $replayData = [
                'attackerName' => $battleData['attackerName'] ?? 'Attacker',
                'defenderName' => $battleData['defenderName'] ?? 'Defender',
                'attackerPower' => $battleData['attackerPower'] ?? 0,
                'defenderPower' => $battleData['defenderPower'] ?? 0,
                'winnerId' => $battleData['winnerId'],
                'winnerName' => $battleData['winnerName'] ?? 'Winner',
                'result' => $battleData['result'] ?? 'win',
                'creditsEarned' => $battleData['creditsEarned'] ?? 0,
                'damageDealt' => $battleData['damageDealt'] ?? 0,
                'damageTaken' => $battleData['damageTaken'] ?? 0,
                'steps' => $steps
            ];
            
            // Save to database
            $stmt = $this->db->prepare("
                INSERT INTO battle_replays 
                (combatant_id, opponent_id, opponent_name, replay_data, winner_id, result, 
                 total_damage_dealt, total_damage_taken, replay_duration, is_public, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->execute([
                $combatantId,
                $battleData['defenderId'] ?? null,
                $battleData['defenderName'] ?? 'Unknown',
                json_encode($replayData),
                $battleData['winnerId'],
                $battleData['result'] ?? 'win',
                $battleData['damageDealt'] ?? 0,
                $battleData['damageTaken'] ?? 0,
                count($steps) * 1500
            ]);
            
            $replayId = (int)$this->db->lastInsertId();
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Replay saved', [
                'replayId' => $replayId
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/replays/{id}
     * Get all replays for a combatant
     */
    public function getReplays($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            $limit = (int)$this->getQuery($request, 'limit', 20);
            $result = $this->getQuery($request, 'result', 'all');
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $query = "
                SELECT 
                    id,
                    opponent_id as opponentId,
                    opponent_name as opponentName,
                    winner_id as winnerId,
                    result,
                    total_damage_dealt as damageDealt,
                    total_damage_taken as damageTaken,
                    replay_duration as duration,
                    views,
                    created_at as createdAt
                FROM battle_replays
                WHERE combatant_id = ?
            ";
            
            $params = [$combatantId];
            
            if ($result === 'win' || $result === 'loss') {
                $query .= " AND result = ?";
                $params[] = $result;
            }
            
            $query .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $replays = $stmt->fetchAll();
            
            // Stats
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN result = 'win' THEN 1 ELSE 0 END) as wins,
                    SUM(CASE WHEN result = 'loss' THEN 1 ELSE 0 END) as losses,
                    COALESCE(SUM(views), 0) as totalViews
                FROM battle_replays
                WHERE combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $stats = $stmt->fetch();
            
            foreach ($replays as &$r) {
                $r['id'] = (int)$r['id'];
                $r['winnerId'] = (int)$r['winnerId'];
                $r['damageDealt'] = (int)$r['damageDealt'];
                $r['damageTaken'] = (int)$r['damageTaken'];
                $r['duration'] = (int)$r['duration'];
                $r['views'] = (int)$r['views'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Replays retrieved', [
                'replays' => $replays,
                'total' => count($replays),
                'stats' => [
                    'totalReplays' => (int)$stats['total'],
                    'wins' => (int)$stats['wins'],
                    'losses' => (int)$stats['losses'],
                    'totalViews' => (int)$stats['totalViews']
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/replays/detail/{id}
     * Get a specific replay with full data
     */
    public function getReplayDetail($request, $response, $args)
    {
        try {
            $replayId = $args['id'] ?? null;
            
            if (!$replayId) {
                return $this->jsonResponse($response, 400, false, 'Replay ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    br.*,
                    c.name as ownerName,
                    c.role as ownerRole
                FROM battle_replays br
                JOIN combatants c ON br.combatant_id = c.id
                WHERE br.id = ?
            ");
            $stmt->execute([$replayId]);
            $replay = $stmt->fetch();
            
            if (!$replay) {
                return $this->jsonResponse($response, 404, false, 'Replay not found');
            }
            
            // Increment views
            $stmt = $this->db->prepare("UPDATE battle_replays SET views = views + 1 WHERE id = ?");
            $stmt->execute([$replayId]);
            
            $replay['id'] = (int)$replay['id'];
            $replay['combatant_id'] = (int)$replay['combatant_id'];
            $replay['winner_id'] = (int)$replay['winner_id'];
            $replay['views'] = (int)$replay['views'] + 1;
            $replay['replay_data'] = json_decode($replay['replay_data'], true);
            $replay['is_public'] = (bool)$replay['is_public'];
            
            return $this->jsonResponse($response, 200, true, 'Replay retrieved', [
                'replay' => $replay
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/replays/shared
     * Get public replays from all combatants
     */
    public function getSharedReplays($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 20);
            $sort = $this->getQuery($request, 'sort', 'recent');
            
            $orderBy = $sort === 'popular' ? 'views DESC' : 'created_at DESC';
            
            $stmt = $this->db->prepare("
                SELECT 
                    br.id,
                    br.opponent_name as opponentName,
                    br.winner_id as winnerId,
                    br.result,
                    br.total_damage_dealt as damageDealt,
                    br.views,
                    br.created_at as createdAt,
                    c.name as ownerName,
                    c.role as ownerRole,
                    c.faction as ownerFaction
                FROM battle_replays br
                JOIN combatants c ON br.combatant_id = c.id
                WHERE br.is_public = TRUE
                ORDER BY $orderBy
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $replays = $stmt->fetchAll();
            
            foreach ($replays as &$r) {
                $r['id'] = (int)$r['id'];
                $r['winnerId'] = (int)$r['winnerId'];
                $r['damageDealt'] = (int)$r['damageDealt'];
                $r['views'] = (int)$r['views'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Shared replays retrieved', [
                'replays' => $replays,
                'total' => count($replays)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * DELETE /api/replays/{id}
     */
    public function deleteReplay($request, $response, $args)
    {
        try {
            $replayId = $args['id'] ?? null;
            $combatantId = (int)$this->getQuery($request, 'combatantId', 0);
            
            if (!$replayId || !$combatantId) {
                return $this->jsonResponse($response, 400, false, 'replayId and combatantId required');
            }
            
            // Verify ownership
            $stmt = $this->db->prepare("SELECT id FROM battle_replays WHERE id = ? AND combatant_id = ?");
            $stmt->execute([$replayId, $combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 403, false, 'Not authorized to delete this replay');
            }
            
            $stmt = $this->db->prepare("DELETE FROM battle_replays WHERE id = ?");
            $stmt->execute([$replayId]);
            
            return $this->jsonResponse($response, 200, true, 'Replay deleted');
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/replays/toggle-visibility/{id}
     */
    public function toggleVisibility($request, $response, $args)
    {
        try {
            $replayId = $args['id'] ?? null;
            $body = json_decode($request->getBody()->getContents(), true);
            $combatantId = (int)($body['combatantId'] ?? 0);
            
            if (!$replayId || !$combatantId) {
                return $this->jsonResponse($response, 400, false, 'replayId and combatantId required');
            }
            
            $stmt = $this->db->prepare("
                UPDATE battle_replays 
                SET is_public = NOT is_public 
                WHERE id = ? AND combatant_id = ?
            ");
            $stmt->execute([$replayId, $combatantId]);
            
            $stmt = $this->db->prepare("SELECT is_public FROM battle_replays WHERE id = ?");
            $stmt->execute([$replayId]);
            $result = $stmt->fetch();
            
            return $this->jsonResponse($response, 200, true, 'Visibility updated', [
                'isPublic' => (bool)$result['is_public']
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