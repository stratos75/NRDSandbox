<?php
// ===================================================================
// NRD SANDBOX - CONSOLIDATED CONFIGURATION DASHBOARD v0.9.4
// ===================================================================
require '../auth.php';

// Get current build information
$build = require '../build-data.php';

// ===================================================================
// SESSION NOTES MANAGEMENT
// ===================================================================
$sessionNotesFile = '../data/session-notes.json';
$sessionNotes = [
    'current_focus' => '',
    'next_priority' => '',
    'recent_changes' => '',
    'known_issues' => '',
    'updated_at' => ''
];

// Load existing session notes
if (file_exists($sessionNotesFile)) {
    $loadedNotes = json_decode(file_get_contents($sessionNotesFile), true);
    if ($loadedNotes) {
        $sessionNotes = array_merge($sessionNotes, $loadedNotes);
    }
}

// Handle session notes saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_session_notes') {
        $sessionNotes = [
            'current_focus' => $_POST['current_focus'] ?? '',
            'next_priority' => $_POST['next_priority'] ?? '',
            'recent_changes' => $_POST['recent_changes'] ?? '',
            'known_issues' => $_POST['known_issues'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($sessionNotesFile, json_encode($sessionNotes, JSON_PRETTY_PRINT));
        $successMessage = 'Session notes saved successfully!';
    }
    
    // Handle quick config changes
    if ($_POST['action'] === 'quick_save_mechs') {
        $_SESSION['player_hp'] = intval($_POST['player_hp']);
        $_SESSION['enemy_hp'] = intval($_POST['enemy_hp']);
        $_SESSION['player_atk'] = intval($_POST['player_atk']);
        $_SESSION['enemy_atk'] = intval($_POST['enemy_atk']);
        
        // Clear existing mech data to force reset
        unset($_SESSION['playerMech']);
        unset($_SESSION['enemyMech']);
        
        $successMessage = 'Mech stats updated successfully!';
    }
    
    if ($_POST['action'] === 'quick_save_rules') {
        $_SESSION['starting_hand_size'] = intval($_POST['starting_hand_size']);
        $_SESSION['max_hand_size'] = intval($_POST['max_hand_size']);
        $_SESSION['deck_size'] = intval($_POST['deck_size']);
        
        $successMessage = 'Game rules updated successfully!';
    }
}

// ===================================================================
// SYSTEM DIAGNOSTICS
// ===================================================================
function getSystemDiagnostics() {
    $diagnostics = [];
    
    // Authentication status
    $diagnostics['auth'] = [
        'status' => isset($_SESSION['username']) ? 'ACTIVE' : 'INACTIVE',
        'user' => $_SESSION['username'] ?? 'None'
    ];
    
    // JSON file system
    $cardsFile = '../data/cards.json';
    $diagnostics['json'] = [
        'cards_exist' => file_exists($cardsFile),
        'cards_writable' => is_writable($cardsFile),
        'cards_count' => 0
    ];
    
    if (file_exists($cardsFile)) {
        $cardData = json_decode(file_get_contents($cardsFile), true);
        $diagnostics['json']['cards_count'] = count($cardData['cards'] ?? []);
    }
    
    // Game state
    $playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100];
    $enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100];
    $playerHand = $_SESSION['player_hand'] ?? [];
    
    $diagnostics['game_state'] = [
        'player_hp' => $playerMech['HP'],
        'enemy_hp' => $enemyMech['HP'],
        'hand_count' => count($playerHand)
    ];
    
    return $diagnostics;
}

