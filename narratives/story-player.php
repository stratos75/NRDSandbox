<?php
/**
 * Standalone Story Player for NRDSandbox
 * Test and navigate stories without game integration
 */

session_start();

// NO AUTHENTICATION REQUIRED FOR STANDALONE TESTING
// This is a pure story testing tool - no login needed

// Handle story actions
$currentStory = null;
$currentNode = null;
$message = '';
$error = '';
$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $debug[] = "POST received - Action: " . $action;
    $debug[] = "POST data: " . json_encode($_POST);
    
    switch ($action) {
        case 'load_story':
            $storyId = $_POST['story_id'] ?? '';
            if ($storyId) {
                $storyFile = __DIR__ . '/data/' . $storyId . '.json';
                if (file_exists($storyFile)) {
                    $storyData = json_decode(file_get_contents($storyFile), true);
                    if ($storyData) {
                        $_SESSION['test_story'] = $storyData;
                        $_SESSION['test_current_node'] = 'start';
                        $_SESSION['test_variables'] = $storyData['variables'] ?? [];
                        $_SESSION['test_history'] = ['start'];
                        $message = "Story loaded: " . $storyData['title'];
                    } else {
                        $error = "Invalid story format";
                    }
                } else {
                    $error = "Story file not found";
                }
            }
            break;
            
        case 'make_choice':
            $choiceIndex = (int)($_POST['choice_index'] ?? -1);
            $debug[] = "Choice index: $choiceIndex";
            
            if (isset($_SESSION['test_story']) && $choiceIndex >= 0) {
                $story = $_SESSION['test_story'];
                $currentNodeId = $_SESSION['test_current_node'] ?? 'start';
                $debug[] = "Current node: $currentNodeId";
                
                if (isset($story['nodes'][$currentNodeId]['choices'][$choiceIndex])) {
                    $choice = $story['nodes'][$currentNodeId]['choices'][$choiceIndex];
                    $targetNode = $choice['target'] ?? '';
                    $debug[] = "Target node: $targetNode";
                    
                    if ($targetNode && isset($story['nodes'][$targetNode])) {
                        // Execute choice actions
                        if (isset($choice['actions'])) {
                            foreach ($choice['actions'] as $actionItem) {
                                if ($actionItem['type'] === 'set_variable') {
                                    $_SESSION['test_variables'][$actionItem['variable']] = $actionItem['value'];
                                    $debug[] = "Set variable: {$actionItem['variable']} = {$actionItem['value']}";
                                }
                            }
                        }
                        
                        // Move to target node
                        $_SESSION['test_current_node'] = $targetNode;
                        
                        // Add to history
                        if (!isset($_SESSION['test_history'])) {
                            $_SESSION['test_history'] = [];
                        }
                        $_SESSION['test_history'][] = $targetNode;
                        
                        $message = "Choice made: " . $choice['text'];
                        $debug[] = "Successfully moved to: $targetNode";
                    } else {
                        $error = "Invalid target node: $targetNode";
                        $debug[] = "ERROR: Target node not found";
                    }
                } else {
                    $error = "Invalid choice index: $choiceIndex";
                    $debug[] = "ERROR: Choice index not found";
                }
            } else {
                $error = "No story loaded or invalid choice";
                $debug[] = "ERROR: No story or invalid choice";
            }
            break;
            
        case 'restart_story':
            if (isset($_SESSION['test_story'])) {
                $_SESSION['test_current_node'] = 'start';
                $_SESSION['test_variables'] = $_SESSION['test_story']['variables'] ?? [];
                $_SESSION['test_history'] = ['start'];
                $message = "Story restarted";
            }
            break;
            
        case 'go_back':
            if (isset($_SESSION['test_history']) && count($_SESSION['test_history']) > 1) {
                array_pop($_SESSION['test_history']); // Remove current
                $previousNode = end($_SESSION['test_history']);
                $_SESSION['test_current_node'] = $previousNode;
                $message = "Went back to: $previousNode";
            }
            break;
            
        case 'clear_story':
            unset($_SESSION['test_story']);
            unset($_SESSION['test_current_node']);
            unset($_SESSION['test_variables']);
            unset($_SESSION['test_history']);
            $message = "Story cleared";
            break;
    }
}

