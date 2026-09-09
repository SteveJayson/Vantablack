-- ============================================
-- AEGIS & ANARCHY - COMPLETE DATABASE SCHEMA
-- Version: 2.0 (With Analytics)
-- ============================================

-- ============================================
-- DROP EXISTING TABLES (Clean Slate)
-- ============================================

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
-- 1. COMBATANTS TABLE
-- ============================================

CREATE TABLE combatants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    bio_capacity_max INT NOT NULL DEFAULT 1000,
    base_recovery INT NOT NULL DEFAULT 3,
    base_risk INT NOT NULL DEFAULT 12,
    credits INT NOT NULL DEFAULT 5000,
    faction ENUM('hero', 'villain') NOT NULL DEFAULT 'hero',
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
-- 5. COMBATANT STATS TABLE (ANALYTICS)
-- ============================================

CREATE TABLE combatant_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    combatant_id INT NOT NULL UNIQUE,
    
    -- Financial Stats
    total_credits_earned INT NOT NULL DEFAULT 0,
    total_credits_spent INT NOT NULL DEFAULT 0,
    total_gear_purchased INT NOT NULL DEFAULT 0,
    total_gear_sold INT NOT NULL DEFAULT 0,
    
    -- Mission Stats
    total_missions_completed INT NOT NULL DEFAULT 0,
    total_missions_failed INT NOT NULL DEFAULT 0,
    total_bounties_collected INT NOT NULL DEFAULT 0,
    
    -- Combat Stats
    total_battles_won INT NOT NULL DEFAULT 0,
    total_battles_lost INT NOT NULL DEFAULT 0,
    total_battles_drawn INT NOT NULL DEFAULT 0,
    longest_win_streak INT NOT NULL DEFAULT 0,
    current_win_streak INT NOT NULL DEFAULT 0,
    
    -- Gear Stats
    total_gear_owned INT NOT NULL DEFAULT 0,
    total_tier5_gear INT NOT NULL DEFAULT 0,
    total_legendary_gear INT NOT NULL DEFAULT 0,
    
    -- Time Stats
    total_hours_played INT NOT NULL DEFAULT 0,
    first_played_at TIMESTAMP NULL,
    last_played_at TIMESTAMP NULL,
    
    -- Rankings
    global_rank INT NULL,
    faction_rank INT NULL,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (combatant_id) REFERENCES combatants(id) ON DELETE CASCADE
);

-- ============================================
-- 6. BATTLE HISTORY TABLE (ANALYTICS)
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
-- 7. ACHIEVEMENTS TABLE (ANALYTICS)
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

-- ============================================
-- 8. COMBATANT ACHIEVEMENTS TABLE (ANALYTICS)
-- ============================================

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
-- 9. ACTIVITY LOG TABLE (ANALYTICS)
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
-- 10. MISSIONS TABLE (Optional)
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

-- ============================================
-- 11. MISSION COMPLETIONS TABLE (Optional)
-- ============================================

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
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_gear_slot ON gear_items(slot);
CREATE INDEX idx_gear_source ON gear_items(source);
CREATE INDEX idx_gear_tier ON gear_items(tier);
CREATE INDEX idx_inventory_combatant ON inventory(combatant_id);
CREATE INDEX idx_inventory_equipped ON inventory(equipped);
CREATE INDEX idx_stats_combatant ON combatant_stats(combatant_id);
CREATE INDEX idx_battle_combatant ON battle_history(combatant_id);
CREATE INDEX idx_battle_result ON battle_history(result);
CREATE INDEX idx_activity_combatant ON activity_log(combatant_id);
CREATE INDEX idx_activity_type ON activity_log(activity_type);
CREATE INDEX idx_achievement_category ON achievements(category);

-- ============================================
-- SEED DATA: COMBATANTS
-- ============================================

INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, clearance_level) VALUES
('Vantablack', 1400, 3, 12, 8200, 'hero', 3),
('Solar Flare', 1200, 5, 8, 4500, 'hero', 2),
('Steel Guardian', 1800, 2, 15, 3000, 'hero', 4),
('Shadow Syndicate', 1100, 4, 20, 9800, 'villain', 3),
('Crimson Wraith', 1500, 1, 25, 6700, 'villain', 4),
('Neuro Hack', 900, 6, 10, 2500, 'villain', 2);

-- ============================================
-- SEED DATA: GEAR ITEMS
-- ============================================

