<?php
// ===================================================================
// NRD SANDBOX - ENHANCED AI CONTEXT SYSTEM FOR SEAMLESS CHAT HANDOFFS
// ===================================================================
require '../auth.php';

// Get current build information
$build = require '../build-data.php';

// ===================================================================
// REAL-TIME SYSTEM DIAGNOSTICS (NEW!)
// ===================================================================

function runSystemDiagnostics() {
    $diagnostics = [];
    
    // Test Authentication System
    $diagnostics['auth'] = [
        'status' => (isset($_SESSION['username']) ? 'ACTIVE' : 'INACTIVE'),
        'user' => $_SESSION['username'] ?? 'None',
        'session_id' => substr(session_id(), 0, 8) . '...'
    ];
    
    // Browser/Environment Detection
    $diagnostics['environment'] = [
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'current_url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ];
    
    // Test JSON File System
    $cardsFile = '../data/cards.json';
    $diagnostics['json'] = [
        'cards_file_exists' => file_exists($cardsFile),
        'cards_file_readable' => is_readable($cardsFile),
        'cards_file_writable' => is_writable($cardsFile),
        'data_dir_writable' => is_writable('../data/'),
        'cards_count' => 0,
        'json_valid' => false
    ];
    
    if (file_exists($cardsFile)) {
        $jsonContent = file_get_contents($cardsFile);
        $cardData = json_decode($jsonContent, true);
        $diagnostics['json']['json_valid'] = ($cardData !== null);
        $diagnostics['json']['cards_count'] = count($cardData['cards'] ?? []);
        $diagnostics['json']['file_size'] = filesize($cardsFile) . ' bytes';
    }
    
    // Test Core Files
    $coreFiles = [
        'index.php' => '../index.php',
        'combat-manager.php' => '../combat-manager.php', 
        'card-manager.php' => '../card-manager.php',
        'style.css' => '../style.css',
        'build-data.php' => '../build-data.php'
    ];
    
    $diagnostics['files'] = [];
    foreach ($coreFiles as $name => $path) {
        $diagnostics['files'][$name] = [
            'exists' => file_exists($path),
            'readable' => file_exists($path) ? is_readable($path) : false,
            'size' => file_exists($path) ? filesize($path) : 0
        ];
    }
    
    return $diagnostics;
}

function checkGameState() {
    $gameState = [];
    
    // Current Mech Status
    $playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100];
    $enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100];
    
    $gameState['mechs'] = [
        'player' => $playerMech,
        'enemy' => $enemyMech
    ];
    
    // Equipment Status
    $playerEquipment = $_SESSION['playerEquipment'] ?? ['weapon' => null, 'armor' => null];
    $enemyEquipment = $_SESSION['enemyEquipment'] ?? ['weapon' => null, 'armor' => null];
    
    $gameState['equipment'] = [
        'player_weapon' => $playerEquipment['weapon'] !== null,
        'player_armor' => $playerEquipment['armor'] !== null,
        'enemy_weapon' => $enemyEquipment['weapon'] !== null,
        'enemy_armor' => $enemyEquipment['armor'] !== null
    ];
    
    // Hand Status
    $playerHand = $_SESSION['player_hand'] ?? [];
    $gameState['hand'] = [
        'cards_in_hand' => count($playerHand),
        'hand_empty' => empty($playerHand)
    ];
    
    // Game Rules
    $gameState['rules'] = [
        'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
        'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
        'deck_size' => $_SESSION['deck_size'] ?? 20,
        'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
        'starting_player' => $_SESSION['starting_player'] ?? 'player'
    ];
    
    // Recent Log Entries
    $gameLog = $_SESSION['log'] ?? [];
    $gameState['recent_actions'] = array_slice($gameLog, -5); // Last 5 actions
    
    return $gameState;
}

