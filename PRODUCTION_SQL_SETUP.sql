-- ===================================================================
-- NRD SANDBOX - PRODUCTION DATABASE SETUP
-- Run these commands in phpMyAdmin for your 'nrdsb' database
-- ===================================================================

-- 1. CREATE USERS TABLE
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
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CREATE USER SESSIONS TABLE
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

-- 3. CREATE USER PROFILES TABLE
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

-- 4. CREATE GAME STATS TABLE
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

-- 5. INSERT DEFAULT USERS (password: password123)
-- Using fresh bcrypt hashes that will work correctly
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, status) VALUES 
('admin', 'admin@nrdsandbox.local', '$2y$12$FF03ltox75MqZLqyLqYJo.as8pc.Vg6cDB88Qu1lA5CtpGqZZXs72', 'System Administrator', 'admin', 'active'),
('tester', 'tester@nrdsandbox.local', '$2y$12$FF03ltox75MqZLqyLqYJo.as8pc.Vg6cDB88Qu1lA5CtpGqZZXs72', 'Test User', 'tester', 'active');

-- 6. CREATE USER PROFILES FOR DEFAULT USERS
INSERT IGNORE INTO user_profiles (user_id, pilot_callsign, tutorial_completed, preferred_theme) 
SELECT id, username, TRUE, 'dark' FROM users WHERE username IN ('admin', 'tester');

-- 7. CREATE UTILITY VIEW FOR USER INFO
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

-- 8. CREATE PERFORMANCE INDEXES
CREATE INDEX IF NOT EXISTS idx_users_status_role ON users(status, role);
CREATE INDEX IF NOT EXISTS idx_sessions_user_active ON user_sessions(user_id, is_active);
CREATE INDEX IF NOT EXISTS idx_stats_user_date ON game_stats(user_id, played_at);

-- 9. CREATE CLEANUP PROCEDURE
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CleanupExpiredSessions()
BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
END //
DELIMITER ;

-- ===================================================================
-- VERIFICATION QUERIES (Run these to test)
-- ===================================================================

-- Check if tables were created
SHOW TABLES;

-- Check if users were created
SELECT id, username, role, status FROM users;

-- Check if profiles were created
SELECT user_id, pilot_callsign, preferred_theme FROM user_profiles;

-- Test the user info view
SELECT username, role, pilot_callsign FROM user_info;