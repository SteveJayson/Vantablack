<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\GeoService;
use PDO;

class GeoController
{
    private PDO $db;
    private GeoService $geo;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->geo = new GeoService();
    }
    
    /**
     * GET /api/geo/lookup
     */
    public function lookupIp($request, $response, $args)
    {
        try {
            $ip = $request->getQueryParam('ip', '');
            $location = $this->geo->lookupIp($ip);
            
            return $this->jsonResponse($response, 200, true, 'Location retrieved', $location);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/geo/combatant-location
     */
    public function getCombatantLocation($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId'])) {
                return $this->jsonResponse($response, 400, false, 'combatantId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $ip = $body['ip'] ?? '';
            
            $stmt = $this->db->prepare("SELECT id, name, role, faction FROM combatants WHERE id = ?");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $location = $this->geo->lookupIp($ip);
            
            return $this->jsonResponse($response, 200, true, 'Combatant location retrieved', [
                'combatant' => $combatant,
                'location' => $location
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    private function jsonResponse($response, int $status, bool $success, string $message, array $data = [])
    {
        $payload = ['status' => $status, 'success' => $success, 'message' => $message, 'data' => $data];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}