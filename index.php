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
// ===================================================================
// COMPANION SYSTEM DATA
// ===================================================================
$companionLibrary = [
    'Jack' => [
        'name' => 'Jack',
        'full_name' => 'Jack the Super-Intelligent Terrier',
        'type' => 'enhanced_animal',
        'energy_bonus' => 1,        // +1 energy per turn
        'atk_bonus' => 3,           // +3 attack
        'def_bonus' => 2,           // +2 defense  
        'heal_per_turn' => 0,       // No healing
        'damage_reduction' => 5,    // 5% damage reduction
        'special_ability' => 'tactical_analysis', // Once per turn: see enemy hand
        'synergy_element' => null,  // No element synergy
        'description' => 'Hyperactive genius provides tactical analysis and energy efficiency',
        'image' => 'images/companions/companion_pilot_jack.png'
    ],
    'AI-Core' => [
        'name' => 'AI-Core',
        'full_name' => 'Tactical AI Core',
        'type' => 'artificial_intelligence',
        'energy_bonus' => 0,        // No energy bonus
        'atk_bonus' => 2,           // +2 attack
        'def_bonus' => 4,           // +4 defense
        'heal_per_turn' => 1,       // +1 HP per turn
        'damage_reduction' => 0,    // No damage reduction
        'special_ability' => 'shield_boost', // Once per turn: temporary defense
        'synergy_element' => 'plasma', // Synergy with plasma weapons
        'description' => 'Advanced AI provides defensive calculations and regenerative protocols',
        'image' => 'images/companions/companion_ai_core.png'
    ]
];

