-- ===================================================================
-- NRD SANDBOX - DATABASE SCHEMA
-- MySQL Database Structure for User Management
-- ===================================================================

-- Create database (for local development)
-- CREATE DATABASE IF NOT EXISTS nrd_sandbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE nrd_sandbox;

-- Users table for authentication and profile management
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NULL,
    role ENUM('admin', 'developer', 'tester', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    
    -- Indexes for performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions table for session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User profiles table for game-specific data
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    pilot_callsign VARCHAR(50) NULL,
    avatar_url VARCHAR(255) NULL,
    preferred_theme ENUM('dark', 'light') DEFAULT 'dark',
    audio_enabled BOOLEAN DEFAULT TRUE,
    tutorial_completed BOOLEAN DEFAULT FALSE,
    total_games_played INT DEFAULT 0,
    games_won INT DEFAULT 0,
    games_lost INT DEFAULT 0,
    favorite_cards JSON NULL,
    game_preferences JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game statistics table
CREATE TABLE IF NOT EXISTS game_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_mode ENUM('tutorial', 'practice', 'campaign') DEFAULT 'practice',
    result ENUM('win', 'loss', 'draw') NOT NULL,
    duration_seconds INT NULL,
    cards_played INT DEFAULT 0,
    damage_dealt INT DEFAULT 0,
    damage_received INT DEFAULT 0,
    game_data JSON NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_played_at (played_at),
    INDEX idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Combat statistics table for detailed performance tracking
CREATE TABLE IF NOT EXISTS combat_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NULL,
    total_damage_dealt INT DEFAULT 0,
    total_damage_taken INT DEFAULT 0,
    critical_hits INT DEFAULT 0,
    effective_hits INT DEFAULT 0,
    resisted_hits INT DEFAULT 0,
    synergies_used INT DEFAULT 0,
    status_effects_applied INT DEFAULT 0,
    max_single_hit INT DEFAULT 0,
    combats_won INT DEFAULT 0,
    combats_lost INT DEFAULT 0,
    total_combats INT DEFAULT 0,
    average_damage_per_hit DECIMAL(10,2) DEFAULT 0,
    elemental_preference VARCHAR(20) NULL,
    elements_used JSON NULL,
    preferred_synergy_type VARCHAR(20) NULL,
    win_rate DECIMAL(5,2) DEFAULT 0,
    damage_efficiency DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_damage_dealt (total_damage_dealt),
    INDEX idx_win_rate (win_rate),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- High scores table for leaderboards
CREATE TABLE IF NOT EXISTS high_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    score_type ENUM('max_damage', 'win_streak', 'total_damage', 'synergy_master', 'elemental_lord') NOT NULL,
    score_value INT NOT NULL,
    score_details JSON NULL,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_score_type (user_id, score_type),
    INDEX idx_score_type (score_type),
    INDEX idx_score_value (score_value),
    INDEX idx_achieved_at (achieved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- CARD MANAGEMENT SYSTEM TABLES
-- ===================================================================

-- Card rarities configuration table
CREATE TABLE IF NOT EXISTS card_rarities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rarity_name VARCHAR(20) NOT NULL UNIQUE,
    rarity_weight DECIMAL(5,2) NOT NULL, -- Probability weight for drawing
    max_copies_in_deck INT DEFAULT 4,
    deck_percentage DECIMAL(5,2) DEFAULT 25.00, -- % of deck that can be this rarity
    power_multiplier DECIMAL(3,2) DEFAULT 1.00, -- Base power scaling
    cost_modifier INT DEFAULT 0, -- Cost adjustment for rarity
    color_hex VARCHAR(7) DEFAULT '#FFFFFF', -- UI color
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rarity_weight (rarity_weight),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Master cards table
CREATE TABLE IF NOT EXISTS cards (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('weapon', 'armor', 'special attack', 'spell', 'creature', 'support') NOT NULL,
    element ENUM('fire', 'ice', 'poison', 'plasma', 'neutral') NOT NULL DEFAULT 'neutral',
    rarity_id INT NOT NULL,
    cost INT NOT NULL DEFAULT 0,
    damage INT DEFAULT 0,
    defense INT DEFAULT 0,
    description TEXT,
    special_effect VARCHAR(50) NULL,
    special_effect_data JSON NULL,
    image_path VARCHAR(255) NULL,
    lore TEXT NULL,
    flavor_text TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_collectible BOOLEAN DEFAULT TRUE,
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (rarity_id) REFERENCES card_rarities(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_type (type),
    INDEX idx_element (element),
    INDEX idx_rarity_id (rarity_id),
    INDEX idx_cost (cost),
    INDEX idx_is_active (is_active),
    INDEX idx_is_collectible (is_collectible),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Card quantities (how many copies exist in the "universe")
CREATE TABLE IF NOT EXISTS card_quantities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id VARCHAR(50) NOT NULL,
    quantity_available INT DEFAULT 1, -- How many copies can be drawn
    quantity_in_circulation INT DEFAULT 0, -- How many are currently "owned"
    base_drop_rate DECIMAL(8,4) DEFAULT 1.0000, -- Individual card drop rate modifier
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_card_quantity (card_id),
    INDEX idx_quantity_available (quantity_available),
    INDEX idx_drop_rate (base_drop_rate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deck templates for different game modes
CREATE TABLE IF NOT EXISTS deck_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    deck_size INT DEFAULT 20,
    hand_size INT DEFAULT 5,
    template_type ENUM('starter', 'balanced', 'aggressive', 'control', 'custom') DEFAULT 'custom',
    rarity_distribution JSON NULL, -- {"common": 60, "uncommon": 25, "rare": 12, "epic": 3}
    required_elements JSON NULL, -- Required element distribution
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by_user_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_template_type (template_type),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User card collections (what cards each user has unlocked/owns)
CREATE TABLE IF NOT EXISTS user_card_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_id VARCHAR(50) NOT NULL,
    quantity_owned INT DEFAULT 0,
    times_used INT DEFAULT 0,
    times_won_with INT DEFAULT 0,
    first_acquired TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_card (user_id, card_id),
    INDEX idx_user_id (user_id),
    INDEX idx_card_id (card_id),
    INDEX idx_quantity_owned (quantity_owned),
    INDEX idx_times_used (times_used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- SEED DATA FOR CARD MANAGEMENT SYSTEM
-- ===================================================================

-- Insert card rarities with proper distribution weights
INSERT IGNORE INTO card_rarities (id, rarity_name, rarity_weight, max_copies_in_deck, deck_percentage, power_multiplier, cost_modifier, color_hex, display_order, is_active) VALUES
(1, 'common', 60.00, 6, 60.00, 1.00, 0, '#9CA3AF', 1, TRUE),
(2, 'uncommon', 25.00, 4, 25.00, 1.15, 1, '#10B981', 2, TRUE),
(3, 'rare', 12.00, 3, 12.00, 1.30, 2, '#3B82F6', 3, TRUE),
(4, 'epic', 2.50, 2, 2.50, 1.50, 3, '#8B5CF6', 4, TRUE),
(5, 'legendary', 0.50, 1, 0.50, 2.00, 5, '#F59E0B', 5, TRUE);

-- Create default balanced deck template
INSERT IGNORE INTO deck_templates (id, name, description, deck_size, hand_size, template_type, rarity_distribution, required_elements, is_default, is_active) VALUES
(1, 'Balanced Starter Deck', 'Well-balanced deck with proper rarity distribution', 20, 5, 'starter', 
'{"common": 60, "uncommon": 25, "rare": 12, "epic": 3}', 
'{"fire": 25, "ice": 25, "poison": 25, "plasma": 25}', TRUE, TRUE);

-- Insert default admin user (password: password123)
-- Note: This hash is for 'password123' using PASSWORD_DEFAULT
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, status) VALUES 
('admin', 'admin@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active'),
('tester', 'tester@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'tester', 'active');

-- Migrate existing cards from JSON with rebalanced rarities
INSERT IGNORE INTO cards (id, name, type, element, rarity_id, cost, damage, defense, description, special_effect, special_effect_data, image_path, is_active, is_collectible) VALUES
-- Common weapons (basic starter weapons)
('weapon_001', 'Venomroot Lance', 'weapon', 'poison', 1, 3, 18, 0, 'A spear crafted from alien vines, dripping with corrosive toxins. High poison damage over time. Special: Critical hits apply double poison stacks.', 'critical_poison_stack', NULL, 'data/images/weapon_001.png', TRUE, TRUE),
('weapon_002', 'Cryoshard Rifle', 'weapon', 'ice', 1, 4, 20, 0, 'Fires ultra-cooled crystal shards that freeze targets on impact. Slows enemy actions. Special: Each hit has 30% chance to freeze enemy for 1 turn.', 'freeze_chance', NULL, 'data/images/weapon_002.png', TRUE, TRUE),
('weapon_003', 'Incinerator Gauntlets', 'weapon', 'fire', 1, 5, 24, 0, 'Mech-mounted flame gauntlets, ideal for close-quarters devastation. Ignites enemies. Special: Burn effects spread to nearby targets.', 'burn_spread', NULL, 'data/images/weapon_003.png', TRUE, TRUE),

-- Common armor (basic starter armor)
('armor_001', 'Spore Guard Plating', 'armor', 'poison', 1, 3, 0, 10, 'Bio-adaptive armor that neutralizes toxins. Reduces poison damage with minor self-repair. Special: Regenerates 2 HP per turn when poisoned.', 'poison_regeneration', NULL, 'data/images/armor_001.png', TRUE, TRUE),
('armor_002', 'Cryo Insulation Shell', 'armor', 'ice', 1, 4, 0, 12, 'High-density layers that prevent freezing and maintain internal heat. Strong cold resistance.', NULL, NULL, 'data/images/armor_002.png', TRUE, TRUE),
('armor_003', 'Thermal Diffusion Harness', 'armor', 'fire', 1, 5, 0, 15, 'Channels heat away from vital systems. Major fire resistance, boosts speed when overheated.', NULL, NULL, 'data/images/armor_003.png', TRUE, TRUE),

-- Common special attacks
('special_001', 'Neuro-Shock Pulse', 'special attack', 'plasma', 1, 2, 18, 0, 'Emits a targeted psychic shockwave. Deals bonus damage to poison enemies.', NULL, NULL, 'data/images/special_001.png', TRUE, TRUE),
('special_002', 'Thermal Overcharge Beam', 'special attack', 'fire', 1, 3, 20, 0, 'Fires a superheated focused energy beam. Deals bonus damage to cold enemies.', NULL, NULL, 'data/images/special_002.png', TRUE, TRUE),
('special_003', 'Cryo Lockdown Grenade', 'special attack', 'ice', 1, 3, 20, 0, 'Deploys an icy explosive that temporarily immobilizes foes. Deals bonus damage to fire enemies.', NULL, NULL, 'data/images/special_003.png', TRUE, TRUE),

-- Uncommon enemy cards
('enemy_weapon_001', 'Rotvine Reaper Blade', 'weapon', 'poison', 2, 4, 18, 0, 'A savage sword fused with forest toxins and alien vines. Inflicts heavy poison damage over time.', NULL, NULL, 'data/images/enemy_weapon_001.png', TRUE, TRUE),
('enemy_armor_001', 'Chitin Husk Armor', 'armor', 'poison', 2, 5, 0, 16, 'Bio-organic exoskeletal armor grown from mutated forest beetle shells. Strong resistance to poison and physical damage.', NULL, NULL, 'data/images/enemy_armor_001.png', TRUE, TRUE),
('enemy_special_001', 'Toxic Spores Burst', 'special attack', 'poison', 2, 3, 20, 0, 'Releases a cloud of virulent spores, reducing enemy defense and inflicting poison over time.', NULL, NULL, 'data/images/enemy_special_001.png', TRUE, TRUE),

-- Rare advanced weapons 
('special_004', 'Quantum Phase Cutter', 'special attack', 'plasma', 3, 4, 24, 0, 'A blade that slips between energy states. Deals bonus damage to plasma enemies.', NULL, NULL, 'data/images/special_004.png', TRUE, TRUE),
('enemy_weapon_002', 'Venomroot Sniper', 'weapon', 'poison', 3, 5, 22, 0, 'A long-range rifle wrapped in alien vines, firing toxin-laced rounds that weaken targets.', NULL, NULL, 'data/images/enemy_weapon_002.png', TRUE, TRUE),

-- Epic legendary equipment
('weapon_004', 'Arc Disruptor Blade', 'weapon', 'plasma', 4, 6, 26, 0, 'A pulsating plasma sword that destabilizes enemy defenses. Pierces shields. Special: Ignores all shields and armor bonuses.', 'shield_pierce', NULL, 'data/images/weapon_004.png', TRUE, TRUE),
('armor_004', 'Phase Conductor Barrier', 'armor', 'plasma', 4, 6, 0, 18, 'Reactive plasma layer that absorbs high-energy attacks. Occasional energy shield activation. Special: 25% chance to reflect damage back to attacker.', 'damage_reflection', NULL, 'data/images/armor_004.png', TRUE, TRUE);

-- Set card quantities for balanced gameplay
INSERT IGNORE INTO card_quantities (card_id, quantity_available, quantity_in_circulation, base_drop_rate) 
SELECT id, 
    CASE 
        WHEN rarity_id = 1 THEN 6  -- Common: 6 copies available
        WHEN rarity_id = 2 THEN 4  -- Uncommon: 4 copies available  
        WHEN rarity_id = 3 THEN 3  -- Rare: 3 copies available
        WHEN rarity_id = 4 THEN 2  -- Epic: 2 copies available
        WHEN rarity_id = 5 THEN 1  -- Legendary: 1 copy available
        ELSE 1
    END as quantity_available,
    0, -- No cards in circulation initially
    1.0000 -- Base drop rate
FROM cards;

-- Create corresponding user profiles
INSERT IGNORE INTO user_profiles (user_id, pilot_callsign, tutorial_completed, preferred_theme) 
SELECT id, username, TRUE, 'dark' FROM users WHERE username IN ('admin', 'tester');

-- ===================================================================
-- UTILITY VIEWS FOR EASY DATA ACCESS
-- ===================================================================

-- Complete user information view
CREATE OR REPLACE VIEW user_info AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.display_name,
    u.role,
    u.status,
    u.created_at,
    u.last_login,
    u.login_count,
    p.pilot_callsign,
    p.preferred_theme,
    p.audio_enabled,
    p.tutorial_completed,
    p.total_games_played,
    p.games_won,
    p.games_lost,
    CASE 
        WHEN p.total_games_played > 0 
        THEN ROUND((p.games_won / p.total_games_played) * 100, 2)
        ELSE 0 
    END as win_percentage
FROM users u
LEFT JOIN user_profiles p ON u.id = p.user_id;

-- ===================================================================
-- CLEANUP PROCEDURES
-- ===================================================================

-- Procedure to clean up expired sessions
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredSessions()
BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
END //
DELIMITER ;

-- ===================================================================
-- INDEXES FOR PERFORMANCE
-- ===================================================================

-- Additional composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_users_status_role ON users(status, role);
CREATE INDEX IF NOT EXISTS idx_sessions_user_active ON user_sessions(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_stats_user_date ON game_stats(user_id, played_at);

-- ===================================================================
-- SAMPLE QUERIES FOR TESTING
-- ===================================================================

/*
-- Test user authentication
SELECT id, username, password_hash, role, status 
FROM users 
WHERE username = 'admin' AND status = 'active';

-- Get user profile
SELECT * FROM user_info WHERE username = 'admin';

-- Update last login
UPDATE users SET last_login = NOW(), login_count = login_count + 1 WHERE id = 1;

-- Create user session
INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
VALUES (1, 'test_session_123', '127.0.0.1', 'Test Browser', DATE_ADD(NOW(), INTERVAL 24 HOUR));

-- Clean up sessions
CALL CleanupExpiredSessions();
*/