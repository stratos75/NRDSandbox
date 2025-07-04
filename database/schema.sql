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

-- Insert default admin user (password: password123)
-- Note: This hash is for 'password123' using PASSWORD_DEFAULT
INSERT IGNORE INTO users (username, email, password_hash, display_name, role, status) VALUES 
('admin', 'admin@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'active'),
('tester', 'tester@nrdsandbox.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'tester', 'active');

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