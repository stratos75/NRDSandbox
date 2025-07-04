<?php
// Test User Authentication
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/User.php';

try {
    echo "=== Testing User Authentication System ===\n";
    
    $db = Database::getInstance();
    $userManager = new User();
    
    // Test 1: Check if users table exists and has data
    echo "1. Checking users table...\n";
    $users = $db->fetchAll("SELECT id, username, display_name, role FROM users");
    echo "   Found " . count($users) . " users:\n";
    foreach ($users as $user) {
        echo "   - ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}\n";
    }
    
    // Test 2: Try to authenticate admin user
    echo "\n2. Testing admin authentication...\n";
    $adminUser = $userManager->authenticate('admin', 'password123');
    if ($adminUser) {
        echo "   ✅ Admin authentication successful!\n";
        echo "   User ID: " . $adminUser['id'] . "\n";
        echo "   Username: " . $adminUser['username'] . "\n";
        echo "   Display Name: " . $adminUser['display_name'] . "\n";
        echo "   Role: " . $adminUser['role'] . "\n";
    } else {
        echo "   ❌ Admin authentication failed\n";
    }
    
    // Test 3: Try to authenticate tester user
    echo "\n3. Testing tester authentication...\n";
    $testerUser = $userManager->authenticate('tester', 'password123');
    if ($testerUser) {
        echo "   ✅ Tester authentication successful!\n";
        echo "   User ID: " . $testerUser['id'] . "\n";
        echo "   Username: " . $testerUser['username'] . "\n";
        echo "   Display Name: " . $testerUser['display_name'] . "\n";
        echo "   Role: " . $testerUser['role'] . "\n";
    } else {
        echo "   ❌ Tester authentication failed\n";
    }
    
    // Test 4: Check user profiles
    echo "\n4. Checking user profiles...\n";
    $profiles = $db->fetchAll("SELECT user_id, pilot_callsign, preferred_theme, tutorial_completed FROM user_profiles");
    echo "   Found " . count($profiles) . " profiles:\n";
    foreach ($profiles as $profile) {
        echo "   - User ID: {$profile['user_id']}, Callsign: {$profile['pilot_callsign']}, Theme: {$profile['preferred_theme']}\n";
    }
    
    echo "\n✅ Authentication system test completed!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}