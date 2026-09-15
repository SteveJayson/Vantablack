<?php
/**
 * ONE-TIME SCRIPT: Create Admin Account
 * 
 * Run this ONCE to create the admin account.
 * Admin accounts cannot be created via the UI anymore.
 * 
 * Usage: php create_admin.php
 */

$host = 'localhost';
$dbname = 'aegis_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "========================================\n";
    echo "  VANTABLACK - ADMIN SETUP\n";
    echo "========================================\n\n";
    
    // 1. Check/create admin combatant
    $stmt = $pdo->query("SELECT id FROM combatants WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $pdo->exec("
            INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level)
            VALUES ('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5)
        ");
        $combatantId = $pdo->lastInsertId();
        echo "✅ Created admin combatant (ID: $combatantId)\n";
    } else {
        $combatantId = $admin['id'];
        echo "ℹ️  Admin combatant exists (ID: $combatantId)\n";
    }
    
    // 2. Check/create admin user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $password = 'admin123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, combatant_id, role, login_count)
            VALUES ('admin', ?, ?, 'admin', 0)
        ");
        $stmt->execute([$hash, $combatantId]);
        
        echo "✅ Created admin user\n";
        echo "   ─────────────────────\n";
        echo "   Username: admin\n";
        echo "   Password: admin123\n";
        echo "   ─────────────────────\n";
    } else {
        echo "ℹ️  Admin user already exists\n";
    }
    
    echo "\n========================================\n";
    echo "  ✅ ADMIN SETUP COMPLETE!\n";
    echo "========================================\n";
    echo "\nYou can now login as admin:\n";
    echo "  URL: http://localhost:5173\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}