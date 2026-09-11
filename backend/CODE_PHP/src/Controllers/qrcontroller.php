<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\QrService;
use PDO;

class QrController
{
    private PDO $db;
    private QrService $qr;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->qr = new QrService();
    }
    
    /**
     * GET /api/qr/gear/{id}
     */
    public function generateGearQr($request, $response, $args)
    {
        try {
            $gearId = $args['id'] ?? null;
            
            if (!$gearId) {
                return $this->jsonResponse($response, 400, false, 'Gear ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, slot, price, source, bio_capacity as bioCapacity,
                    recovery_rate as recoveryRate, risk_modifier as riskModifier
                FROM gear_items WHERE id = ?
            ");
            $stmt->execute([$gearId]);
            $gear = $stmt->fetch();
            
            if (!$gear) {
                return $this->jsonResponse($response, 404, false, 'Gear not found');
            }
            
            $qr = $this->qr->generateGearQr($gear);
            
            return $this->jsonResponse($response, 200, true, 'QR code generated', $qr);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/qr/combatant/{id}
     */
    public function generateCombatantQr($request, $response, $args)
    {
        try {
            $combatantId = $args['id'] ?? null;
            
            if (!$combatantId) {
                return $this->jsonResponse($response, 400, false, 'Combatant ID required');
            }
            
            $stmt = $this->db->prepare("
                SELECT id, name, role, faction, credits, bio_capacity_max
                FROM combatants WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $qr = $this->qr->generateCombatantQr($combatant);
            
            return $this->jsonResponse($response, 200, true, 'QR code generated', $qr);
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