-- ===================================================================
-- NRD SANDBOX - SQLite DATABASE SCHEMA
-- SQLite Database Structure for User Management (Local Development)
-- ===================================================================

-- Users table for authentication and profile management
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT,
    role TEXT DEFAULT 'user' CHECK (role IN ('admin', 'developer', 'tester', 'user')),
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP,
    login_count INTEGER DEFAULT 0
);

-- Create indexes for users table
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- User sessions table for session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    session_id TEXT NOT NULL UNIQUE,
    ip_address TEXT NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active INTEGER DEFAULT 1,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for user_sessions table
CREATE INDEX IF NOT EXISTS idx_sessions_session_id ON user_sessions(session_id);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_expires_at ON user_sessions(expires_at);

-- User profiles table for game-specific data
CREATE TABLE IF NOT EXISTS user_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    pilot_callsign TEXT,
    avatar_url TEXT,
    preferred_theme TEXT DEFAULT 'dark' CHECK (preferred_theme IN ('dark', 'light')),
    audio_enabled INTEGER DEFAULT 1,
    tutorial_completed INTEGER DEFAULT 0,
    total_games_played INTEGER DEFAULT 0,
    games_won INTEGER DEFAULT 0,
    games_lost INTEGER DEFAULT 0,
    favorite_cards TEXT, -- JSON as TEXT in SQLite
    game_preferences TEXT, -- JSON as TEXT in SQLite
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for user_profiles table
CREATE INDEX IF NOT EXISTS idx_profiles_user_id ON user_profiles(user_id);

-- Game statistics table
CREATE TABLE IF NOT EXISTS game_stats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_mode TEXT DEFAULT 'practice' CHECK (game_mode IN ('tutorial', 'practice', 'campaign')),
    result TEXT NOT NULL CHECK (result IN ('win', 'loss', 'draw')),
    duration_seconds INTEGER,
    cards_played INTEGER DEFAULT 0,
    damage_dealt INTEGER DEFAULT 0,
    damage_received INTEGER DEFAULT 0,
    game_data TEXT, -- JSON as TEXT in SQLite
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for game_stats table
CREATE INDEX IF NOT EXISTS idx_stats_user_id ON game_stats(user_id);
CREATE INDEX IF NOT EXISTS idx_stats_played_at ON game_stats(played_at);
CREATE INDEX IF NOT EXISTS idx_stats_result ON game_stats(result);

-- Insert default admin user (password: password123)
-- Note: This hash is for 'password123' using PASSWORD_DEFAULT
INSERT OR IGNORE INTO users (username, email, password_hash, display_name, role, status) VALUES 
('admin', 'admin@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active'),
('tester', 'tester@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'tester', 'active');

-- Create corresponding user profiles
INSERT OR IGNORE INTO user_profiles (user_id, pilot_callsign, tutorial_completed, preferred_theme) 
SELECT id, username, 1, 'dark' FROM users WHERE username IN ('admin', 'tester');

-- ===================================================================
-- UTILITY VIEWS FOR EASY DATA ACCESS
-- ===================================================================

-- Complete user information view
CREATE VIEW IF NOT EXISTS user_info AS
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
        THEN ROUND((CAST(p.games_won AS REAL) / p.total_games_played) * 100, 2)
        ELSE 0 
    END as win_percentage
FROM users u
LEFT JOIN user_profiles p ON u.id = p.user_id;

-- ===================================================================
-- CARD MANAGEMENT SYSTEM TABLES
-- ===================================================================

-- Card rarities configuration table
CREATE TABLE IF NOT EXISTS card_rarities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rarity_name TEXT NOT NULL UNIQUE,
    rarity_weight REAL NOT NULL, -- Probability weight for drawing
    max_copies_in_deck INTEGER DEFAULT 4,
    deck_percentage REAL DEFAULT 25.00, -- % of deck that can be this rarity
    power_multiplier REAL DEFAULT 1.00, -- Base power scaling
    cost_modifier INTEGER DEFAULT 0, -- Cost adjustment for rarity
    color_hex TEXT DEFAULT '#FFFFFF', -- UI color
    display_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Master cards table
CREATE TABLE IF NOT EXISTS cards (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('weapon', 'armor', 'special attack', 'spell', 'creature', 'support')),
    element TEXT NOT NULL DEFAULT 'neutral' CHECK (element IN ('fire', 'ice', 'poison', 'plasma', 'neutral')),
    rarity_id INTEGER NOT NULL,
    cost INTEGER NOT NULL DEFAULT 0,
    damage INTEGER DEFAULT 0,
    defense INTEGER DEFAULT 0,
    description TEXT,
    special_effect TEXT,
    special_effect_data TEXT, -- JSON as TEXT in SQLite
    image_path TEXT,
    lore TEXT,
    flavor_text TEXT,
    is_active INTEGER DEFAULT 1,
    is_collectible INTEGER DEFAULT 1,
    created_by_user_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (rarity_id) REFERENCES card_rarities(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Card quantities (how many copies exist in the "universe")
CREATE TABLE IF NOT EXISTS card_quantities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    card_id TEXT NOT NULL,
    quantity_available INTEGER DEFAULT 1, -- How many copies can be drawn
    quantity_in_circulation INTEGER DEFAULT 0, -- How many are currently "owned"
    base_drop_rate REAL DEFAULT 1.0000, -- Individual card drop rate modifier
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE(card_id)
);

-- Deck templates for different game modes
CREATE TABLE IF NOT EXISTS deck_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    deck_size INTEGER DEFAULT 20,
    hand_size INTEGER DEFAULT 5,
    template_type TEXT DEFAULT 'custom' CHECK (template_type IN ('starter', 'balanced', 'aggressive', 'control', 'custom')),
    rarity_distribution TEXT, -- JSON as TEXT in SQLite
    required_elements TEXT, -- JSON as TEXT in SQLite
    is_default INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_by_user_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- User card collections (what cards each user has unlocked/owns)
CREATE TABLE IF NOT EXISTS user_card_collections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    card_id TEXT NOT NULL,
    quantity_owned INTEGER DEFAULT 0,
    times_used INTEGER DEFAULT 0,
    times_won_with INTEGER DEFAULT 0,
    first_acquired TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE,
    UNIQUE(user_id, card_id)
);

