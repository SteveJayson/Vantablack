<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'https://vantablack-frontend.onrender.com',
];

$envOrigins = getenv('ALLOWED_ORIGINS');
if ($envOrigins) {
    $allowedOrigins = array_merge($allowedOrigins, explode(',', $envOrigins));
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$origin = trim($origin);

if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif ($origin !== '') {
    header('Access-Control-Allow-Origin: *');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key, X-Request-Id, Idempotency-Key');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ============================================
// DATABASE CONFIG
// ============================================
// On Render, these come from Environment Variables.
// On local Laragon, these defaults work.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'aegis_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// ============================================
// 3RD PARTY API KEYS
// ============================================
define('OPENWEATHER_API_KEY', getenv('OPENWEATHER_API_KEY') ?: 'ec74178dbcb33f3ebd64f1f7db7ee90e');

$app = AppFactory::create();

// ============================================
// CORS MIDDLEWARE
// ============================================
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    
    // Allowed origins — add your Render frontend URL after deploying it
    $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'https://vantablack-frontend.onrender.com',
        // Add your Render frontend URL here after deploying:
        // 'https://vantablack-frontend.onrender.com',
    ];
    
    // Also allow origins from env var (comma-separated)
    $envOrigins = getenv('ALLOWED_ORIGINS');
    if ($envOrigins) {
        $allowedOrigins = array_merge($allowedOrigins, explode(',', $envOrigins));
    }
    
    $origin = $request->getHeaderLine('Origin');
    
    // If origin matches allowed list, echo it back. Otherwise allow *.
    if ($origin && in_array($origin, $allowedOrigins)) {
        $allowOrigin = $origin;
    } else {
        $allowOrigin = '*';
    }
    
    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
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
$app->post('/api/loadouts/equip-item', \Aegis\Controllers\LoadoutController::class . ':equipItem');
$app->post('/api/loadouts/unequip-item', \Aegis\Controllers\LoadoutController::class . ':unequipItem');

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
// COMBAT ROUTES
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
// LEADERBOARD ROUTES
// ============================================
$app->get('/api/leaderboards/fighters', \Aegis\Controllers\LeaderboardController::class . ':getTopFighters');
$app->get('/api/leaderboards/earners', \Aegis\Controllers\LeaderboardController::class . ':getTopEarners');
$app->get('/api/leaderboards/collectors', \Aegis\Controllers\LeaderboardController::class . ':getTopCollectors');
$app->get('/api/leaderboards/achievements', \Aegis\Controllers\LeaderboardController::class . ':getAchievementLeaders');
$app->get('/api/leaderboards/factions', \Aegis\Controllers\LeaderboardController::class . ':getFactionRankings');
$app->get('/api/leaderboards/global', \Aegis\Controllers\LeaderboardController::class . ':getGlobalRankings');
$app->get('/api/leaderboards/my-rank/{id}', \Aegis\Controllers\LeaderboardController::class . ':getMyRank');

// ============================================
// REPLAY ROUTES
// ============================================
$app->post('/api/replays/save', \Aegis\Controllers\ReplayController::class . ':saveReplay');
$app->get('/api/replays/shared', \Aegis\Controllers\ReplayController::class . ':getSharedReplays');
$app->get('/api/replays/detail/{id}', \Aegis\Controllers\ReplayController::class . ':getReplayDetail');
$app->get('/api/replays/{id}', \Aegis\Controllers\ReplayController::class . ':getReplays');
$app->delete('/api/replays/{id}', \Aegis\Controllers\ReplayController::class . ':deleteReplay');
$app->post('/api/replays/toggle-visibility/{id}', \Aegis\Controllers\ReplayController::class . ':toggleVisibility');

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
$app->post('/api/admin/grant-credits', \Aegis\Controllers\AdminController::class . ':grantCredits');
$app->get('/api/admin/grant-history', \Aegis\Controllers\AdminController::class . ':getGrantHistory');

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
// HEALTH CHECK
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
            'environment' => getenv('APP_ENV') ?: 'production',
            'database' => [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'name' => DB_NAME,
                'connected' => true
            ],
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

// ============================================
// 404 HANDLER (must be last)
// ============================================
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