function checkForErrors() {
    $errors = [];
    
    // Check for PHP errors (basic checks)
    if (function_exists('error_get_last')) {
        $lastError = error_get_last();
        if ($lastError && time() - filemtime($lastError['file']) < 3600) { // Last hour
            $errors['php_errors'] = [
                'message' => $lastError['message'],
                'file' => basename($lastError['file']),
                'line' => $lastError['line']
            ];
        }
    }
    
    // Check file permissions
    $permissionIssues = [];
    if (!is_writable('../data/')) {
        $permissionIssues[] = 'data/ directory not writable';
    }
    if (file_exists('../data/cards.json') && !is_writable('../data/cards.json')) {
        $permissionIssues[] = 'cards.json not writable';
    }
    
    if (!empty($permissionIssues)) {
        $errors['permissions'] = $permissionIssues;
    }
    
    // Check for missing critical files
    $missingFiles = [];
    $criticalFiles = ['../index.php', '../combat-manager.php', '../card-manager.php', '../style.css'];
    foreach ($criticalFiles as $file) {
        if (!file_exists($file)) {
            $missingFiles[] = basename($file);
        }
    }
    
    if (!empty($missingFiles)) {
        $errors['missing_files'] = $missingFiles;
    }
    
    return $errors;
}

function checkGitStatus() {
    $gitInfo = [
        'git_available' => false,
        'clean_working_directory' => false,
        'current_branch' => 'unknown',
        'uncommitted_files' => 0,
        'last_commit' => 'unknown'
    ];
    
    // Check if we're in a git repository
    if (file_exists('../.git')) {
        $gitInfo['git_available'] = true;
        
        // Try to get git status (if exec is available and safe)
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $output = [];
            $return_var = 0;
            
            // Get current branch
            @exec('cd .. && git branch --show-current 2>/dev/null', $branchOutput, $branchReturn);
            if ($branchReturn === 0 && !empty($branchOutput)) {
                $gitInfo['current_branch'] = trim($branchOutput[0]);
            }
            
            // Get status (check for uncommitted changes)
            @exec('cd .. && git status --porcelain 2>/dev/null', $statusOutput, $statusReturn);
            if ($statusReturn === 0) {
                $gitInfo['clean_working_directory'] = empty($statusOutput);
                $gitInfo['uncommitted_files'] = count($statusOutput);
            }
            
            // Get last commit
            @exec('cd .. && git log -1 --pretty=format:"%h %s" 2>/dev/null', $logOutput, $logReturn);
            if ($logReturn === 0 && !empty($logOutput)) {
                $gitInfo['last_commit'] = trim($logOutput[0]);
            }
        }
    }
    
    return $gitInfo;
}

function getCurrentSessionContext() {
    $context = [
        'last_activity' => $_SESSION['last_activity'] ?? 'Unknown',
        'current_focus' => $_SESSION['current_focus'] ?? 'General development',
        'next_priority' => $_SESSION['next_priority'] ?? 'Card effects implementation',
        'recent_changes' => $_SESSION['recent_changes'] ?? [],
        'known_issues' => $_SESSION['known_issues'] ?? [],
        'development_notes' => $_SESSION['development_notes'] ?? []
    ];
    
    return $context;
}

function generateChatIntroduction($build, $gameState, $diagnostics, $systemErrors) {
    $systemHealth = empty($systemErrors) ? "healthy" : "has some minor issues";
    $cardsCount = $diagnostics['json']['cards_count'];
    $currentPriority = getCurrentSessionContext()['next_priority'];
    
    return "Hello Claude! 👋 

We're working on the **NRD Sandbox** - a PHP-based tactical card battle game development tool. I need to hand off our current development session to you with full technical context.

**🎯 Current Status:** 
- System is {$systemHealth} with {$cardsCount} cards in the library
- All major systems working: AJAX combat, card creator, debug panels
- Ready to continue with: {$currentPriority}

**🔄 What I need:** 
Please review the comprehensive technical context below and confirm you understand the current system state. Then we can continue development exactly where we left off.

**📋 Complete System Context:**
";
}

