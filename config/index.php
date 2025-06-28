<?php
// ===================================================================
// NRD SANDBOX - CONFIGURATION DASHBOARD HUB
// ===================================================================
require '../auth.php';

// Load build information and shared config
$build = require '../builds.php';
require 'shared.php';

// Get current configuration overview
$currentConfig = getCurrentConfigurationOverview();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Hub - NRD Sandbox</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="shared.php?css=1">
</head>
<body>

<div class="battlefield-container">
    <?php renderConfigHeader($build, 'Configuration Hub'); ?>

    <!-- ===================================================================
         CONFIGURATION DASHBOARD
         =================================================================== -->
    <main class="config-dashboard">
        
        <!-- Dashboard Header -->
        <section class="dashboard-header">
            <div class="dashboard-title">
                <h1>⚙️ Configuration Dashboard</h1>
                <p>Customize game mechanics and balance for scenario testing</p>
            </div>
            <div class="dashboard-summary">
                <div class="summary-card">
                    <span class="summary-label">Current Scenario</span>
                    <span class="summary-value"><?= htmlspecialchars($currentConfig['scenario_name']) ?></span>
                </div>
            </div>
        </section>

        <!-- Configuration Cards -->
        <section class="config-cards-grid">
            
            <!-- Mech Configuration Card -->
            <div class="config-dashboard-card mech-card">
                <div class="card-header">
                    <div class="card-icon">🤖</div>
                    <div class="card-title">
                        <h3>Mech Configuration</h3>
                        <p>Player and enemy mech stats, presets</p>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="config-preview">
                        <div class="preview-row">
                            <span class="preview-label">Player HP:</span>
                            <span class="preview-value"><?= $currentConfig['player_hp'] ?></span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Enemy HP:</span>
                            <span class="preview-value"><?= $currentConfig['enemy_hp'] ?></span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Combat Mode:</span>
                            <span class="preview-value"><?= $currentConfig['combat_mode'] ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="mechs.php" class="config-btn primary-btn">
                        ⚙️ Configure Mechs
                    </a>
                </div>
            </div>

            <!-- Game Rules Configuration Card -->
            <div class="config-dashboard-card rules-card">
                <div class="card-header">
                    <div class="card-icon">🎲</div>
                    <div class="card-title">
                        <h3>Game Rules</h3>
                        <p>Hand size, deck size, turn system</p>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="config-preview">
                        <div class="preview-row">
                            <span class="preview-label">Hand Size:</span>
                            <span class="preview-value"><?= $currentConfig['hand_size'] ?> cards</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Deck Size:</span>
                            <span class="preview-value"><?= $currentConfig['deck_size'] ?> cards</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Starting Player:</span>
                            <span class="preview-value"><?= ucfirst($currentConfig['starting_player']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="rules.php" class="config-btn primary-btn">
                        🎲 Configure Rules
                    </a>
                </div>
            </div>

            <!-- Card Management Card (Future) -->
            <div class="config-dashboard-card card-mgmt-card disabled">
                <div class="card-header">
                    <div class="card-icon">🃏</div>
                    <div class="card-title">
                        <h3>Card Management</h3>
                        <p>Deck building, card pools, balance</p>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="config-preview">
                        <div class="preview-row">
                            <span class="preview-label">Total Cards:</span>
                            <span class="preview-value"><?= $currentConfig['total_cards'] ?></span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Card Types:</span>
                            <span class="preview-value"><?= $currentConfig['card_types'] ?></span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Status:</span>
                            <span class="preview-value">Planning Phase</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-actions">
                    <button class="config-btn disabled-btn" disabled>
                        🚧 Coming Soon
                    </button>
                </div>
            </div>

            <!-- AI Behavior Card (Future) -->
            <div class="config-dashboard-card ai-card disabled">
                <div class="card-header">
                    <div class="card-icon">🧠</div>
                    <div class="card-title">
                        <h3>AI Behavior</h3>
                        <p>Enemy AI strategies, difficulty</p>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="config-preview">
                        <div class="preview-row">
                            <span class="preview-label">AI Mode:</span>
                            <span class="preview-value">Basic</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Difficulty:</span>
                            <span class="preview-value">Balanced</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Status:</span>
                            <span class="preview-value">Planning Phase</span>
                        </div>
                    </div>
                </div>
                
                <div class="card-actions">
                    <button class="config-btn disabled-btn" disabled>
                        🚧 Coming Soon
                    </button>
                </div>
            </div>

        </section>

        <!-- Quick Actions -->
        <section class="quick-actions">
            <div class="actions-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="actions-grid">
                <a href="../index.php" class="action-btn return-btn">
                    🏠 Return to Game
                </a>
                <button onclick="resetAllConfigurations()" class="action-btn reset-btn">
                    🔄 Reset All Settings
                </button>
                <a href="../build-info.php" class="action-btn info-btn">
                    📦 Build Information
                </a>
                <button onclick="exportConfiguration()" class="action-btn export-btn">
                    📤 Export Settings
                </button>
            </div>
        </section>

    </main>

    <?php renderConfigFooter($build); ?>

</div>

<script>
function resetAllConfigurations() {
    if (confirm('Reset ALL configuration settings to defaults?\n\nThis will affect:\n• Mech stats\n• Game rules\n• All custom settings\n\nThis action cannot be undone.')) {
        // Send reset request
        fetch('shared.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=reset_all_config'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('All settings reset to defaults!');
                window.location.reload();
            } else {
                alert('Error resetting settings: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error during reset.');
        });
    }
}

function exportConfiguration() {
    // Export current configuration as JSON
    fetch('shared.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=export_config'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Download as file
            const blob = new Blob([JSON.stringify(data.config, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'nrd-sandbox-config.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            alert('Error exporting configuration: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error during export.');
    });
}
</script>

</body>
</html>