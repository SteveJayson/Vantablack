<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class RewardsController
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
     * Reward table - day => [credits, bonus]
     */
    private function getRewardTable(): array
    {
        return [
            1 => ['credits' => 100,  'bonus_type' => null,        'bonus_value' => null],
            2 => ['credits' => 200,  'bonus_type' => null,        'bonus_value' => null],
            3 => ['credits' => 300,  'bonus_type' => null,        'bonus_value' => null],
            4 => ['credits' => 500,  'bonus_type' => null,        'bonus_value' => null],
            5 => ['credits' => 750,  'bonus_type' => null,        'bonus_value' => null],
            6 => ['credits' => 1000, 'bonus_type' => null,        'bonus_value' => null],
            7 => ['credits' => 2000, 'bonus_type' => 'gear_drop', 'bonus_value' => 'g2-01'],
        ];
    }
    
    /**
     * GET /api/rewards/daily-status/{id}
     */
    public function getDailyStatus($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            // Get user streak info
            $stmt = $this->db->prepare("
                SELECT 
                    u.daily_streak,
                    u.last_daily_claim,
                    u.longest_daily_streak,
                    c.name,
                    c.credits
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return $this->jsonResponse($response, 404, false, 'User not found');
            }
            
            $currentStreak = (int)$user['daily_streak'];
            $lastClaim = $user['last_daily_claim'];
            
            // Determine if user can claim
            $canClaim = true;
            $nextStreak = $currentStreak + 1;
            $hoursUntilNext = 0;
            $streakBroken = false;
            
            if ($lastClaim) {
                $lastClaimTime = strtotime($lastClaim);
                $now = time();
                $hoursSince = ($now - $lastClaimTime) / 3600;
                
                if ($hoursSince < 24) {
                    $canClaim = false;
                    $hoursUntilNext = round(24 - $hoursSince, 1);
                } elseif ($hoursSince > 48) {
                    // Streak broken (> 48 hours since last claim)
                    $streakBroken = true;
                    $nextStreak = 1;
                }
            }
            
            // If streak exceeds 7, reset to 1
            if ($nextStreak > 7) {
                $nextStreak = 1;
            }
            
            $rewardTable = $this->getRewardTable();
            $currentReward = $rewardTable[$nextStreak] ?? $rewardTable[1];
            
            // Get recent claim history
            $stmt = $this->db->prepare("
                SELECT day_streak, credits_earned, bonus_type, bonus_value, claimed_at
                FROM daily_rewards_log
                WHERE combatant_id = ?
                ORDER BY claimed_at DESC
                LIMIT 10
            ");
            $stmt->execute([$combatantId]);
            $history = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Daily status retrieved', [
                'canClaim' => $canClaim,
                'currentStreak' => $currentStreak,
                'nextStreak' => $nextStreak,
                'longestStreak' => (int)$user['longest_daily_streak'],
                'lastClaim' => $lastClaim,
                'hoursUntilNext' => $hoursUntilNext,
                'streakBroken' => $streakBroken,
                'nextReward' => [
                    'day' => $nextStreak,
                    'credits' => $currentReward['credits'],
                    'bonusType' => $currentReward['bonus_type'],
                    'bonusValue' => $currentReward['bonus_value']
                ],
                'rewardTable' => $rewardTable,
                'history' => $history
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/rewards/claim-daily
     * Body: { "combatantId": 1 }
     */
    public function claimDaily($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            $combatantId = (int)($body['combatantId'] ?? 0);
            
            if (!$combatantId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            // Get user info
            $stmt = $this->db->prepare("
                SELECT 
                    u.id as user_id,
                    u.daily_streak,
                    u.last_daily_claim,
                    u.longest_daily_streak,
                    c.name,
                    c.credits
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'User not found');
            }
            
            $currentStreak = (int)$user['daily_streak'];
            $lastClaim = $user['last_daily_claim'];
            $nextStreak = $currentStreak + 1;
            $streakBroken = false;
            
            if ($lastClaim) {
                $lastClaimTime = strtotime($lastClaim);
                $now = time();
                $hoursSince = ($now - $lastClaimTime) / 3600;
                
                if ($hoursSince < 24) {
                    $this->db->rollBack();
                    return $this->jsonResponse($response, 400, false, 'Already claimed today', [
                        'hoursUntilNext' => round(24 - $hoursSince, 1)
                    ]);
                }
                
                if ($hoursSince > 48) {
                    // Streak broken
                    $streakBroken = true;
                    $nextStreak = 1;
                }
            }
            
            // Cap at 7 days
            if ($nextStreak > 7) {
                $nextStreak = 1;
                $streakBroken = true;
            }
            
            $rewardTable = $this->getRewardTable();
            $reward = $rewardTable[$nextStreak] ?? $rewardTable[1];
            
            $creditsEarned = $reward['credits'];
            $bonusType = $reward['bonus_type'];
            $bonusValue = $reward['bonus_value'];
            $bonusMessage = null;
            
            // Award credits
            $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$creditsEarned, $combatantId]);
            
            // Day 7: award bonus gear
            if ($bonusType === 'gear_drop' && $bonusValue) {
                // Check if already owns it
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as cnt FROM inventory 
                    WHERE combatant_id = ? AND gear_id = ?
                ");
                $stmt->execute([$combatantId, $bonusValue]);
                
                if ((int)$stmt->fetch()['cnt'] === 0) {
                    // Add to inventory
                    $stmt = $this->db->prepare("
                        INSERT INTO inventory (combatant_id, gear_id, equipped) 
                        VALUES (?, ?, FALSE)
                    ");
                    $stmt->execute([$combatantId, $bonusValue]);
                    
                    // Get gear name
                    $stmt = $this->db->prepare("SELECT name FROM gear_items WHERE id = ?");
                    $stmt->execute([$bonusValue]);
                    $gearName = $stmt->fetch()['name'] ?? 'Bonus Gear';
                    
                    $bonusMessage = "🎁 BONUS: Received '$gearName'!";
                } else {
                    // Already owns it, give extra credits instead
                    $bonusCredits = 1500;
                    $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
                    $stmt->execute([$bonusCredits, $combatantId]);
                    $creditsEarned += $bonusCredits;
                    $bonusMessage = "🎁 BONUS: Already owned the gear. Received ₵$bonusCredits extra!";
                }
            }
            
            // Update longest streak
            $newLongest = max((int)$user['longest_daily_streak'], $nextStreak);
            
            // Update user
            $stmt = $this->db->prepare("
                UPDATE users 
                SET daily_streak = ?,
                    last_daily_claim = NOW(),
                    longest_daily_streak = ?
                WHERE combatant_id = ?
            ");
            $stmt->execute([$nextStreak, $newLongest, $combatantId]);
            
            // Log claim
            $stmt = $this->db->prepare("
                INSERT INTO daily_rewards_log 
                (combatant_id, day_streak, credits_earned, bonus_type, bonus_value, claimed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$combatantId, $nextStreak, $creditsEarned, $bonusType, $bonusValue]);
            
            // Get new balance
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $newBalance = (int)$stmt->fetch()['credits'];
            
            // Log activity
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO activity_log (combatant_id, activity_type, details, credits_change, logged_at)
                    VALUES (?, 'mission', ?, ?, NOW())
                ");
                $stmt->execute([
                    $combatantId,
                    json_encode(['type' => 'daily_reward', 'day' => $nextStreak]),
                    $creditsEarned
                ]);
            } catch (\Exception $e) {}
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Daily reward claimed!', [
                'day' => $nextStreak,
                'creditsEarned' => $creditsEarned,
                'bonusMessage' => $bonusMessage,
                'streakBroken' => $streakBroken,
                'newBalance' => $newBalance,
                'newStreak' => $nextStreak,
                'longestStreak' => $newLongest,
                'nextClaimHours' => 24
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/rewards/leaderboard
     */
    public function getStreakLeaderboard($request, $response, $args)
    {
        try {
            $limit = (int)$this->getQuery($request, 'limit', 10);
            
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.name,
                    c.role,
                    u.daily_streak as currentStreak,
                    u.longest_daily_streak as longestStreak,
                    u.last_daily_claim as lastClaim
                FROM users u
                JOIN combatants c ON u.combatant_id = c.id
                WHERE u.longest_daily_streak > 0
                ORDER BY u.longest_daily_streak DESC, u.daily_streak DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $leaderboard = $stmt->fetchAll();
            
            foreach ($leaderboard as &$l) {
                $l['id'] = (int)$l['id'];
                $l['currentStreak'] = (int)$l['currentStreak'];
                $l['longestStreak'] = (int)$l['longestStreak'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Streak leaderboard retrieved', [
                'leaderboard' => $leaderboard
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/rewards/history/{id}
     */
    public function getClaimHistory($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            $limit = (int)$this->getQuery($request, 'limit', 20);
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    day_streak as dayStreak,
                    credits_earned as creditsEarned,
                    bonus_type as bonusType,
                    bonus_value as bonusValue,
                    claimed_at as claimedAt
                FROM daily_rewards_log
                WHERE combatant_id = ?
                ORDER BY claimed_at DESC
                LIMIT ?
            ");
            $stmt->execute([$combatantId, $limit]);
            $history = $stmt->fetchAll();
            
            foreach ($history as &$h) {
                $h['id'] = (int)$h['id'];
                $h['dayStreak'] = (int)$h['dayStreak'];
                $h['creditsEarned'] = (int)$h['creditsEarned'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Claim history retrieved', [
                'history' => $history,
                'total' => count($history)
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