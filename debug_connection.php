<?php
// ===================================================================
// DATABASE CONNECTION DEBUGGER
// Access via: newretrodawn.dev/nrdsandbox/debug_connection.php?key=debug_2024
// ===================================================================

$secretKey = $_GET['key'] ?? '';
if ($secretKey !== 'debug_2024') {
    die('Access denied. Use: debug_connection.php?key=debug_2024');
}

echo "<h2>Database Connection Debug</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#fff;} .error{color:#ff6666;} .success{color:#00ff66;} .info{color:#00d4ff;}</style>";

// Test 1: Check environment
echo "<h3>1. Environment Check</h3>";
echo "<div class='info'>Server: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</div>";
echo "<div class='info'>Environment detected as: " . (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) ? 'LOCAL' : 'PRODUCTION') . "</div>";

// Test 2: Check database config
echo "<h3>2. Database Configuration</h3>";
$config = [
    'host' => 'mysql.newretrodawn.dev',
    'username' => 'nrd_dev',
    'password' => '@NRDSandBoxAdmin',
    'database' => 'nrdsb',
    'charset' => 'utf8mb4',
    'port' => 3306
];

foreach ($config as $key => $value) {
    if ($key === 'password') {
        echo "<div class='info'>$key: " . str_repeat('*', strlen($value)) . "</div>";
    } else {
        echo "<div class='info'>$key: $value</div>";
    }
}

// Test 3: Test basic PDO connection
echo "<h3>3. PDO Connection Test</h3>";
try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    echo "<div class='info'>DSN: $dsn</div>";
    
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='success'>✅ PDO connection successful!</div>";
    
    // Test 4: Check if database exists
    echo "<h3>4. Database Existence Check</h3>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$config['database']}'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        echo "<div class='success'>✅ Database '{$config['database']}' exists</div>";
        
        // Test 5: Connect to specific database
        echo "<h3>5. Database Connection Test</h3>";
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        echo "<div class='success'>✅ Connected to database '{$config['database']}'</div>";
        
        // Test 6: Check permissions
        echo "<h3>6. Permission Check</h3>";
        $result = $pdo->query("SELECT DATABASE() as current_db, USER() as current_user, NOW() as current_time")->fetch();
        echo "<div class='info'>Current DB: " . $result['current_db'] . "</div>";
        echo "<div class='info'>Current User: " . $result['current_user'] . "</div>";
        echo "<div class='info'>Server Time: " . $result['current_time'] . "</div>";
        
    } else {
        echo "<div class='error'>❌ Database '{$config['database']}' does not exist!</div>";
        echo "<div class='info'>Available databases:</div>";
        $stmt = $pdo->query("SHOW DATABASES");
        while ($row = $stmt->fetch()) {
            echo "<div class='info'>- " . $row['Database'] . "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Error Code: " . $e->getCode() . "</div>";
    
    // Common error solutions
    echo "<h3>Possible Solutions:</h3>";
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "<div class='info'>• Check username/password in DreamHost panel</div>";
        echo "<div class='info'>• Verify user has permissions on database</div>";
        echo "<div class='info'>• Ensure database user is created correctly</div>";
    }
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "<div class='info'>• Create database 'nrdsb' in DreamHost panel</div>";
        echo "<div class='info'>• Check database name spelling</div>";
    }
    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "<div class='info'>• Check hostname 'mysql.newretrodawn.dev'</div>";
        echo "<div class='info'>• Verify port 3306 is correct</div>";
    }
}

echo "<h3>7. Next Steps</h3>";
echo "<div class='info'>1. Fix any issues shown above</div>";
echo "<div class='info'>2. Re-run setup_production.php</div>";
echo "<div class='info'>3. Delete this debug file when done</div>";
?>