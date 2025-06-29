<?php
// ===================================================================
// NRD SANDBOX - MECH CONFIGURATION PAGE
// ===================================================================
require '../auth.php';

// Load build information and shared config
$build = require '../builds.php';
require 'shared.php';

// Set default values for mech stats
$defaults = [
    'player_hp' => 100,
    'player_atk' => 30,
    'player_def' => 15,
    'enemy_hp'  => 100,
    'enemy_atk' => 25,
    'enemy_def' => 10
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
            $value = isset($_POST[$key]) ? intval($_POST[$key]) : $default;
            
            // Basic validation
            if ($value < 1 || $value > 999) {
                $error_message = "All mech stats must be between 1 and 999.";
                $valid = false;
                break;
            }
            
            $new_values[$key] = $value;
        }
        
        if ($valid) {
            // Save to session
            foreach ($new_values as $key => $value) {
                $_SESSION[$key] = $value;
            }
            
            // Clear existing mech data to force reset with new values
            unset($_SESSION['playerMech']);
            unset($_SESSION['enemyMech']);
            
            $success_message = "Mech configuration saved successfully! Changes will apply when you return to the game.";
        }
    } catch (Exception $e) {
        $error_message = "An error occurred while saving mech configuration.";
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
    <title>Mech Configuration - NRD Sandbox</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="shared.php?css=1">
</head>
<body>

<div class="battlefield-container">
    <?php renderConfigHeader($build, 'Mech Configuration'); ?>

    <!-- ===================================================================
         MECH CONFIGURATION CONTENT
         =================================================================== -->
    <main class="config-content">

        <!-- Page Header -->
        <section class="page-header">
            <div class="header-content">
                <h1>🤖 Mech Configuration</h1>
                <p>Configure player and enemy mech statistics for balanced combat scenarios</p>
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

        <!-- Configuration Form -->
        <section class="config-section">
            <div class="config-grid">
                
                <!-- Player Mech Configuration -->
                <div class="config-card player-config">
                    <div class="config-header">
                        <h2>👤 Player Mech</h2>
                        <div class="mech-preview">
                            <div class="mini-mech player-mech">
                                <div class="mini-hp"><?= $current_values['player_hp'] ?></div>
                                <div class="mini-label">P</div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" class="config-form">
                        <div class="stat-inputs">
                            <div class="input-group">
                                <label for="player_hp" class="input-label">
                                    <span class="label-icon">❤️</span>
                                    Health Points (HP)
                                </label>
                                <input type="number" id="player_hp" name="player_hp" 
                                       value="<?= $current_values['player_hp'] ?>" 
                                       min="1" max="999" required class="stat-input hp-input">
                                <div class="input-help">Maximum health for your mech</div>
                            </div>

                            <div class="input-group">
                                <label for="player_atk" class="input-label">
                                    <span class="label-icon">⚔️</span>
                                    Attack Power (ATK)
                                </label>
                                <input type="number" id="player_atk" name="player_atk" 
                                       value="<?= $current_values['player_atk'] ?>" 
                                       min="1" max="999" required class="stat-input atk-input">
                                <div class="input-help">Damage dealt per attack</div>
                            </div>

                            <div class="input-group">
                                <label for="player_def" class="input-label">
                                    <span class="label-icon">🛡️</span>
                                    Defense Rating (DEF)
                                </label>
                                <input type="number" id="player_def" name="player_def" 
                                       value="<?= $current_values['player_def'] ?>" 
                                       min="1" max="999" required class="stat-input def-input">
                                <div class="input-help">Damage reduction capability</div>
                            </div>
                        </div>
                </div>

                <!-- Enemy Mech Configuration -->
                <div class="config-card enemy-config">
                    <div class="config-header">
                        <h2>🤖 Enemy Mech</h2>
                        <div class="mech-preview">
                            <div class="mini-mech enemy-mech">
                                <div class="mini-hp"><?= $current_values['enemy_hp'] ?></div>
                                <div class="mini-label">E</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-inputs">
                        <div class="input-group">
                            <label for="enemy_hp" class="input-label">
                                <span class="label-icon">❤️</span>
                                Health Points (HP)
                            </label>
                            <input type="number" id="enemy_hp" name="enemy_hp" 
                                   value="<?= $current_values['enemy_hp'] ?>" 
                                   min="1" max="999" required class="stat-input hp-input">
                            <div class="input-help">Maximum health for enemy mech</div>
                        </div>

                        <div class="input-group">
                            <label for="enemy_atk" class="input-label">
                                <span class="label-icon">⚔️</span>
                                Attack Power (ATK)
                            </label>
                            <input type="number" id="enemy_atk" name="enemy_atk" 
                                   value="<?= $current_values['enemy_atk'] ?>" 
                                   min="1" max="999" required class="stat-input atk-input">
                            <div class="input-help">Damage dealt per attack</div>
                        </div>

                        <div class="input-group">
                            <label for="enemy_def" class="input-label">
                                <span class="label-icon">🛡️</span>
                                Defense Rating (DEF)
                            </label>
                            <input type="number" id="enemy_def" name="enemy_def" 
                                   value="<?= $current_values['enemy_def'] ?>" 
                                   min="1" max="999" required class="stat-input def-input">
                            <div class="input-help">Damage reduction capability</div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Action Buttons -->
            <div class="config-actions">
                <button type="submit" class="action-btn save-btn">
                    💾 Save Mech Configuration
                </button>
                <button type="button" class="action-btn reset-btn" onclick="resetToDefaults()">
                    🔄 Reset to Defaults
                </button>
                <a href="../index.php" class="action-btn return-btn">
                    🏠 Return to Game
                </a>
            </div>

            </form>
        </section>

        <!-- Quick Presets -->
        <section class="config-section">
            <div class="config-card presets-card">
                <div class="config-header">
                    <h2>⚡ Combat Presets</h2>
                    <div class="preset-info">
                        <span class="preset-description">Quick configurations for different battle scenarios</span>
                    </div>
                </div>
                
                <div class="presets-grid">
                    <button class="preset-btn balanced-preset" onclick="applyPreset('balanced')">
                        <div class="preset-icon">⚖️</div>
                        <div class="preset-title">Balanced Combat</div>
                        <div class="preset-description">Equal stats for fair, strategic battles</div>
                        <div class="preset-stats">HP: 100 | ATK: 30 | DEF: 15</div>
                    </button>
                    
                    <button class="preset-btn tank-preset" onclick="applyPreset('tank')">
                        <div class="preset-icon">🛡️</div>
                        <div class="preset-title">Tank Battle</div>
                        <div class="preset-description">High HP and defense, longer battles</div>
                        <div class="preset-stats">HP: 200 | ATK: 20 | DEF: 25</div>
                    </button>
                    
                    <button class="preset-btn glass-preset" onclick="applyPreset('glass_cannon')">
                        <div class="preset-icon">💥</div>
                        <div class="preset-title">Glass Cannon</div>
                        <div class="preset-description">High damage, low defense, quick battles</div>
                        <div class="preset-stats">HP: 75 | ATK: 50 | DEF: 5</div>
                    </button>
                    
                    <button class="preset-btn endurance-preset" onclick="applyPreset('endurance')">
                        <div class="preset-icon">🏃</div>
                        <div class="preset-title">Endurance Test</div>
                        <div class="preset-description">Extended combat for testing mechanics</div>
                        <div class="preset-stats">HP: 300 | ATK: 15 | DEF: 20</div>
                    </button>
                </div>
            </div>
        </section>

    </main>

    <?php renderConfigFooter($build); ?>

</div>

<script>
// Preset configurations
const presets = {
    balanced: {
        player_hp: 100, player_atk: 30, player_def: 15,
        enemy_hp: 100, enemy_atk: 30, enemy_def: 15
    },
    tank: {
        player_hp: 200, player_atk: 20, player_def: 25,
        enemy_hp: 200, enemy_atk: 20, enemy_def: 25
    },
    glass_cannon: {
        player_hp: 75, player_atk: 50, player_def: 5,
        enemy_hp: 75, enemy_atk: 50, enemy_def: 5
    },
    endurance: {
        player_hp: 300, player_atk: 15, player_def: 20,
        enemy_hp: 300, enemy_atk: 15, enemy_def: 20
    }
};

function applyPreset(presetName) {
    const preset = presets[presetName];
    if (preset) {
        Object.keys(preset).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = preset[key];
                updatePreview();
            }
        });
        
        // Visual feedback
        const presetButtons = document.querySelectorAll('.preset-btn');
        presetButtons.forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.${presetName.replace('_', '-')}-preset`).classList.add('active');
    }
}

function resetToDefaults() {
    document.getElementById('player_hp').value = 100;
    document.getElementById('player_atk').value = 30;
    document.getElementById('player_def').value = 15;
    document.getElementById('enemy_hp').value = 100;
    document.getElementById('enemy_atk').value = 25;
    document.getElementById('enemy_def').value = 10;
    updatePreview();
    
    // Clear preset selection
    document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
}

function updatePreview() {
    // Update mini mech previews
    const playerHP = document.querySelector('.player-config .mini-hp');
    const enemyHP = document.querySelector('.enemy-config .mini-hp');
    
    if (playerHP) playerHP.textContent = document.getElementById('player_hp').value;
    if (enemyHP) enemyHP.textContent = document.getElementById('enemy_hp').value;
}

// Add event listeners for real-time preview updates
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.stat-input');
    inputs.forEach(input => {
        input.addEventListener('input', updatePreview);
    });
});
</script>

<style>
/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    border: 1px solid #333;
}

.header-content h1 {
    color: #00d4ff;
    font-size: 24px;
    margin: 0 0 8px 0;
}

.header-content p {
    color: #aaa;
    margin: 0;
    font-size: 14px;
}

/* Enhanced Preset Cards */
.presets-card {
    border-left: 4px solid #ffc107;
}

.preset-info {
    color: #aaa;
    font-size: 13px;
}

.presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
}

.preset-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid #444;
    border-radius: 12px;
    padding: 20px;
    color: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.preset-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: #00d4ff;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
}

.preset-btn.active {
    background: rgba(0, 212, 255, 0.1);
    border-color: #00d4ff;
    box-shadow: 0 0 15px rgba(0, 212, 255, 0.3);
}

.preset-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.preset-title {
    font-size: 16px;
    font-weight: bold;
    color: #00d4ff;
    margin-bottom: 8px;
}

.preset-description {
    font-size: 12px;
    color: #aaa;
    margin-bottom: 10px;
    line-height: 1.4;
}

.preset-stats {
    font-size: 11px;
    color: #ddd;
    background: rgba(0, 0, 0, 0.3);
    padding: 6px 10px;
    border-radius: 6px;
    font-family: monospace;
}

/* Mech Configuration Specific Styles */
.config-content {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
    max-width: 1200px;
    margin: 0 auto;
}

.config-section {
    margin-bottom: 25px;
}

.config-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.config-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid #333;
    border-radius: 12px;
    overflow: hidden;
}

.player-config {
    border-left: 4px solid #28a745;
}

.enemy-config {
    border-left: 4px solid #dc3545;
}

.config-header {
    background: rgba(0, 0, 0, 0.5);
    padding: 15px 20px;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.config-header h2 {
    color: #00d4ff;
    font-size: 18px;
    margin: 0;
}

.mech-preview {
    display: flex;
    align-items: center;
}

.mini-mech {
    width: 40px;
    height: 50px;
    background: linear-gradient(145deg, #2a2a3e 0%, #1e1e2e 100%);
    border-radius: 6px;
    position: relative;
    border: 1px solid #444;
}

.mini-hp {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: bold;
    color: #333;
}

.mini-label {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 14px;
    height: 14px;
    background: rgba(0, 0, 0, 0.8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    color: #fff;
}

.player-mech .mini-label {
    background: rgba(40, 167, 69, 0.9);
}

.enemy-mech .mini-label {
    background: rgba(220, 53, 69, 0.9);
}

.stat-inputs {
    padding: 20px;
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

.hp-input:focus {
    border-color: #dc3545;
    box-shadow: 0 0 10px rgba(220, 53, 69, 0.3);
}

.atk-input:focus {
    border-color: #ffc107;
    box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
}

.def-input:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 10px rgba(23, 162, 184, 0.3);
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
    color: white;
}

.save-btn:hover {
    background: linear-gradient(145deg, #34ce57 0%, #257e42 100%);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(40, 167, 69, 0.4);
}

.message {
    margin-bottom: 20px;
    padding: 12px 15px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.success-message {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid #28a745;
    color: #28a745;
}

.error-message {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid #dc3545;
    color: #dc3545;
}

.message-icon {
    font-size: 16px;
}

@media (max-width: 768px) {
    .config-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .config-actions {
        flex-direction: column;
    }
    
    .presets-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}
</style>

</body>
</html>