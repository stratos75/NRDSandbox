<?php
// ===================================================================
// NRD SANDBOX - GAME RULES CONFIGURATION
// ===================================================================
require '../auth.php';
require 'shared.php';

// Get current build information
$build = require '../build-data.php';  // FIXED: Updated to use build-data.php

$success_message = '';
$error_message = '';

// Default game rules
$defaults = [
    'starting_hand_size' => 5,
    'max_hand_size' => 7,
    'deck_size' => 20,
    'cards_drawn_per_turn' => 1,
    'starting_player' => 'player'
];

// Save submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $valid = true;
        $new_values = [];
        
        // Validate and sanitize inputs
        foreach ($defaults as $key => $default) {
            if ($key === 'starting_player') {
                $value = isset($_POST[$key]) ? $_POST[$key] : $default;
                if (!in_array($value, ['player', 'enemy'])) {
                    $error_message = "Starting player must be 'player' or 'enemy'.";
                    $valid = false;
                    break;
                }
            } else {
                $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
                
                // Basic validation
                if ($value < 1 || $value > 50) {
                    $error_message = "All numeric values must be between 1 and 50.";
                    $valid = false;
                    break;
                }
                
                // Specific validation
                if ($key === 'starting_hand_size' && $value > $_POST['max_hand_size']) {
                    $error_message = "Starting hand size cannot be larger than maximum hand size.";
                    $valid = false;
                    break;
                }
            }
            
            $new_values[$key] = $value;
        }
        
        if ($valid) {
            // Save to session
            foreach ($new_values as $key => $value) {
                $_SESSION[$key] = $value;
            }
            
            $success_message = "Game rules updated successfully! Changes will apply to new games.";
        }
    } catch (Exception $e) {
        $error_message = "An error occurred while saving rules configuration.";
    }
}

// Get current values (from session or defaults)
$current_values = [];
foreach ($defaults as $key => $default) {
    $current_values[$key] = $_SESSION[$key] ?? $default;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Rules Configuration - NRD Sandbox</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<div class="battlefield-container">

    <!-- ===================================================================
         TOP NAVIGATION BAR
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="../index.php" class="config-link">🏠 Back to Game</a>
            <a href="index.php" class="config-link">📊 Dashboard</a>
            <a href="../build-info.php" class="config-link">📦 Build Info</a>
        </div>
        <div class="nav-center">
            <h1 class="game-title">GAME RULES CONFIGURATION</h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge" title="View Build Information"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         CONFIGURATION CONTENT
         =================================================================== -->
    <main class="config-content">

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
            <div class="message success-message">
                <span class="message-icon">✅</span>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error-message">
                <span class="message-icon">❌</span>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Configuration Form -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>🎲 Game Rules Configuration</h2>
                    <div class="build-badge"><?= htmlspecialchars($build['version']) ?></div>
                </div>
                
                <form method="post" class="config-form">
                    <div class="config-grid-rules">
                        
                        <!-- Hand Management -->
                        <div class="rules-group">
                            <h3>🃏 Hand Management</h3>
                            
                            <div class="input-group">
                                <label for="starting_hand_size" class="input-label">
                                    <span class="label-icon">📋</span>
                                    Starting Hand Size
                                </label>
                                <input type="number" id="starting_hand_size" name="starting_hand_size" 
                                       value="<?= $current_values['starting_hand_size'] ?>" 
                                       min="1" max="15" required class="stat-input">
                                <div class="input-help">How many cards players start with</div>
                            </div>

                            <div class="input-group">
                                <label for="max_hand_size" class="input-label">
                                    <span class="label-icon">📚</span>
                                    Maximum Hand Size
                                </label>
                                <input type="number" id="max_hand_size" name="max_hand_size" 
                                       value="<?= $current_values['max_hand_size'] ?>" 
                                       min="1" max="20" required class="stat-input">
                                <div class="input-help">Maximum cards a player can hold</div>
                            </div>
                        </div>

                        <!-- Deck Configuration -->
                        <div class="rules-group">
                            <h3>📦 Deck Configuration</h3>
                            
                            <div class="input-group">
                                <label for="deck_size" class="input-label">
                                    <span class="label-icon">🎯</span>
                                    Deck Size
                                </label>
                                <input type="number" id="deck_size" name="deck_size" 
                                       value="<?= $current_values['deck_size'] ?>" 
                                       min="10" max="50" required class="stat-input">
                                <div class="input-help">Total cards in each player's deck</div>
                            </div>

                            <div class="input-group">
                                <label for="cards_drawn_per_turn" class="input-label">
                                    <span class="label-icon">⚡</span>
                                    Cards Per Turn
                                </label>
                                <input type="number" id="cards_drawn_per_turn" name="cards_drawn_per_turn" 
                                       value="<?= $current_values['cards_drawn_per_turn'] ?>" 
                                       min="1" max="5" required class="stat-input">
                                <div class="input-help">Cards drawn each turn</div>
                            </div>
                        </div>

                        <!-- Turn Order -->
                        <div class="rules-group">
                            <h3>🎮 Turn Order</h3>
                            
                            <div class="input-group">
                                <label for="starting_player" class="input-label">
                                    <span class="label-icon">🏁</span>
                                    Starting Player
                                </label>
                                <select id="starting_player" name="starting_player" class="stat-input">
                                    <option value="player" <?= $current_values['starting_player'] === 'player' ? 'selected' : '' ?>>
                                        Player Goes First
                                    </option>
                                    <option value="enemy" <?= $current_values['starting_player'] === 'enemy' ? 'selected' : '' ?>>
                                        Enemy Goes First
                                    </option>
                                </select>
                                <div class="input-help">Who takes the first turn</div>
                            </div>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="config-actions">
                        <button type="submit" class="action-btn save-btn">
                            💾 Save Rules
                        </button>
                        <button type="button" class="action-btn reset-btn" onclick="resetToDefaults()">
                            🔄 Reset to Defaults
                        </button>
                        <a href="../index.php" class="action-btn cancel-btn">
                            ↩️ Return to Game
                        </a>
                    </div>
                </form>
            </div>
        </section>

        <!-- Quick Presets -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>⚡ Quick Presets</h2>
                </div>
                <div class="presets-grid">
                    <button class="preset-btn" onclick="applyPreset('classic')">
                        🎯 Classic<br>
                        <small>5/7 hand, 20 deck, standard rules</small>
                    </button>
                    <button class="preset-btn" onclick="applyPreset('fast')">
                        ⚡ Fast Play<br>
                        <small>3/5 hand, 15 deck, quick games</small>
                    </button>
                    <button class="preset-btn" onclick="applyPreset('strategic')">
                        🧠 Strategic<br>
                        <small>7/10 hand, 30 deck, deep strategy</small>
                    </button>
                    <button class="preset-btn" onclick="applyPreset('testing')">
                        🔬 Testing<br>
                        <small>10/15 hand, large decks for development</small>
                    </button>
                </div>
            </div>
        </section>

    </main>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            Game Rules Configuration | Build <?= htmlspecialchars($build['version']) ?> | 
            Configure hand sizes, deck composition, and turn order
        </div>
    </footer>

</div>

<script>
// Preset configurations
const presets = {
    classic: {
        starting_hand_size: 5,
        max_hand_size: 7,
        deck_size: 20,
        cards_drawn_per_turn: 1,
        starting_player: 'player'
    },
    fast: {
        starting_hand_size: 3,
        max_hand_size: 5,
        deck_size: 15,
        cards_drawn_per_turn: 1,
        starting_player: 'player'
    },
    strategic: {
        starting_hand_size: 7,
        max_hand_size: 10,
        deck_size: 30,
        cards_drawn_per_turn: 1,
        starting_player: 'player'
    },
    testing: {
        starting_hand_size: 10,
        max_hand_size: 15,
        deck_size: 50,
        cards_drawn_per_turn: 2,
        starting_player: 'player'
    }
};

function applyPreset(presetName) {
    const preset = presets[presetName];
    if (preset) {
        Object.keys(preset).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = preset[key];
            }
        });
    }
}

