<?php
// ===================================================================
// NRD SANDBOX - STREAMLINED AI HANDOFF DASHBOARD v2.0
// ===================================================================
require '../auth.php';

// Get current build information
$build = require '../build-data.php';

// ===================================================================
// STREAMLINED SESSION NOTES MANAGEMENT
// ===================================================================
$sessionNotesFile = '../data/session-notes.json';
$sessionNotes = [
    'current_focus' => '',
    'next_priority' => '',
    'recent_changes' => '',
    'known_issues' => '',
    'quick_context' => '',
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
            'quick_context' => $_POST['quick_context'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        file_put_contents($sessionNotesFile, json_encode($sessionNotes, JSON_PRETTY_PRINT));
        $successMessage = 'Session context saved successfully!';
    }
}

// ===================================================================
// GITHUB INTEGRATION
// ===================================================================
function getGitHubStatus() {
    $githubUrl = 'https://api.github.com/repos/stratos75/NRDSandbox';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => 'User-Agent: NRD-Sandbox-Dashboard'
        ]
    ]);
    
    $github = [
        'status' => 'UNKNOWN',
        'last_commit' => 'Unknown',
        'branch' => 'main',
        'commits_ahead' => 0,
        'url' => 'https://github.com/stratos75/NRDSandbox'
    ];
    
    try {
        $response = @file_get_contents($githubUrl, false, $context);
        if ($response) {
            $repoData = json_decode($response, true);
            if ($repoData) {
                $github['status'] = 'CONNECTED';
                $github['last_commit'] = substr($repoData['updated_at'], 0, 10);
                $github['branch'] = $repoData['default_branch'];
            }
        }
    } catch (Exception $e) {
        $github['status'] = 'ERROR';
    }
    
    return $github;
}