-- ===================================================================
-- SEED DATA FOR CARD MANAGEMENT SYSTEM
-- ===================================================================

-- Insert card rarities with proper distribution weights
INSERT OR IGNORE INTO card_rarities (id, rarity_name, rarity_weight, max_copies_in_deck, deck_percentage, power_multiplier, cost_modifier, color_hex, display_order, is_active) VALUES
(1, 'common', 60.00, 6, 60.00, 1.00, 0, '#9CA3AF', 1, 1),
(2, 'uncommon', 25.00, 4, 25.00, 1.15, 1, '#10B981', 2, 1),
(3, 'rare', 12.00, 3, 12.00, 1.30, 2, '#3B82F6', 3, 1),
(4, 'epic', 2.50, 2, 2.50, 1.50, 3, '#8B5CF6', 4, 1),
(5, 'legendary', 0.50, 1, 0.50, 2.00, 5, '#F59E0B', 5, 1);

-- Create default balanced deck template
INSERT OR IGNORE INTO deck_templates (id, name, description, deck_size, hand_size, template_type, rarity_distribution, required_elements, is_default, is_active) VALUES
(1, 'Balanced Starter Deck', 'Well-balanced deck with proper rarity distribution', 20, 5, 'starter', 
'{"common": 60, "uncommon": 25, "rare": 12, "epic": 3}', 
'{"fire": 25, "ice": 25, "poison": 25, "plasma": 25}', 1, 1);

-- Migrate existing cards from JSON with rebalanced rarities
INSERT OR IGNORE INTO cards (id, name, type, element, rarity_id, cost, damage, defense, description, special_effect, special_effect_data, image_path, is_active, is_collectible) VALUES
-- Common weapons (basic starter weapons)
('weapon_001', 'Venomroot Lance', 'weapon', 'poison', 1, 3, 18, 0, 'A spear crafted from alien vines, dripping with corrosive toxins. High poison damage over time. Special: Critical hits apply double poison stacks.', 'critical_poison_stack', NULL, 'data/images/weapon_001.png', 1, 1),
('weapon_002', 'Cryoshard Rifle', 'weapon', 'ice', 1, 4, 20, 0, 'Fires ultra-cooled crystal shards that freeze targets on impact. Slows enemy actions. Special: Each hit has 30% chance to freeze enemy for 1 turn.', 'freeze_chance', NULL, 'data/images/weapon_002.png', 1, 1),
('weapon_003', 'Incinerator Gauntlets', 'weapon', 'fire', 1, 5, 24, 0, 'Mech-mounted flame gauntlets, ideal for close-quarters devastation. Ignites enemies. Special: Burn effects spread to nearby targets.', 'burn_spread', NULL, 'data/images/weapon_003.png', 1, 1),

