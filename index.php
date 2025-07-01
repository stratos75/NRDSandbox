<?php
require 'auth.php';

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
                <div class='equipment-card {$slotType}-card {$owner}-equipment {$slotClass}' data-slot='{$slotType}'>
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
                    <button type='submit' name='equipment_click' value='{$owner} {$slotType}' class='equipment-card {$slotType}-card {$owner}-equipment equipped {$element}-element {$rarity}-rarity{$imageClass}' data-slot='{$slotType}' {$backgroundImage}>
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

    <!-- ===================================================================
         TOP NAVIGATION BAR (UPDATED - Clean Navigation)
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <button type="button" class="config-link debug-toggle-btn" onclick="toggleDebugPanel()">🐛 Debug</button>
            <button type="button" class="config-link card-creator-btn" onclick="toggleCardCreator()">🃏 Card Creator</button>
            <a href="config/mechs.php" class="config-link">🤖 Mech Config</a>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">NRD TACTICAL SANDBOX</h1>
        </div>
        <div class="nav-right">
            <a href="config/" class="version-badge" title="Open Control Dashboard">v0.9.3+</a>
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
                            <div class="stat">ATK: <?= $enemyMech['ATK'] ?></div>
                            <div class="stat">DEF: <?= $enemyMech['DEF'] ?></div>
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
            <div class="divider-line"></div>
            <div class="divider-label">COMBAT ZONE</div>
        </div>

        <!-- PLAYER SECTION (BOTTOM) -->
        <section class="combat-zone player-zone">
            <div class="zone-label">PLAYER TERRITORY</div>
            
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
                            <div class="stat">ATK: <?= $playerMech['ATK'] ?></div>
                            <div class="stat">DEF: <?= $playerMech['DEF'] ?></div>
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

    </main>

    <!-- ===================================================================
         GAME CONTROLS PANEL (CONVERTED TO AJAX)
         =================================================================== -->
    <section class="controls-panel">
        <div class="control-group">
            <h3>Combat Actions</h3>
            <div class="action-buttons">
                <button type="button" onclick="performCombatAction('attack_enemy')" class="action-btn attack-btn">
                    ⚔️ Attack Enemy
                </button>
                <button type="button" onclick="performCombatAction('enemy_attack')" class="action-btn defend-btn">
                    🛡️ Enemy Attacks
                </button>
                <button type="button" onclick="performCombatAction('reset_mechs')" class="action-btn reset-btn">
                    🔄 Reset Mechs
                </button>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            NRD Tactical Sandbox | Build v0.9.3+ | Enhanced JSON System + Debug Panel
        </div>
    </footer>

</div>

<!-- ===================================================================
     DEBUG PANEL (SLIDE-IN FROM LEFT)
     =================================================================== -->