// ===================================================================
// AI CONTEXT GENERATION (UPDATED)
// ===================================================================
function generateAIContext($build, $sessionNotes, $diagnostics) {
    $timestamp = date('Y-m-d H:i:s');
    $version = $build['version'];
    
    $context = "# NRD Sandbox - AI Context Handoff {$version}
**Generated:** {$timestamp} | **For:** Next Claude Session
**Session Status:** Active Development

## 🚦 **SYSTEM STATUS**
- Authentication: " . $diagnostics['auth']['status'] . " (User: " . $diagnostics['auth']['user'] . ")
- Cards: " . $diagnostics['json']['cards_count'] . " loaded | Writable: " . ($diagnostics['json']['cards_writable'] ? 'YES' : 'NO') . "
- Game State: Player HP " . $diagnostics['game_state']['player_hp'] . " | Enemy HP " . $diagnostics['game_state']['enemy_hp'] . " | Hand: " . $diagnostics['game_state']['hand_count'] . " cards

## 📝 **SESSION NOTES**
**Last Updated:** " . ($sessionNotes['updated_at'] ?: 'Never') . "

**Current Focus:** " . ($sessionNotes['current_focus'] ?: 'Not specified') . "

**Next Priority:** " . ($sessionNotes['next_priority'] ?: 'Not specified') . "

**Recent Changes:** " . ($sessionNotes['recent_changes'] ?: 'None specified') . "

**Known Issues:** " . ($sessionNotes['known_issues'] ?: 'None specified') . "

## 🎯 **PROJECT OVERVIEW**
NRD Sandbox is a PHP-based tactical card battle game development tool. Current features include:
- ✅ Authentication system (STABLE)
- ✅ AJAX combat with mechs (STABLE)
- ✅ Equipment system (equip/unequip weapons/armor) (STABLE)
- ✅ Card creator with JSON storage (STABLE)
- ✅ Debug panels and configuration (STABLE)
- ✅ Real-time diagnostics (STABLE)
- ✅ Consolidated dashboard config (LATEST)

## 🔧 **TECHNICAL CONTEXT**
- **Environment:** Local LAMP stack, VS Code
- **Files:** All core systems working in " . dirname(__DIR__) . "
- **Database:** JSON files (data/cards.json) - WORKING PERFECTLY
- **Version:** {$version}
- **Session ID:** " . substr(session_id(), 0, 8) . "...

## 📋 **QUICK REFERENCE**
- **Login:** admin/password123
- **Main Interface:** index.php (battlefield with fan card layout)
- **Card Creator:** Right-slide panel with live preview (STABLE)
- **Debug Panel:** Left-slide panel with diagnostics (STABLE)
- **Config Dashboard:** config/index.php (consolidated control center)

## 🚀 **READY FOR DEVELOPMENT**
All systems stable and ready for continued development. Card creation/management is now fully functional. Next logical step is implementing card effects system.";

    return $context;
}

$diagnostics = getSystemDiagnostics();
$aiContext = generateAIContext($build, $sessionNotes, $diagnostics);

