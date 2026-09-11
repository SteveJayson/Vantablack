<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Database config
define('DB_HOST', 'localhost');
define('DB_NAME', 'aegis_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ============================================
// 3RD PARTY API CONFIGURATIONS
// ============================================

// 1. OpenWeatherMap
define('OPENWEATHER_API_KEY', 'YOUR_OPENWEATHER_API_KEY_HERE');
define('OPENWEATHER_BASE_URL', 'https://api.openweathermap.org/data/2.5');

// 2. ExchangeRate API
define('EXCHANGERATE_API_KEY', 'YOUR_EXCHANGERATE_API_KEY_HERE');
define('EXCHANGERATE_BASE_URL', 'https://v6.exchangerate-api.com/v6');

// 3. IP-API (free)
define('IPAPI_BASE_URL', 'http://ip-api.com/json');

// 4. QR Server (free)
define('QRSERVER_BASE_URL', 'https://api.qrserver.com/v1');

$app = AppFactory::create();

// CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Content-Type', 'application/json');
});

$app->addBodyParsingMiddleware();

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// ============================================
// ROUTES
// ============================================

// Combatants
$app->get('/api/combatants', \Aegis\Controllers\CombatantController::class . ':getAllCombatants');
$app->get('/api/combatants/{id}', \Aegis\Controllers\CombatantController::class . ':getCombatant');

// Gear
$app->get('/api/gear', \Aegis\Controllers\GearController::class . ':getCatalog');
$app->get('/api/gear/{id}', \Aegis\Controllers\GearController::class . ':getGearItem');

// Loadouts
$app->post('/api/loadouts/validate', \Aegis\Controllers\LoadoutController::class . ':validateLoadout');
$app->post('/api/loadouts/equip', \Aegis\Controllers\LoadoutController::class . ':equipLoadout');
$app->get('/api/loadouts/{id}', \Aegis\Controllers\LoadoutController::class . ':getLoadout');
$app->get('/api/combatants/{id}/loadout', \Aegis\Controllers\LoadoutController::class . ':getCombatantLoadout');

// Marketplace
$app->post('/api/marketplace/purchase', \Aegis\Controllers\MarketplaceController::class . ':purchase');
$app->post('/api/marketplace/sell', \Aegis\Controllers\MarketplaceController::class . ':sell');
$app->get('/api/marketplace/inventory/{id}', \Aegis\Controllers\MarketplaceController::class . ':getInventory');

// Analytics
$app->get('/api/analytics/{id}/summary', \Aegis\Controllers\AnalyticsController::class . ':getSummary');
$app->get('/api/analytics/{id}/achievements', \Aegis\Controllers\AnalyticsController::class . ':getAchievements');
$app->get('/api/analytics/{id}/battle-history', \Aegis\Controllers\AnalyticsController::class . ':getBattleHistory');
$app->post('/api/analytics/{id}/sync', \Aegis\Controllers\AnalyticsController::class . ':syncStats');

// Missions
$app->get('/api/missions/leaderboard', \Aegis\Controllers\MissionController::class . ':getLeaderboard');
$app->post('/api/missions/complete', \Aegis\Controllers\MissionController::class . ':completeMission');
$app->get('/api/missions', \Aegis\Controllers\MissionController::class . ':getMissions');
$app->get('/api/missions/{id}', \Aegis\Controllers\MissionController::class . ':getMission');

// Admin
$app->get('/api/admin/dashboard', \Aegis\Controllers\AdminController::class . ':getDashboard');
$app->get('/api/admin/transactions', \Aegis\Controllers\AdminController::class . ':getTransactions');
$app->get('/api/admin/users', \Aegis\Controllers\AdminController::class . ':getUsers');
$app->get('/api/admin/users/{id}/transactions', \Aegis\Controllers\AdminController::class . ':getUserTransactions');

// ============================================
// 3RD PARTY API ROUTES
// ============================================

// 1. Weather API
$app->get('/api/weather/current', \Aegis\Controllers\WeatherController::class . ':getCurrent');
$app->get('/api/weather/forecast', \Aegis\Controllers\WeatherController::class . ':getForecast');
$app->post('/api/weather/combat-impact', \Aegis\Controllers\WeatherController::class . ':getCombatImpact');

// 2. Currency/Exchange Rate API
$app->get('/api/currency/rates', \Aegis\Controllers\CurrencyController::class . ':getRates');
$app->get('/api/currency/convert', \Aegis\Controllers\CurrencyController::class . ':convert');
$app->get('/api/currency/gear-price/{id}', \Aegis\Controllers\CurrencyController::class . ':getGearPriceInCurrency');

// 3. Geolocation API
$app->get('/api/geo/lookup', \Aegis\Controllers\GeoController::class . ':lookupIp');
$app->post('/api/geo/combatant-location', \Aegis\Controllers\GeoController::class . ':getCombatantLocation');

// 4. QR Code API
$app->get('/api/qr/gear/{id}', \Aegis\Controllers\QrController::class . ':generateGearQr');
$app->get('/api/qr/combatant/{id}', \Aegis\Controllers\QrController::class . ':generateCombatantQr');

// Health check
$app->get('/api/health', function ($request, $response, $args) {
    $payload = [
        'status' => 200,
        'success' => true,
        'message' => 'Aegis & Anarchy API is running!',
        'data' => [
            'version' => '3.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'features' => [
                'analytics' => true,
                'achievements' => true,
                'missions' => true,
                'weather' => true,
                'currency' => true,
                'geolocation' => true,
                'qr_codes' => true,
                'admin' => true
            ],
            'roles' => ['civilian', 'hero', 'villain', 'admin'],
            'third_party_apis' => [
                'openweathermap' => 'https://openweathermap.org',
                'exchangerate' => 'https://exchangerate-api.com',
                'ip-api' => 'http://ip-api.com',
                'qrserver' => 'https://qrserver.com'
            ]
        ]
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// 404 handler
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response, $args) {
    $payload = ['status' => 404, 'success' => false, 'message' => 'Route not found', 'data' => []];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->addErrorMiddleware(true, true, true);
$app->run();