<div id="debugPanel" class="debug-panel">
    <div class="debug-header">
        <h2>🐛 Debug Console</h2>
        <button type="button" class="close-btn" onclick="toggleDebugPanel()">✕</button>
    </div>
    
    <div class="debug-content">
        
        <!-- System Status Section -->
        <div class="debug-section">
            <h3>📊 System Status</h3>
            <div class="status-grid">
                <?php foreach ($debugInfo as $info): ?>
                    <div class="status-item">
                        <span class="status-value"><?= htmlspecialchars($info) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Current Game State -->
        <div class="debug-section">
            <h3>🎮 Current Game State</h3>
            <div class="game-state-grid">
                <div class="state-card player-state">
                    <div class="state-header">Player Mech</div>
                    <div class="state-stats">
                        <div class="stat-line">HP: <span id="debugPlayerHP"><?= $playerMech['HP'] ?></span>/<?= $playerMech['MAX_HP'] ?></div>
                        <div class="stat-line">ATK: <?= $playerMech['ATK'] ?></div>
                        <div class="stat-line">DEF: <?= $playerMech['DEF'] ?></div>
                    </div>
                </div>
                <div class="state-card enemy-state">
                    <div class="state-header">Enemy Mech</div>
                    <div class="state-stats">
                        <div class="stat-line">HP: <span id="debugEnemyHP"><?= $enemyMech['HP'] ?></span>/<?= $enemyMech['MAX_HP'] ?></div>
                        <div class="stat-line">ATK: <?= $enemyMech['ATK'] ?></div>
                        <div class="stat-line">DEF: <?= $enemyMech['DEF'] ?></div>
                    </div>
                </div>
            </div>
            <div class="hand-status">
                <div class="hand-info">
                    <span>Player Hand: <?= count($playerHand) ?> cards</span>
                    <span>Card Library: <?= count($cardLibrary) ?> cards</span>
                </div>
            </div>
        </div>

        <!-- Reset Controls -->
        <div class="debug-section">
            <h3>🔄 Reset Controls</h3>
            <div class="reset-controls">
                <form method="post" style="display: inline;">
                    <button type="submit" name="reset_mechs" value="1" class="debug-btn reset-health">
                        ❤️ Reset Mech Health
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <button type="submit" name="reset_hands" value="1" class="debug-btn reset-hands">
                        🃏 Reset Card Hands
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <button type="submit" name="clear_log" value="1" class="debug-btn clear-log">
                        📝 Clear Game Log
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <button type="submit" name="reset_all" value="1" class="debug-btn reset-all">
                        🔄 Reset Everything
                    </button>
                </form>
            </div>
        </div>

        <!-- Action Log -->
        <div class="debug-section">
            <h3>📝 Action Log</h3>
            <div class="action-log" id="debugActionLog">
                <?php if (!empty($gameLog)): ?>
                    <?php foreach (array_slice($gameLog, -10) as $logEntry): ?>
                        <div class="log-entry"><?= htmlspecialchars($logEntry) ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="log-entry">No actions yet</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Technical Info -->
        <div class="debug-section">
            <h3>⚙️ Technical Info</h3>
            <div class="tech-info">
                <div class="tech-item">
                    <span class="tech-label">Version:</span>
                    <span class="tech-value">v0.9.3+</span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">PHP Session ID:</span>
                    <span class="tech-value"><?= substr(session_id(), 0, 8) ?>...</span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">Equipment Status:</span>
                    <span class="tech-value">
                        W:<?= $playerEquipment['weapon'] ? '✓' : '✗' ?> 
                        A:<?= $playerEquipment['armor'] ? '✓' : '✗' ?>
                    </span>
                </div>
                <div class="tech-item">
                    <span class="tech-label">Combat System:</span>
                    <span class="tech-value">AJAX v2</span>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Debug Panel Overlay -->
<div id="debugOverlay" class="debug-overlay" onclick="toggleDebugPanel()"></div>

<!-- Hidden forms for card actions -->
<form id="equipmentForm" method="post" style="display: none;">
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
     CARD CREATOR PANEL (SLIDE-IN FROM RIGHT)
     =================================================================== -->
