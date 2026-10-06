<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class MarketplaceController
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
     * POST /api/marketplace/purchase
     */
    public function purchase($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['gearId'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and gearId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $gearId = $body['gearId'];
            
            $stmt = $this->db->prepare("
                SELECT id, name, credits, role, clearance_level as clearanceLevel 
                FROM combatants WHERE id = ? FOR UPDATE
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            if ($combatant['role'] === 'admin') {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Admins cannot purchase gear');
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, price, slot, source, clearance_required as clearanceRequired
                FROM gear_items WHERE id = ?
            ");
            $stmt->execute([$gearId]);
            $gear = $stmt->fetch();
            
            if (!$gear) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Gear not found');
            }
            
            // Allow duplicate purchases for crafting/material gathering
            // Check clearance
            if ($combatant['clearanceLevel'] < $gear['clearanceRequired']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Insufficient clearance');
            }
            
            // Check credits
            if ($combatant['credits'] < $gear['price']) {
                $this->db->rollBack();
                $this->logTransaction($combatant, 'purchase', $gear, 'failed', $combatant['credits'], $combatant['credits']);
                return $this->jsonResponse($response, 402, false, 'Insufficient credits', [
                    'required' => $gear['price'],
                    'available' => $combatant['credits']
                ]);
            }
            
            $creditsBefore = $combatant['credits'];
            $newBalance = $creditsBefore - $gear['price'];
            
            // Deduct credits
            $stmt = $this->db->prepare("UPDATE combatants SET credits = ? WHERE id = ?");
            $stmt->execute([$newBalance, $combatantId]);
            
            // Add to inventory
            $stmt = $this->db->prepare("INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES (?, ?, FALSE)");
            $stmt->execute([$combatantId, $gearId]);
            
            $this->logTransaction($combatant, 'purchase', $gear, 'completed', $newBalance, $creditsBefore);
            
            // 🔔 Create notification
try {
    $stmt = $this->db->prepare("
        INSERT INTO notifications 
        (combatant_id, notification_type, title, message, icon, priority, created_at)
        VALUES (?, 'purchase', ?, ?, '🛒', 'normal', NOW())
    ");
    $stmt->execute([
        $combatantId,
        "Purchase Successful",
        "You bought {$gear['name']} for ₵" . number_format($gear['price'])
    ]);
} catch (\Exception $e) {}

            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Purchase successful', [
                'newBalance' => $newBalance,
                'purchased' => $gear
            ]);

            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/marketplace/sell
     */
    public function sell($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['inventoryId'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and inventoryId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $inventoryId = (int)$body['inventoryId'];
            
            $stmt = $this->db->prepare("SELECT id, name, credits, role FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            if (!in_array($combatant['role'], ['hero', 'villain'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Only heroes and villains can sell gear');
            }
            
            $stmt = $this->db->prepare("
                SELECT i.*, g.price, g.name, g.id as gear_id 
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                WHERE i.id = ? AND i.combatant_id = ?
            ");
            $stmt->execute([$inventoryId, $combatantId]);
            $inventoryItem = $stmt->fetch();
            
            if (!$inventoryItem) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Item not found');
            }
            
            $sellPrice = (int)($inventoryItem['price'] * 0.5);
            $creditsBefore = $combatant['credits'];
            $newBalance = $creditsBefore + $sellPrice;
            
            $stmt = $this->db->prepare("DELETE FROM inventory WHERE id = ? AND combatant_id = ?");
            $stmt->execute([$inventoryId, $combatantId]);
            
            $stmt = $this->db->prepare("UPDATE combatants SET credits = ? WHERE id = ?");
            $stmt->execute([$newBalance, $combatantId]);
            
            if ($inventoryItem['equipped']) {
                $stmt = $this->db->prepare("
                    UPDATE loadouts 
                    SET helmet_id = CASE WHEN helmet_id = ? THEN NULL ELSE helmet_id END,
                        core_id = CASE WHEN core_id = ? THEN NULL ELSE core_id END,
                        dampener_id = CASE WHEN dampener_id = ? THEN NULL ELSE dampener_id END,
                        gauntlets_id = CASE WHEN gauntlets_id = ? THEN NULL ELSE gauntlets_id END,
                        battery_id = CASE WHEN battery_id = ? THEN NULL ELSE battery_id END
                    WHERE combatant_id = ?
                ");
                $stmt->execute([
                    $inventoryItem['gear_id'], $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'], $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'], $combatantId
                ]);
            }
            
            $gear = ['id' => $inventoryItem['gear_id'], 'name' => $inventoryItem['name'], 'price' => $sellPrice];
            $this->logTransaction($combatant, 'sell', $gear, 'completed', $newBalance, $creditsBefore, $sellPrice);
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Gear sold successfully', [
                'gearName' => $inventoryItem['name'],
                'sellPrice' => $sellPrice,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/marketplace/inventory/{id}
     */
    public function getInventory($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    i.id as inventoryId, i.equipped, i.acquired_at as acquiredAt,
                    g.id as gearId, g.slot, g.source, g.name, g.price,
                    g.bio_capacity as bioCapacity,
                    g.recovery_rate as recoveryRate,
                    g.risk_modifier as riskModifier,
                    g.clearance_required as clearanceRequired
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                WHERE i.combatant_id = ?
                ORDER BY g.slot, g.name
            ");
            $stmt->execute([$combatantId]);
            $inventory = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Inventory retrieved', ['inventory' => $inventory]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function logTransaction(array $combatant, string $type, array $gear, string $status, int $balanceAfter, int $balanceBefore, ?int $customAmount = null): void
    {
        try {
            $amount = $customAmount ?? ($gear['price'] ?? 0);
            
            $stmt = $this->db->prepare("
                INSERT INTO transactions 
                (combatant_id, combatant_name, combatant_role, transaction_type, 
                 gear_id, gear_name, amount, credits_before, credits_after, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $combatant['id'], $combatant['name'], $combatant['role'],
                $type, $gear['id'], $gear['name'], $amount,
                $balanceBefore, $balanceAfter, $status
            ]);
        } catch (\Exception $e) {}
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