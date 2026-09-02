-- ============================================
-- AEGIS & ANARCHY DATABASE SCHEMA
-- ============================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS loadouts;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS gear_items;
DROP TABLE IF EXISTS combatants;

-- ============================================
-- COMBATANTS TABLE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- GEAR ITEMS TABLE
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- INVENTORY TABLE (Many-to-Many relationship)
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
-- LOADOUTS TABLE (Current active loadout per combatant)
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
-- SEED DATA - COMBATANTS
-- ============================================
INSERT INTO combatants (name, bio_capacity_max, base_recovery, base_risk, credits, faction, clearance_level) VALUES
-- Heroes
('Vantablack', 1400, 3, 12, 8200, 'hero', 3),
('Solar Flare', 1200, 5, 8, 4500, 'hero', 2),
('Steel Guardian', 1800, 2, 15, 3000, 'hero', 4),

-- Villains
('Shadow Syndicate', 1100, 4, 20, 9800, 'villain', 3),
('Crimson Wraith', 1500, 1, 25, 6700, 'villain', 4),
('Neuro Hack', 900, 6, 10, 2500, 'villain', 2);

-- ============================================
-- SEED DATA - GEAR ITEMS (5 Energy Tiers)
-- ============================================

-- Tier 1: Basic/Starter Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('h1-01', 'helmet', 'armory', 'Standard Issue Helmet', 500, 50, 1, -2, 1),
('c1-01', 'core', 'armory', 'Basic Core Unit', 800, 100, 2, -3, 1),
('d1-01', 'dampener', 'armory', 'Energy Dampener V1', 600, 30, 3, -5, 1),
('g1-01', 'gauntlets', 'armory', 'Starter Gauntlets', 400, 40, 1, -1, 1),
('b1-01', 'battery', 'armory', 'Standard Battery', 300, 60, 0, 0, 1);

-- Tier 2: Enhanced Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('h2-01', 'helmet', 'armory', 'Tactical Command Helmet', 1200, 120, 2, -5, 2),
('c2-01', 'core', 'armory', 'Enhanced Core Unit', 2000, 250, 4, -8, 2),
('d2-01', 'dampener', 'armory', 'Energy Dampener V2', 1500, 80, 6, -12, 2),
('g2-01', 'gauntlets', 'armory', 'Combat Gauntlets MK2', 1000, 100, 2, -3, 2),
('b2-01', 'battery', 'armory', 'High-Capacity Battery', 800, 150, 1, 2, 2);

-- Tier 3: Advanced Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('h3-01', 'helmet', 'armory', 'Cerebral Interface Helmet', 2500, 200, 3, -10, 3),
('c3-01', 'core', 'armory', 'Fusion Core', 4000, 400, 5, -15, 3),
('d3-01', 'dampener', 'armory', 'Quantum Dampener', 3500, 150, 8, -20, 3),
('g3-01', 'gauntlets', 'armory', 'Energy Blade Gauntlets', 2800, 180, 3, -8, 3),
('b3-01', 'battery', 'armory', 'Quantum Battery', 2000, 250, 2, 5, 3);

-- Tier 4: Elite Gear
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('h4-01', 'helmet', 'armory', 'Psi-Shield Helmet', 5000, 350, 4, -15, 4),
('c4-01', 'core', 'armory', 'Antimatter Core', 8000, 600, 6, -25, 4),
('d4-01', 'dampener', 'armory', 'Void Dampener', 7000, 250, 10, -30, 4),
('g4-01', 'gauntlets', 'armory', 'Titan Gauntlets', 6000, 300, 4, -12, 4),
('b4-01', 'battery', 'armory', 'Infinite Battery', 5000, 400, 3, 10, 4);

-- Tier 5: Legendary/Forbidden Gear (Black Market)
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('h5-01', 'helmet', 'black-market', 'Chronos Helmet', 10000, 500, 5, -25, 5),
('c5-01', 'core', 'black-market', 'Singularity Core', 15000, 800, 8, -40, 5),
('d5-01', 'dampener', 'black-market', 'Nexus Dampener', 12000, 350, 12, -50, 5),
('g5-01', 'gauntlets', 'black-market', 'Phantom Gauntlets', 11000, 400, 5, -20, 5),
('b5-01', 'battery', 'black-market', 'Void Battery', 9000, 500, 4, 15, 5);

-- Additional Black Market Items
INSERT INTO gear_items (id, slot, source, name, price, bio_capacity, recovery_rate, risk_modifier, clearance_required) VALUES
('cor-02', 'core', 'black-market', 'Ferrox Overclock Weave', 3400, 420, -1, 22, 3),
('h5-02', 'helmet', 'black-market', 'Shadow Crown', 8500, 450, 6, -30, 4);

-- ============================================
-- SEED DATA - INVENTORY (Some pre-equipped items)
-- ============================================
-- Vantablack's inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(1, 'h3-01', TRUE),   -- Cerebral Interface Helmet
(1, 'c3-01', TRUE),   -- Fusion Core
(1, 'd2-01', TRUE),   -- Energy Dampener V2
(1, 'g3-01', TRUE),   -- Energy Blade Gauntlets
(1, 'b3-01', TRUE);   -- Quantum Battery

