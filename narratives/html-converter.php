<?php
/**
 * HTML to JSON Story Converter for NRDSandbox
 * Converts custom HTML stories to NRDSandbox JSON format
 */

session_start();

$message = '';
$error = '';
$convertedStory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'convert_html':
            if (isset($_FILES['html_file']) && $_FILES['html_file']['error'] === UPLOAD_ERR_OK) {
                $htmlContent = file_get_contents($_FILES['html_file']['tmp_name']);
                $storyTitle = $_POST['story_title'] ?? 'Converted Story';
                $storyDescription = $_POST['story_description'] ?? 'Story converted from HTML';
                
                try {
                    $convertedStory = convertHtmlToStory($htmlContent, $storyTitle, $storyDescription);
                    $message = "HTML converted successfully! Review and save below.";
                } catch (Exception $e) {
                    $error = "Conversion failed: " . $e->getMessage();
                }
            } else {
                $error = "No HTML file uploaded or upload error";
            }
            break;
            
        case 'save_story':
            $storyData = $_POST['story_json'] ?? '';
            $storyId = $_POST['story_id'] ?? '';
            
            if ($storyData && $storyId) {
                try {
                    $story = json_decode($storyData, true);
                    if ($story) {
                        $filename = __DIR__ . '/data/' . $storyId . '.json';
                        file_put_contents($filename, $storyData);
                        $message = "Story saved as: $storyId.json";
                    } else {
                        $error = "Invalid JSON data";
                    }
                } catch (Exception $e) {
                    $error = "Save failed: " . $e->getMessage();
                }
            } else {
                $error = "Missing story data or ID";
            }
            break;
    }
}

/**
 * Convert HTML content to NRDSandbox story JSON format
 */
