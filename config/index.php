<?php
// ===================================================================
// NRD SANDBOX - CONFIGURATION DASHBOARD
// ===================================================================
require '../auth.php';
require 'shared.php';

// Get current build and settings for display
$build = require '../builds.php';

// Get current configuration values for overview
$mechConfig = [
    'player_hp' => $_SESSION['player_hp'] ?? 100,
    'enemy_hp' => $_SESSION['enemy_hp'] ?? 100,
];

$gameConfig = [
    'hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'deck_size' => $_SESSION['deck_size'] ?? 20,
];

// Get card count for display
$cardCount = 0;
if (file_exists('../data/cards.json')) {
    $cardData = json_decode(file_get_contents('../data/cards.json'), true);
    $cardCount = count($cardData['cards'] ?? []);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Dashboard - NRD Sandbox</title>
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
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">CONFIGURATION DASHBOARD</h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         CONFIGURATION DASHBOARD CONTENT
         =================================================================== -->
    <main class="config-content">

        <!-- System Overview -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>⚙️ System Overview</h2>
                    <div class="build-badge"><?= htmlspecialchars($build['version']) ?></div>
                </div>
                
                <div class="system-overview">
                    <div class="overview-stats">
                        <div class="stat-card">
                            <div class="stat-icon">🃏</div>
                            <div class="stat-value"><?= $cardCount ?></div>
                            <div class="stat-label">Cards Created</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">👥</div>
                            <div class="stat-value"><?= $mechConfig['player_hp'] ?>HP</div>
                            <div class="stat-label">Player Mech</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">🎲</div>
                            <div class="stat-value"><?= $gameConfig['hand_size'] ?> cards</div>
                            <div class="stat-label">Hand Size</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">📚</div>
                            <div class="stat-value"><?= $gameConfig['deck_size'] ?> cards</div>
                            <div class="stat-label">Deck Size</div>
                        </div>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="../index.php" class="quick-action-btn game-btn">🎮 Return to Game</a>
                        <button onclick="exportAllSettings()" class="quick-action-btn export-btn">📤 Export All Settings</button>
                        <button onclick="resetAllSettings()" class="quick-action-btn reset-btn">🔄 Reset All Settings</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Configuration Sections -->
        <section class="config-section">
            <div class="config-grid">
                
                <!-- Mech Configuration -->
                <div class="config-card section-card">
                    <div class="config-header">
                        <h3>🤖 Mech Configuration</h3>
                        <div class="section-status">Configured</div>
                    </div>
                    
                    <div class="section-content">
                        <p>Configure player and enemy mech statistics, health points, attack power, and defense ratings.</p>
                        
                        <div class="section-preview">
                            <div class="preview-item">
                                <span>Player HP:</span>
                                <span><?= $mechConfig['player_hp'] ?></span>
                            </div>
                            <div class="preview-item">
                                <span>Enemy HP:</span>
                                <span><?= $mechConfig['enemy_hp'] ?></span>
                            </div>
                        </div>
                        
                        <div class="section-actions">
                            <a href="mechs.php" class="action-btn save-btn">⚙️ Configure Mechs</a>
                        </div>
                    </div>
                </div>

                <!-- Game Rules Configuration -->
                <div class="config-card section-card">
                    <div class="config-header">
                        <h3>🎲 Game Rules</h3>
                        <div class="section-status">Configured</div>
                    </div>
                    
                    <div class="section-content">
                        <p>Set hand sizes, deck sizes, turn order, and core game mechanics for scenario testing.</p>
                        
                        <div class="section-preview">
                            <div class="preview-item">
                                <span>Hand Size:</span>
                                <span><?= $gameConfig['hand_size'] ?> cards</span>
                            </div>
                            <div class="preview-item">
                                <span>Deck Size:</span>
                                <span><?= $gameConfig['deck_size'] ?> cards</span>
                            </div>
                        </div>
                        
                        <div class="section-actions">
                            <a href="rules.php" class="action-btn save-btn">🎲 Configure Rules</a>
                        </div>
                    </div>
                </div>

                <!-- Card Management (Future) -->
                <div class="config-card section-card future">
                    <div class="config-header">
                        <h3>🃏 Card Management</h3>
                        <div class="section-status future-status">Coming Soon</div>
                    </div>
                    
                    <div class="section-content">
                        <p>Build decks, assign cards to players, manage card pools, and create scenario-specific collections.</p>
                        
                        <div class="section-preview">
                            <div class="preview-item">
                                <span>Available Cards:</span>
                                <span><?= $cardCount ?> cards</span>
                            </div>
                            <div class="preview-item">
                                <span>Deck Building:</span>
                                <span>In Development</span>
                            </div>
                        </div>
                        
                        <div class="section-actions">
                            <button disabled class="action-btn disabled-btn">🚧 In Development</button>
                        </div>
                    </div>
                </div>

                <!-- AI Context System -->
                <div class="config-card section-card">
                    <div class="config-header">
                        <h3>🤖 AI Context System</h3>
                        <div class="section-status ai-status">Auto-Generated</div>
                    </div>
                    
                    <div class="section-content">
                        <p>Generate complete project context for seamless Claude chat handoffs and development continuity.</p>
                        
                        <div class="section-preview">
                            <div class="preview-item">
                                <span>Current Version:</span>
                                <span><?= htmlspecialchars($build['version']) ?></span>
                            </div>
                            <div class="preview-item">
                                <span>Last Generated:</span>
                                <span><?= date('H:i:s') ?></span>
                            </div>
                        </div>
                        
                        <div class="section-actions">
                            <a href="ai-context.php" class="action-btn attack-btn">🤖 AI Context</a>
                        </div>
                    </div>
                </div>

            </div>
        </section>

    </main>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            Configuration Dashboard | Build <?= htmlspecialchars($build['version']) ?> | 
            Centralized game configuration management
        </div>
    </footer>

</div>

<script>
function exportAllSettings() {
    fetch('shared.php', {
        method: 'POST',
        body: new FormData(Object.assign(document.createElement('form'), {
            innerHTML: '<input name="action" value="export_settings">'
        }))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Create download link
            const blob = new Blob([JSON.stringify(data.data, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'nrd-sandbox-settings.json';
            a.click();
            URL.revokeObjectURL(url);
        } else {
            alert('Export failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Export error:', error);
        alert('Export failed. Please try again.');
    });
}

function resetAllSettings() {
    if (confirm('Are you sure you want to reset ALL settings to defaults? This cannot be undone.')) {
        fetch('shared.php', {
            method: 'POST',
            body: new FormData(Object.assign(document.createElement('form'), {
                innerHTML: '<input name="action" value="reset_all_settings">'
            }))
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All settings have been reset to defaults.');
                location.reload();
            } else {
                alert('Reset failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Reset error:', error);
            alert('Reset failed. Please try again.');
        });
    }
}
</script>

<style>
/* Configuration Dashboard specific styles */
.system-overview {
    padding: 20px;
}

.overview-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid #444;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    border-color: #00d4ff;
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

.stat-value {
    font-size: 18px;
    font-weight: bold;
    color: #00d4ff;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.quick-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.quick-action-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.game-btn {
    background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
    color: white;
}

.export-btn {
    background: linear-gradient(145deg, #17a2b8 0%, #117a8b 100%);
    color: white;
}

.reset-btn {
    background: linear-gradient(145deg, #6c757d 0%, #495057 100%);
    color: white;
}

.config-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.section-card {
    position: relative;
}

.section-content {
    padding: 20px;
}

.section-content p {
    color: #ddd;
    font-size: 14px;
    line-height: 1.5;
    margin-bottom: 15px;
}

.section-preview {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 15px;
}

.preview-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 12px;
}

.preview-item span:first-child {
    color: #aaa;
}

.preview-item span:last-child {
    color: #00d4ff;
    font-weight: bold;
}

.section-actions {
    text-align: center;
}

.section-status {
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 12px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.section-status {
    background: #28a745;
    color: white;
}

.future-status {
    background: #ffc107;
    color: #000;
}

.ai-status {
    background: #17a2b8;
    color: white;
}

.future {
    opacity: 0.7;
}

.disabled-btn {
    background: #6c757d;
    color: #ccc;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .config-grid {
        grid-template-columns: 1fr;
    }
    
    .overview-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

</body>
</html>