-- TIER 1: Basic Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
('h1-01', 'helmet', 'armory', 'Standard Issue Helmet', 500, 50, 1, -2, 1, 1, FALSE),
('c1-01', 'core', 'armory', 'Basic Core Unit', 800, 100, 2, -3, 1, 1, FALSE),
('d1-01', 'dampener', 'armory', 'Energy Dampener V1', 600, 30, 3, -5, 1, 1, FALSE),
('g1-01', 'gauntlets', 'armory', 'Starter Gauntlets', 400, 40, 1, -1, 1, 1, FALSE),
('b1-01', 'battery', 'armory', 'Standard Battery', 300, 60, 0, 0, 1, 1, FALSE);

-- TIER 2: Enhanced Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
('h2-01', 'helmet', 'armory', 'Tactical Command Helmet', 1200, 120, 2, -5, 2, 2, FALSE),
('c2-01', 'core', 'armory', 'Enhanced Core Unit', 2000, 250, 4, -8, 2, 2, FALSE),
('d2-01', 'dampener', 'armory', 'Energy Dampener V2', 1500, 80, 6, -12, 2, 2, FALSE),
('g2-01', 'gauntlets', 'armory', 'Combat Gauntlets MK2', 1000, 100, 2, -3, 2, 2, FALSE),
('b2-01', 'battery', 'armory', 'High-Capacity Battery', 800, 150, 1, 2, 2, 2, FALSE);

-- TIER 3: Advanced Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
('h3-01', 'helmet', 'armory', 'Cerebral Interface Helmet', 2500, 200, 3, -10, 3, 3, FALSE),
('c3-01', 'core', 'armory', 'Fusion Core', 4000, 400, 5, -15, 3, 3, FALSE),
('d3-01', 'dampener', 'armory', 'Quantum Dampener', 3500, 150, 8, -20, 3, 3, FALSE),
('g3-01', 'gauntlets', 'armory', 'Energy Blade Gauntlets', 2800, 180, 3, -8, 3, 3, FALSE),
('b3-01', 'battery', 'armory', 'Quantum Battery', 2000, 250, 2, 5, 3, 3, FALSE),
('cor-02', 'core', 'black-market', 'Ferrox Overclock Weave', 3400, 420, -1, 22, 3, 3, FALSE);

-- TIER 4: Elite Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
('h4-01', 'helmet', 'armory', 'Psi-Shield Helmet', 5000, 350, 4, -15, 4, 4, FALSE),
('c4-01', 'core', 'armory', 'Antimatter Core', 8000, 600, 6, -25, 4, 4, FALSE),
('d4-01', 'dampener', 'armory', 'Void Dampener', 7000, 250, 10, -30, 4, 4, FALSE),
('g4-01', 'gauntlets', 'armory', 'Titan Gauntlets', 6000, 300, 4, -12, 4, 4, FALSE),
('b4-01', 'battery', 'armory', 'Infinite Battery', 5000, 400, 3, 10, 4, 4, FALSE);

-- TIER 5: Legendary/Black Market Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required, tier, is_legendary) VALUES
('h5-01', 'helmet', 'black-market', 'Chronos Helmet', 10000, 500, 5, -25, 5, 5, TRUE),
('c5-01', 'core', 'black-market', 'Singularity Core', 15000, 800, 8, -40, 5, 5, TRUE),
('d5-01', 'dampener', 'black-market', 'Nexus Dampener', 12000, 350, 12, -50, 5, 5, TRUE),
('g5-01', 'gauntlets', 'black-market', 'Phantom Gauntlets', 11000, 400, 5, -20, 5, 5, TRUE),
('b5-01', 'battery', 'black-market', 'Void Battery', 9000, 500, 4, 15, 5, 5, TRUE),
('h5-02', 'helmet', 'black-market', 'Shadow Crown', 8500, 450, 6, -30, 4, 5, TRUE);

-- ============================================
-- SEED DATA: INVENTORY (Pre-equipped items)
-- ============================================

-- Vantablack's Inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(1, 'h3-01', TRUE),
(1, 'c3-01', TRUE),
(1, 'd2-01', TRUE),
(1, 'g3-01', TRUE),
(1, 'b3-01', TRUE),
(1, 'h4-01', FALSE),
(1, 'c5-01', FALSE);

