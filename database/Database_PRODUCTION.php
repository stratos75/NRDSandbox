<?php
// ===================================================================
// NRDSANDBOX - PRODUCTION DATABASE CONNECTION MANAGER
// This version is specifically for web hosting (MySQL only)
// ===================================================================

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];
    
    // Private constructor for singleton pattern
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    // Singleton instance getter
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Load database configuration for production
    private function loadConfig() {
        // Production MySQL configuration only
        $this->config = [
            'host' => 'mysql.newretrodawn.dev',
            'username' => 'nrd_dev',
            'password' => '@NRDDEVAllDay57',
            'database' => 'nrdsb',
            'charset' => 'utf8mb4',
            'port' => 3306
        ];
        
        // Override with environment variables if available (for security)
        if (getenv('DB_HOST')) $this->config['host'] = getenv('DB_HOST');
        if (getenv('DB_USERNAME')) $this->config['username'] = getenv('DB_USERNAME');
        if (getenv('DB_PASSWORD')) $this->config['password'] = getenv('DB_PASSWORD');
        if (getenv('DB_DATABASE')) $this->config['database'] = getenv('DB_DATABASE');
    }
    
    // Establish database connection
    private function connect() {
        try {
            // MySQL connection for production
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            
        } catch (PDOException $e) {
            // Log error but don't expose database details
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    // Get database connection
    public function getConnection() {
        // Check if connection is still alive
        if ($this->connection === null) {
            $this->connect();
        }
        
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection was lost
            $this->connect();
        }
        
        return $this->connection;
    }
    
    // Execute a prepared statement
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database query failed");
        }
    }
    
    // Fetch single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }
    
    // Fetch multiple rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Insert record and return last insert ID
    public function insert($sql, $params = []) {
        $this->execute($sql, $params);
        return $this->getConnection()->lastInsertId();
    }
    
    // Update/Delete and return affected rows
    public function update($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->getConnection()->rollBack();
    }
    
    // Get current environment info
    public function getEnvironmentInfo() {
        return [
            'type' => 'production',
            'driver' => 'mysql',
            'host' => $this->config['host'],
            'database' => $this->config['database'],
            'charset' => $this->config['charset']
        ];
    }
    
    // Test database connection
    public function testConnection() {
        try {
            $result = $this->fetchOne("SELECT 1 as test, NOW() as current_time");
            return [
                'status' => 'success',
                'message' => 'Database connection successful',
                'server_time' => $result['current_time'],
                'environment' => $this->getEnvironmentInfo()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'environment' => $this->getEnvironmentInfo()
            ];
        }
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {}
}
?>