// ===================================================================
// RUN DIAGNOSTICS
// ===================================================================
$systemDiagnostics = runSystemDiagnostics();
$gameState = checkGameState();
$systemErrors = checkForErrors();
$gitStatus = checkGitStatus();
$sessionContext = getCurrentSessionContext();
$chatIntroduction = generateChatIntroduction($build, $gameState, $systemDiagnostics, $systemErrors);

// Get current game configuration
$gameConfig = [
    'hand_size' => $gameState['rules']['starting_hand_size'],
    'max_hand_size' => $gameState['rules']['max_hand_size'],
    'deck_size' => $gameState['rules']['deck_size'],
    'cards_drawn_per_turn' => $gameState['rules']['cards_drawn_per_turn'],
    'starting_player' => $gameState['rules']['starting_player']
];

// Get card count
$cardCount = $systemDiagnostics['json']['cards_count'];

// Get mech configuration
$playerMech = $gameState['mechs']['player'];
$enemyMech = $gameState['mechs']['enemy'];

// Get hand status
$playerHandCount = $gameState['hand']['cards_in_hand'];

// Generate AI context document
$aiContext = generateEnhancedAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech, $playerHandCount, $systemDiagnostics, $gameState, $systemErrors, $gitStatus, $sessionContext);

function generateEnhancedAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech, $playerHandCount, $diagnostics, $gameState, $errors) {
    $timestamp = date('Y-m-d H:i:s');
    $version = $build['version'];
    
    // Generate status indicators
    $authStatus = $diagnostics['auth']['status'] === 'ACTIVE' ? '✅' : '❌';
    $jsonStatus = $diagnostics['json']['json_valid'] && $diagnostics['json']['cards_file_readable'] ? '✅' : '❌';
    $filesStatus = '✅'; // Will check if any core files are missing
    $errorsStatus = empty($errors) ? '✅' : '⚠️';
    
    // Check files status
    foreach ($diagnostics['files'] as $file => $status) {
        if (!$status['exists']) {
            $filesStatus = '❌';
            break;
        }
    }
    
    return "# NRD Sandbox - Enhanced AI Context Handoff {$version}
**Generated:** {$timestamp} | **For:** Next Claude Session
**Session Status:** Active Development | **Environment:** " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "

## 🚦 **CURRENT SYSTEM STATUS** (Real-Time Diagnostics)

### **Core Systems Health Check**
- {$authStatus} **Authentication**: " . $diagnostics['auth']['status'] . " (User: " . $diagnostics['auth']['user'] . ")
- {$jsonStatus} **JSON Data System**: " . ($diagnostics['json']['json_valid'] ? 'Valid' : 'Invalid') . " ({$cardCount} cards loaded)
- {$filesStatus} **Core Files**: " . (count($diagnostics['files'])) . " files checked
- {$errorsStatus} **Error Status**: " . (empty($errors) ? 'No errors detected' : count($errors) . ' issues found') . "

### **File System Status**
- **Cards JSON**: " . ($diagnostics['json']['cards_file_exists'] ? 'EXISTS' : 'MISSING') . " | Readable: " . ($diagnostics['json']['cards_file_readable'] ? 'YES' : 'NO') . " | Writable: " . ($diagnostics['json']['cards_file_writable'] ? 'YES' : 'NO') . "
- **Data Directory**: Writable: " . ($diagnostics['json']['data_dir_writable'] ? 'YES' : 'NO') . "
- **File Size**: " . ($diagnostics['json']['file_size'] ?? 'Unknown') . "

### **Game State Snapshot**
- **Player Mech**: HP {$playerMech['HP']}/{$playerMech['MAX_HP']} | ATK {$playerMech['ATK']} | DEF {$playerMech['DEF']}
- **Enemy Mech**: HP {$enemyMech['HP']}/{$enemyMech['MAX_HP']} | ATK {$enemyMech['ATK']} | DEF {$enemyMech['DEF']}
- **Player Hand**: {$playerHandCount} cards (Max: {$gameConfig['max_hand_size']})
- **Equipment Status**: P.Weapon:" . ($gameState['equipment']['player_weapon'] ? '✓' : '✗') . " P.Armor:" . ($gameState['equipment']['player_armor'] ? '✓' : '✗') . " | E.Weapon:" . ($gameState['equipment']['enemy_weapon'] ? '✓' : '✗') . " E.Armor:" . ($gameState['equipment']['enemy_armor'] ? '✓' : '✗') . "

### **Session Information**
- **Session ID**: " . $diagnostics['auth']['session_id'] . "
- **Timestamp**: {$timestamp}
- **Recent Actions**: " . (empty($gameState['recent_actions']) ? 'None' : count($gameState['recent_actions']) . ' logged') . "

" . (!empty($errors) ? "
### **⚠️ System Issues Detected**
" . generateErrorReport($errors) : "") . "

## 🎯 **WHAT THIS IS**
A PHP-based web tool for prototyping tactical card battle games. Think \"card game development sandbox\" - not a finished game, but a tool for testing game mechanics, card balance, and UI concepts.

## ⚡ **IMMEDIATE CONTEXT (What works RIGHT NOW)**

### **Authentication System** {$authStatus}
- Login: `admin/password123` or `tester/testpass` (see `users.php`)
- Files: `auth.php`, `login.php`, `logout.php`
- Session-based, currently " . strtolower($diagnostics['auth']['status']) . "

### **Main Battlefield Interface** ✅ 
- File: `index.php` (main game interface)
- Features: Player/Enemy mechs, health bars, fan-style card layout
- Combat: AJAX-based attack/defend/reset buttons (v0.9.3+)
- Cards: Displays real cards from JSON, clickable for details modal
- Equipment: Working weapon/armor card system with equipping

### **Card Creator System** ✅
- Slide-in panel from right side
- Live preview as you type
- Saves to `data/cards.json`
- CRUD operations work perfectly
- Pattern: AJAX-based, smooth UX

### **Debug Panel System** ✅
- Slide-in panel from left side (opposite of card creator)
- Toggle with 🐛 Debug button in navigation
- Shows: System status, game state, mech HP, hand counts
- Reset functions: Mech health, card hands, game log, everything
- Action log with last 10 game events
- Technical info: version, session ID, equipment status

### **Configuration System** ✅
- Location: `/config/` directory
- Dashboard: `config/index.php`
- Mech Stats: `config/mechs.php` 
- Game Rules: `config/rules.php`
- AI Context: `config/ai-context.php` (this enhanced page)
- Shared Functions: `config/shared.php`

## 📁 **FILE STRUCTURE (What each file does)**

```
NRDSandbox/
├── index.php              # Main battlefield interface
├── auth.php               # Authentication logic  
├── login.php              # Login page
├── logout.php             # Logout functionality
├── users.php              # User credentials array
├── style.css              # ALL styling (includes debug panel CSS)
├── card-manager.php       # Card CRUD operations (JSON-based)
├── combat-manager.php     # AJAX combat endpoints
├── build-data.php         # Build information data
├── build-info.php         # Build information display
├── push.sh                # Git deployment script
├── config/
│   ├── index.php          # Configuration dashboard
│   ├── shared.php         # Shared config functions
│   ├── mechs.php          # Mech stat configuration
│   ├── rules.php          # Game rules configuration
│   └── ai-context.php     # Enhanced AI handoff generator (this file)
├── data/
│   └── cards.json         # Persistent card storage ({$cardCount} cards)
└── docs/                  # Documentation
```

## 🎮 **CURRENT GAME STATE**

### **Cards in System:**
- **Count:** {$cardCount} cards in `data/cards.json`
- **Types:** Spell, Weapon, Armor, Creature, Support
- **Sample card structure:**
```json
{
  \"id\": \"card_123456\",
  \"name\": \"Lightning Bolt\", 
  \"cost\": 3,
  \"type\": \"spell\",
  \"damage\": 5,
  \"description\": \"Deal 5 damage\",
  \"rarity\": \"common\",
  \"created_at\": \"2025-06-27 13:17:44\",
  \"created_by\": \"admin\"
}
```

### **Game Rules:**
- Starting Hand: {$gameConfig['hand_size']} cards
- Max Hand: {$gameConfig['max_hand_size']} cards
- Deck Size: {$gameConfig['deck_size']} cards
- Draw Per Turn: {$gameConfig['cards_drawn_per_turn']}
- Starting Player: {$gameConfig['starting_player']}

### **Mech Configuration:**
- Player: HP {$playerMech['HP']}, ATK {$playerMech['ATK']}, DEF {$playerMech['DEF']}
- Enemy: HP {$enemyMech['HP']}, ATK {$enemyMech['ATK']}, DEF {$enemyMech['DEF']}

### **Current Hand Status:**
- Player Hand: {$playerHandCount} cards
- Card Library: {$cardCount} available cards

## 🔧 **HOW THINGS WORK (Code Patterns)**

### **AJAX Pattern (Used in Combat, Card Creator & Debug Panel):**
```javascript
// Send request
fetch('combat-manager.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update UI without page reload
    }
});
```

### **Form Pattern (Used in Equipment & Debug Functions):**
```php
if (\$_POST['action']) {
    // Process action
    \$_SESSION['gameState'] = \$newState;
    header('Location: ' . \$_SERVER['PHP_SELF']);
    exit;
}
```

### **JSON Data Pattern:**
```php
// Load cards
\$data = json_decode(file_get_contents('data/cards.json'), true);
// Save cards  
file_put_contents('data/cards.json', json_encode(\$data, JSON_PRETTY_PRINT));
```

## 🚀 **SYSTEM CAPABILITIES & NEXT PRIORITIES**

### **✅ COMPLETED FEATURES**
1. **AJAX Combat System** - Combat buttons work without page reload
2. **Card Creator Interface** - Right-slide panel with live preview
3. **Debug Panel System** - Left-slide panel with diagnostics
4. **Equipment System** - Weapon/armor cards can be equipped
5. **Real-time Diagnostics** - This enhanced AI context system

### **🎯 IMMEDIATE DEVELOPMENT PRIORITIES**
1. **Card Effects System** - Make cards actually DO things when played
2. **Deck Building Interface** - Assign specific cards to player/enemy decks
3. **Advanced Combat** - Use ATK/DEF stats and equipment bonuses
4. **Card Targeting** - Select targets for spells and abilities

### **📋 TESTING CHECKLIST FOR EACH SESSION**

**Quick 2-Minute System Health Check:**
1. ✅ Login Test: Can log in with admin/password123
2. ✅ Interface Test: Main page loads without errors
3. ✅ AJAX Test: Combat buttons work without page reload
4. ✅ Card Creator: Right-side panel opens and functions
5. ✅ Debug Panel: Left-side panel shows current state
6. ✅ JSON Test: Cards display and can be created/saved

## 🔄 **DEVELOPMENT WORKFLOW**
1. **Local:** Mac with VS Code at `/Volumes/Samples/NRDSandbox/`
2. **Testing:** http://localhost/NRDSandbox/
3. **Version Control:** Git with `push.sh` script
4. **Deployment:** Manual upload to newretrodawn.dev/NRDSandbox
5. **Database:** JSON files (not MySQL yet)

## 📝 **CODE STANDARDS**
- PSR-4 autoloading where possible
- Input sanitization with `htmlspecialchars()`
- AJAX endpoints return JSON responses
- Error handling with try-catch
- Consistent function naming
- Inline documentation for complex logic

## 💾 **SESSION DATA STRUCTURE**
```php
\$_SESSION = [
    'username' => 'admin',
    'playerMech' => ['HP' => {$playerMech['HP']}, 'ATK' => {$playerMech['ATK']}, 'DEF' => {$playerMech['DEF']}, 'MAX_HP' => {$playerMech['MAX_HP']}],
    'enemyMech' => ['HP' => {$enemyMech['HP']}, 'ATK' => {$enemyMech['ATK']}, 'DEF' => {$enemyMech['DEF']}, 'MAX_HP' => {$enemyMech['MAX_HP']}],
    'player_hand' => [/* {$playerHandCount} card objects */],
    'playerEquipment' => ['weapon' => " . ($gameState['equipment']['player_weapon'] ? 'equipped' : 'null') . ", 'armor' => " . ($gameState['equipment']['player_armor'] ? 'equipped' : 'null') . "],
    'enemyEquipment' => ['weapon' => " . ($gameState['equipment']['enemy_weapon'] ? 'equipped' : 'null') . ", 'armor' => " . ($gameState['equipment']['enemy_armor'] ? 'equipped' : 'null') . "],
    'log' => [/* " . count($gameState['recent_actions']) . " recent actions */]
];
```

---

## 🆘 **TROUBLESHOOTING GUIDE**
1. **Cards not saving:** Check data/ directory permissions (" . ($diagnostics['json']['data_dir_writable'] ? 'WRITABLE' : 'NOT WRITABLE') . ")
2. **Login fails:** Check users.php credentials  
3. **Page doesn't load:** Check PHP syntax errors
4. **AJAX not working:** Check browser console for JavaScript errors
5. **JSON errors:** Validate cards.json format
6. **Debug panel not showing:** Check if debug CSS is in style.css

---

**💡 TIP FOR AI:** This is a development tool, not a polished game. Focus on functionality over aesthetics. User can test changes immediately on localhost. All major systems are working and ready for feature development.

**🎯 CURRENT STATUS:** System is stable and ready for card effects implementation or deck building features.";
}