$defaultPlayerMech = [
    'HP' => 75, 
    'ATK' => 30, 
    'DEF' => 15, 
    'MAX_HP' => 75, 
    'companion' => 'Jack',
    'name' => 'Player Mech',
    'image' => null
];
$defaultEnemyMech = [
    'HP' => 75, 
    'ATK' => 25, 
    'DEF' => 10, 
    'MAX_HP' => 75, 
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

// Tutorial state tracking
$tutorialState = $_SESSION['tutorialState'] ?? ['enemyManuallyEquipped' => false];

// For enemy, pre-populate equipment (they start equipped) - but mark as not manually equipped for tutorial
if ($enemyEquipment['weapon'] === null && $enemyEquipment['armor'] === null) {
    $enemyEquipment = [
        'weapon' => ['name' => 'Ion Cannon', 'atk' => 12, 'durability' => 100, 'type' => 'weapon'],
        'armor' => ['name' => 'Reactive Plating', 'def' => 8, 'durability' => 100, 'type' => 'armor']
    ];
    // This is default equipment, not manually equipped
    $tutorialState['enemyManuallyEquipped'] = false;
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
        // Also reset energy when resetting mechs
        $_SESSION['playerEnergy'] = $_SESSION['maxEnergy'] ?? 5;
        $_SESSION['enemyEnergy'] = $_SESSION['maxEnergy'] ?? 5;
        $gameLog[] = "[" . date('H:i:s') . "] Debug: Mech health and energy reset to full";
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
            'HP' => 75, 
            'ATK' => 30, 
            'DEF' => 15, 
            'MAX_HP' => 75, 
            'companion' => 'Pilot-Alpha',
            'name' => 'Player Mech',
            'image' => $playerImage
        ];
        $enemyMech = [
            'HP' => 75, 
            'ATK' => 25, 
            'DEF' => 10, 
            'MAX_HP' => 75, 
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
        
        // Reset energy to maximum
        $_SESSION['playerEnergy'] = $_SESSION['maxEnergy'] ?? 5;
        $_SESSION['enemyEnergy'] = $_SESSION['maxEnergy'] ?? 5;
        
        // Reset tutorial state
        $tutorialState = ['enemyManuallyEquipped' => false];
        
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
            
            // Calculate energy cost for equipment
            $energyCost = 1; // Default for weapon/armor
            if ($card['type'] === 'special attack') {
                $energyCost = 2; // Special attacks cost 2 energy
            }
            
            // Check if player has enough energy
            $currentEnergy = $_SESSION['playerEnergy'] ?? 0;
            if ($currentEnergy < $energyCost) {
                $gameLog[] = "[" . date('H:i:s') . "] Error: Not enough energy to equip {$card['name']}! (Need {$energyCost} energy, have {$currentEnergy})";
            } else {
            
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
                    // Deduct energy for successful special attack equipping
                    $_SESSION['playerEnergy'] -= $energyCost;
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
                // Deduct energy for successful weapon/armor equipping
                $_SESSION['playerEnergy'] -= $energyCost;
            } else {
                $gameLog[] = "[" . date('H:i:s') . "] Error: Cannot equip {$card['type']} card to {$equipSlot} slot!";
            }
            } // Close energy check block
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
    $_SESSION['tutorialState'] = $tutorialState;
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
                            <div class="companion-pog enemy-companion" title="Companion: <?= safeHtmlOutput($enemyMech['companion'], 'AI-Core') ?>">
                                <?php 
                                $companionImage = 'images/companions/companion_ai_core.png';
                                if (file_exists($companionImage)): ?>
                                    <img src="<?= $companionImage ?>" alt="<?= safeHtmlOutput($enemyMech['companion'], 'AI-Core') ?>" class="companion-image">
                                <?php else: ?>
                                    <div class="companion-text"><?= substr(safeHtmlOutput($enemyMech['companion'], 'AI'), 0, 2) ?></div>
                                <?php endif; ?>
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
                <div class="energy-debug-controls" style="margin-top: 10px;">
                    <button type="button" onclick="debugChangeEnergy(-1)" class="debug-btn">-1 Energy</button>
                    <button type="button" onclick="debugChangeEnergy(1)" class="debug-btn">+1 Energy</button>
                    <button type="button" onclick="debugResetEnergy()" class="debug-btn">Reset Energy</button>
                </div>
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
                            <?php
                            $companionName = $playerMech['companion'] ?? 'Jack';
                            $companionData = $companionLibrary[$companionName] ?? $companionLibrary['Jack'];
                            $isActive = $_SESSION['playerCompanionActive'] ?? false;
                            $activeClass = $isActive ? 'companion-active' : '';
                            $costText = $isActive ? 'Active!' : 'Click: 2 Energy';
                            ?>
                            <div class="companion-pog player-companion <?= $activeClass ?>" 
                                 title="<?= $companionData['full_name'] ?> - <?= $costText ?>"
                                 onclick="activateCompanion('player')"
                                 id="playerCompanionPog">
                                <?php 
                                $companionImage = $companionData['image'];
                                if (file_exists($companionImage)): ?>
                                    <img src="<?= $companionImage ?>" alt="<?= $companionData['name'] ?>" class="companion-image">
                                <?php else: ?>
                                    <div class="companion-text">🐕</div>
                                <?php endif; ?>
                                <?php if ($isActive): ?>
                                    <div class="companion-active-indicator">⚡</div>
                                <?php endif; ?>
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
            <button type="button" onclick="playCard(window.currentCardIndex)" class="action-btn attack-btn">⚡ Play Card</button>
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
            <img src="images/old_man_thoughtful.png" alt="Old Man Instructor" class="help-old-man-image">
            <div class="help-header-text">
                <h2>🎓 Welcome to Combat Training, Pilot</h2>
                <p class="help-subtitle">Listen carefully to these old battle-tested lessons...</p>
            </div>
            <button type="button" class="help-close-btn" onclick="closeHelpModal()">✕</button>
        </div>
        
        <div class="help-modal-body">
            <div class="help-sections">
                
                <!-- 1. Welcome & Basic Concepts -->
                <div class="help-section">
                    <h3>🎯 Lesson 1: Understanding Your Mission</h3>
                    <p><em>"Alright, rookie. You're about to pilot a combat mech in tactical card-based warfare. This isn't child's play - it's about strategy, resource management, and making every decision count. Pay attention."</em></p>
                    <p><strong>Your Goal:</strong> Reduce the enemy mech's HP to zero before they destroy you. Simple concept, complex execution.</p>
                </div>
                
                <!-- 2. Energy - The Core Resource -->
                <div class="help-section">
                    <h3>⚡ Lesson 2: Energy - Your Lifeline</h3>
                    <p><em>"Listen carefully, pilot. Energy is EVERYTHING. You get 5 energy each turn - no more, no less. Waste it, and you're dead."</em></p>
                    <div class="help-subsection">
                        <h4>🔋 Energy Costs (Learn These!):</h4>
                        <ul>
                            <li><strong>Equip Weapon:</strong> 1 energy</li>
                            <li><strong>Equip Armor:</strong> 1 energy</li>
                            <li><strong>Equip Special Attack:</strong> 2 energy</li>
                            <li><strong>Attack Enemy:</strong> 1 energy</li>
                            <li><strong>Play Spell Cards:</strong> Varies (check the card cost)</li>
                        </ul>
                    </div>
                    <p><em>"Full equipment setup costs 4 energy (weapon + armor + special). That leaves you only 1 energy for attacking. Choose wisely."</em></p>
                </div>
                
                <!-- 3. Equipment Fundamentals -->
                <div class="help-section">
                    <h3>⚔️ Lesson 3: Equipment Basics</h3>
                    <p><em>"A naked mech is a dead mech. Here's what you need to know about gear:"</em></p>
                    <div class="help-subsection">
                        <h4>🔧 Equipment Types:</h4>
                        <ul>
                            <li><strong>⚔️ Weapons:</strong> Increase your attack damage</li>
                            <li><strong>🛡️ Armor:</strong> Reduce incoming damage</li>
                            <li><strong>💥 Special Attacks:</strong> Enhanced abilities (requires weapon first)</li>
                        </ul>
                    </div>
                    <p><em>"To equip: Click a card in your hand. To unequip: Click the red ✕ button (but it costs no energy to unequip)."</em></p>
                </div>
                
                <!-- 4. Combat Mechanics -->
                <div class="help-section">
                    <h3>⚔️ Lesson 4: Combat Mathematics</h3>
                    <p><em>"Combat isn't random, pilot. It's pure mathematics. Here's the formula that'll keep you alive:"</em></p>
                    <div class="help-subsection">
                        <h4>📊 Damage Calculation:</h4>
                        <ul>
                            <li><strong>Your Damage =</strong> (Mech ATK + Weapon Bonus) - (Enemy DEF + Armor Bonus)</li>
                            <li><strong>Minimum Damage:</strong> Always at least 1 (can't be reduced to 0)</li>
                            <li><strong>HP:</strong> Both mechs start with 75 HP - every point matters</li>
                        </ul>
                    </div>
                    <p><em>"Example: Your 30 ATK + 20 weapon vs enemy's 10 DEF + 15 armor = 25 damage dealt."</em></p>
                </div>
                
                <!-- Lesson 5: Strategic Thinking -->
                <div class="help-section">
                    <h3>🧠 Lesson 5: Strategic Thinking</h3>
                    <div class="help-subsection">
                        <p><em>"Listen up, cadet. Equipment makes you stronger, but strategy keeps you alive. Here's how real pilots think:"</em></p>
                        <h4>🎯 Turn Planning:</h4>
                        <ul>
                            <li><strong>Think 2-3 turns ahead:</strong> What will you need? What will the enemy do?</li>
                            <li><strong>Energy curves:</strong> Turn 1: 1 energy, Turn 2: 2 energy, Turn 3: 3 energy, etc.</li>
                            <li><strong>Save vs spend:</strong> Sometimes hoarding energy for a big turn wins battles</li>
                            <li><strong>Card advantage:</strong> Drawing cards is worthless if you can't afford to play them</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>⚖️ Risk Assessment:</h4>
                        <ul>
                            <li><strong>Calculate lethal:</strong> Can you kill the enemy this turn or next?</li>
                            <li><strong>Defensive math:</strong> Will their attack kill you? Armor up or attack harder?</li>
                            <li><strong>Resource trading:</strong> Is spending 3 energy worth 15 damage?</li>
                            <li><strong>Emergency planning:</strong> Always have a backup if your main plan fails</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>⏱️ Tempo Control:</h4>
                        <ul>
                            <li><strong>Early aggression:</strong> Pressure enemies before they can set up</li>
                            <li><strong>Mid-game control:</strong> Maintain equipment advantage and board presence</li>
                            <li><strong>Late-game power:</strong> High-cost cards dominate if you survive</li>
                        </ul>
                    </div>
                    <p><em>"Strategy without execution is just wishful thinking. But execution without strategy? That's how good pilots die."</em></p>
                </div>
                
                <!-- Lesson 6: Battle-Tested Tactics -->
                <div class="help-section">
                    <h3>⚔️ Lesson 6: Battle-Tested Tactics</h3>
                    <div class="help-subsection">
                        <p><em>"These aren't theories, kid. These are tactics that have won and lost real battles. Choose your approach wisely:"</em></p>
                        <h4>🏃 Aggressive Rush (1-3 energy focus):</h4>
                        <ul>
                            <li><strong>Goal:</strong> End the fight before it starts</li>
                            <li><strong>Cards:</strong> Cheap weapons, low-cost spells, quick attacks</li>
                            <li><strong>Energy use:</strong> Spend everything every turn, maximum pressure</li>
                            <li><strong>Risk:</strong> If you don't win fast, you'll lose slow</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>⚖️ Balanced Setup (2-4 energy focus):</h4>
                        <ul>
                            <li><strong>Goal:</strong> Steady pressure with defensive options</li>
                            <li><strong>Cards:</strong> Medium-cost everything, versatile responses</li>
                            <li><strong>Energy use:</strong> Curve out smoothly, save 1-2 energy for reactions</li>
                            <li><strong>Strength:</strong> Adaptable to any enemy strategy</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>🏰 Power Build (3-5 energy focus):</h4>
                        <ul>
                            <li><strong>Goal:</strong> Survive to deploy devastating late-game cards</li>
                            <li><strong>Cards:</strong> Heavy armor, expensive weapons, powerful spells</li>
                            <li><strong>Energy use:</strong> Bank energy early, explosive powerful turns</li>
                            <li><strong>Risk:</strong> Vulnerable to early rush strategies</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>🔮 Spell Focus (Variable energy):</h4>
                        <ul>
                            <li><strong>Goal:</strong> Control the battlefield with magical effects</li>
                            <li><strong>Cards:</strong> Light equipment, heavy on spells and abilities</li>
                            <li><strong>Energy use:</strong> Flexible spending based on spell costs</li>
                            <li><strong>Note:</strong> Master this when spell effects are fully implemented</li>
                        </ul>
                    </div>
                    <p><em>"I've seen hotheads rush to their doom and cowards hide until they're overwhelmed. Find your style, but respect your enemy's."</em></p>
                </div>
                
                <!-- Lesson 7: Interface & Controls -->
                <div class="help-section">
                    <h3>🖥️ Lesson 7: Interface & Controls</h3>
                    <div class="help-subsection">
                        <p><em>"Your interface is your lifeline. Fumble with the controls and you're dead. Master these essentials:"</em></p>
                        <h4>📋 Essential Controls:</h4>
                        <ul>
                            <li><strong>Card Selection:</strong> Click cards in your hand to play them</li>
                            <li><strong>Equipment:</strong> Click weapons/armor to equip, red X to unequip</li>
                            <li><strong>Energy Display:</strong> Top-left shows current/max energy</li>
                            <li><strong>Deck:</strong> Click your deck to draw cards (when hand is empty)</li>
                            <li><strong>Attack Button:</strong> Big red button when you're ready to strike</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>📊 Combat Information:</h4>
                        <ul>
                            <li><strong>HP Bars:</strong> Green = healthy, yellow = damaged, red = critical</li>
                            <li><strong>Stat Displays:</strong> ATK/DEF numbers include equipment bonuses</li>
                            <li><strong>Turn Indicator:</strong> Shows whose turn it is</li>
                            <li><strong>Combat Log:</strong> Right panel shows all battle actions</li>
                        </ul>
                    </div>
                    <div class="help-subsection">
                        <h4>⚙️ Advanced Features:</h4>
                        <ul>
                            <li><strong>Configuration:</strong> Access card creator, debug tools, game settings</li>
                            <li><strong>Debug Panel:</strong> Real-time system diagnostics (left slide panel)</li>
                            <li><strong>Enemy Manager:</strong> Equip enemy cards for testing scenarios</li>
                            <li><strong>Reset Functions:</strong> Restore mechs to full health for testing</li>
                        </ul>
                    </div>
                    <p><em>"A good pilot knows their mech inside and out. A great pilot knows their interface just as well. Don't let clumsy fingers cost you the battle."</em></p>
                </div>
                
            </div>
        </div>
        
        <div class="help-modal-footer">
            <button type="button" onclick="closeHelpModal()" class="help-close-button">Understood, instructor! ⚔️</button>
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
    
    // Check tutorial flow - enemy must be equipped first
    if (!validateTutorialFlow('combat_action')) {
        return Promise.reject('Tutorial flow validation failed');
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
            
            // NEW: Tutorial feedback for attack actions
            if (action === 'attack_enemy') {
                NarrativeGuide.trigger('player_attacks');
            }
            
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

function playCardOld() {
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
                        `<div class="zoom-equip-action" onclick="playCard(${cardIndex})">
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

    // NEW function to replace the simple show() - acts as event bus
    trigger: function(eventName, data = {}) {
        if (!this.panel) this.init(); // Ensure it's initialized
        let expression = 'neutral';
        let text = '';

        switch(eventName) {
            case 'game_start':
                expression = 'serious';
                text = "Welcome to the combat simulator, pilot. Before we can begin combat training, the enemy MECH needs to be armed. Click the '⚙️ Enemy Gear' button below, then use '🎲 Randomize' to equip the enemy with weapons and armor. I'll wait here until that's done.";
                break;
            case 'enemy_needs_equipment':
                expression = 'waiting';
                text = "I'm still waiting, pilot. The enemy MECH must be equipped with both weapon and armor before we proceed. Use the '⚙️ Enemy Gear' button, then '🎲 Randomize' to arm your opponent.";
                break;
            case 'player_turn_start':
                expression = 'thoughtful';
                text = `Alright, our turn. We have ${data.energy} energy. Let's make it count.`;
                break;
            case 'player_plays_weapon':
                expression = 'happy';
                text = `A solid weapon! Our base attack power is now boosted.`;
                break;
            case 'player_plays_armor':
                expression = 'happy';
                text = `Smart move. That armor will absorb some of the next hit.`;
                break;
            case 'player_plays_special':
                expression = 'happy';
                text = `A special attack! That'll supercharge our next weapon strike.`;
                break;
            case 'player_equipped_all':
                expression = 'serious';
                text = "Alright, you're geared up. Weapon and armor are online. Now it's time to fight back. Prepare your attack.";
                break;
            case 'enemy_equipped':
                expression = 'thoughtful';
                text = "Good. The enemy is properly armed now. Next, you need to equip yourself with weapon and armor cards. If you don't have cards yet, click your deck to draw some first.";
                break;
            case 'player_drew_cards':
                expression = 'happy';
                text = "Excellent! Your tactical systems are online. Now equip yourself for battle - play a weapon card and an armor card from your hand. Each card costs energy, so spend wisely.";
                break;
            case 'player_needs_equipment':
                expression = 'serious';
                text = "You'll need both a weapon and armor before engaging. Look through your hand and equip what you need. Remember - weapons boost your attack, armor protects you from damage.";
                break;
            case 'player_insufficient_energy':
                expression = 'disappointed';
                text = `Whoa there. We don't have enough energy for that move. Costs ${data.cost}, but we only have ${data.energy}.`;
                break;
            case 'player_prepares_attack':
                expression = 'serious';
                text = `Preparing to attack. This will use our main action for the turn. Are you sure?`;
                break;
            case 'player_attacks':
                expression = 'happy';
                text = `Direct hit! That's how it's done!`;
                break;
            case 'enemy_turn_start':
                expression = 'serious';
                text = `Hmph. The enemy is making its move. Stay sharp.`;
                break;
            case 'game_win':
                expression = 'happy';
                text = 'Heh. Not bad, kid. Not bad at all. You might just make a pilot yet.';
                break;
            case 'game_loss':
                expression = 'disappointed';
                text = 'Simulation failed. Resetting. Every pilot gets knocked down. The trick is getting back up.';
                break;
            default:
                return; // Do nothing if event is unknown
        }

        this._display(expression, text);
    },

    // The _display() function handles the visual update (renamed from show)
    _display: function(expression, text) {
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
    },

    // Keep the old show() function for backward compatibility
    show: function(expression, text) {
        this._display(expression, text);
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
                // Update tutorial state in JavaScript
                window.tutorialState.enemyManuallyEquipped = true;
                
                // Trigger tutorial dialogue for enemy being equipped
                NarrativeGuide.trigger('enemy_equipped');
                // Switch back to main combat view
                updateActionBar('main');
                // Reload page to show new equipment
                setTimeout(() => {
                    window.location.reload();
                }, 1500); // Give time for dialogue to show
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
        NarrativeGuide.init();
        setTimeout(() => {
            checkTutorialFlow();
        }, 1000); // Show after a 1-second delay
    });
} else {
    CardZoom.init();
    initDragAndDrop();
    // Initialize Narrative Guide
    NarrativeGuide.init();
    setTimeout(() => {
        checkTutorialFlow();
    }, 1000); // Show after a 1-second delay
}

