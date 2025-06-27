<?php
// ===================================================================
// NRD SANDBOX - TACTICAL CARD BATTLE INTERFACE
// ===================================================================
require 'auth.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================================================================
// VERSION & BUILD INFO
// ===================================================================
$build = require 'builds.php';

// ===================================================================
// GAME CONFIGURATION VARIABLES
// ===================================================================
// These will eventually be configurable via the interface
$gameConfig = [
    'hand_size' => $_SESSION['hand_size'] ?? 5,
    'draw_deck_size' => $_SESSION['draw_deck_size'] ?? 20,
    'enable_companions' => $_SESSION['enable_companions'] ?? true,
    'show_enemy_cards' => false, // Enemy cards always hidden
    'show_player_cards' => true  // Player cards always visible
];

// ===================================================================
// MECH DATA INITIALIZATION
// ===================================================================
// Base default values
$basePlayerHP = 100;
$baseEnemyHP = 100;

// Default mech stats - can be overridden via config.php
$defaultPlayerMech = [
    'HP' => $_SESSION['player_hp'] ?? $basePlayerHP,
    'ATK' => $_SESSION['player_atk'] ?? 30,
    'DEF' => $_SESSION['player_def'] ?? 15,
    'MAX_HP' => $_SESSION['player_hp'] ?? $basePlayerHP,
    'companion' => $_SESSION['player_companion'] ?? 'Pilot-Alpha'
];

$defaultEnemyMech = [
    'HP' => $_SESSION['enemy_hp'] ?? $baseEnemyHP,
    'ATK' => $_SESSION['enemy_atk'] ?? 25,
    'DEF' => $_SESSION['enemy_def'] ?? 10,
    'MAX_HP' => $_SESSION['enemy_hp'] ?? $baseEnemyHP,
    'companion' => $_SESSION['enemy_companion'] ?? 'AI-Core'
];

// Current mech states (persisted in session)
$playerMech = $_SESSION['playerMech'] ?? $defaultPlayerMech;
$enemyMech = $_SESSION['enemyMech'] ?? $defaultEnemyMech;

// ⚠️  CRITICAL: Ensure MAX_HP is never null or zero
if (!isset($playerMech['MAX_HP']) || $playerMech['MAX_HP'] <= 0) {
    $playerMech['MAX_HP'] = $playerMech['HP'] > 0 ? $playerMech['HP'] : $basePlayerHP;
}

if (!isset($enemyMech['MAX_HP']) || $enemyMech['MAX_HP'] <= 0) {
    $enemyMech['MAX_HP'] = $enemyMech['HP'] > 0 ? $enemyMech['HP'] : $baseEnemyHP;
}

// ⚠️  CRITICAL: Ensure companion keys exist to prevent errors
if (!isset($playerMech['companion']) || empty($playerMech['companion'])) {
    $playerMech['companion'] = $defaultPlayerMech['companion'];
}

if (!isset($enemyMech['companion']) || empty($enemyMech['companion'])) {
    $enemyMech['companion'] = $defaultEnemyMech['companion'];
}

// Initialize card data (placeholder for now)
$playerWeapon = $_SESSION['playerWeapon'] ?? ['name' => 'Plasma Rifle', 'atk' => 15, 'durability' => 100];
$playerArmor = $_SESSION['playerArmor'] ?? ['name' => 'Shield Array', 'def' => 10, 'durability' => 100];
$enemyWeapon = $_SESSION['enemyWeapon'] ?? ['name' => 'Ion Cannon', 'atk' => 12, 'durability' => 100];
$enemyArmor = $_SESSION['enemyArmor'] ?? ['name' => 'Reactive Plating', 'def' => 8, 'durability' => 100];

$gameLog = $_SESSION['log'] ?? [];

// ===================================================================
// FORM PROCESSING
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
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
    
    // Handle log clearing
    if (isset($_POST['clear_log'])) {
        $gameLog = [];
    }
    
    // Handle mech reset
    if (isset($_POST['reset_mechs'])) {
        $playerMech = $defaultPlayerMech;
        $enemyMech = $defaultEnemyMech;
        // Ensure MAX_HP and companion are properly set on reset
        $playerMech['MAX_HP'] = $playerMech['HP'];
        $enemyMech['MAX_HP'] = $enemyMech['HP'];
        $playerMech['companion'] = $defaultPlayerMech['companion'];
        $enemyMech['companion'] = $defaultEnemyMech['companion'];
        $gameLog[] = "[" . date('H:i:s') . "] Mechs reset to full health!";
    }
    
    // Save state back to session
    $_SESSION['playerMech'] = $playerMech;
    $_SESSION['enemyMech'] = $enemyMech;
    $_SESSION['playerWeapon'] = $playerWeapon;
    $_SESSION['playerArmor'] = $playerArmor;
    $_SESSION['enemyWeapon'] = $enemyWeapon;
    $_SESSION['enemyArmor'] = $enemyArmor;
    $_SESSION['log'] = $gameLog;
    
    // Prevent form resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ===================================================================