function generateErrorReport($errors) {
    $report = "";
    
    if (isset($errors['php_errors'])) {
        $report .= "- **PHP Error**: " . $errors['php_errors']['message'] . " in " . $errors['php_errors']['file'] . " line " . $errors['php_errors']['line'] . "\n";
    }
    
    if (isset($errors['permissions'])) {
        $report .= "- **Permission Issues**: " . implode(', ', $errors['permissions']) . "\n";
    }
    
    if (isset($errors['missing_files'])) {
        $report .= "- **Missing Files**: " . implode(', ', $errors['missing_files']) . "\n";
    }
    
    return $report;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced AI Context System - NRD Sandbox</title>
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
            <a href="index.php" class="config-link">⚙️ Config Dashboard</a>
        </div>
        <div class="nav-center">
            <h1 class="game-title">ENHANCED AI CONTEXT SYSTEM</h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         SYSTEM STATUS DASHBOARD
         =================================================================== -->
    <main class="config-content">

        <!-- Real-Time System Status -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>🚦 Real-Time System Status</h2>
                    <div class="system-status <?= empty($systemErrors) ? 'healthy' : 'warning' ?>">
                        <?= empty($systemErrors) ? '✅ HEALTHY' : '⚠️ ISSUES DETECTED' ?>
                    </div>
                </div>
                
                <div class="status-grid">
                    <div class="status-card <?= $systemDiagnostics['auth']['status'] === 'ACTIVE' ? 'healthy' : 'error' ?>">
                        <div class="status-icon">🔐</div>
                        <div class="status-info">
                            <div class="status-title">Authentication</div>
                            <div class="status-value"><?= $systemDiagnostics['auth']['status'] ?></div>
                            <div class="status-detail">User: <?= $systemDiagnostics['auth']['user'] ?></div>
                        </div>
                    </div>
                    
                    <div class="status-card <?= $systemDiagnostics['json']['json_valid'] ? 'healthy' : 'error' ?>">
                        <div class="status-icon">📄</div>
                        <div class="status-info">
                            <div class="status-title">JSON Data</div>
                            <div class="status-value"><?= $systemDiagnostics['json']['cards_count'] ?> cards</div>
                            <div class="status-detail">
                                <?= $systemDiagnostics['json']['cards_file_readable'] ? '✅' : '❌' ?> Readable | 
                                <?= $systemDiagnostics['json']['cards_file_writable'] ? '✅' : '❌' ?> Writable
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-card healthy">
                        <div class="status-icon">🎮</div>
                        <div class="status-info">
                            <div class="status-title">Game State</div>
                            <div class="status-value">P:<?= $playerMech['HP'] ?> E:<?= $enemyMech['HP'] ?></div>
                            <div class="status-detail"><?= $playerHandCount ?> cards in hand</div>
                        </div>
                    </div>
                    
                    <div class="status-card <?= empty($systemErrors) ? 'healthy' : 'warning' ?>">
                        <div class="status-icon">⚠️</div>
                        <div class="status-info">
                            <div class="status-title">System Errors</div>
                            <div class="status-value"><?= empty($systemErrors) ? 'None' : count($systemErrors) ?></div>
                            <div class="status-detail"><?= empty($systemErrors) ? 'All systems OK' : 'Issues detected' ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($systemErrors)): ?>
                <div class="error-details">
                    <h4>⚠️ System Issues:</h4>
                    <ul>
                        <?php foreach ($systemErrors as $errorType => $errorData): ?>
                            <?php if (is_array($errorData)): ?>
                                <?php foreach ($errorData as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><?= htmlspecialchars($errorData) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Enhanced AI Context Output -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>📋 Enhanced AI Context for Chat Handoff</h2>
                    <div class="header-options">
                        <label class="checkbox-option">
                            <input type="checkbox" id="includeIntroduction" onchange="updateContextOutput()">
                            <span>Include Chat Introduction</span>
                        </label>
                        <button onclick="copyAIContext()" class="action-btn attack-btn">📋 Copy All</button>
                    </div>
                </div>
                
                <div class="ai-context-container">
                    <textarea id="aiContextText" readonly class="ai-context-text"><?= htmlspecialchars($aiContext) ?></textarea>
                </div>
                
                <div class="context-actions">
                    <button onclick="copyAIContext()" class="action-btn save-btn">📋 Copy to Clipboard</button>
                    <button onclick="regenerateContext()" class="action-btn reset-btn">🔄 Regenerate</button>
                    <a href="index.php" class="action-btn cancel-btn">↩️ Back to Config</a>
                </div>
            </div>
        </section>

        <!-- System Diagnostics Details -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>🔧 Detailed System Diagnostics</h2>
                </div>
                
                <div class="diagnostics-grid">
                    <div class="diagnostic-section">
                        <h4>📁 File System</h4>
                        <table class="diagnostic-table">
                            <tr><td>Data Directory</td><td><?= $systemDiagnostics['json']['data_dir_writable'] ? '✅ Writable' : '❌ Not Writable' ?></td></tr>
                            <tr><td>Cards JSON</td><td><?= $systemDiagnostics['json']['cards_file_exists'] ? '✅ Exists' : '❌ Missing' ?></td></tr>
                            <tr><td>JSON Valid</td><td><?= $systemDiagnostics['json']['json_valid'] ? '✅ Valid' : '❌ Invalid' ?></td></tr>
                            <tr><td>File Size</td><td><?= $systemDiagnostics['json']['file_size'] ?? 'Unknown' ?></td></tr>
                        </table>
                    </div>
                    
                    <div class="diagnostic-section">
                        <h4>🎮 Game State</h4>
                        <table class="diagnostic-table">
                            <tr><td>Player HP</td><td><?= $playerMech['HP'] ?>/<?= $playerMech['MAX_HP'] ?></td></tr>
                            <tr><td>Enemy HP</td><td><?= $enemyMech['HP'] ?>/<?= $enemyMech['MAX_HP'] ?></td></tr>
                            <tr><td>Cards in Hand</td><td><?= $playerHandCount ?></td></tr>
                            <tr><td>Equipment</td><td>
                                P.W:<?= $gameState['equipment']['player_weapon'] ? '✓' : '✗' ?> 
                                P.A:<?= $gameState['equipment']['player_armor'] ? '✓' : '✗' ?> | 
                                E.W:<?= $gameState['equipment']['enemy_weapon'] ? '✓' : '✗' ?> 
                                E.A:<?= $gameState['equipment']['enemy_armor'] ? '✓' : '✗' ?>
                            </td></tr>
                        </table>
                    </div>
                    
                    <div class="diagnostic-section">
                        <h4>📄 Core Files</h4>
                        <table class="diagnostic-table">
                            <?php foreach ($systemDiagnostics['files'] as $fileName => $fileStatus): ?>
                            <tr>
                                <td><?= $fileName ?></td>
                                <td>
                                    <?= $fileStatus['exists'] ? '✅' : '❌' ?> 
                                    <?= $fileStatus['exists'] ? number_format($fileStatus['size']) . ' bytes' : 'Missing' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
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
            Enhanced AI Context System | Build <?= htmlspecialchars($build['version']) ?> | 
            Real-time diagnostics with comprehensive chat handoff documentation
        </div>
    </footer>

</div>

<script>
// Store the original context and introduction
const originalContext = <?= json_encode($aiContext) ?>;
const chatIntroduction = <?= json_encode($chatIntroduction) ?>;

function updateContextOutput() {
    const includeIntro = document.getElementById('includeIntroduction').checked;
    const textArea = document.getElementById('aiContextText');
    
    if (includeIntro) {
        textArea.value = chatIntroduction + "\n\n" + originalContext;
    } else {
        textArea.value = originalContext;
    }
}

function copyAIContext() {
    const textArea = document.getElementById('aiContextText');
    textArea.select();
    textArea.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        
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
    }
}