// ===================================================================
// TUTORIAL FLOW DETECTION
// ===================================================================
function checkTutorialFlow() {
    // Get current game state from PHP variables
    const playerHand = <?= json_encode($playerHand) ?>;
    const playerEquipment = <?= json_encode($playerEquipment) ?>;
    const enemyEquipment = <?= json_encode($enemyEquipment) ?>;
    const tutorialState = <?= json_encode($tutorialState) ?>;
    
    console.log('🎓 Tutorial Flow Check:', {
        handSize: playerHand.length,
        hasPlayerWeapon: !!playerEquipment.weapon,
        hasPlayerArmor: !!playerEquipment.armor,
        hasEnemyWeapon: !!enemyEquipment.weapon,
        hasEnemyArmor: !!enemyEquipment.armor,
        enemyManuallyEquipped: tutorialState.enemyManuallyEquipped
    });
    
    // PRIORITY 1: Enemy MUST be manually equipped first - everything else waits
    if (!tutorialState.enemyManuallyEquipped) {
        // If this is the very first load, show game_start
        // If player has done other actions, show reminder
        if (playerHand.length === 0 && !playerEquipment.weapon && !playerEquipment.armor) {
            NarrativeGuide.trigger('game_start');
        } else {
            NarrativeGuide.trigger('enemy_needs_equipment');
        }
        return;
    }
    
    // Step 2: Enemy is equipped, now we can proceed with player setup
    if (playerHand.length === 0) {
        NarrativeGuide.trigger('enemy_equipped');
        return;
    }
    
    // Step 3: Player has cards, check if they need equipment
    if (!playerEquipment.weapon || !playerEquipment.armor) {
        // If they just drew cards, show drew cards message
        if (playerHand.length >= 3) {
            NarrativeGuide.trigger('player_drew_cards');
        } else {
            // Otherwise remind them to equip
            NarrativeGuide.trigger('player_needs_equipment');
        }
        return;
    }
    
    // Step 4: Player is fully equipped - ready to fight
    NarrativeGuide.trigger('player_equipped_all');
}

