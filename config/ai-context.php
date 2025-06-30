<?php
// ===================================================================
// NRD SANDBOX - AI CONTEXT SYSTEM FOR SEAMLESS CHAT HANDOFFS
// ===================================================================
require '../auth.php';

// Get current build information
$build = require '../build-data.php';  // FIXED: Updated to use build-data.php

// Get current game configuration
$gameConfig = [
    'hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
    'deck_size' => $_SESSION['deck_size'] ?? 20,
    'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
    'starting_player' => $_SESSION['starting_player'] ?? 'player'
];

// Get card count
$cardCount = 0;
if (file_exists('../data/cards.json')) {
    $cardData = json_decode(file_get_contents('../data/cards.json'), true);
    $cardCount = count($cardData['cards'] ?? []);
}

// Get mech configuration
$playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15];
$enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10];

// Get hand status
$playerHandCount = count($_SESSION['player_hand'] ?? []);

// Generate AI context document
$aiContext = generateAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech, $playerHandCount);

function generateAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech, $playerHandCount) {
    $timestamp = date('Y-m-d H:i:s');
    $version = $build['version'];
    
    return "# NRD Sandbox - Complete AI Context Handoff {$version}
**Generated:** {$timestamp} | **For:** Next Claude Session
**Project Status:** Stable, Ready for Feature Development

## 🎯 **WHAT THIS IS**
A PHP-based web tool for prototyping tactical card battle games. Think \"card game development sandbox\" - not a finished game, but a tool for testing game mechanics, card balance, and UI concepts.

## ⚡ **IMMEDIATE CONTEXT (What works RIGHT NOW)**

### **Authentication System** ✅
- Login: `admin/password123` or `tester/testpass` (see `users.php`)
- Files: `auth.php`, `login.php`, `logout.php`
- Session-based, works perfectly

### **Main Battlefield Interface** ✅ 
- File: `index.php` (main game interface)
- Features: Player/Enemy mechs, health bars, fan-style card layout
- Combat: Basic attack/defend buttons (currently form-based, NEEDS AJAX conversion)
- Cards: Displays real cards from JSON, clickable for details modal
- Equipment: Working weapon/armor card system with equipping

### **Card Creator System** ✅
- Slide-in panel from right side
- Live preview as you type
- Saves to `data/cards.json`
- CRUD operations work perfectly
- Pattern: AJAX-based, smooth UX

### **Debug Panel System** ✅ **NEW!**
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
- AI Context: `config/ai-context.php` (this page)
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
├── build-data.php         # Build information data (CLEANED UP)
├── build-info.php         # Build information display
├── push.sh                # Git deployment script
├── config/
│   ├── index.php          # Configuration dashboard
│   ├── shared.php         # Shared config functions
│   ├── mechs.php          # Mech stat configuration
│   ├── rules.php          # Game rules configuration
│   └── ai-context.php     # AI handoff generator (this file)
├── data/
│   └── cards.json         # Persistent card storage
└── docs/                  # Documentation (suggested)
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

### **AJAX Pattern (Used in Card Creator & Debug Panel):**
```javascript
// Send request
fetch('card-manager.php', {
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

### **Form Pattern (Used in Combat - NEEDS CONVERSION):**
```php
if (\$_POST['damage']) {
    // Update game state
    \$_SESSION['playerMech'] = \$playerMech;
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

### **Debug System Pattern (NEW):**
```javascript
// Toggle debug panel (slides from left)
function toggleDebugPanel() {
    const panel = document.getElementById('debugPanel');
    const overlay = document.getElementById('debugOverlay');
    panel.classList.toggle('active');
    overlay.classList.toggle('active');
}
```

## 🚀 **NEXT LOGICAL STEPS (Priority Order)**

### **1. Convert Combat to AJAX** (Immediate - Ready to implement)
- File: `index.php` 
- Convert form-based combat buttons to AJAX
- Follow card creator/debug panel pattern
- Remove `window.location.reload()`
- Status: Debug system in place, perfect for testing

### **2. Deck Building System** (Next Phase)
- Assign cards to player/enemy decks
- Deck composition rules
- Scenario-specific card pools

### **3. Card Effects System** (Future)
- Implement card abilities
- Target selection
- Effect resolution

## 🧪 **HOW TO TEST THINGS**

### **Authentication:**
```
1. Go to /login.php
2. Use: admin/password123
3. Should redirect to index.php
```

### **Debug Panel:**
```
1. Click \"🐛 Debug\" button in top navigation
2. Panel should slide in from left
3. Should show system status, game state, reset controls
4. Test reset functions (mech health, card hands, etc.)
5. Check action log for game events
```

### **Card Creator:**
```
1. Click \"🃏 Card Creator\" button
2. Fill out card form
3. Should see live preview update
4. Click \"Save Card\" - should save to JSON
5. Check card library shows new card
```

### **Mech Configuration:**
```
1. Go to /config/mechs.php  
2. Change HP values
3. Click save
4. Return to main game - should see new HP values
```

### **Combat System:**
```
1. Click \"Attack Enemy\" button
2. Should reduce enemy HP by 10
3. Currently triggers page reload (NEEDS AJAX)
4. Check debug panel for updated HP values
```

## 🐛 **KNOWN ISSUES & NEXT PRIORITIES**

1. **Combat buttons cause page reload** (form-based, not AJAX) - READY TO FIX
2. **No card effects implemented** (cards are just data)
3. **No actual deck building** (all cards accessible in hand)
4. **Mobile warning** but no mobile optimization

## 💾 **SESSION DATA STRUCTURE**
```php
\$_SESSION = [
    'username' => 'admin',
    'playerMech' => ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100],
    'enemyMech' => ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100],
    'player_hand' => [/* array of card objects */],
    'playerEquipment' => ['weapon' => null, 'armor' => null],
    'enemyEquipment' => ['weapon' => {...}, 'armor' => {...}],
    'log' => ['array of game events']
];
```

## 🎨 **STYLING NOTES**
- Single CSS file: `style.css`
- Uses CSS custom properties (variables)
- Responsive grid layout
- Dark theme with blue accents
- Card animations and hover effects
- **NEW:** Debug panel styles included (left-side slide-in)

## 🔄 **DEVELOPMENT WORKFLOW**
1. **Local:** Mac with VS Code at `/Volumes/Samples/NRDSandbox/`
2. **Testing:** http://localhost/NRDSandbox/
3. **Version Control:** Git with `push.sh` script
4. **Deployment:** Manual upload to newretrodawn.dev/NRDSandbox
5. **Database:** JSON files (not MySQL yet)

## 📝 **CODE STANDARDS**
- PSR-4 autoloading where possible
- Input sanitization with `htmlspecialchars()`
- Error handling with try-catch
- Consistent function naming
- Inline documentation for complex logic

## 🧹 **RECENT CLEANUP COMPLETED**
- **Fixed:** Removed duplicate `builds.php` file, now using `build-data.php`
- **Fixed:** All config files updated to use correct build data file
- **Added:** Complete debug panel system with reset functions
- **Cleaned:** Navigation bar - single debug button instead of duplicates
- **Organized:** All form processing into logical sections
- **Tested:** All major systems working properly

---

## 🆘 **IF SOMETHING BREAKS**
1. **Cards not saving:** Check `data/` directory permissions
2. **Login fails:** Check `users.php` credentials  
3. **Page doesn't load:** Check PHP syntax errors
4. **Config not working:** Check `config/shared.php` functions
5. **CSS broken:** Check `style.css` path
6. **Debug panel not showing:** Check if debug CSS is in `style.css`

---

**💡 TIP FOR AI:** This is a development tool, not a polished game. Focus on functionality over aesthetics. User can test changes immediately on localhost. The debug panel is perfect for testing new features!

**🎯 IMMEDIATE WIN:** Convert combat actions to AJAX following the card creator pattern. Debug panel is ready for testing the results.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Context System - NRD Sandbox</title>
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
            <h1 class="game-title">AI CONTEXT SYSTEM</h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         AI CONTEXT CONTENT
         =================================================================== -->
    <main class="config-content">

        <!-- AI Context Overview -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>🤖 AI Chat Handoff Documentation</h2>
                    <div class="build-badge">Auto-Generated</div>
                </div>
                
                <div class="ai-context-info">
                    <div class="context-description">
                        <p><strong>Purpose:</strong> This page generates complete project context for seamless Claude chat restarts. Copy the text below and paste it into new Claude conversations to maintain full project continuity.</p>
                        
                        <div class="context-stats">
                            <div class="stat-item">
                                <span class="stat-label">Current Version:</span>
                                <span class="stat-value"><?= htmlspecialchars($build['version']) ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Cards Created:</span>
                                <span class="stat-value"><?= $cardCount ?> cards</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Player Hand:</span>
                                <span class="stat-value"><?= $playerHandCount ?> cards</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Generated:</span>
                                <span class="stat-value"><?= date('Y-m-d H:i:s') ?></span>
                            </div>
                        </div>
                        
                        <div class="recent-updates">
                            <h4>🔥 Recent Updates Include:</h4>
                            <ul>
                                <li>✅ <strong>Debug Panel System</strong> - Left-side slide panel with reset functions</li>
                                <li>✅ <strong>Dependency Cleanup</strong> - Fixed all config file build references</li>
                                <li>✅ <strong>Navigation Cleanup</strong> - Single debug button, organized layout</li>
                                <li>✅ <strong>Reset Functions</strong> - Mech health, card hands, game log, everything</li>
                                <li>🎯 <strong>Ready for AJAX</strong> - Combat conversion is next priority</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AI Context Document -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>📋 Copy This Text for New Claude Chats</h2>
                    <button onclick="copyAIContext()" class="action-btn attack-btn">📋 Copy All</button>
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

        <!-- Quick Resource Reference -->
        <section class="config-section">
            <div class="config-card">
                <div class="config-header">
                    <h2>📁 Quick Resource Reference</h2>
                </div>
                
                <div class="resource-grid">
                    <div class="resource-item">
                        <h4>🏠 Local Development</h4>
                        <p>Mac/VS Code environment<br>
                        <code>/Volumes/Samples/NRDSandbox/</code></p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>🌐 Live Production</h4>
                        <p>DreamHost hosting<br>
                        <code>newretrodawn.dev/NRDSandbox</code></p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>🗂️ Version Control</h4>
                        <p>GitHub repository<br>
                        <code>./push.sh deployment</code></p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>💾 Data Storage</h4>
                        <p>JSON + PHP Sessions<br>
                        <code>data/cards.json</code></p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>🐛 Debug System</h4>
                        <p>Left-side panel<br>
                        <code>Reset functions & logs</code></p>
                    </div>
                    
                    <div class="resource-item">
                        <h4>🎯 Next Priority</h4>
                        <p>Combat AJAX conversion<br>
                        <code>Follow card creator pattern</code></p>
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
            AI Context System | Build <?= htmlspecialchars($build['version']) ?> | 
            Auto-generated project handoff documentation with debug system info
        </div>
    </footer>

</div>

<script>
function copyAIContext() {
    const textArea = document.getElementById('aiContextText');
    textArea.select();
    textArea.setSelectionRange(0, 99999); // For mobile devices
    
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
    }
}

function regenerateContext() {
    // Reload the page to regenerate with current data
    window.location.reload();
}

// Auto-select text when textarea is clicked
document.getElementById('aiContextText').addEventListener('click', function() {
    this.select();
});
</script>

<style>
/* AI Context specific styles */
.ai-context-info {
    padding: 20px;
}

.context-description {
    background: rgba(0, 212, 255, 0.1);
    border-left: 4px solid #00d4ff;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.context-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.stat-label {
    color: #aaa;
    font-weight: bold;
}

.stat-value {
    color: #00d4ff;
    font-weight: bold;
}

.recent-updates {
    margin-top: 20px;
    padding: 15px;
    background: rgba(40, 167, 69, 0.1);
    border-left: 4px solid #28a745;
    border-radius: 6px;
}

.recent-updates h4 {
    color: #28a745;
    margin-bottom: 10px;
}

.recent-updates ul {
    margin: 0;
    padding-left: 20px;
}

.recent-updates li {
    color: #ddd;
    margin-bottom: 5px;
    font-size: 14px;
}

.ai-context-container {
    padding: 20px;
}

.ai-context-text {
    width: 100%;
    height: 600px;
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

.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 20px;
}

.resource-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid #444;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.resource-item h4 {
    color: #00d4ff;
    margin-bottom: 10px;
    font-size: 14px;
}

.resource-item p {
    color: #ddd;
    font-size: 12px;
    line-height: 1.4;
}

.resource-item code {
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #ffc107;
}

@media (max-width: 768px) {
    .context-stats {
        grid-template-columns: 1fr;
    }
    
    .resource-grid {
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