<?php
// ===================================================================
// NRD SANDBOX - QUICK JSON FIX SCRIPT
// ===================================================================
// Save this as: fix-cards-json.php in your NRDSandbox root directory
// Run this once to fix the JSON corruption issue

require 'auth.php';

echo "<h1>🔧 NRD Sandbox - JSON Fix Tool</h1>";
echo "<h2>Fixing cards.json corruption...</h2>";

$cardsFile = 'data/cards.json';
$backupFile = 'data/cards.json.backup.' . date('Y-m-d-H-i-s');

// ===================================================================
// STEP 1: BACKUP EXISTING FILE
// ===================================================================
echo "<h3>1. Creating Backup</h3>";

if (file_exists($cardsFile)) {
    if (copy($cardsFile, $backupFile)) {
        echo "<p style='color: green;'>✅ Backup created: {$backupFile}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create backup</p>";
        exit;
    }
} else {
    echo "<p style='color: orange;'>⚠️ No existing cards.json file found</p>";
}

// ===================================================================
// STEP 2: FIX THE JSON STRUCTURE
// ===================================================================
echo "<h3>2. Fixing JSON Structure</h3>";

// Create the correct structure with sample cards
$correctStructure = [
    'cards' => [
        [
            'id' => 'weapon_001',
            'name' => 'Plasma Rifle',
            'cost' => 2,
            'type' => 'weapon',
            'damage' => 15,
            'description' => 'Standard issue energy weapon. Reliable and efficient.',
            'rarity' => 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ],
        [
            'id' => 'weapon_002',
            'name' => 'Ion Cannon',
            'cost' => 4,
            'type' => 'weapon',
            'damage' => 22,
            'description' => 'Heavy artillery weapon. High damage output.',
            'rarity' => 'rare',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ],
        [
            'id' => 'armor_001',
            'name' => 'Shield Array',
            'cost' => 2,
            'type' => 'armor',
            'damage' => 10,
            'description' => 'Energy barrier system. Provides solid defense.',
            'rarity' => 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ],
        [
            'id' => 'armor_002',
            'name' => 'Reactive Plating',
            'cost' => 3,
            'type' => 'armor',
            'damage' => 8,
            'description' => 'Advanced armor with damage reflection capability.',
            'rarity' => 'uncommon',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ],
        [
            'id' => 'spell_001',
            'name' => 'Lightning Bolt',
            'cost' => 3,
            'type' => 'spell',
            'damage' => 12,
            'description' => 'Instant damage spell. Quick and effective.',
            'rarity' => 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ],
        [
            'id' => 'spell_002',
            'name' => 'Shield Repair',
            'cost' => 2,
            'type' => 'spell',
            'damage' => 0,
            'description' => 'Restore mech HP and repair equipment.',
            'rarity' => 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ]
    ],
    'meta' => [
        'created' => date('Y-m-d H:i:s'),
        'version' => '1.1',
        'total_cards' => 6,
        'last_updated' => date('Y-m-d H:i:s'),
        'fixed_by_script' => true
    ]
];

// Write the corrected JSON
$json = json_encode($correctStructure, JSON_PRETTY_PRINT);
if ($json === false) {
    echo "<p style='color: red;'>❌ Failed to encode JSON</p>";
    exit;
}

if (file_put_contents($cardsFile, $json)) {
    echo "<p style='color: green;'>✅ cards.json fixed successfully!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to write fixed JSON file</p>";
    exit;
}

// ===================================================================
// STEP 3: VERIFY THE FIX
// ===================================================================
echo "<h3>3. Verifying Fix</h3>";

// Test reading the file
$content = file_get_contents($cardsFile);
$data = json_decode($content, true);

