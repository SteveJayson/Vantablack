<?php

namespace Aegis\Controllers;

use Aegis\Config\Database;
use PDO;

class ExternalApiController
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
    
    // ============================================
    // API 1: EXCHANGERATE (Currency Conversion)
    // ============================================
    
    /**
     * GET /api/external/exchange-rates
     * Get current exchange rates from USD
     */
    public function getExchangeRates($request, $response, $args)
    {
        try {
            $url = "https://open.er-api.com/v6/latest/USD";
            $data = $this->callApi($url);
            
            if (!$data || !isset($data['rates'])) {
                // Fallback rates
                return $this->jsonResponse($response, 200, true, 'Exchange rates (fallback)', [
                    'source' => 'fallback',
                    'base' => 'USD',
                    'rates' => [
                        'PHP' => 56.50,
                        'EUR' => 0.92,
                        'JPY' => 149.50,
                        'GBP' => 0.79,
                        'KRW' => 1320.00,
                        'CNY' => 7.24
                    ],
                    'updated' => date('Y-m-d H:i:s'),
                    'note' => 'Live rates unavailable - using cached values'
                ]);
            }
            
            return $this->jsonResponse($response, 200, true, 'Exchange rates retrieved', [
                'source' => 'live',
                'base' => 'USD',
                'rates' => $data['rates'],
                'updated' => $data['time_last_update_utc'] ?? date('Y-m-d H:i:s'),
                'next_update' => $data['time_next_update_utc'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/external/convert-credits
     * Convert Aegis credits to real currency
     * Body: { "credits": 5000, "currency": "PHP" }
     */
    public function convertCredits($request, $response, $args)
    {
        try {
            $rawBody = (string)$request->getBody();
            $body = !empty($rawBody) ? json_decode($rawBody, true) : [];
            $queryParams = $request->getQueryParams();
            
            $credits = (int)($body['credits'] ?? $queryParams['credits'] ?? 0);
            $currency = strtoupper($body['currency'] ?? $queryParams['currency'] ?? 'PHP');
            
            // 1 Aegis Credit = 0.10 USD
            $creditsToUsd = $credits * 0.10;
            
            // Get exchange rates
            $rates = null;
            $source = 'fallback';
            
            $url = "https://open.er-api.com/v6/latest/USD";
            $data = $this->callApi($url);
            
            if ($data && isset($data['rates'])) {
                $rates = $data['rates'];
                $source = 'live';
            } else {
                // Fallback rates
                $rates = [
                    'PHP' => 56.50,
                    'EUR' => 0.92,
                    'JPY' => 149.50,
                    'GBP' => 0.79,
                    'KRW' => 1320.00,
                    'CNY' => 7.24,
                    'USD' => 1.0
                ];
            }
            
            $rate = $rates[$currency] ?? null;
            
            if (!$rate) {
                return $this->jsonResponse($response, 400, false, "Currency '$currency' not supported");
            }
            
            $converted = $creditsToUsd * $rate;
            
            return $this->jsonResponse($response, 200, true, 'Credits converted', [
                'credits' => $credits,
                'credits_to_usd_rate' => 0.10,
                'usd_value' => round($creditsToUsd, 2),
                'target_currency' => $currency,
                'exchange_rate' => round($rate, 4),
                'converted_value' => round($converted, 2),
                'formatted' => $this->formatCurrency($converted, $currency),
                'source' => $source
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * GET /api/external/marketplace-prices?currency=PHP
     * Show all gear prices in real currency
     */
    public function getMarketplacePrices($request, $response, $args)
    {
        try {
            $currency = strtoupper($this->getQuery($request, 'currency', 'PHP'));
            
            // Get exchange rates
            $rates = null;
            $source = 'fallback';
            
            $url = "https://open.er-api.com/v6/latest/USD";
            $data = $this->callApi($url);
            
            if ($data && isset($data['rates'])) {
                $rates = $data['rates'];
                $source = 'live';
            } else {
                $rates = ['PHP' => 56.50, 'EUR' => 0.92, 'JPY' => 149.50, 'GBP' => 0.79, 'USD' => 1.0];
            }
            
            $rate = $rates[$currency] ?? 1.0;
            
            // Get all gear
            $stmt = $this->db->query("
                SELECT id, slot, source, name, price, tier, is_legendary
                FROM gear_items
                ORDER BY price ASC
            ");
            $gear = $stmt->fetchAll();
            
            // Convert prices
            foreach ($gear as &$item) {
                $usdValue = $item['price'] * 0.10;
                $converted = $usdValue * $rate;
                
                $item['usd_value'] = round($usdValue, 2);
                $item['converted_value'] = round($converted, 2);
                $item['formatted'] = $this->formatCurrency($converted, $currency);
            }
            
            return $this->jsonResponse($response, 200, true, 'Marketplace prices retrieved', [
                'currency' => $currency,
                'exchange_rate' => round($rate, 4),
                'source' => $source,
                'items' => $gear,
                'total_items' => count($gear)
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // API 2: RANDOMUSER (Test Data Generator)
    // ============================================
    
    /**
     * GET /api/external/random-user
     * Generate one random user
     */
    public function getRandomUser($request, $response, $args)
    {
        try {
            $url = "https://randomuser.me/api/";
            $data = $this->callApi($url);
            
            if (!$data || !isset($data['results'][0])) {
                // Fallback
                return $this->jsonResponse($response, 200, true, 'Random user (fallback)', [
                    'source' => 'fallback',
                    'name' => 'Alex Johnson',
                    'email' => 'alex.johnson@example.com',
                    'avatar' => null,
                    'country' => 'Philippines'
                ]);
            }
            
            $user = $data['results'][0];
            
            return $this->jsonResponse($response, 200, true, 'Random user generated', [
                'source' => 'live',
                'name' => $user['name']['first'] . ' ' . $user['name']['last'],
                'email' => $user['email'],
                'avatar' => $user['picture']['thumbnail'],
                'country' => $user['location']['country'],
                'city' => $user['location']['city']
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/external/generate-bot
     * Generate a random bot combatant and add to database
     * Body: { "role": "hero" | "villain" | "civilian" }
     */
    public function generateBot($request, $response, $args)
    {
        $this->db->beginTransaction();
        
        try {
            $rawBody = (string)$request->getBody();
            $body = !empty($rawBody) ? json_decode($rawBody, true) : [];
            $queryParams = $request->getQueryParams();
            $role = $body['role'] ?? $queryParams['role'] ?? 'hero';
            
            if (!in_array($role, ['civilian', 'hero', 'villain'])) {
                $this->db->rollBack();
                return $this->jsonResponse($response, 400, false, 'Invalid role');
            }
            
            // Get random user from API
            $url = "https://randomuser.me/api/";
            $data = $this->callApi($url);
            
            $name = 'Test Combatant';
            $email = 'test@aegis.com';
            $avatar = null;
            
            if ($data && isset($data['results'][0])) {
                $user = $data['results'][0];
                $name = $user['name']['first'] . ' ' . $user['name']['last'];
                $email = $user['email'];
                $avatar = $user['picture']['thumbnail'];
            }
            
            // Generate unique username
            $baseUsername = strtolower(str_replace(' ', '', $name));
            $username = $baseUsername . rand(1000, 9999);
            
            // Check if exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            while ($stmt->fetch()) {
                $username = $baseUsername . rand(1000, 9999);
                $stmt->execute([$username]);
            }
            
            // Role config
            $configs = [
                'civilian' => ['credits' => 500, 'bio' => 100, 'recovery' => 1, 'risk' => 5, 'faction' => 'hero', 'clearance' => 1],
                'hero' => ['credits' => 5000, 'bio' => 1200, 'recovery' => 3, 'risk' => 10, 'faction' => 'hero', 'clearance' => 2],
                'villain' => ['credits' => 5000, 'bio' => 1100, 'recovery' => 4, 'risk' => 15, 'faction' => 'villain', 'clearance' => 2]
            ];
            $config = $configs[$role];
            
            // Create combatant
            $stmt = $this->db->prepare("
                INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $config['bio'], $config['recovery'], $config['risk'],
                $config['credits'], $config['faction'], $role, $config['clearance']
            ]);
            $combatantId = (int)$this->db->lastInsertId();
            
            // Create user (password: botpassword123)
            $hash = password_hash('botpassword123', PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, password_hash, combatant_id, role, login_count)
                VALUES (?, ?, ?, ?, 0)
            ");
            $stmt->execute([$username, $hash, $combatantId, $role]);
            
            // Create stats
            try {
                $stmt = $this->db->prepare("INSERT INTO combatant_stats (combatant_id) VALUES (?)");
                $stmt->execute([$combatantId]);
            } catch (\Exception $e) {}
            
            $this->db->commit();
            
            return $this->jsonResponse($response, 200, true, 'Bot generated successfully!', [
                'combatant' => [
                    'id' => $combatantId,
                    'name' => $name,
                    'username' => $username,
                    'role' => $role,
                    'credits' => $config['credits'],
                    'bio_capacity' => $config['bio'],
                    'avatar' => $avatar,
                    'email' => $email,
                    'password' => 'botpassword123'
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/external/generate-bots
     * Generate multiple bots at once
     * Body: { "count": 5, "role": "hero" }
     */
    public function generateBots($request, $response, $args)
    {
        try {
            $rawBody = (string)$request->getBody();
            $body = !empty($rawBody) ? json_decode($rawBody, true) : [];
            $queryParams = $request->getQueryParams();
            
            $count = min(max((int)($body['count'] ?? $queryParams['count'] ?? 3), 1), 10);
            $role = $body['role'] ?? $queryParams['role'] ?? 'hero';
            
            $generated = [];
            $errors = [];
            
            for ($i = 0; $i < $count; $i++) {
                // Generate directly (simplified)
                $configs = [
                    'civilian' => ['credits' => 500, 'bio' => 100, 'recovery' => 1, 'risk' => 5, 'faction' => 'hero', 'clearance' => 1],
                    'hero' => ['credits' => 5000, 'bio' => 1200, 'recovery' => 3, 'risk' => 10, 'faction' => 'hero', 'clearance' => 2],
                    'villain' => ['credits' => 5000, 'bio' => 1100, 'recovery' => 4, 'risk' => 15, 'faction' => 'villain', 'clearance' => 2]
                ];
                $config = $configs[$role] ?? $configs['hero'];
                
                // Get random user
                $url = "https://randomuser.me/api/";
                $data = $this->callApi($url);
                
                $name = 'Bot ' . rand(1000, 9999);
                if ($data && isset($data['results'][0])) {
                    $user = $data['results'][0];
                    $name = $user['name']['first'] . ' ' . $user['name']['last'];
                }
                
                $baseUsername = strtolower(str_replace(' ', '', $name));
                $username = $baseUsername . rand(100, 999);
                
                try {
                    // Check if username exists
                    $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $username = $baseUsername . rand(1000, 9999);
                    }
                    
                    // Create combatant
                    $stmt = $this->db->prepare("
                        INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $name, $config['bio'], $config['recovery'], $config['risk'],
                        $config['credits'], $config['faction'], $role, $config['clearance']
                    ]);
                    $combatantId = (int)$this->db->lastInsertId();
                    
                    // Create user
                    $hash = password_hash('botpassword123', PASSWORD_DEFAULT);
                    $stmt = $this->db->prepare("
                        INSERT INTO users (username, password_hash, combatant_id, role, login_count)
                        VALUES (?, ?, ?, ?, 0)
                    ");
                    $stmt->execute([$username, $hash, $combatantId, $role]);
                    
                    // Stats
                    try {
                        $stmt = $this->db->prepare("INSERT INTO combatant_stats (combatant_id) VALUES (?)");
                        $stmt->execute([$combatantId]);
                    } catch (\Exception $e) {}
                    
                    $generated[] = [
                        'id' => $combatantId,
                        'name' => $name,
                        'username' => $username,
                        'role' => $role
                    ];
                } catch (\Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
            
            return $this->jsonResponse($response, 200, true, "Generated {$count} bots", [
                'generated' => $generated,
                'count' => count($generated),
                'errors' => $errors
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse($response, 500, false, 'Server error: ' . $e->getMessage());
        }
    }
    
    // ============================================
    // HELPERS
    // ============================================
    
    private function formatCurrency($amount, $currency): string
    {
        $symbols = [
            'USD' => '$',
            'PHP' => '₱',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'KRW' => '₩',
            'CNY' => '¥'
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }
    
    private function callApi(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Aegis-Anarchy/1.0');
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