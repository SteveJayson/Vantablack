<?php

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;

// Load autoloader
require __DIR__ . '/../vendor/autoload.php';

// Manually set environment variables
define('DB_HOST', 'localhost');
define('DB_NAME', 'aegis_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create app
$app = AppFactory::create();

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Content-Type', 'application/json');
});

// Add body parsing middleware
$app->addBodyParsingMiddleware();

// Handle preflight OPTIONS requests
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// ============================================
// ============== ALL ROUTES =================
// ============================================

// ---------- COMBATANT ROUTES ----------
$app->get('/api/combatants', \Aegis\Controllers\CombatantController::class . ':getAllCombatants');
$app->get('/api/combatants/{id}', \Aegis\Controllers\CombatantController::class . ':getCombatant');

// ---------- GEAR ROUTES ----------
$app->get('/api/gear', \Aegis\Controllers\GearController::class . ':getCatalog');
$app->get('/api/gear/{id}', \Aegis\Controllers\GearController::class . ':getGearItem');

// ---------- LOADOUT ROUTES ----------
$app->post('/api/loadouts/validate', \Aegis\Controllers\LoadoutController::class . ':validateLoadout');
$app->post('/api/loadouts/equip', \Aegis\Controllers\LoadoutController::class . ':equipLoadout');
$app->get('/api/loadouts/{id}', \Aegis\Controllers\LoadoutController::class . ':getLoadout');
$app->get('/api/combatants/{id}/loadout', \Aegis\Controllers\LoadoutController::class . ':getCombatantLoadout');

// ---------- MARKETPLACE ROUTES ----------
$app->post('/api/marketplace/purchase', \Aegis\Controllers\MarketplaceController::class . ':purchase');
$app->post('/api/marketplace/sell', \Aegis\Controllers\MarketplaceController::class . ':sell');
$app->get('/api/marketplace/inventory/{id}', \Aegis\Controllers\MarketplaceController::class . ':getInventory');
$app->get('/api/combatants/{id}/inventory', \Aegis\Controllers\MarketplaceController::class . ':getInventory');

// ---------- HEALTH CHECK ROUTE ----------
$app->get('/api/health', function ($request, $response, $args) {
    $payload = [
        'status' => 200,
        'success' => true,
        'message' => 'Aegis & Anarchy API is running!',
        'data' => [
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => 'connected'
        ]
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// ---------- 404 Handler ----------
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response, $args) {
    $payload = [
        'status' => 404,
        'success' => false,
        'message' => 'Route not found',
        'data' => []
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

// Error handler
$app->addErrorMiddleware(true, true, true);

// Run the app
$app->run();