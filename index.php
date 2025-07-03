<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require 'auth.php';

// Initialize turn-based system session variables
if (!isset($_SESSION['currentPlayer'])) {
    $_SESSION['currentPlayer'] = 'player';
}
if (!isset($_SESSION['playerEnergy'])) {
    $_SESSION['playerEnergy'] = 5;
}
if (!isset($_SESSION['maxEnergy'])) {
    $_SESSION['maxEnergy'] = 5;
}

// Initialize AI state
if (!isset($_SESSION['enemy_hand'])) {
    $enemyHand = [];
    if (!empty($cardLibrary)) {
        $shuffledCards = $cardLibrary;
        shuffle($shuffledCards);
        $enemyHand = array_slice($shuffledCards, 0, 5); // Give enemy 5 cards
    }
    $_SESSION['enemy_hand'] = $enemyHand;
}
if (!isset($_SESSION['enemyEnergy'])) {
    $_SESSION['enemyEnergy'] = 5;
}

$gameConfig = ['hand_size' => 5, 'draw_deck_size' => 20, 'enable_companions' => true];

// Load game rules from session with defaults
$gameRules = [
    'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
    'deck_size' => $_SESSION['deck_size'] ?? 20,
    'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
    'starting_player' => $_SESSION['starting_player'] ?? 'player'
];

// ===================================================================
// IMPROVED CARD LIBRARY & HAND MANAGEMENT
// ===================================================================
// Initialize arrays and debug info
$cardLibrary = [];
$debugInfo = [];

// Define the expected path
$cardsJsonPath = 'data/cards.json';

// Enhanced file existence and readability check
$debugInfo[] = "Looking for cards file at: {$cardsJsonPath}";
$debugInfo[] = "Current working directory: " . getcwd();
$debugInfo[] = "Cards JSON exists: " . (file_exists($cardsJsonPath) ? 'YES' : 'NO');

if (file_exists($cardsJsonPath)) {
    // Check if file is readable
    if (!is_readable($cardsJsonPath)) {
        $debugInfo[] = "ERROR: File exists but is not readable. Check permissions.";
    } else {
        // Read file content
        $jsonContent = file_get_contents($cardsJsonPath);
        
        if ($jsonContent === false) {
            $debugInfo[] = "ERROR: Failed to read file content";
        } elseif (empty($jsonContent)) {
            $debugInfo[] = "ERROR: File is empty";
        } else {
            $debugInfo[] = "File read successfully. Content length: " . strlen($jsonContent) . " characters";
            
            // Try to decode JSON
            $cardData = json_decode($jsonContent, true);
            
            if ($cardData === null) {
                $jsonError = json_last_error_msg();
                $debugInfo[] = "JSON ERROR: {$jsonError}";
                
                // Show first part of content for debugging
                $preview = substr($jsonContent, 0, 100);
                $debugInfo[] = "File preview: " . htmlspecialchars($preview) . "...";
            } else {
                $debugInfo[] = "JSON decoded successfully";
                
                // Check if the expected structure exists
                if (!isset($cardData['cards'])) {
                    $debugInfo[] = "ERROR: JSON missing 'cards' key. Available keys: " . implode(', ', array_keys($cardData));
                } elseif (!is_array($cardData['cards'])) {
                    $debugInfo[] = "ERROR: 'cards' is not an array";
                } else {
                    $cardLibrary = $cardData['cards'];
                    $debugInfo[] = "SUCCESS: Loaded " . count($cardLibrary) . " cards";
                    
                    // Validate card structure
                    $validCards = 0;
                    $invalidCards = 0;
                    foreach ($cardLibrary as $index => $card) {
                        $requiredFields = ['id', 'name', 'type', 'cost'];
                        $hasAllFields = true;
                        
                        foreach ($requiredFields as $field) {
                            if (!isset($card[$field])) {
                                $hasAllFields = false;
                                break;
                            }
                        }
                        
                        if ($hasAllFields) {
                            $validCards++;
                        } else {
                            $invalidCards++;
                            $debugInfo[] = "WARNING: Card at index {$index} missing required fields";
                        }
                    }
                    
                    $debugInfo[] = "Card validation: {$validCards} valid, {$invalidCards} invalid";
                }
            }
        }
    }
} else {
    $debugInfo[] = "ERROR: {$cardsJsonPath} file does not exist";
    
    // Try to create default file
    $debugInfo[] = "Attempting to create default cards file...";
    
    if (!is_dir('data')) {
        if (mkdir('data', 0755, true)) {
            $debugInfo[] = "Created data/ directory";
        } else {
            $debugInfo[] = "ERROR: Failed to create data/ directory";
        }
    }
    
    $defaultCards = [
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
                'id' => 'spell_001',
                'name' => 'Lightning Bolt',
                'cost' => 3,
                'type' => 'spell',
                'damage' => 12,
                'description' => 'Instant damage spell. Quick and effective.',
                'rarity' => 'common',
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => 'system'
            ]
        ],
        'meta' => [
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.1',
            'total_cards' => 3,
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ];
    
    $defaultJson = json_encode($defaultCards, JSON_PRETTY_PRINT);
    
    if (file_put_contents($cardsJsonPath, $defaultJson)) {
        $debugInfo[] = "SUCCESS: Created default cards.json file";
        $cardLibrary = $defaultCards['cards'];
    } else {
        $debugInfo[] = "ERROR: Failed to create default cards.json file";
    }
}

// ===================================================================
// ENHANCED PLAYER HAND INITIALIZATION
// ===================================================================
// Initialize player hand from session (separate from library)
if (!isset($_SESSION['player_hand'])) {
    // Start with empty hand - cards will be drawn from deck
    $_SESSION['player_hand'] = [];
    $debugInfo[] = "Initialized empty player hand";
} else {
    $debugInfo[] = "Loaded existing player hand with " . count($_SESSION['player_hand']) . " cards";
}

$playerHand = $_SESSION['player_hand'] ?? [];

// Initialize basic mech data with image support
$defaultPlayerMech = [
    'HP' => 100, 
    'ATK' => 30, 
    'DEF' => 15, 
    'MAX_HP' => 100, 
    'companion' => 'Pilot-Alpha',
    'name' => 'Player Mech',
    'image' => null
];
$defaultEnemyMech = [
    'HP' => 100, 
    'ATK' => 25, 
    'DEF' => 10, 
    'MAX_HP' => 100, 
    'companion' => 'AI-Core',
    'name' => 'Enemy Mech',
    'image' => null
];

// Merge session data with defaults to ensure all keys exist
$playerMech = array_merge($defaultPlayerMech, $_SESSION['playerMech'] ?? []);
$enemyMech = array_merge($defaultEnemyMech, $_SESSION['enemyMech'] ?? []);

// ===================================================================
// EQUIPMENT STATE TRACKING
// ===================================================================
$playerEquipment = $_SESSION['playerEquipment'] ?? ['weapon' => null, 'armor' => null, 'weapon_special' => null];
$enemyEquipment = $_SESSION['enemyEquipment'] ?? ['weapon' => null, 'armor' => null];

// For enemy, pre-populate equipment (they start equipped)
if ($enemyEquipment['weapon'] === null && $enemyEquipment['armor'] === null) {
    $enemyEquipment = [
        'weapon' => ['name' => 'Ion Cannon', 'atk' => 12, 'durability' => 100, 'type' => 'weapon'],
        'armor' => ['name' => 'Reactive Plating', 'def' => 8, 'durability' => 100, 'type' => 'armor']
    ];
}

$gameLog = $_SESSION['log'] ?? [];