-- Solar Flare's Inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(2, 'h2-01', TRUE),
(2, 'c2-01', TRUE),
(2, 'd1-01', TRUE),
(2, 'g2-01', TRUE),
(2, 'b2-01', TRUE);

-- Steel Guardian's Inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(3, 'h4-01', TRUE),
(3, 'c4-01', TRUE),
(3, 'd3-01', TRUE),
(3, 'g4-01', TRUE),
(3, 'b4-01', TRUE);

-- Shadow Syndicate's Inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(4, 'h5-01', TRUE),
(4, 'c5-01', TRUE),
(4, 'd4-01', TRUE),
(4, 'g5-01', TRUE),
(4, 'b5-01', TRUE);

-- ============================================
-- SEED DATA: LOADOUTS
-- ============================================

INSERT INTO loadouts (combatant_id, helmet_id, core_id, dampener_id, gauntlets_id, battery_id) VALUES
(1, 'h3-01', 'c3-01', 'd2-01', 'g3-01', 'b3-01'),
(2, 'h2-01', 'c2-01', 'd1-01', 'g2-01', 'b2-01'),
(3, 'h4-01', 'c4-01', 'd3-01', 'g4-01', 'b4-01'),
(4, 'h5-01', 'c5-01', 'd4-01', 'g5-01', 'b5-01');

-- ============================================
-- SEED DATA: COMBATANT STATS
-- ============================================

INSERT INTO combatant_stats (combatant_id, total_credits_earned, total_credits_spent, total_gear_purchased, total_battles_won, total_battles_lost, total_bounties_collected, total_gear_owned, total_tier5_gear, total_legendary_gear, first_played_at, last_played_at) VALUES
(1, 25000, 15000, 12, 156, 42, 23, 7, 1, 1, '2024-01-15 10:00:00', NOW()),
(2, 12000, 8000, 8, 89, 31, 15, 5, 0, 0, '2024-01-20 14:30:00', NOW()),
(3, 8000, 5000, 6, 45, 28, 8, 5, 0, 0, '2024-02-01 09:00:00', NOW()),
(4, 35000, 25000, 20, 203, 67, 45, 10, 3, 3, '2024-01-10 08:00:00', NOW());

-- ============================================
-- SEED DATA: ACHIEVEMENTS
-- ============================================

INSERT INTO achievements (name, description, category, points, badge_icon, unlock_condition, is_secret) VALUES
-- Combat Achievements
('First Blood', 'Win your first battle', 'combat', 10, '⚔️', '{"type": "battles_won", "threshold": 1}', FALSE),
('Warrior', 'Win 50 battles', 'combat', 25, '🗡️', '{"type": "battles_won", "threshold": 50}', FALSE),
('Champion', 'Win 100 battles', 'combat', 50, '🏆', '{"type": "battles_won", "threshold": 100}', FALSE),
('Legend', 'Win 500 battles', 'combat', 100, '👑', '{"type": "battles_won", "threshold": 500}', FALSE),
('Undefeated', 'Win 10 battles in a row', 'combat', 30, '🔥', '{"type": "win_streak", "threshold": 10}', FALSE),

-- Mission Achievements
('Mercenary', 'Complete 10 missions', 'missions', 15, '📋', '{"type": "missions_completed", "threshold": 10}', FALSE),
('Veteran', 'Complete 50 missions', 'missions', 30, '🎯', '{"type": "missions_completed", "threshold": 50}', FALSE),
('Bounty Hunter', 'Collect 100 bounties', 'missions', 40, '💰', '{"type": "bounties_collected", "threshold": 100}', FALSE),
('Legendary Hunter', 'Collect 500 bounties', 'missions', 75, '💎', '{"type": "bounties_collected", "threshold": 500}', FALSE),

-- Gear Achievements
('Collector', 'Own 20 unique gear items', 'gear', 20, '🎒', '{"type": "gear_owned", "threshold": 20}', FALSE),
('Master Collector', 'Own 50 unique gear items', 'gear', 40, '📦', '{"type": "gear_owned", "threshold": 50}', FALSE),
('Tier 5 Owner', 'Own 5 Tier 5 items', 'gear', 35, '⭐', '{"type": "tier5_gear", "threshold": 5}', FALSE),
('Legendary Collector', 'Own 10 Legendary items', 'gear', 50, '🌟', '{"type": "legendary_gear", "threshold": 10}', FALSE),
('Gear Enthusiast', 'Own at least one item from each slot', 'gear', 15, '🔧', '{"type": "complete_set", "threshold": 1}', FALSE),

