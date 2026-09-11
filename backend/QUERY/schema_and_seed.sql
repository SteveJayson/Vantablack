-- ============================================
-- UPDATE COMBATANTS TABLE - Add Role System
-- ============================================

-- First, check if role column exists, if not add it
ALTER TABLE combatants 
ADD COLUMN role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian' AFTER faction;

-- Update existing combatants to have roles
UPDATE combatants SET role = 'hero' WHERE faction = 'hero';
UPDATE combatants SET role = 'villain' WHERE faction = 'villain';

-- Add admin user
INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) 
VALUES 
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- TRANSACTION LOG TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_combatants_role ON combatants(role);

-- ============================================
-- SEED DATA - Sample Transactions
-- ============================================

INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(1, 'Vantablack', 'hero', 'purchase', 'c5-01', 'Singularity Core', 15000, 3200, -11800, 'failed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(7, 'Juan Dela Cruz', 'civilian', 'purchase', 'h1-01', 'Standard Issue Helmet', 500, 500, 0, 'completed'),
(8, 'Maria Santos', 'civilian', 'purchase', 'g1-01', 'Starter Gauntlets', 400, 300, -100, 'failed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

    -- ============================================
-- UPDATE EXISTING COMBATANTS WITH ROLES
-- ============================================

ALTER TABLE combatants 
ADD COLUMN role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian' AFTER faction;

-- Set roles for existing combatants
UPDATE combatants SET role = 'hero' WHERE faction = 'hero';
UPDATE combatants SET role = 'villain' WHERE faction = 'villain';

-- ============================================
-- ADD ADMIN AND CIVILIAN USERS
-- ============================================

INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) VALUES
-- Admin (ID will be 7)
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),

-- Civilians (IDs 8 and 9)
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- TRANSACTION LOG TABLE
-- ============================================

DROP TABLE IF EXISTS transactions;

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_combatants_role ON combatants(role);