// Store tutorial state in JavaScript for dynamic checking
window.tutorialState = <?= json_encode($tutorialState) ?>;

// Tutorial validation function - ensures proper flow
function validateTutorialFlow(actionType) {
    // Check tutorial state - enemy must be manually equipped
    const enemyManuallyEquipped = window.tutorialState?.enemyManuallyEquipped || false;
    
    console.log('🎓 Tutorial validation check:', {
        actionType,
        enemyManuallyEquipped,
        tutorialState: window.tutorialState
    });
    
    // Always check if enemy was manually equipped first
    if (!enemyManuallyEquipped) {
        console.log('🚫 Tutorial validation failed: Enemy not manually equipped for action:', actionType);
        NarrativeGuide.trigger('enemy_needs_equipment');
        return false;
    }
    
    return true;
}

// Add periodic tutorial reminders
setInterval(() => {
    // Check if enemy equipment slots are filled
    const enemyWeaponSlot = document.querySelector('.enemy-equipment .weapon-card.equipped');
    const enemyArmorSlot = document.querySelector('.enemy-equipment .armor-card.equipped');
    const enemyEquipped = enemyWeaponSlot && enemyArmorSlot;
    
    // If enemy isn't equipped after 30 seconds, show reminder
    if (!enemyEquipped) {
        if (Math.random() < 0.3) { // 30% chance every 30 seconds
            NarrativeGuide.trigger('enemy_needs_equipment');
        }
    }
}, 30000); // Check every 30 seconds

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
            // Update UI without page reload
            if (data.data && data.data.enemyEquipment) {
                // TODO: Update enemy equipment display dynamically
                console.log('🎯 Enemy equipment updated:', data.data.enemyEquipment);
            }
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
            NarrativeGuide.trigger('player_prepares_attack');
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
            NarrativeGuide.trigger('game_win');
            break;
        case 'enemy_wins':
            window.gameState = 'enemy_wins';
            textEl.textContent = 'DEFEAT! Your mech has been destroyed.';
            buttonsEl.innerHTML = `<form method="post"><button type="submit" name="reset_all" class="action-btn reset-btn">⚡ Try Again</button></form>`;
            NarrativeGuide.trigger('game_loss');
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
        case 'discard_mode':
            textEl.textContent = 'Discard Mode: Click on cards in your hand to discard them.';
            buttonsEl.innerHTML = `
                <button onclick="updateActionBar('main')" class="action-btn back-btn">↩️ Back to Combat</button>
                <div class="discard-info">
                    <small>Click cards in your hand to discard them permanently</small>
                </div>
            `;
            // Enable discard mode
            window.discardMode = true;
            break;
        case 'main':
        default:
            textEl.textContent = 'Your move. Play a card or prepare an attack.';
            buttonsEl.innerHTML = `
                <button onclick="updateActionBar('combat_confirm')" class="action-btn attack-btn">⚔️ Attack</button>
                <button onclick="updateActionBar('discard_mode')" class="action-btn discard-btn" title="Discard cards from your hand">🗑️ Discard Cards</button>
                <button onclick="endTurn()" id="endTurnBtn" class="action-btn">➡️ End Turn</button>
                <button onclick="updateActionBar('enemy_config')" class="action-btn config-btn" title="Configure enemy equipment">⚙️ Enemy Gear</button>
            `;
            // Disable discard mode when returning to main
            window.discardMode = false;
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
    NarrativeGuide.trigger('enemy_turn_start');

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
                
                NarrativeGuide.trigger('player_turn_start', { energy: data.data.playerEnergy });
                
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
    // DISABLED: This was overriding energy display after cards were played
    // fetch('combat-manager.php', { 
    //     method: 'POST', 
    //     body: new URLSearchParams('action=get_combat_status')
    // })
    // .then(res => res.json())
    // .then(status => {
    //     if(status.success) updateCombatUI(status.data);
    // });

    setTimeout(() => {
        processAIActions(actions, finalCallback); // Process next action
    }, delay);
}

