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
     * Helper: Get query parameter (Slim 4 compatible)
     */
    private function getQuery($request, string $key, $default = null)
    {
        $params = $request->getQueryParams();
        return $params[$key] ?? $default;
    }
    /**
     * POST /api/loadouts/equip-item
     * Equip a single gear item from inventory
     * Body: { "combatantId": 1, "inventoryId": 5 }
     */
    public function equipItem($request, $response, $args)
    {
        $this->db->beginTransaction();

        try {
            $body = json_decode($request->getBody()->getContents(), true);

            $combatantId = (int)($body['combatantId'] ?? 0);
            $inventoryId = (int)($body['inventoryId'] ?? 0);

            if (!$combatantId || !$inventoryId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and inventoryId required');
            }

            // Get the inventory item + gear details
            $stmt = $this->db->prepare("
                SELECT 
                    i.id as inventoryId,
                    i.gear_id,
                    i.equipped,
                    g.slot,
                    g.name,
                    g.bio_capacity,
                    g.recovery_rate,
                    g.risk_modifier
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                WHERE i.id = ? AND i.combatant_id = ?
            ");
            $stmt->execute([$inventoryId, $combatantId]);
            $item = $stmt->fetch();

            if (!$item) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Item not in your inventory');
            }

            $slot = $item['slot'];           // helmet, core, dampener, gauntlets, battery
            $column = $slot . '_id';         // helmet_id, core_id, etc.

            // Ensure loadout row exists
            $stmt = $this->db->prepare("
                INSERT INTO loadouts (combatant_id)
                VALUES (?)
                ON DUPLICATE KEY UPDATE combatant_id = combatant_id
            ");
            $stmt->execute([$combatantId]);

            // Get previous item in this slot (to unequip it)
            $stmt = $this->db->prepare("
                SELECT {$column} as prev_gear_id FROM loadouts WHERE combatant_id = ?
            ");
            $stmt->execute([$combatantId]);
            $prev = $stmt->fetch();
            $prevGearId = $prev['prev_gear_id'] ?? null;

            // Unequip the previous item in this slot
            if ($prevGearId) {
                $stmt = $this->db->prepare("
                    UPDATE inventory SET equipped = FALSE
                    WHERE combatant_id = ? AND gear_id = ?
                ");
                $stmt->execute([$combatantId, $prevGearId]);
            }

            // Update loadout with new gear
            $stmt = $this->db->prepare("
                UPDATE loadouts SET {$column} = ? WHERE combatant_id = ?
            ");
            $stmt->execute([$item['gear_id'], $combatantId]);

            // Mark this item as equipped
            $stmt = $this->db->prepare("
                UPDATE inventory SET equipped = TRUE WHERE id = ?
            ");
            $stmt->execute([$inventoryId]);

            $this->db->commit();

            return $this->jsonResponse($response, 200, true, "Equipped {$item['name']}!", [
                'slot' => $slot,
                'gearId' => $item['gear_id'],
                'gearName' => $item['name'],
                'bioCapacity' => (int)$item['bio_capacity'],
                'recoveryRate' => (int)$item['recovery_rate'],
                'riskModifier' => (int)$item['risk_modifier']
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/loadouts/unequip-item
     * Unequip a gear item
     * Body: { "combatantId": 1, "slot": "helmet" }
     */
    public function unequipItem($request, $response, $args)
    {
        $this->db->beginTransaction();

        try {
            $body = json_decode($request->getBody()->getContents(), true);
            $combatantId = (int)($body['combatantId'] ?? 0);
            $slot = $body['slot'] ?? '';

            if (!$combatantId || !in_array($slot, ['helmet', 'core', 'dampener', 'gauntlets', 'battery'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and valid slot required');
            }

            $column = $slot . '_id';

            // Get current gear ID
            $stmt = $this->db->prepare("SELECT {$column} as gear_id FROM loadouts WHERE combatant_id = ?");
            $stmt->execute([$combatantId]);
            $row = $stmt->fetch();
            $gearId = $row['gear_id'] ?? null;

            if (!$gearId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Nothing equipped in this slot');
            }

            // Unequip in inventory
            $stmt = $this->db->prepare("
                UPDATE inventory SET equipped = FALSE
                WHERE combatant_id = ? AND gear_id = ?
            ");
            $stmt->execute([$combatantId, $gearId]);

            // Clear loadout slot
            $stmt = $this->db->prepare("UPDATE loadouts SET {$column} = NULL WHERE combatant_id = ?");
            $stmt->execute([$combatantId]);

            $this->db->commit();

            return $this->jsonResponse($response, 200, true, "Unequipped {$slot}", [
                'slot' => $slot
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    /**
     * GET /api/combatants/{id}/loadout
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
                    l.helmet_id, l.core_id, l.dampener_id, l.gauntlets_id, l.battery_id,
                    g1.name as helmet_name, g2.name as core_name,
                    g3.name as dampener_name, g4.name as gauntlets_name, g5.name as battery_name
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
                return $this->jsonResponse($response, 404, false, 'No loadout found');
            }

            return $this->jsonResponse($response, 200, true, 'Loadout retrieved', ['loadout' => $loadout]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }

    /**
     * POST /api/loadouts/validate
     */
    public function validateLoadout($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['combatantId']) || !isset($body['loadout'])) {
                return $this->jsonResponse($response, 400, false, 'combatantId and loadout required');
            }

            $combatantId = (int)$body['combatantId'];
            $loadout = $body['loadout'];

            $stmt = $this->db->prepare("
                SELECT id, name, bio_capacity_max as bioCapacityMax,
                    base_recovery as baseRecovery, base_risk as baseRisk,
                    credits, faction, clearance_level as clearanceLevel
                FROM combatants WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();

            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }

            $validatedLoadout = [];
            $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];

            foreach ($slots as $slot) {
                if (isset($loadout[$slot]) && $loadout[$slot] !== null) {
                    $gear = $loadout[$slot];

                    if (is_string($gear) || is_numeric($gear)) {
                        $gearStmt = $this->db->prepare("
                            SELECT id, slot, source, name, price,
                                bio_capacity as bioCapacity, recovery_rate as recoveryRate,
                                risk_modifier as riskModifier, clearance_required as clearanceRequired
                            FROM gear_items WHERE id = ?
                        ");
                        $gearStmt->execute([$gear]);
                        $validatedLoadout[$slot] = $gearStmt->fetch() ?: null;
                    } else {
                        $validatedLoadout[$slot] = $gear;
                    }
                } else {
                    $validatedLoadout[$slot] = null;
                }
            }

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
     */
    public function equipLoadout($request, $response, $args)
    {
        $this->db->beginTransaction();

        try {
            $body = json_decode($request->getBody()->getContents(), true);

            if (!$body || !isset($body['combatantId']) || !isset($body['loadout'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and loadout required');
            }

            $combatantId = (int)$body['combatantId'];
            $loadout = $body['loadout'];

            $stmt = $this->db->prepare("SELECT id FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            if (!$stmt->fetch()) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }

            $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
            $gearIds = [];

            foreach ($slots as $slot) {
                if (isset($loadout[$slot]) && $loadout[$slot] !== null) {
                    $gearId = is_array($loadout[$slot]) ? $loadout[$slot]['id'] : $loadout[$slot];
                    $gearIds[$slot] = $gearId;
                } else {
                    $gearIds[$slot] = null;
                }
            }

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
                $gearIds['helmet'],
                $gearIds['core'],
                $gearIds['dampener'],
                $gearIds['gauntlets'],
                $gearIds['battery']
            ]);

            $this->db->commit();

            return $this->jsonResponse($response, 200, true, 'Loadout equipped successfully');
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }

    /**
     * GET /api/loadouts/{id}
     */
    public function getLoadout($request, $response, $args)
    {
        $id = $args['id'] ?? null;

        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Loadout ID required');
        }

        try {
            $stmt = $this->db->prepare("
                SELECT l.*, c.name as combatant_name
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