-- Seed sample transactions
INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(1, 'Vantablack', 'hero', 'sell', 'b3-01', 'Quantum Battery', 1000, 3200, 4200, 'completed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

    -- ============================================
-- AEGIS & ANARCHY - COMPLETE SCHEMA v3.0
-- With Roles, Transactions, and Admin
-- ============================================

-- Drop everything
DROP VIEW IF EXISTS v_admin_dashboard;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS combatant_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS battle_history;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS combatant_stats;
DROP TABLE IF EXISTS mission_completions;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS loadouts;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS gear_items;
DROP TABLE IF EXISTS combatants;

-- ============================================
-- 1. COMBATANTS TABLE (with ROLE)
-- ============================================

CREATE TABLE combatants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio_capacity_max INT NOT NULL DEFAULT 1000,
    base_recovery INT NOT NULL DEFAULT 3,
    base_risk INT NOT NULL DEFAULT 12,
    credits INT NOT NULL DEFAULT 5000,
    faction ENUM('hero', 'villain') NOT NULL DEFAULT 'hero',
    role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian',
    clearance_level INT NOT NULL DEFAULT 1,
    last_bonus_claim TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 2. GEAR ITEMS TABLE
-- ============================================

CREATE TABLE gear_items (
    id VARCHAR(50) PRIMARY KEY,
    slot ENUM('helmet', 'core', 'dampener', 'gauntlets', 'battery') NOT NULL,
    source ENUM('armory', 'black-market') NOT NULL,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    bio_capacity INT NOT NULL DEFAULT 0,
    recovery_rate INT NOT NULL DEFAULT 0,
    risk_modifier INT NOT NULL DEFAULT 0,
    clearance_required INT NOT NULL DEFAULT 1,
    tier INT NOT NULL DEFAULT 1,
    is_legendary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 3. INVENTORY TABLE
-- ============================================

CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    equipped BOOLEAN DEFAULT FALSE,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inventory_item (combatant_id, gear_id)
);

-- ============================================
-- 4. LOADOUTS TABLE
-- ============================================

CREATE TABLE loadouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL UNIQUE,
    helmet_id VARCHAR(50) NULL,
    core_id VARCHAR(50) NULL,
    dampener_id VARCHAR(50) NULL,
    gauntlets_id VARCHAR(50) NULL,
    battery_id VARCHAR(50) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (helmet_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (core_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (dampener_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (gauntlets_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (battery_id) REFERENCES gear_items(id) ON DELETE SET NULL
);

-- ============================================
-- 5. TRANSACTIONS TABLE (NEW!)
-- ============================================

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 6. COMBATANT STATS TABLE
-- ============================================

CREATE TABLE combatant_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL UNIQUE,
    total_credits_earned INT NOT NULL DEFAULT 0,
    total_credits_spent INT NOT NULL DEFAULT 0,
    total_gear_purchased INT NOT NULL DEFAULT 0,
    total_gear_sold INT NOT NULL DEFAULT 0,
    total_missions_completed INT NOT NULL DEFAULT 0,
    total_missions_failed INT NOT NULL DEFAULT 0,
    total_bounties_collected INT NOT NULL DEFAULT 0,
    total_battles_won INT NOT NULL DEFAULT 0,
    total_battles_lost INT NOT NULL DEFAULT 0,
    total_battles_drawn INT NOT NULL DEFAULT 0,
    longest_win_streak INT NOT NULL DEFAULT 0,
    current_win_streak INT NOT NULL DEFAULT 0,
    total_gear_owned INT NOT NULL DEFAULT 0,
    total_tier5_gear INT NOT NULL DEFAULT 0,
    total_legendary_gear INT NOT NULL DEFAULT 0,
    total_hours_played INT NOT NULL DEFAULT 0,
    first_played_at TIMESTAMP NULL,
    last_played_at TIMESTAMP NULL,
    global_rank INT NULL,
    faction_rank INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 7. BATTLE HISTORY TABLE
-- ============================================

CREATE TABLE battle_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    opponent_id INT NULL,
    opponent_name VARCHAR(100) NULL,
    battle_type ENUM('pvp', 'pve', 'event', 'training') NOT NULL DEFAULT 'pve',
    result ENUM('win', 'loss', 'draw') NOT NULL,
    loadout_used JSON NOT NULL,
    opponent_loadout JSON,
    damage_dealt INT NULL,
    damage_taken INT NULL,
    credits_earned INT DEFAULT 0,
    gear_dropped VARCHAR(50) NULL,
    battle_duration_seconds INT NULL,
    fought_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 8. ACHIEVEMENTS TABLE
-- ============================================

CREATE TABLE achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category ENUM('combat', 'missions', 'gear', 'economy', 'social', 'special') NOT NULL,
    points INT NOT NULL DEFAULT 10,
    badge_icon VARCHAR(50) NOT NULL,
    unlock_condition JSON NOT NULL,
    is_secret BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE combatant_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_combatant_achievement (combatant_id, achievement_id)
);

-- ============================================
-- 9. ACTIVITY LOG TABLE
-- ============================================

CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    activity_type ENUM('login', 'purchase', 'sell', 'mission', 'battle', 'craft', 'event', 'achievement') NOT NULL,
    details JSON,
    credits_change INT DEFAULT 0,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 10. MISSIONS TABLE
-- ============================================

CREATE TABLE missions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    reward INT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard', 'expert') NOT NULL,
    required_clearance INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mission_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    mission_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion (combatant_id, mission_id)
);

-- ============================================
-- INDEXES
-- ============================================

CREATE INDEX idx_combatants_role ON combatants(role);
CREATE INDEX idx_combatants_faction ON combatants(faction);
CREATE INDEX idx_gear_slot ON gear_items(slot);
CREATE INDEX idx_gear_source ON gear_items(source);
CREATE INDEX idx_gear_tier ON gear_items(tier);
CREATE INDEX idx_inventory_combatant ON inventory(combatant_id);
CREATE INDEX idx_stats_combatant ON combatant_stats(combatant_id);
CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_battle_combatant ON battle_history(combatant_id);
CREATE INDEX idx_activity_combatant ON activity_log(combatant_id);

-- ============================================
-- SEED DATA: COMBATANTS (with roles)
-- ============================================

INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) VALUES
-- Heroes (1-3)
('Vantablack', 1400, 3, 12, 8200, 'hero', 'hero', 3),
('Solar Flare', 1200, 5, 8, 4500, 'hero', 'hero', 2),
('Steel Guardian', 1800, 2, 15, 3000, 'hero', 'hero', 4),

-- Villains (4-6)
('Shadow Syndicate', 1100, 4, 20, 9800, 'villain', 'villain', 3),
('Crimson Wraith', 1500, 1, 25, 6700, 'villain', 'villain', 4),
('Neuro Hack', 900, 6, 10, 2500, 'villain', 'villain', 2),

-- Admin (7)
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),

