<?php
/**
 * Story API Endpoint for NRDSandbox Arrow Integration
 * Handles AJAX requests for story loading, choices, and progress
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../auth.php';
require_once 'StoryManager.php';

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are supported. Use POST with JSON data containing an "action" field.');
    }
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    if (empty($rawInput)) {
        throw new Exception('No request body provided. Send JSON data with an "action" field.');
    }
    
    $input = json_decode($rawInput, true);
    
    if ($input === null) {
        throw new Exception('Invalid JSON format. Ensure request body is valid JSON.');
    }
    
    if (!isset($input['action'])) {
        throw new Exception('Missing "action" field in request. Available actions: get_stories, load_story, make_choice, get_progress, reset_progress, get_story_effects');
    }
    
    $action = $input['action'];
    $storyManager = new StoryManager();
    
    switch ($action) {
        case 'get_stories':
            $stories = $storyManager->getAvailableStories();
            echo json_encode([
                'success' => true,
                'stories' => $stories
            ]);
            break;
            
        case 'load_story':
            $storyId = $input['story_id'] ?? '';
            if (empty($storyId)) {
                throw new Exception('Story ID required');
            }
            
            $success = $storyManager->loadStory($storyId);
            if (!$success) {
                throw new Exception('Failed to load story');
            }
            
            $currentNode = $storyManager->getCurrentNode();
            echo json_encode([
                'success' => true,
                'story' => [
                    'id' => $storyId,
                    'title' => $currentNode['title'] ?? 'Story'
                ],
                'current_node' => $currentNode
            ]);
            break;
            
        case 'make_choice':
            $choiceIndex = $input['choice_index'] ?? -1;
            if ($choiceIndex < 0) {
                throw new Exception('Invalid choice index');
            }
            
            $result = $storyManager->makeChoice($choiceIndex);
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'node_data' => $result['node_data'],
                'effects' => $result['effects']
            ]);
            break;
            
        case 'get_progress':
            $storyId = $input['story_id'] ?? '';
            if (empty($storyId)) {
                throw new Exception('Story ID required');
            }
            
            $success = $storyManager->loadStory($storyId);
            if (!$success) {
                throw new Exception('Failed to load story');
            }
            
            $currentNode = $storyManager->getCurrentNode();
            echo json_encode([
                'success' => true,
                'current_node' => $currentNode
            ]);
            break;
            
        case 'reset_progress':
            $storyId = $input['story_id'] ?? '';
            if (empty($storyId)) {
                throw new Exception('Story ID required');
            }
            
            $storyManager->resetProgress($storyId);
            echo json_encode([
                'success' => true,
                'message' => 'Progress reset successfully'
            ]);
            break;
            
        case 'process_arrow_export':
            $exportFile = $input['export_file'] ?? '';
            if (empty($exportFile)) {
                throw new Exception('Export file required');
            }
            
            $exportPath = __DIR__ . '/exports/' . basename($exportFile);
            if (!file_exists($exportPath)) {
                throw new Exception('Export file not found');
            }
            
            require_once 'processor.php';
            $processor = new ArrowProcessor();
            $result = $processor->processArrowExport($exportPath);
            
            echo json_encode($result);
            break;
            
        case 'get_story_effects':
            // Get current story effects for game integration
            $effects = [
                'rewards' => $_SESSION['story_rewards'] ?? [],
                'narrative' => $_SESSION['story_narrative'] ?? [],
                'stat_mods' => $_SESSION['story_stat_mods'] ?? [],
                'unlocks' => $_SESSION['story_unlocks'] ?? [],
                'bias' => $_SESSION['story_bias'] ?? [],
                'equipment' => $_SESSION['story_equipment'] ?? []
            ];
            
            echo json_encode([
                'success' => true,
                'effects' => $effects
            ]);
            break;
            
        case 'clear_story_effects':
            // Clear story effects after they've been applied
            unset($_SESSION['story_rewards']);
            unset($_SESSION['story_narrative']);
            unset($_SESSION['story_stat_mods']);
            unset($_SESSION['story_unlocks']);
            unset($_SESSION['story_bias']);
            unset($_SESSION['story_equipment']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Story effects cleared'
            ]);
            break;
            
        case 'save_story_variable':
            $variable = $input['variable'] ?? '';
            $value = $input['value'] ?? '';
            
            if (empty($variable)) {
                throw new Exception('Variable name required');
            }
            
            if (!isset($_SESSION['story_variables'])) {
                $_SESSION['story_variables'] = [];
            }
            
            $_SESSION['story_variables'][$variable] = $value;
            
            echo json_encode([
                'success' => true,
                'message' => 'Variable saved'
            ]);
            break;
            
        case 'get_story_variables':
            $variables = $_SESSION['story_variables'] ?? [];
            
            echo json_encode([
                'success' => true,
                'variables' => $variables
            ]);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>