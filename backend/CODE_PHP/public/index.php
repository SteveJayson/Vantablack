<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Database config
define('DB_HOST', 'localhost');
define('DB_NAME', 'aegis_db');
define('DB_USER', 'root');
define('DB_PASS', '');

$app = AppFactory::create();

// CORS
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
// AUTH ROUTES
// ============================================
$app->post('/api/auth/register', \Aegis\Controllers\AuthController::class . ':register');
$app->post('/api/auth/login', \Aegis\Controllers\AuthController::class . ':login');
$app->get('/api/auth/me', \Aegis\Controllers\AuthController::class . ':me');
$app->post('/api/auth/logout', \Aegis\Controllers\AuthController::class . ':logout');
$app->get('/api/auth/roles', \Aegis\Controllers\AuthController::class . ':getRoles');

// ============================================
// COMBATANTS
// ============================================
$app->get('/api/combatants', \Aegis\Controllers\CombatantController::class . ':getAllCombatants');
$app->get('/api/combatants/{id}', \Aegis\Controllers\CombatantController::class . ':getCombatant');

// ============================================
// GEAR
// ============================================
$app->get('/api/gear', \Aegis\Controllers\GearController::class . ':getCatalog');
$app->get('/api/gear/{id}', \Aegis\Controllers\GearController::class . ':getGearItem');

// ============================================
// LOADOUTS
// ============================================
$app->post('/api/loadouts/validate', \Aegis\Controllers\LoadoutController::class . ':validateLoadout');
$app->post('/api/loadouts/equip', \Aegis\Controllers\LoadoutController::class . ':equipLoadout');
$app->get('/api/loadouts/{id}', \Aegis\Controllers\LoadoutController::class . ':getLoadout');
$app->get('/api/combatants/{id}/loadout', \Aegis\Controllers\LoadoutController::class . ':getCombatantLoadout');

// ============================================
// MARKETPLACE
// ============================================
$app->post('/api/marketplace/purchase', \Aegis\Controllers\MarketplaceController::class . ':purchase');
$app->post('/api/marketplace/sell', \Aegis\Controllers\MarketplaceController::class . ':sell');
$app->get('/api/marketplace/inventory/{id}', \Aegis\Controllers\MarketplaceController::class . ':getInventory');

// ============================================
// ANALYTICS
// ============================================
$app->get('/api/analytics/{id}/summary', \Aegis\Controllers\AnalyticsController::class . ':getSummary');
$app->get('/api/analytics/{id}/achievements', \Aegis\Controllers\AnalyticsController::class . ':getAchievements');
$app->get('/api/analytics/{id}/battle-history', \Aegis\Controllers\AnalyticsController::class . ':getBattleHistory');
$app->post('/api/analytics/{id}/sync', \Aegis\Controllers\AnalyticsController::class . ':syncStats');

// ============================================
// MISSIONS (static routes first!)
// ============================================
$app->get('/api/missions/leaderboard', \Aegis\Controllers\MissionController::class . ':getLeaderboard');
$app->post('/api/missions/complete', \Aegis\Controllers\MissionController::class . ':completeMission');
$app->get('/api/missions', \Aegis\Controllers\MissionController::class . ':getMissions');
$app->get('/api/missions/{id}', \Aegis\Controllers\MissionController::class . ':getMission');

// ============================================
// ADMIN
// ============================================
$app->get('/api/admin/dashboard', \Aegis\Controllers\AdminController::class . ':getDashboard');
$app->get('/api/admin/transactions', \Aegis\Controllers\AdminController::class . ':getTransactions');
$app->get('/api/admin/users', \Aegis\Controllers\AdminController::class . ':getUsers');
$app->get('/api/admin/users/{id}/transactions', \Aegis\Controllers\AdminController::class . ':getUserTransactions');
$app->get('/api/admin/login-history', \Aegis\Controllers\AdminController::class . ':getLoginHistory');

// ============================================
// HEALTH
// ============================================
$app->get('/api/health', function ($request, $response, $args) {
    $payload = [
        'status' => 200,
        'success' => true,
        'message' => 'Aegis & Anarchy API is running!',
        'data' => [
            'version' => '4.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'features' => ['auth', 'analytics', 'missions', 'admin', 'marketplace', 'roles']
        ]
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// 404
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response, $args) {
    $payload = ['status' => 404, 'success' => false, 'message' => 'Route not found', 'data' => []];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->addErrorMiddleware(true, true, true);
$app->run();