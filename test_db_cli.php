<?php
// CLI Database Test
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/User.php';

try {
    echo "=== NRDSandbox Database Test ===\n";
    
    // Test 1: Database Connection
    echo "1. Testing database connection...\n";
    $db = Database::getInstance();
    $result = $db->testConnection();
    echo "   Status: " . $result['status'] . "\n";
    echo "   Message: " . $result['message'] . "\n";
    
    if ($result['status'] === 'success') {
        echo "   Environment: " . $result['environment']['type'] . "\n";
        echo "   Database: " . $result['environment']['database'] . "\n";
        
        // Test 2: Schema Initialization
        echo "\n2. Testing schema initialization...\n";
        $db->initializeSchema();
        echo "   Schema initialized successfully!\n";
        
        // Test 3: User System
        echo "\n3. Testing user system...\n";
        $userManager = new User();
        
        // Check if admin user exists
        $adminUser = $userManager->authenticate('admin', 'password123');
        if ($adminUser) {
            echo "   Admin user exists and authentication works!\n";
            echo "   User ID: " . $adminUser['id'] . "\n";
            echo "   Username: " . $adminUser['username'] . "\n";
            echo "   Display Name: " . $adminUser['display_name'] . "\n";
        } else {
            echo "   Admin user not found - this is expected for fresh install\n";
        }
        
        echo "\n✅ All database tests completed successfully!\n";
    } else {
        echo "\n❌ Database connection failed - check your MySQL setup\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}