function regenerateContext() {
    window.location.reload();
}

document.getElementById('aiContextText').addEventListener('click', function() {
    this.select();
});

// Initialize the context output on page load
document.addEventListener('DOMContentLoaded', function() {
    updateContextOutput();
});
</script>

<style>
/* Enhanced AI Context specific styles */
.system-status {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 12px;
}

.system-status.healthy {
    background: #28a745;
    color: white;
}

.system-status.warning {
    background: #ffc107;
    color: #000;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 20px;
}

.status-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #444;
}

.status-card.healthy {
    background: rgba(40, 167, 69, 0.1);
    border-color: #28a745;
}

.status-card.warning {
    background: rgba(255, 193, 7, 0.1);
    border-color: #ffc107;
}

.status-card.error {
    background: rgba(220, 53, 69, 0.1);
    border-color: #dc3545;
}

.status-icon {
    font-size: 24px;
}

.status-title {
    font-weight: bold;
    color: #00d4ff;
    font-size: 14px;
}

.status-value {
    color: #fff;
    font-weight: bold;
    font-size: 16px;
}

.status-detail {
    color: #aaa;
    font-size: 12px;
}

.error-details {
    margin-top: 15px;
    padding: 15px;
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid #dc3545;
    border-radius: 6px;
}

.error-details h4 {
    color: #dc3545;
    margin-bottom: 10px;
}

