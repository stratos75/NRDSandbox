<?php
// ===================================================================
// NRD SANDBOX - JSON DEBUG & FIX TOOL
// ===================================================================
// Save this as: debug-json.php in your NRDSandbox root directory
// Run this to diagnose and fix JSON reading issues

require 'auth.php';

echo "<h1>NRD Sandbox - JSON Debug Tool</h1>";
echo "<h2>Diagnosing cards.json reading issue...</h2>";

// ===================================================================
// STEP 1: CHECK FILE LOCATIONS
// ===================================================================
echo "<h3>1. File Location Check</h3>";

$possiblePaths = [
    'data/cards.json',
    'cards.json', 
    './data/cards.json',
    './cards.json',
    __DIR__ . '/data/cards.json',
    __DIR__ . '/cards.json'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Path</th><th>Exists</th><th>Readable</th><th>Size</th></tr>";

$foundFiles = [];
foreach ($possiblePaths as $path) {
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    $size = $exists ? filesize($path) : 0;
    
    if ($exists) {
        $foundFiles[] = $path;
    }
    
    echo "<tr>";
    echo "<td>{$path}</td>";
    echo "<td>" . ($exists ? "✅ YES" : "❌ NO") . "</td>";
    echo "<td>" . ($readable ? "✅ YES" : "❌ NO") . "</td>";
    echo "<td>{$size} bytes</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Working Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Script Directory:</strong> " . __DIR__ . "</p>";

// ===================================================================
// STEP 2: ANALYZE JSON CONTENT
// ===================================================================
echo "<h3>2. JSON Content Analysis</h3>";

if (empty($foundFiles)) {
    echo "<p style='color: red;'><strong>❌ ERROR:</strong> No cards.json files found!</p>";
    echo "<p>You need to create the data/cards.json file. Here's the content:</p>";
    
    // Provide the correct JSON content
    $correctJson = '{
    "cards": [
        {
            "id": "weapon_001",
            "name": "Plasma Rifle",
            "cost": 2,
            "type": "weapon",
            "damage": 15,
            "description": "Standard issue energy weapon. Reliable and efficient.",
            "rarity": "common",
            "created_at": "2025-06-29 18:00:00",
            "created_by": "system"
        },
        {
            "id": "spell_001",
            "name": "Lightning Bolt",
            "cost": 3,
            "type": "spell",
            "damage": 12,
            "description": "Instant damage spell. Quick and effective.",
            "rarity": "common",
            "created_at": "2025-06-29 18:06:00",
            "created_by": "system"
        }
    ],
    "meta": {
        "created": "2025-06-29 18:00:00",
        "version": "1.1",
        "total_cards": 2,
        "last_updated": "2025-06-29 18:09:00"
    }
}';
    
    echo "<textarea style='width: 100%; height: 200px;'>{$correctJson}</textarea>";
    
    // Try to create the file
    if (!is_dir('data')) {
        if (mkdir('data', 0755, true)) {
            echo "<p style='color: green;'>✅ Created data/ directory</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create data/ directory</p>";
        }
    }
    
    if (file_put_contents('data/cards.json', $correctJson)) {
        echo "<p style='color: green;'>✅ Created data/cards.json with sample content</p>";
        $foundFiles[] = 'data/cards.json';
    } else {
        echo "<p style='color: red;'>❌ Failed to create data/cards.json</p>";
    }
} else {
    foreach ($foundFiles as $path) {
        echo "<h4>Analyzing: {$path}</h4>";
        
        $content = file_get_contents($path);
        echo "<p><strong>Content length:</strong> " . strlen($content) . " characters</p>";
        
        // Check for common JSON issues
        if (empty($content)) {
            echo "<p style='color: red;'>❌ File is empty!</p>";
            continue;
        }
        
        // Try to decode JSON
        $decoded = json_decode($content, true);
        $jsonError = json_last_error();
        
        if ($jsonError !== JSON_ERROR_NONE) {
            echo "<p style='color: red;'>❌ JSON Error: " . json_last_error_msg() . "</p>";
            echo "<p>First 500 characters of file:</p>";
            echo "<textarea style='width: 100%; height: 100px;'>" . htmlspecialchars(substr($content, 0, 500)) . "</textarea>";
        } else {
            echo "<p style='color: green;'>✅ JSON is valid!</p>";
            
            // Check structure
            if (isset($decoded['cards']) && is_array($decoded['cards'])) {
                echo "<p style='color: green;'>✅ Has 'cards' array with " . count($decoded['cards']) . " cards</p>";
                
                // Show first card
                if (!empty($decoded['cards'])) {
                    $firstCard = $decoded['cards'][0];
                    echo "<p><strong>First card:</strong></p>";
                    echo "<ul>";
                    foreach ($firstCard as $key => $value) {
                        echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
                    }
                    echo "</ul>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Missing or invalid 'cards' array</p>";
            }
            
            if (isset($decoded['meta'])) {
                echo "<p style='color: green;'>✅ Has metadata</p>";
            }
        }
    }
}

// ===================================================================
// STEP 3: TEST THE ACTUAL CODE FROM INDEX.PHP
// ===================================================================
echo "<h3>3. Testing Current Code Logic</h3>";

// This is the exact code from index.php
$cardLibrary = [];
$debugInfo = [];

$debugInfo[] = "Cards JSON exists: " . (file_exists('data/cards.json') ? 'YES' : 'NO');

if (file_exists('data/cards.json')) {
    $jsonContent = file_get_contents('data/cards.json');
    $cardData = json_decode($jsonContent, true);
    if ($cardData === null) {
        $debugInfo[] = "JSON ERROR: " . json_last_error_msg();
    } else {
        $cardLibrary = $cardData['cards'] ?? [];
        $debugInfo[] = "Cards loaded: " . count($cardLibrary);
    }
} else {
    $debugInfo[] = "ERROR: data/cards.json missing!";
}

echo "<p><strong>Debug Output:</strong></p>";
echo "<ul>";
foreach ($debugInfo as $info) {
    $color = strpos($info, 'ERROR') !== false ? 'red' : (strpos($info, 'YES') !== false ? 'green' : 'black');
    echo "<li style='color: {$color};'>{$info}</li>";
}
echo "</ul>";

echo "<p><strong>Result:</strong> " . count($cardLibrary) . " cards loaded into \$cardLibrary</p>";

if (!empty($cardLibrary)) {
    echo "<p style='color: green;'>✅ SUCCESS! Cards are being read correctly.</p>";
    echo "<h4>Loaded Cards:</h4>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Cost</th><th>Damage</th></tr>";
    foreach ($cardLibrary as $card) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($card['id'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($card['name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($card['type'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($card['cost'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($card['damage'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No cards loaded. There's still an issue.</p>";
}

// ===================================================================
// STEP 4: PERMISSION CHECK
// ===================================================================
echo "<h3>4. File Permissions Check</h3>";

if (file_exists('data/cards.json')) {
    $perms = fileperms('data/cards.json');
    echo "<p><strong>File permissions:</strong> " . decoct($perms & 0777) . "</p>";
    
    if (is_readable('data/cards.json')) {
        echo "<p style='color: green;'>✅ File is readable</p>";
    } else {
        echo "<p style='color: red;'>❌ File is not readable</p>";
    }
    
    if (is_writable('data/cards.json')) {
        echo "<p style='color: green;'>✅ File is writable</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ File is not writable (needed for card creator)</p>";
    }
}

// ===================================================================
// STEP 5: RECOMMENDATIONS
// ===================================================================
echo "<h3>5. Recommendations</h3>";

if (count($cardLibrary) > 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Your JSON reading is working correctly!</p>";
    echo "<p>The issue might be in how the cards are being displayed or used in the interface.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Check the hand initialization code in index.php</li>";
    echo "<li>Verify the card display logic in the HTML</li>";
    echo "<li>Test the card creator functionality</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ JSON reading is not working.</p>";
    echo "<p><strong>To fix this:</strong></p>";
    echo "<ul>";
    echo "<li>Ensure data/cards.json exists and has content</li>";
    echo "<li>Check file permissions (should be 644 or 755)</li>";
    echo "<li>Verify JSON syntax is valid</li>";
    echo "<li>Make sure the file structure matches what the code expects</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Game Interface</a></p>";
echo "<p><a href='config/index.php'>← Back to Control Dashboard</a></p>";
?>