-- Economy Achievements
('Millionaire', 'Earn 1,000,000 total credits', 'economy', 50, '💵', '{"type": "total_credits_earned", "threshold": 1000000}', FALSE),
('Tycoon', 'Earn 10,000,000 total credits', 'economy', 100, '🏦', '{"type": "total_credits_earned", "threshold": 10000000}', FALSE),
('Spender', 'Spend 100,000 credits', 'economy', 25, '🛍️', '{"type": "total_credits_spent", "threshold": 100000}', FALSE),
('Big Spender', 'Spend 1,000,000 credits', 'economy', 50, '💳', '{"type": "total_credits_spent", "threshold": 1000000}', FALSE),

-- Special Achievements
('Secret Agent', 'Complete a mission without taking damage', 'special', 30, '🕵️', '{"type": "mission_perfect", "threshold": 1}', TRUE),
('Iron Man', 'Win 10 battles without losing', 'special', 35, '🦾', '{"type": "win_streak", "threshold": 10}', FALSE),
('The Collector', 'Own all items from a single slot', 'special', 45, '📦', '{"type": "complete_set", "threshold": 1}', TRUE),
('Night Owl', 'Play for 100 hours total', 'special', 20, '🦉', '{"type": "total_hours", "threshold": 100}', FALSE),
('Faction Hero', 'Reach rank 1 in your faction', 'special', 50, '🏅', '{"type": "faction_rank", "threshold": 1}', FALSE);

-- ============================================
-- SEED DATA: COMBATANT ACHIEVEMENTS (Pre-unlocked)
-- ============================================

-- Vantablack's Achievements
INSERT INTO combatant_achievements (combatant_id, achievement_id, unlocked_at, progress, is_completed) VALUES
(1, 1, '2024-01-15 10:30:00', 100, TRUE),
(1, 2, '2024-02-20 15:00:00', 100, TRUE),
(1, 3, '2024-03-10 20:00:00', 100, TRUE),
(1, 6, '2024-02-01 12:00:00', 100, TRUE),
(1, 8, '2024-03-01 18:00:00', 100, TRUE),
(1, 10, '2024-03-15 14:00:00', 100, TRUE),
(1, 13, '2024-04-01 09:00:00', 100, TRUE);

-- Shadow Syndicate's Achievements
INSERT INTO combatant_achievements (combatant_id, achievement_id, unlocked_at, progress, is_completed) VALUES
(4, 1, '2024-01-10 09:00:00', 100, TRUE),
(4, 2, '2024-01-25 16:00:00', 100, TRUE),
(4, 3, '2024-02-15 20:00:00', 100, TRUE),
(4, 8, '2024-02-20 14:00:00', 100, TRUE),
(4, 12, '2024-03-01 10:00:00', 100, TRUE),
(4, 13, '2024-03-15 11:00:00', 100, TRUE);

-- ============================================
-- SEED DATA: MISSIONS
-- ============================================

INSERT INTO missions (name, description, reward, difficulty, required_clearance) VALUES
('Patrol Duty', 'Complete a routine patrol of the city', 500, 'easy', 1),
('Gear Testing', 'Test prototype gear in the field', 800, 'easy', 1),
('Intercept Delivery', 'Stop a black market shipment', 1200, 'medium', 2),
('Rescue Mission', 'Save civilians from a villain attack', 1500, 'medium', 2),
('Counter-Intelligence', 'Gather intel on enemy movements', 2000, 'hard', 3),
('Assault Base', 'Lead an assault on enemy headquarters', 3000, 'hard', 3),
('Stop Superweapon', 'Prevent activation of a superweapon', 5000, 'expert', 4),
('Dark Matter Heist', 'Steal dark matter from a villain lab', 10000, 'expert', 5);

-- ============================================
-- SEED DATA: MISSION COMPLETIONS
-- ============================================

INSERT INTO mission_completions (combatant_id, mission_id, completed_at) VALUES
(1, 1, '2024-01-20 10:00:00'),
(1, 2, '2024-01-25 14:00:00'),
(1, 3, '2024-02-01 09:00:00'),
(1, 4, '2024-02-10 16:00:00'),
(1, 5, '2024-02-20 11:00:00'),
(4, 1, '2024-01-12 08:00:00'),
(4, 2, '2024-01-18 15:00:00'),
(4, 3, '2024-01-25 12:00:00'),
(4, 4, '2024-02-05 14:00:00');

