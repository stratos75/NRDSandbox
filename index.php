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
// CARD LIBRARY & HAND MANAGEMENT
// ===================================================================
// Load permanent card library from JSON (never modified by gameplay)
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

// Initialize player hand from session (separate from library)
if (!isset($_SESSION['player_hand'])) {
    $_SESSION['player_hand'] = [];
}
$playerHand = $_SESSION['player_hand'] ?? [];
$debugInfo[] = "Hand size: " . count($playerHand);

// Initialize basic mech data
$playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 'companion' => 'Pilot-Alpha'];
$enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 'companion' => 'AI-Core'];

// ===================================================================
// EQUIPMENT STATE TRACKING
// ===================================================================
$playerEquipment = $_SESSION['playerEquipment'] ?? ['weapon' => null, 'armor' => null];
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
// FORM PROCESSING
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ===================================================================
    // DEBUG RESET FUNCTIONS (NEW)
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
        // Reset mech stats
        $playerMech = ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 'companion' => 'Pilot-Alpha'];
        $enemyMech = ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 'companion' => 'AI-Core'];
        
        // Reset equipment
        $playerEquipment = ['weapon' => null, 'armor' => null];
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
    // COMBAT ACTIONS
    // ===================================================================
    // Handle damage actions
    if (isset($_POST['damage'])) {
        $target = $_POST['damage'];
        $damageAmount = 10; // Base damage
        
        if ($target === 'enemy') {
            $enemyMech['HP'] = max(0, $enemyMech['HP'] - $damageAmount);
            $gameLog[] = "[" . date('H:i:s') . "] Player attacks Enemy for {$damageAmount} damage!";
        } elseif ($target === 'player') {
            $playerMech['HP'] = max(0, $playerMech['HP'] - $damageAmount);
            $gameLog[] = "[" . date('H:i:s') . "] Enemy attacks Player for {$damageAmount} damage!";
        }
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
            // Draw cards from library to hand
            $drawnCards = array_slice($cardLibrary, 0, $cardsToDraw);
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
        $equipSlot = $_POST['equip_slot']; // 'weapon' or 'armor'
        
        if (isset($playerHand[$cardIndex])) {
            $card = $playerHand[$cardIndex];
            
            // Validate card type matches slot
            if ($card['type'] === $equipSlot) {
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

// ===================================================================
// EQUIPMENT DISPLAY HELPER
// ===================================================================
function renderEquipmentSlot($equipment, $slotType, $owner) {
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
        
        return "
            <div class='equipment-area'>
                <form method='post'>
                    <button type='submit' name='equipment_click' value='{$owner} {$slotType}' class='equipment-card {$slotType}-card {$owner}-equipment equipped'>
                        <div class='card-type'>{$slotType}</div>
                        <div class='card-name'>" . htmlspecialchars($equipment['name']) . "</div>
                        <div class='card-stats'>{$statLabel}: {$statValue}</div>
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
         TOP NAVIGATION BAR (CLEANED UP)
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="config/" class="config-link">⚙️ Configure</a>
            <button type="button" class="config-link debug-toggle-btn" onclick="toggleDebugPanel()">🐛 Debug</button>
            <button type="button" class="config-link card-creator-btn" onclick="toggleCardCreator()">🃏 Card Creator</button>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">NRD TACTICAL SANDBOX</h1>
        </div>
        <div class="nav-right">
            <a href="build-info.php" class="version-badge">v0.9.2</a>
            <a href="logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Game Rules Summary Bar -->
    <div class="rules-summary-bar">
        <div class="rules-summary-content">
            <span class="rules-item">📋 Hand: <?= $gameRules['starting_hand_size'] ?>/<?= $gameRules['max_hand_size'] ?></span>
            <span class="rules-item">📚 Deck: <?= $gameRules['deck_size'] ?> cards</span>
            <span class="rules-item">🎯 Draw: <?= $gameRules['cards_drawn_per_turn'] ?>/turn</span>
            <span class="rules-item">🎮 Start: <?= ucfirst($gameRules['starting_player']) ?></span>
            <a href="config/rules.php" class="rules-config-link">⚙️ Configure Rules</a>
        </div>
    </div>

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

                <!-- Enemy Mech (Center) -->
                <div class="mech-area enemy-mech">
                    <div class="mech-card <?= getMechStatusClass($enemyMech['HP'], $enemyMech['MAX_HP']) ?>">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog enemy-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($enemyMech['companion'], 'AI-Core') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">E</div>
                        <div class="mech-body"></div>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value"><?= $enemyMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name">Enemy Mech</div>
                        <div class="mech-stats">
                            <div class="stat">HP: <?= $enemyMech['HP'] ?>/<?= $enemyMech['MAX_HP'] ?></div>
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
                <?= renderEquipmentSlot($playerEquipment['weapon'], 'weapon', 'player') ?>

                <!-- Player Mech (Center) -->
                <div class="mech-area player-mech">
                    <div class="mech-card <?= getMechStatusClass($playerMech['HP'], $playerMech['MAX_HP']) ?>">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog player-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($playerMech['companion'], 'Pilot-Alpha') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">P</div>
                        <div class="mech-body"></div>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value"><?= $playerMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name">Your Mech</div>
                        <div class="mech-stats">
                            <div class="stat">HP: <?= $playerMech['HP'] ?>/<?= $playerMech['MAX_HP'] ?></div>
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
                            <button type="button" onclick="handleCardClick(<?= $index ?>, '<?= $card['type'] ?>')" class="hand-card face-up fan-card <?= $card['type'] ?>-card" data-card='<?= htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8') ?>'>
                                <div class="card-mini-name"><?= htmlspecialchars($card['name']) ?></div>
                                <div class="card-mini-cost"><?= $card['cost'] ?></div>
                                <?php if ($card['damage'] > 0): ?>
                                    <div class="card-mini-damage">💥<?= $card['damage'] ?></div>
                                <?php endif; ?>
                                <div class="card-type-icon">
                                    <?php
                                    $typeIcons = [
                                        'spell' => '✨',
                                        'weapon' => '⚔️', 
                                        'armor' => '🛡️',
                                        'creature' => '👾',
                                        'support' => '🔧'
                                    ];
                                    echo $typeIcons[$card['type']] ?? '❓';
                                    ?>
                                </div>
                                
                                <!-- DELETE BUTTON (X) -->
                                <button type="button" onclick="deleteCard(<?= $index ?>)" class="card-delete-btn" title="Remove card from hand">✕</button>
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
        </section>

    </main>

    <!-- ===================================================================
         GAME CONTROLS PANEL
         =================================================================== -->
    <section class="controls-panel">
        <div class="control-group">
            <h3>Combat Actions</h3>
            <form method="post" class="action-buttons">
                <button type="submit" name="damage" value="enemy" class="action-btn attack-btn">
                    ⚔️ Attack Enemy
                </button>
                <button type="submit" name="damage" value="player" class="action-btn defend-btn">
                    🛡️ Enemy Attacks
                </button>
                <button type="submit" name="reset_mechs" value="1" class="action-btn reset-btn">
                    🔄 Reset Mechs
                </button>
            </form>
        </div>
    </section>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            NRD Tactical Sandbox | Build v0.9.2 | Stable Debug System & Reset Functions
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
                        <div class="stat-line">HP: <?= $playerMech['HP'] ?>/<?= $playerMech['MAX_HP'] ?></div>
                        <div class="stat-line">ATK: <?= $playerMech['ATK'] ?></div>
                        <div class="stat-line">DEF: <?= $playerMech['DEF'] ?></div>
                    </div>
                </div>
                <div class="state-card enemy-state">
                    <div class="state-header">Enemy Mech</div>
                    <div class="state-stats">
                        <div class="stat-line">HP: <?= $enemyMech['HP'] ?>/<?= $enemyMech['MAX_HP'] ?></div>
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
            <div class="action-log">
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
                    <span class="tech-value">v0.9.2</span>
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
                    <input type="text" id="cardName" name="cardName" placeholder="Lightning Bolt" oninput="updateCardPreview()">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardCost">Cost:</label>
                        <input type="number" id="cardCost" name="cardCost" min="0" max="10" value="3" oninput="updateCardPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="cardType">Type:</label>
                        <select id="cardType" name="cardType" onchange="updateCardPreview()">
                            <option value="spell">Spell</option>
                            <option value="weapon">Weapon</option>
                            <option value="armor">Armor</option>
                            <option value="creature">Creature</option>
                            <option value="support">Support</option>
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
                            <option value="legendary">Legendary</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cardDescription">Description:</label>
                    <textarea id="cardDescription" name="cardDescription" placeholder="Deal damage to target enemy..." oninput="updateCardPreview()"></textarea>
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
                <div id="cardPreview" class="preview-card spell-card">
                    <div class="preview-cost">3</div>
                    <div class="preview-name">Lightning Bolt</div>
                    <div class="preview-type">SPELL</div>
                    <div class="preview-art">[Art]</div>
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
// CARD MANAGEMENT JAVASCRIPT
// ===================================================================
function handleCardClick(cardIndex, cardType) {
    // Check if this is a weapon or armor card
    if (cardType === 'weapon' || cardType === 'armor') {
        // Try to equip the card
        equipCard(cardIndex, cardType);
    } else {
        // Show card details for non-equipment cards
        showCardDetails(cardIndex);
    }
}

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
// CARD DETAIL MODAL FUNCTIONS
// ===================================================================
function showCardDetails(cardIndex) {
    // Get the card button element
    const cardButton = document.querySelector(`[data-card][style*="--card-index: ${cardIndex}"]`);
    if (!cardButton) {
        console.error('Card button not found for index:', cardIndex);
        return;
    }
    
    // Parse card data from data attribute
    let cardData;
    try {
        cardData = JSON.parse(cardButton.getAttribute('data-card'));
    } catch (e) {
        console.error('Error parsing card data:', e);
        return;
    }
    
    // Update card preview
    const preview = document.getElementById('modalCardPreview');
    preview.className = 'modal-card-preview ' + (cardData.type || 'spell') + '-card';
    
    // Update preview elements
    preview.querySelector('.modal-card-cost').textContent = cardData.cost || '0';
    preview.querySelector('.modal-card-name').textContent = cardData.name || 'Unknown Card';
    preview.querySelector('.modal-card-type').textContent = (cardData.type || 'unknown').toUpperCase();
    preview.querySelector('.modal-card-damage').textContent = cardData.damage > 0 ? `💥 ${cardData.damage}` : '';
    preview.querySelector('.modal-card-description').textContent = cardData.description || 'No description available';
    
    const rarityElement = preview.querySelector('.modal-card-rarity');
    rarityElement.textContent = (cardData.rarity || 'common').charAt(0).toUpperCase() + (cardData.rarity || 'common').slice(1);
    rarityElement.className = 'modal-card-rarity ' + (cardData.rarity || 'common') + '-rarity';
    
    // Update information panel
    document.getElementById('modalCardName').textContent = cardData.name || 'Unknown';
    document.getElementById('modalCardType').textContent = (cardData.type || 'Unknown').charAt(0).toUpperCase() + (cardData.type || 'Unknown').slice(1);
    document.getElementById('modalCardCost').textContent = cardData.cost || '0';
    document.getElementById('modalCardDamage').textContent = cardData.damage || '0';
    document.getElementById('modalCardRarity').textContent = (cardData.rarity || 'Unknown').charAt(0).toUpperCase() + (cardData.rarity || 'Unknown').slice(1);
    document.getElementById('modalCardDescription').textContent = cardData.description || 'No description available';
    document.getElementById('modalCardCreated').textContent = cardData.created_at || 'Unknown';
    document.getElementById('modalCardCreator').textContent = cardData.created_by || 'Unknown';
    document.getElementById('modalCardId').textContent = cardData.id || 'Unknown';
    
    // Show modal
    document.getElementById('cardDetailModal').classList.add('active');
    document.getElementById('cardDetailOverlay').classList.add('active');
}

function closeCardDetails() {
    document.getElementById('cardDetailModal').classList.remove('active');
    document.getElementById('cardDetailOverlay').classList.remove('active');
}

function playCard() {
    // Log card play action
    console.log('Playing card');
    // TODO: Implement card play mechanics
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
    const type = document.getElementById('cardType').value || 'spell';
    const damage = document.getElementById('cardDamage').value || '0';
    const description = document.getElementById('cardDescription').value || 'Card description...';
    const rarity = document.getElementById('cardRarity').value || 'common';
    
    // Update preview card
    preview.querySelector('.preview-cost').textContent = cost;
    preview.querySelector('.preview-name').textContent = name;
    preview.querySelector('.preview-type').textContent = type.toUpperCase();
    preview.querySelector('.preview-damage').textContent = damage > 0 ? `💥 ${damage}` : '';
    preview.querySelector('.preview-description').textContent = description;
    preview.querySelector('.preview-rarity').textContent = rarity.charAt(0).toUpperCase() + rarity.slice(1);
    
    // Update card styling based on type
    preview.className = `preview-card ${type}-card`;
    preview.querySelector('.preview-rarity').className = `preview-rarity ${rarity}-rarity`;
}

function resetCardForm() {
    document.getElementById('cardCreatorForm').reset();
    document.getElementById('cardCost').value = '3';
    document.getElementById('cardDamage').value = '5';
    updateCardPreview();
}

function saveCard() {
    const cardData = {
        name: document.getElementById('cardName').value,
        cost: document.getElementById('cardCost').value,
        type: document.getElementById('cardType').value,
        damage: document.getElementById('cardDamage').value,
        description: document.getElementById('cardDescription').value,
        rarity: document.getElementById('cardRarity').value
    };
    
    // Validate required fields
    if (!cardData.name.trim()) {
        alert('Please enter a card name');
        return;
    }
    
    // Prepare form data for submission
    const formData = new FormData();
    formData.append('action', 'save_card');
    formData.append('name', cardData.name);
    formData.append('cost', cardData.cost);
    formData.append('type', cardData.type);
    formData.append('damage', cardData.damage);
    formData.append('description', cardData.description);
    formData.append('rarity', cardData.rarity);
    
    // Send to server
    fetch('card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Card saved successfully!\n\nCard ID: ' + data.data.id + '\nName: ' + data.data.name);
            // Reset form after successful save
            resetCardForm();
            // Update card library
            loadCardLibrary();
            // Reload the page to show new card in library
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Error saving card: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error while saving card. Please try again.');
    });
}

function loadCardLibrary() {
    // Load saved cards from server
    fetch('card-manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_all_cards'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCardLibrary(data.data);
            updateCardCount(data.data.length);
        }
    })
    .catch(error => {
        console.error('Error loading cards:', error);
        document.getElementById('cardLibrary').innerHTML = '<div class="library-error">Error loading cards</div>';
    });
}

function displayCardLibrary(cards) {
    const libraryContainer = document.getElementById('cardLibrary');
    
    if (cards.length === 0) {
        libraryContainer.innerHTML = '<div class="library-empty">No cards created yet. Create your first card above!</div>';
        return;
    }
    
    let html = '';
    cards.forEach(card => {
        html += `
            <div class="library-card ${card.type}-card" data-card-id="${card.id}">
                <div class="library-card-header">
                    <span class="library-card-name">${card.name}</span>
                    <span class="library-card-cost">${card.cost}</span>
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

function editCard(cardId) {
    // TODO: Load card data into form for editing
    alert('Edit card feature coming soon! Card ID: ' + cardId);
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
</script>

</body>
</html>