function playCard(cardIndex) {
    console.log('🎯 PLAY CARD CALLED with index:', cardIndex);
    console.log('🎯 Type of cardIndex:', typeof cardIndex, 'Value:', cardIndex);
    
    // Validate player action
    if (!validatePlayerAction('play card')) {
        console.log('❌ Player action validation failed');
        return;
    }
    
    // Close any open modals
    CardZoom.closeZoomModal();
    closeCardDetails();
    
    // Get current energy before making the request
    const currentEnergyElement = document.getElementById('playerEnergyValue');
    const currentEnergy = currentEnergyElement ? parseInt(currentEnergyElement.textContent) : 0;
    console.log('🔋 Current energy before request:', currentEnergy);

    console.log('🔋 MAKING PLAY CARD REQUEST...');
    fetch('combat-manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(`action=play_card&card_index=${cardIndex}`)
    })
    .then(response => {
        console.log('🔋 Got response, parsing JSON...');
        return response.json();
    })
    .then(data => {
        console.log('🔋 FULL RESPONSE DATA:', JSON.stringify(data, null, 2));
        
        if (data.success) {
            console.log('🔋 SUCCESS! Energy in response:', data.data.playerEnergy);
            
            // FORCE UPDATE THE ENERGY DISPLAY IMMEDIATELY
            if (data.data.playerEnergy !== undefined) {
                console.log('🔋 FORCING ENERGY UPDATE TO:', data.data.playerEnergy);
                updateEnergyDisplay(data.data.playerEnergy);
                
                // Also update the session storage for debugging
                sessionStorage.setItem('lastKnownEnergy', data.data.playerEnergy);
            }
            
            // Re-render the player's hand
            if (data.data.playerHand) {
                renderPlayerHand(data.data.playerHand);
            }
            
            showMessage(data.message, 'success');
            
            // Tutorial feedback based on card type
            if (data.data.playedCard) {
                const cardType = data.data.playedCard.type;
                if (cardType === 'weapon') {
                    NarrativeGuide.trigger('player_plays_weapon');
                } else if (cardType === 'armor') {
                    NarrativeGuide.trigger('player_plays_armor');
                } else if (cardType === 'special attack') {
                    NarrativeGuide.trigger('player_plays_special');
                }
            }
            
            // Check if player is fully equipped (weapon and armor)
            if (data.data.playerEquipment) {
                const equip = data.data.playerEquipment;
                if (equip.weapon && equip.armor) {
                    // Use a timeout to let the other dialogue finish first
                    setTimeout(() => {
                        NarrativeGuide.trigger('player_equipped_all');
                    }, 2000); // 2-second delay
                } else if (data.data.playerHand && data.data.playerHand.length > 0) {
                    // Player has cards but still needs equipment
                    setTimeout(() => {
                        NarrativeGuide.trigger('player_needs_equipment');
                    }, 2000); // 2-second delay
                }
            }
            
            // Check for game over conditions
            if (data.data && data.data.gameOver) {
                if (data.data.gameOver === 'player_wins') {
                    updateActionBar('player_wins');
                } else if (data.data.gameOver === 'enemy_wins') {
                    updateActionBar('enemy_wins');
                }
            }
        } else {
            console.log('❌ PLAY CARD FAILED:', data.message);
            // Tutorial feedback for insufficient energy
            if (data.message && data.message.includes('Not enough energy') && data.data) {
                NarrativeGuide.trigger('player_insufficient_energy', { 
                    cost: data.data.cost, 
                    energy: data.data.energy 
                });
            }
            showMessage('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ PLAY CARD ERROR:', error);
        showMessage('Error playing card: ' + error.message, 'error');
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
            
            // Create card container element
            const cardContainer = document.createElement('div');
            cardContainer.className = 'hand-card-container';
            cardContainer.style.setProperty('--card-index', index);
            
            // Create card button element
            const cardButton = document.createElement('button');
            cardButton.type = 'button';
            cardButton.className = `hand-card face-up fan-card ${card.type}-card ${cardElement}-element ${cardRarity}-rarity${imageClass}`;
            cardButton.setAttribute('data-card', JSON.stringify(card));
            cardButton.draggable = true;
            if (hasImage) {
                cardButton.style.backgroundImage = `url('${card.image}')`;
            }
            
            // Add event listener instead of onclick attribute
            cardButton.addEventListener('click', function() {
                console.log('🎯 CARD BUTTON CLICKED! Index:', index, 'Card:', card.name);
                handleCardClick(index, card.type);
            });
            
            // Add drag and drop functionality
            cardButton.draggable = true;
            cardButton.addEventListener('dragstart', handleCardDragStart);
            cardButton.addEventListener('dragend', handleDragEnd);
            
            // Add card content
            cardButton.innerHTML = `
                <div class="card-mini-name">${card.name}</div>
                <div class="card-mini-cost">${card.cost}</div>
                ${!hasImage ? `<div class="card-type-icon">${typeIcons[card.type] || '❓'}</div>` : ''}
                ${(card.damage && card.damage > 0) ? `<div class="card-mini-damage">💥${card.damage}</div>` : ''}
            `;
            
            // Append to container and then to hand
            cardContainer.appendChild(cardButton);
            handContainer.appendChild(cardContainer);
        });
    }
    
    // Update hand count display
    console.log('🎯 renderPlayerHand completed. Added', hand.length, 'cards with event listeners');
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