function resetToDefaults() {
    document.getElementById('starting_hand_size').value = 5;
    document.getElementById('max_hand_size').value = 7;
    document.getElementById('deck_size').value = 20;
    document.getElementById('cards_drawn_per_turn').value = 1;
    document.getElementById('starting_player').value = 'player';
}
</script>

<style>
/* Rules configuration specific styles */
.config-grid-rules {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.rules-group {
    background: rgba(0, 0, 0, 0.2);
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
}

.rules-group h3 {
    color: #00d4ff;
    margin-bottom: 15px;
    font-size: 16px;
    border-bottom: 1px solid #333;
    padding-bottom: 5px;
}

.input-group {
    margin-bottom: 20px;
}

.input-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: bold;
    color: #ddd;
    margin-bottom: 8px;
    font-size: 14px;
}

.label-icon {
    font-size: 16px;
}

.stat-input {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid #444;
    border-radius: 6px;
    color: #fff;
    font-size: 16px;
    transition: all 0.3s ease;
}

.stat-input:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
}

.input-help {
    font-size: 12px;
    color: #888;
    margin-top: 4px;
    font-style: italic;
}

.config-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 20px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid #333;
}

.save-btn {
    background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
}

.reset-btn {
    background: linear-gradient(145deg, #6c757d 0%, #495057 100%);
}

.cancel-btn {
    background: linear-gradient(145deg, #17a2b8 0%, #117a8b 100%);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
}

.preset-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid #444;
    border-radius: 8px;
    padding: 15px;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.preset-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: #00d4ff;
    transform: translateY(-2px);
}

.preset-btn small {
    display: block;
    color: #aaa;
    margin-top: 5px;
    font-size: 11px;
}

@media (max-width: 768px) {
    .config-grid-rules {
        grid-template-columns: 1fr;
    }
    
    .config-actions {
        flex-direction: column;
    }
    
    .presets-grid {
        grid-template-columns: 1fr;
    }
}
</style>

</body>
</html>