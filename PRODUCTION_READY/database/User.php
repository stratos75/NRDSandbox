<?php
// ===================================================================
// NRD SANDBOX - USER AUTHENTICATION & MANAGEMENT
// Handles user authentication, registration, and profile management
// ===================================================================

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get current timestamp for database queries
     */
    private function getCurrentTimestamp() {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Get timestamp for future date
     */
    private function getFutureTimestamp($hours = 24) {
        return date('Y-m-d H:i:s', time() + ($hours * 3600));
    }
    
    // ===================================================================
    // AUTHENTICATION METHODS
    // ===================================================================
    
    /**
     * Authenticate user with username/email and password
     * @param string $identifier Username or email
     * @param string $password Plain text password
     * @return array|false User data or false on failure
     */
    public function authenticate($identifier, $password) {
        try {
            // Find user by username or email
            $sql = "SELECT id, username, email, password_hash, display_name, role, status 
                   FROM users 
                   WHERE (username = ? OR email = ?) AND status = 'active'
                   LIMIT 1";
            
            $user = $this->db->fetchOne($sql, [$identifier, $identifier]);
            
            if (!$user) {
                return false; // User not found
            }
            
            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false; // Invalid password
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Remove password hash from returned data
            unset($user['password_hash']);
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new user session
     * @param int $userId User ID
     * @param string $ipAddress Client IP address
     * @param string $userAgent Client user agent
     * @return string Session ID
     */
    public function createSession($userId, $ipAddress = '', $userAgent = '') {
        try {
            // Generate secure session ID
            $sessionId = bin2hex(random_bytes(32));
            
            // Session expires in 24 hours
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Clean up old sessions for this user
            $this->cleanupUserSessions($userId);
            
            // Insert new session
            $sql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) 
                   VALUES (?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $userId,
                $sessionId,
                $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $expiresAt
            ]);
            
            return $sessionId;
            
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
            throw new Exception("Failed to create session");
        }
    }
    
    /**
     * Validate session and return user data
     * @param string $sessionId Session ID
     * @return array|false User data or false if invalid
     */
    public function validateSession($sessionId) {
        try {
            $sql = "SELECT u.id, u.username, u.email, u.display_name, u.role, u.status,
                          s.expires_at, s.created_at as session_created
                   FROM users u
                   JOIN user_sessions s ON u.id = s.user_id
                   WHERE s.session_id = ? AND s.is_active = 1 AND s.expires_at > ?
                   AND u.status = 'active'
                   LIMIT 1";
            
            $result = $this->db->fetchOne($sql, [$sessionId, $this->getCurrentTimestamp()]);
            
            if (!$result) {
                return false; // Invalid or expired session
            }
            
            // Extend session expiration
            $this->extendSession($sessionId);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Destroy user session
     * @param string $sessionId Session ID
     * @return bool Success status
     */
    public function destroySession($sessionId) {
        try {
            $sql = "UPDATE user_sessions SET is_active = 0 WHERE session_id = ?";
            $this->db->execute($sql, [$sessionId]);
            return true;
        } catch (Exception $e) {
            error_log("Session destroy error: " . $e->getMessage());
            return false;
        }
    }
    
    // ===================================================================
    // USER MANAGEMENT METHODS
    // ===================================================================
    
    /**
     * Create new user account
     * @param array $userData User data (username, email, password, etc.)
     * @return int|false User ID or false on failure
     */
    public function createUser($userData) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Check if username or email already exists
            if ($this->userExists($userData['username'], $userData['email'])) {
                throw new Exception("Username or email already exists");
            }
            
            // Validate password strength
            if (!$this->isValidPassword($userData['password'])) {
                throw new Exception("Password does not meet security requirements");
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            // Start transaction
            $this->db->beginTransaction();
            
            try {
                // Insert user
                $sql = "INSERT INTO users (username, email, password_hash, display_name, role, status) 
                       VALUES (?, ?, ?, ?, ?, ?)";
                
                $userId = $this->db->insert($sql, [
                    $userData['username'],
                    $userData['email'],
                    $passwordHash,
                    $userData['display_name'] ?? $userData['username'],
                    $userData['role'] ?? 'user',
                    $userData['status'] ?? 'active'
                ]);
                
                // Create user profile
                $this->createUserProfile($userId, $userData);
                
                // Commit transaction
                $this->db->commit();
                
                return $userId;
                
            } catch (Exception $e) {
                $this->db->rollback();
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|false User data or false if not found
     */
    public function getUserById($userId) {
        try {
            $sql = "SELECT * FROM user_info WHERE id = ? LIMIT 1";
            return $this->db->fetchOne($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile
     * @param int $userId User ID
     * @param array $profileData Profile data to update
     * @return bool Success status
     */
    public function updateUserProfile($userId, $profileData) {
        try {
            $allowedFields = ['pilot_callsign', 'preferred_theme', 'audio_enabled', 'tutorial_completed'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($profileData[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $profileData[$field];
                }
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            $params[] = $userId; // Add user ID for WHERE clause
            
            $sql = "UPDATE user_profiles SET " . implode(', ', $updateFields) . ", updated_at = ? WHERE user_id = ?";
            // Add timestamp parameter before user_id
            array_splice($params, -1, 0, [$this->getCurrentTimestamp()]);
            
            $this->db->execute($sql, $params);
            return true;
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return false;
        }
    }
    
    // ===================================================================
    // HELPER METHODS
    // ===================================================================
    
    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = ?, login_count = login_count + 1 WHERE id = ?";
        $this->db->execute($sql, [date('Y-m-d H:i:s'), $userId]);
    }
    
    /**
     * Extend session expiration
     */
    private function extendSession($sessionId) {
        $sql = "UPDATE user_sessions SET expires_at = ? WHERE session_id = ?";
        $this->db->execute($sql, [$this->getFutureTimestamp(24), $sessionId]);
    }
    
    /**
     * Clean up old sessions for user
     */
    private function cleanupUserSessions($userId) {
        $sql = "UPDATE user_sessions SET is_active = 0 
               WHERE user_id = ? AND (expires_at < ? OR is_active = 0)";
        $this->db->execute($sql, [$userId, $this->getCurrentTimestamp()]);
    }
    
    /**
     * Check if user exists
     */
    private function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
        $result = $this->db->fetchOne($sql, [$username, $email]);
        return $result !== false;
    }
    
    /**
     * Validate password strength
     */
    private function isValidPassword($password) {
        // Minimum 8 characters, at least one letter and one number
        return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
    }
    
    /**
     * Create user profile
     */
    private function createUserProfile($userId, $userData) {
        $sql = "INSERT INTO user_profiles (user_id, pilot_callsign, preferred_theme, audio_enabled) 
               VALUES (?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $userId,
            $userData['pilot_callsign'] ?? $userData['username'],
            $userData['preferred_theme'] ?? 'dark',
            $userData['audio_enabled'] ?? true
        ]);
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        try {
            $sql = "SELECT 
                       total_games_played,
                       games_won,
                       games_lost,
                       CASE 
                           WHEN total_games_played > 0 
                           THEN ROUND((games_won / total_games_played) * 100, 2)
                           ELSE 0 
                       END as win_percentage,
                       (SELECT COUNT(*) FROM game_stats WHERE user_id = ? AND played_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as games_this_week
                   FROM user_profiles 
                   WHERE user_id = ?";
            
            return $this->db->fetchOne($sql, [$userId, $userId]);
            
        } catch (Exception $e) {
            error_log("Get user stats error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record game result
     */
    public function recordGameResult($userId, $result, $gameData = []) {
        try {
            $this->db->beginTransaction();
            
            // Insert game stats
            $sql = "INSERT INTO game_stats (user_id, result, duration_seconds, cards_played, damage_dealt, damage_received, game_data) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $userId,
                $result,
                $gameData['duration_seconds'] ?? null,
                $gameData['cards_played'] ?? 0,
                $gameData['damage_dealt'] ?? 0,
                $gameData['damage_received'] ?? 0,
                json_encode($gameData)
            ]);
            
            // Update user profile counters
            if ($result === 'win') {
                $updateSql = "UPDATE user_profiles SET total_games_played = total_games_played + 1, games_won = games_won + 1 WHERE user_id = ?";
            } elseif ($result === 'loss') {
                $updateSql = "UPDATE user_profiles SET total_games_played = total_games_played + 1, games_lost = games_lost + 1 WHERE user_id = ?";
            } else {
                $updateSql = "UPDATE user_profiles SET total_games_played = total_games_played + 1 WHERE user_id = ?";
            }
            
            $this->db->execute($updateSql, [$userId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Record game result error: " . $e->getMessage());
            return false;
        }
    }
}
?>