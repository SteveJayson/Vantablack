<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\FeasibilityEngine;
use PDO;

class LoadoutController
{
    private PDO $db;
    private FeasibilityEngine $engine;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->engine = new FeasibilityEngine();
    }
    
    /**
     * GET /api/combatants/{id}/loadout
     * Get combatant's current loadout
     */
    public function getCombatantLoadout($request, $response, $args)
    {
        $combatantId = $args['id'] ?? null;
        
        if (!$combatantId) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.helmet_id,
                    l.core_id,
                    l.dampener_id,
                    l.gauntlets_id,
                    l.battery_id,
                    g1.name as helmet_name,
                    g2.name as core_name,
                    g3.name as dampener_name,
                    g4.name as gauntlets_name,
                    g5.name as battery_name,
                    g1.bio_capacity as helmet_bio,
                    g2.bio_capacity as core_bio,
                    g3.bio_capacity as dampener_bio,
                    g4.bio_capacity as gauntlets_bio,
                    g5.bio_capacity as battery_bio
                FROM loadouts l
                LEFT JOIN gear_items g1 ON l.helmet_id = g1.id
                LEFT JOIN gear_items g2 ON l.core_id = g2.id
                LEFT JOIN gear_items g3 ON l.dampener_id = g3.id
                LEFT JOIN gear_items g4 ON l.gauntlets_id = g4.id
                LEFT JOIN gear_items g5 ON l.battery_id = g5.id
                WHERE l.combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $loadout = $stmt->fetch();
            
            if (!$loadout) {
                return $this->jsonResponse($response, 404, false, 'No loadout found for this combatant');
            }
            
            return $this->jsonResponse($response, 200, true, 'Loadout retrieved', ['loadout' => $loadout]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/loadouts/validate
     * Validates a loadout configuration without saving it
     */
    public function validateLoadout($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['loadout'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid request: combatantId and loadout required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $loadout = $body['loadout'];
            
            // Get combatant details
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    name,
                    bio_capacity_max as bioCapacityMax,
                    base_recovery as baseRecovery,
                    base_risk as baseRisk,
                    credits,
                    faction,
                    clearance_level as clearanceLevel
                FROM combatants
                WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Validate loadout items
            $validatedLoadout = [];
            $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
            
            foreach ($slots as $slot) {
                if (isset($loadout[$slot]) && $loadout[$slot] !== null) {
                    $gear = $loadout[$slot];
                    // If gear is just an ID, fetch full details
                    if (is_string($gear) || is_numeric($gear)) {
                        $gearStmt = $this->db->prepare("
                            SELECT 
                                id,
                                slot,
                                source,
                                name,
                                price,
                                bio_capacity as bioCapacity,
                                recovery_rate as recoveryRate,
                                risk_modifier as riskModifier,
                                clearance_required as clearanceRequired
                            FROM gear_items
                            WHERE id = ?
                        ");
                        $gearStmt->execute([$gear]);
                        $gearDetails = $gearStmt->fetch();
                        if ($gearDetails) {
                            $validatedLoadout[$slot] = $gearDetails;
                        } else {
                            $validatedLoadout[$slot] = null;
                        }
                    } else {
                        // Assume it's already a gear object
                        $validatedLoadout[$slot] = $gear;
                    }
                } else {
                    $validatedLoadout[$slot] = null;
                }
            }
            
            // Calculate feasibility
            $result = $this->engine->calculate($combatant, $validatedLoadout);
            $result['combatantId'] = $combatantId;
            $result['loadout'] = $validatedLoadout;
            
            return $this->jsonResponse($response, 200, true, 'Loadout validated', $result);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/loadouts/equip
     * Equips a loadout for a combatant (saves to database)
     */
    public function equipLoadout($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId']) || !isset($body['loadout'])) {
                return $this->jsonResponse($response, 400, false, 'Invalid request: combatantId and loadout required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $loadout = $body['loadout'];
            
            // Verify combatant exists
            $stmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            if (!$stmt->fetch()) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Verify each gear item exists and belongs to combatant
            $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
            $gearIds = [];
            
            foreach ($slots as $slot) {
                if (isset($loadout[$slot]) && $loadout[$slot] !== null) {
                    $gearId = is_array($loadout[$slot]) ? $loadout[$slot]['id'] : $loadout[$slot];
                    $gearIds[$slot] = $gearId;
                    
                    // Verify gear exists in combatant's inventory
                    $invStmt = $this->db->prepare("
                        SELECT id FROM inventory 
                        WHERE combatant_id = ? AND gear_id = ?
                    ");
                    $invStmt->execute([$combatantId, $gearId]);
                    if (!$invStmt->fetch()) {
                        return $this->jsonResponse($response, 400, false, "Gear item $gearId not in combatant's inventory");
                    }
                } else {
                    $gearIds[$slot] = null;
                }
            }
            
            // Update or insert loadout
            $stmt = $this->db->prepare("
                INSERT INTO loadouts (combatant_id, helmet_id, core_id, dampener_id, gauntlets_id, battery_id)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    helmet_id = VALUES(helmet_id),
                    core_id = VALUES(core_id),
                    dampener_id = VALUES(dampener_id),
                    gauntlets_id = VALUES(gauntlets_id),
                    battery_id = VALUES(battery_id)
            ");
            
            $stmt->execute([
                $combatantId,
                $gearIds['helmet'] ?? null,
                $gearIds['core'] ?? null,
                $gearIds['dampener'] ?? null,
                $gearIds['gauntlets'] ?? null,
                $gearIds['battery'] ?? null
            ]);
            
            // Update inventory equipped status
            foreach ($gearIds as $slot => $gearId) {
                if ($gearId) {
                    $stmt = $this->db->prepare("
                        UPDATE inventory SET equipped = TRUE 
                        WHERE combatant_id = ? AND gear_id = ?
                    ");
                    $stmt->execute([$combatantId, $gearId]);
                }
            }
            
            // Set other inventory items as not equipped
            $allGearIds = array_filter($gearIds);
            if (!empty($allGearIds)) {
                $placeholders = implode(',', array_fill(0, count($allGearIds), '?'));
                $stmt = $this->db->prepare("
                    UPDATE inventory 
                    SET equipped = FALSE 
                    WHERE combatant_id = ? AND gear_id NOT IN ($placeholders)
                ");
                $params = array_merge([$combatantId], $allGearIds);
                $stmt->execute($params);
            }
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Loadout equipped successfully');
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/loadouts/{id}
     * Get loadout by ID
     */
    public function getLoadout($request, $response, $args)
    {
        $id = $args['id'] ?? null;
        
        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Loadout ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    l.*,
                    c.name as combatant_name
                FROM loadouts l
                JOIN combatants c ON l.combatant_id = c.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            $loadout = $stmt->fetch();
            
            if (!$loadout) {
                return $this->jsonResponse($response, 404, false, 'Loadout not found');
            }
            
            return $this->jsonResponse($response, 200, true, 'Loadout retrieved', ['loadout' => $loadout]);
            
        } catch (\Exception $e) {
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