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

// ============================================
// 3RD PARTY API KEYS
// ============================================
// Get your free API key at: https://openweathermap.org/api
// Replace the placeholder below with your actual key
define('OPENWEATHER_API_KEY', 'ec74178dbcb33f3ebd64f1f7db7ee90e');

$app = AppFactory::create();

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
// AUTH
// ============================================
$app->post('/api/auth/register', \Aegis\Controllers\AuthController::class . ':register');
$app->post('/api/auth/login', \Aegis\Controllers\AuthController::class . ':login');
$app->get('/api/auth/me', \Aegis\Controllers\AuthController::class . ':me');
$app->post('/api/auth/logout', \Aegis\Controllers\AuthController::class . ':logout');
$app->get('/api/auth/roles', \Aegis\Controllers\AuthController::class . ':getRoles');

// FORGOT PASSWORD
$app->post('/api/auth/forgot-password', \Aegis\Controllers\AuthController::class . ':forgotPassword');
$app->post('/api/auth/reset-password', \Aegis\Controllers\AuthController::class . ':resetPassword');
$app->get('/api/auth/verify-reset-token', \Aegis\Controllers\AuthController::class . ':verifyResetToken');

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
// MISSIONS
// ============================================
$app->get('/api/missions/leaderboard', \Aegis\Controllers\MissionController::class . ':getLeaderboard');
$app->post('/api/missions/complete', \Aegis\Controllers\MissionController::class . ':completeMission');
$app->get('/api/missions', \Aegis\Controllers\MissionController::class . ':getMissions');
$app->get('/api/missions/{id}', \Aegis\Controllers\MissionController::class . ':getMission');

// ============================================
// COMBAT ROUTES (Feature #1)
// ============================================
$app->get('/api/combat/opponents', \Aegis\Controllers\CombatController::class . ':getOpponents');
$app->post('/api/combat/simulate', \Aegis\Controllers\CombatController::class . ':simulate');
$app->get('/api/combat/history/{id}', \Aegis\Controllers\CombatController::class . ':getHistory');
$app->get('/api/combat/leaderboard', \Aegis\Controllers\CombatController::class . ':getLeaderboard');

// ============================================
// CRAFTING ROUTES
// ============================================
$app->get('/api/crafting/recipes', \Aegis\Controllers\CraftingController::class . ':getRecipes');
$app->post('/api/crafting/craft', \Aegis\Controllers\CraftingController::class . ':craft');
$app->get('/api/crafting/history/{id}', \Aegis\Controllers\CraftingController::class . ':getHistory');
$app->post('/api/crafting/disassemble', \Aegis\Controllers\CraftingController::class . ':disassemble');

// ============================================
// FACTION WARS ROUTES
// ============================================
$app->get('/api/factions/territories', \Aegis\Controllers\FactionController::class . ':getTerritories');
$app->get('/api/factions/stats', \Aegis\Controllers\FactionController::class . ':getFactionStats');
$app->post('/api/factions/attack', \Aegis\Controllers\FactionController::class . ':attack');
$app->get('/api/factions/history', \Aegis\Controllers\FactionController::class . ':getAttackHistory');
$app->get('/api/factions/bonuses/{id}', \Aegis\Controllers\FactionController::class . ':getActiveBonuses');

// ============================================
// DAILY REWARDS ROUTES
// ============================================
$app->get('/api/rewards/daily-status/{id}', \Aegis\Controllers\RewardsController::class . ':getDailyStatus');
$app->post('/api/rewards/claim-daily', \Aegis\Controllers\RewardsController::class . ':claimDaily');
$app->get('/api/rewards/leaderboard', \Aegis\Controllers\RewardsController::class . ':getStreakLeaderboard');
$app->get('/api/rewards/history/{id}', \Aegis\Controllers\RewardsController::class . ':getClaimHistory');

// ============================================
// CHAT ROUTES
// ============================================
$app->get('/api/chat/messages', \Aegis\Controllers\ChatController::class . ':getMessages');
$app->post('/api/chat/send', \Aegis\Controllers\ChatController::class . ':sendMessage');
$app->get('/api/chat/stats', \Aegis\Controllers\ChatController::class . ':getChatStats');
$app->delete('/api/chat/message/{id}', \Aegis\Controllers\ChatController::class . ':deleteMessage');

// ============================================
// EVENTS ROUTES
// ============================================
$app->get('/api/events', \Aegis\Controllers\EventController::class . ':getEvents');
$app->get('/api/events/active-count', \Aegis\Controllers\EventController::class . ':getActiveCount');
$app->get('/api/events/{id}', \Aegis\Controllers\EventController::class . ':getEvent');
$app->post('/api/events/join', \Aegis\Controllers\EventController::class . ':joinEvent');
$app->post('/api/events/score', \Aegis\Controllers\EventController::class . ':addScore');
$app->post('/api/events/claim-rewards', \Aegis\Controllers\EventController::class . ':claimRewards');

