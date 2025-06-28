<?php
// ===================================================================
// NRD SANDBOX - GAME RULES CONFIGURATION PAGE
// ===================================================================
require '../auth.php';

// Load build information and shared config
$build = require '../builds.php';
require 'shared.php';

// Set default values for game rules
$defaults = [
    'starting_hand_size' => 5,
    'max_hand_size' => 7,
    'deck_size' => 20,
    'cards_drawn_per_turn' => 1,
    'starting_player' => 'player'
];

$success_message = '';
$error_message = '';

// Save submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $valid = true;
        $new_values = [];
        
        // Validate and sanitize inputs
        foreach ($defaults as $key => $default) {
            if ($key === 'starting_hand_size' || $key === 'max_hand_size') {
                // Hand size validation
                $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
                if ($value < 1 || $value > 10) {
                    $error_message = "Hand sizes must be between 1 and 10 cards.";
                    $valid = false;
                    break;
                }
            } elseif ($key === 'deck_size') {
                // Deck size validation
                $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
                if ($value < 10 || $value > 50) {
                    $error_message = "Deck size must be between 10 and 50 cards.";
                    $valid = false;
                    break;
                }
            } elseif ($key === 'cards_drawn_per_turn') {
                // Cards drawn validation
                $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
                if ($value < 0 || $value > 3) {
                    $error_message = "Cards drawn per turn must be between 0 and 3.";
                    $valid = false;
                    break;
                }
            } elseif ($key === 'starting_player') {
                // Starting player validation
                $value = isset($_POST[$key]) ? $_POST[$key] : $default;
                if (!in_array($value, ['player', 'enemy', 'random'])) {
                    $error_message = "Invalid starting player option.";
                    $valid = false;
                    break;
                }
            } else {
                $value = $default;
            }
            
            $new_values[$key] = $value;
        }
        
        // Additional logical validation
        if ($valid && $new_values['starting_hand_size'] > $new_values['max_hand_size']) {
            $error_message = "Starting hand size cannot be larger than maximum hand size.";
            $valid = false;
        }
        
        if ($valid) {
            // Save to session
            foreach ($new_values as $key => $value) {
                $_SESSION[$key] = $value;
            }
            
            $success_message = "Game rules saved successfully! Changes will apply when you return to the game.";
        }
    } catch (Exception $e) {
        $error_message = "An error occurred while saving game rules.";
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
    <link rel="stylesheet" href="shared.php?css=1">
</head>
<body>

<div class="battlefield-container">
    <?php renderConfigHeader($build, 'Game Rules Configuration'); ?>

    <!-- ===================================================================
         GAME RULES CONFIGURATION CONTENT
         =================================================================== -->
    <main class="config-content">

        <!-- Page Header -->
        <section class="page-header">
            <div class="header-content">
                <h1>🎲 Game Rules Configuration</h1>
                <p>Configure core game mechanics for scenario testing and balance validation</p>
            </div>
            <div class="header-actions">
                <a href="index.php" class="action-btn info-btn">← Config Hub</a>
            </div>
        </section>

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

        <!-- Rules Configuration Form -->
        <section class="config-section">
            <form method="post" class="rules-form">
                <div class="rules-grid">
                    
                    <!-- Hand Management -->
                    <div class="rules-card hand-rules">
                        <div class="rules-header">
                            <div class="rules-icon">🃏</div>
                            <div class="rules-title">
                                <h3>Hand Management</h3>
                                <p>Control card flow and player hand limits</p>
                            </div>
                            <div class="rules-preview">
                                <span class="preview-value" id="handPreview"><?= $current_values['starting_hand_size'] ?>/<?= $current_values['max_hand_size'] ?></span>
                            </div>
                        </div>
                        
                        <div class="rules-content">
                            <div class="input-group">
                                <label for="starting_hand_size" class="input-label">
                                    <span class="label-icon">🎯</span>
                                    Starting Hand Size
                                </label>
                                <input type="number" id="starting_hand_size" name="starting_hand_size" 
                                       value="<?= $current_values['starting_hand_size'] ?>" 
                                       min="1" max="10" required class="stat-input rules-input">
                                <div class="input-help">Number of cards dealt at game start (1-10)</div>
                            </div>

                            <div class="input-group">
                                <label for="max_hand_size" class="input-label">
                                    <span class="label-icon">📏</span>
                                    Maximum Hand Size
                                </label>
                                <input type="number" id="max_hand_size" name="max_hand_size" 
                                       value="<?= $current_values['max_hand_size'] ?>" 
                                       min="1" max="10" required class="stat-input rules-input">
                                <div class="input-help">Maximum cards a player can hold (1-10)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Deck Management -->
                    <div class="rules-card deck-rules">
                        <div class="rules-header">
                            <div class="rules-icon">📚</div>
                            <div class="rules-title">
                                <h3>Deck Management</h3>
                                <p>Configure deck size and draw mechanics</p>
                            </div>
                            <div class="rules-preview">
                                <span class="preview-value" id="deckPreview"><?= $current_values['deck_size'] ?> Cards</span>
                            </div>
                        </div>
                        
                        <div class="rules-content">
                            <div class="input-group">
                                <label for="deck_size" class="input-label">
                                    <span class="label-icon">📦</span>
                                    Total Deck Size
                                </label>
                                <input type="number" id="deck_size" name="deck_size" 
                                       value="<?= $current_values['deck_size'] ?>" 
                                       min="10" max="50" required class="stat-input rules-input">
                                <div class="input-help">Total cards in each player's deck (10-50)</div>
                            </div>

                            <div class="input-group">
                                <label for="cards_drawn_per_turn" class="input-label">
                                    <span class="label-icon">🔄</span>
                                    Cards Drawn Per Turn
                                </label>
                                <input type="number" id="cards_drawn_per_turn" name="cards_drawn_per_turn" 
                                       value="<?= $current_values['cards_drawn_per_turn'] ?>" 
                                       min="0" max="3" required class="stat-input rules-input">
                                <div class="input-help">Cards automatically drawn each turn (0-3)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Turn System -->
                    <div class="rules-card turn-rules">
                        <div class="rules-header">
                            <div class="rules-icon">⚡</div>
                            <div class="rules-title">
                                <h3>Turn System</h3>
                                <p>Configure turn order and flow</p>
                            </div>
                            <div class="rules-preview">
                                <span class="preview-value" id="turnPreview"><?= ucfirst($current_values['starting_player']) ?></span>
                            </div>
                        </div>
                        
                        <div class="rules-content">
                            <div class="input-group">
                                <label for="starting_player" class="input-label">
                                    <span class="label-icon">🎲</span>
                                    Starting Player
                                </label>
                                <select id="starting_player" name="starting_player" class="stat-input rules-input">
                                    <option value="player" <?= $current_values['starting_player'] === 'player' ? 'selected' : '' ?>>Player Always Starts</option>
                                    <option value="enemy" <?= $current_values['starting_player'] === 'enemy' ? 'selected' : '' ?>>Enemy Always Starts</option>
                                    <option value="random" <?= $current_values['starting_player'] === 'random' ? 'selected' : '' ?>>Random Choice</option>
                                </select>
                                <div class="input-help">Who takes the first turn in each game</div>
                            </div>
                        </div>
                    </div>

                    <!-- Game Balance Info -->
                    <div class="rules-card balance-info">
                        <div class="rules-header">
                            <div class="rules-icon">📊</div>
                            <div class="rules-title">
                                <h3>Balance Analysis</h3>
                                <p>Current configuration assessment</p>
                            </div>
                        </div>
                        
                        <div class="rules-content">
                            <div class="balance-metrics">
                                <div class="metric-row">
                                    <span class="metric-label">Game Pace:</span>
                                    <span class="metric-value" id="gamePace">Balanced</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Hand Pressure:</span>
                                    <span class="metric-value" id="handPressure">Medium</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Deck Cycling:</span>
                                    <span class="metric-value" id="deckCycling">~<?= round($current_values['deck_size'] / max(1, $current_values['cards_drawn_per_turn'])) ?> turns</span>
                                </div>
                                <div class="metric-row">
                                    <span class="metric-label">Recommended For:</span>
                                    <span class="metric-value" id="recommended">Strategy Testing</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Action Buttons -->
                <div class="config-actions">
                    <button type="submit" class="action-btn save-btn">
                        💾 Save Game Rules
                    </button>
                    <button type="button" class="action-btn reset-btn" onclick="resetRulesToDefaults()">
                        🔄 Reset to Defaults
                    </button>
                    <a href="../index.php" class="action-btn return-btn">
                        🏠 Return to Game
                    </a>
                </div>
            </form>
        </section>

        <!-- Rules Presets -->
        <section class="config-section">
            <div class="config-card presets-card">
                <div class="config-header">
                    <h2>⚡ Game Rules Presets</h2>
                    <div class="preset-info">
                        <span class="preset-description">Quick configurations for different gameplay scenarios</span>
                    </div>
                </div>
                
                <div class="presets-grid">
                    <button class="preset-btn classic-preset" onclick="applyRulesPreset('classic')">
                        <div class="preset-icon">🎴</div>
                        <div class="preset-title">Classic Card Game</div>
                        <div class="preset-description">Traditional card game balance</div>
                        <div class="preset-stats">5 Start | 7 Max | 20 Deck | 1 Draw</div>
                    </button>
                    
                    <button class="preset-btn fast-preset" onclick="applyRulesPreset('fast')">
                        <div class="preset-icon">⚡</div>
                        <div class="preset-title">Fast Paced</div>
                        <div class="preset-description">Quick games with more card flow</div>
                        <div class="preset-stats">3 Start | 5 Max | 15 Deck | 2 Draw</div>
                    </button>
                    
                    <button class="preset-btn strategic-preset" onclick="applyRulesPreset('strategic')">
                        <div class="preset-icon">🧠</div>
                        <div class="preset-title">Strategic</div>
                        <div class="preset-description">More options, deeper decisions</div>
                        <div class="preset-stats">7 Start | 10 Max | 30 Deck | 1 Draw</div>
                    </button>
                    
                    <button class="preset-btn minimalist-preset" onclick="applyRulesPreset('minimalist')">
                        <div class="preset-icon">🎯</div>
                        <div class="preset-title">Minimalist</div>
                        <div class="preset-description">Limited resources, high tension</div>
                        <div class="preset-stats">2 Start | 4 Max | 12 Deck | 1 Draw</div>
                    </button>
                </div>
            </div>
        </section>

    </main>

    <?php renderConfigFooter($build); ?>

</div>

<script>
// Rules presets
const rulesPresets = {
    classic: {
        starting_hand_size: 5, max_hand_size: 7, 
        deck_size: 20, cards_drawn_per_turn: 1, starting_player: 'player'
    },
    fast: {
        starting_hand_size: 3, max_hand_size: 5, 
        deck_size: 15, cards_drawn_per_turn: 2, starting_player: 'random'
    },
    strategic: {
        starting_hand_size: 7, max_hand_size: 10, 
        deck_size: 30, cards_drawn_per_turn: 1, starting_player: 'player'
    },
    minimalist: {
        starting_hand_size: 2, max_hand_size: 4, 
        deck_size: 12, cards_drawn_per_turn: 1, starting_player: 'random'
    }
};

function applyRulesPreset(presetName) {
    const preset = rulesPresets[presetName];
    if (preset) {
        Object.keys(preset).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = preset[key];
            }
        });
        
        updateRulesPreview();
        
        // Visual feedback
        const presetButtons = document.querySelectorAll('.preset-btn');
        presetButtons.forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.${presetName}-preset`).classList.add('active');
    }
}

function resetRulesToDefaults() {
    document.getElementById('starting_hand_size').value = 5;
    document.getElementById('max_hand_size').value = 7;
    document.getElementById('deck_size').value = 20;
    document.getElementById('cards_drawn_per_turn').value = 1;
    document.getElementById('starting_player').value = 'player';
    
    updateRulesPreview();
    
    // Clear preset selection
    document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
}

function updateRulesPreview() {
    // Update hand preview
    const startHand = document.getElementById('starting_hand_size').value;
    const maxHand = document.getElementById('max_hand_size').value;
    document.getElementById('handPreview').textContent = `${startHand}/${maxHand}`;
    
    // Update deck preview
    const deckSize = document.getElementById('deck_size').value;
    document.getElementById('deckPreview').textContent = `${deckSize} Cards`;
    
    // Update turn preview
    const startPlayer = document.getElementById('starting_player').value;
    document.getElementById('turnPreview').textContent = startPlayer.charAt(0).toUpperCase() + startPlayer.slice(1);
    
    // Update balance analysis
    updateBalanceAnalysis();
}

function updateBalanceAnalysis() {
    const handSize = parseInt(document.getElementById('starting_hand_size').value);
    const deckSize = parseInt(document.getElementById('deck_size').value);
    const drawRate = parseInt(document.getElementById('cards_drawn_per_turn').value);
    
    // Game pace analysis
    let pace = 'Balanced';
    if (drawRate >= 2 || deckSize <= 15) pace = 'Fast';
    else if (drawRate === 0 || deckSize >= 35) pace = 'Slow';
    document.getElementById('gamePace').textContent = pace;
    
    // Hand pressure analysis
    let pressure = 'Medium';
    const maxHand = parseInt(document.getElementById('max_hand_size').value);
    if (maxHand - handSize <= 1) pressure = 'High';
    else if (maxHand - handSize >= 4) pressure = 'Low';
    document.getElementById('handPressure').textContent = pressure;
    
    // Deck cycling
    const cycleTime = Math.round(deckSize / Math.max(1, drawRate));
    document.getElementById('deckCycling').textContent = `~${cycleTime} turns`;
    
    // Recommendation
    let recommendation = 'Strategy Testing';
    if (pace === 'Fast') recommendation = 'Quick Testing';
    else if (pressure === 'High') recommendation = 'Tension Testing';
    else if (deckSize >= 30) recommendation = 'Endurance Testing';
    document.getElementById('recommended').textContent = recommendation;
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const rulesInputs = document.querySelectorAll('.rules-input');
    rulesInputs.forEach(input => {
        input.addEventListener('input', updateRulesPreview);
        input.addEventListener('change', updateRulesPreview);
    });
    
    // Initialize
    updateRulesPreview();
});
</script>

<style>
/* Rules Configuration Specific Styles */
.rules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.rules-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid #333;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.rules-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

.hand-rules {
    border-left: 4px solid #28a745;
}

.deck-rules {
    border-left: 4px solid #17a2b8;
}

.turn-rules {
    border-left: 4px solid #ffc107;
}

.balance-info {
    border-left: 4px solid #9c27b0;
}

.rules-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid #333;
}

.rules-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.rules-title {
    flex: 1;
}

.rules-title h3 {
    color: #00d4ff;
    font-size: 16px;
    margin: 0 0 4px 0;
}

.rules-title p {
    color: #aaa;
    margin: 0;
    font-size: 12px;
}

.rules-preview .preview-value {
    background: rgba(0, 212, 255, 0.2);
    color: #00d4ff;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    border: 1px solid #00d4ff;
}

.rules-content {
    padding: 20px;
}

.rules-input:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 10px rgba(23, 162, 184, 0.3);
}

/* Balance Analysis Specific */
.balance-metrics {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.metric-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    border-left: 3px solid #9c27b0;
}

.metric-label {
    color: #aaa;
    font-size: 13px;
    font-weight: bold;
}

.metric-value {
    color: #fff;
    font-size: 13px;
    font-weight: bold;
}

/* Enhanced Presets */
.presets-card {
    border-left: 4px solid #fd7e14;
    grid-column: 1 / -1;
}

.preset-btn.active {
    background: rgba(0, 212, 255, 0.15);
    border-color: #00d4ff;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
}

@media (max-width: 768px) {
    .rules-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .rules-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .presets-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
}
</style>

</body>
</html>