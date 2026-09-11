<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use Aegis\Services\WeatherService;
use PDO;

class WeatherController
{
    private PDO $db;
    private WeatherService $weather;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->weather = new WeatherService();
    }
    
    /**
     * GET /api/weather/current
     */
    public function getCurrent($request, $response, $args)
    {
        try {
            // ✅ FIXED: Use getQueryParams() for Slim 4
            $queryParams = $request->getQueryParams();
            $city = $queryParams['city'] ?? 'Manila';
            $units = $queryParams['units'] ?? 'metric';
            
            $weather = $this->weather->getCurrentWeather($city, $units);
            
            return $this->jsonResponse($response, 200, true, 'Weather retrieved', $weather);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Weather error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/weather/forecast
     */
    public function getForecast($request, $response, $args)
    {
        try {
            // ✅ FIXED: Use getQueryParams() for Slim 4
            $queryParams = $request->getQueryParams();
            $city = $queryParams['city'] ?? 'Manila';
            $days = (int)($queryParams['days'] ?? 5);
            
            $forecast = $this->weather->getForecast($city, $days);
            
            return $this->jsonResponse($response, 200, true, 'Forecast retrieved', $forecast);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Weather error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/weather/combat-impact
     */
    public function getCombatImpact($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            if (!$body || !isset($body['combatantId'])) {
                return $this->jsonResponse($response, 400, false, 'combatantId required');
            }
            
            $combatantId = (int)$body['combatantId'];
            $city = $body['city'] ?? 'Manila';
            
            $stmt = $this->db->prepare("
                SELECT id, name, bio_capacity_max, base_recovery, base_risk, role
                FROM combatants WHERE id = ?
            ");
            $stmt->execute([$combatantId]);
            $combatant = $stmt->fetch();
            
            if (!$combatant) {
                return $this->jsonResponse($response, 404, false, 'Combatant not found');
            }
            
            $weather = $this->weather->getCurrentWeather($city);
            $impact = $weather['energy_impact'];
            
            $adjustedBioCapacity = round($combatant['bio_capacity_max'] * $impact['drain_multiplier']);
            $adjustedRecovery = round($combatant['base_recovery'] * $impact['recovery_multiplier'], 1);
            $adjustedRisk = min(100, round($combatant['base_risk'] + (($impact['drain_multiplier'] - 1) * 50)));
            
            return $this->jsonResponse($response, 200, true, 'Combat impact calculated', [
                'combatant' => [
                    'id' => (int)$combatant['id'],
                    'name' => $combatant['name'],
                    'role' => $combatant['role']
                ],
                'weather' => [
                    'city' => $weather['city'],
                    'temperature' => $weather['temperature'],
                    'condition' => $weather['weather'],
                    'humidity' => $weather['humidity']
                ],
                'base_stats' => [
                    'bio_capacity' => (int)$combatant['bio_capacity_max'],
                    'recovery' => (int)$combatant['base_recovery'],
                    'risk' => (int)$combatant['base_risk']
                ],
                'adjusted_stats' => [
                    'bio_capacity' => $adjustedBioCapacity,
                    'recovery' => $adjustedRecovery,
                    'risk' => $adjustedRisk
                ],
                'impact' => [
                    'drain_multiplier' => $impact['drain_multiplier'],
                    'recovery_multiplier' => $impact['recovery_multiplier'],
                    'effect_summary' => $impact['effect_summary'],
                    'warnings' => $impact['warnings']
                ]
            ]);
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