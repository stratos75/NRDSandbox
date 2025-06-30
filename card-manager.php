<?php
// ===================================================================
// NRD SANDBOX - CARD MANAGEMENT SYSTEM (JSON FILE STORAGE)
// ===================================================================

require 'auth.php';

// CardManager class definition
class CardManager {
    private $dataFile = 'data/cards.json';
    
    public function __construct() {
        if (!file_exists(dirname($this->dataFile))) {
            mkdir(dirname($this->dataFile), 0755, true);
        }
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, json_encode([]));
        }
    }
    
    public function getAllCards() {
        $cards = json_decode(file_get_contents($this->dataFile), true) ?: [];
        return $cards;
    }
    
    public function saveCard($cardData) {
        $cards = $this->getAllCards();
        $cardData['id'] = uniqid('card_');
        $cardData['created_at'] = date('Y-m-d H:i:s');
        $cards[] = $cardData;
        if (file_put_contents($this->dataFile, json_encode($cards))) {
            return $cardData;
        }
        return false;
    }
    
    public function updateCard($cardId, $cardData) {
        $cards = $this->getAllCards();
        foreach ($cards as $key => $card) {
            if ($card['id'] === $cardId) {
                $cardData['id'] = $cardId;
                $cardData['created_at'] = $card['created_at'] ?? date('Y-m-d H:i:s');
                $cardData['updated_at'] = date('Y-m-d H:i:s');
                $cards[$key] = $cardData;
                file_put_contents($this->dataFile, json_encode($cards));
                return $cardData;
            }
        }
        return false;
    }
    
    public function deleteCard($cardId) {
        $cards = $this->getAllCards();
        foreach ($cards as $key => $card) {
            if ($card['id'] === $cardId) {
                unset($cards[$key]);
                file_put_contents($this->dataFile, json_encode(array_values($cards)));
                return true;
            }
        }
        return false;
    }
}

// Helper function for card validation
function validate_card_data($cardData) {
    $errors = [];
    if (empty($cardData['name'])) {
        $errors[] = 'Name is required';
    }
    if (!is_numeric($cardData['cost'])) {
        $errors[] = 'Cost must be a number';
    }
    return $errors;
}

// Set content type for JSON response
header('Content-Type: application/json');

// Initialize response structure
$response = ['success' => false, 'message' => '', 'data' => null];

// Handle direct browser access (GET request) - for debugging
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $response['message'] = 'Card Manager is working. Use POST with action parameter for API calls.';
    $response['success'] = true;
    $response['debug'] = [
        'method' => 'GET',
        'timestamp' => date('Y-m-d H:i:s'),
        'cards_file_exists' => file_exists('data/cards.json'),
        'available_actions' => ['get_all_cards', 'create_card', 'update_card', 'delete_card']
    ];
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
            'id' => 'card_' . time() . '_' . rand(1000, 9999),
            'name' => $_POST['name'] ?? '',
            'cost' => intval($_POST['cost'] ?? 0),
            'type' => $_POST['type'] ?? '',
            'damage' => intval($_POST['damage'] ?? 0),
            'description' => $_POST['description'] ?? '',
            'rarity' => $_POST['rarity'] ?? 'common',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'] ?? 'unknown'
        ];
        
        if (validate_card_data($cardData)) {
            try {
                $success = $cardManager->saveCard($cardData);
                if ($success) {
                    $response['success'] = true;
                    $response['message'] = 'Card created successfully';
                    $response['data'] = $cardData;
                } else {
                    $response['message'] = 'Failed to save card';
                }
            } catch (Exception $e) {
                $response['message'] = 'Error creating card: ' . $e->getMessage();
            }
        } else {
            $response['message'] = 'Invalid card data';
        }
        break;
        
    case 'update_card':
        $cardId = $_POST['card_id'] ?? '';
        $cardData = [
            'name' => $_POST['name'] ?? '',
            'cost' => $_POST['cost'] ?? 0,
            'type' => $_POST['type'] ?? 'spell',
            'damage' => $_POST['damage'] ?? 0,
            'description' => $_POST['description'] ?? '',
            'rarity' => $_POST['rarity'] ?? 'common'
        ];
        
        $errors = validate_card_data($cardData);
        if (empty($errors)) {
            $updatedCard = $cardManager->updateCard($cardId, $cardData);
            if ($updatedCard) {
                $response['success'] = true;
                $response['message'] = 'Card updated successfully';
                $response['data'] = $updatedCard;
            } else {
                $response['message'] = 'Card not found or failed to update';
            }
        } else {
            $response['message'] = implode(', ', $errors);
        }
        break;
        
    case 'delete_card':
        $cardId = $_POST['card_id'] ?? '';
        if ($cardId) {
            $deleted = $cardManager->deleteCard($cardId);
            if ($deleted) {
                $response['success'] = true;
                $response['message'] = 'Card deleted successfully!';
            } else {
                $response['message'] = 'Card not found or could not be deleted';
            }
        } else {
            $response['message'] = 'Card ID is required';
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