-- ============================================
-- SEED DATA: ACTIVITY LOG
-- ============================================

INSERT INTO activity_log (combatant_id, activity_type, details, credits_change, logged_at) VALUES
(1, 'login', '{"ip": "192.168.1.1"}', 0, '2024-04-15 09:00:00'),
(1, 'purchase', '{"gear_id": "h4-01", "price": 5000}', -5000, '2024-04-15 09:30:00'),
(1, 'mission', '{"mission_id": 5, "result": "completed"}', 2000, '2024-04-15 10:00:00'),
(1, 'battle', '{"opponent": "Shadow Syndicate", "result": "win"}', 500, '2024-04-15 10:30:00'),
(1, 'sell', '{"gear_id": "b2-01", "price": 400}', 400, '2024-04-15 11:00:00'),
(4, 'login', '{"ip": "192.168.1.100"}', 0, '2024-04-15 08:00:00'),
(4, 'purchase', '{"gear_id": "c5-01", "price": 15000}', -15000, '2024-04-15 08:30:00'),
(4, 'battle', '{"opponent": "Steel Guardian", "result": "win"}', 750, '2024-04-15 09:00:00');

-- ============================================
-- SEED DATA: BATTLE HISTORY (FIXED JSON)
-- ============================================

INSERT INTO battle_history (combatant_id, opponent_name, battle_type, result, loadout_used, credits_earned, damage_dealt, damage_taken, battle_duration_seconds, fought_at) VALUES
(1, 'Shadow Syndicate', 'pvp', 'win', '{"helmet":"h3-01","core":"c3-01","dampener":"d2-01","gauntlets":"g3-01","battery":"b3-01"}', 500, 350, 120, 45, '2024-04-14 14:00:00'),
(1, 'Crimson Wraith', 'pvp', 'win', '{"helmet":"h3-01","core":"c3-01","dampener":"d2-01","gauntlets":"g3-01","battery":"b3-01"}', 750, 420, 180, 52, '2024-04-13 16:30:00'),
(4, 'Steel Guardian', 'pvp', 'win', '{"helmet":"h5-01","core":"c5-01","dampener":"d4-01","gauntlets":"g5-01","battery":"b5-01"}', 1000, 500, 250, 60, '2024-04-12 20:00:00'),
(1, 'Neuro Hack', 'pvp', 'loss', '{"helmet":"h3-01","core":"c3-01","dampener":"d2-01","gauntlets":"g3-01","battery":"b3-01"}', 0, 200, 350, 30, '2024-04-11 10:00:00'),
(4, 'Solar Flare', 'pvp', 'win', '{"helmet":"h5-01","core":"c5-01","dampener":"d4-01","gauntlets":"g5-01","battery":"b5-01"}', 800, 450, 150, 40, '2024-04-10 13:00:00');

-- ============================================
-- VIEW: Combatant Full Profile
-- ============================================

CREATE OR REPLACE VIEW v_combatant_full AS
SELECT 
    c.*,
    cs.total_credits_earned,
    cs.total_credits_spent,
    cs.total_battles_won,
    cs.total_battles_lost,
    cs.total_bounties_collected,
    cs.current_win_streak,
    cs.total_gear_owned,
    (SELECT COUNT(*) FROM inventory WHERE combatant_id = c.id AND equipped = TRUE) as equipped_count,
    (SELECT COUNT(*) FROM combatant_achievements WHERE combatant_id = c.id AND is_completed = TRUE) as achievements_unlocked,
    (SELECT SUM(points) FROM combatant_achievements ca JOIN achievements a ON ca.achievement_id = a.id WHERE ca.combatant_id = c.id AND ca.is_completed = TRUE) as achievement_points
FROM combatants c
LEFT JOIN combatant_stats cs ON c.id = cs.combatant_id;

-- ============================================
-- FINAL VERIFICATION
-- ============================================

-- Show all tables
SHOW TABLES;

-- Show counts
SELECT 
    (SELECT COUNT(*) FROM combatants) as total_combatants,
    (SELECT COUNT(*) FROM gear_items) as total_gear,
    (SELECT COUNT(*) FROM achievements) as total_achievements,
    (SELECT COUNT(*) FROM missions) as total_missions,
    (SELECT COUNT(*) FROM battle_history) as total_battles;