// Add global card click handler that works with any card rendering system
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Setting up global card click handlers');
    
    // Use event delegation to catch clicks on any card, regardless of how it was rendered
    document.addEventListener('click', function(event) {
        const cardElement = event.target.closest('.hand-card');
        if (cardElement && cardElement.closest('.player-hand-section')) {
            console.log('🎯 GLOBAL CARD CLICK DETECTED!', cardElement);
            
            // Try to get card index from various sources
            let cardIndex = null;
            let cardData = null;
            
            // Method 1: From container style attribute
            const container = cardElement.closest('.hand-card-container');
            if (container) {
                const styleAttr = container.getAttribute('style');
                const match = styleAttr && styleAttr.match(/--card-index:\s*(\d+)/);
                if (match) {
                    cardIndex = parseInt(match[1]);
                }
            }
            
            // Method 2: Get card data from data attribute
            const dataAttr = cardElement.getAttribute('data-card');
            if (dataAttr) {
                try {
                    cardData = JSON.parse(dataAttr);
                } catch (e) {
                    console.log('❌ Failed to parse card data:', e);
                }
            }
            
            // If we found valid data, handle the click
            if (cardIndex !== null && cardData) {
                console.log('🎯 PROCESSING CARD CLICK - Index:', cardIndex, 'Card:', cardData.name);
                event.preventDefault();
                event.stopPropagation();
                handleCardClickInternal(cardIndex, cardData.type, cardData);
            } else {
                console.log('❌ Could not extract card data from click');
            }
        }
    });
});