if ($data === null) {
    echo "<p style='color: red;'>❌ JSON is still invalid: " . json_last_error_msg() . "</p>";
} elseif (!isset($data['cards'])) {
    echo "<p style='color: red;'>❌ Missing 'cards' key</p>";
} elseif (!is_array($data['cards'])) {
    echo "<p style='color: red;'>❌ 'cards' is not an array</p>";
} else {
    echo "<p style='color: green;'>✅ JSON structure is correct!</p>";
    echo "<p><strong>Cards loaded:</strong> " . count($data['cards']) . "</p>";
    echo "<p><strong>File size:</strong> " . strlen($content) . " bytes</p>";
    
    // Show first card as example
    if (!empty($data['cards'])) {
        $firstCard = $data['cards'][0];
        echo "<p><strong>First card example:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($firstCard['name']) . "</li>";
        echo "<li><strong>Type:</strong> " . htmlspecialchars($firstCard['type']) . "</li>";
        echo "<li><strong>Cost:</strong> " . htmlspecialchars($firstCard['cost']) . "</li>";
        echo "<li><strong>Damage:</strong> " . htmlspecialchars($firstCard['damage']) . "</li>";
        echo "</ul>";
    }
}

// ===================================================================
// STEP 4: TEST CARD MANAGER
// ===================================================================
echo "<h3>4. Testing Card Manager</h3>";

try {
    // Test if card-manager.php is working
    if (file_exists('card-manager.php')) {
        echo "<p style='color: green;'>✅ card-manager.php exists</p>";
        
        // Test basic functionality by simulating a request
        $_POST = ['action' => 'get_all_cards'];
        
        // Capture output
        ob_start();
        include 'card-manager.php';
        $output = ob_get_clean();
        
        // Parse response
        $response = json_decode($output, true);
        if ($response && $response['success']) {
            echo "<p style='color: green;'>✅ Card Manager is working! Loaded " . count($response['data']) . " cards</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Card Manager response: " . htmlspecialchars($output) . "</p>";
        }
        
        // Clean up
        unset($_POST['action']);
        
    } else {
        echo "<p style='color: red;'>❌ card-manager.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error testing Card Manager: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===================================================================
// STEP 5: INSTRUCTIONS
// ===================================================================
echo "<h3>5. Next Steps</h3>";
echo "<div style='background: #e8f5e8; border: 1px solid #4CAF50; padding: 15px; border-radius: 5px;'>";
echo "<h4 style='color: #2E7D32; margin-top: 0;'>🎉 Fix Complete!</h4>";
echo "<p><strong>Your cards.json file has been fixed. Here's what to do next:</strong></p>";
echo "<ol>";
echo "<li>Go back to your main interface: <a href='index.php'>index.php</a></li>";
echo "<li>Click the deck to draw cards - you should now see 6 cards load</li>";
echo "<li>Test the Card Creator by clicking '🃏 Card Creator' in the navigation</li>";
echo "<li>Create a new card and verify it saves properly</li>";
echo "<li>Test equipping weapon/armor cards by clicking them in your hand</li>";
echo "</ol>";
echo "<p><strong>If you see any issues:</strong></p>";
echo "<ul>";
echo "<li>Check the debug panel (🐛 Debug button) for real-time diagnostics</li>";
echo "<li>Use the configuration dashboard: <a href='config/'>config/index.php</a></li>";
echo "<li>Your original file was backed up as: <code>{$backupFile}</code></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><strong>Quick Links:</strong></p>";
echo "<p>";
echo "<a href='index.php' style='padding: 8px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🏠 Back to Game</a>";
echo "<a href='config/' style='padding: 8px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px;'>⚙️ Config Dashboard</a>";
echo "<a href='card-manager.php' style='padding: 8px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 4px;'>🃏 Test Card Manager</a>";
echo "</p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #ffffff;
    margin: 0;
    padding: 20px;
    min-height: 100vh;
}

h1, h2, h3 {
    color: #00d4ff;
}

code {
    background: rgba(255, 255, 255, 0.1);
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

a {
    color: #00d4ff;
}

ul, ol {
    line-height: 1.6;
}
</style>