// ===================================================================
// FORM PROCESSING (NON-COMBAT ACTIONS ONLY - COMBAT NOW USES AJAX)
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ===================================================================
    // DEBUG RESET FUNCTIONS
    // ===================================================================
    // Reset mech health to full
    if (isset($_POST['reset_mechs'])) {
        $playerMech['HP'] = $playerMech['MAX_HP'];
        $enemyMech['HP'] = $enemyMech['MAX_HP'];
        $gameLog[] = "[" . date('H:i:s') . "] Debug: Mech health reset to full";
    }
    
    // Reset card hands to starting state
    if (isset($_POST['reset_hands'])) {
        // Clear existing hands
        $_SESSION['player_hand'] = [];
        $playerHand = [];
        
        // Rebuild starting hands from card library
        if (!empty($cardLibrary)) {
            $handSize = $gameRules['starting_hand_size'];
            $shuffledCards = $cardLibrary;
            shuffle($shuffledCards);
            
            for ($i = 0; $i < min($handSize, count($shuffledCards)); $i++) {
                $playerHand[] = $shuffledCards[$i];
            }
            $_SESSION['player_hand'] = $playerHand;
        }
        
        $gameLog[] = "[" . date('H:i:s') . "] Debug: Card hands reset to starting state";
    }
    
    // Clear game log only
    if (isset($_POST['clear_log'])) {
        $gameLog = [];
        $gameLog[] = "[" . date('H:i:s') . "] Debug: Game log cleared";
    }
    
    // Reset everything to initial state
    if (isset($_POST['reset_all'])) {
        // Reset mech stats (preserve images)
        $playerImage = $playerMech['image'] ?? null;
        $enemyImage = $enemyMech['image'] ?? null;
        $playerMech = [
            'HP' => 100, 
            'ATK' => 30, 
            'DEF' => 15, 
            'MAX_HP' => 100, 
            'companion' => 'Pilot-Alpha',
            'name' => 'Player Mech',
            'image' => $playerImage
        ];
        $enemyMech = [
            'HP' => 100, 
            'ATK' => 25, 
            'DEF' => 10, 
            'MAX_HP' => 100, 
            'companion' => 'AI-Core',
            'name' => 'Enemy Mech',
            'image' => $enemyImage
        ];
        
        // Reset equipment
        $playerEquipment = ['weapon' => null, 'armor' => null, 'weapon_special' => null];
        $enemyEquipment = [
            'weapon' => ['name' => 'Ion Cannon', 'atk' => 12, 'durability' => 100, 'type' => 'weapon'],
            'armor' => ['name' => 'Reactive Plating', 'def' => 8, 'durability' => 100, 'type' => 'armor']
        ];
        
        // Reset hands
        $_SESSION['player_hand'] = [];
        $playerHand = [];
        
        // Rebuild starting hands
        if (!empty($cardLibrary)) {
            $handSize = $gameRules['starting_hand_size'];
            $shuffledCards = $cardLibrary;
            shuffle($shuffledCards);
            
            for ($i = 0; $i < min($handSize, count($shuffledCards)); $i++) {
                $playerHand[] = $shuffledCards[$i];
            }
            $_SESSION['player_hand'] = $playerHand;
        }
        
        // Reset log
        $gameLog = [];
        $gameLog[] = "[" . date('H:i:s') . "] Debug: Complete game state reset";
    }
    
    // ===================================================================
    // CARD MANAGEMENT SYSTEM
    // ===================================================================
    // Handle card deletion (remove from hand without equipping)
    if (isset($_POST['delete_card'])) {
        $cardIndex = intval($_POST['card_index']);
        
        if (isset($playerHand[$cardIndex])) {
            $card = $playerHand[$cardIndex];
            array_splice($playerHand, $cardIndex, 1);
            $_SESSION['player_hand'] = $playerHand;
            $gameLog[] = "[" . date('H:i:s') . "] Deleted {$card['name']} from hand!";
        }
    }
    
    // ===================================================================
    // DECK INTERACTION SYSTEM
    // ===================================================================
    // Handle deck clicking (draw cards)
    if (isset($_POST['draw_cards'])) {
        $requestedCards = intval($_POST['cards_to_draw'] ?? 5);
        $maxHandSize = $gameRules['max_hand_size'];
        $currentHandSize = count($playerHand);
        
        // Calculate how many cards we can actually draw
        $availableSlots = $maxHandSize - $currentHandSize;
        $cardsInLibrary = count($cardLibrary);
        $cardsToDraw = min($requestedCards, $availableSlots, $cardsInLibrary);
        
        if ($cardsToDraw > 0) {
            // Shuffle library to get random cards
            $shuffledLibrary = $cardLibrary;
            shuffle($shuffledLibrary);
            
            // Draw random cards from library to hand
            $drawnCards = array_slice($shuffledLibrary, 0, $cardsToDraw);
            $playerHand = array_merge($playerHand, $drawnCards);
            $_SESSION['player_hand'] = $playerHand;
            
            $gameLog[] = "[" . date('H:i:s') . "] Drew {$cardsToDraw} cards from deck!";
        } else {
            if ($availableSlots <= 0) {
                $gameLog[] = "[" . date('H:i:s') . "] Hand is full! Cannot draw more cards.";
            } elseif ($cardsInLibrary <= 0) {
                $gameLog[] = "[" . date('H:i:s') . "] Deck is empty! No cards to draw.";
            }
        }
    }
    
    // ===================================================================
    // EQUIPMENT SYSTEM
    // ===================================================================
    // Handle card equipping
    if (isset($_POST['equip_card'])) {
        $cardIndex = intval($_POST['card_index']);
        $equipSlot = $_POST['equip_slot']; // 'weapon', 'armor', or 'weapon_special'
        
        if (isset($playerHand[$cardIndex])) {
            $card = $playerHand[$cardIndex];
            
            // Handle special attack equipping
            if ($card['type'] === 'special attack' && $equipSlot === 'weapon_special') {
                // Check if weapon is equipped first
                if ($playerEquipment['weapon'] === null) {
                    $gameLog[] = "[" . date('H:i:s') . "] Error: Must equip weapon before special attack!";
                } else {
                    // Equip special attack (no element matching required)
                    $playerEquipment['weapon_special'] = [
                        'name' => $card['name'],
                        'damage' => $card['damage'] ?? 0,
                        'type' => $card['type'],
                        'card_data' => $card
                    ];
                    
                    // Remove card from hand
                    array_splice($playerHand, $cardIndex, 1);
                    $_SESSION['player_hand'] = $playerHand;
                    
                    $gameLog[] = "[" . date('H:i:s') . "] Equipped {$card['name']} special attack!";
                }
            }
            // Validate card type matches slot for weapons and armor
            elseif ($card['type'] === $equipSlot && ($card['type'] === 'weapon' || $card['type'] === 'armor')) {
                // Equip the card
                $playerEquipment[$equipSlot] = [
                    'name' => $card['name'],
                    'atk' => $card['damage'] ?? 0,
                    'def' => $card['damage'] ?? 0, // For armor, we'll use damage as defense value
                    'durability' => 100,
                    'type' => $card['type'],
                    'card_data' => $card
                ];
                
                // Remove card from hand session only (NOT from JSON library)
                array_splice($playerHand, $cardIndex, 1);
                $_SESSION['player_hand'] = $playerHand;
                
                $gameLog[] = "[" . date('H:i:s') . "] Equipped {$card['name']} to {$equipSlot} slot!";
            } else {
                $gameLog[] = "[" . date('H:i:s') . "] Error: Cannot equip {$card['type']} card to {$equipSlot} slot!";
            }
        }
    }
    
    // ===================================================================
    // HANDLE EQUIPMENT UNEQUIPPING
    // ===================================================================
    // Handle equipment unequipping
    if (isset($_POST['unequip_item'])) {
        $equipSlot = $_POST['equipment_slot']; // 'weapon', 'armor', or 'weapon_special'
        
        // Prevent unequipping weapon if special attack is attached
        if ($equipSlot === 'weapon' && $playerEquipment['weapon_special'] !== null) {
            $gameLog[] = "[" . date('H:i:s') . "] Error: Cannot unequip weapon while special attack is attached!";
        }
        // Check if player has something equipped in this slot
        elseif (isset($playerEquipment[$equipSlot]) && $playerEquipment[$equipSlot] !== null) {
            $equippedItem = $playerEquipment[$equipSlot];
            
            // Check if we have the original card data
            if (isset($equippedItem['card_data'])) {
                $originalCard = $equippedItem['card_data'];
                
                // Add card back to player hand
                $playerHand[] = $originalCard;
                $_SESSION['player_hand'] = $playerHand;
                
                // Remove from equipment slot
                $playerEquipment[$equipSlot] = null;
                $_SESSION['playerEquipment'] = $playerEquipment;
                
                $gameLog[] = "[" . date('H:i:s') . "] Unequipped {$originalCard['name']} from {$equipSlot} slot!";
            } else {
                $gameLog[] = "[" . date('H:i:s') . "] Error: Could not unequip {$equipSlot} - missing card data!";
            }
        } else {
            $gameLog[] = "[" . date('H:i:s') . "] Error: No {$equipSlot} equipped to unequip!";
        }
    }
    
    // Handle card clicks
    if (isset($_POST['card_click'])) {
        $cardInfo = htmlspecialchars($_POST['card_click']);
        $gameLog[] = "[" . date('H:i:s') . "] Card activated: {$cardInfo}";
    }
    
    // Handle equipment clicks
    if (isset($_POST['equipment_click'])) {
        $equipInfo = htmlspecialchars($_POST['equipment_click']);
        $gameLog[] = "[" . date('H:i:s') . "] Equipment used: {$equipInfo}";
    }
    
    // Save state back to session
    $_SESSION['playerMech'] = $playerMech;
    $_SESSION['enemyMech'] = $enemyMech;
    $_SESSION['playerEquipment'] = $playerEquipment;
    $_SESSION['enemyEquipment'] = $enemyEquipment;
    $_SESSION['log'] = $gameLog;
    
    // Prevent form resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ===================================================================
// HELPER FUNCTIONS
// ===================================================================
function getMechHealthPercent($currentHP, $maxHP) {
    if (!$maxHP || $maxHP <= 0) {
        return 100;
    }
    return max(0, min(100, ($currentHP / $maxHP) * 100));
}

function getMechStatusClass($currentHP, $maxHP) {
    $percent = getMechHealthPercent($currentHP, $maxHP);
    if ($percent > 60) return 'healthy';
    if ($percent > 30) return 'damaged';
    return 'critical';
}

