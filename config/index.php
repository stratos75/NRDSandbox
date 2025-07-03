<?php
// ===================================================================
// NRD SANDBOX - GAME CONFIGURATION DASHBOARD
// ===================================================================
require '../auth.php';

// Simple version for display purposes
$version = 'v1.0';

// ===================================================================
// GAME RULES MANAGEMENT
// ===================================================================
$gameRules = [
    'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
    'deck_size' => $_SESSION['deck_size'] ?? 20,
    'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
    'starting_player' => $_SESSION['starting_player'] ?? 'player',
    'starting_energy' => $_SESSION['starting_energy'] ?? 5,
    'max_energy' => $_SESSION['max_energy'] ?? 5
];

// Handle game rules updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_game_rules') {
        $_SESSION['starting_hand_size'] = intval($_POST['starting_hand_size'] ?? 5);
        $_SESSION['max_hand_size'] = intval($_POST['max_hand_size'] ?? 7);
        $_SESSION['deck_size'] = intval($_POST['deck_size'] ?? 20);
        $_SESSION['cards_drawn_per_turn'] = intval($_POST['cards_drawn_per_turn'] ?? 1);
        $_SESSION['starting_player'] = $_POST['starting_player'] ?? 'player';
        $_SESSION['starting_energy'] = intval($_POST['starting_energy'] ?? 5);
        $_SESSION['max_energy'] = intval($_POST['max_energy'] ?? 5);
        
        // Update the display values
        $gameRules = [
            'starting_hand_size' => $_SESSION['starting_hand_size'],
            'max_hand_size' => $_SESSION['max_hand_size'],
            'deck_size' => $_SESSION['deck_size'],
            'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'],
            'starting_player' => $_SESSION['starting_player'],
            'starting_energy' => $_SESSION['starting_energy'],
            'max_energy' => $_SESSION['max_energy']
        ];
        
        $successMessage = 'Game rules updated successfully!';
    }
    
    if ($_POST['action'] === 'reset_game_rules') {
        // Reset to defaults
        $_SESSION['starting_hand_size'] = 5;
        $_SESSION['max_hand_size'] = 7;
        $_SESSION['deck_size'] = 20;
        $_SESSION['cards_drawn_per_turn'] = 1;
        $_SESSION['starting_player'] = 'player';
        $_SESSION['starting_energy'] = 5;
        $_SESSION['max_energy'] = 5;
        
        $gameRules = [
            'starting_hand_size' => 5,
            'max_hand_size' => 7,
            'deck_size' => 20,
            'cards_drawn_per_turn' => 1,
            'starting_player' => 'player',
            'starting_energy' => 5,
            'max_energy' => 5
        ];
        
        $successMessage = 'Game rules reset to defaults!';
    }
}

// ===================================================================
// SYSTEM STATUS MONITORING
// ===================================================================
function getSystemStatus() {
    $status = [];
    
    // Authentication
    $status['auth_user'] = $_SESSION['username'] ?? 'Not logged in';
    
    // Game state
    $playerMech = $_SESSION['playerMech'] ?? ['HP' => 100];
    $enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100];
    $playerHand = $_SESSION['player_hand'] ?? [];
    
    $status['player_hp'] = $playerMech['HP'];
    $status['enemy_hp'] = $enemyMech['HP'];
    $status['hand_count'] = count($playerHand);
    
    // Cards system
    $cardsFile = '../data/cards.json';
    if (file_exists($cardsFile)) {
        $cardsData = json_decode(file_get_contents($cardsFile), true);
        $status['cards_working'] = ($cardsData !== null && isset($cardsData['cards']));
        $status['cards_count'] = $status['cards_working'] ? count($cardsData['cards']) : 0;
        
        // Count cards with images
        $cardsWithImages = 0;
        if ($status['cards_working']) {
            foreach ($cardsData['cards'] as $card) {
                if (!empty($card['image']) && file_exists('../' . $card['image'])) {
                    $cardsWithImages++;
                }
            }
        }
        $status['cards_with_images'] = $cardsWithImages;
    } else {
        $status['cards_working'] = false;
        $status['cards_count'] = 0;
        $status['cards_with_images'] = 0;
    }
    
    // Image system
    $imageDir = '../data/images/';
    $status['images_working'] = is_dir($imageDir) && is_writable($imageDir);
    
    // Count images
    $cardImagesDir = $imageDir;
    $mechImagesDir = $imageDir . 'mechs/';
    $status['card_images_count'] = is_dir($cardImagesDir) ? count(glob($cardImagesDir . '*.{png,jpg,jpeg}', GLOB_BRACE)) : 0;
    $status['mech_images_count'] = is_dir($mechImagesDir) ? count(glob($mechImagesDir . '*.{png,jpg,jpeg}', GLOB_BRACE)) : 0;
    
    // Mech images
    $status['player_has_image'] = !empty($playerMech['image']) && file_exists('../' . $playerMech['image']);
    $status['enemy_has_image'] = !empty($enemyMech['image']) && file_exists('../' . $enemyMech['image']);
    
    // Overall system health
    $status['system_health'] = ($status['cards_working'] && $status['images_working']) ? 'GOOD' : 'WARNING';
    
    return $status;
}

