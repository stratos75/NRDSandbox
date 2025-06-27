<?php
// ===================================================================
// NRD SANDBOX - CARD MANAGEMENT SYSTEM (JSON FILE STORAGE)
// ===================================================================

class CardManager {
    private $cardsFile = 'data/cards.json';
    private $dataDir = 'data';
    
    public function __construct() {
        $this->ensureDataDirectory();
        $this->ensureCardsFile();
    }
    
    // ===================================================================
    // FILE SYSTEM SETUP
    // ===================================================================
    private function ensureDataDirectory() {
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    private function ensureCardsFile() {
        if (!file_exists($this->cardsFile)) {
            $initialData = [
                'cards' => [],
                'meta' => [
                    'created' => date('Y-m-d H:i:s'),
                    'version' => '1.0',
                    'total_cards' => 0
                ]
            ];
            $this->writeJsonFile($this->cardsFile, $initialData);
        }
    }
    
    // ===================================================================
    // JSON FILE OPERATIONS
    // ===================================================================
    private function readJsonFile($filename) {
        if (!file_exists($filename)) {
            return null;
        }
        
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }
    
    private function writeJsonFile($filename, $data) {
        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($filename, $jsonContent);
    }
    
    // ===================================================================
    // CARD CRUD OPERATIONS
    // ===================================================================
    public function saveCard($cardData) {
        $data = $this->readJsonFile($this->cardsFile);
        
        // Generate unique ID for the card
        $cardId = $this->generateCardId();
        
        // Prepare card with metadata
        $card = [
            'id' => $cardId,
            'name' => sanitize_input($cardData['name']),
            'cost' => intval($cardData['cost']),
            'type' => sanitize_input($cardData['type']),
            'damage' => intval($cardData['damage']),
            'description' => sanitize_input($cardData['description']),
            'rarity' => sanitize_input($cardData['rarity']),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'] ?? 'unknown'
        ];
        
        // Add card to collection
        $data['cards'][] = $card;
        $data['meta']['total_cards'] = count($data['cards']);
        $data['meta']['last_updated'] = date('Y-m-d H:i:s');
        
        // Save to file
        $result = $this->writeJsonFile($this->cardsFile, $data);
        
        return $result ? $card : false;
    }
    
    public function getAllCards() {
        $data = $this->readJsonFile($this->cardsFile);
        return $data ? $data['cards'] : [];
    }
    
    public function getCardById($cardId) {
        $cards = $this->getAllCards();
        foreach ($cards as $card) {
            if ($card['id'] === $cardId) {
                return $card;
            }
        }
        return null;
    }
    
    public function updateCard($cardId, $cardData) {
        $data = $this->readJsonFile($this->cardsFile);
        
        foreach ($data['cards'] as &$card) {
            if ($card['id'] === $cardId) {
                $card['name'] = sanitize_input($cardData['name']);
                $card['cost'] = intval($cardData['cost']);
                $card['type'] = sanitize_input($cardData['type']);
                $card['damage'] = intval($cardData['damage']);
                $card['description'] = sanitize_input($cardData['description']);
                $card['rarity'] = sanitize_input($cardData['rarity']);
                $card['updated_at'] = date('Y-m-d H:i:s');
                $card['updated_by'] = $_SESSION['username'] ?? 'unknown';
                
                $data['meta']['last_updated'] = date('Y-m-d H:i:s');
                $this->writeJsonFile($this->cardsFile, $data);
                return $card;
            }
        }
        
        return false;
    }
    
    public function deleteCard($cardId) {
        $data = $this->readJsonFile($this->cardsFile);
        
        foreach ($data['cards'] as $index => $card) {
            if ($card['id'] === $cardId) {
                array_splice($data['cards'], $index, 1);
                $data['meta']['total_cards'] = count($data['cards']);
                $data['meta']['last_updated'] = date('Y-m-d H:i:s');
                
                $this->writeJsonFile($this->cardsFile, $data);
                return true;
            }
        }
        
        return false;
    }
    
    // ===================================================================
    // UTILITY FUNCTIONS
    // ===================================================================
    private function generateCardId() {
        return 'card_' . uniqid() . '_' . time();
    }
    
    public function getCardsByType($type) {
        $cards = $this->getAllCards();
        return array_filter($cards, function($card) use ($type) {
            return $card['type'] === $type;
        });
    }
    
    public function getCardsByRarity($rarity) {
        $cards = $this->getAllCards();
        return array_filter($cards, function($card) use ($rarity) {
            return $card['rarity'] === $rarity;
        });
    }
    
    public function searchCards($searchTerm) {
        $cards = $this->getAllCards();
        $searchTerm = strtolower($searchTerm);
        
        return array_filter($cards, function($card) use ($searchTerm) {
            return strpos(strtolower($card['name']), $searchTerm) !== false ||
                   strpos(strtolower($card['description']), $searchTerm) !== false;
        });
    }
    
    public function getStats() {
        $data = $this->readJsonFile($this->cardsFile);
        $cards = $data['cards'] ?? [];
        
        $stats = [
            'total_cards' => count($cards),
            'by_type' => [],
            'by_rarity' => [],
            'average_cost' => 0,
            'average_damage' => 0
        ];
        
        if (empty($cards)) {
            return $stats;
        }
        
        $totalCost = 0;
        $totalDamage = 0;
        
        foreach ($cards as $card) {
            // Count by type
            $type = $card['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
            
            // Count by rarity
            $rarity = $card['rarity'];
            $stats['by_rarity'][$rarity] = ($stats['by_rarity'][$rarity] ?? 0) + 1;
            
            // Calculate averages
            $totalCost += $card['cost'];
            $totalDamage += $card['damage'];
        }
        
        $stats['average_cost'] = round($totalCost / count($cards), 1);
        $stats['average_damage'] = round($totalDamage / count($cards), 1);
        
        return $stats;
    }
}

// ===================================================================
// HELPER FUNCTIONS
// ===================================================================
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validate_card_data($cardData) {
    $errors = [];
    
    if (empty($cardData['name']) || strlen($cardData['name']) < 2) {
        $errors[] = 'Card name must be at least 2 characters long';
    }
    
    if (!is_numeric($cardData['cost']) || $cardData['cost'] < 0 || $cardData['cost'] > 20) {
        $errors[] = 'Cost must be a number between 0 and 20';
    }
    
    if (!in_array($cardData['type'], ['spell', 'weapon', 'armor', 'creature', 'support'])) {
        $errors[] = 'Invalid card type';
    }
    
    if (!is_numeric($cardData['damage']) || $cardData['damage'] < 0 || $cardData['damage'] > 100) {
        $errors[] = 'Damage must be a number between 0 and 100';
    }
    
    if (!in_array($cardData['rarity'], ['common', 'uncommon', 'rare', 'legendary'])) {
        $errors[] = 'Invalid rarity level';
    }
    
    return $errors;
}

// ===================================================================
// API ENDPOINT HANDLING
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once 'auth.php'; // Ensure user is authenticated
    
    $cardManager = new CardManager();
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    switch ($_POST['action']) {
        case 'save_card':
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
                $savedCard = $cardManager->saveCard($cardData);
                if ($savedCard) {
                    $response['success'] = true;
                    $response['message'] = 'Card saved successfully!';
                    $response['data'] = $savedCard;
                } else {
                    $response['message'] = 'Failed to save card to file';
                }
            } else {
                $response['message'] = implode(', ', $errors);
            }
            break;
            
        case 'get_all_cards':
            $cards = $cardManager->getAllCards();
            $response['success'] = true;
            $response['data'] = $cards;
            break;
            
        case 'get_stats':
            $stats = $cardManager->getStats();
            $response['success'] = true;
            $response['data'] = $stats;
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
            $response['message'] = 'Invalid action';
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>