function convertHtmlToStory($htmlContent, $title, $description) {
    $story = [
        'id' => generateStoryId($title),
        'title' => $title,
        'description' => $description,
        'nodes' => [],
        'variables' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ];
    
    // Parse HTML structure
    $dom = new DOMDocument();
    @$dom->loadHTML($htmlContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    // Extract story nodes from HTML
    $story['nodes'] = extractNodesFromHtml($dom);
    
    // Ensure we have a start node
    if (!isset($story['nodes']['start'])) {
        $story['nodes']['start'] = createDefaultStartNode($title);
    }
    
    return $story;
}

/**
 * Extract story nodes from HTML DOM
 */
function extractNodesFromHtml($dom) {
    $nodes = [];
    
    // Look for different HTML patterns that might represent story structure
    $sections = $dom->getElementsByTagName('section');
    $divs = $dom->getElementsByTagName('div');
    $articles = $dom->getElementsByTagName('article');
    
    $nodeCounter = 1;
    
    // Try to extract from sections first
    if ($sections->length > 0) {
        foreach ($sections as $section) {
            $node = extractNodeFromElement($section, 'section_' . $nodeCounter);
            if ($node) {
                $nodes[$node['id']] = $node;
                $nodeCounter++;
            }
        }
    }
    
    // Try to extract from divs with specific classes/ids
    if (empty($nodes) && $divs->length > 0) {
        foreach ($divs as $div) {
            $class = $div->getAttribute('class');
            $id = $div->getAttribute('id');
            
            // Look for story-related classes/ids
            if (strpos($class, 'story') !== false || 
                strpos($class, 'node') !== false ||
                strpos($class, 'scene') !== false ||
                strpos($id, 'story') !== false ||
                strpos($id, 'node') !== false) {
                
                $nodeId = $id ?: 'node_' . $nodeCounter;
                $node = extractNodeFromElement($div, $nodeId);
                if ($node) {
                    $nodes[$node['id']] = $node;
                    $nodeCounter++;
                }
            }
        }
    }
    
    // If still no nodes found, create from paragraphs and headers
    if (empty($nodes)) {
        $nodes = extractFromGenericHtml($dom);
    }
    
    return $nodes;
}

/**
 * Extract a story node from an HTML element
 */
function extractNodeFromElement($element, $defaultId) {
    $nodeId = $element->getAttribute('id') ?: $defaultId;
    
    // Extract title from h1-h6 tags
    $title = '';
    $titleTags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    foreach ($titleTags as $tag) {
        $headers = $element->getElementsByTagName($tag);
        if ($headers->length > 0) {
            $title = trim($headers->item(0)->textContent);
            break;
        }
    }
    
    // Extract content from paragraphs
    $content = '';
    $paragraphs = $element->getElementsByTagName('p');
    foreach ($paragraphs as $p) {
        $content .= trim($p->textContent) . "\n\n";
    }
    
    // Extract choices from links or buttons
    $choices = [];
    $links = $element->getElementsByTagName('a');
    $buttons = $element->getElementsByTagName('button');
    
    foreach ($links as $link) {
        $href = $link->getAttribute('href');
        $text = trim($link->textContent);
        if ($text && $href) {
            $target = extractTargetFromHref($href);
            if ($target) {
                $choices[] = [
                    'text' => $text,
                    'target' => $target,
                    'conditions' => [],
                    'actions' => []
                ];
            }
        }
    }
    
    foreach ($buttons as $button) {
        $onclick = $button->getAttribute('onclick');
        $dataTarget = $button->getAttribute('data-target');
        $text = trim($button->textContent);
        
        if ($text) {
            $target = $dataTarget ?: extractTargetFromOnclick($onclick);
            if ($target) {
                $choices[] = [
                    'text' => $text,
                    'target' => $target,
                    'conditions' => [],
                    'actions' => []
                ];
            }
        }
    }
    
    return [
        'id' => $nodeId,
        'type' => 'text',
        'title' => $title ?: 'Untitled',
        'content' => trim($content),
        'choices' => $choices,
        'conditions' => [],
        'actions' => []
    ];
}

/**
 * Extract from generic HTML when no structured elements found
 */
function extractFromGenericHtml($dom) {
    $nodes = [];
    
    // Get all text content
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        $body = $dom;
    }
    
    $allText = strip_tags($body->textContent);
    $paragraphs = array_filter(explode("\n", $allText), 'trim');
    
    if (!empty($paragraphs)) {
        $content = implode("\n\n", array_slice($paragraphs, 0, 10)); // First 10 paragraphs
        
        $nodes['start'] = [
            'id' => 'start',
            'type' => 'text',
            'title' => 'Story Beginning',
            'content' => $content,
            'choices' => [
                [
                    'text' => 'Continue',
                    'target' => 'end',
                    'conditions' => [],
                    'actions' => []
                ]
            ],
            'conditions' => [],
            'actions' => []
        ];
        
        $nodes['end'] = [
            'id' => 'end',
            'type' => 'text',
            'title' => 'Story End',
            'content' => 'The story concludes here.',
            'choices' => [],
            'conditions' => [],
            'actions' => []
        ];
    }
    
    return $nodes;
}

/**
 * Extract target node from href attribute
 */