.error-details ul {
    margin: 0;
    padding-left: 20px;
}

.error-details li {
    color: #ddd;
    margin-bottom: 5px;
}

.diagnostics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

.diagnostic-section h4 {
    color: #00d4ff;
    margin-bottom: 10px;
    border-bottom: 1px solid #333;
    padding-bottom: 5px;
}

.diagnostic-table {
    width: 100%;
    border-collapse: collapse;
}

.diagnostic-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #333;
    color: #ddd;
    font-size: 13px;
}

.diagnostic-table td:first-child {
    color: #aaa;
    font-weight: bold;
    width: 40%;
}

.ai-context-container {
    padding: 20px;
}

.ai-context-text {
    width: 100%;
    height: 500px;
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid #444;
    border-radius: 6px;
    padding: 15px;
    color: #ddd;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    resize: vertical;
    white-space: pre-wrap;
}

.ai-context-text:focus {
    outline: none;
    border-color: #00d4ff;
    box-shadow: 0 0 10px rgba(0, 212, 255, 0.3);
}

.context-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 20px;
    border-top: 1px solid #333;
}

@media (max-width: 768px) {
    .status-grid {
        grid-template-columns: 1fr;
    }
    
    .diagnostics-grid {
        grid-template-columns: 1fr;
    }
    
    .context-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

</body>
</html>