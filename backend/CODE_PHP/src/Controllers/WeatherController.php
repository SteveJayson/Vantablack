<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class WeatherController
{
    private PDO $db;
    private string $apiKey;
    private string $baseUrl;
    
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->apiKey = \defined('OPENWEATHER_API_KEY') ? \OPENWEATHER_API_KEY : '';
        $this->baseUrl = 'https://api.openweathermap.org/data/2.5';
    }
    
    private function getQuery($request, string $key, $default = null)
    {
        $params = $request->getQueryParams();
        return $params[$key] ?? $default;
    }
    
    /**
     * GET /api/weather/current?city=Manila
     * Get current weather for a city
     */
    public function getCurrent($request, $response, $args)
    {
        try {
            $city = $this->getQuery($request, 'city', 'Manila');
            
            // If no API key, return demo data
            if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
                $demoWeather = [
                    'demo_mode' => true,
                    'city' => $city,
                    'temperature' => 28,
                    'feels_like' => 32,
                    'condition' => 'Clear',
                    'description' => 'clear sky',
                    'humidity' => 75,
                    'wind_speed' => 5.2,
                    'icon' => '01d'
                ];
                $impact = $this->calculateCombatImpact($demoWeather);
                return $this->jsonResponse($response, 200, true, 'Weather (DEMO mode - add API key for real data)', array_merge($demoWeather, $impact));
            }
            
            $url = "{$this->baseUrl}/weather?q=" . urlencode($city) . "&appid={$this->apiKey}&units=metric";
            $data = $this->callApi($url);
            
            if (!$data || isset($data['cod']) && $data['cod'] != 200) {
                return $this->jsonResponse($response, 404, false, 'City not found or API error');
            }
            
            $condition = $data['weather'][0]['main'] ?? 'Clear';
            $temp = round($data['main']['temp'], 1);
            $liveWeather = [
                'demo_mode' => false,
                'city' => $data['name'],
                'country' => $data['sys']['country'] ?? '',
                'temperature' => $temp,
                'feels_like' => round($data['main']['feels_like'], 1),
                'temp_min' => round($data['main']['temp_min'], 1),
                'temp_max' => round($data['main']['temp_max'], 1),
                'condition' => $condition,
                'description' => $data['weather'][0]['description'],
                'humidity' => $data['main']['humidity'],
                'pressure' => $data['main']['pressure'],
                'wind_speed' => $data['wind']['speed'],
                'wind_degree' => $data['wind']['deg'] ?? 0,
                'clouds' => $data['clouds']['all'] ?? 0,
                'icon' => $data['weather'][0]['icon'],
                'sunrise' => date('H:i', $data['sys']['sunrise']),
                'sunset' => date('H:i', $data['sys']['sunset'])
            ];
            $impact = $this->calculateCombatImpact(['condition' => $condition, 'temperature' => $temp]);
            
            return $this->jsonResponse($response, 200, true, 'Weather retrieved', array_merge($liveWeather, $impact));
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/weather/forecast?city=Manila
     * Get 5-day forecast
     */
    public function getForecast($request, $response, $args)
    {
        try {
            $city = $this->getQuery($request, 'city', 'Manila');
            
            if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
                return $this->jsonResponse($response, 200, true, 'Forecast (DEMO mode)', [
                    'demo_mode' => true,
                    'city' => $city,
                    'forecast' => [
                        ['date' => date('Y-m-d', strtotime('+1 day')), 'temp' => 29, 'condition' => 'Clear', 'icon' => '01d'],
                        ['date' => date('Y-m-d', strtotime('+2 day')), 'temp' => 27, 'condition' => 'Clouds', 'icon' => '03d'],
                        ['date' => date('Y-m-d', strtotime('+3 day')), 'temp' => 26, 'condition' => 'Rain', 'icon' => '10d'],
                        ['date' => date('Y-m-d', strtotime('+4 day')), 'temp' => 28, 'condition' => 'Clear', 'icon' => '01d'],
                        ['date' => date('Y-m-d', strtotime('+5 day')), 'temp' => 30, 'condition' => 'Clouds', 'icon' => '02d'],
                    ]
                ]);
            }
            
            $url = "{$this->baseUrl}/forecast?q=" . urlencode($city) . "&appid={$this->apiKey}&units=metric";
            $data = $this->callApi($url);
            
            if (!$data || !isset($data['list'])) {
                return $this->jsonResponse($response, 404, false, 'Forecast not available');
            }
            
            // Group by day
            $daily = [];
            foreach ($data['list'] as $item) {
                $date = date('Y-m-d', $item['dt']);
                
                if (!isset($daily[$date])) {
                    $daily[$date] = [
                        'date' => $date,
                        'temps' => [],
                        'conditions' => [],
                        'icon' => $item['weather'][0]['icon']
                    ];
                }
                
                $daily[$date]['temps'][] = $item['main']['temp'];
                $daily[$date]['conditions'][] = $item['weather'][0]['main'];
            }
            
            $forecast = [];
            foreach (array_slice($daily, 0, 5) as $day) {
                $forecast[] = [
                    'date' => $day['date'],
                    'temp' => round(array_sum($day['temps']) / count($day['temps']), 1),
                    'temp_high' => round(max($day['temps']), 1),
                    'temp_low' => round(min($day['temps']), 1),
                    'condition' => $this->getMostCommon($day['conditions']),
                    'icon' => $day['icon']
                ];
            }
            
            return $this->jsonResponse($response, 200, true, 'Forecast retrieved', [
                'demo_mode' => false,
                'city' => $data['city']['name'],
                'forecast' => $forecast
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/weather/combat-impact
     * Calculate how weather affects a combatant's loadout
     */
    public function getCombatImpact($request, $response, $args)
    {
        try {
            $body = json_decode($request->getBody()->getContents(), true);
            
            $city = $body['city'] ?? 'Manila';
            $combatantId = $body['combatantId'] ?? null;
            $loadoutImpact = $combatantId ? $this->getLoadoutImpact((int)$combatantId) : ['bio_capacity' => 0, 'recovery_rate' => 0, 'risk_modifier' => 0];
            
            // Get weather
            $weather = null;
            if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
                // Demo weather
                $weather = [
                    'temperature' => 28,
                    'condition' => 'Clear',
                    'humidity' => 75,
                    'wind_speed' => 5.2
                ];
            } else {
                $url = "{$this->baseUrl}/weather?q=" . urlencode($city) . "&appid={$this->apiKey}&units=metric";
                $data = $this->callApi($url);
                
                if ($data && !isset($data['cod']) || $data['cod'] == 200) {
                    $weather = [
                        'temperature' => $data['main']['temp'],
                        'condition' => $data['weather'][0]['main'],
                        'humidity' => $data['main']['humidity'],
                        'wind_speed' => $data['wind']['speed']
                    ];
                }
            }
            
            if (!$weather) {
                return $this->jsonResponse($response, 500, false, 'Could not fetch weather');
            }
            
            // Calculate combat impact with equipped gear influence
            $impact = $this->calculateCombatImpact($weather, $loadoutImpact);
            
            return $this->jsonResponse($response, 200, true, 'Combat impact calculated', [
                'city' => $city,
                'weather' => $weather,
                'equipment' => $loadoutImpact,
                'impact' => $impact
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/weather/combat-forecast?city=Manila
     * 5-day forecast with combat impact
     */
    public function getCombatForecast($request, $response, $args)
    {
        try {
            $city = $this->getQuery($request, 'city', 'Manila');
            $combatantId = $this->getQuery($request, 'combatantId', null);
            $loadoutImpact = $combatantId ? $this->getLoadoutImpact((int)$combatantId) : ['bio_capacity' => 0, 'recovery_rate' => 0, 'risk_modifier' => 0];
            
            // Get forecast
            if (empty($this->apiKey) || $this->apiKey === 'YOUR_API_KEY_HERE') {
                $forecast = [
                    ['date' => date('Y-m-d', strtotime('+1 day')), 'temp' => 29, 'condition' => 'Clear'],
                    ['date' => date('Y-m-d', strtotime('+2 day')), 'temp' => 27, 'condition' => 'Clouds'],
                    ['date' => date('Y-m-d', strtotime('+3 day')), 'temp' => 26, 'condition' => 'Rain'],
                    ['date' => date('Y-m-d', strtotime('+4 day')), 'temp' => 25, 'condition' => 'Thunderstorm'],
                    ['date' => date('Y-m-d', strtotime('+5 day')), 'temp' => 28, 'condition' => 'Clear'],
                ];
            } else {
                $url = "{$this->baseUrl}/forecast?q=" . urlencode($city) . "&appid={$this->apiKey}&units=metric";
                $data = $this->callApi($url);
                
                $forecast = [];
                if ($data && isset($data['list'])) {
                    $daily = [];
                    foreach ($data['list'] as $item) {
                        $date = date('Y-m-d', $item['dt']);
                        if (!isset($daily[$date])) {
                            $daily[$date] = ['temps' => [], 'conditions' => []];
                        }
                        $daily[$date]['temps'][] = $item['main']['temp'];
                        $daily[$date]['conditions'][] = $item['weather'][0]['main'];
                    }
                    
                    foreach (array_slice($daily, 0, 5, true) as $date => $day) {
                        $forecast[] = [
                            'date' => $date,
                            'temp' => round(array_sum($day['temps']) / count($day['temps']), 1),
                            'condition' => $this->getMostCommon($day['conditions'])
                        ];
                    }
                }
            }
            
            // Add combat impact to each day
            foreach ($forecast as &$day) {
                $day['combat_impact'] = $this->calculateCombatImpact([
                    'temperature' => $day['temp'],
                    'condition' => $day['condition'],
                    'humidity' => 70,
                    'wind_speed' => 5
                ], $loadoutImpact);
            }
            
            return $this->jsonResponse($response, 200, true, 'Combat forecast retrieved', [
                'city' => $city,
                'equipment' => $loadoutImpact,
                'forecast' => $forecast
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * Calculate how weather affects combat
     */
    private function calculateCombatImpact(array $weather, array $equipment = []): array
    {
        $condition = $weather['condition'] ?? 'Clear';
        $temp = $weather['temperature'] ?? 25;
        $bioCapacity = (int)($equipment['bio_capacity'] ?? 0);
        $recoveryRate = (int)($equipment['recovery_rate'] ?? 0);
        $riskModifier = (int)($equipment['risk_modifier'] ?? 0);
        
        // Base impacts by condition
        $impacts = [
            'Clear' => [
                'bio_drain_modifier' => 0,
                'visibility' => 100,
                'mobility' => 100,
                'risk_modifier' => 0,
                'description' => 'Perfect combat conditions'
            ],
            'Clouds' => [
                'bio_drain_modifier' => -5,
                'visibility' => 90,
                'mobility' => 95,
                'risk_modifier' => -2,
                'description' => 'Slightly overcast, good conditions'
            ],
            'Rain' => [
                'bio_drain_modifier' => 10,
                'visibility' => 70,
                'mobility' => 80,
                'risk_modifier' => 5,
                'description' => 'Wet conditions, reduced mobility'
            ],
            'Drizzle' => [
                'bio_drain_modifier' => 5,
                'visibility' => 85,
                'mobility' => 90,
                'risk_modifier' => 2,
                'description' => 'Light rain, minor impact'
            ],
            'Thunderstorm' => [
                'bio_drain_modifier' => 25,
                'visibility' => 50,
                'mobility' => 60,
                'risk_modifier' => 15,
                'description' => 'DANGEROUS! Extreme bio-drain'
            ],
            'Snow' => [
                'bio_drain_modifier' => 15,
                'visibility' => 60,
                'mobility' => 70,
                'risk_modifier' => 10,
                'description' => 'Cold and slippery, increased drain'
            ],
            'Fog' => [
                'bio_drain_modifier' => 5,
                'visibility' => 30,
                'mobility' => 90,
                'risk_modifier' => 8,
                'description' => 'Poor visibility, tactical disadvantage'
            ],
            'Mist' => [
                'bio_drain_modifier' => 3,
                'visibility' => 60,
                'mobility' => 95,
                'risk_modifier' => 4,
                'description' => 'Light mist, minor visibility loss'
            ],
            'Haze' => [
                'bio_drain_modifier' => 8,
                'visibility' => 50,
                'mobility' => 95,
                'risk_modifier' => 6,
                'description' => 'Poor air quality, increased strain'
            ]
        ];
        
        $baseImpact = $impacts[$condition] ?? $impacts['Clear'];
        
        // Temperature modifiers
        $tempModifier = 0;
        if ($temp > 35) {
            $tempModifier = 15;
        } elseif ($temp > 30) {
            $tempModifier = 8;
        } elseif ($temp < 0) {
            $tempModifier = 20;
        } elseif ($temp < 10) {
            $tempModifier = 10;
        }

        // Gear modifies survivability and strain.
        // Risky gear increases strain, while recovery/bio capacity reduce it.
        $gearAdjustment = ($riskModifier * 1.2) - ($recoveryRate * 0.35) - ($bioCapacity * 0.08);
        $bioDrainModifier = $baseImpact['bio_drain_modifier'] + $tempModifier + round($gearAdjustment);
        $riskModifierFinal = $baseImpact['risk_modifier'] + $tempModifier + max(0, $riskModifier);
        
        return [
            'condition' => $condition,
            'temperature' => $temp,
            'bio_drain_modifier' => $bioDrainModifier,
            'visibility' => max(0, $baseImpact['visibility']),
            'mobility' => max(0, $baseImpact['mobility']),
            'risk_modifier' => $riskModifierFinal,
            'description' => $baseImpact['description'],
            'equipment_adjustment' => round($gearAdjustment, 2),
            'combat_rating' => $this->getCombatRating($bioDrainModifier)
        ];
    }
    
    private function getCombatRating(int $drain): array
    {
        if ($drain >= 30) {
            return ['rating' => 'F', 'label' => '⚠️ EXTREME', 'color' => '#ff0044'];
        } elseif ($drain >= 20) {
            return ['rating' => 'D', 'label' => '🔴 DANGEROUS', 'color' => '#ff0044'];
        } elseif ($drain >= 10) {
            return ['rating' => 'C', 'label' => '🟠 CHALLENGING', 'color' => '#ffaa00'];
        } elseif ($drain >= 5) {
            return ['rating' => 'B', 'label' => '🟡 MODERATE', 'color' => '#ffaa00'];
        } elseif ($drain > 0) {
            return ['rating' => 'A', 'label' => '🟢 FAVORABLE', 'color' => '#00ff88'];
        } else {
            return ['rating' => 'S', 'label' => '💚 OPTIMAL', 'color' => '#00ff88'];
        }
    }
    
    private function getLoadoutImpact(int $combatantId): array
    {
        $stmt = $this->db->prepare("SELECT helmet_id, core_id, dampener_id, gauntlets_id, battery_id FROM loadouts WHERE combatant_id = ?");
        $stmt->execute([$combatantId]);
        $loadout = $stmt->fetch();

        if (!$loadout) {
            return ['bio_capacity' => 0, 'recovery_rate' => 0, 'risk_modifier' => 0];
        }

        $slots = ['helmet', 'core', 'dampener', 'gauntlets', 'battery'];
        $totals = ['bio_capacity' => 0, 'recovery_rate' => 0, 'risk_modifier' => 0];

        foreach ($slots as $slot) {
            $gearId = $loadout[$slot . '_id'] ?? null;
            if (!$gearId) {
                continue;
            }

            $gearStmt = $this->db->prepare("SELECT bio_capacity, recovery_rate, risk_modifier FROM gear_items WHERE id = ?");
            $gearStmt->execute([$gearId]);
            $gear = $gearStmt->fetch();

            if (!$gear) {
                continue;
            }

            $totals['bio_capacity'] += (int)($gear['bio_capacity'] ?? 0);
            $totals['recovery_rate'] += (int)($gear['recovery_rate'] ?? 0);
            $totals['risk_modifier'] += (int)($gear['risk_modifier'] ?? 0);
        }

        return $totals;
    }
    
    private function getMostCommon(array $items): string
    {
        $counts = array_count_values($items);
        arsort($counts);
        return array_key_first($counts);
    }
    
    private function callApi(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return $response ? json_decode($response, true) : null;
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