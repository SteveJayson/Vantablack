<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class CraftingController
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
     * GET /api/crafting/recipes
     */
    public function getRecipes($request, $response, $args)
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    cr.id,
                    cr.result_gear_id as resultGearId,
                    cr.required_materials as requiredMaterials,
                    cr.required_credits as requiredCredits,
                    cr.required_level as requiredLevel,
                    g.name as resultName,
                    g.slot,
                    g.source,
                    g.price,
                    g.bio_capacity as bioCapacity,
                    g.recovery_rate as recoveryRate,
                    g.risk_modifier as riskModifier,
                    g.tier,
                    g.is_legendary as isLegendary
                FROM crafting_recipes cr
                JOIN gear_items g ON cr.result_gear_id = g.id
                ORDER BY g.tier ASC, cr.required_credits ASC
            ");
            $recipes = $stmt->fetchAll();
            
            foreach ($recipes as &$r) {
                $r['id'] = (int)$r['id'];
                $r['requiredMaterials'] = json_decode($r['requiredMaterials'], true);
                $r['requiredCredits'] = (int)$r['requiredCredits'];
                $r['requiredLevel'] = (int)$r['requiredLevel'];
                $r['price'] = (int)$r['price'];
                $r['bioCapacity'] = (int)$r['bioCapacity'];
                $r['recoveryRate'] = (int)$r['recoveryRate'];
                $r['riskModifier'] = (int)$r['riskModifier'];
                $r['tier'] = (int)$r['tier'];
                $r['isLegendary'] = (bool)$r['isLegendary'];
                
                // Get material names
                $materialNames = [];
                foreach ($r['requiredMaterials'] as $gearId => $qty) {
                    $stmt2 = $this->db->prepare("SELECT name FROM gear_items WHERE id = ?");
                    $stmt2->execute([$gearId]);
                    $gear = $stmt2->fetch();
                    $materialNames[$gearId] = [
                        'id' => $gearId,
                        'name' => $gear ? $gear['name'] : $gearId,
                        'quantity' => $qty
                    ];
                }
                $r['materialDetails'] = array_values($materialNames);
            }
            
            return $this->jsonResponse($response, 200, true, 'Recipes retrieved', [
                'recipes' => $recipes,
                'total' => count($recipes)
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/crafting/craft
     * Body: { "combatantId": 1, "recipeId": 1 }
     */
    public function craft($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $combatantId = (int)($body['combatantId'] ?? 0);
            $recipeId = (int)($body['recipeId'] ?? 0);
            
            if (!$combatantId || !$recipeId) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'combatantId and recipeId required');
            }
            
            // Get recipe
            $stmt = $this->db->prepare("
                SELECT * FROM crafting_recipes WHERE id = ?
            ");
            $stmt->execute([$recipeId]);
            $recipe = $stmt->fetch();
            
            if (!$recipe) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Recipe not found');
            }
            
            $materials = json_decode($recipe['required_materials'], true);
            $requiredCredits = (int)$recipe['required_credits'];
            
            // Get combatant
            $stmt = $this->db->prepare("SELECT id, name, credits, clearance_level FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            // Check credits
            if ($combatant['credits'] < $requiredCredits) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 402, false, 'Insufficient credits', [
                    'required' => $requiredCredits,
                    'available' => (int)$combatant['credits']
                ]);
            }
            
            // Check clearance
            if ($combatant['clearance_level'] < $recipe['required_level']) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 403, false, 'Clearance level too low', [
                    'required' => (int)$recipe['required_level'],
                    'current' => (int)$combatant['clearance_level']
                ]);
            }
            
            // Check materials
            foreach ($materials as $gearId => $qty) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as cnt FROM inventory 
                    WHERE combatant_id = ? AND gear_id = ?
                ");
                $stmt->execute([$combatantId, $gearId]);
                $owned = (int)$stmt->fetch()['cnt'];
                
                if ($owned < $qty) {
                    $this->db->rollBack();
                    return $this->jsonResponse($response, 400, false, "Not enough $gearId (need $qty, have $owned)");
                }
            }
            
            // Check if already owns result
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as cnt FROM inventory 
                WHERE combatant_id = ? AND gear_id = ?
            ");
            $stmt->execute([$combatantId, $recipe['result_gear_id']]);
            if ((int)$stmt->fetch()['cnt'] > 0) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Already own this gear');
            }
            
            // Remove materials
            foreach ($materials as $gearId => $qty) {
                for ($i = 0; $i < $qty; $i++) {
                    $stmt = $this->db->prepare("
                        DELETE FROM inventory 
                        WHERE combatant_id = ? AND gear_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$combatantId, $gearId]);
                }
            }
            
            // Deduct credits
            $newBalance = (int)$combatant['credits'] - $requiredCredits;
            $stmt = $this->db->prepare("UPDATE combatants SET credits = ? WHERE id = ?");
            $stmt->execute([$newBalance, $combatantId]);
            
            // Add result gear to inventory
            $stmt = $this->db->prepare("
                INSERT INTO inventory (combatant_id, gear_id, equipped) 
                VALUES (?, ?, FALSE)
            ");
            $stmt->execute([$combatantId, $recipe['result_gear_id']]);
            
            // Log craft
            $stmt = $this->db->prepare("
                INSERT INTO crafting_log (combatant_id, recipe_id, result_gear_id, materials_used, credits_spent, crafted_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $combatantId,
                $recipeId,
                $recipe['result_gear_id'],
                json_encode($materials),
                $requiredCredits
            ]);
            
            // Log activity
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO activity_log (combatant_id, activity_type, details, credits_change, logged_at)
                    VALUES (?, 'craft', ?, ?, NOW())
                ");
                $stmt->execute([
                    $combatantId,
                    json_encode(['result' => $recipe['result_gear_id']]),
                    -$requiredCredits
                ]);
            } catch (\Exception $e) {}
            
            // Get result gear details
            $stmt = $this->db->prepare("SELECT * FROM gear_items WHERE id = ?");
            $stmt->execute([$recipe['result_gear_id']]);
            $resultGear = $stmt->fetch();
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Crafted successfully!', [
                'crafted' => [
                    'id' => $resultGear['id'],
                    'name' => $resultGear['name'],
                    'slot' => $resultGear['slot'],
                    'bioCapacity' => (int)$resultGear['bio_capacity'],
                    'recoveryRate' => (int)$resultGear['recovery_rate'],
                    'riskModifier' => (int)$resultGear['risk_modifier']
                ],
                'creditsSpent' => $requiredCredits,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/crafting/history/{id}
     */
    public function getHistory($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT 
                    cl.id,
                    cl.result_gear_id as resultGearId,
                    cl.materials_used as materialsUsed,
                    cl.credits_spent as creditsSpent,
                    cl.crafted_at as craftedAt,
                    g.name as resultName
                FROM crafting_log cl
                JOIN gear_items g ON cl.result_gear_id = g.id
                WHERE cl.combatant_id = ?
                ORDER BY cl.crafted_at DESC
                LIMIT 20
            ");
            $stmt->execute([$combatantId]);
            $history = $stmt->fetchAll();
            
            foreach ($history as &$h) {
                $h['id'] = (int)$h['id'];
                $h['creditsSpent'] = (int)$h['creditsSpent'];
                $h['materialsUsed'] = json_decode($h['materialsUsed'], true);
            }
            
            return $this->jsonResponse($response, 200, true, 'Crafting history retrieved', [
                'history' => $history,
                'total' => count($history)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/crafting/disassemble
     * Body: { "combatantId": 1, "inventoryId": 5 }
     * Breaks down gear into credits (50% of value)
     */
    public function disassemble($request, $response, $args)
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
            
            // Get inventory item
            $stmt = $this->db->prepare("
                SELECT i.id, i.gear_id, g.name, g.price, g.slot
                FROM inventory i
                JOIN gear_items g ON i.gear_id = g.id
                WHERE i.id = ? AND i.combatant_id = ?
            ");
            $stmt->execute([$inventoryId, $combatantId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 404, false, 'Item not found in inventory');
            }
            
            // Calculate return (40% of price)
            $refund = (int)($item['price'] * 0.4);
            
            // Delete from inventory
            $stmt = $this->db->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$inventoryId]);
            
            // Add credits
            $stmt = $this->db->prepare("UPDATE combatants SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$refund, $combatantId]);
            
            // Get new balance
            $stmt = $this->db->prepare("SELECT credits FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $newBalance = (int)$stmt->fetch()['credits'];
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Disassembled successfully', [
                'gearName' => $item['name'],
                'refund' => $refund,
                'newBalance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
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