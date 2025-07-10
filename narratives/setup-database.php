<?php
/**
 * Database Setup Script for NRDSandbox Story System
 * Safely adds story tables to existing database
 */

// Include database connection
require_once '../database/Database.php';

echo "🎮 NRDSandbox Story System Database Setup\n";
echo "==========================================\n\n";

try {
    $db = Database::getInstance();
    echo "✅ Database connection established\n";
    
    // Check current database type
    $pdo = $db->getConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    echo "📊 Database type: " . strtoupper($driver) . "\n";
    
    // Load appropriate schema file
    if ($driver === 'sqlite') {
        $schemaFile = __DIR__ . '/story-schema-sqlite.sql';
        echo "📁 Using SQLite schema file\n";
    } else {
        $schemaFile = __DIR__ . '/story-schema.sql';
        echo "📁 Using MySQL schema file\n";
    }
    
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found: $schemaFile");
    }
    
    echo "\n🔧 Setting up story system tables...\n";
    
    // Read and execute schema
    $schema = file_get_contents($schemaFile);
    
    // Split schema into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            // Filter out empty statements and comments
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt);
        }
    );
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            // Clean up statement
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Extract table name for reporting
            if (preg_match('/CREATE TABLE.*?(\w+)\s*\(/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "  📋 Creating table: $tableName...";
                
                $pdo->exec($statement);
                echo " ✅\n";
                $successCount++;
                
            } else if (preg_match('/CREATE INDEX.*?(\w+)/i', $statement, $matches)) {
                $indexName = $matches[1];
                echo "  🔍 Creating index: $indexName...";
                
                $pdo->exec($statement);
                echo " ✅\n";
                $successCount++;
                
            } else if (preg_match('/CREATE VIEW.*?(\w+)/i', $statement, $matches)) {
                $viewName = $matches[1];
                echo "  👁️  Creating view: $viewName...";
                
                $pdo->exec($statement);
                echo " ✅\n";
                $successCount++;
                
            } else if (preg_match('/CREATE TRIGGER.*?(\w+)/i', $statement, $matches)) {
                $triggerName = $matches[1];
                echo "  ⚡ Creating trigger: $triggerName...";
                
                $pdo->exec($statement);
                echo " ✅\n";
                $successCount++;
                
            } else if (preg_match('/INSERT.*?INTO\s+(\w+)/i', $statement, $matches)) {
                $tableName = $matches[1];
                echo "  📝 Inserting data into: $tableName...";
                
                $pdo->exec($statement);
                echo " ✅\n";
                $successCount++;
                
            } else {
                // Execute other statements without specific reporting
                $pdo->exec($statement);
                $successCount++;
            }
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo " ⏭️  (already exists)\n";
                $skipCount++;
            } else {
                echo " ❌ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n📊 Setup Summary:\n";
    echo "  ✅ Successful operations: $successCount\n";
    echo "  ⏭️  Skipped (already exist): $skipCount\n";
    echo "  ❌ Errors: $errorCount\n";
    
    // Verify tables were created
    echo "\n🔍 Verifying story tables...\n";
    
    $storyTables = [
        'story_metadata',
        'story_progress', 
        'story_choices',
        'story_rewards',
        'story_variables',
        'story_sessions',
        'story_analytics',
        'story_imports'
    ];
    
    foreach ($storyTables as $table) {
        try {
            if ($driver === 'sqlite') {
                $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            } else {
                $result = $pdo->query("SHOW TABLES LIKE '$table'");
            }
            
            if ($result && $result->rowCount() > 0) {
                echo "  ✅ Table '$table' exists\n";
            } else {
                echo "  ❌ Table '$table' missing\n";
            }
        } catch (Exception $e) {
            echo "  ⚠️  Could not verify table '$table': " . $e->getMessage() . "\n";
        }
    }
    
    // Test basic functionality
    echo "\n🧪 Testing story system functionality...\n";
    
    try {
        // Test inserting a story metadata record
        $testStoryId = 'setup_test_' . time();
        $sql = "INSERT INTO story_metadata (story_id, title, description, nodes_count) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$testStoryId, 'Setup Test Story', 'Test story created during database setup', 1]);
        echo "  ✅ Story metadata insert test passed\n";
        
        // Test reading the record back
        $sql = "SELECT * FROM story_metadata WHERE story_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$testStoryId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['title'] === 'Setup Test Story') {
            echo "  ✅ Story metadata read test passed\n";
        } else {
            echo "  ❌ Story metadata read test failed\n";
        }
        
        // Clean up test record
        $sql = "DELETE FROM story_metadata WHERE story_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$testStoryId]);
        echo "  ✅ Test cleanup completed\n";
        
    } catch (Exception $e) {
        echo "  ❌ Functionality test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Database setup completed!\n";
    echo "\nNext steps:\n";
    echo "  1. Visit /narratives/ to access the story manager\n";
    echo "  2. Import Arrow HTML exports or use the test story\n";
    echo "  3. Integrate the story system with your main game\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  1. Check database connection settings\n";
    echo "  2. Ensure database file is writable (SQLite)\n";
    echo "  3. Verify schema file exists and is readable\n";
    exit(1);
}
?>