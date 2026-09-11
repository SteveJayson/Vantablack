<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\FeasibilityEngine;
use PDO;

class CombatantController
{
    private PDO $db;
    private FeasibilityEngine $engine;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->engine = new FeasibilityEngine();
    }
    
    /**
     * GET /api/combatants
     */
    public function getAllCombatants($request, $response, $args)
    {
        try {
            $role = $request->getQueryParam('role');
            
            $query = "SELECT 
                id, name, bio_capacity_max as bioCapacityMax,
                base_recovery as baseRecovery, base_risk as baseRisk,
                credits, faction, role, clearance_level as clearanceLevel
            FROM combatants";
            
            $params = [];
            if ($role) {
                $query .= " WHERE role = ?";
                $params[] = $role;
            }
            
            $query .= " ORDER BY role, name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $combatants = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Combatants retrieved', ['combatants' => $combatants]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/combatants/{id}
     */
    public function getCombatant($request, $response, $args)
    {
        $id = $args['id'] ?? null;
        
        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Combatant ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id, c.name,
                    c.bio_capacity_max as bioCapacityMax,
                    c.base_recovery as baseRecovery,
                    c.base_risk as baseRisk,
                    c.credits, c.faction, c.role,
                    c.clearance_level as clearanceLevel
                FROM combatants c
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Only load loadout for heroes/villains
            if (in_array($combatant['role'], ['hero', 'villain'])) {
                $loadout = $this->getLoadoutDetails($id);
                $combatant['loadout'] = $loadout;
                
                if (!empty($loadout)) {
                    $stats = $this->engine->calculate($combatant, $loadout);
                    $combatant['loadoutStats'] = $stats;
                }
            }
            
            return $this->jsonResponse($response, 200, true, 'Combatant found', ['combatant' => $combatant]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function getLoadoutDetails(int $combatantId): array
    {
        $stmt = $this->db->prepare("
            SELECT helmet_id, core_id, dampener_id, gauntlets_id, battery_id
            FROM loadouts WHERE combatant_id = ?
        ");
        $stmt->execute([$combatantId]);
        $loadout = $stmt->fetch();
        
        if (!$loadout) return [];
        
        $result = [];
        $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
        
        foreach ($slots as $slot) {
            $gearId = $loadout[$slot . '_id'];
            if ($gearId) {
                $gearStmt = $this->db->prepare("
                    SELECT id, slot, source, name, price,
                        bio_capacity as bioCapacity,
                        recovery_rate as recoveryRate,
                        risk_modifier as riskModifier,
                        clearance_required as clearanceRequired
                    FROM gear_items WHERE id = ?
                ");
                $gearStmt->execute([$gearId]);
                $result[$slot] = $gearStmt->fetch();
            } else {
                $result[$slot] = null;
            }
        }
        
        return $result;
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