function extractTargetFromHref($href) {
    // Remove # from anchor links
    if (strpos($href, '#') === 0) {
        return substr($href, 1);
    }
    
    // Extract from various URL patterns
    if (preg_match('/[?&]node=([^&]+)/', $href, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/[?&]target=([^&]+)/', $href, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Extract target node from onclick attribute
 */
function extractTargetFromOnclick($onclick) {
    if (preg_match('/goto\([\'"]([^\'"]+)[\'"]\)/', $onclick, $matches)) {
        return $matches[1];
    }
    
    if (preg_match('/navigateTo\([\'"]([^\'"]+)[\'"]\)/', $onclick, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Generate unique story ID from title
 */
function generateStoryId($title) {
    $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($title));
    $base = preg_replace('/_+/', '_', $base);
    $base = trim($base, '_');
    
    if (empty($base)) {
        $base = 'story';
    }
    
    return $base . '_' . time();
}

/**
 * Create default start node
 */
function createDefaultStartNode($title) {
    return [
        'id' => 'start',
        'type' => 'text',
        'title' => $title,
        'content' => 'This story was converted from HTML. The original structure may need manual adjustment.',
        'choices' => [],
        'conditions' => [],
        'actions' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML to JSON Converter - NRDSandbox</title>
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
        
        .converter-section {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00d4ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #00d4ff;
            font-weight: 600;
        }
        
        input, textarea, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #00d4ff;
            border-radius: 4px;
            background: #2d2d2d;
            color: #e0e0e0;
            font-family: inherit;
        }
        
        button {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: #000;
            font-weight: 600;
            cursor: pointer;
            width: auto;
            padding: 10px 20px;
        }
        
        button:hover {
            background: linear-gradient(135deg, #0099cc 0%, #007aa3 100%);
        }
        
        .json-output {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
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
        
        .help-section {
            background: rgba(0, 212, 255, 0.1);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .help-section h3 {
            color: #00d4ff;
            margin-top: 0;
        }
        
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-links">
            <a href="story-player.php">📖 Story Player</a>
            <a href="index.php">← Story Manager</a>
            <a href="../index.php">← Main Game</a>
        </div>
        
        <h1>🔄 HTML to JSON Story Converter</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="help-section">
            <h3>How This Works</h3>
            <p>This tool converts your custom HTML stories into the JSON format that NRDSandbox uses. It tries to automatically detect story structure, but you may need to manually adjust the output.</p>
            
            <strong>Supported HTML Patterns:</strong>
            <ul>
                <li><code>&lt;section&gt;</code> or <code>&lt;div class="story"&gt;</code> for story nodes</li>
                <li><code>&lt;h1-h6&gt;</code> for node titles</li>
                <li><code>&lt;p&gt;</code> for story content</li>
                <li><code>&lt;a href="#target"&gt;</code> for choices</li>
                <li><code>&lt;button data-target="node"&gt;</code> for choices</li>
            </ul>
        </div>
        
        <div class="two-column">
            <div class="converter-section">
                <h2>Convert HTML File</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="html_file">HTML File:</label>
                        <input type="file" name="html_file" id="html_file" accept=".html,.htm" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="story_title">Story Title:</label>
                        <input type="text" name="story_title" id="story_title" placeholder="Enter story title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="story_description">Story Description:</label>
                        <textarea name="story_description" id="story_description" rows="3" placeholder="Brief description of your story"></textarea>
                    </div>
                    
                    <button type="submit" name="action" value="convert_html">Convert HTML to JSON</button>
                </form>
            </div>
            
            <div class="converter-section">
                <h2>Story Requirements</h2>
                <p><strong>JSON Format Structure:</strong></p>
                <ul>
                    <li><strong>Nodes:</strong> Each story section/scene</li>
                    <li><strong>Choices:</strong> Player decision points</li>
                    <li><strong>Targets:</strong> Where choices lead</li>
                    <li><strong>Variables:</strong> Story state tracking</li>
                </ul>
                
                <p><strong>Tips for Better Conversion:</strong></p>
                <ul>
                    <li>Use semantic HTML structure</li>
                    <li>Give sections unique IDs</li>
                    <li>Use consistent link patterns</li>
                    <li>Keep content in paragraphs</li>
                </ul>
            </div>
        </div>
        
        <?php if ($convertedStory): ?>
            <div class="converter-section">
                <h2>Converted Story JSON</h2>
                <p>Review the converted story below. You can edit the JSON if needed, then save it.</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="story_id">Story ID (filename):</label>
                        <input type="text" name="story_id" id="story_id" value="<?= htmlspecialchars($convertedStory['id']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="story_json">Story JSON:</label>
                        <textarea name="story_json" id="story_json" rows="20" class="json-output"><?= htmlspecialchars(json_encode($convertedStory, JSON_PRETTY_PRINT)) ?></textarea>
                    </div>
                    
                    <button type="submit" name="action" value="save_story">Save Story</button>
                </form>
                
                <h3>Conversion Summary</h3>
                <ul>
                    <li><strong>Nodes Created:</strong> <?= count($convertedStory['nodes']) ?></li>
                    <li><strong>Has Start Node:</strong> <?= isset($convertedStory['nodes']['start']) ? 'Yes' : 'No' ?></li>
                    <li><strong>Total Choices:</strong> 
                        <?php 
                        $totalChoices = 0;
                        foreach ($convertedStory['nodes'] as $node) {
                            $totalChoices += count($node['choices']);
                        }
                        echo $totalChoices;
                        ?>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>