$status = getSystemStatus();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Configuration - NRD Sandbox <?= htmlspecialchars($version) ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Fix battlefield container for proper scrolling */
        .battlefield-container {
            height: auto;
            min-height: 100vh;
            overflow-y: auto;
            padding-bottom: 50px;
        }
        
        .config-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .main-panel, .side-panel {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
        }
        
        .panel-header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .panel-header h2 {
            color: #00d4ff;
            margin: 0;
            font-size: 20px;
        }
        
        .form-section {
            margin-bottom: 25px;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
        }
        
        .form-section h3 {
            color: #00d4ff;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            color: #fff;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .form-input, .form-select {
            width: 100%;
            padding: 8px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
        }
        
        .form-input:focus, .form-select:focus {
            border-color: #00d4ff;
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-item {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
        }
        
        .status-item.warning {
            border-color: #ffc107;
        }
        
        .status-label {
            font-size: 11px;
            color: #aaa;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .status-value {
            font-size: 14px;
            color: #fff;
            font-weight: bold;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(145deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(145deg, #ffc107 0%, #e0a800 100%);
            color: #000;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .quick-actions {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .quick-actions h3 {
            color: #00d4ff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .quick-actions .btn {
            width: 100%;
            margin-bottom: 8px;
            text-align: center;
            text-decoration: none;
            display: block;
            margin-right: 0;
        }
        
        .claude-viewer {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.4;
            white-space: pre-wrap;
            color: #ddd;
        }
        
        @media (max-width: 768px) {
            .config-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="battlefield-container">
    <!-- Top Navigation -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="../index.php" class="config-link">🏠 Back to Game</a>
            <span class="user-info">👤 <?= htmlspecialchars($status['auth_user']) ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">⚙️ GAME CONFIGURATION</h1>
        </div>
        <div class="nav-right">
            <span class="version-badge"><?= htmlspecialchars($version) ?></span>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Success Message -->
    <?php if (isset($successMessage)): ?>
        <div class="success-message">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="config-container">
        
        <!-- Main Panel: Game Configuration -->
        <div class="main-panel">
            <div class="panel-header">
                <h2>🎮 Game Rules Configuration</h2>
            </div>
            <div class="panel-content">
                
                <!-- Current Game Rules -->
                <div class="form-section">
                    <h3>📊 Current Settings</h3>
                    <div class="status-grid">
                        <div class="status-item">
                            <div class="status-label">Starting Hand</div>
                            <div class="status-value"><?= $gameRules['starting_hand_size'] ?> cards</div>
                        </div>
                        <div class="status-item">
                            <div class="status-label">Max Hand Size</div>
                            <div class="status-value"><?= $gameRules['max_hand_size'] ?> cards</div>
                        </div>
                        <div class="status-item">
                            <div class="status-label">Deck Size</div>
                            <div class="status-value"><?= $gameRules['deck_size'] ?> cards</div>
                        </div>
                        <div class="status-item">
                            <div class="status-label">Cards Per Turn</div>
                            <div class="status-value"><?= $gameRules['cards_drawn_per_turn'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="status-label">Starting Energy</div>
                            <div class="status-value"><?= $gameRules['starting_energy'] ?></div>
                        </div>
                        <div class="status-item">
                            <div class="status-label">Max Energy</div>
                            <div class="status-value"><?= $gameRules['max_energy'] ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Game Rules Form -->
                <form method="post">
                    <input type="hidden" name="action" value="update_game_rules">
                    
                    <div class="form-section">
                        <h3>🃏 Card Rules</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Starting Hand Size</label>
                                <input type="number" name="starting_hand_size" class="form-input" 
                                       value="<?= $gameRules['starting_hand_size'] ?>" min="1" max="10">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Maximum Hand Size</label>
                                <input type="number" name="max_hand_size" class="form-input" 
                                       value="<?= $gameRules['max_hand_size'] ?>" min="1" max="15">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Deck Size</label>
                                <input type="number" name="deck_size" class="form-input" 
                                       value="<?= $gameRules['deck_size'] ?>" min="10" max="50">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Cards Drawn Per Turn</label>
                                <input type="number" name="cards_drawn_per_turn" class="form-input" 
                                       value="<?= $gameRules['cards_drawn_per_turn'] ?>" min="1" max="5">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>⚡ Energy System</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Starting Energy</label>
                                <input type="number" name="starting_energy" class="form-input" 
                                       value="<?= $gameRules['starting_energy'] ?>" min="1" max="20">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Maximum Energy</label>
                                <input type="number" name="max_energy" class="form-input" 
                                       value="<?= $gameRules['max_energy'] ?>" min="1" max="20">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Starting Player</label>
                            <select name="starting_player" class="form-select">
                                <option value="player" <?= $gameRules['starting_player'] === 'player' ? 'selected' : '' ?>>Player</option>
                                <option value="enemy" <?= $gameRules['starting_player'] === 'enemy' ? 'selected' : '' ?>>Enemy</option>
                                <option value="random" <?= $gameRules['starting_player'] === 'random' ? 'selected' : '' ?>>Random</option>
                            </select>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-success">💾 Save Game Rules</button>
                    </div>
                </form>
                
                <!-- Reset Form -->
                <form method="post" style="text-align: center; margin-top: 15px;">
                    <input type="hidden" name="action" value="reset_game_rules">
                    <button type="submit" class="btn btn-warning" 
                            onclick="return confirm('Reset all game rules to defaults?')">
                        🔄 Reset to Defaults
                    </button>
                </form>
                
            </div>
        </div>

        <!-- Side Panel: Quick Actions & Status -->
        <div class="side-panel">
            
            <!-- Configuration Sections -->
            <div class="quick-actions">
                <h3>⚙️ Configuration Sections</h3>
                <a href="cards.php" class="btn btn-primary">🃏 Card Management</a>
                <a href="mechs.php" class="btn btn-primary">🤖 Mech Configuration</a>
                <a href="debug.php" class="btn btn-warning">🐛 Debug & Diagnostics</a>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>⚡ Quick Actions</h3>
                <a href="../index.php" class="btn btn-success">🏠 Return to Game</a>
                <button onclick="window.location.reload()" class="btn btn-primary">🔄 Refresh Status</button>
            </div>
            
            <!-- System Health -->
            <div class="quick-actions">
                <h3>🔍 System Status</h3>
                <div style="font-size: 11px; color: #ddd; line-height: 1.4;">
                    <strong>Authentication:</strong> <?= $status['auth_user'] ?><br>
                    <strong>Cards System:</strong> <?= $status['cards_working'] ? '✅ Working' : '❌ Broken' ?><br>
                    <strong>Image System:</strong> <?= $status['images_working'] ? '✅ Working' : '❌ Broken' ?><br>
                    <strong>Cards:</strong> <?= $status['cards_count'] ?> total, <?= $status['cards_with_images'] ?? 0 ?> with images<br>
                    <strong>Mechs:</strong> P:<?= $status['player_has_image'] ? '✅' : '❌' ?> E:<?= $status['enemy_has_image'] ? '✅' : '❌' ?><br>
                    <strong>Version:</strong> <?= $version ?><br>
                    <strong>Status:</strong> <?= $status['system_health'] ?>
                </div>
            </div>
            
            <!-- CLAUDE.md Context -->
            <div class="quick-actions">
                <h3>📖 AI Context (CLAUDE.md)</h3>
                <?php
                $claudeMdPath = '../CLAUDE.md';
                if (file_exists($claudeMdPath)) {
                    $claudeContent = file_get_contents($claudeMdPath);
                    $previewContent = substr($claudeContent, 0, 500);
                    echo '<div class="claude-viewer">' . htmlspecialchars($previewContent) . '...</div>';
                    echo '<a href="#" onclick="toggleClaudeView()" class="btn btn-primary" style="margin-top: 10px;">📄 View Full CLAUDE.md</a>';
                } else {
                    echo '<p style="color: #aaa; font-size: 12px;">CLAUDE.md not found</p>';
                }
                ?>
            </div>
            
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            Game Configuration Dashboard | <?= htmlspecialchars($version) ?> | 
            Configure Rules, Mechs & System Settings
        </div>
    </footer>
</div>

<script>
function toggleClaudeView() {
    // Future: Could implement a modal to show full CLAUDE.md content
    alert('Full CLAUDE.md viewer coming soon! For now, check the file directly in your editor.');
}
</script>

</body>
</html>