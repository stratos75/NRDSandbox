<?php
// ===================================================================
// NRD SANDBOX - MAIN GAME INTERFACE
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
    'MAX_HP' => $_SESSION['player_hp'] ?? $basePlayerHP
];

$defaultEnemyMech = [
    'HP' => $_SESSION['enemy_hp'] ?? $baseEnemyHP,
    'ATK' => $_SESSION['enemy_atk'] ?? 25,
    'DEF' => $_SESSION['enemy_def'] ?? 10,
    'MAX_HP' => $_SESSION['enemy_hp'] ?? $baseEnemyHP
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
    
    // Handle log clearing
    if (isset($_POST['clear_log'])) {
        $gameLog = [];
    }
    
    // Handle mech reset
    if (isset($_POST['reset_mechs'])) {
        $playerMech = $defaultPlayerMech;
        $enemyMech = $defaultEnemyMech;
        // Ensure MAX_HP is properly set on reset
        $playerMech['MAX_HP'] = $playerMech['HP'];
        $enemyMech['MAX_HP'] = $enemyMech['HP'];
        $gameLog[] = "[" . date('H:i:s') . "] Mechs reset to full health!";
    }
    
    // Save state back to session
    $_SESSION['playerMech'] = $playerMech;
    $_SESSION['enemyMech'] = $enemyMech;
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRD Sandbox - Tactical Battle Interface</title>
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
            <span class="version-badge"><?= htmlspecialchars($build['version']) ?></span>
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
            
            <div class="battlefield-row">
                <!-- Enemy Deck -->
                <div class="deck-area enemy-deck">
                    <form method="post" class="card-stack">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="submit" name="card_click" value="Enemy Card <?= $i ?>" 
                                    class="game-card enemy-card" title="Enemy Card <?= $i ?>">
                                <div class="card-face"></div>
                            </button>
                        <?php endfor; ?>
                    </form>
                    <div class="deck-label">Enemy Deck</div>
                </div>

                <!-- Enemy Mech -->
                <div class="mech-area enemy-mech">
                    <div class="mech-card <?= getMechStatusClass($enemyMech['HP'], $enemyMech['MAX_HP']) ?>">
                        <div class="mech-hp-circle">
                            <span class="hp-value"><?= $enemyMech['HP'] ?></span>
                        </div>
                        <div class="mech-faction-label">E</div>
                        <div class="mech-body"></div>
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
            
            <div class="battlefield-row">
                <div class="spacer"></div>

                <!-- Player Mech -->
                <div class="mech-area player-mech">
                    <div class="mech-card <?= getMechStatusClass($playerMech['HP'], $playerMech['MAX_HP']) ?>">
                        <div class="mech-hp-circle">
                            <span class="hp-value"><?= $playerMech['HP'] ?></span>
                        </div>
                        <div class="mech-faction-label">P</div>
                        <div class="mech-body"></div>
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

                <!-- Player Deck -->
                <div class="deck-area player-deck">
                    <form method="post" class="card-stack">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <button type="submit" name="card_click" value="Player Card <?= $i ?>" 
                                    class="game-card player-card" title="Player Card <?= $i ?>">
                                <div class="card-face"></div>
                            </button>
                        <?php endfor; ?>
                    </form>
                    <div class="deck-label">Your Deck</div>
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
                        <?php foreach (array_reverse(array_slice($gameLog, -10)) as $logEntry): ?>
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