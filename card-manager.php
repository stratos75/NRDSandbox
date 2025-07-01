<?php
// ===================================================================
// NRD SANDBOX - ENHANCED CARD MANAGEMENT SYSTEM (JSON FILE STORAGE)
// ===================================================================

require 'auth.php';

// Enhanced CardManager class with robust JSON handling
class CardManager {
    private $dataFile = 'data/cards.json';
    
    public function __construct() {
        $this->ensureDataStructure();
    }
    
    /**
     * Ensure proper directory and file structure exists
     */
    private function ensureDataStructure() {
        // Create data directory if it doesn't exist
        if (!file_exists(dirname($this->dataFile))) {
            mkdir(dirname($this->dataFile), 0755, true);
        }
        
        // Create or validate the JSON file
        if (!file_exists($this->dataFile)) {
            $this->createEmptyCardsFile();
        } else {
            $this->validateAndFixCardsFile();
        }
    }
    
    /**
     * Create a properly structured empty cards file
     */
    private function createEmptyCardsFile() {
        $emptyStructure = [
            'cards' => [],
            'meta' => [
                'created' => date('Y-m-d H:i:s'),
                'version' => '1.1',
                'total_cards' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->writeCardsFile($emptyStructure);
    }
    
    /**
     * Validate and fix the cards file if it's corrupted
     */
    private function validateAndFixCardsFile() {
        $content = file_get_contents($this->dataFile);
        
        if (empty($content)) {
            $this->createEmptyCardsFile();
            return;
        }
        
        $data = json_decode($content, true);
        
        if ($data === null) {
            // JSON is corrupted, recreate
            $this->createEmptyCardsFile();
            return;
        }
        
        // Check if it's an array instead of object (the bug we're fixing)
        if (is_array($data) && isset($data[0])) {
            // It's a corrupted array format, convert to proper structure
            $fixedData = [
                'cards' => array_values($data), // Ensure it's a proper array
                'meta' => [
                    'created' => date('Y-m-d H:i:s'),
                    'version' => '1.1',
                    'total_cards' => count($data),
                    'last_updated' => date('Y-m-d H:i:s'),
                    'fixed_from_corruption' => true
                ]
            ];
            
            $this->writeCardsFile($fixedData);
            return;
        }
        
        // Check if it's missing the 'cards' key
        if (!isset($data['cards'])) {
            $this->createEmptyCardsFile();
            return;
        }
        
        // Structure looks good, ensure meta exists
        if (!isset($data['meta'])) {
            $data['meta'] = [
                'created' => date('Y-m-d H:i:s'),
                'version' => '1.1',
                'total_cards' => count($data['cards']),
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            $this->writeCardsFile($data);
        }
    }
    
    /**
     * Safely write cards data to file
     */
    private function writeCardsFile($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new Exception('Failed to encode JSON data');
        }
        
        if (file_put_contents($this->dataFile, $json) === false) {
            throw new Exception('Failed to write cards file');
        }
    }
    
    /**
     * Get all cards with enhanced error handling
     */
    public function getAllCards() {
        $this->validateAndFixCardsFile();
        
        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        
        if (!isset($data['cards']) || !is_array($data['cards'])) {
            return [];
        }
        
        return $data['cards'];
    }
    
    /**
     * Get full data structure (cards + meta)
     */
    public function getFullData() {
        $this->validateAndFixCardsFile();
        
        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        
        return $data ?: ['cards' => [], 'meta' => []];
    }
    
    /**
     * Save a new card
     */
    public function saveCard($cardData) {
        $data = $this->getFullData();
        
        // Generate unique ID
        $cardData['id'] = 'card_' . time() . '_' . rand(1000, 9999);
        $cardData['created_at'] = date('Y-m-d H:i:s');
        $cardData['created_by'] = $_SESSION['username'] ?? 'unknown';
        
        // Add to cards array
        $data['cards'][] = $cardData;
        
        // Update metadata
        $data['meta']['total_cards'] = count($data['cards']);
        $data['meta']['last_updated'] = date('Y-m-d H:i:s');
        
        $this->writeCardsFile($data);
        
        return $cardData;
    }
    
    /**
     * Update an existing card
     */
    public function updateCard($cardId, $cardData) {
        $data = $this->getFullData();
        
        foreach ($data['cards'] as $key => $card) {
            if ($card['id'] === $cardId) {
                $cardData['id'] = $cardId;
                $cardData['created_at'] = $card['created_at'] ?? date('Y-m-d H:i:s');
                $cardData['updated_at'] = date('Y-m-d H:i:s');
                $data['cards'][$key] = $cardData;
                
                // Update metadata
                $data['meta']['last_updated'] = date('Y-m-d H:i:s');
                
                $this->writeCardsFile($data);
                return $cardData;
            }
        }
        
        return false;
    }
    
    /**
     * Delete a card
     */
    public function deleteCard($cardId) {
        $data = $this->getFullData();
        
        foreach ($data['cards'] as $key => $card) {
            if ($card['id'] === $cardId) {
                unset($data['cards'][$key]);
                
                // Reindex array
                $data['cards'] = array_values($data['cards']);
                
                // Update metadata
                $data['meta']['total_cards'] = count($data['cards']);
                $data['meta']['last_updated'] = date('Y-m-d H:i:s');
                
                $this->writeCardsFile($data);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get system diagnostics
     */
    public function getDiagnostics() {
        return [
            'file_exists' => file_exists($this->dataFile),
            'file_readable' => is_readable($this->dataFile),
            'file_writable' => is_writable($this->dataFile),
            'file_size' => file_exists($this->dataFile) ? filesize($this->dataFile) : 0,
            'json_valid' => $this->isJsonValid(),
            'card_count' => count($this->getAllCards()),
            'structure_valid' => $this->isStructureValid()
        ];
    }
    
    /**
     * Check if JSON is valid
     */
    private function isJsonValid() {
        if (!file_exists($this->dataFile)) {
            return false;
        }
        
        $content = file_get_contents($this->dataFile);
        json_decode($content, true);
        
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Check if structure is valid
     */
    private function isStructureValid() {
        if (!file_exists($this->dataFile)) {
            return false;
        }
        
        $content = file_get_contents($this->dataFile);
        $data = json_decode($content, true);
        
        return isset($data['cards']) && is_array($data['cards']) && isset($data['meta']);
    }
}

// Helper function for card validation
function validate_card_data($cardData) {
    $errors = [];
    
    if (empty($cardData['name']) || !is_string($cardData['name'])) {
        $errors[] = 'Name is required and must be a string';
    }
    
    if (!is_numeric($cardData['cost']) || $cardData['cost'] < 0) {
        $errors[] = 'Cost must be a non-negative number';
    }
    
    $validTypes = ['spell', 'weapon', 'armor', 'creature', 'support'];
    if (!in_array($cardData['type'], $validTypes)) {
        $errors[] = 'Type must be one of: ' . implode(', ', $validTypes);
    }
    
    if (!is_numeric($cardData['damage']) || $cardData['damage'] < 0) {
        $errors[] = 'Damage must be a non-negative number';
    }
    
    $validRarities = ['common', 'uncommon', 'rare', 'legendary'];
    if (!in_array($cardData['rarity'], $validRarities)) {
        $errors[] = 'Rarity must be one of: ' . implode(', ', $validRarities);
    }
    
    return $errors;
}

// Set content type for JSON response
header('Content-Type: application/json');

// Initialize response structure
$response = ['success' => false, 'message' => '', 'data' => null];

// Handle direct browser access (GET request) - for debugging
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $cardManager = new CardManager();
        $diagnostics = $cardManager->getDiagnostics();
        
        $response['success'] = true;
        $response['message'] = 'Card Manager is working. Use POST with action parameter for API calls.';
        $response['diagnostics'] = $diagnostics;
        $response['debug'] = [
            'method' => 'GET',
            'timestamp' => date('Y-m-d H:i:s'),
            'available_actions' => ['get_all_cards', 'create_card', 'update_card', 'delete_card', 'diagnostics']
        ];
    } catch (Exception $e) {
        $response['message'] = 'CardManager initialization failed: ' . $e->getMessage();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Only handle POST requests for actual API calls
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Use POST.';
    echo json_encode($response);
    exit;
}

// Get the action from POST data
$action = $_POST['action'] ?? '';

if (empty($action)) {
    $response['message'] = 'No action specified';
    echo json_encode($response);
    exit;
}

// Initialize CardManager
try {
    $cardManager = new CardManager();
} catch (Exception $e) {
    $response['message'] = 'Failed to initialize CardManager: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// Process different actions
switch ($action) {
    case 'get_all_cards':
        try {
            $cards = $cardManager->getAllCards();
            $response['success'] = true;
            $response['data'] = $cards;
            $response['message'] = 'Cards loaded successfully';
            $response['count'] = count($cards);
        } catch (Exception $e) {
            $response['message'] = 'Error loading cards: ' . $e->getMessage();
        }
        break;
        
    case 'create_card':
        $cardData = [
            'name' => trim($_POST['name'] ?? ''),
            'cost' => intval($_POST['cost'] ?? 0),
            'type' => trim($_POST['type'] ?? 'spell'),
            'damage' => intval($_POST['damage'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'rarity' => trim($_POST['rarity'] ?? 'common')
        ];
        
        $errors = validate_card_data($cardData);
        if (empty($errors)) {
            try {
                $savedCard = $cardManager->saveCard($cardData);
                $response['success'] = true;
                $response['message'] = 'Card created successfully';
                $response['data'] = $savedCard;
            } catch (Exception $e) {
                $response['message'] = 'Error creating card: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Validation errors: ' . implode(', ', $errors);
            $response['errors'] = $errors;
        }
        break;
        
    case 'update_card':
        $cardId = $_POST['card_id'] ?? '';
        $cardData = [
            'name' => trim($_POST['name'] ?? ''),
            'cost' => intval($_POST['cost'] ?? 0),
            'type' => trim($_POST['type'] ?? 'spell'),
            'damage' => intval($_POST['damage'] ?? 0),
            'description' => trim($_POST['description'] ?? ''),
            'rarity' => trim($_POST['rarity'] ?? 'common')
        ];
        
        $errors = validate_card_data($cardData);
        if (empty($errors)) {
            try {
                $updatedCard = $cardManager->updateCard($cardId, $cardData);
                if ($updatedCard) {
                    $response['success'] = true;
                    $response['message'] = 'Card updated successfully';
                    $response['data'] = $updatedCard;
                } else {
                    $response['message'] = 'Card not found or failed to update';
                }
            } catch (Exception $e) {
                $response['message'] = 'Error updating card: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Validation errors: ' . implode(', ', $errors);
            $response['errors'] = $errors;
        }
        break;
        
    case 'delete_card':
        $cardId = $_POST['card_id'] ?? '';
        if ($cardId) {
            try {
                $deleted = $cardManager->deleteCard($cardId);
                if ($deleted) {
                    $response['success'] = true;
                    $response['message'] = 'Card deleted successfully!';
                } else {
                    $response['message'] = 'Card not found or could not be deleted';
                }
            } catch (Exception $e) {
                $response['message'] = 'Error deleting card: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Card ID is required';
        }
        break;
        
    case 'diagnostics':
        try {
            $diagnostics = $cardManager->getDiagnostics();
            $response['success'] = true;
            $response['message'] = 'Diagnostics retrieved successfully';
            $response['data'] = $diagnostics;
        } catch (Exception $e) {
            $response['message'] = 'Error getting diagnostics: ' . $e->getMessage();
        }
        break;
        
    default:
        $response['message'] = "Unknown action: {$action}";
        break;
}

// Return JSON response
echo json_encode($response);
exit;
?>