<?php
// ===================================================================
// NRD SANDBOX - DATABASE CONNECTION MANAGER
// Handles MySQL connections for local development and DreamHost production
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
    
    // Load database configuration based on environment
    private function loadConfig() {
        // Detect environment - handle CLI mode
        $isLocalhost = (php_sapi_name() === 'cli') || 
                       (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) ||
                       (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
        
        if ($isLocalhost) {
            // Local development configuration - use SQLite for simplicity
            $this->config = [
                'driver' => 'sqlite',
                'database' => __DIR__ . '/../data/nrd_sandbox.sqlite',
                'charset' => 'utf8mb4'
            ];
        } else {
            // DreamHost production configuration
            // Note: Update these values with your actual DreamHost database credentials
            $this->config = [
                'host' => 'mysql.newretrodawn.dev',  // Your DreamHost MySQL hostname
                'username' => 'nrd_dev',            // Your DreamHost MySQL username
                'password' => '@NRDDEVAllDay57',     // Your DreamHost MySQL password
                'database' => 'nrdsb',         // Your DreamHost database name
                'charset' => 'utf8mb4',
                'port' => 3306
            ];
        }
        
        // Override with environment variables if available (for security)
        if (getenv('DB_HOST')) $this->config['host'] = getenv('DB_HOST');
        if (getenv('DB_USERNAME')) $this->config['username'] = getenv('DB_USERNAME');
        if (getenv('DB_PASSWORD')) $this->config['password'] = getenv('DB_PASSWORD');
        if (getenv('DB_DATABASE')) $this->config['database'] = getenv('DB_DATABASE');
    }
    
    // Establish database connection
    private function connect() {
        try {
            if (isset($this->config['driver']) && $this->config['driver'] === 'sqlite') {
                // SQLite connection for local development
                $dsn = "sqlite:{$this->config['database']}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                $this->connection = new PDO($dsn, null, null, $options);
            } else {
                // MySQL connection for production
                $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            }
            
            // Log successful connection in development
            if ($this->isLocalEnvironment()) {
                $host = isset($this->config['host']) ? $this->config['host'] : 'SQLite';
                error_log("Database connected successfully to: " . $host);
            }
            
        } catch (PDOException $e) {
            // Log error but don't expose database details
            error_log("Database connection failed: " . $e->getMessage());
            
            if ($this->isLocalEnvironment()) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            } else {
                throw new Exception("Database connection failed. Please try again later.");
            }
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
            throw new Exception($this->isLocalEnvironment() ? $e->getMessage() : "Database query failed");
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
    
    // Check if we're in local environment
    private function isLocalEnvironment() {
        return (php_sapi_name() === 'cli') || 
               (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])) ||
               (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
    }
    
    // Get current environment info
    public function getEnvironmentInfo() {
        return [
            'type' => $this->isLocalEnvironment() ? 'development' : 'production',
            'driver' => isset($this->config['driver']) ? $this->config['driver'] : 'mysql',
            'host' => isset($this->config['host']) ? $this->config['host'] : 'SQLite',
            'database' => $this->config['database'],
            'charset' => $this->config['charset']
        ];
    }
    
    // Test database connection
    public function testConnection() {
        try {
            if (isset($this->config['driver']) && $this->config['driver'] === 'sqlite') {
                $result = $this->fetchOne("SELECT 1 as test, datetime('now') as current_time");
            } else {
                $result = $this->fetchOne("SELECT 1 as test, NOW() as current_time");
            }
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
    
    // Initialize database schema (for local development)
    public function initializeSchema() {
        if (!$this->isLocalEnvironment()) {
            throw new Exception("Schema initialization only allowed in development environment");
        }
        
        $schemaFile = (isset($this->config['driver']) && $this->config['driver'] === 'sqlite') 
            ? __DIR__ . '/schema_sqlite.sql' 
            : __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: " . $schemaFile);
        }
        
        $schema = file_get_contents($schemaFile);
        
        // Remove comments and empty lines
        $schema = preg_replace('/^--.*$/m', '', $schema);
        $schema = preg_replace('/^\s*$/m', '', $schema);
        
        // Split by semicolons but preserve multi-line statements
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $this->getConnection()->exec($statement);
                    if ($this->isLocalEnvironment() && strpos($statement, 'CREATE TABLE') !== false) {
                        echo "Created table: " . preg_replace('/.*CREATE TABLE[^\\s]*\\s+([^\\s\\(]+).*/s', '$1', $statement) . "\n";
                    }
                } catch (PDOException $e) {
                    // Log but continue with other statements
                    error_log("Schema statement failed: " . $e->getMessage() . " | SQL: " . substr($statement, 0, 100));
                    if ($this->isLocalEnvironment()) {
                        echo "Schema error: " . $e->getMessage() . "\n";
                        echo "SQL: " . substr($statement, 0, 200) . "...\n\n";
                    }
                }
            }
        }
        
        return true;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {}
}
?>