// Modify the existing card click handler
function handleCardClick(cardIndex, cardType) {
    handleCardClickInternal(cardIndex, cardType, null);
}

function handleCardClickInternal(cardIndex, cardType, providedCardData) {
    console.log('🎯 CARD CLICKED! Index:', cardIndex, 'Type:', cardType);
    
    // Check tutorial flow - enemy must be equipped first
    if (!validateTutorialFlow('card_click')) {
        return;
    }
    
    // Check if we're in discard mode
    if (window.discardMode) {
        console.log('🗑️ DISCARD MODE: Discarding card at index:', cardIndex);
        discardCard(cardIndex);
        return;
    }
    
    // Store card index for modal button
    window.currentCardIndex = cardIndex;
    console.log('🎯 Stored window.currentCardIndex:', window.currentCardIndex);
    
    // Get card data from provided parameter or from DOM element
    let finalCardData = providedCardData;
    if (!finalCardData) {
        const cardElement = document.querySelector(`.hand-card-container[style*="--card-index: ${cardIndex}"] .hand-card`);
        if (!cardElement) {
            console.log('❌ Card element not found for index:', cardIndex);
            return;
        }
        try {
            finalCardData = JSON.parse(cardElement.dataset.card);
        } catch (e) {
            console.log('❌ Failed to parse card data from element:', e);
            return;
        }
    }
    
    console.log('🎯 Opening CardZoom modal for card:', finalCardData.name);
    CardZoom.showZoomModal(finalCardData, false, cardIndex); // Pass cardIndex to the modal
}

