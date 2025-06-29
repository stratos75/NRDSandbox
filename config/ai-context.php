<?php
// ===================================================================
// NRD SANDBOX - AI CONTEXT SYSTEM FOR SEAMLESS CHAT HANDOFFS
// ===================================================================
require '../auth.php';

// Get current build information
$build = require '../builds.php';

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

// Generate AI context document
$aiContext = generateAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech);

function generateAIContext($build, $gameConfig, $cardCount, $playerMech, $enemyMech) {
    $timestamp = date('Y-m-d H:i:s');
    $version = $build['version'];
    
    return "# NRD Sandbox - AI Context Document {$version}
## COMPLETE PROJECT STATE FOR AI HANDOFF
Generated: {$timestamp}

### 🎯 PROJECT OVERVIEW
**Project Name:** NRD Sandbox
**Core Purpose:** PHP-based web tool for prototyping and balancing tactical card battle games
**Current Version:** {$version} - {$build['build_name']}
**Development Status:** {$build['notes']}

### ✅ COMPLETED PHASES (WORKING & TESTED)
- **Phase 1:** JSON card integration - Real cards display in battlefield hand
- **Phase 2:** Card detail modal system - Click cards for full information view  
- **Phase 3A:** Game rules configuration - Hand size, deck size, turn system controls
- **Major Refactor:** Clean /config/ directory system with organized pages
- **Authentication:** Login/logout system fully functional
- **Card Creator:** Create cards with live preview, save to JSON storage
- **Mech Configuration:** Player/Enemy HP/ATK/DEF settings with presets

### 🏗️ CURRENT TECHNICAL STATE
**File Structure:**
```
NRDSandbox/
├── auth.php, login.php, logout.php, users.php (authentication)
├── index.php (main battlefield interface)
├── style.css (comprehensive styling system)
├── card-manager.php (JSON card storage system)
├── builds.php (build data), build-info.php (build display)
├── config/ (organized configuration system)
│   ├── index.php (configuration dashboard)
│   ├── shared.php (shared functions)
│   ├── mechs.php (mech stats configuration)
│   ├── rules.php (game rules configuration)
│   └── ai-context.php (this AI handoff system)
├── data/cards.json (persistent card storage)
└── push.sh (deployment script)
```

**Technology Stack:**
- PHP 8.0+ with PSR standards compliance
- JSON file storage for cards and configuration
- PHP sessions for game state management
- Responsive CSS with mobile warnings
- JavaScript for interactive elements

### 📊 CURRENT GAME STATE
**Cards Created:** {$cardCount} cards in data/cards.json
**Game Rules Configuration:**
- Starting Hand Size: {$gameConfig['hand_size']} cards
- Maximum Hand Size: {$gameConfig['max_hand_size']} cards  
- Deck Size: {$gameConfig['deck_size']} cards
- Cards Drawn Per Turn: {$gameConfig['cards_drawn_per_turn']}
- Starting Player: {$gameConfig['starting_player']}

**Mech Configuration:**
- Player Mech: HP {$playerMech['HP']}, ATK {$playerMech['ATK']}, DEF {$playerMech['DEF']}
- Enemy Mech: HP {$enemyMech['HP']}, ATK {$enemyMech['ATK']}, DEF {$enemyMech['DEF']}

### 🌐 DEVELOPMENT ENVIRONMENT RESOURCES
**Local Development (Primary):**
- Platform: Mac with Visual Studio Code
- Location: /Volumes/Samples/NRDSandbox/
- Testing: http://localhost/NRDSandbox/
- Status: Most current version with active development

**Live Production:**
- URL: newretrodawn.dev/NRDSandbox
- Hosting: DreamHost with PHP/MySQL support
- Database: phpMyAdmin available (currently using JSON storage)
- Deployment: Manual upload when stable builds ready

**Version Control:**
- Repository: GitHub (private/public - user will specify)
- Deployment Script: ./push.sh available for commits
- Workflow: Local development → GitHub → Manual production deployment

### 📁 AVAILABLE RESOURCES FOR AI ASSISTANCE
**Complete File Access:** User has full access to all project files and can provide:
- Any specific PHP, CSS, or JavaScript files upon request
- Complete database of created cards (JSON format)
- Configuration files and session data
- Build history and changelog information
- Error logs and debugging information

**Code Standards:** Project follows PSR standards, clean documentation, and organized structure

### 🚀 NEXT DEVELOPMENT PHASE
**Current Priority:** Card Management System (Phase 4)
**Planned Features:**
- Deck Builder Interface (assign cards to player/enemy decks)
- Deck Composition Rules (control card type distribution)
- Scenario-Specific Pools (different card sets for testing)
- Deck Validation and Rarity Balancing

### 🔧 DEVELOPMENT WORKFLOW NOTES
- Focus on functionality over design (tool for game developers)
- Test locally before deployment suggestions
- Update build information with each completed phase
- Maintain clean, documented code for team collaboration
- Desktop-first approach (mobile not priority for development tool)

### ⚡ IMMEDIATE CONTEXT FOR NEW AI
**User Workflow:** User frequently needs to restart Claude chats due to context limits
**Resource Availability:** User can provide any project files, database exports, or specific code sections as needed
**Current Session:** User has working local environment and can test changes immediately
**Deployment Status:** Latest stable builds pushed to production when ready

---
**AI INSTRUCTION:** This project has solid foundations. User can provide any specific files or clarification needed. Focus on building requested features while maintaining code quality and updating build information appropriately.";
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
                                <span class="stat-label">Generated:</span>
                                <span class="stat-value"><?= date('Y-m-d H:i:s') ?></span>
                            </div>
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
            Auto-generated project handoff documentation
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
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

.resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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