-- Solar Flare's inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(2, 'h2-01', TRUE),
(2, 'c2-01', TRUE),
(2, 'd1-01', TRUE),
(2, 'g2-01', TRUE),
(2, 'b2-01', TRUE);

-- Steel Guardian's inventory
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(3, 'h4-01', TRUE),
(3, 'c4-01', TRUE),
(3, 'd3-01', TRUE),
(3, 'g4-01', TRUE),
(3, 'b4-01', TRUE);

-- ============================================
-- SEED DATA - LOADOUTS (Current active loadouts)
-- ============================================
INSERT INTO loadouts (combatant_id, helmet_id, core_id, dampener_id, gauntlets_id, battery_id) VALUES
(1, 'h3-01', 'c3-01', 'd2-01', 'g3-01', 'b3-01'),
(2, 'h2-01', 'c2-01', 'd1-01', 'g2-01', 'b2-01'),
(3, 'h4-01', 'c4-01', 'd3-01', 'g4-01', 'b4-01');

-- ============================================
-- ADDITIONAL INVENTORY ITEMS (For marketplace testing)
-- ============================================
-- Give each combatant some extra gear to purchase
INSERT INTO inventory (combatant_id, gear_id, equipped) VALUES
(1, 'h4-01', FALSE),
(1, 'c5-01', FALSE),
(1, 'b5-01', FALSE),
(2, 'h3-01', FALSE),
(2, 'cor-02', FALSE),
(3, 'h5-01', FALSE);

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================
CREATE INDEX idx_gear_slot ON gear_items(slot);
CREATE INDEX idx_gear_source ON gear_items(source);
CREATE INDEX idx_inventory_combatant ON inventory(combatant_id);
CREATE INDEX idx_inventory_equipped ON inventory(equipped);

-- ============================================
-- VIEW FOR COMBATANT FULL DETAILS WITH LOADOUT
-- ============================================
CREATE OR REPLACE VIEW combatant_loadout_view AS
SELECT 
    c.id,
    c.name,
    c.bio_capacity_max,
    c.base_recovery,
    c.base_risk,
    c.credits,
    c.faction,
    c.clearance_level,
    l.helmet_id,
    l.core_id,
    l.dampener_id,
    l.gauntlets_id,
    l.battery_id,
    -- Get gear details for the loadout
    g1.name AS helmet_name,
    g1.bio_capacity AS helmet_bio_capacity,
    g1.recovery_rate AS helmet_recovery_rate,
    g1.risk_modifier AS helmet_risk_modifier,
    g2.name AS core_name,
    g2.bio_capacity AS core_bio_capacity,
    g2.recovery_rate AS core_recovery_rate,
    g2.risk_modifier AS core_risk_modifier,
    g3.name AS dampener_name,
    g3.bio_capacity AS dampener_bio_capacity,
    g3.recovery_rate AS dampener_recovery_rate,
    g3.risk_modifier AS dampener_risk_modifier,
    g4.name AS gauntlets_name,
    g4.bio_capacity AS gauntlets_bio_capacity,
    g4.recovery_rate AS gauntlets_recovery_rate,
    g4.risk_modifier AS gauntlets_risk_modifier,
    g5.name AS battery_name,
    g5.bio_capacity AS battery_bio_capacity,
    g5.recovery_rate AS battery_recovery_rate,
    g5.risk_modifier AS battery_risk_modifier
FROM combatants c
LEFT JOIN loadouts l ON c.id = l.combatant_id
LEFT JOIN gear_items g1 ON l.helmet_id = g1.id
LEFT JOIN gear_items g2 ON l.core_id = g2.id
LEFT JOIN gear_items g3 ON l.dampener_id = g3.id
LEFT JOIN gear_items g4 ON l.gauntlets_id = g4.id
LEFT JOIN gear_items g5 ON l.battery_id = g5.id;

-- ============================================
-- FUNCTION: Calculate Loadout Stats
-- ============================================
DELIMITER //
CREATE FUNCTION get_loadout_stats(combatant_id INT)
RETURNS JSON
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_bio_capacity INT DEFAULT 0;
    DECLARE total_recovery INT DEFAULT 0;
    DECLARE total_risk INT DEFAULT 0;
    
    -- Calculate sums from equipped gear
    SELECT 
        COALESCE(SUM(gi.bio_capacity), 0),
        COALESCE(SUM(gi.recovery_rate), 0),
        COALESCE(SUM(gi.risk_modifier), 0)
    INTO total_bio_capacity, total_recovery, total_risk
    FROM loadouts l
    LEFT JOIN gear_items gi ON gi.id IN (l.helmet_id, l.core_id, l.dampener_id, l.gauntlets_id, l.battery_id)
    WHERE l.combatant_id = combatant_id;
    
    RETURN JSON_OBJECT(
        'totalBioCapacity', total_bio_capacity,
        'totalRecovery', total_recovery,
        'totalRisk', total_risk
    );
END //
DELIMITER ;