// Get current story state
if (isset($_SESSION['test_story'])) {
    $currentStory = $_SESSION['test_story'];
    $currentNodeId = $_SESSION['test_current_node'] ?? 'start';
    $currentNode = $currentStory['nodes'][$currentNodeId] ?? null;
}

// Get available stories
$availableStories = [];
$dataDir = __DIR__ . '/data/';
if (is_dir($dataDir)) {
    foreach (glob($dataDir . '*.json') as $file) {
        $storyData = json_decode(file_get_contents($file), true);
        if ($storyData && isset($storyData['id'], $storyData['title'])) {
            $availableStories[] = [
                'id' => $storyData['id'],
                'title' => $storyData['title'],
                'description' => $storyData['description'] ?? ''
            ];
        }
    }
}

// Process variables in content
function processVariables($content, $variables) {
    return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($variables) {
        $variable = $matches[1];
        return $variables[$variable] ?? $matches[0];
    }, $content);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Player - NRDSandbox</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1 {
            color: #00d4ff;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .story-controls {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 10px;
            align-items: center;
        }
        
        .story-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        select, button {
            padding: 8px 15px;
            border: 1px solid #00d4ff;
            border-radius: 4px;
            background: #2d2d2d;
            color: #e0e0e0;
            cursor: pointer;
        }
        
        button {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #000;
            font-weight: 600;
        }
        
        button:hover {
            background: linear-gradient(135deg, #0099cc 0%, #007aa3 100%);
        }
        
        .story-content {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
        }
        
        .main-story {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 20px;
        }
        
        .story-title {
            color: #00d4ff;
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        
        .node-info {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #ccc;
        }
        
        .story-text {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            line-height: 1.6;
            font-size: 1.1em;
        }
        
        .choices {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .choice-button {
            background: linear-gradient(135deg, #2d2d2d 0%, #404040 100%);
            border: 1px solid #00d4ff;
            border-radius: 6px;
            padding: 15px;
            color: #e0e0e0;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s;
        }
        
        .choice-button:hover {
            background: linear-gradient(135deg, #404040 0%, #555555 100%);
            transform: translateX(5px);
        }
        
        .no-choices {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
        
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .debug-panel {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 15px;
        }
        
        .debug-panel h3 {
            color: #00d4ff;
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .variables-list, .history-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .variable-item, .history-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
            padding: 5px 8px;
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .history-item.current {
            background: rgba(0, 212, 255, 0.2);
            border: 1px solid rgba(0, 212, 255, 0.5);
        }
        
        .message {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00aa00;
            color: #00ff00;
        }
        
        .message.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #cc0000;
            color: #ff4444;
        }
        
        .no-story {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .nav-links {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .nav-links a {
            color: #00d4ff;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .nav-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .story-content {
                grid-template-columns: 1fr;
            }
            
            .story-controls {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">← Story Manager</a>
            <a href="../index.php">← Main Game</a>
            <a href="test-api.php">🧪 API Test</a>
            <span style="color: #ffaa00;">📖 STANDALONE STORY TESTER - No Game Integration</span>
        </div>
        
        <h1>📖 Story Player & Tester</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($debug)): ?>
            <div class="message" style="background: rgba(255, 255, 0, 0.1); border: 1px solid #ffaa00; color: #ffaa00;">
                <strong>Debug Info:</strong><br>
                <?php foreach ($debug as $debugItem): ?>
                    • <?= htmlspecialchars($debugItem) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="story-controls">
            <form method="post" class="story-selector">
                <label for="story_select">Choose Story:</label>
                <select name="story_id" id="story_select" required>
                    <option value="">Select a story...</option>
                    <?php foreach ($availableStories as $story): ?>
                        <option value="<?= htmlspecialchars($story['id']) ?>" 
                                <?= (isset($_SESSION['test_story']) && $_SESSION['test_story']['id'] === $story['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($story['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="action" value="load_story">Load Story</button>
            </form>
            
            <?php if ($currentStory): ?>
                <form method="post" style="display: inline;">
                    <button type="submit" name="action" value="restart_story">🔄 Restart</button>
                </form>
                
                <form method="post" style="display: inline;">
                    <button type="submit" name="action" value="go_back">⬅️ Back</button>
                </form>
                
                <form method="post" style="display: inline;">
                    <button type="submit" name="action" value="clear_story">✖️ Clear</button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if ($currentStory && $currentNode): ?>
            <div class="story-content">
                <div class="main-story">
                    <div class="story-title"><?= htmlspecialchars($currentStory['title']) ?></div>
                    
                    <div class="node-info">
                        <strong>Current Node:</strong> <?= htmlspecialchars($currentNode['id']) ?><br>
                        <strong>Node Title:</strong> <?= htmlspecialchars($currentNode['title'] ?? 'Untitled') ?><br>
                        <strong>Node Type:</strong> <?= htmlspecialchars($currentNode['type'] ?? 'text') ?>
                    </div>
                    
                    <div class="story-text">
                        <?= nl2br(htmlspecialchars(processVariables($currentNode['content'] ?? 'No content available.', $_SESSION['test_variables'] ?? []))) ?>
                    </div>
                    
                    <div class="choices">
                        <?php if (isset($currentNode['choices']) && !empty($currentNode['choices'])): ?>
                            <?php foreach ($currentNode['choices'] as $index => $choice): ?>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="choice_index" value="<?= $index ?>">
                                    <input type="hidden" name="action" value="make_choice">
                                    <button type="submit" class="choice-button">
                                        <?= htmlspecialchars($choice['text']) ?>
                                        <?php if (isset($choice['target'])): ?>
                                            <small style="color: #999; display: block; margin-top: 5px;">
                                                → <?= htmlspecialchars($choice['target']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-choices">
                                📜 End of story - No more choices available
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="sidebar">
                    <div class="debug-panel">
                        <h3>🔍 Story Variables</h3>
                        <div class="variables-list">
                            <?php if (isset($_SESSION['test_variables']) && !empty($_SESSION['test_variables'])): ?>
                                <?php foreach ($_SESSION['test_variables'] as $name => $value): ?>
                                    <div class="variable-item">
                                        <strong><?= htmlspecialchars($name) ?>:</strong> <?= htmlspecialchars($value) ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="color: #999; font-style: italic;">No variables set</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="debug-panel">
                        <h3>📍 Navigation History</h3>
                        <div class="history-list">
                            <?php if (isset($_SESSION['test_history']) && !empty($_SESSION['test_history'])): ?>
                                <?php foreach ($_SESSION['test_history'] as $index => $nodeId): ?>
                                    <div class="history-item <?= $nodeId === ($_SESSION['test_current_node'] ?? '') ? 'current' : '' ?>">
                                        <?= $index + 1 ?>. <?= htmlspecialchars($nodeId) ?>
                                        <?= $nodeId === ($_SESSION['test_current_node'] ?? '') ? ' (current)' : '' ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="color: #999; font-style: italic;">No history</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="debug-panel">
                        <h3>📊 Story Info</h3>
                        <div class="variable-item">
                            <strong>Total Nodes:</strong> <?= count($currentStory['nodes'] ?? []) ?>
                        </div>
                        <div class="variable-item">
                            <strong>Story Version:</strong> <?= htmlspecialchars($currentStory['version'] ?? 'Unknown') ?>
                        </div>
                        <div class="variable-item">
                            <strong>Story ID:</strong> <?= htmlspecialchars($currentStory['id'] ?? 'Unknown') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-story">
                <h3>No Story Loaded</h3>
                <p>Select a story from the dropdown above to start testing.</p>
                
                <?php if (empty($availableStories)): ?>
                    <p><em>No stories found in /narratives/data/ directory.</em></p>
                    <p>Create stories using Arrow and import them through the <a href="index.php" style="color: #00d4ff;">Story Manager</a>.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>