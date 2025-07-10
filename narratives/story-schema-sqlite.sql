-- NRDSandbox Story System Database Schema - SQLite Version
-- Add these tables to your existing SQLite database

-- Story metadata table
CREATE TABLE IF NOT EXISTS story_metadata (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    story_id TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    description TEXT,
    nodes_count INTEGER DEFAULT 0,
    version TEXT DEFAULT '1.0.0',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    is_active INTEGER DEFAULT 1
);

-- User story progress tracking
CREATE TABLE IF NOT EXISTS story_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    story_id TEXT NOT NULL,
    current_node TEXT NOT NULL,
    story_variables TEXT, -- JSON as TEXT in SQLite
    completed_nodes TEXT, -- JSON as TEXT in SQLite
    total_choices INTEGER DEFAULT 0,
    started_at TEXT DEFAULT CURRENT_TIMESTAMP,
    last_updated TEXT DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE (user_id, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Story choices logging (for analytics)
CREATE TABLE IF NOT EXISTS story_choices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    story_id TEXT NOT NULL,
    node_id TEXT NOT NULL,
    choice_index INTEGER NOT NULL,
    choice_text TEXT,
    session_id TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Story rewards tracking
CREATE TABLE IF NOT EXISTS story_rewards (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    story_id TEXT NOT NULL,
    reward_type TEXT NOT NULL CHECK (reward_type IN ('card', 'equipment', 'stat_boost', 'unlock', 'currency')),
    reward_data TEXT, -- JSON as TEXT in SQLite
    node_id TEXT,
    choice_index INTEGER,
    granted_at TEXT DEFAULT CURRENT_TIMESTAMP,
    claimed_at TEXT NULL,
    is_claimed INTEGER DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Story variables for global game state
CREATE TABLE IF NOT EXISTS story_variables (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    variable_name TEXT NOT NULL,
    variable_value TEXT,
    variable_type TEXT DEFAULT 'string' CHECK (variable_type IN ('string', 'number', 'boolean', 'json')),
    story_id TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE (user_id, variable_name, story_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Story sessions for tracking playthroughs
CREATE TABLE IF NOT EXISTS story_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    story_id TEXT NOT NULL,
    session_id TEXT NOT NULL,
    start_node TEXT NOT NULL,
    end_node TEXT,
    choices_made INTEGER DEFAULT 0,
    rewards_earned INTEGER DEFAULT 0,
    completion_status TEXT DEFAULT 'started' CHECK (completion_status IN ('started', 'completed', 'abandoned')),
    play_time_seconds INTEGER DEFAULT 0,
    started_at TEXT DEFAULT CURRENT_TIMESTAMP,
    completed_at TEXT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Story analytics for admin insights
CREATE TABLE IF NOT EXISTS story_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    story_id TEXT NOT NULL,
    node_id TEXT NOT NULL,
    event_type TEXT NOT NULL CHECK (event_type IN ('node_visited', 'choice_made', 'reward_earned', 'story_completed', 'story_abandoned')),
    event_data TEXT, -- JSON as TEXT in SQLite
    user_id INTEGER,
    session_id TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Story imports/exports tracking
CREATE TABLE IF NOT EXISTS story_imports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    story_id TEXT NOT NULL,
    import_file TEXT NOT NULL,
    import_type TEXT NOT NULL CHECK (import_type IN ('arrow_html', 'json', 'custom')),
    imported_by INTEGER NOT NULL,
    file_size INTEGER,
    processing_status TEXT DEFAULT 'pending' CHECK (processing_status IN ('pending', 'processing', 'completed', 'failed')),
    error_message TEXT,
    imported_at TEXT DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_story_metadata_story_id ON story_metadata(story_id);
CREATE INDEX IF NOT EXISTS idx_story_metadata_created_at ON story_metadata(created_at);
CREATE INDEX IF NOT EXISTS idx_story_metadata_is_active ON story_metadata(is_active);

CREATE INDEX IF NOT EXISTS idx_story_progress_user_id ON story_progress(user_id);
CREATE INDEX IF NOT EXISTS idx_story_progress_story_id ON story_progress(story_id);
CREATE INDEX IF NOT EXISTS idx_story_progress_last_updated ON story_progress(last_updated);
CREATE INDEX IF NOT EXISTS idx_story_progress_composite ON story_progress(user_id, story_id, last_updated);

CREATE INDEX IF NOT EXISTS idx_story_choices_user_id ON story_choices(user_id);
CREATE INDEX IF NOT EXISTS idx_story_choices_story_id ON story_choices(story_id);
CREATE INDEX IF NOT EXISTS idx_story_choices_node_id ON story_choices(node_id);
CREATE INDEX IF NOT EXISTS idx_story_choices_created_at ON story_choices(created_at);
CREATE INDEX IF NOT EXISTS idx_story_choices_composite ON story_choices(user_id, story_id, created_at);

CREATE INDEX IF NOT EXISTS idx_story_rewards_user_id ON story_rewards(user_id);
CREATE INDEX IF NOT EXISTS idx_story_rewards_story_id ON story_rewards(story_id);
CREATE INDEX IF NOT EXISTS idx_story_rewards_reward_type ON story_rewards(reward_type);
CREATE INDEX IF NOT EXISTS idx_story_rewards_granted_at ON story_rewards(granted_at);
CREATE INDEX IF NOT EXISTS idx_story_rewards_is_claimed ON story_rewards(is_claimed);
CREATE INDEX IF NOT EXISTS idx_story_rewards_composite ON story_rewards(user_id, story_id, is_claimed);

CREATE INDEX IF NOT EXISTS idx_story_variables_user_id ON story_variables(user_id);
CREATE INDEX IF NOT EXISTS idx_story_variables_variable_name ON story_variables(variable_name);
CREATE INDEX IF NOT EXISTS idx_story_variables_story_id ON story_variables(story_id);

CREATE INDEX IF NOT EXISTS idx_story_sessions_user_id ON story_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_story_sessions_story_id ON story_sessions(story_id);
CREATE INDEX IF NOT EXISTS idx_story_sessions_session_id ON story_sessions(session_id);
CREATE INDEX IF NOT EXISTS idx_story_sessions_completion_status ON story_sessions(completion_status);
CREATE INDEX IF NOT EXISTS idx_story_sessions_started_at ON story_sessions(started_at);

CREATE INDEX IF NOT EXISTS idx_story_analytics_story_id ON story_analytics(story_id);
CREATE INDEX IF NOT EXISTS idx_story_analytics_node_id ON story_analytics(node_id);
CREATE INDEX IF NOT EXISTS idx_story_analytics_event_type ON story_analytics(event_type);
CREATE INDEX IF NOT EXISTS idx_story_analytics_user_id ON story_analytics(user_id);
CREATE INDEX IF NOT EXISTS idx_story_analytics_created_at ON story_analytics(created_at);

CREATE INDEX IF NOT EXISTS idx_story_imports_story_id ON story_imports(story_id);
CREATE INDEX IF NOT EXISTS idx_story_imports_import_type ON story_imports(import_type);
CREATE INDEX IF NOT EXISTS idx_story_imports_imported_by ON story_imports(imported_by);
CREATE INDEX IF NOT EXISTS idx_story_imports_processing_status ON story_imports(processing_status);
CREATE INDEX IF NOT EXISTS idx_story_imports_imported_at ON story_imports(imported_at);

-- Create views for common queries (SQLite version)
CREATE VIEW IF NOT EXISTS story_progress_summary AS
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
        WHEN datetime(sp.last_updated) < datetime('now', '-7 days') THEN 'abandoned'
        ELSE 'active'
    END as status
FROM story_progress sp
JOIN story_metadata sm ON sp.story_id = sm.story_id
WHERE sm.is_active = 1;

CREATE VIEW IF NOT EXISTS story_analytics_summary AS
SELECT 
    story_id,
    node_id,
    event_type,
    COUNT(*) as event_count,
    COUNT(DISTINCT user_id) as unique_users,
    date(created_at) as event_date
FROM story_analytics
GROUP BY story_id, node_id, event_type, date(created_at)
ORDER BY event_date DESC, story_id, node_id;

-- Initialize default story metadata
INSERT OR REPLACE INTO story_metadata (story_id, title, description, nodes_count, version) VALUES
('welcome_story', 'Welcome to NRDSandbox', 'A brief introduction to the story system and how it integrates with card battles.', 5, '1.0.0'),
('tutorial_story', 'Combat Tutorial', 'Learn the basics of tactical card combat through an interactive story.', 8, '1.0.0'),
('first_mission', 'The First Mission', 'Your first tactical assignment in the field.', 12, '1.0.0');

-- Create triggers for analytics (SQLite version)
CREATE TRIGGER IF NOT EXISTS story_progress_analytics 
AFTER UPDATE ON story_progress
FOR EACH ROW
WHEN OLD.current_node != NEW.current_node
BEGIN
    INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
    VALUES (NEW.story_id, NEW.current_node, 'node_visited', NEW.user_id, 
            json_object('previous_node', OLD.current_node));
END;

CREATE TRIGGER IF NOT EXISTS story_choice_analytics 
AFTER INSERT ON story_choices
FOR EACH ROW
BEGIN
    INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
    VALUES (NEW.story_id, NEW.node_id, 'choice_made', NEW.user_id, 
            json_object('choice_index', NEW.choice_index, 'choice_text', NEW.choice_text));
END;

CREATE TRIGGER IF NOT EXISTS story_reward_analytics 
AFTER INSERT ON story_rewards
FOR EACH ROW
BEGIN
    INSERT INTO story_analytics (story_id, node_id, event_type, user_id, event_data)
    VALUES (NEW.story_id, NEW.node_id, 'reward_earned', NEW.user_id, 
            json_object('reward_type', NEW.reward_type, 'reward_data', NEW.reward_data));
END;

-- Update trigger for story_variables updated_at
CREATE TRIGGER IF NOT EXISTS story_variables_updated_at
AFTER UPDATE ON story_variables
FOR EACH ROW
BEGIN
    UPDATE story_variables 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;

-- Update trigger for story_metadata updated_at
CREATE TRIGGER IF NOT EXISTS story_metadata_updated_at
AFTER UPDATE ON story_metadata
FOR EACH ROW
BEGIN
    UPDATE story_metadata 
    SET updated_at = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;

-- Update trigger for story_progress last_updated
CREATE TRIGGER IF NOT EXISTS story_progress_last_updated
AFTER UPDATE ON story_progress
FOR EACH ROW
BEGIN
    UPDATE story_progress 
    SET last_updated = CURRENT_TIMESTAMP 
    WHERE id = NEW.id;
END;