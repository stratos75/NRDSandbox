<?php
// ===================================================================
// NRD SANDBOX - DATABASE-DRIVEN CARD MANAGEMENT SYSTEM
// ===================================================================

require_once 'Database.php';

/**
 * CardManager - Handles all database operations for cards, rarities, and deck building
 */
class CardManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all cards with their rarity information
     * @return array Array of cards with rarity data
     */
    public function getAllCards() {
        try {
            $sql = "
                SELECT c.*, cr.rarity_name, cr.rarity_weight, cr.power_multiplier, 
                       cr.color_hex, cq.quantity_available, cq.base_drop_rate
                FROM cards c 
                JOIN card_rarities cr ON c.rarity_id = cr.id
                LEFT JOIN card_quantities cq ON c.id = cq.card_id
                WHERE c.is_active = TRUE AND c.is_collectible = TRUE
                ORDER BY cr.display_order ASC, c.name ASC
            ";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("CardManager::getAllCards error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get cards filtered by rarity weight for weighted random selection
     * @return array Cards organized by rarity for weighted selection
     */
    public function getCardsForWeightedSelection() {
        try {
            $sql = "
                SELECT c.*, cr.rarity_name, cr.rarity_weight, cr.power_multiplier, 
                       cr.color_hex, cq.quantity_available, cq.base_drop_rate
                FROM cards c 
                JOIN card_rarities cr ON c.rarity_id = cr.id
                LEFT JOIN card_quantities cq ON c.id = cq.card_id
                WHERE c.is_active = TRUE AND c.is_collectible = TRUE 
                    AND (cq.quantity_available IS NULL OR cq.quantity_available > 0)
                ORDER BY cr.rarity_weight DESC
            ";
            $cards = $this->db->fetchAll($sql);
            
            // Group cards by rarity for weighted selection
            $cardsByRarity = [];
            foreach ($cards as $card) {
                $rarity = $card['rarity_name'];
                if (!isset($cardsByRarity[$rarity])) {
                    $cardsByRarity[$rarity] = [
                        'weight' => floatval($card['rarity_weight']),
                        'cards' => []
                    ];
                }
                $cardsByRarity[$rarity]['cards'][] = $card;
            }
            
            return $cardsByRarity;
        } catch (Exception $e) {
            error_log("CardManager::getCardsForWeightedSelection error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Deal a balanced starting hand using rarity-weighted distribution
     * @param int $handSize Number of cards to deal
     * @param bool $guaranteeTypes Ensure at least one weapon, armor, special attack
     * @return array Array of dealt cards
     */
    public function dealBalancedStartingHand($handSize = 5, $guaranteeTypes = true) {
        $cardsByRarity = $this->getCardsForWeightedSelection();
        
        if (empty($cardsByRarity)) {
            return [];
        }
        
        $hand = [];
        $usedCardIds = [];
        
        // If guaranteeing types, deal one of each equipment type first
        if ($guaranteeTypes) {
            $requiredTypes = ['weapon', 'armor', 'special attack'];
            
            foreach ($requiredTypes as $type) {
                if ($handSize <= 0) break;
                
                $card = $this->drawCardByType($type, $usedCardIds, $cardsByRarity);
                if ($card) {
                    $hand[] = $card;
                    $usedCardIds[] = $card['id'];
                    $handSize--;
                }
            }
        }
        
        // Fill remaining slots with weighted random selection
        while ($handSize > 0 && count($usedCardIds) < $this->getTotalAvailableCards()) {
            $card = $this->drawWeightedRandomCard($usedCardIds, $cardsByRarity);
            if ($card) {
                $hand[] = $card;
                $usedCardIds[] = $card['id'];
                $handSize--;
            } else {
                break; // No more cards available
            }
        }
        
        return $hand;
    }
    
    /**
     * Draw additional cards using rarity weighting
     * @param array $currentHand Current player hand
     * @param int $cardsToDraw Number of cards to draw
     * @return array Array of drawn cards
     */
    public function drawBalancedCards($currentHand, $cardsToDraw) {
        $cardsByRarity = $this->getCardsForWeightedSelection();
        
        if (empty($cardsByRarity)) {
            return [];
        }
        
        $currentCardIds = array_column($currentHand, 'id');
        $drawnCards = [];
        
        while ($cardsToDraw > 0) {
            $card = $this->drawWeightedRandomCard($currentCardIds, $cardsByRarity);
            if ($card) {
                $drawnCards[] = $card;
                $currentCardIds[] = $card['id']; // Prevent duplicates in same draw
                $cardsToDraw--;
            } else {
                break; // No more unique cards available
            }
        }
        
        return $drawnCards;
    }
    
    /**
     * Draw a random card using rarity weights
     * @param array $excludeIds Cards to exclude from selection
     * @param array $cardsByRarity Cards organized by rarity
     * @return array|null Selected card or null if none available
     */
    private function drawWeightedRandomCard($excludeIds, $cardsByRarity) {
        // Calculate total weight
        $totalWeight = 0;
        $availableRarities = [];
        
        foreach ($cardsByRarity as $rarity => $data) {
            $availableCards = array_filter($data['cards'], function($card) use ($excludeIds) {
                return !in_array($card['id'], $excludeIds);
            });
            
            if (!empty($availableCards)) {
                $availableRarities[$rarity] = [
                    'weight' => $data['weight'],
                    'cards' => $availableCards
                ];
                $totalWeight += $data['weight'];
            }
        }
        
        if ($totalWeight === 0) {
            return null;
        }
        
        // Select rarity based on weight
        $random = mt_rand(1, intval($totalWeight * 100)) / 100;
        $currentWeight = 0;
        
        foreach ($availableRarities as $rarity => $data) {
            $currentWeight += $data['weight'];
            if ($random <= $currentWeight) {
                // Randomly select a card from this rarity
                $selectedCard = $data['cards'][array_rand($data['cards'])];
                return $selectedCard;
            }
        }
        
        // Fallback - should not reach here
        $firstRarity = array_values($availableRarities)[0];
        return $firstRarity['cards'][array_rand($firstRarity['cards'])];
    }
    
    /**
     * Draw a card of specific type (for balanced hand guarantees)
     * @param string $type Card type to find
     * @param array $excludeIds Cards to exclude
     * @param array $cardsByRarity Cards organized by rarity
     * @return array|null Selected card or null if none available
     */
    private function drawCardByType($type, $excludeIds, $cardsByRarity) {
        $availableCards = [];
        
        foreach ($cardsByRarity as $rarity => $data) {
            foreach ($data['cards'] as $card) {
                if ($card['type'] === $type && !in_array($card['id'], $excludeIds)) {
                    $availableCards[] = $card;
                }
            }
        }
        
        if (empty($availableCards)) {
            return null;
        }
        
        // Randomly select from available cards of this type
        return $availableCards[array_rand($availableCards)];
    }
    
    /**
     * Get total number of unique cards available
     * @return int Total card count
     */
    private function getTotalAvailableCards() {
        try {
            $sql = "
                SELECT COUNT(*) as total 
                FROM cards c 
                WHERE c.is_active = TRUE AND c.is_collectible = TRUE
            ";
            $result = $this->db->fetchOne($sql);
            return intval($result['total']);
        } catch (Exception $e) {
            error_log("CardManager::getTotalAvailableCards error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get rarity distribution statistics
     * @return array Rarity distribution data
     */
    public function getRarityDistribution() {
        try {
            $sql = "
                SELECT cr.rarity_name, cr.rarity_weight, cr.color_hex,
                       COUNT(c.id) as card_count
                FROM card_rarities cr
                LEFT JOIN cards c ON cr.id = c.rarity_id AND c.is_active = TRUE
                WHERE cr.is_active = TRUE
                GROUP BY cr.id, cr.rarity_name, cr.rarity_weight, cr.color_hex
                ORDER BY cr.display_order ASC
            ";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("CardManager::getRarityDistribution error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get detailed card information by ID
     * @param string $cardId Card ID to lookup
     * @return array|null Card data or null if not found
     */
    public function getCardById($cardId) {
        try {
            $sql = "
                SELECT c.*, cr.rarity_name, cr.rarity_weight, cr.power_multiplier, 
                       cr.color_hex, cq.quantity_available, cq.base_drop_rate
                FROM cards c 
                JOIN card_rarities cr ON c.rarity_id = cr.id
                LEFT JOIN card_quantities cq ON c.id = cq.card_id
                WHERE c.id = ? AND c.is_active = TRUE
            ";
            return $this->db->fetchOne($sql, [$cardId]);
        } catch (Exception $e) {
            error_log("CardManager::getCardById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Convert database card format to legacy JSON format for compatibility
     * @param array $dbCard Database card array
     * @return array Legacy format card array
     */
    public function convertToLegacyFormat($dbCard) {
        return [
            'id' => $dbCard['id'],
            'name' => $dbCard['name'],
            'cost' => intval($dbCard['cost']),
            'type' => $dbCard['type'],
            'damage' => intval($dbCard['damage']),
            'defense' => intval($dbCard['defense'] ?? 0),
            'description' => $dbCard['description'],
            'rarity' => $dbCard['rarity_name'],
            'element' => $dbCard['element'],
            'special_effect' => $dbCard['special_effect'],
            'image' => $dbCard['image_path'],
            'created_at' => $dbCard['created_at'],
            'updated_at' => $dbCard['updated_at']
        ];
    }
    
    /**
     * Convert array of database cards to legacy format
     * @param array $dbCards Array of database cards
     * @return array Array of legacy format cards
     */
    public function convertArrayToLegacyFormat($dbCards) {
        return array_map([$this, 'convertToLegacyFormat'], $dbCards);
    }
}