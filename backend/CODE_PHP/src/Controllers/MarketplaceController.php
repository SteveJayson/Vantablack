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
    
    /**
     * POST /api/marketplace/purchase
     * Purchase gear from the marketplace
     */
    public function purchase($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['gearId'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid request: combatantId and gearId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $gearId = $body['gearId'];
            $source = $body['source'] ?? null;
            
            // Get combatant details with lock
            $stmt = $this->db->prepare("
                SELECT id, name, credits, clearance_level as clearanceLevel 
                FROM combatants 
                WHERE id = ? FOR UPDATE
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Get gear details
            $stmt = $this->db->prepare("
                SELECT id, name, price, slot, source, clearance_required as clearanceRequired
                FROM gear_items 
                WHERE id = ?
            ");
            $stmt->execute([$gearId]);
            $gear = $stmt->fetch();
            
            if (!$gear) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Gear item not found');
            }
            
            // Check if combatant already owns this item
            $stmt = $this->db->prepare("
                SELECT id FROM inventory 
                WHERE combatant_id = ? AND gear_id = ?
            ");
            $stmt->execute([$combatantId, $gearId]);
            if ($stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Combatant already owns this gear');
            }
            
            // Check clearance level
            if ($combatant['clearanceLevel'] < $gear['clearanceRequired']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Insufficient clearance level');
            }
            
            // Check source restriction
            if ($source && $gear['source'] !== $source) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Gear not available from this source');
            }
            
            // Check if combatant has enough credits
            if ($combatant['credits'] < $gear['price']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 402, false, 'Insufficient credits', [
                    'required' => $gear['price'],
                    'available' => $combatant['credits'],
                    'shortfall' => $gear['price'] - $combatant['credits']
                ]);
            }
            
            // Deduct credits
            $newBalance = $combatant['credits'] - $gear['price'];
            $stmt = $this->db->prepare("
                UPDATE combatants 
                SET credits = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newBalance, $combatantId]);
            
            // Add to inventory
            $stmt = $this->db->prepare("
                INSERT INTO inventory (combatant_id, gear_id, equipped) 
                VALUES (?, ?, FALSE)
            ");
            $stmt->execute([$combatantId, $gearId]);
            
            $this->db->commit();
            
            // Return updated combatant data
            $stmt = $this->db->prepare("
                SELECT id, name, bio_capacity_max as bioCapacityMax, 
                       base_recovery as baseRecovery, base_risk as baseRisk,
                       credits, faction, clearance_level as clearanceLevel
                FROM combatants 
                WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $updatedCombatant = $stmt->fetch();
            
            return $this->jsonResponse($response, 200, true, 'Purchase successful', [
                'combatant' => $updatedCombatant,
                'purchased' => $gear,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/marketplace/inventory/{id}
     * Get a combatant's inventory
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
                    i.id as inventoryId,
                    i.equipped,
                    i.acquired_at as acquiredAt,
                    g.id as gearId,
                    g.slot,
                    g.source,
                    g.name,
                    g.price,
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
    
    /**
     * POST /api/marketplace/sell
     * Sell gear back to marketplace
     */
    public function sell($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['inventoryId'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid request: combatantId and inventoryId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $inventoryId = (int)$body['inventoryId'];
            
            // Get inventory item
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
                return $this->jsonResponse($response, 404, false, 'Inventory item not found');
            }
            
            // Calculate sell price (50% of original)
            $sellPrice = (int)($inventoryItem['price'] * 0.5);
            
            // Remove from inventory
            $stmt = $this->db->prepare("
                DELETE FROM inventory 
                WHERE id = ? AND combatant_id = ?
            ");
            $stmt->execute([$inventoryId, $combatantId]);
            
            // Add credits to combatant
            $stmt = $this->db->prepare("
                UPDATE combatants 
                SET credits = credits + ? 
                WHERE id = ?
            ");
            $stmt->execute([$sellPrice, $combatantId]);
            
            // If item was equipped, remove from loadout
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
                    $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'],
                    $inventoryItem['gear_id'],
                    $combatantId
                ]);
            }
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Gear sold successfully', [
                'inventoryId' => $inventoryId,
                'gearName' => $inventoryItem['name'],
                'sellPrice' => $sellPrice
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * JSON response helper
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