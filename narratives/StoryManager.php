<?php
/**
 * Story Manager for NRDSandbox Arrow Integration
 * Handles story playback, choice tracking, and game integration
 */

require_once '../database/Database.php';

class StoryManager {
    private $db;
    private $currentStory;
    private $currentNode;
    private $storyVariables;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->storyVariables = [];
    }
    
    /**
     * Load a story by ID
     * @param string $storyId Story identifier
     * @return bool Success status
     */
    public function loadStory($storyId) {
        try {
            $jsonFile = __DIR__ . '/data/' . $storyId . '.json';
            
            if (!file_exists($jsonFile)) {
                throw new Exception("Story file not found: $storyId");
            }
            
            $storyData = json_decode(file_get_contents($jsonFile), true);
            if (!$storyData) {
                throw new Exception("Invalid story data: $storyId");
            }
            
            $this->currentStory = $storyData;
            $this->currentNode = 'start';
            $this->storyVariables = $storyData['variables'] ?? [];
            
            // Load user progress if exists
            $this->loadUserProgress();
            
            return true;
            
        } catch (Exception $e) {
            error_log("StoryManager::loadStory error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current story node
     * @return array|null Current node data
     */
    public function getCurrentNode() {
        if (!$this->currentStory || !$this->currentNode) {
            return null;
        }
        
        if (!isset($this->currentStory['nodes'][$this->currentNode])) {
            return null;
        }
        
        $node = $this->currentStory['nodes'][$this->currentNode];
        
        // Process variables in content
        $node['content'] = $this->processVariables($node['content']);
        
        // Filter choices based on conditions
        $node['choices'] = $this->filterChoices($node['choices']);
        
        return $node;
    }
    
    /**
     * Make a story choice
     * @param int $choiceIndex Choice index
     * @return array Result data
     */
    public function makeChoice($choiceIndex) {
        try {
            $currentNode = $this->getCurrentNode();
            if (!$currentNode) {
                throw new Exception("No current node available");
            }
            
            if (!isset($currentNode['choices'][$choiceIndex])) {
                throw new Exception("Invalid choice index: $choiceIndex");
            }
            
            $choice = $currentNode['choices'][$choiceIndex];
            
            // Execute choice actions
            $this->executeActions($choice['actions'] ?? []);
            
            // Execute NRD-specific effects
            $this->executeNRDEffects($choice['nrd_effects'] ?? []);
            
            // Move to target node
            $this->currentNode = $choice['target'];
            
            // Save progress
            $this->saveUserProgress();
            
            // Log choice for analytics
            $this->logChoice($currentNode['id'], $choiceIndex, $choice['text']);
            
            return [
                'success' => true,
                'current_node' => $this->currentNode,
                'node_data' => $this->getCurrentNode(),
                'effects' => $this->getLastEffects()
            ];
            
        } catch (Exception $e) {
            error_log("StoryManager::makeChoice error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Execute story actions
     * @param array $actions Actions to execute
     */
    private function executeActions($actions) {
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'set_variable':
                    $this->storyVariables[$action['variable']] = $action['value'];
                    break;
                    
                case 'reward_card':
                    $this->rewardCard($action['card_id'], $action['rarity'] ?? 'common');
                    break;
                    
                case 'force_equipment':
                    $this->forceEquipment($action['slot'], $action['card_id']);
                    break;
                    
                case 'narrative_audio':
                    $this->triggerNarrative($action['event'], $action['text'] ?? '');
                    break;
            }
        }
    }
    
    /**
     * Execute NRDSandbox-specific effects
     * @param array $effects Effects to execute
     */
    private function executeNRDEffects($effects) {
        foreach ($effects as $effect) {
            switch ($effect['type']) {
                case 'bias_card_type':
                    $this->biasCardDraw($effect['card_type']);
                    break;
                    
                case 'modify_stats':
                    $this->modifyMechStats($effect['stat'], $effect['value']);
                    break;
                    
                case 'unlock_feature':
                    $this->unlockFeature($effect['feature']);
                    break;
            }
        }
    }
    
    /**
     * Process variables in text content
     * @param string $content Content with variables
     * @return string Processed content
     */
    private function processVariables($content) {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) {
            $variable = $matches[1];
            return $this->storyVariables[$variable] ?? $matches[0];
        }, $content);
    }
    
    /**
     * Filter choices based on conditions
     * @param array $choices Raw choices
     * @return array Filtered choices
     */
    private function filterChoices($choices) {
        $filtered = [];
        
        foreach ($choices as $choice) {
            if ($this->checkConditions($choice['conditions'] ?? [])) {
                $filtered[] = $choice;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if conditions are met
     * @param array $conditions Conditions to check
     * @return bool Conditions met
     */
    private function checkConditions($conditions) {
        foreach ($conditions as $condition) {
            $variable = $condition['variable'] ?? '';
            $operator = $condition['operator'] ?? '==';
            $value = $condition['value'] ?? '';
            
            $currentValue = $this->storyVariables[$variable] ?? '';
            
            switch ($operator) {
                case '==':
                    if ($currentValue != $value) return false;
                    break;
                case '!=':
                    if ($currentValue == $value) return false;
                    break;
                case '>':
                    if ((float)$currentValue <= (float)$value) return false;
                    break;
                case '<':
                    if ((float)$currentValue >= (float)$value) return false;
                    break;
                case 'isset':
                    if (!isset($this->storyVariables[$variable])) return false;
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Reward a card to the player
     * @param string $cardId Card identifier
     * @param string $rarity Card rarity
     */
    private function rewardCard($cardId, $rarity = 'common') {
        if (!isset($_SESSION['story_rewards'])) {
            $_SESSION['story_rewards'] = [];
        }
        
        $_SESSION['story_rewards'][] = [
            'type' => 'card',
            'card_id' => $cardId,
            'rarity' => $rarity,
            'timestamp' => time()
        ];
        
        // Add to player hand immediately if in game
        if (isset($_SESSION['playerHand']) && is_array($_SESSION['playerHand'])) {
            // Load card data from database
            require_once '../database/CardManager.php';
            $cardManager = new CardManager();
            $card = $cardManager->getCardById($cardId);
            
            if ($card) {
                $_SESSION['playerHand'][] = $card;
            }
        }
    }
    
    /**
     * Force equipment of a specific card
     * @param string $slot Equipment slot
     * @param string $cardId Card identifier
     */
    private function forceEquipment($slot, $cardId) {
        if (!isset($_SESSION['story_equipment'])) {
            $_SESSION['story_equipment'] = [];
        }
        
        $_SESSION['story_equipment'][$slot] = $cardId;
        
        // Apply immediately if in game
        if (isset($_SESSION['playerEquipment'])) {
            // Load card data and equip
            require_once '../database/CardManager.php';
            $cardManager = new CardManager();
            $card = $cardManager->getCardById($cardId);
            
            if ($card && ($card['type'] === 'weapon' || $card['type'] === 'armor')) {
                $_SESSION['playerEquipment'][$slot] = $card;
            }
        }
    }
    
    /**
     * Trigger narrative audio
     * @param string $event Event name
     * @param string $text Custom text
     */
    private function triggerNarrative($event, $text = '') {
        if (!isset($_SESSION['story_narrative'])) {
            $_SESSION['story_narrative'] = [];
        }
        
        $_SESSION['story_narrative'][] = [
            'event' => $event,
            'text' => $text,
            'timestamp' => time()
        ];
    }
    
    /**
     * Bias card drawing toward specific type
     * @param string $cardType Card type to bias toward
     */
    private function biasCardDraw($cardType) {
        if (!isset($_SESSION['story_bias'])) {
            $_SESSION['story_bias'] = [];
        }
        
        $_SESSION['story_bias']['card_type'] = $cardType;
        $_SESSION['story_bias']['strength'] = 0.3; // 30% bias
    }
    
    /**
     * Modify mech stats
     * @param string $stat Stat name
     * @param int $value Value to add
     */
    private function modifyMechStats($stat, $value) {
        if (!isset($_SESSION['story_stat_mods'])) {
            $_SESSION['story_stat_mods'] = [];
        }
        
        $_SESSION['story_stat_mods'][$stat] = ($_SESSION['story_stat_mods'][$stat] ?? 0) + $value;
    }
    
    /**
     * Unlock a feature
     * @param string $feature Feature name
     */
    private function unlockFeature($feature) {
        if (!isset($_SESSION['story_unlocks'])) {
            $_SESSION['story_unlocks'] = [];
        }
        
        $_SESSION['story_unlocks'][] = $feature;
    }
    
    /**
     * Load user progress from database
     */
    private function loadUserProgress() {
        if (!isset($_SESSION['user_id']) || !$this->currentStory) {
            return;
        }
        
        try {
            $sql = "SELECT current_node, story_variables FROM story_progress WHERE user_id = ? AND story_id = ?";
            $result = $this->db->fetchOne($sql, [$_SESSION['user_id'], $this->currentStory['id']]);
            
            if ($result) {
                $this->currentNode = $result['current_node'] ?? 'start';
                $savedVariables = json_decode($result['story_variables'] ?? '{}', true);
                $this->storyVariables = array_merge($this->storyVariables, $savedVariables);
            }
        } catch (Exception $e) {
            // Table doesn't exist yet, continue without progress
            error_log("Load user progress failed: " . $e->getMessage());
        }
    }
    
    /**
     * Save user progress to database
     */
    private function saveUserProgress() {
        if (!isset($_SESSION['user_id']) || !$this->currentStory) {
            return;
        }
        
        try {
            $sql = "INSERT INTO story_progress (user_id, story_id, current_node, story_variables, last_updated) 
                    VALUES (?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    current_node = VALUES(current_node),
                    story_variables = VALUES(story_variables),
                    last_updated = VALUES(last_updated)";
            
            $this->db->execute($sql, [
                $_SESSION['user_id'],
                $this->currentStory['id'],
                $this->currentNode,
                json_encode($this->storyVariables)
            ]);
        } catch (Exception $e) {
            // Table doesn't exist yet, continue without saving
            error_log("Save user progress failed: " . $e->getMessage());
        }
    }
    
    /**
     * Log choice for analytics
     * @param string $nodeId Node ID
     * @param int $choiceIndex Choice index
     * @param string $choiceText Choice text
     */
    private function logChoice($nodeId, $choiceIndex, $choiceText) {
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        try {
            $sql = "INSERT INTO story_choices (user_id, story_id, node_id, choice_index, choice_text, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $this->db->execute($sql, [
                $_SESSION['user_id'],
                $this->currentStory['id'],
                $nodeId,
                $choiceIndex,
                $choiceText
            ]);
        } catch (Exception $e) {
            // Table doesn't exist yet, continue without logging
            error_log("Log choice failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get last effects for UI display
     * @return array Last effects
     */
    private function getLastEffects() {
        $effects = [];
        
        if (isset($_SESSION['story_rewards'])) {
            $effects['rewards'] = $_SESSION['story_rewards'];
        }
        
        if (isset($_SESSION['story_narrative'])) {
            $effects['narrative'] = $_SESSION['story_narrative'];
        }
        
        if (isset($_SESSION['story_stat_mods'])) {
            $effects['stat_mods'] = $_SESSION['story_stat_mods'];
        }
        
        return $effects;
    }
    
    /**
     * Get available stories
     * @return array Available stories
     */
    public function getAvailableStories() {
        $stories = [];
        $dataDir = __DIR__ . '/data/';
        
        if (is_dir($dataDir)) {
            $files = glob($dataDir . '*.json');
            foreach ($files as $file) {
                $storyData = json_decode(file_get_contents($file), true);
                if ($storyData) {
                    $stories[] = [
                        'id' => $storyData['id'],
                        'title' => $storyData['title'],
                        'description' => $storyData['description'],
                        'nodes_count' => count($storyData['nodes']),
                        'version' => $storyData['version'] ?? '1.0.0'
                    ];
                }
            }
        }
        
        return $stories;
    }
    
    /**
     * Reset story progress
     * @param string $storyId Story ID
     */
    public function resetProgress($storyId) {
        if (isset($_SESSION['user_id'])) {
            try {
                $sql = "DELETE FROM story_progress WHERE user_id = ? AND story_id = ?";
                $this->db->execute($sql, [$_SESSION['user_id'], $storyId]);
            } catch (Exception $e) {
                error_log("Reset progress failed: " . $e->getMessage());
            }
        }
        
        // Clear session story data
        unset($_SESSION['story_rewards']);
        unset($_SESSION['story_narrative']);
        unset($_SESSION['story_stat_mods']);
        unset($_SESSION['story_unlocks']);
        unset($_SESSION['story_bias']);
        unset($_SESSION['story_equipment']);
    }
}
?>