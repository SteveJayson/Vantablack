<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class EventController
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
     * POST /api/events/create
     */
    public function createEvent($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            if (!is_array($body)) {
                return $this->jsonResponse($response, 400, false, 'Valid event data required');
            }

            $adminId = (int)($body['adminId'] ?? 0);
            $adminStmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ? AND role = 'admin'");
            $adminStmt->execute([$adminId]);
            if (!$adminId || !$adminStmt->fetch()) {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }

            $nameInput = $body['name'] ?? '';
            $descriptionInput = $body['description'] ?? '';
            $iconInput = $body['icon'] ?? '🎉';
            if (!is_string($nameInput) || !is_string($descriptionInput) || !is_string($iconInput)) {
                return $this->jsonResponse($response, 400, false, 'Event name, description, and icon must be text');
            }

            $name = trim($nameInput);
            $description = trim($descriptionInput);
            $eventType = $body['eventType'] ?? 'special';
            $icon = trim($iconInput);
            $minRole = $body['minRole'] ?? 'any';
            $entryFee = filter_var($body['entryFee'] ?? 0, FILTER_VALIDATE_INT);
            $maxParticipants = filter_var($body['maxParticipants'] ?? 0, FILTER_VALIDATE_INT);

            if ($name === '' || strlen($name) > 600 || !in_array($eventType, ['holiday', 'tournament', 'special', 'weekly'], true)) {
                return $this->jsonResponse($response, 400, false, 'Event name and a valid event type are required');
            }
            if (!in_array($minRole, ['civilian', 'hero', 'villain', 'admin', 'any'], true)
                || $entryFee === false || $entryFee < 0
                || $maxParticipants === false || $maxParticipants < 0) {
                return $this->jsonResponse($response, 400, false, 'Invalid role, entry fee, or participant limit');
            }

            $startInput = $body['startDate'] ?? null;
            $endInput = $body['endDate'] ?? null;
            if (!is_string($startInput) || trim($startInput) === '' || !is_string($endInput) || trim($endInput) === '') {
                return $this->jsonResponse($response, 400, false, 'Valid start and end dates are required');
            }
            try {
                $startDate = new \DateTimeImmutable($startInput);
                $endDate = new \DateTimeImmutable($endInput);
            } catch (\Exception $e) {
                return $this->jsonResponse($response, 400, false, 'Valid start and end dates are required');
            }
            if ($endDate <= $startDate) {
                return $this->jsonResponse($response, 400, false, 'Event end date must be after the start date');
            }

            $requestedRewards = $body['rewardPool'] ?? [];
            if (!is_array($requestedRewards)) {
                return $this->jsonResponse($response, 400, false, 'Reward pool must be an object');
            }
            $rewardPool = [];
            foreach (['1st', '2nd', '3rd', 'top5', 'top10', 'participation'] as $place) {
                if (!isset($requestedRewards[$place])) {
                    continue;
                }
                if (!is_array($requestedRewards[$place])) {
                    return $this->jsonResponse($response, 400, false, 'Each reward must contain a credits object');
                }
                $credits = filter_var($requestedRewards[$place]['credits'] ?? null, FILTER_VALIDATE_INT);
                if ($credits === false || $credits < 0 || $credits > 2147483647) {
                    return $this->jsonResponse($response, 400, false, 'Reward credits must be a valid non-negative whole number');
                }
                if ($credits > 0) {
                    $rewardPool[$place] = ['credits' => $credits];
                }
            }
            if (!$rewardPool) {
                return $this->jsonResponse($response, 400, false, 'Add at least one credit reward');
            }

            $this->db->exec("SET time_zone = '+00:00'");
            $stmt = $this->db->prepare("
                INSERT INTO events
                    (name, description, event_type, icon, start_date, end_date, reward_pool, entry_fee, max_participants, min_role, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([
                $name,
                $description,
                $eventType,
                $icon !== '' ? $icon : '🎉',
                $startDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                $endDate->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                json_encode($rewardPool),
                $entryFee,
                $maxParticipants,
                $minRole
            ]);

            return $this->jsonResponse($response, 201, true, 'Event created', [
                'eventId' => (int)$this->db->lastInsertId(),
                'name' => $name
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/events
     */
    public function getEvents($request, $response, $args)
    {
        try {
            $combatantId = (int)$this->getQuery($request, 'combatantId', 0);
            $status = $this->getQuery($request, 'status', 'all');
            
            $query = "
                SELECT 
                    e.id,
                    e.name,
                    e.description,
                    e.event_type as eventType,
                    e.icon,
                    UNIX_TIMESTAMP(e.start_date) as startDateEpoch,
                    UNIX_TIMESTAMP(e.end_date) as endDateEpoch,
                    e.reward_pool as rewardPool,
                    e.entry_fee as entryFee,
                    e.max_participants as maxParticipants,
                    e.min_role as minRole,
                    e.is_active as isActive,
                    (SELECT COUNT(*) FROM event_participation WHERE event_id = e.id) as participantCount,
                    CASE 
                        WHEN NOW() < e.start_date THEN 'upcoming'
                        WHEN NOW() > e.end_date THEN 'ended'
                        ELSE 'active'
                    END as currentStatus
                FROM events e
                WHERE 1=1
            ";
            
            $params = [];
            
            if ($status === 'active') {
                $query .= " AND NOW() BETWEEN e.start_date AND e.end_date";
            } elseif ($status === 'upcoming') {
                $query .= " AND NOW() < e.start_date";
            } elseif ($status === 'ended') {
                $query .= " AND NOW() > e.end_date";
            }
            
            $query .= " ORDER BY 
                CASE 
                    WHEN NOW() BETWEEN e.start_date AND e.end_date THEN 0
                    WHEN NOW() < e.start_date THEN 1
                    ELSE 2
                END,
                e.start_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $events = $stmt->fetchAll();
            
            foreach ($events as &$event) {
                $event['id'] = (int)$event['id'];
                $event['entryFee'] = (int)$event['entryFee'];
                $event['maxParticipants'] = (int)$event['maxParticipants'];
                $event['participantCount'] = (int)$event['participantCount'];
                $event['isActive'] = (bool)$event['isActive'];
                $event['rewardPool'] = json_decode($event['rewardPool'], true);
                $event['startDate'] = gmdate('Y-m-d\\TH:i:s\\Z', (int)$event['startDateEpoch']);
                $event['endDate'] = gmdate('Y-m-d\\TH:i:s\\Z', (int)$event['endDateEpoch']);
                unset($event['startDateEpoch'], $event['endDateEpoch']);
                
                $event['myParticipation'] = null;
                if ($combatantId > 0) {
                    $stmt2 = $this->db->prepare("
                        SELECT score, final_rank as myRank, rewards_claimed as rewardsClaimed, joined_at as joinedAt
                        FROM event_participation
                        WHERE event_id = ? AND combatant_id = ?
                    ");
                    $stmt2->execute([$event['id'], $combatantId]);
                    $part = $stmt2->fetch();
                    if ($part) {
                        $event['myParticipation'] = [
                            'score' => (int)$part['score'],
                            'rank' => $part['myRank'] ? (int)$part['myRank'] : null,
                            'rewardsClaimed' => (bool)$part['rewardsClaimed'],
                            'joinedAt' => $part['joinedAt']
                        ];
                    }
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'Events retrieved', [
                'events' => $events,
                'total' => count($events)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/events/{id}
     */
    public function getEvent($request, $response, $args)
    {
        try {
            $eventId = $args['id'] ?? null;
            $combatantId = (int)$this->getQuery($request, 'combatantId', 0);
            
            if (!$eventId) {
                return $this->jsonResponse($response, 400, false, 'Event ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.*,
                    e.event_type as eventType,
                    UNIX_TIMESTAMP(e.start_date) as startDateEpoch,
                    UNIX_TIMESTAMP(e.end_date) as endDateEpoch,
                    e.reward_pool as rewardPool,
                    e.entry_fee as entryFee,
                    e.max_participants as maxParticipants,
                    e.min_role as minRole,
                    e.is_active as isActive,
                    (SELECT COUNT(*) FROM event_participation WHERE event_id = e.id) as participantCount,
                    CASE 
                        WHEN NOW() < e.start_date THEN 'upcoming'
                        WHEN NOW() > e.end_date THEN 'ended'
                        ELSE 'active'
                    END as currentStatus
                FROM events e
                WHERE e.id = ?
            ");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if (!$event) {
                return $this->jsonResponse($response, 404, false, 'Event not found');
            }
            
            $event['id'] = (int)$event['id'];
            $event['entryFee'] = (int)$event['entryFee'];
            $event['maxParticipants'] = (int)$event['maxParticipants'];
            $event['participantCount'] = (int)$event['participantCount'];
            $event['isActive'] = (bool)$event['isActive'];
            $event['rewardPool'] = json_decode($event['rewardPool'], true);
            $event['startDate'] = gmdate('Y-m-d\\TH:i:s\\Z', (int)$event['startDateEpoch']);
            $event['endDate'] = gmdate('Y-m-d\\TH:i:s\\Z', (int)$event['endDateEpoch']);
            unset($event['startDateEpoch'], $event['endDateEpoch']);
            
            // Leaderboard
            $stmt = $this->db->prepare("
                SELECT 
                    ep.combatant_id as combatantId,
                    c.name,
                    c.role,
                    c.faction,
                    ep.score,
                    ep.final_rank as myRank,
                    ep.joined_at as joinedAt
                FROM event_participation ep
                JOIN combatants c ON ep.combatant_id = c.id
                WHERE ep.event_id = ?
                ORDER BY ep.score DESC, ep.joined_at ASC
                LIMIT 20
            ");
            $stmt->execute([$eventId]);
            $leaderboard = $stmt->fetchAll();
            
            foreach ($leaderboard as &$entry) {
                $entry['combatantId'] = (int)$entry['combatantId'];
                $entry['score'] = (int)$entry['score'];
                $entry['rank'] = $entry['myRank'] ? (int)$entry['myRank'] : null;
            }
            
            $event['leaderboard'] = $leaderboard;
            
            // My participation
            $event['myParticipation'] = null;
            if ($combatantId > 0) {
                $stmt = $this->db->prepare("
                    SELECT score, final_rank as myRank, rewards_claimed as rewardsClaimed, joined_at as joinedAt
                    FROM event_participation
                    WHERE event_id = ? AND combatant_id = ?
                ");
                $stmt->execute([$eventId, $combatantId]);
                $part = $stmt->fetch();
                if ($part) {
                    $event['myParticipation'] = [
                        'score' => (int)$part['score'],
                        'rank' => $part['myRank'] ? (int)$part['myRank'] : null,
                        'rewardsClaimed' => (bool)$part['rewardsClaimed'],
                        'joinedAt' => $part['joinedAt']
                    ];
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'Event retrieved', [
                'event' => $event
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/events/join
     */
    public function joinEvent($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $eventId = (int)($body['eventId'] ?? 0);
            
            if (!$combatantId || !$eventId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and eventId required');
            }
            
            $stmt = $this->db->prepare("SELECT id, name, role, credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if (!$event) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Event not found');
            }
            
            $now = time();
            $start = strtotime($event['start_date']);
            $end = strtotime($event['end_date']);
            
            if ($now < $start) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Event has not started yet');
            }
            
            if ($now > $end) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Event has ended');
            }
            
            if ($event['min_role'] !== 'any' && $combatant['role'] !== $event['min_role']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Your role cannot join this event');
            }
            
            $stmt = $this->db->prepare("SELECT id FROM event_participation WHERE event_id = ? AND combatant_id = ?");
            $stmt->execute([$eventId, $combatantId]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Already joined this event');
            }
            
            if ($event['max_participants'] > 0) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM event_participation WHERE event_id = ?");
                $stmt->execute([$eventId]);
                $count = (int)$stmt->fetch()['cnt'];
                if ($count >= $event['max_participants']) {
                    $this->db->rollBack();
                    return $this->jsonResponse($response, 400, false, 'Event is full');
                }
            }
            
            $entryFee = (int)$event['entry_fee'];
            if ($entryFee > 0) {
                if ((int)$combatant['credits'] < $entryFee) {
                    $this->db->rollBack();
                    return $this->jsonResponse($response, 402, false, "Entry fee required: ₵{$entryFee}");
                }
                
                $stmt = $this->db->prepare("UPDATE combatants SET credits = credits - ? WHERE id = ?");
                $stmt->execute([$entryFee, $combatantId]);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO event_participation (event_id, combatant_id, score, joined_at)
                VALUES (?, ?, 0, NOW())
            ");
            $stmt->execute([$eventId, $combatantId]);
            
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $newBalance = (int)$stmt->fetch()['credits'];
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, "Joined {$event['name']}!", [
                'eventId' => $eventId,
                'eventName' => $event['name'],
                'entryFee' => $entryFee,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/events/score
     */
    public function addScore($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $eventId = (int)($body['eventId'] ?? 0);
            $points = (int)($body['points'] ?? 0);
            
            if (!$combatantId || !$eventId || $points <= 0) {
                return $this->jsonResponse($response, 400, false, 'combatantId, eventId, points required');
            }
            
            $stmt = $this->db->prepare("SELECT id FROM event_participation WHERE event_id = ? AND combatant_id = ?");
            $stmt->execute([$eventId, $combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 400, false, 'Not participating in this event');
            }
            
            $stmt = $this->db->prepare("
                UPDATE event_participation
                SET score = score + ?, last_action_at = NOW()
                WHERE event_id = ? AND combatant_id = ?
            ");
            $stmt->execute([$points, $eventId, $combatantId]);
            
            return $this->jsonResponse($response, 200, true, 'Score added', [
                'pointsAdded' => $points
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/events/claim-rewards
     */
    public function claimRewards($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $eventId = (int)($body['eventId'] ?? 0);
            
            if (!$combatantId || !$eventId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and eventId required');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if (!$event) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Event not found');
            }
            
            $now = time();
            $end = strtotime($event['end_date']);
            
            if ($now <= $end) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Event is still active');
            }
            
            $stmt = $this->db->prepare("SELECT * FROM event_participation WHERE event_id = ? AND combatant_id = ?");
            $stmt->execute([$eventId, $combatantId]);
            $participation = $stmt->fetch();
            
            if (!$participation) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Not participating in this event');
            }
            
            if ($participation['rewards_claimed']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Rewards already claimed');
            }
            
            $rank = $participation['final_rank'];
            if (!$rank) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) + 1 as my_rank
                    FROM event_participation
                    WHERE event_id = ? AND score > ?
                ");
                $stmt->execute([$eventId, $participation['score']]);
                $rank = (int)$stmt->fetch()['my_rank'];
                
                $stmt = $this->db->prepare("UPDATE event_participation SET final_rank = ? WHERE id = ?");
                $stmt->execute([$rank, $participation['id']]);
            }
            
            $rewardPool = json_decode($event['reward_pool'], true);
            $creditsEarned = 0;
            $gearReceived = null;
            $rewardMessages = [];
            
            if ($rank === 1 && isset($rewardPool['1st'])) {
                $creditsEarned = (int)($rewardPool['1st']['credits'] ?? 0);
                $gearReceived = $rewardPool['1st']['gear'] ?? null;
                $rewardMessages[] = "🏆 1st Place!";
            } elseif ($rank === 2 && isset($rewardPool['2nd'])) {
                $creditsEarned = (int)($rewardPool['2nd']['credits'] ?? 0);
                $gearReceived = $rewardPool['2nd']['gear'] ?? null;
                $rewardMessages[] = "🥈 2nd Place!";
            } elseif ($rank === 3 && isset($rewardPool['3rd'])) {
                $creditsEarned = (int)($rewardPool['3rd']['credits'] ?? 0);
                $gearReceived = $rewardPool['3rd']['gear'] ?? null;
                $rewardMessages[] = "🥉 3rd Place!";
            } elseif ($rank <= 10 && isset($rewardPool['top10'])) {
                $creditsEarned = (int)($rewardPool['top10']['credits'] ?? 0);
                $rewardMessages[] = "⭐ Top 10!";
            } elseif ($rank <= 5 && isset($rewardPool['top5'])) {
                $creditsEarned = (int)($rewardPool['top5']['credits'] ?? 0);
                $gearReceived = $rewardPool['top5']['gear'] ?? null;
                $rewardMessages[] = "⭐ Top 5!";
            } elseif (isset($rewardPool['participation'])) {
                $creditsEarned = (int)($rewardPool['participation']['credits'] ?? 0);
                $gearReceived = $rewardPool['participation']['gear'] ?? null;
                $rewardMessages[] = "🎉 Participation reward!";
            }
            
            if ($creditsEarned > 0) {
                $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
                $stmt->execute([$creditsEarned, $combatantId]);
                $rewardMessages[] = "💰 +₵{$creditsEarned}";
            }
            
            if ($gearReceived) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM inventory WHERE combatant_id = ? AND gear_id = ?");
                $stmt->execute([$combatantId, $gearReceived]);
                
                if ((int)$stmt->fetch()['cnt'] === 0) {
                    $stmt = $this->db->prepare("INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES (?, ?, FALSE)");
                    $stmt->execute([$combatantId, $gearReceived]);
                    
                    $stmt = $this->db->prepare("SELECT name FROM gear_items WHERE id = ?");
                    $stmt->execute([$gearReceived]);
                    $gearName = $stmt->fetch()['name'] ?? $gearReceived;
                    
                    $rewardMessages[] = "🎁 Received: {$gearName}";
                }
            }
            
            $stmt = $this->db->prepare("UPDATE event_participation SET rewards_claimed = TRUE WHERE id = ?");
            $stmt->execute([$participation['id']]);
            
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $newBalance = (int)$stmt->fetch()['credits'];
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Rewards claimed!', [
                'rank' => $rank,
                'creditsEarned' => $creditsEarned,
                'gearReceived' => $gearReceived,
                'messages' => $rewardMessages,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/events/active-count
     */
    public function getActiveCount($request, $response, $args)
    {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(*) as count FROM events
                WHERE NOW() BETWEEN start_date AND end_date AND is_active = TRUE
            ");
            $count = (int)$stmt->fetch()['count'];
            
            return $this->jsonResponse($response, 200, true, 'Active event count', [
                'count' => $count
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