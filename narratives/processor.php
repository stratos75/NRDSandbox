<?php
/**
 * Arrow Export Processor for NRDSandbox
 * Converts Arrow HTML exports into NRDSandbox-compatible narrative data
 */

require_once '../auth.php';
require_once '../database/Database.php';

class ArrowProcessor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Process an Arrow HTML export file
     * @param string $htmlFile Path to the Arrow HTML export
     * @return array Processing result
     */
    public function processArrowExport($htmlFile) {
        try {
            if (!file_exists($htmlFile)) {
                throw new Exception("Arrow export file not found: $htmlFile");
            }
            
            $html = file_get_contents($htmlFile);
            $storyData = $this->extractStoryData($html);
            
            // Generate unique story ID
            $storyId = $this->generateStoryId($storyData['title']);
            
            // Process story structure
            $processedStory = [
                'id' => $storyId,
                'title' => $storyData['title'],
                'description' => $storyData['description'] ?? '',
                'nodes' => $this->processNodes($storyData['nodes']),
                'variables' => $storyData['variables'] ?? [],
                'created_at' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ];
            
            // Save to database and file system
            $this->saveStory($processedStory);
            
            return [
                'success' => true,
                'story_id' => $storyId,
                'title' => $storyData['title'],
                'nodes_count' => count($processedStory['nodes']),
                'message' => 'Story processed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("ArrowProcessor error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract story data from Arrow HTML export
     * @param string $html HTML content
     * @return array Story data
     */
    private function extractStoryData($html) {
        // Look for embedded JSON data in Arrow exports
        $pattern = '/var\s+project\s*=\s*({.*?});/s';
        if (preg_match($pattern, $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                return $this->parseArrowProject($jsonData);
            }
        }
        
        // Fallback: Parse HTML structure
        return $this->parseHtmlStructure($html);
    }
    
    /**
     * Parse Arrow project JSON data
     * @param array $project Arrow project data
     * @return array Parsed story data
     */
    private function parseArrowProject($project) {
        $storyData = [
            'title' => $project['title'] ?? 'Untitled Story',
            'description' => $project['description'] ?? '',
            'nodes' => [],
            'variables' => $project['variables'] ?? []
        ];
        
        // Process nodes
        foreach ($project['nodes'] ?? [] as $nodeId => $node) {
            $storyData['nodes'][$nodeId] = [
                'id' => $nodeId,
                'type' => $node['type'] ?? 'text',
                'title' => $node['title'] ?? '',
                'content' => $node['content'] ?? '',
                'choices' => $this->extractChoices($node),
                'conditions' => $node['conditions'] ?? [],
                'actions' => $this->extractActions($node),
                'position' => $node['position'] ?? ['x' => 0, 'y' => 0]
            ];
        }
        
        return $storyData;
    }
    
    /**
     * Extract choices from Arrow node
     * @param array $node Arrow node data
     * @return array Choices
     */
    private function extractChoices($node) {
        $choices = [];
        
        if (isset($node['choices'])) {
            foreach ($node['choices'] as $choice) {
                $choices[] = [
                    'text' => $choice['text'] ?? '',
                    'target' => $choice['target'] ?? '',
                    'conditions' => $choice['conditions'] ?? [],
                    'actions' => $choice['actions'] ?? []
                ];
            }
        }
        
        return $choices;
    }
    
    /**
     * Extract actions from Arrow node (for NRDSandbox integration)
     * @param array $node Arrow node data
     * @return array Actions
     */
    private function extractActions($node) {
        $actions = [];
        
        // Look for NRDSandbox-specific actions
        if (isset($node['actions'])) {
            foreach ($node['actions'] as $action) {
                $actionType = $action['type'] ?? '';
                
                switch ($actionType) {
                    case 'give_card':
                        $actions[] = [
                            'type' => 'reward_card',
                            'card_id' => $action['card_id'] ?? '',
                            'rarity' => $action['rarity'] ?? 'common'
                        ];
                        break;
                        
                    case 'set_equipment':
                        $actions[] = [
                            'type' => 'force_equipment',
                            'slot' => $action['slot'] ?? 'weapon',
                            'card_id' => $action['card_id'] ?? ''
                        ];
                        break;
                        
                    case 'audio_trigger':
                        $actions[] = [
                            'type' => 'narrative_audio',
                            'event' => $action['event'] ?? 'custom',
                            'text' => $action['text'] ?? ''
                        ];
                        break;
                        
                    case 'set_variable':
                        $actions[] = [
                            'type' => 'set_variable',
                            'variable' => $action['variable'] ?? '',
                            'value' => $action['value'] ?? ''
                        ];
                        break;
                }
            }
        }
        
        return $actions;
    }
    
    /**
     * Parse HTML structure as fallback
     * @param string $html HTML content
     * @return array Basic story data
     */
    private function parseHtmlStructure($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $title = '';
        $titleNodes = $dom->getElementsByTagName('title');
        if ($titleNodes->length > 0) {
            $title = $titleNodes->item(0)->textContent;
        }
        
        return [
            'title' => $title ?: 'Imported Story',
            'description' => 'Story imported from Arrow HTML export',
            'nodes' => [
                'start' => [
                    'id' => 'start',
                    'type' => 'text',
                    'title' => 'Start',
                    'content' => 'This story was imported from an Arrow HTML export.',
                    'choices' => [],
                    'conditions' => [],
                    'actions' => []
                ]
            ],
            'variables' => []
        ];
    }
    
    /**
     * Process nodes for NRDSandbox integration
     * @param array $nodes Raw nodes
     * @return array Processed nodes
     */
    private function processNodes($nodes) {
        $processed = [];
        
        foreach ($nodes as $nodeId => $node) {
            $processed[$nodeId] = [
                'id' => $nodeId,
                'type' => $node['type'] ?? 'text',
                'title' => $node['title'] ?? '',
                'content' => $this->processContent($node['content'] ?? ''),
                'choices' => $this->processChoices($node['choices'] ?? []),
                'conditions' => $node['conditions'] ?? [],
                'actions' => $node['actions'] ?? [],
                'nrd_actions' => $this->generateNRDActions($node)
            ];
        }
        
        return $processed;
    }
    
    /**
     * Process content for NRDSandbox display
     * @param string $content Raw content
     * @return string Processed content
     */
    private function processContent($content) {
        // Clean up content for display
        $content = strip_tags($content, '<em><strong><br><p>');
        $content = html_entity_decode($content);
        
        // Convert Arrow variables to NRDSandbox format
        $content = preg_replace('/\{(\w+)\}/', '{{$1}}', $content);
        
        return $content;
    }
    
    /**
     * Process choices for NRDSandbox integration
     * @param array $choices Raw choices
     * @return array Processed choices
     */
    private function processChoices($choices) {
        $processed = [];
        
        foreach ($choices as $choice) {
            $processed[] = [
                'text' => $choice['text'] ?? '',
                'target' => $choice['target'] ?? '',
                'conditions' => $choice['conditions'] ?? [],
                'actions' => $choice['actions'] ?? [],
                'nrd_effects' => $this->generateChoiceEffects($choice)
            ];
        }
        
        return $processed;
    }
    
    /**
     * Generate NRDSandbox-specific actions
     * @param array $node Node data
     * @return array NRD actions
     */
    private function generateNRDActions($node) {
        $actions = [];
        
        // Auto-generate audio triggers based on content
        if (!empty($node['content'])) {
            $actions[] = [
                'type' => 'narrative_trigger',
                'event' => 'story_node',
                'text' => $node['content']
            ];
        }
        
        return $actions;
    }
    
    /**
     * Generate choice effects for NRDSandbox
     * @param array $choice Choice data
     * @return array Choice effects
     */
    private function generateChoiceEffects($choice) {
        $effects = [];
        
        // Check for card reward keywords
        if (stripos($choice['text'], 'weapon') !== false) {
            $effects[] = ['type' => 'bias_card_type', 'card_type' => 'weapon'];
        }
        if (stripos($choice['text'], 'armor') !== false) {
            $effects[] = ['type' => 'bias_card_type', 'card_type' => 'armor'];
        }
        
        return $effects;
    }
    
    /**
     * Generate unique story ID
     * @param string $title Story title
     * @return string Unique ID
     */
    private function generateStoryId($title) {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($title));
        $base = preg_replace('/_+/', '_', $base);
        $base = trim($base, '_');
        
        if (empty($base)) {
            $base = 'story';
        }
        
        return $base . '_' . time();
    }
    
    /**
     * Save story to database and file system
     * @param array $story Story data
     */
    private function saveStory($story) {
        // Save to JSON file
        $jsonFile = __DIR__ . '/data/' . $story['id'] . '.json';
        file_put_contents($jsonFile, json_encode($story, JSON_PRETTY_PRINT));
        
        // Save metadata to database (if story_metadata table exists)
        try {
            $sql = "INSERT INTO story_metadata (story_id, title, description, nodes_count, created_at, version) VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->execute($sql, [
                $story['id'],
                $story['title'],
                $story['description'],
                count($story['nodes']),
                $story['created_at'],
                $story['version']
            ]);
        } catch (Exception $e) {
            // Table doesn't exist yet, just log
            error_log("Story metadata save failed (table may not exist): " . $e->getMessage());
        }
    }
}

// CLI usage for testing
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Usage: php processor.php <arrow_export.html>\n";
        exit(1);
    }
    
    $processor = new ArrowProcessor();
    $result = $processor->processArrowExport($argv[1]);
    
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
}
?>