<div id="cardCreatorPanel" class="card-creator-panel">
    <div class="card-creator-header">
        <h2>🃏 Card Creator</h2>
        <button type="button" class="close-btn" onclick="toggleCardCreator()">✕</button>
    </div>
    
    <div class="card-creator-content">
        <!-- Card Form -->
        <div class="card-form-section">
            <h3>Card Properties</h3>
            <form id="cardCreatorForm" class="card-form">
                <div class="form-group">
                    <label for="cardName">Card Name:</label>
                    <input type="text" id="cardName" name="cardName" placeholder="Plasma Rifle" oninput="updateCardPreview()">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardCost">Cost:</label>
                        <input type="number" id="cardCost" name="cardCost" min="0" max="10" value="3" oninput="updateCardPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="cardType">Type:</label>
                        <select id="cardType" name="cardType" onchange="updateCardPreview()">
                            <option value="weapon">⚔️ Weapon</option>
                            <option value="armor">🛡️ Armor</option>
                            <option value="special attack">💥 Special Attack</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardDamage">Damage:</label>
                        <input type="number" id="cardDamage" name="cardDamage" min="0" max="50" value="5" oninput="updateCardPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="cardRarity">Rarity:</label>
                        <select id="cardRarity" name="cardRarity" onchange="updateCardPreview()">
                            <option value="common">Common</option>
                            <option value="uncommon">Uncommon</option>
                            <option value="rare">Rare</option>
                            <option value="epic">Epic</option>
                            <option value="legendary">Legendary</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardElement">Element:</label>
                        <select id="cardElement" name="cardElement" onchange="updateCardPreview()">
                            <option value="fire">🔥 Fire</option>
                            <option value="ice">🧊 Ice</option>
                            <option value="poison">🧪 Poison</option>
                            <option value="plasma">⚡ Plasma</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <!-- Empty space for balanced layout -->
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cardDescription">Description:</label>
                    <textarea id="cardDescription" name="cardDescription" placeholder="High-powered energy weapon with devastating firepower..." oninput="updateCardPreview()"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="cardImage">Card Image:</label>
                    <input type="file" id="cardImage" name="cardImage" accept="image/*" onchange="handleImageUpload(this)">
                    <div class="image-upload-hint">
                        Recommended: 300x400px (PNG/JPG, max 2MB)
                    </div>
                    <div id="imagePreview" class="image-preview" style="display: none;">
                        <img id="previewImg" src="" alt="Card Image Preview">
                        <button type="button" onclick="removeImage()" class="remove-image-btn">✕ Remove</button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="action-btn reset-btn" onclick="resetCardForm()">🔄 Reset</button>
                    <button type="button" class="action-btn attack-btn" onclick="saveCard()">💾 Save Card</button>
                </div>
            </form>
        </div>
        
        <!-- Card Preview -->
        <div class="card-preview-section">
            <h3>Live Preview</h3>
            <div class="card-preview-container">
                <div id="cardPreview" class="preview-card weapon-card fire-element common-rarity">
                    <div class="preview-cost">3</div>
                    <div class="preview-name">New Weapon</div>
                    <div class="preview-type">WEAPON</div>
                    <div class="preview-art" id="previewArt">
                        <div id="previewArtPlaceholder">[Art]</div>
                        <img id="previewArtImage" src="" alt="Card Art" style="display: none;">
                    </div>
                    <div class="preview-damage">💥 5</div>
                    <div class="preview-description">Deal damage to target enemy...</div>
                    <div class="preview-rarity common-rarity">Common</div>
                </div>
            </div>
        </div>
        
        <!-- Card Library -->
        <div class="card-library-section">
            <h3>Your Card Library</h3>
            <div class="library-stats">
                <span id="cardCount">0 cards</span> | 
                <button type="button" onclick="loadCardLibrary()" class="refresh-btn">🔄 Refresh</button>
            </div>
            <div id="cardLibrary" class="card-library">
                <div class="library-empty">No cards created yet. Create your first card above!</div>
            </div>
        </div>
    </div>
</div>

<!-- Card Creator Overlay -->
<div id="cardCreatorOverlay" class="card-creator-overlay" onclick="toggleCardCreator()"></div>

<script>
// ===================================================================
// COMBAT SYSTEM AJAX FUNCTIONS (RESTORED)
// ===================================================================
function performCombatAction(action) {
    // Disable buttons during request to prevent double-clicks
    const buttons = document.querySelectorAll('.action-btn');
    buttons.forEach(btn => btn.disabled = true);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', action);
    
    // Send AJAX request to combat manager
    fetch('combat-manager.php', {
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
            
            // Show brief success message
            showCombatMessage(data.message, 'success');
        } else {
            // Show error message
            showCombatMessage('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Combat action error:', error);
        showCombatMessage('Network error during combat action', 'error');
    })
    .finally(() => {
        // Re-enable buttons
        buttons.forEach(btn => btn.disabled = false);
    });
}

function updateCombatUI(combatData) {
    const { playerMech, enemyMech } = combatData;
    
    // Update Player HP displays
    const playerHPValue = document.getElementById('playerHPValue');
    const playerHPDisplay = document.getElementById('playerHPDisplay');
    const debugPlayerHP = document.getElementById('debugPlayerHP');
    if (playerHPValue) playerHPValue.textContent = playerMech.HP;
    if (playerHPDisplay) playerHPDisplay.textContent = playerMech.HP;
    if (debugPlayerHP) debugPlayerHP.textContent = playerMech.HP;
    
    // Update Enemy HP displays
    const enemyHPValue = document.getElementById('enemyHPValue');
    const enemyHPDisplay = document.getElementById('enemyHPDisplay');
    const debugEnemyHP = document.getElementById('debugEnemyHP');
    if (enemyHPValue) enemyHPValue.textContent = enemyMech.HP;
    if (enemyHPDisplay) enemyHPDisplay.textContent = enemyMech.HP;
    if (debugEnemyHP) debugEnemyHP.textContent = enemyMech.HP;
    
    // Update mech card status classes
    updateMechStatusClass('playerMechCard', playerMech.HP, playerMech.MAX_HP);
    updateMechStatusClass('enemyMechCard', enemyMech.HP, enemyMech.MAX_HP);
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
    // Set the form values
    document.getElementById('equipCardIndex').value = cardIndex;
    document.getElementById('equipSlot').value = cardType;
    
    // Submit the form
    document.getElementById('equipmentForm').submit();
}

