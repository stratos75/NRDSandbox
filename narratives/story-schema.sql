-- NRDSandbox Story System Database Schema
-- Add these tables to your existing database

-- Story metadata table
CREATE TABLE IF NOT EXISTS story_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    nodes_count INT DEFAULT 0,
    version VARCHAR(50) DEFAULT '1.0.0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_story_id (story_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_active (is_active)
);

-- User story progress tracking
CREATE TABLE IF NOT EXISTS story_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    story_id VARCHAR(255) NOT NULL,
    current_node VARCHAR(255) NOT NULL,
    story_variables JSON,
    completed_nodes JSON,
    total_choices INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_story (user_id, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_story_id (story_id),
    INDEX idx_last_updated (last_updated)
);

-- Story choices logging (for analytics)
CREATE TABLE IF NOT EXISTS story_choices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    story_id VARCHAR(255) NOT NULL,
    node_id VARCHAR(255) NOT NULL,
    choice_index INT NOT NULL,
    choice_text TEXT,
    session_id VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_story_id (story_id),
    INDEX idx_node_id (node_id),
    INDEX idx_created_at (created_at)
);

-- Story rewards tracking
CREATE TABLE IF NOT EXISTS story_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    story_id VARCHAR(255) NOT NULL,
    reward_type ENUM('card', 'equipment', 'stat_boost', 'unlock', 'currency') NOT NULL,
    reward_data JSON,
    node_id VARCHAR(255),
    choice_index INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed_at TIMESTAMP NULL,
    is_claimed BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_story_id (story_id),
    INDEX idx_reward_type (reward_type),
    INDEX idx_granted_at (granted_at),
    INDEX idx_is_claimed (is_claimed)
);

-- Story variables for global game state
CREATE TABLE IF NOT EXISTS story_variables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    variable_name VARCHAR(255) NOT NULL,
    variable_value TEXT,
    variable_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    story_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_variable (user_id, variable_name, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_variable_name (variable_name),
    INDEX idx_story_id (story_id)
);

-- Story sessions for tracking playthroughs
CREATE TABLE IF NOT EXISTS story_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    story_id VARCHAR(255) NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    start_node VARCHAR(255) NOT NULL,
    end_node VARCHAR(255),
    choices_made INT DEFAULT 0,
    rewards_earned INT DEFAULT 0,
    completion_status ENUM('started', 'completed', 'abandoned') DEFAULT 'started',
    play_time_seconds INT DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_story_id (story_id),
    INDEX idx_session_id (session_id),
    INDEX idx_completion_status (completion_status),
    INDEX idx_started_at (started_at)
);

-- Story analytics for admin insights
CREATE TABLE IF NOT EXISTS story_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id VARCHAR(255) NOT NULL,
    node_id VARCHAR(255) NOT NULL,
    event_type ENUM('node_visited', 'choice_made', 'reward_earned', 'story_completed', 'story_abandoned') NOT NULL,
    event_data JSON,
    user_id INT,
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_story_id (story_id),
    INDEX idx_node_id (node_id),
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- Story imports/exports tracking
CREATE TABLE IF NOT EXISTS story_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id VARCHAR(255) NOT NULL,
    import_file VARCHAR(255) NOT NULL,
    import_type ENUM('arrow_html', 'json', 'custom') NOT NULL,
    imported_by INT NOT NULL,
    file_size INT,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_story_id (story_id),
    INDEX idx_import_type (import_type),
    INDEX idx_imported_by (imported_by),
    INDEX idx_processing_status (processing_status),
    INDEX idx_imported_at (imported_at)
);

-- Initialize default story metadata
INSERT INTO story_metadata (story_id, title, description, nodes_count, version) VALUES
('welcome_story', 'Welcome to NRDSandbox', 'A brief introduction to the story system and how it integrates with card battles.', 5, '1.0.0'),
('tutorial_story', 'Combat Tutorial', 'Learn the basics of tactical card combat through an interactive story.', 8, '1.0.0'),
('first_mission', 'The First Mission', 'Your first tactical assignment in the field.', 12, '1.0.0')
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    nodes_count = VALUES(nodes_count),
    version = VALUES(version);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_story_progress_composite ON story_progress (user_id, story_id, last_updated);
CREATE INDEX IF NOT EXISTS idx_story_choices_composite ON story_choices (user_id, story_id, created_at);
CREATE INDEX IF NOT EXISTS idx_story_rewards_composite ON story_rewards (user_id, story_id, is_claimed);

-- Create views for common queries
CREATE OR REPLACE VIEW story_progress_summary AS
SELECT 
    sp.user_id,
    sp.story_id,
    sm.title as story_title,
    sp.current_node,
    sp.total_choices,
    sp.started_at,
    sp.last_updated,
    CASE 
        WHEN sp.current_node = 'end' THEN 'completed'
        WHEN sp.last_updated < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'abandoned'
        ELSE 'active'
    END as status
FROM story_progress sp
JOIN story_metadata sm ON sp.story_id = sm.story_id
WHERE sm.is_active = TRUE;

CREATE OR REPLACE VIEW story_analytics_summary AS
SELECT 
    story_id,
    node_id,
    event_type,
    COUNT(*) as event_count,
    COUNT(DISTINCT user_id) as unique_users,
    DATE(created_at) as event_date
FROM story_analytics
GROUP BY story_id, node_id, event_type, DATE(created_at)
ORDER BY event_date DESC, story_id, node_id;

-- Create triggers for analytics
DELIMITER //

CREATE TRIGGER story_progress_analytics 
AFTER UPDATE ON story_progress
FOR EACH ROW
BEGIN
    IF OLD.current_node != NEW.current_node THEN
        INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
        VALUES (NEW.story_id, NEW.current_node, 'node_visited', NEW.user_id, JSON_OBJECT('previous_node', OLD.current_node));
    END IF;
END//

CREATE TRIGGER story_choice_analytics 
AFTER INSERT ON story_choices
FOR EACH ROW
BEGIN
    INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
    VALUES (NEW.story_id, NEW.node_id, 'choice_made', NEW.user_id, JSON_OBJECT('choice_index', NEW.choice_index, 'choice_text', NEW.choice_text));
END//

CREATE TRIGGER story_reward_analytics 
AFTER INSERT ON story_rewards
FOR EACH ROW
BEGIN
    INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
    VALUES (NEW.story_id, NEW.node_id, 'reward_earned', NEW.user_id, JSON_OBJECT('reward_type', NEW.reward_type, 'reward_data', NEW.reward_data));
END//

DELIMITER ;

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_metadata TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_progress TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_choices TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_rewards TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_variables TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_sessions TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_analytics TO 'nrd_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON story_imports TO 'nrd_user'@'localhost';
-- GRANT SELECT ON story_progress_summary TO 'nrd_user'@'localhost';
-- GRANT SELECT ON story_analytics_summary TO 'nrd_user'@'localhost';