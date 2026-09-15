<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class ChatController
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
     * GET /api/chat/messages?channel=global&limit=50
     */
    public function getMessages($request, $response, $args)
    {
        try {
            $channel = $this->getQuery($request, 'channel', 'global');
            $limit = (int)$this->getQuery($request, 'limit', 50);
            $combatantId = (int)$this->getQuery($request, 'combatantId', 0);
            $since = $this->getQuery($request, 'since', null);
            
            if (!in_array($channel, ['global', 'faction', 'trade'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid channel');
            }
            
            $query = "
                SELECT 
                    cm.id,
                    cm.combatant_id as combatantId,
                    cm.sender_name as senderName,
                    cm.sender_role as senderRole,
                    cm.sender_faction as senderFaction,
                    cm.channel,
                    cm.message,
                    cm.created_at as createdAt
                FROM chat_messages cm
            ";
            
            $params = [];
            $conditions = [];
            
            // Faction channel - filter by faction
            if ($channel === 'faction' && $combatantId > 0) {
                $stmt = $this->db->prepare("SELECT faction FROM combatants WHERE id = ?");
                $stmt->execute([$combatantId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $conditions[] = "cm.channel = 'faction'";
                    $conditions[] = "cm.sender_faction = ?";
                    $params[] = $user['faction'];
                } else {
                    $conditions[] = "cm.channel = 'faction'";
                    $conditions[] = "1=0";  // No access
                }
            } else {
                $conditions[] = "cm.channel = ?";
                $params[] = $channel;
            }
            
            // Since (for polling new messages only)
            if ($since) {
                $conditions[] = "cm.created_at > ?";
                $params[] = $since;
            }
            
            $query .= " WHERE " . implode(" AND ", $conditions);
            $query .= " ORDER BY cm.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $messages = $stmt->fetchAll();
            
            // Reverse to chronological order
            $messages = array_reverse($messages);
            
            foreach ($messages as &$m) {
                $m['id'] = (int)$m['id'];
                $m['combatantId'] = (int)$m['combatantId'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Messages retrieved', [
                'messages' => $messages,
                'channel' => $channel,
                'total' => count($messages)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/chat/send
     * Body: { "combatantId": 1, "channel": "global", "message": "Hello!" }
     */
    public function sendMessage($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $channel = $body['channel'] ?? 'global';
            $message = trim($body['message'] ?? '');
            
            if (!$combatantId || empty($message)) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and message required');
            }
            
            if (!in_array($channel, ['global', 'faction', 'trade'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Invalid channel');
            }
            
            if (strlen($message) > 500) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Message too long (max 500 chars)');
            }
            
            // Rate limit: max 3 messages per 10 seconds
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM chat_messages
                WHERE combatant_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
            ");
            $stmt->execute([$combatantId]);
            $recent = (int)$stmt->fetch()['cnt'];
            
            if ($recent >= 3) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 429, false, 'Slow down! Max 3 messages per 10 seconds');
            }
            
            // Get sender info
            $stmt = $this->db->prepare("
                SELECT id, name, role, faction FROM combatants WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $sender = $stmt->fetch();
            
            if (!$sender) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Cannot post to faction if admin
            if ($channel === 'faction' && $sender['role'] === 'admin') {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Admins cannot post to faction chat');
            }
            
            // Insert message
            $stmt = $this->db->prepare("
                INSERT INTO chat_messages 
                (combatant_id, sender_name, sender_role, sender_faction, channel, message, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $combatantId,
                $sender['name'],
                $sender['role'],
                $sender['faction'],
                $channel,
                $message
            ]);
            
            $messageId = (int)$this->db->lastInsertId();
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Message sent', [
                'id' => $messageId,
                'combatantId' => $combatantId,
                'senderName' => $sender['name'],
                'senderRole' => $sender['role'],
                'senderFaction' => $sender['faction'],
                'channel' => $channel,
                'message' => $message,
                'createdAt' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/chat/stats
     */
    public function getChatStats($request, $response, $args)
    {
        try {
            // Count messages per channel
            $stmt = $this->db->query("
                SELECT channel, COUNT(*) as count
                FROM chat_messages
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY channel
            ");
            $channelCounts = ['global' => 0, 'faction' => 0, 'trade' => 0];
            foreach ($stmt->fetchAll() as $row) {
                $channelCounts[$row['channel']] = (int)$row['count'];
            }
            
            // Top chatters (last 24h)
            $stmt = $this->db->query("
                SELECT 
                    sender_name as name,
                    sender_role as role,
                    COUNT(*) as messageCount
                FROM chat_messages
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY sender_name, sender_role
                ORDER BY messageCount DESC
                LIMIT 5
            ");
            $topChatters = $stmt->fetchAll();
            
            foreach ($topChatters as &$c) {
                $c['messageCount'] = (int)$c['messageCount'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Chat stats retrieved', [
                'channelCounts' => $channelCounts,
                'topChatters' => $topChatters,
                'totalMessages' => array_sum($channelCounts)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * DELETE /api/chat/message/{id}
     * Admin only - delete a message
     */
    public function deleteMessage($request, $response, $args)
    {
        try {
            $messageId = $args['id'] ?? null;
            $adminId = (int)$this->getQuery($request, 'adminId', 0);
            
            if (!$messageId || !$adminId) {
                return $this->jsonResponse($response, 400, false, 'messageId and adminId required');
            }
            
            // Check admin
            $stmt = $this->db->prepare("SELECT role FROM combatants WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || $admin['role'] !== 'admin') {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            $stmt = $this->db->prepare("DELETE FROM chat_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            
            return $this->jsonResponse($response, 200, true, 'Message deleted');
            
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