function safeHtmlOutput($value, $default = 'Unknown') {
    if (empty($value) || $value === null) {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}

/**
 * Safely load and validate cards from JSON file
 * @param string $path Path to the JSON file
 * @return array Array of cards or empty array on failure
 */
function loadCardsFromJson($path) {
    if (!file_exists($path) || !is_readable($path)) {
        return [];
    }
    
    $content = file_get_contents($path);
    if ($content === false || empty($content)) {
        return [];
    }
    
    $data = json_decode($content, true);
    if ($data === null || !isset($data['cards']) || !is_array($data['cards'])) {
        return [];
    }
    
    return $data['cards'];
}

/**
 * Safely save cards to JSON file
 * @param string $path Path to the JSON file
 * @param array $cards Array of cards to save
 * @return bool Success status
 */
function saveCardsToJson($path, $cards) {
    $data = [
        'cards' => $cards,
        'meta' => [
            'created' => date('Y-m-d H:i:s'),
            'version' => '1.1',
            'total_cards' => count($cards),
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ];
    
    $json = json_encode($data, JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;
    }
    
    return file_put_contents($path, $json) !== false;
}

/**
 * Validate card structure
 * @param array $card Card data to validate
 * @return bool Whether card has all required fields
 */
function validateCard($card) {
    $requiredFields = ['id', 'name', 'type', 'cost'];
    foreach ($requiredFields as $field) {
        if (!isset($card[$field])) {
            return false;
        }
    }
    return true;
}

// ===================================================================
// EQUIPMENT DISPLAY HELPER
// ===================================================================
function renderEquipmentSlot($equipment, $slotType, $owner, $specialAttack = null) {
    if ($equipment === null) {
        // Empty slot
        $slotLabel = ucfirst($slotType);
        $placeholderText = "Equip {$slotLabel} Here";
        $slotClass = "empty-equipment-slot";
        
        return "
            <div class='equipment-area'>
                <div class='equipment-card {$slotType}-card {$owner}-equipment {$slotClass}' data-slot='{$slotType}' data-owner='{$owner}'>
                    <div class='card-type'>{$slotLabel}</div>
                    <div class='card-name empty-slot-text'>{$placeholderText}</div>
                    <div class='card-stats empty-slot-stats'>Click card in hand</div>
                </div>
            </div>
        ";
    } else {
        // Equipped item
        $statValue = $slotType === 'weapon' ? "+{$equipment['atk']}" : "+{$equipment['def']}";
        $statLabel = $slotType === 'weapon' ? 'ATK' : 'DEF';
        
        // Get element and rarity for styling
        $element = $equipment['card_data']['element'] ?? 'fire';
        $rarity = $equipment['card_data']['rarity'] ?? 'common';
        
        // Add X button ONLY for player equipment
        $unequipButton = '';
        if ($owner === 'player') {
            $unequipButton = "
                <button type='button' 
                        onclick='unequipItem(\"{$slotType}\")' 
                        class='equipment-unequip-btn' 
                        title='Unequip {$equipment['name']}'>
                    ✕
                </button>
            ";
        }
        
        // Check if this is a weapon with special attack
        $specialAttackHtml = '';
        if ($slotType === 'weapon' && $specialAttack !== null) {
            $specialUnequipButton = '';
            if ($owner === 'player') {
                $specialUnequipButton = "
                    <button type='button' 
                            onclick='unequipItem(\"weapon_special\")' 
                            class='special-unequip-btn' 
                            title='Unequip {$specialAttack['name']}'>
                        ✕
                    </button>
                ";
            }
            
            $specialElement = $specialAttack['card_data']['element'] ?? 'plasma';
            $specialAttackHtml = "
                <div class='special-attack-card' data-slot='weapon_special' data-element='{$specialElement}'>
                    <div class='special-card-name'>" . htmlspecialchars($specialAttack['name']) . "</div>
                    <div class='special-card-description'>" . htmlspecialchars($specialAttack['card_data']['description'] ?? '') . "</div>
                    {$specialUnequipButton}
                </div>
            ";
        }
        
        // Prepare background image styling
        $backgroundImage = '';
        $imageClass = '';
        if (isset($equipment['card_data']['image']) && !empty($equipment['card_data']['image']) && file_exists($equipment['card_data']['image'])) {
            $backgroundImage = "style=\"background-image: url('" . htmlspecialchars($equipment['card_data']['image']) . "');\"";
            $imageClass = ' has-image';
        }
        
        return "
            <div class='equipment-area weapon-combo-area'>
                {$specialAttackHtml}
                <form method='post'>
                    <button type='submit' name='equipment_click' value='{$owner} {$slotType}' class='equipment-card {$slotType}-card {$owner}-equipment equipped {$element}-element {$rarity}-rarity{$imageClass}' data-slot='{$slotType}' data-owner='{$owner}' {$backgroundImage}>
                        <div class='card-type'>{$slotType}</div>
                        <div class='card-name'>" . htmlspecialchars($equipment['name']) . "</div>
                        <div class='card-stats'>{$statLabel}: {$statValue}</div>
                        {$unequipButton}
                    </button>
                </form>
            </div>
        ";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRD Sandbox - Tactical Card Battle Interface</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="battlefield-container">

    <!-- NARRATIVE GUIDE PANEL -->
    <div id="narrativeGuide" class="narrative-guide-panel">
        <div class="portrait-container">
            <img id="guidePortrait" src="images/old_man_neutral.png" alt="Old Man">
            <!-- This div will be used for a blinking animation -->
            <div id="guideBlink" class="blink-overlay" style="background-image: url('images/old_man_blink.png');"></div>
        </div>
        <div class="speech-bubble">
            <p id="guideDialogueText">Welcome to town. Let's show our friend here what we can do.</p>
        </div>
    </div>

    <!-- ===================================================================
         TOP NAVIGATION BAR (UPDATED - Clean Navigation)
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="config/" class="config-link">⚙️ Configuration</a>
            <button type="button" onclick="showHelpModal()" class="help-button" title="Game Help & Instructions">❓ Help</button>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">NRD TACTICAL SANDBOX</h1>
        </div>
        <div class="nav-right">
            <a href="config/" class="version-badge" title="Open Configuration Dashboard">v1.0</a>
            <a href="logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         MAIN BATTLEFIELD LAYOUT
         =================================================================== -->
    <main class="battlefield">

        <!-- ENEMY SECTION (TOP) -->
        <section class="combat-zone enemy-zone">
            <div class="zone-label">ENEMY TERRITORY</div>
            
            <!-- Enemy Hand (Top - Hidden Cards in Fan Layout) -->
            <div class="hand-section enemy-hand-section">
                <div class="hand-cards-fan">
                    <?php for ($i = 1; $i <= $gameRules['starting_hand_size']; $i++): ?>
                        <div class="hand-card face-down fan-card" style="--card-index: <?= $i-1 ?>"></div>
                    <?php endfor; ?>
                </div>
                <div class="hand-label">Enemy Hand (<?= $gameRules['starting_hand_size'] ?>)</div>
            </div>
            
            <div class="battlefield-layout">
                <!-- Enemy Draw Deck (Far Left) -->
                <div class="draw-deck-area enemy-deck">
                    <div class="simple-deck-display">
                        <div class="deck-stack">
                            <div class="deck-card"></div>
                            <div class="deck-card"></div>
                            <div class="deck-card"></div>
                        </div>
                    </div>
                    <div class="simple-deck-label">Draw (<?= count($cardLibrary) ?>)</div>
                </div>

                <!-- Enemy Weapon Card -->
                <?= renderEquipmentSlot($enemyEquipment['weapon'], 'weapon', 'enemy') ?>

                <!-- Enemy Mech (Center) - ADDED IDs FOR AJAX -->
                <div class="mech-area enemy-mech">
                    <div class="mech-card <?= getMechStatusClass($enemyMech['HP'], $enemyMech['MAX_HP']) ?>" id="enemyMechCard">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog enemy-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($enemyMech['companion'], 'AI-Core') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">E</div>
                        
                        <?php if (!empty($enemyMech['image']) && file_exists($enemyMech['image'])): ?>
                            <div class="mech-image-container">
                                <img src="<?= htmlspecialchars($enemyMech['image']) ?>" alt="<?= htmlspecialchars($enemyMech['name'] ?? 'Enemy Mech') ?>" class="mech-image">
                            </div>
                        <?php else: ?>
                            <div class="mech-body"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value" id="enemyHPValue"><?= $enemyMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name"><?= htmlspecialchars($enemyMech['name'] ?? 'Enemy Mech') ?></div>
                        <div class="mech-stats">
                            <div class="stat">HP: <span id="enemyHPDisplay"><?= $enemyMech['HP'] ?></span>/<?= $enemyMech['MAX_HP'] ?></div>
                            <div class="stat">ATK: <span id="enemyATKDisplay"><?= $enemyMech['ATK'] + ($enemyEquipment['weapon']['atk'] ?? 0) ?></span></div>
                            <div class="stat">DEF: <span id="enemyDEFDisplay"><?= $enemyMech['DEF'] + ($enemyEquipment['armor']['def'] ?? 0) ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Enemy Armor Card -->
                <?= renderEquipmentSlot($enemyEquipment['armor'], 'armor', 'enemy') ?>

                <!-- Spacer -->
                <div class="spacer"></div>
            </div>
        </section>

        <!-- BATTLEFIELD DIVIDER -->
        <div class="battlefield-divider">
            <div id="turnIndicator" class="turn-indicator player-turn">PLAYER'S TURN</div>
            <div class="divider-line"></div>
            <div class="divider-label">COMBAT ZONE</div>
        </div>

        <!-- ENEMY EQUIPMENT MANAGER -->

        <!-- PLAYER SECTION (BOTTOM) -->
        <section class="combat-zone player-zone">
            <div class="zone-label">PLAYER TERRITORY</div>
            
            <div class="player-resources">
                <div class="energy-display">⚡️ Energy: <span id="playerEnergyValue"><?= $_SESSION['playerEnergy'] ?></span> / <?= $_SESSION['maxEnergy'] ?></div>
            </div>
            
            <div class="battlefield-layout">
                <!-- Player Draw Deck (Far Left) -->
                <div class="draw-deck-area player-deck">
                    <form method="post">
                        <button type="submit" name="draw_cards" value="1" class="simple-deck-button" title="Click to draw 5 cards">
                            <input type="hidden" name="cards_to_draw" value="5">
                            <div class="deck-stack">
                                <div class="deck-card"></div>
                                <div class="deck-card"></div>
                                <div class="deck-card"></div>
                            </div>
                        </button>
                    </form>
                    <div class="simple-deck-label">Draw (<?= count($cardLibrary) ?>)</div>
                </div>

                <!-- Player Weapon Card -->
                <?= renderEquipmentSlot($playerEquipment['weapon'], 'weapon', 'player', $playerEquipment['weapon_special']) ?>

                <!-- Player Mech (Center) - ADDED IDs FOR AJAX -->
                <div class="mech-area player-mech">
                    <div class="mech-card <?= getMechStatusClass($playerMech['HP'], $playerMech['MAX_HP']) ?>" id="playerMechCard">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog player-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($playerMech['companion'], 'Pilot-Alpha') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">P</div>
                        
                        <?php if (!empty($playerMech['image']) && file_exists($playerMech['image'])): ?>
                            <div class="mech-image-container">
                                <img src="<?= htmlspecialchars($playerMech['image']) ?>" alt="<?= htmlspecialchars($playerMech['name'] ?? 'Player Mech') ?>" class="mech-image">
                            </div>
                        <?php else: ?>
                            <div class="mech-body"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value" id="playerHPValue"><?= $playerMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name"><?= htmlspecialchars($playerMech['name'] ?? 'Your Mech') ?></div>
                        <div class="mech-stats">
                            <div class="stat">HP: <span id="playerHPDisplay"><?= $playerMech['HP'] ?></span>/<?= $playerMech['MAX_HP'] ?></div>
                            <div class="stat">ATK: <span id="playerATKDisplay"><?= $playerMech['ATK'] + ($playerEquipment['weapon']['atk'] ?? 0) ?></span></div>
                            <div class="stat">DEF: <span id="playerDEFDisplay"><?= $playerMech['DEF'] + ($playerEquipment['armor']['def'] ?? $playerEquipment['armor']['defense'] ?? 0) ?></span></div>
                        </div>
                    </div>
                </div>

                <!-- Player Armor Card -->
                <?= renderEquipmentSlot($playerEquipment['armor'], 'armor', 'player') ?>

                <!-- Spacer -->
                <div class="spacer"></div>
            </div>
            
            <!-- Player Hand (Bottom - Visible Cards in Fan Layout) -->
            <div class="hand-section player-hand-section">
                <div class="hand-label">Your Hand (<?= count($playerHand) ?>/<?= $gameRules['max_hand_size'] ?>)</div>
                <div class="hand-cards-fan">
                    <?php 
                    // Only display actual cards in hand (no empty slots)
                    foreach ($playerHand as $index => $card): 
                    ?>
                        <div class="hand-card-container" style="--card-index: <?= $index ?>">
                            <?php 
                            // Prepare background image and classes
                            $hasImage = !empty($card['image']) && file_exists($card['image']);
                            $backgroundStyle = '';
                            $imageClass = '';
                            
                            if ($hasImage) {
                                $backgroundStyle = 'style="background-image: url(\'' . htmlspecialchars($card['image']) . '\');"';
                                $imageClass = ' has-image';
                            }
                            ?>
                            <button type="button" onclick="handleCardClick(<?= $index ?>, '<?= $card['type'] ?>')" class="hand-card face-up fan-card <?= $card['type'] ?>-card <?= ($card['element'] ?? 'fire') ?>-element <?= ($card['rarity'] ?? 'common') ?>-rarity<?= $imageClass ?>" data-card='<?= htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8') ?>' draggable="true" <?= $backgroundStyle ?>>
                                <div class="card-mini-name"><?= htmlspecialchars($card['name']) ?></div>
                                <div class="card-mini-cost"><?= $card['cost'] ?></div>
                                
                                <?php if (!$hasImage): ?>
                                    <div class="card-type-icon">
                                        <?php
                                        $typeIcons = [
                                            'spell' => '✨',
                                            'weapon' => '⚔️', 
                                            'armor' => '🛡️',
                                            'creature' => '👾',
                                            'support' => '🔧',
                                            'special attack' => '💥'
                                        ];
                                        echo $typeIcons[$card['type']] ?? '❓';
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($card['damage'] > 0): ?>
                                    <div class="card-mini-damage">💥<?= $card['damage'] ?></div>
                                <?php endif; ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($playerHand) === 0): ?>
                        <div class="hand-empty-message">
                            <div class="empty-hand-text">Click deck to draw cards</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Trash Zone for Card Deletion -->
            <div class="trash-zone" id="trashZone">
                <div class="trash-icon">🗑️</div>
                <div class="trash-label">Drag cards here to delete</div>
            </div>
        </section>

        <!-- ACTION BAR - The new heart of player interaction -->
        <div id="actionBar" class="action-bar">
            <div id="actionBarText" class="action-bar-text">Your move. Play a card or prepare an attack.</div>
            <div id="actionBarButtons" class="action-bar-buttons">
                <!-- Buttons will be dynamically inserted here by JavaScript -->
            </div>
        </div>

    </main>


</div>

<!-- Debug Panel Removed - Now in config/debug.php -->
    <input type="hidden" name="equip_card" value="1">
    <input type="hidden" name="card_index" id="equipCardIndex">
    <input type="hidden" name="equip_slot" id="equipSlot">
</form>

<form id="deleteCardForm" method="post" style="display: none;">
    <input type="hidden" name="delete_card" value="1">
    <input type="hidden" name="card_index" id="deleteCardIndex">
</form>

<form id="unequipForm" method="post" style="display: none;">
    <input type="hidden" name="unequip_item" value="1">
    <input type="hidden" name="equipment_slot" id="unequipSlot">
</form>

<!-- ===================================================================
     CARD DETAIL MODAL
     =================================================================== -->
<div id="cardDetailModal" class="card-detail-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🃏 Card Details</h2>
            <button type="button" class="close-btn" onclick="closeCardDetails()">✕</button>
        </div>
        
        <div class="modal-body">
            <div class="card-detail-layout">
                <!-- Large Card Preview -->
                <div class="card-preview-section">
                    <div id="modalCardPreview" class="modal-card-preview">
                        <div class="modal-card-cost">0</div>
                        <div class="modal-card-name">Card Name</div>
                        <div class="modal-card-type">TYPE</div>
                        <div class="modal-card-art">[Card Art]</div>
                        <div class="modal-card-damage"></div>
                        <div class="modal-card-description">Card description...</div>
                        <div class="modal-card-rarity">Common</div>
                    </div>
                </div>
                
                <!-- Card Information Panel -->
                <div class="card-info-section">
                    <h3>Card Information</h3>
                    <div class="card-info-grid">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span id="modalCardName" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Type:</span>
                            <span id="modalCardType" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cost:</span>
                            <span id="modalCardCost" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Damage:</span>
                            <span id="modalCardDamage" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Rarity:</span>
                            <span id="modalCardRarity" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Description:</span>
                            <span id="modalCardDescription" class="info-value">-</span>
                        </div>
                    </div>
                    
                    <h3>Metadata</h3>
                    <div class="card-info-grid">
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span id="modalCardCreated" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Creator:</span>
                            <span id="modalCardCreator" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Card ID:</span>
                            <span id="modalCardId" class="info-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" onclick="playCard()" class="action-btn attack-btn">⚡ Play Card</button>
            <button type="button" onclick="closeCardDetails()" class="action-btn cancel-btn">Close</button>
        </div>
    </div>
</div>

<!-- Card Detail Modal Overlay -->
<div id="cardDetailOverlay" class="card-detail-overlay" onclick="closeCardDetails()"></div>

<!-- ===================================================================
     HELP MODAL
     =================================================================== -->
<div id="helpModal" class="help-modal">
    <div class="help-modal-content">
        <div class="help-modal-header">
            <h2>🎮 NRD Tactical Sandbox - Game Guide</h2>
            <button type="button" class="help-close-btn" onclick="closeHelpModal()">✕</button>
        </div>
        
        <div class="help-modal-body">
            <div class="help-sections">
                
                <!-- Game Overview -->
                <div class="help-section">
                    <h3>🎯 Game Overview</h3>
                    <p>NRD Tactical Sandbox is a turn-based card battle game where you pilot a mech and battle against an AI opponent. Use strategy, equipment, and card management to emerge victorious!</p>
                </div>
                
                <!-- Turn System -->
                <div class="help-section">
                    <h3>⚡ Turn-Based System</h3>
                    <div class="help-subsection">
                        <h4>🟢 Player Turn:</h4>
                        <ul>
                            <li>Your energy is restored to maximum (default: 5)</li>
                            <li>You can play cards that cost energy</li>
                            <li>Each card has an energy cost shown in the top-left corner</li>
                            <li>Click "End Turn" when you're done</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>🔴 Enemy Turn:</h4>
                        <ul>
                            <li>The AI takes its turn automatically</li>
                            <li>Watch for enemy actions and prepare your strategy</li>
                            <li>Turn returns to you after AI completes its actions</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Card System -->
                <div class="help-section">
                    <h3>🃏 Card System</h3>
                    <div class="help-subsection">
                        <h4>Card Types:</h4>
                        <ul>
                            <li><strong>⚔️ Weapons:</strong> Equip to increase attack power</li>
                            <li><strong>🛡️ Armor:</strong> Equip to increase defense</li>
                            <li><strong>⚡ Spells:</strong> Cast for immediate effects</li>
                            <li><strong>👾 Creatures:</strong> Summon allies to the battlefield</li>
                            <li><strong>🔧 Support:</strong> Utility effects and buffs</li>
                            <li><strong>💥 Special Attacks:</strong> Attach to weapons for enhanced abilities</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>Playing Cards:</h4>
                        <ul>
                            <li>Click a card in your hand to play it (costs energy)</li>
                            <li>Equipment cards can be equipped by clicking or dragging to slots</li>
                            <li>Spell cards are consumed when played</li>
                            <li>You cannot play cards if you don't have enough energy</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Equipment System -->
                <div class="help-section">
                    <h3>⚔️ Equipment System</h3>
                    <div class="help-subsection">
                        <h4>Equipment Slots:</h4>
                        <ul>
                            <li><strong>Weapon Slot:</strong> Increases your mech's attack power</li>
                            <li><strong>Armor Slot:</strong> Increases your mech's defense</li>
                            <li><strong>Special Attack:</strong> Attaches to weapons for extra abilities</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>Managing Equipment:</h4>
                        <ul>
                            <li>Drag weapon/armor cards to the appropriate slots</li>
                            <li>Click the red ✕ button to unequip items</li>
                            <li>Unequipped cards return to your hand</li>
                            <li>Equipment bonuses apply immediately to combat</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Combat System -->
                <div class="help-section">
                    <h3>⚔️ Combat System</h3>
                    <div class="help-subsection">
                        <h4>Mech Stats:</h4>
                        <ul>
                            <li><strong>HP:</strong> Health points - when this reaches 0, you lose</li>
                            <li><strong>ATK:</strong> Attack power + weapon bonuses</li>
                            <li><strong>DEF:</strong> Defense power + armor bonuses</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>Combat Actions:</h4>
                        <ul>
                            <li><strong>⚔️ Attack Enemy:</strong> Deal damage based on your ATK vs enemy DEF</li>
                            <li><strong>🛡️ Enemy Attacks:</strong> Simulate enemy attacking you</li>
                            <li><strong>🔄 Reset Mechs:</strong> Restore both mechs to full health</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Strategy Tips -->
                <div class="help-section">
                    <h3>🧠 Strategy Tips</h3>
                    <ul>
                        <li><strong>Energy Management:</strong> Plan your turns - expensive cards may require saving energy</li>
                        <li><strong>Equipment First:</strong> Equip weapons and armor early for combat bonuses</li>
                        <li><strong>Card Draw:</strong> Click the deck to draw cards when your hand is empty</li>
                        <li><strong>Special Combos:</strong> Attach special attacks to weapons for powerful combinations</li>
                        <li><strong>Defense Matters:</strong> Armor can significantly reduce incoming damage</li>
                        <li><strong>Timing:</strong> Some effects are best saved for critical moments</li>
                    </ul>
                </div>
                
                <!-- Interface Guide -->
                <div class="help-section">
                    <h3>🖥️ Interface Guide</h3>
                    <div class="help-subsection">
                        <h4>Top Navigation:</h4>
                        <ul>
                            <li><strong>⚙️ Configuration:</strong> Access card creator, debug tools, and settings</li>
                            <li><strong>❓ Help:</strong> This help guide</li>
                            <li><strong>v1.0:</strong> Current game version</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>Battlefield Layout:</h4>
                        <ul>
                            <li><strong>Top:</strong> Enemy territory with their mech and equipment</li>
                            <li><strong>Center:</strong> Combat zone divider with turn indicator</li>
                            <li><strong>Bottom:</strong> Your territory with hand, mech, and equipment</li>
                            <li><strong>Right Panel:</strong> Combat actions and enemy equipment manager</li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
        
        <div class="help-modal-footer">
            <button type="button" onclick="closeHelpModal()" class="help-close-button">Got it! Let's Play! 🎮</button>
        </div>
    </div>
</div>

<!-- Help Modal Overlay -->
<div id="helpModalOverlay" class="help-modal-overlay" onclick="closeHelpModal()"></div>


<script>
// ===================================================================
// COMBAT SYSTEM AJAX FUNCTIONS (RESTORED)
// ===================================================================
function performCombatAction(action) {
    // Validate player action
    if (!validatePlayerAction(action)) {
        return Promise.reject('Invalid turn');
    }
    
    // Disable buttons during request to prevent double-clicks
    const buttons = document.querySelectorAll('.action-btn');
    buttons.forEach(btn => btn.disabled = true);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', action);
    
    // Send AJAX request to combat manager and return promise
    return fetch('combat-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI with new combat state
            updateCombatUI(data.data);
            
            // Add log entry to debug panel if open
            addLogEntry(data.data.logEntry);
            
            // Check for game over conditions
            if (data.data.gameOver) {
                if (data.data.gameOver === 'player_wins') {
                    updateActionBar('player_wins');
                } else if (data.data.gameOver === 'enemy_wins') {
                    updateActionBar('enemy_wins');
                }
            }
            
            // Show brief success message
            showCombatMessage(data.message, 'success');
            
            return data; // Return data for chaining
        } else {
            // Show error message
            showCombatMessage('Error: ' + data.message, 'error');
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('Combat action error:', error);
        showCombatMessage('Network error during combat action', 'error');
        throw error;
    })
    .finally(() => {
        // Re-enable buttons
        buttons.forEach(btn => btn.disabled = false);
    });
}

function updateCombatUI(combatData) {
    const { playerMech, enemyMech, playerEnergy, playerEquipment, enemyEquipment } = combatData;
    
    // Update Player HP displays (if playerMech data is available)
    if (playerMech) {
        console.log('🩺 Updating Player HP to:', playerMech.HP, 'Max HP:', playerMech.MAX_HP);
        const playerHPValue = document.getElementById('playerHPValue');
        const playerHPDisplay = document.getElementById('playerHPDisplay');
        const debugPlayerHP = document.getElementById('debugPlayerHP');
        if (playerHPValue) playerHPValue.textContent = playerMech.HP;
        if (playerHPDisplay) playerHPDisplay.textContent = playerMech.HP;
        if (debugPlayerHP) debugPlayerHP.textContent = playerMech.HP;
        
        // Update mech card status class
        updateMechStatusClass('playerMechCard', playerMech.HP, playerMech.MAX_HP);
        
        // Update ATK/DEF displays if equipment data is available
        if (playerEquipment) {
            const weaponATK = (playerEquipment.weapon && playerEquipment.weapon.atk) ? parseInt(playerEquipment.weapon.atk) : 0;
            const armorDEF = (playerEquipment.armor && (playerEquipment.armor.def || playerEquipment.armor.defense)) ? parseInt(playerEquipment.armor.def || playerEquipment.armor.defense) : 0;
            const totalATK = playerMech.ATK + weaponATK;
            const totalDEF = playerMech.DEF + armorDEF;
            
            const playerATKDisplay = document.getElementById('playerATKDisplay');
            const playerDEFDisplay = document.getElementById('playerDEFDisplay');
            const debugPlayerATK = document.getElementById('debugPlayerATK');
            const debugPlayerDEF = document.getElementById('debugPlayerDEF');
            
            if (playerATKDisplay) playerATKDisplay.textContent = totalATK;
            if (playerDEFDisplay) playerDEFDisplay.textContent = totalDEF;
            if (debugPlayerATK) debugPlayerATK.textContent = totalATK;
            if (debugPlayerDEF) debugPlayerDEF.textContent = totalDEF;
        }
    }
    
    // Update Enemy HP displays (if enemyMech data is available)
    if (enemyMech) {
        console.log('🤖 Updating Enemy HP to:', enemyMech.HP, 'Max HP:', enemyMech.MAX_HP);
        const enemyHPValue = document.getElementById('enemyHPValue');
        const enemyHPDisplay = document.getElementById('enemyHPDisplay');
        const debugEnemyHP = document.getElementById('debugEnemyHP');
        if (enemyHPValue) enemyHPValue.textContent = enemyMech.HP;
        if (enemyHPDisplay) enemyHPDisplay.textContent = enemyMech.HP;
        if (debugEnemyHP) debugEnemyHP.textContent = enemyMech.HP;
        
        // Update mech card status class
        updateMechStatusClass('enemyMechCard', enemyMech.HP, enemyMech.MAX_HP);
        
        // Update ATK/DEF displays if equipment data is available
        if (enemyEquipment) {
            const weaponATK = (enemyEquipment.weapon && enemyEquipment.weapon.atk) ? parseInt(enemyEquipment.weapon.atk) : 0;
            const armorDEF = (enemyEquipment.armor && (enemyEquipment.armor.def || enemyEquipment.armor.defense)) ? parseInt(enemyEquipment.armor.def || enemyEquipment.armor.defense) : 0;
            const totalATK = enemyMech.ATK + weaponATK;
            const totalDEF = enemyMech.DEF + armorDEF;
            
            const enemyATKDisplay = document.getElementById('enemyATKDisplay');
            const enemyDEFDisplay = document.getElementById('enemyDEFDisplay');
            const debugEnemyATK = document.getElementById('debugEnemyATK');
            const debugEnemyDEF = document.getElementById('debugEnemyDEF');
            
            if (enemyATKDisplay) enemyATKDisplay.textContent = totalATK;
            if (enemyDEFDisplay) enemyDEFDisplay.textContent = totalDEF;
            if (debugEnemyATK) debugEnemyATK.textContent = totalATK;
            if (debugEnemyDEF) debugEnemyDEF.textContent = totalDEF;
        }
    }
    
    // Update energy display (if playerEnergy data is available)
    if (playerEnergy !== undefined) {
        updateEnergyDisplay(playerEnergy);
    }
}

function updateMechStatusClass(mechCardId, currentHP, maxHP) {
    const mechCard = document.getElementById(mechCardId);
    if (!mechCard) return;
    
    // Calculate health percentage
    const percent = (currentHP / maxHP) * 100;
    
    // Remove existing status classes
    mechCard.classList.remove('healthy', 'damaged', 'critical');
    
    // Add appropriate status class
    if (percent > 60) {
        mechCard.classList.add('healthy');
    } else if (percent > 30) {
        mechCard.classList.add('damaged');
    } else {
        mechCard.classList.add('critical');
    }
}

function showCombatMessage(message, type) {
    // Create temporary message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `combat-message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 10px 20px;
        border-radius: 5px;
        color: white;
        font-weight: bold;
        z-index: 9999;
        pointer-events: none;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    `;
    
    document.body.appendChild(messageDiv);
    
    // Remove message after 2 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.parentNode.removeChild(messageDiv);
        }
    }, 2000);
}

function showMessage(message, type = 'success') {
    // Create temporary message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `game-message ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 9999;
        pointer-events: none;
        background: ${type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#28a745'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        border: 1px solid ${type === 'error' ? '#c82333' : type === 'warning' ? '#e0a800' : '#1e7e34'};
        font-size: 14px;
        max-width: 300px;
        word-wrap: break-word;
        animation: slideInRight 0.3s ease-out;
    `;
    
    // Add CSS animation if not already added
    if (!document.getElementById('message-animations')) {
        const style = document.createElement('style');
        style.id = 'message-animations';
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(messageDiv);
    
    // Remove message after 3 seconds
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.style.animation = 'slideInRight 0.3s ease-out reverse';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }
    }, 3000);
}

function addLogEntry(logEntry) {
    // Add to debug panel log if it exists
    const actionLog = document.getElementById('debugActionLog');
    if (actionLog && logEntry) {
        const logDiv = document.createElement('div');
        logDiv.className = 'log-entry';
        logDiv.textContent = logEntry;
        actionLog.appendChild(logDiv);
        
        // Keep only last 10 entries
        const entries = actionLog.querySelectorAll('.log-entry');
        if (entries.length > 10) {
            actionLog.removeChild(entries[0]);
        }
        
        // Scroll to bottom
        actionLog.scrollTop = actionLog.scrollHeight;
    }
}

// ===================================================================
// UNEQUIP FUNCTIONALITY
// ===================================================================
function unequipItem(slotType) {
    // Confirm unequip action
    if (confirm(`Remove ${slotType} and return card to hand?`)) {
        // Set the form values
        document.getElementById('unequipSlot').value = slotType;
        
        // Submit the form
        document.getElementById('unequipForm').submit();
    }
}

// ===================================================================
// CARD MANAGEMENT JAVASCRIPT - CONSOLIDATED SYSTEM
// ===================================================================

function equipCard(cardIndex, cardType) {
    console.log(`🎯 Equipping card ${cardIndex} as ${cardType}`);
    
    // Use AJAX instead of form submission to maintain dynamic UI
    const formData = new URLSearchParams();
    formData.append('equip_card', '1');
    formData.append('card_index', cardIndex);
    formData.append('equip_slot', cardType);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.text())
    .then(() => {
        // Reload the page to see the equipped item
        // TODO: In future, this could be made fully dynamic
        window.location.reload();
    })
    .catch(error => {
        console.error('Equipment error:', error);
        showMessage('❌ Failed to equip item');
    });
}

function deleteCard(cardIndex) {
    if (confirm('Remove this card from your hand?')) {
        // Set the form values
        document.getElementById('deleteCardIndex').value = cardIndex;
        
        // Submit the form
        document.getElementById('deleteCardForm').submit();
    }
}

// Debug panel functions removed - now in config/debug.php

// ===================================================================
// CARD DETAIL MODAL FUNCTIONS (LEGACY SUPPORT)
// ===================================================================
function showCardDetails(cardIndex) {
    // Redirect to unified CardZoom system
    const cardElement = document.querySelector(`[data-card][style*="--card-index: ${cardIndex}"]`);
    if (cardElement) {
        CardZoom.showZoomModal(cardElement);
    } else {
        console.warn(`Card element not found for index ${cardIndex}`);
    }
}

function closeCardDetails() {
    document.getElementById('cardDetailModal').classList.remove('active');
    document.getElementById('cardDetailOverlay').classList.remove('active');
}

function playCard() {
    // Get the current card data from the modal
    const cardName = document.getElementById('modalCardName').textContent;
    const cardType = document.getElementById('modalCardType').textContent.toLowerCase();
    const cardCost = parseInt(document.getElementById('modalCardCost').textContent) || 0;
    const cardDamage = parseInt(document.getElementById('modalCardDamage').textContent) || 0;
    
    console.log(`Playing card: ${cardName} (${cardType})`);
    
    // Implement basic card effects based on type
    switch (cardType) {
        case 'spell':
            if (cardDamage > 0) {
                // Damage spell - attack enemy
                showMessage(`🔥 ${cardName} deals ${cardDamage} damage!`);
                addLogEntry(`Player cast ${cardName} - ${cardDamage} damage to enemy`);
            } else {
                // Utility spell
                showMessage(`✨ ${cardName} activated!`);
                addLogEntry(`Player cast ${cardName}`);
            }
            break;
            
        case 'creature':
            showMessage(`👾 ${cardName} summoned to the battlefield!`);
            addLogEntry(`Player summoned ${cardName}`);
            break;
            
        case 'support':
            showMessage(`🔧 ${cardName} support activated!`);
            addLogEntry(`Player activated ${cardName}`);
            break;
            
        default:
            showMessage(`⚡ ${cardName} played!`);
            addLogEntry(`Player played ${cardName}`);
    }
    
    closeCardDetails();
}

// Card creator functions removed - now in config/cards.php

// Card Zoom Modal Management
const CardZoom = {
    activeModal: null,
    isOpen: false,

    // Initialize zoom system
    init() {
        this.bindEvents();
        console.log('🔍 Card Zoom system initialized');
    },

    // Bind click events for card zoom
    bindEvents() {
        // Handle hand card clicks for zoom (allow all card types)
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.hand-card[data-card]');
            if (card) {
                e.preventDefault();
                e.stopPropagation();
                this.showZoomModal(card);
            }
        });

        // Handle equipment card clicks (equipped items)
        document.addEventListener('click', (e) => {
            const equipment = e.target.closest('.equipment-card.equipped');
            if (equipment && !e.target.closest('.equipment-unequip-btn')) {
                e.preventDefault();
                e.stopPropagation();
                this.showEquipmentZoom(equipment);
            }
        });

        // Handle keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeZoomModal();
            }
        });

        // Prevent zoom when clicking action buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.card-delete-btn, .equipment-unequip-btn, .action-btn')) {
                e.stopPropagation();
            }
        });
    },

    // Check if card is equipment type (should use equipment action instead of zoom)
    isEquipmentCard(cardElement) {
        try {
            const cardData = JSON.parse(cardElement.getAttribute('data-card'));
            return cardData.type === 'weapon' || cardData.type === 'armor';
        } catch (e) {
            return false;
        }
    },

    // Show zoom modal for hand cards
    showZoomModal(cardData, isEquipment = false, cardIndex = null) {
        if (this.isOpen) return;

        try {
            // Store cardIndex for use in modal
            this.currentCardIndex = cardIndex;
            this.createZoomModal(cardData, isEquipment, cardIndex);
            this.isOpen = true;
        } catch (e) {
            console.error('Error parsing card data for zoom:', e);
        }
    },

    // Show zoom modal for equipped items
    showEquipmentZoom(equipmentElement) {
        if (this.isOpen) return;

        // Get the equipment type from the element
        const type = equipmentElement.classList.contains('weapon-card') ? 'weapon' : 'armor';
        
        // Access the equipment data from PHP session
        const playerEquipment = <?= json_encode($playerEquipment) ?>;
        const equippedItem = playerEquipment[type];
        
        let equipmentData;
        
        // Check if we have the original card data stored
        if (equippedItem && equippedItem.card_data) {
            // Use the original card data (preserves image and all details)
            equipmentData = {
                ...equippedItem.card_data,
                description: `${equippedItem.card_data.description || 'No description available.'}\n\n[Currently equipped - providing combat bonuses]`
            };
        } else {
            // Fallback to basic equipment data if card_data is missing
            const name = equipmentElement.querySelector('.card-name').textContent;
            const stats = equipmentElement.querySelector('.card-stats').textContent;
            
            equipmentData = {
                id: 'equipped_' + type,
                name: name,
                type: type,
                cost: '?',
                damage: stats.match(/\d+/) ? parseInt(stats.match(/\d+/)[0]) : 0,
                description: `Currently equipped ${type}. Providing combat bonuses.`,
                rarity: 'equipped',
                created_at: 'Equipment',
                created_by: 'Player'
            };
        }

        this.createZoomModal(equipmentData, true);
        this.isOpen = true;
    },

    // Create and show the zoom modal
    createZoomModal(cardData, isEquipment = false, cardIndex = null) {
        // Remove existing modal if any
        this.closeZoomModal();

        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'card-zoom-overlay';
        overlay.innerHTML = this.generateZoomHTML(cardData, isEquipment, cardIndex);

        // Add click handler to overlay (close when clicking outside card)
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.closeZoomModal();
            }
        });

        // Add to DOM
        document.body.appendChild(overlay);
        this.activeModal = overlay;

        // Bind close button
        const closeBtn = overlay.querySelector('.card-zoom-close');
        closeBtn.addEventListener('click', () => {
            this.closeZoomModal();
        });

        // Show with animation
        setTimeout(() => {
            overlay.classList.add('active');
        }, 10);

        // Add flip effect to card
        const container = overlay.querySelector('.card-zoom-container');
        setTimeout(() => {
            container.classList.add('flipping');
        }, 200);
    },

    // Generate zoom modal HTML
    generateZoomHTML(cardData, isEquipment = false, cardIndex = null) {
        const typeIcon = this.getTypeIcon(cardData.type);
        const actionHint = isEquipment ? 
            'Currently equipped • Providing combat bonuses' : 
            this.getActionHint(cardData.type);
            
        // Debug: Log card data and image path
        console.log('🔍 Zoom Modal - Card Data:', cardData);
        console.log('🖼️ Image Path:', cardData.image);

        return `
            <div class="card-zoom-container">
                <div class="card-zoom-large ${cardData.type}-card ${(cardData.element || 'fire')}-element ${(cardData.rarity || 'common')}-rarity">
                    <button class="card-zoom-close" title="Close (ESC)">✕</button>
                    
                    <div class="zoom-cost">${cardData.cost}</div>
                    
                    <div class="zoom-header">
                        <div class="zoom-name">${this.escapeHtml(cardData.name)}</div>
                        <div class="zoom-type">${typeIcon} ${cardData.type.toUpperCase()}</div>
                    </div>
                    
                    <div class="zoom-art">
                        ${cardData.image && cardData.image.length > 0 ? 
                            `<img src="${this.escapeHtml(cardData.image)}" alt="${this.escapeHtml(cardData.name)}" class="zoom-art-image" onerror="console.error('Failed to load image:', '${this.escapeHtml(cardData.image)}'); this.style.display='none'; this.parentNode.innerHTML='<div class=\\'zoom-art-placeholder\\'>${typeIcon}<br><small>Image not found</small></div>';">` :
                            `<div class="zoom-art-placeholder">${typeIcon}<br><small>Card Artwork</small></div>`
                        }
                    </div>
                    
                    <div class="zoom-stats">
                        <div class="zoom-stat damage">
                            <div class="zoom-stat-label">Damage</div>
                            <div class="zoom-stat-value">${cardData.damage || 0}</div>
                        </div>
                        <div class="zoom-stat rarity">
                            <div class="zoom-stat-label">Rarity</div>
                            <div class="zoom-stat-value">${this.capitalizeFirst(cardData.rarity || 'common')}</div>
                        </div>
                        <div class="zoom-stat type">
                            <div class="zoom-stat-label">Type</div>
                            <div class="zoom-stat-value">${this.capitalizeFirst(cardData.type)}</div>
                        </div>
                    </div>
                    
                    <div class="zoom-description">
                        ${this.escapeHtml(cardData.description || 'No description available.')}
                    </div>
                    
                    <div class="zoom-rarity ${cardData.rarity || 'common'}">
                        ${this.capitalizeFirst(cardData.rarity || 'common')}
                    </div>
                    
                    <div class="card-zoom-hint">
                        ${actionHint}
                    </div>
                    
                    ${(cardData.type === 'weapon' || cardData.type === 'armor') && !isEquipment ? 
                        `<div class="zoom-equip-action" onclick="CardZoom.closeZoomModal(); equipCardById('${cardData.id}');">
                            <div class="equip-icon">${cardData.type === 'weapon' ? '⚔️' : '🛡️'}</div>
                            <div class="equip-text">EQUIP ${cardData.type.toUpperCase()}</div>
                        </div>` : ''
                    }
                    ${!isEquipment && cardIndex !== null && (cardData.type !== 'weapon' && cardData.type !== 'armor') ? 
                        `<div class="zoom-equip-action" onclick="playCardFromHand(${cardIndex})">
                            <div class="equip-icon">⚡</div>
                            <div class="equip-text">PLAY CARD (${cardData.cost} Energy)</div>
                        </div>` : ''
                    }
                </div>
            </div>
        `;
    },

    // Close zoom modal
    closeZoomModal() {
        if (this.activeModal) {
            this.activeModal.classList.remove('active');
            
            setTimeout(() => {
                if (this.activeModal && this.activeModal.parentNode) {
                    this.activeModal.parentNode.removeChild(this.activeModal);
                }
                this.activeModal = null;
                this.isOpen = false;
            }, 300);
        }
    },

    // Get type icon
    getTypeIcon(type) {
        const icons = {
            'spell': '✨',
            'weapon': '⚔️', 
            'armor': '🛡️',
            'creature': '👾',
            'support': '🔧',
            'special attack' : '💥'
        };
        return icons[type] || '❓';
    },

    // Get action hint based on card type
    getActionHint(type) {
        switch (type) {
            case 'weapon':
            case 'armor':
                return 'Click to equip this ' + type;
            case 'spell':
                return 'Click to cast this spell';
            case 'creature':
                return 'Click to summon this creature';
            case 'support':
                return 'Click to activate this support';
            default:
                return 'Click outside to close';
        }
    },

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
};

// ===================================================================
// NARRATIVE GUIDE SYSTEM
// ===================================================================
const NarrativeGuide = {
    panel: null,
    portrait: null,
    dialogue: null,
    typewriterTimer: null,
    preloadedImages: {},

    init: function() {
        this.panel = document.getElementById('narrativeGuide');
        this.portrait = document.getElementById('guidePortrait');
        this.dialogue = document.getElementById('guideDialogueText');
        this.preloadImages();
    },

    preloadImages: function() {
        const expressions = ['neutral', 'happy', 'serious', 'thoughtful', 'disappointed'];
        expressions.forEach(expression => {
            const img = new Image();
            img.onload = () => {
                this.preloadedImages[expression] = true;
            };
            img.onerror = () => {
                console.warn(`Failed to load old_man_${expression}.png`);
                this.preloadedImages[expression] = false;
            };
            img.src = `images/old_man_${expression}.png`;
        });
    },

    show: function(expression, text) {
        if (!this.panel) this.init();
        this.panel.classList.add('visible');

        // Clear any existing typewriter animation
        if (this.typewriterTimer) {
            clearTimeout(this.typewriterTimer);
        }

        // Validate and change the expression
        if (this.preloadedImages[expression] !== false) {
            this.portrait.src = `images/old_man_${expression}.png`;
        } else {
            console.warn(`Using fallback for missing expression: ${expression}`);
            this.portrait.src = `images/old_man_neutral.png`;
        }

        // Animate the text with a typewriter effect
        let i = 0;
        this.dialogue.innerHTML = '';
        const typeWriter = () => {
            if (i < text.length) {
                this.dialogue.innerHTML += text.charAt(i);
                i++;
                this.typewriterTimer = setTimeout(typeWriter, 30);
            }
        };
        typeWriter();
    }
};

// ===================================================================
// UNIFIED CARD INTERACTION SYSTEM
// ===================================================================

// ===================================================================
// ENERGY AND TURN SYSTEM FUNCTIONS
// ===================================================================

// Turn validation helper function
function isPlayerTurn() {
    // Check if it's the player's turn and game is active
    return window.gameState === 'active' && window.currentPlayerTurn === 'player';
}

function validatePlayerAction(actionName) {
    if (window.gameState !== 'active') {
        showMessage('Game is over! Please start a new game.', 'error');
        return false;
    }
    
    if (window.currentPlayerTurn !== 'player') {
        showMessage('It\'s not your turn! Wait for the enemy to finish.', 'warning');
        return false;
    }
    
    return true;
}

function playCard(cardIndex) {
    const formData = new FormData();
    formData.append('action', 'play_card');
    formData.append('card_index', cardIndex);

    fetch('combat-manager.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload to reflect changes. We will replace this with dynamic updates later.
                window.location.reload();
            } else {
                alert('Could not play card: ' + data.message);
            }
        });
}


// ===================================================================
// ENEMY EQUIPMENT MANAGER FUNCTIONS
// ===================================================================
function toggleEnemyManager() {
    const content = document.getElementById('enemyManagerContent');
    const toggle = document.getElementById('enemyManagerToggle');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        toggle.textContent = 'Hide ▲';
    } else {
        content.style.display = 'none';
        toggle.textContent = 'Show ▼';
    }
}

function assignEnemyEquipment(slotType, cardId) {
    if (!cardId) {
        // Clearing equipment
        clearEnemyEquipmentSlot(slotType);
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'assign_enemy_equipment');
    formData.append('slot_type', slotType);
    formData.append('card_id', cardId);
    
    fetch('combat-manager.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload to reflect changes
                window.location.reload();
            } else {
                alert('Could not assign equipment: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Assignment error:', error);
            alert('Network error during equipment assignment');
        });
}

function clearEnemyEquipmentSlot(slotType) {
    const formData = new FormData();
    formData.append('action', 'clear_enemy_equipment');
    formData.append('slot_type', slotType);
    
    fetch('combat-manager.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Could not clear equipment: ' + data.message);
            }
        });
}

function clearEnemyEquipment() {
    if (confirm('Clear all enemy equipment?')) {
        clearEnemyEquipmentSlot('weapon');
        setTimeout(() => clearEnemyEquipmentSlot('armor'), 200);
    }
}

function randomEnemyLoadout() {
    const formData = new FormData();
    formData.append('action', 'random_enemy_loadout');
    
    fetch('combat-manager.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Could not generate random loadout: ' + data.message);
            }
        });
}

// Helper function to equip card by ID from zoom modal
function equipCardById(cardId) {
    // Find the card index in player hand by ID
    const playerHand = <?= json_encode($playerHand) ?>;
    const cardIndex = playerHand.findIndex(card => card.id === cardId);
    
    if (cardIndex !== -1) {
        const card = playerHand[cardIndex];
        equipCard(cardIndex, card.type);
    } else {
        console.error('Card not found in hand:', cardId);
        showMessage('❌ Card not found in hand');
    }
}

// Initialize zoom system when DOM is ready (consolidated)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        CardZoom.init();
        initDragAndDrop();
        // Initialize Narrative Guide
        setTimeout(() => {
            NarrativeGuide.show('neutral', "Welcome to town. Let's show our friend here what we can do.");
        }, 1000); // Show after a 1-second delay
    });
} else {
    CardZoom.init();
    initDragAndDrop();
    // Initialize Narrative Guide
    setTimeout(() => {
        NarrativeGuide.show('neutral', "Welcome to town. Let's show our friend here what we can do.");
    }, 1000); // Show after a 1-second delay
}

// ===================================================================
// DRAG AND DROP CARD DELETION SYSTEM
// ===================================================================
function initDragAndDrop() {
    const trashZone = document.getElementById('trashZone');
    
    // Make hand cards draggable
    document.querySelectorAll('.hand-card').forEach(card => {
        card.draggable = true;
        card.addEventListener('dragstart', handleCardDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });
    
    // Set up trash zone drop handling
    trashZone.addEventListener('dragover', handleDragOver);
    trashZone.addEventListener('dragenter', handleDragEnter);
    trashZone.addEventListener('dragleave', handleDragLeave);
    trashZone.addEventListener('drop', handleTrashDrop);
    
    // Set up equipment slot drop handling
    const equipmentSlots = document.querySelectorAll('.equipment-card');
    console.log('🎯 Found', equipmentSlots.length, 'equipment slots');
    
    equipmentSlots.forEach((slot, index) => {
        console.log(`🎯 Setting up slot ${index}:`, slot.getAttribute('data-slot'), slot.className);
        slot.addEventListener('dragover', handleEquipmentDragOver);
        slot.addEventListener('dragenter', handleEquipmentDragEnter);
        slot.addEventListener('dragleave', handleEquipmentDragLeave);
        slot.addEventListener('drop', handleEquipmentDrop);
    });
}

function handleCardDragStart(e) {
    const cardContainer = e.target.closest('.hand-card-container');
    const cardIndex = cardContainer.style.getPropertyValue('--card-index');
    const cardButton = e.target.closest('.hand-card');
    
    // Get card data to determine type
    let cardType = 'unknown';
    try {
        const cardData = JSON.parse(cardButton.getAttribute('data-card'));
        cardType = cardData.type;
        console.log('🎮 Parsed card data:', cardData);
    } catch (err) {
        console.warn('Could not parse card data for drag:', err);
        console.warn('Raw data-card attribute:', cardButton.getAttribute('data-card'));
    }
    
    const dragString = `card:${cardIndex}:${cardType}`;
    
    // Store drag data globally for Mac compatibility
    window.currentDragData = dragString;
    
    e.dataTransfer.setData('text/plain', dragString);
    e.dataTransfer.setData('text', dragString); // Fallback for some browsers
    e.dataTransfer.effectAllowed = 'move';
    
    // Add visual feedback
    e.target.style.opacity = '0.5';
    
    console.log('🎮 Dragging card:', cardIndex, 'Type:', cardType, 'DragString:', dragString);
}

function handleDragEnd(e) {
    e.target.style.opacity = '1';
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function handleDragEnter(e) {
    e.preventDefault();
    e.target.closest('.trash-zone').classList.add('drag-over');
}

function handleDragLeave(e) {
    // Only remove class if we're actually leaving the trash zone
    if (!e.target.closest('.trash-zone').contains(e.relatedTarget)) {
        e.target.closest('.trash-zone').classList.remove('drag-over');
    }
}

function handleTrashDrop(e) {
    e.preventDefault();
    const dragData = e.dataTransfer.getData('text/plain');
    const trashZone = e.target.closest('.trash-zone');
    
    trashZone.classList.remove('drag-over');
    
    if (dragData.startsWith('card:')) {
        const cardIndex = dragData.split(':')[1];
        // Show confirmation
        if (confirm('🗑️ Delete this card from your hand?')) {
            deleteCard(parseInt(cardIndex));
        }
    }
}

// Equipment slot drag handlers
function handleEquipmentDragOver(e) {
    // Try to get drag data, use global fallback for Mac compatibility
    let dragData = e.dataTransfer.getData('text/plain') || e.dataTransfer.getData('text') || window.currentDragData || '';
    console.log('🎯 DragOver - dragData:', dragData);
    
    if (dragData.startsWith('card:')) {
        const [, cardIndex, cardType] = dragData.split(':');
        const slot = e.target.closest('.equipment-card');
        const slotType = slot ? slot.getAttribute('data-slot') : 'no-slot';
        
        console.log('🎯 DragOver - cardType:', cardType, 'slotType:', slotType);
        
        // Allow valid card types to be dropped on matching slots
        if ((cardType === slotType && (cardType === 'weapon' || cardType === 'armor')) ||
            (cardType === 'special attack' && slotType === 'weapon')) {
            console.log('✅ Valid drop allowed');
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        } else {
            console.log('❌ Invalid drop');
        }
    }
}

function handleEquipmentDragEnter(e) {
    const dragData = e.dataTransfer.getData('text/plain') || e.dataTransfer.getData('text') || window.currentDragData || '';
    if (dragData.startsWith('card:')) {
        const [, cardIndex, cardType] = dragData.split(':');
        const slot = e.target.closest('.equipment-card');
        const slotType = slot ? slot.getAttribute('data-slot') : 'no-slot';
        
        // Visual feedback for valid drops
        if ((cardType === slotType && (cardType === 'weapon' || cardType === 'armor')) ||
            (cardType === 'special attack' && slotType === 'weapon')) {
            e.preventDefault();
            slot.classList.add('valid-drop-target');
        } else {
            slot.classList.add('invalid-drop-target');
        }
    }
}

function handleEquipmentDragLeave(e) {
    const slot = e.target.closest('.equipment-card');
    if (slot && !slot.contains(e.relatedTarget)) {
        slot.classList.remove('valid-drop-target', 'invalid-drop-target');
    }
}

function handleEquipmentDrop(e) {
    console.log('🎯 Drop event triggered!');
    e.preventDefault();
    const dragData = e.dataTransfer.getData('text/plain') || e.dataTransfer.getData('text') || window.currentDragData || '';
    const slot = e.target.closest('.equipment-card');
    
    console.log('🎯 Drop - dragData:', dragData, 'slot:', slot);
    
    // Remove visual feedback
    if (slot) {
        slot.classList.remove('valid-drop-target', 'invalid-drop-target');
    }
    
    // Clear global drag data
    window.currentDragData = null;
    
    if (dragData.startsWith('card:')) {
        const [, cardIndex, cardType] = dragData.split(':');
        const slotType = slot ? slot.getAttribute('data-slot') : 'no-slot';
        const slotOwner = slot ? slot.getAttribute('data-owner') : 'no-owner';
        
        console.log(`🎯 Drop - Attempting to equip ${cardType} to ${slotOwner} ${slotType}`);
        
        // Handle equipment based on card and slot type
        if (cardType === 'special attack' && slotType === 'weapon') {
            // Special attacks go to weapon_special slot
            if (slotOwner === 'player') {
                console.log(`💥 Equipping player special attack from index ${cardIndex} to weapon`);
                equipCard(parseInt(cardIndex), 'weapon_special');
            } else if (slotOwner === 'enemy') {
                console.log(`💥 Assigning enemy special attack from index ${cardIndex} to weapon`);
                assignEnemyEquipment(parseInt(cardIndex), 'weapon_special');
            }
        } else if (cardType === slotType && (cardType === 'weapon' || cardType === 'armor')) {
            if (slotOwner === 'player') {
                console.log(`⚔️ Equipping player ${cardType} from index ${cardIndex} to ${slotType} slot`);
                equipCard(parseInt(cardIndex), cardType);
            } else if (slotOwner === 'enemy') {
                console.log(`⚔️ Assigning enemy ${cardType} from index ${cardIndex} to ${slotType} slot`);
                assignEnemyEquipment(parseInt(cardIndex), cardType);
            }
        } else {
            console.log(`❌ Cannot equip ${cardType} to ${slotType} slot`);
            showMessage(`❌ Cannot equip ${cardType} to ${slotType} slot`);
        }
    }
}

// ===================================================================
// ENEMY EQUIPMENT ASSIGNMENT FUNCTION
// ===================================================================
function assignEnemyEquipment(cardIndex, slotType) {
    console.log(`🎯 Assigning enemy equipment - card ${cardIndex} to ${slotType}`);
    
    // Get player hand from session to find the card
    const formData = new FormData();
    formData.append('action', 'assign_enemy_equipment');
    formData.append('card_index', cardIndex);
    formData.append('slot_type', slotType);
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('🎯 Enemy equipment assignment response:', data);
        if (data.success) {
            showMessage(`✅ Assigned enemy equipment: ${data.message || 'Equipment assigned'}`);
            // Reload page to update equipment display
            location.reload();
        } else {
            showMessage(`❌ Failed to assign enemy equipment: ${data.error || 'Unknown error'}`);
        }
    })
    .catch(error => {
        console.error('❌ Enemy equipment assignment error:', error);
        showMessage('❌ Error assigning enemy equipment');
    });
}

// Function to prevent dragging on specific elements
function preventDrag(element) {
    element.addEventListener('mousedown', function(e){
        e.stopPropagation(); // Stop the drag from starting
    });
}

// Apply to all delete buttons
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.card-delete-btn');
    deleteButtons.forEach(preventDrag);

    const unequipButtons = document.querySelectorAll('.equipment-unequip-btn');
    unequipButtons.forEach(preventDrag);
});

// ===================================================================
// ACTION BAR STATE MACHINE
// ===================================================================
function updateActionBar(state) {
    const textEl = document.getElementById('actionBarText');
    const buttonsEl = document.getElementById('actionBarButtons');
    buttonsEl.innerHTML = ''; // Clear existing buttons

    switch (state) {
        case 'combat_confirm':
            textEl.textContent = 'You are preparing to attack. Are you sure?';
            buttonsEl.innerHTML = `
                <button onclick="performCombatAction('attack_enemy').then((data) => { if (!data.data.gameOver) updateActionBar('main'); })" class="action-btn attack-btn">Confirm Attack</button>
                <button onclick="updateActionBar('main')" class="action-btn defend-btn">Cancel</button>
            `;
            break;
        case 'enemy_turn':
            textEl.textContent = 'The enemy is taking its turn...';
            // No buttons during enemy turn
            break;
        case 'player_wins':
            window.gameState = 'player_wins';
            textEl.textContent = 'VICTORY! You have defeated the enemy.';
            buttonsEl.innerHTML = `<form method="post"><button type="submit" name="reset_all" class="action-btn reset-btn">🎉 Play Again</button></form>`;
            NarrativeGuide.show('happy', 'Heh. Not bad, kid. Not bad at all.');
            break;
        case 'enemy_wins':
            window.gameState = 'enemy_wins';
            textEl.textContent = 'DEFEAT! Your mech has been destroyed.';
            buttonsEl.innerHTML = `<form method="post"><button type="submit" name="reset_all" class="action-btn reset-btn">⚡ Try Again</button></form>`;
            NarrativeGuide.show('disappointed', 'Simulation failed. Resetting. Every pilot gets knocked down.');
            break;
        case 'enemy_config':
            textEl.textContent = 'Configure enemy equipment. Choose weapons and armor.';
            NarrativeGuide.show('thoughtful', 'Let\'s set up our opponent. Make it challenging, but fair.');
            buttonsEl.innerHTML = `
                <div class="config-section">
                    <div class="config-row">
                        <button onclick="clearEnemyEquipment()" class="action-btn clear-btn" title="Remove all enemy equipment">🗑️ Clear All</button>
                        <button onclick="randomEnemyLoadout()" class="action-btn random-btn" title="Give enemy random equipment">🎲 Randomize</button>
                    </div>
                    <div class="config-info">
                        <small>Drag weapon/armor cards from your hand to enemy slots, or use buttons above</small>
                    </div>
                    <button onclick="updateActionBar('main')" class="action-btn back-btn">↩️ Back to Combat</button>
                </div>
            `;
            break;
        case 'main':
        default:
            textEl.textContent = 'Your move. Play a card or prepare an attack.';
            buttonsEl.innerHTML = `
                <button onclick="updateActionBar('combat_confirm')" class="action-btn attack-btn">⚔️ Attack</button>
                <button onclick="endTurn()" id="endTurnBtn" class="action-btn">➡️ End Turn</button>
                <button onclick="updateActionBar('enemy_config')" class="action-btn config-btn" title="Configure enemy equipment">⚙️ Enemy Gear</button>
            `;
            break;
    }
}

// ===================================================================
// TURN-BASED SYSTEM FUNCTIONS
// ===================================================================
function endTurn() {
    // Set turn to enemy
    window.currentPlayerTurn = 'enemy';
    
    updateActionBar('enemy_turn');
    showTurnIndicator('enemy');
    NarrativeGuide.show('serious', 'Hmph. Let\'s see what this machine has planned.');

    fetch('combat-manager.php', {
        method: 'POST',
        body: new URLSearchParams('action=end_turn')
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            processAIActions(data.data.ai_actions, () => {
                // This callback runs after all AI actions are visualized
                updateCombatUI(data.data);
                updateEnergyDisplay(data.data.playerEnergy);
                showTurnIndicator('player');
                
                // Set turn back to player
                window.currentPlayerTurn = 'player';
                
                NarrativeGuide.show('thoughtful', 'Alright, our turn. Full energy. Make it count.');
                
                // Check for game over conditions after AI turn
                if (data.data.gameOver) {
                    if (data.data.gameOver === 'player_wins') {
                        updateActionBar('player_wins');
                    } else if (data.data.gameOver === 'enemy_wins') {
                        updateActionBar('enemy_wins');
                    }
                } else {
                    updateActionBar('main');
                }
            });
        } else {
            showMessage('An error occurred during the AI turn.', 'error');
            showTurnIndicator('player');
            updateActionBar('main');
        }
    });
}

function processAIActions(actions, finalCallback) {
    if (!actions || actions.length === 0) {
        if (finalCallback) finalCallback();
        return;
    }

    const action = actions.shift(); // Process one action at a time
    let delay = 1500; // 1.5 second delay between actions

    if (action.type === 'play_card') {
        showCombatMessage(`AI plays ${action.card.name}!`, 'error');
    } else if (action.type === 'attack') {
        showCombatMessage(`AI attacks for ${action.damage} damage!`, 'error');
    }

    // Fetch the very latest game state to update UI mid-turn
    fetch('combat-manager.php', { 
        method: 'POST', 
        body: new URLSearchParams('action=get_combat_status')
    })
    .then(res => res.json())
    .then(status => {
        if(status.success) updateCombatUI(status.data);
    });

    setTimeout(() => {
        processAIActions(actions, finalCallback); // Process next action
    }, delay);
}

function playCardFromHand(cardIndex) {
    // Validate player action
    if (!validatePlayerAction('play card')) {
        return;
    }
    
    CardZoom.closeZoomModal(); // Close the modal first

    fetch('combat-manager.php', {
        method: 'POST',
        body: new URLSearchParams(`action=play_card&card_index=${cardIndex}`)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // SUCCESS: Update UI dynamically
            updateEnergyDisplay(data.data.playerEnergy);
            // Re-render the player's hand
            renderPlayerHand(data.data.playerHand); 
            showMessage(data.message, 'success');
            
            // Check for game over conditions
            if (data.data && data.data.gameOver) {
                if (data.data.gameOver === 'player_wins') {
                    updateActionBar('player_wins');
                } else if (data.data.gameOver === 'enemy_wins') {
                    updateActionBar('enemy_wins');
                }
            }
        } else {
            showMessage('Error: ' + data.message, 'error');
        }
    });
}

// NEW FUNCTION: To dynamically render the hand (matching original clean design)
function renderPlayerHand(hand) {
    const handContainer = document.querySelector('.player-hand-section .hand-cards-fan');
    if (!handContainer) return;
    
    handContainer.innerHTML = ''; // Clear current hand
    
    if (hand.length === 0) {
        handContainer.innerHTML = '<div class="hand-empty-message"><div class="empty-hand-text">Click deck to draw cards</div></div>';
    } else {
        hand.forEach((card, index) => {
            // Build the card HTML string dynamically based on ORIGINAL clean structure
            const cardElement = card.element || 'fire';
            const cardRarity = card.rarity || 'common';
            const hasImage = card.image && card.image.length > 0;
            const backgroundStyle = hasImage ? `style="background-image: url('${card.image}');"` : '';
            const imageClass = hasImage ? ' has-image' : '';
            
            // Type icons mapping (same as original)
            const typeIcons = {
                'spell': '✨',
                'weapon': '⚔️', 
                'armor': '🛡️',
                'creature': '👾',
                'support': '🔧',
                'special attack': '💥'
            };
            
            const cardHTML = `
                <div class="hand-card-container" style="--card-index: ${index}">
                    <button type="button" onclick="handleCardClick(${index}, '${card.type}')" 
                            class="hand-card face-up fan-card ${card.type}-card ${cardElement}-element ${cardRarity}-rarity${imageClass}" 
                            data-card='${JSON.stringify(card).replace(/'/g, "&apos;")}' 
                            draggable="true" ${backgroundStyle}>
                        <div class="card-mini-name">${card.name}</div>
                        <div class="card-mini-cost">${card.cost}</div>
                        ${!hasImage ? `<div class="card-type-icon">${typeIcons[card.type] || '❓'}</div>` : ''}
                        ${(card.damage && card.damage > 0) ? `<div class="card-mini-damage">💥${card.damage}</div>` : ''}
                    </button>
                </div>
            `;
            handContainer.innerHTML += cardHTML;
        });
    }
    
    // Update hand count display
    const handLabel = document.querySelector('.player-hand-section .hand-label');
    if (handLabel) {
        const maxHandSize = <?= $gameRules['max_hand_size'] ?>;
        handLabel.textContent = `Your Hand (${hand.length}/${maxHandSize})`;
    }
    
    // Re-attach drag and drop event handlers to newly created cards
    const newCards = handContainer.querySelectorAll('.hand-card');
    newCards.forEach(card => {
        card.addEventListener('dragstart', handleCardDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });
}

// Modify the existing card click handler
function handleCardClick(cardIndex, cardType) {
    const cardElement = document.querySelector(`.hand-card-container[style*="--card-index: ${cardIndex}"] .hand-card`);
    if (!cardElement) return;

    const cardData = JSON.parse(cardElement.dataset.card);
    CardZoom.showZoomModal(cardData, false, cardIndex); // Pass cardIndex to the modal
}

// Helper functions for UI updates
function updateEnergyDisplay(newEnergy) {
    const energyElement = document.getElementById('playerEnergyValue');
    if (energyElement) {
        energyElement.textContent = newEnergy;
    }
}

function showTurnIndicator(player) {
    const indicator = document.getElementById('turnIndicator');
    if (indicator) {
        if (player === 'player') {
            indicator.textContent = "PLAYER'S TURN";
            indicator.className = 'turn-indicator player-turn';
        } else {
            indicator.textContent = "ENEMY'S TURN";
            indicator.className = 'turn-indicator enemy-turn';
        }
    }
}

// ===================================================================
// HELP MODAL FUNCTIONS
// ===================================================================
function showHelpModal() {
    const modal = document.getElementById('helpModal');
    const overlay = document.getElementById('helpModalOverlay');
    
    if (modal && overlay) {
        modal.classList.add('active');
        overlay.classList.add('active');
        
        // Prevent body scrolling when modal is open
        document.body.style.overflow = 'hidden';
        
        // Add escape key listener
        document.addEventListener('keydown', handleHelpEscKey);
    }
}

function closeHelpModal() {
    const modal = document.getElementById('helpModal');
    const overlay = document.getElementById('helpModalOverlay');
    
    if (modal && overlay) {
        modal.classList.remove('active');
        overlay.classList.remove('active');
        
        // Restore body scrolling
        document.body.style.overflow = '';
        
        // Remove escape key listener
        document.removeEventListener('keydown', handleHelpEscKey);
    }
}

function handleHelpEscKey(event) {
    if (event.key === 'Escape') {
        closeHelpModal();
    }
}

// Initialize Action Bar on page load
updateActionBar('main');

// Global game state tracking
window.gameState = 'active'; // Can be 'active', 'player_wins', 'enemy_wins'
window.currentPlayerTurn = 'player'; // Can be 'player' or 'enemy'
</script>

</body>
</html>