// HELPER FUNCTIONS
// ===================================================================
function getMechHealthPercent($currentHP, $maxHP) {
    // Safety check: prevent division by zero
    if (!$maxHP || $maxHP <= 0) {
        return 100; // Default to full health if maxHP is invalid
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
    // Safely output HTML, handling null/empty values
    if (empty($value) || $value === null) {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
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
         TOP NAVIGATION BAR
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="config.php" class="config-link">⚙️ Configure Mechs</a>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">NRD TACTICAL SANDBOX</h1>
        </div>
        <div class="nav-right">
            <a href="build-info.php" class="version-badge" title="View Build Information"><?= htmlspecialchars($build['version']) ?></a>
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
                    <?php for ($i = 1; $i <= $gameConfig['hand_size']; $i++): ?>
                        <div class="hand-card face-down fan-card" style="--card-index: <?= $i-1 ?>"></div>
                    <?php endfor; ?>
                </div>
                <div class="hand-label">Enemy Hand (<?= $gameConfig['hand_size'] ?>)</div>
            </div>
            
            <div class="battlefield-layout">
                <!-- Enemy Draw Deck (Far Left) -->
                <div class="draw-deck-area enemy-deck">
                    <div class="draw-pile">
                        <?php for ($i = 0; $i < min(5, $gameConfig['draw_deck_size']); $i++): ?>
                            <div class="draw-card face-down" style="z-index: <?= 5-$i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="deck-label">Draw (<?= $gameConfig['draw_deck_size'] ?>)</div>
                </div>

                <!-- Enemy Weapon Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Enemy Weapon" class="equipment-card weapon-card enemy-equipment">
                            <div class="card-type">WEAPON</div>
                            <div class="card-name"><?= htmlspecialchars($enemyWeapon['name']) ?></div>
                            <div class="card-stats">ATK: +<?= $enemyWeapon['atk'] ?></div>
                        </button>
                    </form>
                </div>

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
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Enemy Armor" class="equipment-card armor-card enemy-equipment">
                            <div class="card-type">ARMOR</div>
                            <div class="card-name"><?= htmlspecialchars($enemyArmor['name']) ?></div>
                            <div class="card-stats">DEF: +<?= $enemyArmor['def'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Spacer (replaces old hand area) -->
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
                    <div class="draw-pile">
                        <?php for ($i = 0; $i < min(5, $gameConfig['draw_deck_size']); $i++): ?>
                            <div class="draw-card face-down" style="z-index: <?= 5-$i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="deck-label">Draw (<?= $gameConfig['draw_deck_size'] ?>)</div>
                </div>

                <!-- Player Weapon Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Player Weapon" class="equipment-card weapon-card player-equipment">
                            <div class="card-type">WEAPON</div>
                            <div class="card-name"><?= htmlspecialchars($playerWeapon['name']) ?></div>
                            <div class="card-stats">ATK: +<?= $playerWeapon['atk'] ?></div>
                        </button>
                    </form>
                </div>

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
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Player Armor" class="equipment-card armor-card player-equipment">
                            <div class="card-type">ARMOR</div>
                            <div class="card-name"><?= htmlspecialchars($playerArmor['name']) ?></div>
                            <div class="card-stats">DEF: +<?= $playerArmor['def'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Spacer (replaces old hand area) -->
                <div class="spacer"></div>
            </div>
            
            <!-- Player Hand (Bottom - Visible Cards in Fan Layout) -->
            <div class="hand-section player-hand-section">
                <div class="hand-label">Your Hand (<?= $gameConfig['hand_size'] ?>)</div>
                <div class="hand-cards-fan">
                    <?php for ($i = 1; $i <= $gameConfig['hand_size']; $i++): ?>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="card_click" value="Player Hand Card <?= $i ?>" class="hand-card face-up fan-card" style="--card-index: <?= $i-1 ?>">
                                <div class="card-mini-name">Card <?= $i ?></div>
                                <div class="card-mini-cost"><?= rand(1,5) ?></div>
                            </button>
                        </form>
                    <?php endfor; ?>
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

        <div class="control-group">
            <h3>Game Log</h3>
            <div class="log-container">
                <div id="game-log" class="game-log">
                    <?php if (empty($gameLog)): ?>
                        <div class="log-empty">No actions yet. Start the battle!</div>
                    <?php else: ?>
                        <?php foreach (array_reverse(array_slice($gameLog, -8)) as $logEntry): ?>
                            <div class="log-entry"><?= htmlspecialchars($logEntry) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="post" class="log-controls">
                    <button type="submit" name="clear_log" value="1" class="clear-btn">Clear Log</button>
                </form>
            </div>
        </div>
    </section>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            Build: <?= htmlspecialchars($build['version']) ?> | 
            Date: <?= htmlspecialchars($build['date']) ?> | 
            <?= htmlspecialchars($build['notes']) ?>
        </div>
    </footer>

</div>

</body>
</html>