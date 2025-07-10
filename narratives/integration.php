<?php
/**
 * Integration script to include narrative system in main game
 * Include this in index.php to add story functionality
 */

// Check if story system should be activated
$storyMode = false;
$currentStoryId = null;

// Check for story start parameter
if (isset($_GET['start_story'])) {
    $currentStoryId = $_GET['start_story'];
    $storyMode = true;
}

// Check for existing story session
if (isset($_SESSION['current_story_id'])) {
    $currentStoryId = $_SESSION['current_story_id'];
    $storyMode = true;
}

// Include story system files if in story mode
if ($storyMode) {
    require_once 'narratives/StoryManager.php';
    require_once 'narratives/RewardManager.php';
    
    // Initialize story manager
    $storyManager = new StoryManager();
    $rewardManager = new RewardManager();
    
    // Load story if specified
    if ($currentStoryId) {
        $storyLoaded = $storyManager->loadStory($currentStoryId);
        if ($storyLoaded) {
            $_SESSION['current_story_id'] = $currentStoryId;
        } else {
            // Story not found, exit story mode
            $storyMode = false;
            unset($_SESSION['current_story_id']);
        }
    }
}

// Function to get story assets URLs
function getStoryAssets() {
    return [
        'css' => 'narratives/story-styles.css',
        'js' => 'narratives/story-integration.js',
        'api' => 'narratives/story-api.php'
    ];
}

// Function to render story integration HTML
function renderStoryIntegration() {
    global $storyMode, $currentStoryId;
    
    if (!$storyMode) {
        return '';
    }
    
    return '
    <!-- Story Integration -->
    <div id="storyIntegration" class="story-integration">
        <script>
            // Initialize story system
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof StoryNarrative !== "undefined" && "' . $currentStoryId . '") {
                    StoryNarrative.startStory("' . htmlspecialchars($currentStoryId) . '");
                }
            });
        </script>
    </div>';
}

// Function to process story effects on game state
function processStoryEffects() {
    global $storyMode, $rewardManager;
    
    if (!$storyMode || !isset($_SESSION['story_rewards'])) {
        return;
    }
    
    // Process pending story rewards
    $userId = $_SESSION['user_id'] ?? 0;
    $storyId = $_SESSION['current_story_id'] ?? '';
    
    if ($userId && $storyId) {
        $rewards = $_SESSION['story_rewards'] ?? [];
        if (!empty($rewards)) {
            $rewardManager->processStoryRewards($userId, $storyId, $rewards);
            // Clear processed rewards
            unset($_SESSION['story_rewards']);
        }
    }
    
    // Apply story stat modifications
    if (isset($_SESSION['story_stat_mods'])) {
        foreach ($_SESSION['story_stat_mods'] as $stat => $value) {
            $statUpper = strtoupper($stat);
            
            // Apply to player mech
            if (isset($_SESSION['playerMech'][$statUpper])) {
                $_SESSION['playerMech'][$statUpper] += $value;
            }
            
            // Apply to enemy mech if specified
            if (isset($_SESSION['enemyMech'][$statUpper])) {
                $_SESSION['enemyMech'][$statUpper] += $value;
            }
        }
    }
    
    // Apply story equipment
    if (isset($_SESSION['story_equipment'])) {
        foreach ($_SESSION['story_equipment'] as $slot => $cardId) {
            // Load card and equip it
            require_once 'database/CardManager.php';
            $cardManager = new CardManager();
            $card = $cardManager->getCardById($cardId);
            
            if ($card && ($card['type'] === 'weapon' || $card['type'] === 'armor')) {
                if (!isset($_SESSION['playerEquipment'])) {
                    $_SESSION['playerEquipment'] = [];
                }
                $_SESSION['playerEquipment'][$slot] = $card;
            }
        }
    }
    
    // Apply story card bias
    if (isset($_SESSION['story_bias'])) {
        // This would be used by CardManager when drawing cards
        // The actual implementation depends on how card drawing works
    }
}

// Function to add story navigation to main menu
function addStoryNavigation() {
    return '
    <div class="story-nav">
        <a href="narratives/" class="nav-link" title="Story Manager">
            📖 Stories
        </a>
    </div>';
}

// Function to add story mode indicator
function renderStoryModeIndicator() {
    global $storyMode, $currentStoryId;
    
    if (!$storyMode) {
        return '';
    }
    
    $storyTitle = 'Story Mode';
    if ($currentStoryId) {
        // Try to get story title
        $storyFile = __DIR__ . '/narratives/data/' . $currentStoryId . '.json';
        if (file_exists($storyFile)) {
            $storyData = json_decode(file_get_contents($storyFile), true);
            if ($storyData && isset($storyData['title'])) {
                $storyTitle = $storyData['title'];
            }
        }
    }
    
    return '
    <div class="story-mode-indicator">
        <div class="story-mode-badge">
            <span class="story-mode-icon">📖</span>
            <span class="story-mode-text">' . htmlspecialchars($storyTitle) . '</span>
            <button onclick="StoryNarrative.exitStory()" class="story-mode-exit" title="Exit Story Mode">×</button>
        </div>
    </div>';
}

// Function to check if story system is available
function isStorySystemAvailable() {
    return file_exists(__DIR__ . '/narratives/StoryManager.php');
}

// Function to get story system status
function getStorySystemStatus() {
    global $storyMode, $currentStoryId;
    
    return [
        'available' => isStorySystemAvailable(),
        'active' => $storyMode,
        'current_story' => $currentStoryId,
        'session_data' => [
            'rewards' => $_SESSION['story_rewards'] ?? [],
            'variables' => $_SESSION['story_variables'] ?? [],
            'stat_mods' => $_SESSION['story_stat_mods'] ?? [],
            'equipment' => $_SESSION['story_equipment'] ?? [],
            'bias' => $_SESSION['story_bias'] ?? []
        ]
    ];
}

// CSS for story integration
function renderStoryIntegrationCSS() {
    return '
    <style>
        .story-integration {
            position: relative;
            z-index: 999;
        }
        
        .story-nav {
            display: inline-block;
            margin-left: 10px;
        }
        
        .story-nav .nav-link {
            color: #00d4ff;
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid #00d4ff;
            border-radius: 4px;
            font-size: 0.9em;
            transition: all 0.2s;
        }
        
        .story-nav .nav-link:hover {
            background: #00d4ff;
            color: #000;
        }
        
        .story-mode-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1001;
        }
        
        .story-mode-badge {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: white;
            padding: 8px 12px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .story-mode-icon {
            font-size: 1.1em;
        }
        
        .story-mode-exit {
            background: none;
            border: none;
            color: white;
            font-size: 1.2em;
            cursor: pointer;
            padding: 0 4px;
            margin-left: 4px;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        
        .story-mode-exit:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Integration with existing narrative guide */
        body.story-active .narrative-guide-panel {
            left: 400px;
        }
        
        /* Story panel appears from left */
        .story-panel {
            z-index: 1000;
        }
        
        .story-panel.active + .battlefield-container {
            margin-left: 380px;
        }
    </style>';
}

// Process story effects on page load
if ($storyMode) {
    processStoryEffects();
}
?>