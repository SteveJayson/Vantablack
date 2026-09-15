<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class NotificationController
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
     * GET /api/notifications/{id}
     * Get all notifications for a combatant
     */
    public function getNotifications($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            $limit = (int)$this->getQuery($request, 'limit', 20);
            $unreadOnly = $this->getQuery($request, 'unreadOnly', 'false') === 'true';
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $query = "
                SELECT 
                    id,
                    notification_type as type,
                    title,
                    message,
                    icon,
                    action_url as actionUrl,
                    is_read as isRead,
                    priority,
                    created_at as createdAt
                FROM notifications
                WHERE combatant_id = ?
            ";
            
            $params = [$combatantId];
            
            if ($unreadOnly) {
                $query .= " AND is_read = FALSE";
            }
            
            $query .= " ORDER BY 
                CASE priority 
                    WHEN 'urgent' THEN 0
                    WHEN 'high' THEN 1
                    WHEN 'normal' THEN 2
                    ELSE 3
                END,
                created_at DESC
                LIMIT ?";
            
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();
            
            // Count unread
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as unread FROM notifications
                WHERE combatant_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$combatantId]);
            $unreadCount = (int)$stmt->fetch()['unread'];
            
            foreach ($notifications as &$n) {
                $n['id'] = (int)$n['id'];
                $n['isRead'] = (bool)$n['isRead'];
            }
            
            return $this->jsonResponse($response, 200, true, 'Notifications retrieved', [
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'total' => count($notifications)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/notifications/{id}/unread-count
     */
    public function getUnreadCount($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as unread FROM notifications
                WHERE combatant_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$combatantId]);
            $unread = (int)$stmt->fetch()['unread'];
            
            return $this->jsonResponse($response, 200, true, 'Unread count retrieved', [
                'unreadCount' => $unread
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/notifications/create
     * Create a notification (used by other controllers)
     */
    public function createNotification($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $type = $body['type'] ?? 'system';
            $title = trim($body['title'] ?? '');
            $message = trim($body['message'] ?? '');
            $icon = $body['icon'] ?? '🔔';
            $actionUrl = $body['actionUrl'] ?? null;
            $priority = $body['priority'] ?? 'normal';
            
            if (!$combatantId || empty($title) || empty($message)) {
                return $this->jsonResponse($response, 400, false, 'combatantId, title, message required');
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO notifications 
                (combatant_id, notification_type, title, message, icon, action_url, priority, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$combatantId, $type, $title, $message, $icon, $actionUrl, $priority]);
            
            $notificationId = (int)$this->db->lastInsertId();
            
            return $this->jsonResponse($response, 200, true, 'Notification created', [
                'id' => $notificationId
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/notifications/{id}/read
     * Mark a notification as read
     */
    public function markAsRead($request, $response, $args)
    {
        try {
            $notificationId = $args['id'] ?? null;
            $body = json_decode($request->getBody()->getContents(), true);
            $combatantId = (int)($body['combatantId'] ?? 0);
            
            if (!$notificationId || !$combatantId) {
                return $this->jsonResponse($response, 400, false, 'notificationId and combatantId required');
            }
            
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND combatant_id = ?
            ");
            $stmt->execute([$notificationId, $combatantId]);
            
            return $this->jsonResponse($response, 200, true, 'Marked as read');
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/notifications/read-all
     * Mark all notifications as read
     */
    public function markAllAsRead($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            $combatantId = (int)($body['combatantId'] ?? 0);
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'combatantId required');
            }
            
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE combatant_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$combatantId]);
            
            $affected = $stmt->rowCount();
            
            return $this->jsonResponse($response, 200, true, 'All marked as read', [
                'markedCount' => $affected
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * DELETE /api/notifications/{id}
     */
    public function deleteNotification($request, $response, $args)
    {
        try {
            $notificationId = $args['id'] ?? null;
            $combatantId = (int)$this->getQuery($request, 'combatantId', 0);
            
            if (!$notificationId || !$combatantId) {
                return $this->jsonResponse($response, 400, false, 'notificationId and combatantId required');
            }
            
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND combatant_id = ?
            ");
            $stmt->execute([$notificationId, $combatantId]);
            
            return $this->jsonResponse($response, 200, true, 'Notification deleted');
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/notifications/broadcast
     * Admin: broadcast to all users or specific role
     */
    public function broadcast($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $adminId = (int)($body['adminId'] ?? 0);
            $targetRole = $body['targetRole'] ?? 'all'; // all, civilian, hero, villain
            $title = trim($body['title'] ?? '');
            $message = trim($body['message'] ?? '');
            $icon = $body['icon'] ?? '📢';
            $priority = $body['priority'] ?? 'normal';
            
            if (!$adminId || empty($title) || empty($message)) {
                return $this->jsonResponse($response, 400, false, 'adminId, title, message required');
            }
            
            // Verify admin
            $stmt = $this->db->prepare("SELECT role FROM combatants WHERE id = ?");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || $admin['role'] !== 'admin') {
                return $this->jsonResponse($response, 403, false, 'Admin access required');
            }
            
            // Get target users
            if ($targetRole === 'all') {
                $stmt = $this->db->query("SELECT id FROM combatants");
            } else {
                $stmt = $this->db->prepare("SELECT id FROM combatants WHERE role = ?");
                $stmt->execute([$targetRole]);
            }
            $users = $stmt->fetchAll();
            
            // Insert notifications for all
            $inserted = 0;
            $stmt = $this->db->prepare("
                INSERT INTO notifications 
                (combatant_id, notification_type, title, message, icon, priority, created_at)
                VALUES (?, 'system', ?, ?, ?, ?, NOW())
            ");
            
            foreach ($users as $user) {
                $stmt->execute([$user['id'], $title, $message, $icon, $priority]);
                $inserted++;
            }
            
            return $this->jsonResponse($response, 200, true, 'Broadcast sent', [
                'sentTo' => $inserted
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper: Create a notification (used internally by other controllers)
     */
    public static function notify(PDO $db, int $combatantId, string $type, string $title, string $message, string $icon = '🔔', string $priority = 'normal', ?string $actionUrl = null): void
    {
        try {
            $stmt = $db->prepare("
                INSERT INTO notifications 
                (combatant_id, notification_type, title, message, icon, action_url, priority, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$combatantId, $type, $title, $message, $icon, $actionUrl, $priority]);
        } catch (\Exception $e) {
            // Silently fail — notifications shouldn't break main flow
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