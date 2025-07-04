<?php
// Test Password Hashing
require_once __DIR__ . '/database/Database.php';

try {
    echo "=== Testing Password Hashing ===\n";
    
    $db = Database::getInstance();
    
    // Check stored password hashes
    $users = $db->fetchAll("SELECT id, username, password_hash FROM users");
    echo "Stored password hashes:\n";
    foreach ($users as $user) {
        echo "- {$user['username']}: {$user['password_hash']}\n";
    }
    
    // Test password verification
    $testPassword = 'password123';
    $storedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "\nTesting password verification:\n";
    echo "Test password: $testPassword\n";
    echo "Stored hash: $storedHash\n";
    echo "Verification result: " . (password_verify($testPassword, $storedHash) ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Generate new hash for comparison
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    echo "New hash: $newHash\n";
    echo "New hash verification: " . (password_verify($testPassword, $newHash) ? "✅ PASS" : "❌ FAIL") . "\n";
    
    // Update database with working hash
    echo "\nUpdating database with working hash...\n";
    $db->execute("UPDATE users SET password_hash = ? WHERE username = 'admin'", [$newHash]);
    $db->execute("UPDATE users SET password_hash = ? WHERE username = 'tester'", [$newHash]);
    echo "Database updated!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}