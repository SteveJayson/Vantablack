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
     * Helper: Get query parameter (Slim 4 compatible)
     */
    private function getQuery($request, string $key, $default = null)
    {
        $params = $request->getQueryParams();
        return $params[$key] ?? $default;
    }
    
    /**
     * GET /api/gear
     */
    public function getCatalog($request, $response, $args)
    {
        try {
            $slot = $this->getQuery($request, 'slot');
            $source = $this->getQuery($request, 'source');
            $minPrice = $this->getQuery($request, 'minPrice');
            $maxPrice = $this->getQuery($request, 'maxPrice');
            
            $query = "SELECT 
                id, slot, source, name, price,
                bio_capacity as bioCapacity,
                recovery_rate as recoveryRate,
                risk_modifier as riskModifier,
                clearance_required as clearanceRequired
            FROM gear_items";
            
            $params = [];
            $conditions = [];
            
            if ($slot) {
                $conditions[] = "slot = ?";
                $params[] = $slot;
            }
            
            if ($source) {
                $conditions[] = "source = ?";
                $params[] = $source;
            }
            
            if ($minPrice) {
                $conditions[] = "price >= ?";
                $params[] = (int)$minPrice;
            }
            
            if ($maxPrice) {
                $conditions[] = "price <= ?";
                $params[] = (int)$maxPrice;
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
                    id, slot, source, name, price,
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