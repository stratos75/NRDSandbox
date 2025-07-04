<?php
// Debug Login Issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Debug Login System ===\n";

try {
    // Test 1: Include User class
    echo "1. Testing User class inclusion...\n";
    require_once __DIR__ . '/database/User.php';
    echo "   ✅ User class loaded successfully\n";
    
    // Test 2: Create User instance
    echo "2. Testing User instance creation...\n";
    $userManager = new User();
    echo "   ✅ User instance created successfully\n";
    
    // Test 3: Test authentication
    echo "3. Testing authentication...\n";
    $user = $userManager->authenticate('admin', 'password123');
    if ($user) {
        echo "   ✅ Authentication successful!\n";
        echo "   User data: " . json_encode($user) . "\n";
        
        // Test 4: Test session creation
        echo "4. Testing session creation...\n";
        $sessionId = $userManager->createSession(
            $user['id'], 
            '127.0.0.1',
            'Test User Agent'
        );
        echo "   ✅ Session created: $sessionId\n";
        
    } else {
        echo "   ❌ Authentication failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}