function deleteCard(cardIndex) {
    if (confirm('Remove this card from your hand?')) {
        // Set the form values
        document.getElementById('deleteCardIndex').value = cardIndex;
        
        // Submit the form
        document.getElementById('deleteCardForm').submit();
    }
}

// ===================================================================
// DEBUG PANEL FUNCTIONS
// ===================================================================
function toggleDebugPanel() {
    const panel = document.getElementById('debugPanel');
    const overlay = document.getElementById('debugOverlay');
    
    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        panel.classList.add('active');
        overlay.classList.add('active');
    }
}

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

// ===================================================================
// CARD CREATOR JAVASCRIPT FUNCTIONS
// ===================================================================
function toggleCardCreator() {
    const panel = document.getElementById('cardCreatorPanel');
    const overlay = document.getElementById('cardCreatorOverlay');
    
    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        panel.classList.add('active');
        overlay.classList.add('active');
    }
}

function updateCardPreview() {
    const preview = document.getElementById('cardPreview');
    const name = document.getElementById('cardName').value || 'New Card';
    const cost = document.getElementById('cardCost').value || '0';
    const type = document.getElementById('cardType').value || 'weapon';
    const damage = document.getElementById('cardDamage').value || '0';
    const description = document.getElementById('cardDescription').value || 'Card description...';
    const rarity = document.getElementById('cardRarity').value || 'common';
    const element = document.getElementById('cardElement').value || 'fire';
    
    // Update preview card
    preview.querySelector('.preview-cost').textContent = cost;
    preview.querySelector('.preview-name').textContent = name;
    preview.querySelector('.preview-type').textContent = type.toUpperCase();
    preview.querySelector('.preview-damage').textContent = damage > 0 ? `💥 ${damage}` : '';
    preview.querySelector('.preview-description').textContent = description;
    preview.querySelector('.preview-rarity').textContent = rarity.charAt(0).toUpperCase() + rarity.slice(1);
    
    // Update card styling based on type, element, and rarity
    preview.className = `preview-card ${type}-card ${element}-element ${rarity}-rarity`;
    preview.querySelector('.preview-rarity').className = `preview-rarity ${rarity}-rarity`;
}

// Image upload handling functions
let uploadedImageData = null;

function handleImageUpload(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file (PNG, JPG, etc.)');
        input.value = '';
        return;
    }
    
    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        alert('Image file is too large. Please select an image smaller than 2MB.');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const imageData = e.target.result;
        uploadedImageData = imageData;
        
        // Show preview in form
        const previewImg = document.getElementById('previewImg');
        const imagePreview = document.getElementById('imagePreview');
        previewImg.src = imageData;
        imagePreview.style.display = 'block';
        
        // Update card preview
        updateCardPreviewImage(imageData);
    };
    reader.readAsDataURL(file);
}

function updateCardPreviewImage(imageData) {
    const previewCard = document.getElementById('cardPreview');
    const previewArtPlaceholder = document.getElementById('previewArtPlaceholder');
    
    if (imageData) {
        // Set background image and add has-image class
        previewCard.style.backgroundImage = `url(${imageData})`;
        previewCard.classList.add('has-image');
        previewArtPlaceholder.style.display = 'none';
    } else {
        // Remove background image and has-image class
        previewCard.style.backgroundImage = '';
        previewCard.classList.remove('has-image');
        previewArtPlaceholder.style.display = 'block';
    }
}

function removeImage() {
    uploadedImageData = null;
    document.getElementById('cardImage').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    updateCardPreviewImage(null);
}

function resetCardForm() {
    document.getElementById('cardCreatorForm').reset();
    document.getElementById('cardCost').value = '3';
    document.getElementById('cardDamage').value = '5';
    
    // Reset image upload
    uploadedImageData = null;
    document.getElementById('imagePreview').style.display = 'none';
    updateCardPreviewImage(null);
    
    updateCardPreview();
}