// Get current configuration values
$currentConfig = [
    'player_hp' => $_SESSION['player_hp'] ?? 100,
    'player_atk' => $_SESSION['player_atk'] ?? 30,
    'enemy_hp' => $_SESSION['enemy_hp'] ?? 100,  
    'enemy_atk' => $_SESSION['enemy_atk'] ?? 25,
    'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
    'deck_size' => $_SESSION['deck_size'] ?? 20
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Dashboard - NRD Sandbox v<?= htmlspecialchars($build['version']) ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Override battlefield container for dashboard */
        .battlefield-container {
            overflow-y: auto !important;
            height: auto !important;
        }
        
        /* Dashboard specific styles */
        .dashboard-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            grid-template-rows: auto auto auto;
            gap: 20px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
            min-height: calc(100vh - 200px);
        }
        
        .widget {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .widget-header {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .widget-header h3 {
            color: #00d4ff;
            margin: 0;
            font-size: 16px;
        }
        
        .widget-content {
            padding: 20px;
        }
        
        .ai-context-widget {
            grid-column: 1;
            grid-row: 1;
        }
        
        .system-health-widget {
            grid-column: 2;
            grid-row: 1;
        }
        
        .quick-config-widget {
            grid-column: 1;
            grid-row: 2;
        }
        
        .build-info-widget {
            grid-column: 2;
            grid-row: 2;
        }
        
        .card-creator-widget {
            grid-column: 1 / span 2;
            grid-row: 3;
        }
        
        /* Session Notes Form */
        .session-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .note-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .note-label {
            color: #ddd;
            font-weight: bold;
            font-size: 13px;
        }
        
        .note-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 4px;
            padding: 8px 10px;
            color: #fff;
            font-size: 13px;
            resize: vertical;
            min-height: 60px;
        }
        
        .note-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.3);
        }
        
        /* AI Context Output */
        .context-output {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #444;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            color: #ddd;
        }
        
        /* System Health */
        .health-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .health-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #444;
        }
        
        .health-item.good { border-left-color: #28a745; }
        .health-item.warning { border-left-color: #ffc107; }
        .health-item.error { border-left-color: #dc3545; }
        
        .health-label {
            color: #aaa;
            font-size: 11px;
            font-weight: bold;
        }
        
        .health-value {
            color: #fff;
            font-size: 14px;
            font-weight: bold;
        }
        
        /* Quick Config */
        .config-section {
            margin-bottom: 20px;
        }
        
        .config-section h4 {
            color: #00d4ff;
            margin-bottom: 10px;
            font-size: 14px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        
        .config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .config-input-group {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .config-label {
            color: #aaa;
            font-size: 11px;
            font-weight: bold;
        }
        
        .config-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 4px;
            padding: 6px 8px;
            color: #fff;
            font-size: 12px;
            width: 100%;
        }
        
        .config-input:focus {
            outline: none;
            border-color: #00d4ff;
        }
        
        /* Build Info */
        .build-summary {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .build-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .build-item:last-child {
            border-bottom: none;
        }
        
        .build-label {
            color: #aaa;
            font-size: 11px;
            font-weight: bold;
        }
        
        .build-value {
            color: #fff;
            font-size: 12px;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        /* Dashboard Card Creator Specific Styles */
        .dashboard-card-preview {
            width: 140px;
            height: 200px;
            border: 2px solid #444;
            border-radius: 8px;
            padding: 10px;
            position: relative;
            color: white;
            display: flex;
            flex-direction: column;
            background: linear-gradient(145deg, #2d4a87 0%, #1e3a5f 100%);
        }
        
        .dashboard-card-library {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #444;
            border-radius: 6px;
            padding: 10px;
            height: 250px;
            overflow-y: auto;
        }
        
        .dashboard-card-item {
            background: rgba(0,0,0,0.3);
            padding: 8px;
            border-radius: 4px;
            border-left: 3px solid #444;
            margin-bottom: 8px;
        }
        
        .dashboard-card-item:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<div class="battlefield-container">
    <!-- Top Navigation -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="../index.php" class="config-link">🏠 Back to Game</a>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">CONTROL DASHBOARD</h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Success Message -->
    <?php if (isset($successMessage)): ?>
        <div class="success-message">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Dashboard Widgets -->
    <main class="dashboard-container">
        
        <!-- AI Context Generator Widget (Priority) -->
        <div class="widget ai-context-widget">
            <div class="widget-header">
                <h3>🤖 AI Context Generator</h3>
                <div class="widget-actions">
                    <button onclick="copyAIContext()" class="action-btn attack-btn" style="font-size: 11px; padding: 4px 8px;">📋 Copy Context</button>
                </div>
            </div>
            <div class="widget-content">
                <!-- Session Notes Form -->
                <form method="post" class="session-form">
                    <input type="hidden" name="action" value="save_session_notes">
                    
                    <div class="note-group">
                        <label class="note-label">Current Focus: (What you're working on)</label>
                        <textarea name="current_focus" class="note-input" placeholder="e.g., Implementing card effects system"><?= htmlspecialchars($sessionNotes['current_focus']) ?></textarea>
                    </div>
                    
                    <div class="note-group">
                        <label class="note-label">Next Priority: (What's next)</label>
                        <textarea name="next_priority" class="note-input" placeholder="e.g., Card targeting system for spells"><?= htmlspecialchars($sessionNotes['next_priority']) ?></textarea>
                    </div>
                    
                    <div class="note-group">
                        <label class="note-label">Recent Changes: (What you just completed)</label>
                        <textarea name="recent_changes" class="note-input" placeholder="e.g., Fixed card creator validation and JSON loading issues"><?= htmlspecialchars($sessionNotes['recent_changes']) ?></textarea>
                    </div>
                    
                    <div class="note-group">
                        <label class="note-label">Known Issues: (Any problems to mention)</label>
                        <textarea name="known_issues" class="note-input" placeholder="e.g., Cards don't have functional effects yet"><?= htmlspecialchars($sessionNotes['known_issues']) ?></textarea>
                    </div>
                    
                    <button type="submit" class="action-btn save-btn" style="font-size: 12px;">💾 Save Session Notes</button>
                </form>
                
                <!-- Generated Context Output -->
                <div class="context-output" id="aiContextOutput"><?= htmlspecialchars($aiContext) ?></div>
            </div>
        </div>

        <!-- System Health Widget -->
        <div class="widget system-health-widget">
            <div class="widget-header">
                <h3>🚦 System Health</h3>
            </div>
            <div class="widget-content">
                <div class="health-grid">
                    <div class="health-item <?= $diagnostics['auth']['status'] === 'ACTIVE' ? 'good' : 'error' ?>">
                        <div class="health-label">Authentication</div>
                        <div class="health-value"><?= $diagnostics['auth']['status'] ?></div>
                    </div>
                    
                    <div class="health-item <?= $diagnostics['json']['cards_exist'] ? 'good' : 'error' ?>">
                        <div class="health-label">Cards JSON</div>
                        <div class="health-value"><?= $diagnostics['json']['cards_count'] ?> cards</div>
                    </div>
                    
                    <div class="health-item good">
                        <div class="health-label">Player HP</div>
                        <div class="health-value"><?= $diagnostics['game_state']['player_hp'] ?></div>
                    </div>
                    
                    <div class="health-item good">
                        <div class="health-label">Enemy HP</div>
                        <div class="health-value"><?= $diagnostics['game_state']['enemy_hp'] ?></div>
                    </div>
                </div>
                
                <div class="health-item good">
                    <div class="health-label">Hand Status</div>
                    <div class="health-value"><?= $diagnostics['game_state']['hand_count'] ?> cards in hand</div>
                </div>
                
                <div style="margin-top: 15px;">
                    <div class="health-label">Session ID:</div>
                    <div class="health-value" style="font-family: monospace; font-size: 10px;"><?= substr(session_id(), 0, 12) ?>...</div>
                </div>
            </div>
        </div>

        <!-- Quick Config Widget -->
        <div class="widget quick-config-widget">
            <div class="widget-header">
                <h3>⚙️ Quick Configuration</h3>
            </div>
            <div class="widget-content">
                <div class="config-section">
                    <h4>🤖 Mech Stats</h4>
                    <form method="post" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="action" value="quick_save_mechs">
                        <div class="config-grid">
                            <div class="config-input-group">
                                <label class="config-label">Player HP</label>
                                <input type="number" name="player_hp" value="<?= $currentConfig['player_hp'] ?>" class="config-input" min="1" max="999">
                            </div>
                            <div class="config-input-group">
                                <label class="config-label">Enemy HP</label>
                                <input type="number" name="enemy_hp" value="<?= $currentConfig['enemy_hp'] ?>" class="config-input" min="1" max="999">
                            </div>
                            <div class="config-input-group">
                                <label class="config-label">Player ATK</label>
                                <input type="number" name="player_atk" value="<?= $currentConfig['player_atk'] ?>" class="config-input" min="1" max="999">
                            </div>
                            <div class="config-input-group">
                                <label class="config-label">Enemy ATK</label>
                                <input type="number" name="enemy_atk" value="<?= $currentConfig['enemy_atk'] ?>" class="config-input" min="1" max="999">
                            </div>
                        </div>
                        <button type="submit" class="action-btn save-btn" style="font-size: 11px;">💾 Save Mechs</button>
                    </form>
                </div>
                
                <div class="config-section">
                    <h4>🎲 Game Rules</h4>
                    <form method="post" style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="hidden" name="action" value="quick_save_rules">
                        <div class="config-grid">
                            <div class="config-input-group">
                                <label class="config-label">Hand Size</label>
                                <input type="number" name="starting_hand_size" value="<?= $currentConfig['starting_hand_size'] ?>" class="config-input" min="1" max="15">
                            </div>
                            <div class="config-input-group">
                                <label class="config-label">Max Hand</label>
                                <input type="number" name="max_hand_size" value="<?= $currentConfig['max_hand_size'] ?>" class="config-input" min="1" max="20">
                            </div>
                            <div class="config-input-group">
                                <label class="config-label">Deck Size</label>
                                <input type="number" name="deck_size" value="<?= $currentConfig['deck_size'] ?>" class="config-input" min="10" max="50">
                            </div>
                        </div>
                        <button type="submit" class="action-btn save-btn" style="font-size: 11px;">💾 Save Rules</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Build Info Widget -->
        <div class="widget build-info-widget">
            <div class="widget-header">
                <h3>📊 Build Information</h3>
            </div>
            <div class="widget-content">
                <div class="build-summary">
                    <div class="build-item">
                        <span class="build-label">Version:</span>
                        <span class="build-value"><?= htmlspecialchars($build['version']) ?></span>
                    </div>
                    <div class="build-item">
                        <span class="build-label">Build Date:</span>
                        <span class="build-value"><?= htmlspecialchars($build['date']) ?></span>
                    </div>
                    <div class="build-item">
                        <span class="build-label">Build Name:</span>
                        <span class="build-value"><?= htmlspecialchars($build['build_name']) ?></span>
                    </div>
                    <div class="build-item">
                        <span class="build-label">PHP Required:</span>
                        <span class="build-value"><?= htmlspecialchars($build['php_required']) ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <div class="build-label">Recent Changes:</div>
                    <ul style="margin: 8px 0; padding-left: 15px; color: #ddd; font-size: 11px;">
                        <?php foreach (array_slice($build['changelog'], 0, 3) as $change): ?>
                            <li style="margin-bottom: 3px;"><?= htmlspecialchars($change) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div style="margin-top: 15px;">
                    <a href="../build-info.php" class="action-btn info-btn" style="font-size: 11px; width: 100%; text-align: center;">📋 Full Build Details</a>
                </div>
            </div>
        </div>

        <!-- Card Creator Widget (COMPLETELY FIXED) -->
        <div class="widget card-creator-widget">
            <div class="widget-header">
                <h3>🃏 Card Creator</h3>
                <div class="widget-actions">
                    <span id="cardCount" style="font-size: 11px; color: #aaa;">Loading...</span>
                </div>
            </div>
            <div class="widget-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 20px;">
                    <!-- Card Form -->
                    <div>
                        <h4 style="color: #00d4ff; margin-bottom: 10px; font-size: 14px;">Create New Card</h4>
                        <form id="dashboardCardForm" style="display: flex; flex-direction: column; gap: 10px;">
                            <input type="text" id="dashboardCardName" placeholder="Card Name" class="config-input" style="width: 100%;" oninput="updateDashboardPreview()">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <input type="number" id="dashboardCardCost" placeholder="Cost" value="3" min="0" max="10" class="config-input" oninput="updateDashboardPreview()">
                                <select id="dashboardCardType" class="config-input" onchange="updateDashboardPreview()">
                                    <option value="spell">Spell</option>
                                    <option value="weapon">Weapon</option>
                                    <option value="armor">Armor</option>
                                    <option value="creature">Creature</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                <input type="number" id="dashboardCardDamage" placeholder="Damage" value="5" min="0" max="50" class="config-input" oninput="updateDashboardPreview()">
                                <select id="dashboardCardRarity" class="config-input" onchange="updateDashboardPreview()">
                                    <option value="common">Common</option>
                                    <option value="uncommon">Uncommon</option>
                                    <option value="rare">Rare</option>
                                    <option value="legendary">Legendary</option>
                                </select>
                            </div>
                            
                            <textarea id="dashboardCardDescription" placeholder="Card description..." class="note-input" style="min-height: 60px; resize: vertical;" oninput="updateDashboardPreview()"></textarea>
                            
                            <button type="button" onclick="saveDashboardCard()" class="action-btn save-btn" style="font-size: 11px;">💾 Save Card</button>
                        </form>
                    </div>
                    
                    <!-- Card Preview -->
                    <div>
                        <h4 style="color: #00d4ff; margin-bottom: 10px; font-size: 14px;">Live Preview</h4>
                        <div id="dashboardCardPreview" class="dashboard-card-preview">
                            <div style="position: absolute; top: -6px; left: -6px; width: 20px; height: 20px; background: #ffc107; color: #000; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;" id="dashboardPreviewCost">3</div>
                            <div style="font-size: 12px; font-weight: bold; text-align: center; margin-bottom: 4px;" id="dashboardPreviewName">Lightning Bolt</div>
                            <div style="font-size: 8px; text-align: center; color: rgba(255, 255, 255, 0.7); margin-bottom: 8px;" id="dashboardPreviewType">SPELL</div>
                            <div style="flex: 1; background: rgba(0, 0, 0, 0.3); border: 1px dashed #666; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; min-height: 60px;">
                                <span style="color: #888; font-style: italic; font-size: 10px;">[Art]</span>
                            </div>
                            <div style="text-align: center; font-size: 10px; font-weight: bold; color: #dc3545; margin-bottom: 6px;" id="dashboardPreviewDamage">💥 5</div>
                            <div style="font-size: 8px; text-align: center; line-height: 1.2; color: #ddd; margin-bottom: 6px; min-height: 30px;" id="dashboardPreviewDescription">Deal damage to target enemy...</div>
                            <div style="position: absolute; bottom: -6px; right: -6px; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; background: #6c757d; color: white;" id="dashboardPreviewRarity">Common</div>
                        </div>
                    </div>
                    
                    <!-- Card Library -->
                    <div>
                        <h4 style="color: #00d4ff; margin-bottom: 10px; font-size: 14px;">Card Library</h4>
                        <div id="dashboardCardLibrary" class="dashboard-card-library">
                            <div style="text-align: center; color: #888; font-style: italic; padding: 20px;">Loading cards...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            Control Dashboard | Build <?= htmlspecialchars($build['version']) ?> | 
            Latest & Greatest - All systems stable
        </div>
    </footer>
</div>

<script>
// ===================================================================
// AI CONTEXT COPY FUNCTION
// ===================================================================
function copyAIContext() {
    const contextOutput = document.getElementById('aiContextOutput');
    const textArea = document.createElement('textarea');
    textArea.value = contextOutput.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        
        // Visual feedback
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✅ Copied!';
        button.style.background = '#28a745';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 2000);
        
    } catch (err) {
        alert('Copy failed. Please select the text manually and copy with Ctrl+C');
    } finally {
        document.body.removeChild(textArea);
    }
}

// ===================================================================
// DASHBOARD CARD CREATOR FUNCTIONS (FIXED)
// ===================================================================
function updateDashboardPreview() {
    const name = document.getElementById('dashboardCardName').value || 'New Card';
    const cost = document.getElementById('dashboardCardCost').value || '0';
    const type = document.getElementById('dashboardCardType').value || 'spell';
    const damage = document.getElementById('dashboardCardDamage').value || '0';
    const description = document.getElementById('dashboardCardDescription').value || 'Card description...';
    const rarity = document.getElementById('dashboardCardRarity').value || 'common';
    
    // Update preview elements
    document.getElementById('dashboardPreviewCost').textContent = cost;
    document.getElementById('dashboardPreviewName').textContent = name;
    document.getElementById('dashboardPreviewType').textContent = type.toUpperCase();
    document.getElementById('dashboardPreviewDamage').textContent = damage > 0 ? `💥 ${damage}` : '';
    document.getElementById('dashboardPreviewDescription').textContent = description;
    document.getElementById('dashboardPreviewRarity').textContent = rarity.charAt(0).toUpperCase() + rarity.slice(1);
    
    // Update card styling based on type
    const preview = document.getElementById('dashboardCardPreview');
    const typeColors = {
        'spell': 'linear-gradient(145deg, #2d4a87 0%, #1e3a5f 100%)',
        'weapon': 'linear-gradient(145deg, #8b2635 0%, #5f1e2a 100%)',
        'armor': 'linear-gradient(145deg, #2d6b35 0%, #1e4a25 100%)',
        'creature': 'linear-gradient(145deg, #7d4a87 0%, #5f1e3a 100%)',
        'support': 'linear-gradient(145deg, #87652d 0%, #5f4a1e 100%)'
    };
    preview.style.background = typeColors[type] || typeColors['spell'];
    
    // Update rarity styling
    const rarityColors = {
        'common': '#6c757d',
        'uncommon': '#28a745',
        'rare': '#007bff',
        'legendary': 'linear-gradient(45deg, #ffc107, #ff6b35)'
    };
    const rarityElement = document.getElementById('dashboardPreviewRarity');
    rarityElement.style.background = rarityColors[rarity] || rarityColors['common'];
}

function saveDashboardCard() {
    const cardData = {
        name: document.getElementById('dashboardCardName').value,
        cost: document.getElementById('dashboardCardCost').value,
        type: document.getElementById('dashboardCardType').value,
        damage: document.getElementById('dashboardCardDamage').value,
        description: document.getElementById('dashboardCardDescription').value,
        rarity: document.getElementById('dashboardCardRarity').value
    };
    
    // Validate required fields
    if (!cardData.name.trim()) {
        alert('Please enter a card name');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'create_card');
    formData.append('name', cardData.name);
    formData.append('cost', cardData.cost);
    formData.append('type', cardData.type);
    formData.append('damage', cardData.damage);
    formData.append('description', cardData.description);
    formData.append('rarity', cardData.rarity);
    
    // Send to card-manager.php
    fetch('../card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Card saved successfully!\n\nCard ID: ' + data.data.id + '\nName: ' + data.data.name);
            // Reset form
            document.getElementById('dashboardCardForm').reset();
            document.getElementById('dashboardCardCost').value = '3';
            document.getElementById('dashboardCardDamage').value = '5';
            updateDashboardPreview();
            // Reload card library
            loadDashboardCardLibrary();
        } else {
            alert('Error saving card: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error while saving card. Please try again.');
    });
}

function loadDashboardCardLibrary() {
    const formData = new FormData();
    formData.append('action', 'get_all_cards');
    
    fetch('../card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayDashboardCardLibrary(data.data);
            updateDashboardCardCount(data.data.length);
        } else {
            document.getElementById('dashboardCardLibrary').innerHTML = 
                '<div style="color: red; padding: 10px;">Error: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Error loading cards:', error);
        document.getElementById('dashboardCardLibrary').innerHTML = 
            '<div style="color: red; padding: 10px;">Network error loading cards</div>';
    });
}

function displayDashboardCardLibrary(cards) {
    const container = document.getElementById('dashboardCardLibrary');
    
    if (!cards || cards.length === 0) {
        container.innerHTML = '<div style="color: #666; padding: 20px;">No cards found</div>';
        return;
    }
    
    // Generate HTML for dashboard-style card display
    let html = '';
    cards.forEach((card, index) => {
        html += `
            <div class="dashboard-card-item">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-weight: bold; color: #fff;">${card.name || 'Unnamed Card'}</span>
                    <span style="background: #ffc107; color: #000; padding: 2px 6px; border-radius: 50%; font-size: 10px; font-weight: bold;">${card.cost || 0}</span>
                </div>
                <div style="font-size: 10px; color: #aaa; margin-bottom: 2px;">${(card.type || 'Unknown').toUpperCase()}</div>
                <div style="font-size: 10px; color: #dc3545; font-weight: bold; margin-bottom: 4px;">${card.damage > 0 ? '💥 ' + card.damage : ''}</div>
                <div style="font-size: 9px; color: #ddd; line-height: 1.2; margin-bottom: 4px;">${card.description || 'No description'}</div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 8px; padding: 1px 4px; border-radius: 6px; background: #6c757d; color: white;">${card.rarity || 'common'}</span>
                    <div>
                        <button onclick="editDashboardCard('${card.id}')" style="background: none; border: 1px solid #666; color: #ddd; padding: 1px 4px; border-radius: 2px; font-size: 8px; cursor: pointer;">Edit</button>
                        <button onclick="deleteDashboardCard('${card.id}')" style="background: none; border: 1px solid #666; color: #ddd; padding: 1px 4px; border-radius: 2px; font-size: 8px; cursor: pointer; margin-left: 2px;">Delete</button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateDashboardCardCount(count) {
    document.getElementById('cardCount').textContent = count + (count === 1 ? ' card' : ' cards');
}

function editDashboardCard(cardId) {
    alert('Edit card feature coming soon! Card ID: ' + cardId);
}

function deleteDashboardCard(cardId) {
    if (confirm('Are you sure you want to delete this card from the library?')) {
        const formData = new FormData();
        formData.append('action', 'delete_card');
        formData.append('card_id', cardId);
        
        fetch('../card-manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadDashboardCardLibrary(); // Refresh the library
                alert('Card deleted successfully!');
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

// ===================================================================
// INITIALIZATION
// ===================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard card preview
    updateDashboardPreview();
    
    // Load dashboard card library
    loadDashboardCardLibrary();
});
</script>

</body>
</html>