// ============================================
// NOTIFICATION ROUTES
// ============================================
$app->get('/api/notifications/{id}', \Aegis\Controllers\NotificationController::class . ':getNotifications');
$app->get('/api/notifications/{id}/unread-count', \Aegis\Controllers\NotificationController::class . ':getUnreadCount');
$app->post('/api/notifications/create', \Aegis\Controllers\NotificationController::class . ':createNotification');
$app->post('/api/notifications/{id}/read', \Aegis\Controllers\NotificationController::class . ':markAsRead');
$app->post('/api/notifications/read-all', \Aegis\Controllers\NotificationController::class . ':markAllAsRead');
$app->delete('/api/notifications/{id}', \Aegis\Controllers\NotificationController::class . ':deleteNotification');
$app->post('/api/notifications/broadcast', \Aegis\Controllers\NotificationController::class . ':broadcast');


// ============================================
// ADMIN
// ============================================
$app->get('/api/admin/dashboard', \Aegis\Controllers\AdminController::class . ':getDashboard');
$app->get('/api/admin/spending-report', \Aegis\Controllers\AdminController::class . ':getSpendingReport');
$app->get('/api/admin/role-transactions', \Aegis\Controllers\AdminController::class . ':getRoleTransactions');
$app->get('/api/admin/transactions', \Aegis\Controllers\AdminController::class . ':getTransactions');
$app->get('/api/admin/users', \Aegis\Controllers\AdminController::class . ':getUsers');
$app->get('/api/admin/users/{id}/transactions', \Aegis\Controllers\AdminController::class . ':getUserTransactions');
$app->get('/api/admin/login-history', \Aegis\Controllers\AdminController::class . ':getLoginHistory');
$app->get('/api/admin/charts', \Aegis\Controllers\AdminController::class . ':getChartData');

// ============================================
// WEATHER (3RD PARTY API)
// ============================================
$app->get('/api/weather/current', \Aegis\Controllers\WeatherController::class . ':getCurrent');
$app->get('/api/weather/forecast', \Aegis\Controllers\WeatherController::class . ':getForecast');
$app->post('/api/weather/combat-impact', \Aegis\Controllers\WeatherController::class . ':getCombatImpact');
$app->get('/api/weather/combat-forecast', \Aegis\Controllers\WeatherController::class . ':getCombatForecast');

// ============================================
// EXTERNAL APIs (ExchangeRate + RandomUser)
// ============================================
$app->get('/api/external/exchange-rates', \Aegis\Controllers\ExternalApiController::class . ':getExchangeRates');
$app->map(['GET', 'POST'], '/api/external/convert-credits', \Aegis\Controllers\ExternalApiController::class . ':convertCredits');
$app->get('/api/external/marketplace-prices', \Aegis\Controllers\ExternalApiController::class . ':getMarketplacePrices');
$app->get('/api/external/random-user', \Aegis\Controllers\ExternalApiController::class . ':getRandomUser');
$app->map(['GET', 'POST'], '/api/external/generate-bot', \Aegis\Controllers\ExternalApiController::class . ':generateBot');
$app->map(['GET', 'POST'], '/api/external/generate-bots', \Aegis\Controllers\ExternalApiController::class . ':generateBots');

// ============================================
// HEALTH
// ============================================
$app->get('/api/health', function ($request, $response, $args) {
    $apiKey = defined('OPENWEATHER_API_KEY') ? OPENWEATHER_API_KEY : '';
    $weatherEnabled = !empty($apiKey) && $apiKey !== 'YOUR_API_KEY_HERE';
    
    $payload = [
        'status' => 200,
        'success' => true,
        'message' => 'Vantablack API is running!',
        'data' => [
            'version' => '5.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'features' => [
                'auth' => true,
                'analytics' => true,
                'missions' => true,
                'admin' => true,
                'marketplace' => true,
                'roles' => true,
                'weather' => $weatherEnabled,
                'weather_mode' => $weatherEnabled ? 'LIVE' : 'DEMO'
            ]
        ]
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// 404
$app->map(['GET', 'POST', 'PUT', 'DELETE'], '/{routes:.+}', function ($request, $response, $args) {
    $payload = [
        'status' => 404,
        'success' => false,
        'message' => 'Route not found',
        'data' => [
            'url' => (string)$request->getUri()->getPath(),
            'method' => $request->getMethod()
        ]
    ];
    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
});

$app->addErrorMiddleware(true, true, true);
$app->run();