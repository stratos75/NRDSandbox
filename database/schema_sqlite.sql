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
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ===================================================================

-- Additional composite indexes for common queries
CREATE INDEX IF NOT EXISTS idx_users_status_role ON users(status, role);
CREATE INDEX IF NOT EXISTS idx_sessions_user_active ON user_sessions(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_stats_user_date ON game_stats(user_id, played_at);