-- Civilians (8-9)
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- SEED DATA: GEAR ITEMS (same as before)
-- ============================================

INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
-- Tier 1
('h1-01', 'helmet', 'armory', 'Standard Issue Helmet', 500, 50, 1, -2, 1, 1, FALSE),
('c1-01', 'core', 'armory', 'Basic Core Unit', 800, 100, 2, -3, 1, 1, FALSE),
('d1-01', 'dampener', 'armory', 'Energy Dampener V1', 600, 30, 3, -5, 1, 1, FALSE),
('g1-01', 'gauntlets', 'armory', 'Starter Gauntlets', 400, 40, 1, -1, 1, 1, FALSE),
('b1-01', 'battery', 'armory', 'Standard Battery', 300, 60, 0, 0, 1, 1, FALSE),
-- Tier 2
('h2-01', 'helmet', 'armory', 'Tactical Command Helmet', 1200, 120, 2, -5, 2, 2, FALSE),
('c2-01', 'core', 'armory', 'Enhanced Core Unit', 2000, 250, 4, -8, 2, 2, FALSE),
('d2-01', 'dampener', 'armory', 'Energy Dampener V2', 1500, 80, 6, -12, 2, 2, FALSE),
('g2-01', 'gauntlets', 'armory', 'Combat Gauntlets MK2', 1000, 100, 2, -3, 2, 2, FALSE),
('b2-01', 'battery', 'armory', 'High-Capacity Battery', 800, 150, 1, 2, 2, 2, FALSE),
-- Tier 3
('h3-01', 'helmet', 'armory', 'Cerebral Interface Helmet', 2500, 200, 3, -10, 3, 3, FALSE),
('c3-01', 'core', 'armory', 'Fusion Core', 4000, 400, 5, -15, 3, 3, FALSE),
('d3-01', 'dampener', 'armory', 'Quantum Dampener', 3500, 150, 8, -20, 3, 3, FALSE),
('g3-01', 'gauntlets', 'armory', 'Energy Blade Gauntlets', 2800, 180, 3, -8, 3, 3, FALSE),
('b3-01', 'battery', 'armory', 'Quantum Battery', 2000, 250, 2, 5, 3, 3, FALSE),
('cor-02', 'core', 'black-market', 'Ferrox Overclock Weave', 3400, 420, -1, 22, 3, 3, FALSE),
-- Tier 4
('h4-01', 'helmet', 'armory', 'Psi-Shield Helmet', 5000, 350, 4, -15, 4, 4, FALSE),
('c4-01', 'core', 'armory', 'Antimatter Core', 8000, 600, 6, -25, 4, 4, FALSE),
('d4-01', 'dampener', 'armory', 'Void Dampener', 7000, 250, 10, -30, 4, 4, FALSE),
('g4-01', 'gauntlets', 'armory', 'Titan Gauntlets', 6000, 300, 4, -12, 4, 4, FALSE),
('b4-01', 'battery', 'armory', 'Infinite Battery', 5000, 400, 3, 10, 4, 4, FALSE),
-- Tier 5
('h5-01', 'helmet', 'black-market', 'Chronos Helmet', 10000, 500, 5, -25, 5, 5, TRUE),
('c5-01', 'core', 'black-market', 'Singularity Core', 15000, 800, 8, -40, 5, 5, TRUE),
('d5-01', 'dampener', 'black-market', 'Nexus Dampener', 12000, 350, 12, -50, 5, 5, TRUE),
('g5-01', 'gauntlets', 'black-market', 'Phantom Gauntlets', 11000, 400, 5, -20, 5, 5, TRUE),
('b5-01', 'battery', 'black-market', 'Void Battery', 9000, 500, 4, 15, 5, 5, TRUE),
('h5-02', 'helmet', 'black-market', 'Shadow Crown', 8500, 450, 6, -30, 4, 5, TRUE);

-- ============================================
-- SEED DATA: INVENTORY
-- ============================================

INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(1, 'h3-01', TRUE), (1, 'c3-01', TRUE), (1, 'd2-01', TRUE), (1, 'g3-01', TRUE), (1, 'b3-01', TRUE),
(1, 'h4-01', FALSE), (1, 'c5-01', FALSE),
(2, 'h2-01', TRUE), (2, 'c2-01', TRUE), (2, 'd1-01', TRUE), (2, 'g2-01', TRUE), (2, 'b2-01', TRUE),
(3, 'h4-01', TRUE), (3, 'c4-01', TRUE), (3, 'd3-01', TRUE), (3, 'g4-01', TRUE), (3, 'b4-01', TRUE),
(4, 'h5-01', TRUE), (4, 'c5-01', TRUE), (4, 'd4-01', TRUE), (4, 'g5-01', TRUE), (4, 'b5-01', TRUE),
(8, 'h1-01', TRUE),
(9, 'g1-01', TRUE);

-- ============================================
-- SEED DATA: LOADOUTS
-- ============================================

INSERT INTO loadouts (combatant_id, helmet_id, core_id, dampener_id, gauntlets_id, battery_id) VALUES
(1, 'h3-01', 'c3-01', 'd2-01', 'g3-01', 'b3-01'),
(2, 'h2-01', 'c2-01', 'd1-01', 'g2-01', 'b2-01'),
(3, 'h4-01', 'c4-01', 'd3-01', 'g4-01', 'b4-01'),
(4, 'h5-01', 'c5-01', 'd4-01', 'g5-01', 'b5-01'),
(8, 'h1-01', NULL, NULL, NULL, NULL),
(9, NULL, NULL, NULL, 'g1-01', NULL);

-- ============================================
-- SEED DATA: TRANSACTIONS
-- ============================================

INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(1, 'Vantablack', 'hero', 'purchase', 'c5-01', 'Singularity Core', 15000, 3200, -11800, 'failed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(8, 'Juan Dela Cruz', 'civilian', 'purchase', 'h1-01', 'Standard Issue Helmet', 500, 500, 0, 'completed'),
(9, 'Maria Santos', 'civilian', 'purchase', 'g1-01', 'Starter Gauntlets', 400, 300, -100, 'failed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

-- ============================================
-- DONE!
-- ============================================
SHOW TABLES;

-- ============================================
-- UPDATE COMBATANTS TABLE - Add Role System
-- ============================================

-- First, check if role column exists, if not add it
ALTER TABLE combatants 
ADD COLUMN role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian' AFTER faction;

-- Update existing combatants to have roles
UPDATE combatants SET role = 'hero' WHERE faction = 'hero';
UPDATE combatants SET role = 'villain' WHERE faction = 'villain';

-- Add admin user
INSERT INTO combatants (name, 
bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) 
VALUES 
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- TRANSACTION LOG TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_combatants_role ON combatants(role);

-- ============================================
-- SEED DATA - Sample Transactions
-- ============================================

INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(1, 'Vantablack', 'hero', 'purchase', 'c5-01', 'Singularity Core', 15000, 3200, -11800, 'failed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(7, 'Juan Dela Cruz', 'civilian', 'purchase', 'h1-01', 'Standard Issue Helmet', 500, 500, 0, 'completed'),
(8, 'Maria Santos', 'civilian', 'purchase', 'g1-01', 'Starter Gauntlets', 400, 300, -100, 'failed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT SUM(amount) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

    -- ============================================
-- UPDATE EXISTING COMBATANTS WITH ROLES
-- ============================================

ALTER TABLE combatants 
ADD COLUMN role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian' AFTER faction;

-- Set roles for existing combatants
UPDATE combatants SET role = 'hero' WHERE faction = 'hero';
UPDATE combatants SET role = 'villain' WHERE faction = 'villain';

-- ============================================
-- ADD ADMIN AND CIVILIAN USERS
-- ============================================

INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) VALUES
-- Admin (ID will be 7)
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),

-- Civilians (IDs 8 and 9)
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- TRANSACTION LOG TABLE
-- ============================================

DROP TABLE IF EXISTS transactions;

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_combatants_role ON combatants(role);

-- Seed sample transactions
INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(1, 'Vantablack', 'hero', 'sell', 'b3-01', 'Quantum Battery', 1000, 3200, 4200, 'completed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

    -- ============================================
-- AEGIS & ANARCHY - COMPLETE SCHEMA v3.0
-- With Roles, Transactions, and Admin
-- ============================================

-- Drop everything
DROP VIEW IF EXISTS v_admin_dashboard;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS combatant_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS battle_history;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS combatant_stats;
DROP TABLE IF EXISTS mission_completions;
DROP TABLE IF EXISTS missions;
DROP TABLE IF EXISTS loadouts;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS gear_items;
DROP TABLE IF EXISTS combatants;

-- ============================================
-- 1. COMBATANTS TABLE (with ROLE)
-- ============================================

CREATE TABLE combatants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio_capacity_max INT NOT NULL DEFAULT 1000,
    base_recovery INT NOT NULL DEFAULT 3,
    base_risk INT NOT NULL DEFAULT 12,
    credits INT NOT NULL DEFAULT 5000,
    faction ENUM('hero', 'villain') NOT NULL DEFAULT 'hero',
    role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL DEFAULT 'civilian',
    clearance_level INT NOT NULL DEFAULT 1,
    last_bonus_claim TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 2. GEAR ITEMS TABLE
-- ============================================

CREATE TABLE gear_items (
    id VARCHAR(50) PRIMARY KEY,
    slot ENUM('helmet', 'core', 'dampener', 'gauntlets', 'battery') NOT NULL,
    source ENUM('armory', 'black-market') NOT NULL,
    name VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    bio_capacity INT NOT NULL DEFAULT 0,
    recovery_rate INT NOT NULL DEFAULT 0,
    risk_modifier INT NOT NULL DEFAULT 0,
    clearance_required INT NOT NULL DEFAULT 1,
    tier INT NOT NULL DEFAULT 1,
    is_legendary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- 3. INVENTORY TABLE
-- ============================================

CREATE TABLE inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    equipped BOOLEAN DEFAULT FALSE,
    acquired_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_inventory_item (combatant_id, gear_id)
);

-- ============================================
-- 4. LOADOUTS TABLE
-- ============================================

CREATE TABLE loadouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL UNIQUE,
    helmet_id VARCHAR(50) NULL,
    core_id VARCHAR(50) NULL,
    dampener_id VARCHAR(50) NULL,
    gauntlets_id VARCHAR(50) NULL,
    battery_id VARCHAR(50) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (helmet_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (core_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (dampener_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (gauntlets_id) REFERENCES gear_items(id) ON DELETE SET NULL,
    FOREIGN KEY (battery_id) REFERENCES gear_items(id) ON DELETE SET NULL
);

-- ============================================
-- 5. TRANSACTIONS TABLE (NEW!)
-- ============================================

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 6. COMBATANT STATS TABLE
-- ============================================

CREATE TABLE combatant_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL UNIQUE,
    total_credits_earned INT NOT NULL DEFAULT 0,
    total_credits_spent INT NOT NULL DEFAULT 0,
    total_gear_purchased INT NOT NULL DEFAULT 0,
    total_gear_sold INT NOT NULL DEFAULT 0,
    total_missions_completed INT NOT NULL DEFAULT 0,
    total_missions_failed INT NOT NULL DEFAULT 0,
    total_bounties_collected INT NOT NULL DEFAULT 0,
    total_battles_won INT NOT NULL DEFAULT 0,
    total_battles_lost INT NOT NULL DEFAULT 0,
    total_battles_drawn INT NOT NULL DEFAULT 0,
    longest_win_streak INT NOT NULL DEFAULT 0,
    current_win_streak INT NOT NULL DEFAULT 0,
    total_gear_owned INT NOT NULL DEFAULT 0,
    total_tier5_gear INT NOT NULL DEFAULT 0,
    total_legendary_gear INT NOT NULL DEFAULT 0,
    total_hours_played INT NOT NULL DEFAULT 0,
    first_played_at TIMESTAMP NULL,
    last_played_at TIMESTAMP NULL,
    global_rank INT NULL,
    faction_rank INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 7. BATTLE HISTORY TABLE
-- ============================================

CREATE TABLE battle_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    opponent_id INT NULL,
    opponent_name VARCHAR(100) NULL,
    battle_type ENUM('pvp', 'pve', 'event', 'training') NOT NULL DEFAULT 'pve',
    result ENUM('win', 'loss', 'draw') NOT NULL,
    loadout_used JSON NOT NULL,
    opponent_loadout JSON,
    damage_dealt INT NULL,
    damage_taken INT NULL,
    credits_earned INT DEFAULT 0,
    gear_dropped VARCHAR(50) NULL,
    battle_duration_seconds INT NULL,
    fought_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 8. ACHIEVEMENTS TABLE
-- ============================================

CREATE TABLE achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NOT NULL,
    category ENUM('combat', 'missions', 'gear', 'economy', 'social', 'special') NOT NULL,
    points INT NOT NULL DEFAULT 10,
    badge_icon VARCHAR(50) NOT NULL,
    unlock_condition JSON NOT NULL,
    is_secret BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE combatant_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    achievement_id INT NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_combatant_achievement (combatant_id, achievement_id)
);

-- ============================================
-- 9. ACTIVITY LOG TABLE
-- ============================================

CREATE TABLE activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    activity_type ENUM('login', 'purchase', 'sell', 'mission', 'battle', 'craft', 'event', 'achievement') NOT NULL,
    details JSON,
    credits_change INT DEFAULT 0,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 10. MISSIONS TABLE
-- ============================================

CREATE TABLE missions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    reward INT NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard', 'expert') NOT NULL,
    required_clearance INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mission_completions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    mission_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_completion (combatant_id, mission_id)
);

-- ============================================
-- INDEXES
-- ============================================

CREATE INDEX idx_combatants_role ON combatants(role);
CREATE INDEX idx_combatants_faction ON combatants(faction);
CREATE INDEX idx_gear_slot ON gear_items(slot);
CREATE INDEX idx_gear_source ON gear_items(source);
CREATE INDEX idx_gear_tier ON gear_items(tier);
CREATE INDEX idx_inventory_combatant ON inventory(combatant_id);
CREATE INDEX idx_stats_combatant ON combatant_stats(combatant_id);
CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_battle_combatant ON battle_history(combatant_id);
CREATE INDEX idx_activity_combatant ON activity_log(combatant_id);

-- ============================================
-- SEED DATA: COMBATANTS (with roles)
-- ============================================

INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) VALUES
-- Heroes (1-3)
('Vantablack', 1400, 3, 12, 8200, 'hero', 'hero', 3),
('Solar Flare', 1200, 5, 8, 4500, 'hero', 'hero', 2),
('Steel Guardian', 1800, 2, 15, 3000, 'hero', 'hero', 4),

-- Villains (4-6)
('Shadow Syndicate', 1100, 4, 20, 9800, 'villain', 'villain', 3),
('Crimson Wraith', 1500, 1, 25, 6700, 'villain', 'villain', 4),
('Neuro Hack', 900, 6, 10, 2500, 'villain', 'villain', 2),

-- Admin (7)
('System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),

-- Civilians (8-9)
('Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
('Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- SEED DATA: GEAR ITEMS (same as before)
-- ============================================

INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
-- Tier 1
('h1-01', 'helmet', 'armory', 'Standard Issue Helmet', 500, 50, 1, -2, 1, 1, FALSE),
('c1-01', 'core', 'armory', 'Basic Core Unit', 800, 100, 2, -3, 1, 1, FALSE),
('d1-01', 'dampener', 'armory', 'Energy Dampener V1', 600, 30, 3, -5, 1, 1, FALSE),
('g1-01', 'gauntlets', 'armory', 'Starter Gauntlets', 400, 40, 1, -1, 1, 1, FALSE),
('b1-01', 'battery', 'armory', 'Standard Battery', 300, 60, 0, 0, 1, 1, FALSE),
-- Tier 2
('h2-01', 'helmet', 'armory', 'Tactical Command Helmet', 1200, 120, 2, -5, 2, 2, FALSE),
('c2-01', 'core', 'armory', 'Enhanced Core Unit', 2000, 250, 4, -8, 2, 2, FALSE),
('d2-01', 'dampener', 'armory', 'Energy Dampener V2', 1500, 80, 6, -12, 2, 2, FALSE),
('g2-01', 'gauntlets', 'armory', 'Combat Gauntlets MK2', 1000, 100, 2, -3, 2, 2, FALSE),
('b2-01', 'battery', 'armory', 'High-Capacity Battery', 800, 150, 1, 2, 2, 2, FALSE),
-- Tier 3
('h3-01', 'helmet', 'armory', 'Cerebral Interface Helmet', 2500, 200, 3, -10, 3, 3, FALSE),
('c3-01', 'core', 'armory', 'Fusion Core', 4000, 400, 5, -15, 3, 3, FALSE),
('d3-01', 'dampener', 'armory', 'Quantum Dampener', 3500, 150, 8, -20, 3, 3, FALSE),
('g3-01', 'gauntlets', 'armory', 'Energy Blade Gauntlets', 2800, 180, 3, -8, 3, 3, FALSE),
('b3-01', 'battery', 'armory', 'Quantum Battery', 2000, 250, 2, 5, 3, 3, FALSE),
('cor-02', 'core', 'black-market', 'Ferrox Overclock Weave', 3400, 420, -1, 22, 3, 3, FALSE),
-- Tier 4
('h4-01', 'helmet', 'armory', 'Psi-Shield Helmet', 5000, 350, 4, -15, 4, 4, FALSE),
('c4-01', 'core', 'armory', 'Antimatter Core', 8000, 600, 6, -25, 4, 4, FALSE),
('d4-01', 'dampener', 'armory', 'Void Dampener', 7000, 250, 10, -30, 4, 4, FALSE),
('g4-01', 'gauntlets', 'armory', 'Titan Gauntlets', 6000, 300, 4, -12, 4, 4, FALSE),
('b4-01', 'battery', 'armory', 'Infinite Battery', 5000, 400, 3, 10, 4, 4, FALSE),
-- Tier 5
('h5-01', 'helmet', 'black-market', 'Chronos Helmet', 10000, 500, 5, -25, 5, 5, TRUE),
('c5-01', 'core', 'black-market', 'Singularity Core', 15000, 800, 8, -40, 5, 5, TRUE),
('d5-01', 'dampener', 'black-market', 'Nexus Dampener', 12000, 350, 12, -50, 5, 5, TRUE),
('g5-01', 'gauntlets', 'black-market', 'Phantom Gauntlets', 11000, 400, 5, -20, 5, 5, TRUE),
('b5-01', 'battery', 'black-market', 'Void Battery', 9000, 500, 4, 15, 5, 5, TRUE),
('h5-02', 'helmet', 'black-market', 'Shadow Crown', 8500, 450, 6, -30, 4, 5, TRUE);

-- ============================================
-- SEED DATA: INVENTORY
-- ============================================

INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(1, 'h3-01', TRUE), (1, 'c3-01', TRUE), (1, 'd2-01', TRUE), (1, 'g3-01', TRUE), (1, 'b3-01', TRUE),
(1, 'h4-01', FALSE), (1, 'c5-01', FALSE),
(2, 'h2-01', TRUE), (2, 'c2-01', TRUE), (2, 'd1-01', TRUE), (2, 'g2-01', TRUE), (2, 'b2-01', TRUE),
(3, 'h4-01', TRUE), (3, 'c4-01', TRUE), (3, 'd3-01', TRUE), (3, 'g4-01', TRUE), (3, 'b4-01', TRUE),
(4, 'h5-01', TRUE), (4, 'c5-01', TRUE), (4, 'd4-01', TRUE), (4, 'g5-01', TRUE), (4, 'b5-01', TRUE),
(8, 'h1-01', TRUE),
(9, 'g1-01', TRUE);

-- ============================================
-- SEED DATA: LOADOUTS
-- ============================================

INSERT INTO loadouts (combatant_id, helmet_id, core_id, dampener_id, gauntlets_id, battery_id) VALUES
(1, 'h3-01', 'c3-01', 'd2-01', 'g3-01', 'b3-01'),
(2, 'h2-01', 'c2-01', 'd1-01', 'g2-01', 'b2-01'),
(3, 'h4-01', 'c4-01', 'd3-01', 'g4-01', 'b4-01'),
(4, 'h5-01', 'c5-01', 'd4-01', 'g5-01', 'b5-01'),
(8, 'h1-01', NULL, NULL, NULL, NULL),
(9, NULL, NULL, NULL, 'g1-01', NULL);

-- ============================================
-- SEED DATA: TRANSACTIONS
-- ============================================

INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(1, 'Vantablack', 'hero', 'purchase', 'c5-01', 'Singularity Core', 15000, 3200, -11800, 'failed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(8, 'Juan Dela Cruz', 'civilian', 'purchase', 'h1-01', 'Standard Issue Helmet', 500, 500, 0, 'completed'),
(9, 'Maria Santos', 'civilian', 'purchase', 'g1-01', 'Starter Gauntlets', 400, 300, -100, 'failed');

-- ============================================
-- VIEW FOR ADMIN DASHBOARD
-- ============================================

CREATE OR REPLACE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

-- ============================================
-- DONE!
-- ============================================
SHOW TABLES;



-- ============================================
-- ROLE-BASED SYSTEM UPDATE
-- ============================================

USE aegis_db;

-- Step 1: Add role column to combatants
ALTER TABLE combatants 
ADD COLUMN IF NOT EXISTS role ENUM('civilian', 'hero', 'villain', 'admin') 
NOT NULL DEFAULT 'civilian' AFTER faction;

-- Step 2: Update existing combatants
UPDATE combatants SET role = 'hero' WHERE faction = 'hero' AND role = 'civilian';
UPDATE combatants SET role = 'villain' WHERE faction = 'villain' AND role = 'civilian';

-- Step 3: Add admin and civilians
INSERT IGNORE INTO combatants (id, name, bio_capacity_max, base_recovery, base_risk, credits, faction, role, clearance_level) VALUES
(7, 'System Admin', 0, 0, 0, 999999, 'hero', 'admin', 5),
(8, 'Juan Dela Cruz', 100, 1, 5, 500, 'hero', 'civilian', 1),
(9, 'Maria Santos', 80, 1, 3, 300, 'hero', 'civilian', 1);

-- ============================================
-- TRANSACTIONS TABLE
-- ============================================

DROP TABLE IF EXISTS transactions;

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL,
    combatant_name VARCHAR(100) NOT NULL,
    combatant_role ENUM('civilian', 'hero', 'villain', 'admin') NOT NULL,
    transaction_type ENUM('purchase', 'sell') NOT NULL,
    gear_id VARCHAR(50) NOT NULL,
    gear_name VARCHAR(100) NOT NULL,
    amount INT NOT NULL,
    credits_before INT NOT NULL,
    credits_after INT NOT NULL,
    status ENUM('completed', 'failed', 'refunded') DEFAULT 'completed',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- Indexes
CREATE INDEX idx_transactions_combatant ON transactions(combatant_id);
CREATE INDEX idx_transactions_type ON transactions(transaction_type);
CREATE INDEX idx_transactions_date ON transactions(created_at);
CREATE INDEX idx_combatants_role ON combatants(role);

-- ============================================
-- ADMIN DASHBOARD VIEW
-- ============================================

DROP VIEW IF EXISTS v_admin_dashboard;

CREATE VIEW v_admin_dashboard AS
SELECT 
    (SELECT COUNT(*) FROM combatants WHERE role = 'civilian') as total_civilians,
    (SELECT COUNT(*) FROM combatants WHERE role = 'hero') as total_heroes,
    (SELECT COUNT(*) FROM combatants WHERE role = 'villain') as total_villains,
    (SELECT COUNT(*) FROM combatants WHERE role = 'admin') as total_admins,
    (SELECT COUNT(*) FROM combatants) as total_users,
    (SELECT COUNT(*) FROM transactions) as total_transactions,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'purchase') as total_purchases,
    (SELECT COUNT(*) FROM transactions WHERE transaction_type = 'sell') as total_sells,
    (SELECT COUNT(*) FROM transactions WHERE status = 'completed') as completed_transactions,
    (SELECT COUNT(*) FROM transactions WHERE status = 'failed') as failed_transactions,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'purchase' AND status = 'completed') as total_revenue,
    (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE transaction_type = 'sell' AND status = 'completed') as total_payouts,
    (SELECT COUNT(*) FROM gear_items) as total_gear_items,
    (SELECT COUNT(*) FROM inventory) as total_inventory_items,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM achievements) as total_achievements;

-- ============================================
-- SAMPLE TRANSACTIONS
-- ============================================

INSERT INTO transactions (combatant_id, combatant_name, combatant_role, transaction_type, gear_id, gear_name, amount, credits_before, credits_after, status) VALUES
(1, 'Vantablack', 'hero', 'purchase', 'h4-01', 'Psi-Shield Helmet', 5000, 8200, 3200, 'completed'),
(2, 'Solar Flare', 'hero', 'purchase', 'h2-01', 'Tactical Command Helmet', 1200, 4500, 3300, 'completed'),
(4, 'Shadow Syndicate', 'villain', 'purchase', 'd4-01', 'Void Dampener', 7000, 9800, 2800, 'completed'),
(1, 'Vantablack', 'hero', 'sell', 'b3-01', 'Quantum Battery', 1000, 3200, 4200, 'completed'),
(8, 'Juan Dela Cruz', 'civilian', 'purchase', 'h1-01', 'Standard Issue Helmet', 500, 500, 0, 'completed');