// Card discard functionality
function discardCard(cardIndex) {
    console.log('🗑️ Discarding card at index:', cardIndex);
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: new URLSearchParams(`action=discard_card&card_index=${cardIndex}`)
    })
    .then(response => response.json())
    .then(data => {
        console.log('🗑️ Discard response:', data);
        if (data.success) {
            // Re-render the player's hand
            if (data.data.playerHand) {
                renderPlayerHand(data.data.playerHand);
            }
            showMessage(data.message, 'success');
        } else {
            showMessage('Error discarding card: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error discarding card:', error);
        showMessage('Error discarding card: ' + error.message, 'error');
    });
}

// Helper functions for UI updates
function updateEnergyDisplay(newEnergy) {
    console.log('🔋 updateEnergyDisplay called with:', newEnergy);
    const energyElement = document.getElementById('playerEnergyValue');
    if (energyElement) {
        console.log('🔋 Energy element found, updating from', energyElement.textContent, 'to', newEnergy);
        energyElement.textContent = newEnergy;
    } else {
        console.log('❌ Energy element not found!');
    }
}

// Energy debug functions
function debugChangeEnergy(amount) {
    console.log('🔧 DEBUG: Changing energy by', amount);
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: new URLSearchParams(`action=debug_change_energy&amount=${amount}`)
    })
    .then(response => response.json())
    .then(data => {
        console.log('🔧 DEBUG: Energy change response:', data);
        if (data.success) {
            updateEnergyDisplay(data.data.playerEnergy);
            showMessage(`Energy changed by ${amount}. New energy: ${data.data.playerEnergy}`, 'info');
        } else {
            showMessage('Failed to change energy: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error changing energy:', error);
        showMessage('Error changing energy: ' + error.message, 'error');
    });
}

function debugResetEnergy() {
    console.log('🔧 DEBUG: Resetting energy');
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: new URLSearchParams('action=debug_reset_energy')
    })
    .then(response => response.json())
    .then(data => {
        console.log('🔧 DEBUG: Energy reset response:', data);
        if (data.success) {
            updateEnergyDisplay(data.data.playerEnergy);
            showMessage(`Energy reset to ${data.data.playerEnergy}`, 'info');
        } else {
            showMessage('Failed to reset energy: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error resetting energy:', error);
        showMessage('Error resetting energy: ' + error.message, 'error');
    });
}

// ===================================================================
// COMPANION ACTIVATION SYSTEM
// ===================================================================
function activateCompanion(owner) {
    console.log(`🐕 Activating ${owner} companion`);
    
    const formData = new URLSearchParams();
    formData.append('action', 'activate_companion');
    formData.append('owner', owner);
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('🐕 Companion activation response:', data);
        if (data.success) {
            // Update energy display
            if (data.data.playerEnergy !== undefined) {
                updateEnergyDisplay(data.data.playerEnergy);
            }
            
            // Update companion visual state
            updateCompanionState(owner, true);
            
            // Show success message
            showMessage(data.message, 'success');
            
            console.log('🐕 Companion activated successfully!');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error activating companion:', error);
        showMessage('Error activating companion: ' + error.message, 'error');
    });
}

function updateCompanionState(owner, isActive) {
    const companionPog = document.getElementById(`${owner}CompanionPog`);
    if (!companionPog) return;
    
    if (isActive) {
        companionPog.classList.add('companion-active');
        // Add active indicator if not already present
        if (!companionPog.querySelector('.companion-active-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'companion-active-indicator';
            indicator.textContent = '⚡';
            companionPog.appendChild(indicator);
        }
        // Update tooltip
        companionPog.title = companionPog.title.replace('Click: 2 Energy', 'Active!');
    } else {
        companionPog.classList.remove('companion-active');
        // Remove active indicator
        const indicator = companionPog.querySelector('.companion-active-indicator');
        if (indicator) {
            indicator.remove();
        }
        // Update tooltip
        companionPog.title = companionPog.title.replace('Active!', 'Click: 2 Energy');
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