// ===================================================================
// SYSTEM DIAGNOSTICS (ENHANCED WITH GITHUB)
// ===================================================================
function getSystemStatus() {
    $cardsFile = '../data/cards.json';
    $playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'MAX_HP' => 100, 'image' => null];
    $enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'MAX_HP' => 100, 'image' => null];
    $playerHand = $_SESSION['player_hand'] ?? [];
    
    // Check image system status
    $cardImagesDir = '../data/images';
    $mechImagesDir = '../data/images/mechs';
    $imageSystemWorking = is_dir($cardImagesDir) && is_writable($cardImagesDir);
    
    // Count images
    $cardImagesCount = 0;
    $mechImagesCount = 0;
    if ($imageSystemWorking) {
        $cardImagesCount = count(glob($cardImagesDir . '/card_*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE));
        if (is_dir($mechImagesDir)) {
            $mechImagesCount = count(glob($mechImagesDir . '/*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE));
        }
    }
    
    $status = [
        'auth_user' => $_SESSION['username'] ?? 'Unknown',
        'cards_count' => 0,
        'cards_working' => false,
        'player_hp' => $playerMech['HP'],
        'enemy_hp' => $enemyMech['HP'],
        'hand_count' => count($playerHand),
        'system_health' => 'GOOD',
        'images_working' => $imageSystemWorking,
        'card_images_count' => $cardImagesCount,
        'mech_images_count' => $mechImagesCount,
        'player_has_image' => !empty($playerMech['image']) && file_exists('../' . $playerMech['image']),
        'enemy_has_image' => !empty($enemyMech['image']) && file_exists('../' . $enemyMech['image'])
    ];
    
    if (file_exists($cardsFile)) {
        $cardData = json_decode(file_get_contents($cardsFile), true);
        if ($cardData && isset($cardData['cards'])) {
            $status['cards_count'] = count($cardData['cards']);
            $status['cards_working'] = true;
            
            // Count cards with images
            $cardsWithImages = 0;
            foreach ($cardData['cards'] as $card) {
                if (!empty($card['image']) && file_exists('../' . $card['image'])) {
                    $cardsWithImages++;
                }
            }
            $status['cards_with_images'] = $cardsWithImages;
        }
    }
    
    if (!$status['cards_working'] || !$status['images_working']) {
        $status['system_health'] = 'NEEDS_ATTENTION';
    }
    
    return $status;
}

// ===================================================================
// STREAMLINED AI CONTEXT GENERATION
// ===================================================================
function generateStreamlinedContext($build, $sessionNotes, $status) {
    $timestamp = date('Y-m-d H:i:s');
    
    $context = "# 🎮 NRD Sandbox - AI Handoff Context
**Generated:** {$timestamp} | **Version:** {$build['version']}
**User:** {$status['auth_user']} | **System:** {$status['system_health']}

## 🎯 CURRENT DEVELOPMENT STATUS
**Now Working On:** Card effects system implementation (make cards actually DO things)

**Next Priority:** Targeting system for spells and abilities  

**Recent Completed:** 
- ✅ Fixed drag-and-drop for weapons/armor on Mac Safari
- ✅ Enhanced special attack visual positioning (quarter above weapon)
- ✅ Implemented comprehensive card persistence with atomic writes & file locking
- ✅ Fixed card creator artwork saving (window no longer disappears)  
- ✅ Added background images behind card text with CSS overlay system
- ✅ Fixed mech image saving for player/enemy mechs (session preservation)

**Known Issues:** None - all major systems stable and working

## 🚀 QUICK CONTEXT FOR NEW AI CHATS
**Major Systems Working:** All core functionality stable - drag-and-drop equipment, card creator with images, mech customization, persistent storage with robust file locking. Focus is now on making cards have actual game effects and targeting systems.

**Database Discussion:** Staying with JSON file storage for now (working perfectly), will migrate to MySQL only when reaching production scale with multiple users.

## 📊 LIVE SYSTEM STATE
- **Cards System:** {$status['cards_count']} cards loaded, " . ($status['cards_working'] ? 'WORKING' : 'BROKEN') . "
- **Image System:** " . ($status['images_working'] ? 'WORKING' : 'BROKEN') . " - {$status['card_images_count']} card images, {$status['mech_images_count']} mech images
- **Game State:** Player {$status['player_hp']}HP, Enemy {$status['enemy_hp']}HP, {$status['hand_count']} cards in hand
- **Mechs:** Player " . ($status['player_has_image'] ? 'has image' : 'no image') . ", Enemy " . ($status['enemy_has_image'] ? 'has image' : 'no image') . "
- **Authentication:** {$status['auth_user']} logged in
- **Build:** {$build['version']} - {$build['build_name']}

## 🛠️ PROJECT OVERVIEW
**NRD Sandbox** = PHP-based tactical card battle game development tool

**What Works Now:**
✅ Authentication (admin/password123)
✅ AJAX combat system with real-time HP updates  
✅ Equipment system: Drag-and-drop weapons/armor to equip (Mac Safari compatible)
✅ Special attack layering: Visual cards show quarter above equipped weapons
✅ Card creator: Create/save/delete cards with live preview + robust image upload
✅ Mech configurator: Full mech customization with persistent image upload
✅ Image system: Background images behind card text + mech avatars 
✅ Storage system: Atomic writes, file locking, automatic backups, image cleanup
✅ Debug panel: Real-time diagnostics (🐛 button)
✅ Visual effects: Health-based image filters, hover animations, elemental borders

**Image System Status:**
- Card Images: {$status['card_images_count']} uploaded, " . ($status['cards_with_images'] ?? 0) . "/{$status['cards_count']} cards have images
- Mech Images: Player " . ($status['player_has_image'] ? '✅' : '❌') . " Enemy " . ($status['enemy_has_image'] ? '✅' : '❌') . "
- Storage: " . ($status['images_working'] ? 'WORKING' : 'BROKEN') . "

**Main Interface:** index.php (battlefield with fan card layout)
**Config Dashboard:** config/index.php (this page)
**Mech Config:** config/mechs.php (mech image upload)

## 📁 KEY FILES
- `index.php` - Main battlefield interface with image display
- `style.css` - All styling (single file) including image effects
- `combat-manager.php` - AJAX combat endpoints
- `card-manager.php` - Card CRUD + image upload operations
- `config/mechs.php` - Mech configuration + image upload
- `data/cards.json` - Card storage ({$status['cards_count']} cards)
- `data/images/` - Card image storage ({$status['card_images_count']} files)
- `data/images/mechs/` - Mech image storage ({$status['mech_images_count']} files)

## 🔥 IMMEDIATE NEXT STEPS
1. **Card Effects System** - Make spells and abilities actually functional in combat
2. **Targeting System** - Allow players to select targets for spell effects  
3. **Combat Integration** - Use ATK/DEF stats from equipped cards in battle calculations
4. **Deck Building** - Interface to assign specific cards to player/enemy decks

---
*Ready for development collaboration! All core systems stable.*";

    return $context;
}

$status = getSystemStatus();
$streamlinedContext = generateStreamlinedContext($build, $sessionNotes, $status);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Handoff Dashboard - NRD Sandbox v<?= htmlspecialchars($build['version']) ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Fix battlefield container for proper scrolling */
        .battlefield-container {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .handoff-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            min-height: calc(100vh - 200px);
        }
        
        .main-panel {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .panel-header {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px 20px;
            border-bottom: 1px solid #333;
        }
        
        .panel-header h2 {
            color: #00d4ff;
            margin: 0;
            font-size: 18px;
        }
        
        .panel-content {
            padding: 20px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .status-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #28a745;
            text-align: center;
        }
        
        .status-item.warning { border-left-color: #ffc107; }
        .status-item.error { border-left-color: #dc3545; }
        
        .status-label {
            color: #aaa;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-value {
            color: #fff;
            font-size: 14px;
            font-weight: bold;
        }
        
        .form-section {
            margin-bottom: 20px;
        }
        
        .form-section h3 {
            color: #00d4ff;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            color: #ddd;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        
        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 4px;
            padding: 8px 10px;
            color: #fff;
            font-size: 12px;
            resize: vertical;
            min-height: 60px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.3);
        }
        
        .context-output {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #444;
            border-radius: 6px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.4;
            max-height: 400px;
            min-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            color: #ddd;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(145deg, #00d4ff 0%, #0099cc 100%);
            color: #000;
            flex: 1;
        }
        
        .btn-success {
            background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
            color: white;
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
            font-size: 12px;
        }
        
        .quick-actions {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 15px;
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
        }
        
        @media (max-width: 768px) {
            .handoff-container {
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
            <h1 class="game-title">🤖 AI HANDOFF DASHBOARD</h1>
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

    <!-- Main Content -->
    <main class="handoff-container">
        
        <!-- Main Panel: Session Notes & Context -->
        <div class="main-panel">
            <div class="panel-header">
                <h2>🎯 AI Development Context</h2>
            </div>
            <div class="panel-content">
                
                <!-- Live System Status -->
                <div class="status-grid">
                    <div class="status-item <?= $status['system_health'] === 'GOOD' ? '' : 'warning' ?>">
                        <div class="status-label">System Health</div>
                        <div class="status-value"><?= $status['system_health'] ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Cards System</div>
                        <div class="status-value"><?= $status['cards_count'] ?> cards</div>
                    </div>
                    <div class="status-item <?= $status['images_working'] ? '' : 'warning' ?>">
                        <div class="status-label">Image System</div>
                        <div class="status-value"><?= $status['images_working'] ? 'WORKING' : 'BROKEN' ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Card Images</div>
                        <div class="status-value"><?= $status['cards_with_images'] ?? 0 ?>/<?= $status['cards_count'] ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Mech Images</div>
                        <div class="status-value"><?= ($status['player_has_image'] ? 'P✅' : 'P❌') ?> <?= ($status['enemy_has_image'] ? 'E✅' : 'E❌') ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Game State</div>
                        <div class="status-value">P:<?= $status['player_hp'] ?> E:<?= $status['enemy_hp'] ?></div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">Hand Cards</div>
                        <div class="status-value"><?= $status['hand_count'] ?></div>
                    </div>
                </div>
                
                <!-- Streamlined Session Form -->
                <form method="post">
                    <input type="hidden" name="action" value="save_session_notes">
                    
                    <div class="form-section">
                        <h3>🎯 Current Development Focus</h3>
                        
                        <div class="form-group">
                            <label class="form-label">What are you working on RIGHT NOW?</label>
                            <textarea name="current_focus" class="form-input" placeholder="e.g., Implementing Phase 3 drop zones for draggable cards system"><?= htmlspecialchars($sessionNotes['current_focus']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">What's the IMMEDIATE next step?</label>
                            <textarea name="next_priority" class="form-input" placeholder="e.g., Add drop zone detection and card effect resolution"><?= htmlspecialchars($sessionNotes['next_priority']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>📝 Recent Work & Issues</h3>
                        
                        <div class="form-group">
                            <label class="form-label">What did you just complete?</label>
                            <textarea name="recent_changes" class="form-input" placeholder="e.g., Phase 2 drag visuals with smooth mouse tracking and lift animations"><?= htmlspecialchars($sessionNotes['recent_changes']) ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Any current problems or bugs?</label>
                            <textarea name="known_issues" class="form-input" placeholder="e.g., Delete button disconnects when dragging cards"><?= htmlspecialchars($sessionNotes['known_issues']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>🚀 Quick Context for New AI Chats</h3>
                        
                        <div class="form-group">
                            <label class="form-label">Essential context for AI to jump in immediately:</label>
                            <textarea name="quick_context" class="form-input" placeholder="e.g., Working on draggable card system. Phase 1 (tooltips) ✅ Phase 2 (drag visuals) ✅ Now need Phase 3 (drop zones). Foundation solid, just need battlefield interaction logic."><?= htmlspecialchars($sessionNotes['quick_context']) ?></textarea>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-success">💾 Save Context</button>
                    </div>
                </form>
                
                <!-- Generated Context Output -->
                <div class="form-section">
                    <h3>📋 Generated AI Handoff Context</h3>
                    <div class="context-output" id="contextOutput"><?= htmlspecialchars($streamlinedContext) ?></div>
                    <div class="action-buttons">
                        <button onclick="copyContext()" class="btn btn-primary">📋 Copy Context for New AI Chat</button>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Side Panel: Quick Actions -->
        <div class="side-panel">
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>⚡ Quick Actions</h3>
                <a href="../index.php" class="btn btn-success">🏠 Return to Game</a>
                <a href="mechs.php" class="btn btn-primary">🤖 Mech Config</a>
                <button onclick="window.location.reload()" class="btn btn-primary">🔄 Refresh Status</button>
                <a href="../build-info.php" class="btn" style="background: linear-gradient(145deg, #6c757d 0%, #495057 100%); color: white;">📊 Build Info</a>
            </div>
            
            <!-- System Health -->
            <div class="quick-actions">
                <h3>🔍 System Health</h3>
                <div style="font-size: 11px; color: #ddd; line-height: 1.4;">
                    <strong>Authentication:</strong> <?= $status['auth_user'] ?><br>
                    <strong>Cards System:</strong> <?= $status['cards_working'] ? '✅ Working' : '❌ Broken' ?><br>
                    <strong>Image System:</strong> <?= $status['images_working'] ? '✅ Working' : '❌ Broken' ?><br>
                    <strong>Cards:</strong> <?= $status['cards_count'] ?> total, <?= $status['cards_with_images'] ?? 0 ?> with images<br>
                    <strong>Mechs:</strong> P:<?= $status['player_has_image'] ? '✅' : '❌' ?> E:<?= $status['enemy_has_image'] ? '✅' : '❌' ?><br>
                    <strong>Build:</strong> <?= $build['version'] ?><br>
                    <strong>Status:</strong> <?= $status['system_health'] ?>
                </div>
            </div>
            
            <!-- Feature Status -->
            <div class="quick-actions">
                <h3>🎮 Feature Status</h3>
                <div style="font-size: 11px; color: #ddd; line-height: 1.4;">
                    ✅ <strong>Card System:</strong> Full CRUD + Images<br>
                    ✅ <strong>Mech System:</strong> Config + Images<br>
                    ✅ <strong>Image Upload:</strong> Cards + Mechs<br>
                    ✅ <strong>Visual Effects:</strong> Health filters<br>
                    ✅ <strong>Equipment:</strong> Weapon/Armor equip<br>
                    ✅ <strong>Combat:</strong> AJAX battles<br>
                    ⏳ <strong>Card Effects:</strong> Spell mechanics<br>
                    ⏳ <strong>Deck Building:</strong> Custom decks<br>
                </div>
            </div>
            
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            AI Handoff Dashboard | Build <?= htmlspecialchars($build['version']) ?> | 
            Image Upload System Complete | Cards + Mechs Fully Operational
        </div>
    </footer>
</div>

<script>
function copyContext() {
    const contextOutput = document.getElementById('contextOutput');
    const textArea = document.createElement('textarea');
    textArea.value = contextOutput.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    
    try {
        document.execCommand('copy');
        
        const button = event.target;
        const originalText = button.textContent;
        
        button.textContent = '✅ Context Copied!';
        button.style.background = 'linear-gradient(145deg, #28a745 0%, #1e7e34 100%)';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
        }, 3000);
        
    } catch (err) {
        alert('Copy failed. Please select the text manually and copy with Ctrl+C');
    } finally {
        document.body.removeChild(textArea);
    }
}

// Auto-save form data to localStorage to prevent loss
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const textareas = form.querySelectorAll('textarea');
    
    // Load saved drafts
    textareas.forEach(textarea => {
        const key = 'nrd_draft_' + textarea.name;
        const saved = localStorage.getItem(key);
        if (saved && !textarea.value) {
            textarea.value = saved;
        }
        
        // Save on input
        textarea.addEventListener('input', () => {
            localStorage.setItem(key, textarea.value);
        });
    });
    
    // Clear drafts on successful save
    form.addEventListener('submit', () => {
        textareas.forEach(textarea => {
            localStorage.removeItem('nrd_draft_' + textarea.name);
        });
    });
});
</script>

</body>
</html>