function saveCard() {
    const cardData = {
        name: document.getElementById('cardName').value,
        cost: document.getElementById('cardCost').value,
        type: document.getElementById('cardType').value,
        damage: document.getElementById('cardDamage').value,
        description: document.getElementById('cardDescription').value,
        rarity: document.getElementById('cardRarity').value,
        element: document.getElementById('cardElement').value
    };
    
    // Validate required fields
    if (!cardData.name.trim()) {
        alert('Please enter a card name');
        return;
    }
    
    // Prepare form data for submission
    const formData = new FormData();
    
    // Check if we're editing an existing card
    if (window.editingCardId) {
        formData.append('action', 'update_card');
        formData.append('card_id', window.editingCardId);
    } else {
        formData.append('action', 'create_card');
    }
    
    formData.append('name', cardData.name);
    formData.append('cost', cardData.cost);
    formData.append('type', cardData.type);
    formData.append('damage', cardData.damage);
    formData.append('description', cardData.description);
    formData.append('rarity', cardData.rarity);
    formData.append('element', cardData.element);
    
    // Add image data if available
    if (uploadedImageData) {
        formData.append('image_data', uploadedImageData);
        console.log('📸 Image data attached to save request:', uploadedImageData.length, 'characters');
    } else {
        console.log('❌ No image data to save');
    }
    
    // Send to server
    fetch('card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const actionText = window.editingCardId ? 'updated' : 'created';
            showMessage(`✅ Card ${actionText} successfully: ${data.data.name}`);
            addLogEntry(`Card ${actionText}: ${data.data.name}`);
            
            // Clear editing mode
            window.editingCardId = null;
            
            // Reset form after successful save
            resetCardForm();
            
            // Update card library without reloading page
            loadCardLibrary();
            
            // Show success feedback
            console.log('✅ Card saved successfully:', data.data);
        } else {
            alert('Error saving card: ' + data.message);
            console.error('❌ Card save failed:', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error while saving card. Please try again.');
    });
}

function loadCardLibrary() {
    // Load saved cards from server
    const formData = new FormData();
    formData.append('action', 'get_all_cards');
    
    fetch('card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCardLibrary(data.data);
            updateCardCount(data.data.length);
        } else {
            document.getElementById('cardLibrary').innerHTML = 
                '<div class="library-error">Error: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Error loading cards:', error);
        document.getElementById('cardLibrary').innerHTML = 
            '<div class="library-error">Network error loading cards</div>';
    });
}

function displayCardLibrary(cards) {
    const libraryContainer = document.getElementById('cardLibrary');
    
    if (!cards || cards.length === 0) {
        libraryContainer.innerHTML = '<div class="library-empty">No cards created yet. Create your first card above!</div>';
        return;
    }
    
    let html = '';
    cards.forEach(card => {
        const imageContent = card.image ? 
            `<img src="${card.image}" alt="${card.name}" class="library-card-image" loading="lazy">` :
            `<div class="library-card-icon">${getTypeIcon(card.type)}</div>`;
            
        html += `
            <div class="library-card ${card.type}-card" data-card-id="${card.id}">
                <div class="library-card-header">
                    <span class="library-card-name">${card.name}</span>
                    <span class="library-card-cost">${card.cost}</span>
                </div>
                <div class="library-card-visual">
                    ${imageContent}
                </div>
                <div class="library-card-type">${card.type.toUpperCase()}</div>
                <div class="library-card-damage">${card.damage > 0 ? '💥 ' + card.damage : ''}</div>
                <div class="library-card-description">${card.description}</div>
                <div class="library-card-footer">
                    <span class="library-card-rarity ${card.rarity}-rarity">${card.rarity}</span>
                    <div class="library-card-actions">
                        <button onclick="editCard('${card.id}')" class="edit-btn">✏️</button>
                        <button onclick="deleteLibraryCard('${card.id}')" class="delete-btn">🗑️</button>
                    </div>
                </div>
            </div>
        `;
    });
    
    libraryContainer.innerHTML = html;
}

function updateCardCount(count) {
    document.getElementById('cardCount').textContent = count + (count === 1 ? ' card' : ' cards');
}

function getTypeIcon(type) {
    const icons = {
        'spell': '✨',
        'weapon': '⚔️', 
        'armor': '🛡️',
        'creature': '👾',
        'support': '🔧'
    };
    return icons[type] || '❓';
}