-- Common armor (basic starter armor)
('armor_001', 'Spore Guard Plating', 'armor', 'poison', 1, 3, 0, 10, 'Bio-adaptive armor that neutralizes toxins. Reduces poison damage with minor self-repair. Special: Regenerates 2 HP per turn when poisoned.', 'poison_regeneration', NULL, 'data/images/armor_001.png', 1, 1),
('armor_002', 'Cryo Insulation Shell', 'armor', 'ice', 1, 4, 0, 12, 'High-density layers that prevent freezing and maintain internal heat. Strong cold resistance.', NULL, NULL, 'data/images/armor_002.png', 1, 1),
('armor_003', 'Thermal Diffusion Harness', 'armor', 'fire', 1, 5, 0, 15, 'Channels heat away from vital systems. Major fire resistance, boosts speed when overheated.', NULL, NULL, 'data/images/armor_003.png', 1, 1),

-- Common special attacks
('special_001', 'Neuro-Shock Pulse', 'special attack', 'plasma', 1, 2, 18, 0, 'Emits a targeted psychic shockwave. Deals bonus damage to poison enemies.', NULL, NULL, 'data/images/special_001.png', 1, 1),
('special_002', 'Thermal Overcharge Beam', 'special attack', 'fire', 1, 3, 20, 0, 'Fires a superheated focused energy beam. Deals bonus damage to cold enemies.', NULL, NULL, 'data/images/special_002.png', 1, 1),
('special_003', 'Cryo Lockdown Grenade', 'special attack', 'ice', 1, 3, 20, 0, 'Deploys an icy explosive that temporarily immobilizes foes. Deals bonus damage to fire enemies.', NULL, NULL, 'data/images/special_003.png', 1, 1),

-- Uncommon enemy cards
('enemy_weapon_001', 'Rotvine Reaper Blade', 'weapon', 'poison', 2, 4, 18, 0, 'A savage sword fused with forest toxins and alien vines. Inflicts heavy poison damage over time.', NULL, NULL, 'data/images/enemy_weapon_001.png', 1, 1),
('enemy_armor_001', 'Chitin Husk Armor', 'armor', 'poison', 2, 5, 0, 16, 'Bio-organic exoskeletal armor grown from mutated forest beetle shells. Strong resistance to poison and physical damage.', NULL, NULL, 'data/images/enemy_armor_001.png', 1, 1),
('enemy_special_001', 'Toxic Spores Burst', 'special attack', 'poison', 2, 3, 20, 0, 'Releases a cloud of virulent spores, reducing enemy defense and inflicting poison over time.', NULL, NULL, 'data/images/enemy_special_001.png', 1, 1),

-- Rare advanced weapons 
('special_004', 'Quantum Phase Cutter', 'special attack', 'plasma', 3, 4, 24, 0, 'A blade that slips between energy states. Deals bonus damage to plasma enemies.', NULL, NULL, 'data/images/special_004.png', 1, 1),
('enemy_weapon_002', 'Venomroot Sniper', 'weapon', 'poison', 3, 5, 22, 0, 'A long-range rifle wrapped in alien vines, firing toxin-laced rounds that weaken targets.', NULL, NULL, 'data/images/enemy_weapon_002.png', 1, 1),

-- Epic legendary equipment
('weapon_004', 'Arc Disruptor Blade', 'weapon', 'plasma', 4, 6, 26, 0, 'A pulsating plasma sword that destabilizes enemy defenses. Pierces shields. Special: Ignores all shields and armor bonuses.', 'shield_pierce', NULL, 'data/images/weapon_004.png', 1, 1),
('armor_004', 'Phase Conductor Barrier', 'armor', 'plasma', 4, 6, 0, 18, 'Reactive plasma layer that absorbs high-energy attacks. Occasional energy shield activation. Special: 25% chance to reflect damage back to attacker.', 'damage_reflection', NULL, 'data/images/armor_004.png', 1, 1);

-- Set card quantities for balanced gameplay
INSERT OR IGNORE INTO card_quantities (card_id, quantity_available, quantity_in_circulation, base_drop_rate) 
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

-- ===================================================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ===================================================================

-- Additional composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_users_status_role ON users(status, role);
CREATE INDEX IF NOT EXISTS idx_sessions_user_active ON user_sessions(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_stats_user_date ON game_stats(user_id, played_at);

-- Card management indexes
CREATE INDEX IF NOT EXISTS idx_cards_type ON cards(type);
CREATE INDEX IF NOT EXISTS idx_cards_element ON cards(element);
CREATE INDEX IF NOT EXISTS idx_cards_rarity ON cards(rarity_id);
CREATE INDEX IF NOT EXISTS idx_card_quantities_available ON card_quantities(quantity_available);
CREATE INDEX IF NOT EXISTS idx_card_rarities_weight ON card_rarities(rarity_weight);
CREATE INDEX IF NOT EXISTS idx_card_rarities_active ON card_rarities(is_active);