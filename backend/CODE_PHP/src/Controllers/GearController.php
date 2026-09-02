<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class GearController
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    
    /**
     * GET /api/gear
     * Get gear catalog with optional filters
     */
    public function getCatalog($request, $response, $args)
    {
        try {
            $query = "SELECT 
                id,
                slot,
                source,
                name,
                price,
                bio_capacity as bioCapacity,
                recovery_rate as recoveryRate,
                risk_modifier as riskModifier,
                clearance_required as clearanceRequired
            FROM gear_items";
            
            $params = [];
            $conditions = [];
            
            // Apply filters
            if ($request->getQueryParam('slot')) {
                $conditions[] = "slot = ?";
                $params[] = $request->getQueryParam('slot');
            }
            
            if ($request->getQueryParam('source')) {
                $conditions[] = "source = ?";
                $params[] = $request->getQueryParam('source');
            }
            
            if ($request->getQueryParam('minPrice')) {
                $conditions[] = "price >= ?";
                $params[] = (int)$request->getQueryParam('minPrice');
            }
            
            if ($request->getQueryParam('maxPrice')) {
                $conditions[] = "price <= ?";
                $params[] = (int)$request->getQueryParam('maxPrice');
            }
            
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $query .= " ORDER BY price ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $gear = $stmt->fetchAll();
            
            return $this->jsonResponse($response, 200, true, 'Catalog retrieved', ['catalog' => $gear]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/gear/{id}
     * Get single gear item
     */
    public function getGearItem($request, $response, $args)
    {
        $id = $args['id'] ?? null;
        
        if (!$id) {
            return $this->jsonResponse($response, 400, false, 'Gear ID required');
        }
        
        try {
            $stmt = $this->db->prepare("
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
            $stmt->execute([$id]);
            $gear = $stmt->fetch();
            
            if (!$gear) {
                return $this->jsonResponse($response, 404, false, 'Gear item not found');
            }
            
            return $this->jsonResponse($response, 200, true, 'Gear item found', ['gear' => $gear]);
            
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