function editCard(cardId) {
    // Load card data from server and populate form
    const formData = new FormData();
    formData.append('action', 'get_card');
    formData.append('card_id', cardId);
    
    fetch('card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(responseText => {
        try {
            const data = JSON.parse(responseText);
            return data;
        } catch (e) {
            console.error('EditCard: JSON parse error:', e);
            console.error('EditCard: Response was:', responseText.substring(0, 200));
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success && data.data) {
            const card = data.data;
            
            // Populate form fields
            document.getElementById('cardName').value = card.name || '';
            document.getElementById('cardCost').value = card.cost || 0;
            document.getElementById('cardType').value = card.type || 'weapon';
            document.getElementById('cardDamage').value = card.damage || 0;
            document.getElementById('cardDescription').value = card.description || '';
            document.getElementById('cardRarity').value = card.rarity || 'common';
            document.getElementById('cardElement').value = card.element || 'fire';
            
            // Set editing mode flag
            window.editingCardId = cardId;
            
            // Update card preview
            updateCardPreview();
            
            // Show card creator panel if not already visible
            const panel = document.getElementById('cardCreatorPanel');
            if (!panel.classList.contains('active')) {
                toggleCardCreator();
            }
            
            // Scroll to top of panel
            panel.scrollTop = 0;
            
            // Show editing indicator
            showMessage(`📝 Editing card: ${card.name}`);
            addLogEntry(`Started editing card: ${card.name}`);
            
        } else {
            if (data.error_type === 'auth_required') {
                alert('Your session has expired. Please log in again.');
                window.location.href = 'login.php';
            } else {
                alert('Error loading card: ' + (data.message || 'Card not found'));
            }
        }
    })
    .catch(error => {
        console.error('EditCard error:', error.message);
        alert('Network error while loading card for editing: ' + error.message);
    });
}

function deleteLibraryCard(cardId) {
    if (confirm('Are you sure you want to delete this card from the library?')) {
        const formData = new FormData();
        formData.append('action', 'delete_card');
        formData.append('card_id', cardId);
        
        fetch('card-manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCardLibrary(); // Refresh the library
                // Reload page to update hand display
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Error deleting card: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error while deleting card.');
        });
    }
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCardPreview();
    loadCardLibrary(); // Load existing cards when page loads
});
/// ===================================================================
// CARD ZOOM MODAL SYSTEM - Add this to your <script> section in index.php
// ===================================================================

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
    showZoomModal(cardElement) {
        if (this.isOpen) return;

        try {
            const cardData = JSON.parse(cardElement.getAttribute('data-card'));
            this.createZoomModal(cardData);
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
    createZoomModal(cardData, isEquipment = false) {
        // Remove existing modal if any
        this.closeZoomModal();

        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.className = 'card-zoom-overlay';
        overlay.innerHTML = this.generateZoomHTML(cardData, isEquipment);

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
    generateZoomHTML(cardData, isEquipment = false) {
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
            'special attack': '💥'
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
// UNIFIED CARD INTERACTION SYSTEM
// ===================================================================
function handleCardClick(cardIndex, cardType) {
    console.log(`Card clicked: Index ${cardIndex}, Type: ${cardType}`);
    
    // ALWAYS show zoom modal for ALL cards to view details
    const cardElement = document.querySelector(`[data-card][style*="--card-index: ${cardIndex}"]`);
    if (cardElement) {
        CardZoom.showZoomModal(cardElement);
    } else {
        console.warn(`Card element not found for index ${cardIndex}`);
    }
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
    });
} else {
    CardZoom.init();
    initDragAndDrop();
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
        
        console.log(`🎯 Drop - Attempting to equip ${cardType} to ${slotType}`);
        
        // Handle equipment based on card and slot type
        if (cardType === 'special attack' && slotType === 'weapon') {
            // Special attacks go to weapon_special slot
            console.log(`💥 Equipping special attack from index ${cardIndex} to weapon`);
            equipCard(parseInt(cardIndex), 'weapon_special');
        } else if (cardType === slotType && (cardType === 'weapon' || cardType === 'armor')) {
            console.log(`⚔️ Equipping ${cardType} from index ${cardIndex} to ${slotType} slot`);
            equipCard(parseInt(cardIndex), cardType);
        } else {
            console.log(`❌ Cannot equip ${cardType} to ${slotType} slot`);
            showMessage(`❌ Cannot equip ${cardType} to ${slotType} slot`);
        }
    }
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
</script>

</body>
</html>