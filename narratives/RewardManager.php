<?php
/**
 * Reward Manager for NRDSandbox Arrow Integration
 * Handles story-based rewards and their integration with the card system
 */

require_once '../database/Database.php';
require_once '../database/CardManager.php';

class RewardManager {
    private $db;
    private $cardManager;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cardManager = new CardManager();
    }
    
    /**
     * Process story rewards and integrate them with the game
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $rewards Array of reward data
     * @return array Processing results
     */
    public function processStoryRewards($userId, $storyId, $rewards) {
        $results = [];
        
        foreach ($rewards as $reward) {
            try {
                $result = $this->processReward($userId, $storyId, $reward);
                $results[] = $result;
                
                // Log reward for analytics
                $this->logReward($userId, $storyId, $reward, $result);
                
            } catch (Exception $e) {
                error_log("RewardManager::processReward error: " . $e->getMessage());
                $results[] = [
                    'success' => false,
                    'reward' => $reward,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Process a single reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processReward($userId, $storyId, $reward) {
        $rewardType = $reward['type'] ?? 'unknown';
        
        switch ($rewardType) {
            case 'card':
                return $this->processCardReward($userId, $storyId, $reward);
                
            case 'equipment':
                return $this->processEquipmentReward($userId, $storyId, $reward);
                
            case 'stat_boost':
                return $this->processStatBoostReward($userId, $storyId, $reward);
                
            case 'currency':
                return $this->processCurrencyReward($userId, $storyId, $reward);
                
            case 'unlock':
                return $this->processUnlockReward($userId, $storyId, $reward);
                
            case 'random_card':
                return $this->processRandomCardReward($userId, $storyId, $reward);
                
            case 'card_pack':
                return $this->processCardPackReward($userId, $storyId, $reward);
                
            default:
                throw new Exception("Unknown reward type: $rewardType");
        }
    }
    
    /**
     * Process card reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processCardReward($userId, $storyId, $reward) {
        $cardId = $reward['card_id'] ?? null;
        $rarity = $reward['rarity'] ?? 'common';
        
        if (!$cardId) {
            throw new Exception("Card ID required for card reward");
        }
        
        // Get card from database
        $card = $this->cardManager->getCardById($cardId);
        if (!$card) {
            throw new Exception("Card not found: $cardId");
        }
        
        // Add to player's collection/hand
        $this->addCardToPlayerCollection($userId, $card);
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'card', [
            'card_id' => $cardId,
            'card_name' => $card['name'],
            'rarity' => $rarity,
            'card_type' => $card['type']
        ]);
        
        return [
            'success' => true,
            'type' => 'card',
            'card' => $card,
            'message' => "Received card: " . $card['name']
        ];
    }
    
    /**
     * Process equipment reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processEquipmentReward($userId, $storyId, $reward) {
        $equipmentId = $reward['equipment_id'] ?? null;
        $slot = $reward['slot'] ?? 'weapon';
        
        if (!$equipmentId) {
            throw new Exception("Equipment ID required for equipment reward");
        }
        
        // Get equipment from database
        $equipment = $this->cardManager->getCardById($equipmentId);
        if (!$equipment || ($equipment['type'] !== 'weapon' && $equipment['type'] !== 'armor')) {
            throw new Exception("Invalid equipment: $equipmentId");
        }
        
        // Auto-equip if specified
        if ($reward['auto_equip'] ?? false) {
            $this->autoEquipItem($userId, $equipment, $slot);
        } else {
            // Add to player's collection
            $this->addCardToPlayerCollection($userId, $equipment);
        }
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'equipment', [
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipment['name'],
            'slot' => $slot,
            'auto_equipped' => $reward['auto_equip'] ?? false
        ]);
        
        return [
            'success' => true,
            'type' => 'equipment',
            'equipment' => $equipment,
            'message' => "Received equipment: " . $equipment['name']
        ];
    }
    
    /**
     * Process stat boost reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processStatBoostReward($userId, $storyId, $reward) {
        $stat = $reward['stat'] ?? null;
        $value = $reward['value'] ?? 0;
        $duration = $reward['duration'] ?? 'permanent';
        
        if (!$stat || $value == 0) {
            throw new Exception("Stat and value required for stat boost reward");
        }
        
        // Apply stat boost to session
        $this->applyStatBoost($userId, $stat, $value, $duration);
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'stat_boost', [
            'stat' => $stat,
            'value' => $value,
            'duration' => $duration
        ]);
        
        return [
            'success' => true,
            'type' => 'stat_boost',
            'stat' => $stat,
            'value' => $value,
            'duration' => $duration,
            'message' => "Stat boost: +$value $stat ($duration)"
        ];
    }
    
    /**
     * Process currency reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processCurrencyReward($userId, $storyId, $reward) {
        $currency = $reward['currency'] ?? 'credits';
        $amount = $reward['amount'] ?? 0;
        
        if ($amount <= 0) {
            throw new Exception("Amount must be positive for currency reward");
        }
        
        // Add currency to player (implement according to your currency system)
        $this->addCurrencyToPlayer($userId, $currency, $amount);
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'currency', [
            'currency' => $currency,
            'amount' => $amount
        ]);
        
        return [
            'success' => true,
            'type' => 'currency',
            'currency' => $currency,
            'amount' => $amount,
            'message' => "Received: $amount $currency"
        ];
    }
    
    /**
     * Process unlock reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processUnlockReward($userId, $storyId, $reward) {
        $feature = $reward['feature'] ?? null;
        $unlockType = $reward['unlock_type'] ?? 'feature';
        
        if (!$feature) {
            throw new Exception("Feature required for unlock reward");
        }
        
        // Unlock feature for player
        $this->unlockFeature($userId, $feature, $unlockType);
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'unlock', [
            'feature' => $feature,
            'unlock_type' => $unlockType
        ]);
        
        return [
            'success' => true,
            'type' => 'unlock',
            'feature' => $feature,
            'unlock_type' => $unlockType,
            'message' => "Unlocked: $feature"
        ];
    }
    
    /**
     * Process random card reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processRandomCardReward($userId, $storyId, $reward) {
        $rarity = $reward['rarity'] ?? null;
        $cardType = $reward['card_type'] ?? null;
        $element = $reward['element'] ?? null;
        
        // Get random card based on criteria
        $card = $this->getRandomCard($rarity, $cardType, $element);
        
        if (!$card) {
            throw new Exception("No suitable random card found");
        }
        
        // Add to player's collection
        $this->addCardToPlayerCollection($userId, $card);
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'random_card', [
            'card_id' => $card['id'],
            'card_name' => $card['name'],
            'rarity' => $card['rarity'],
            'card_type' => $card['type'],
            'criteria' => [
                'rarity' => $rarity,
                'card_type' => $cardType,
                'element' => $element
            ]
        ]);
        
        return [
            'success' => true,
            'type' => 'random_card',
            'card' => $card,
            'message' => "Received random card: " . $card['name']
        ];
    }
    
    /**
     * Process card pack reward
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Reward data
     * @return array Processing result
     */
    private function processCardPackReward($userId, $storyId, $reward) {
        $packSize = $reward['pack_size'] ?? 3;
        $rarityWeights = $reward['rarity_weights'] ?? null;
        $typeFilter = $reward['type_filter'] ?? null;
        
        $cards = [];
        
        for ($i = 0; $i < $packSize; $i++) {
            $rarity = $rarityWeights ? $this->getWeightedRarity($rarityWeights) : null;
            $card = $this->getRandomCard($rarity, $typeFilter);
            
            if ($card) {
                $cards[] = $card;
                $this->addCardToPlayerCollection($userId, $card);
            }
        }
        
        // Store reward in database
        $this->storeReward($userId, $storyId, 'card_pack', [
            'pack_size' => $packSize,
            'cards' => array_map(function($card) {
                return [
                    'card_id' => $card['id'],
                    'card_name' => $card['name'],
                    'rarity' => $card['rarity']
                ];
            }, $cards)
        ]);
        
        return [
            'success' => true,
            'type' => 'card_pack',
            'cards' => $cards,
            'message' => "Received card pack: " . count($cards) . " cards"
        ];
    }
    
    /**
     * Add card to player's collection
     * @param int $userId User ID
     * @param array $card Card data
     */
    private function addCardToPlayerCollection($userId, $card) {
        // Add to session for immediate use
        if (!isset($_SESSION['story_rewards'])) {
            $_SESSION['story_rewards'] = [];
        }
        
        $_SESSION['story_rewards'][] = [
            'type' => 'card',
            'card' => $card,
            'timestamp' => time()
        ];
        
        // Add to player hand if in active game
        if (isset($_SESSION['playerHand']) && is_array($_SESSION['playerHand'])) {
            $_SESSION['playerHand'][] = $card;
        }
    }
    
    /**
     * Auto-equip item
     * @param int $userId User ID
     * @param array $equipment Equipment data
     * @param string $slot Equipment slot
     */
    private function autoEquipItem($userId, $equipment, $slot) {
        if (!isset($_SESSION['playerEquipment'])) {
            $_SESSION['playerEquipment'] = [];
        }
        
        $_SESSION['playerEquipment'][$slot] = $equipment;
        
        // Store in story equipment for session
        if (!isset($_SESSION['story_equipment'])) {
            $_SESSION['story_equipment'] = [];
        }
        
        $_SESSION['story_equipment'][$slot] = $equipment['id'];
    }
    
    /**
     * Apply stat boost
     * @param int $userId User ID
     * @param string $stat Stat name
     * @param int $value Boost value
     * @param string $duration Duration
     */
    private function applyStatBoost($userId, $stat, $value, $duration) {
        if (!isset($_SESSION['story_stat_mods'])) {
            $_SESSION['story_stat_mods'] = [];
        }
        
        $_SESSION['story_stat_mods'][$stat] = ($_SESSION['story_stat_mods'][$stat] ?? 0) + $value;
        
        // Apply to current mech if available
        if (isset($_SESSION['playerMech']) && isset($_SESSION['playerMech'][strtoupper($stat)])) {
            $_SESSION['playerMech'][strtoupper($stat)] += $value;
        }
    }
    
    /**
     * Add currency to player
     * @param int $userId User ID
     * @param string $currency Currency type
     * @param int $amount Amount
     */
    private function addCurrencyToPlayer($userId, $currency, $amount) {
        // For now, store in session - implement actual currency system later
        if (!isset($_SESSION['story_currency'])) {
            $_SESSION['story_currency'] = [];
        }
        
        $_SESSION['story_currency'][$currency] = ($_SESSION['story_currency'][$currency] ?? 0) + $amount;
    }
    
    /**
     * Unlock feature for player
     * @param int $userId User ID
     * @param string $feature Feature name
     * @param string $unlockType Unlock type
     */
    private function unlockFeature($userId, $feature, $unlockType) {
        if (!isset($_SESSION['story_unlocks'])) {
            $_SESSION['story_unlocks'] = [];
        }
        
        $_SESSION['story_unlocks'][] = [
            'feature' => $feature,
            'unlock_type' => $unlockType,
            'timestamp' => time()
        ];
    }
    
    /**
     * Get random card based on criteria
     * @param string|null $rarity Rarity filter
     * @param string|null $cardType Card type filter
     * @param string|null $element Element filter
     * @return array|null Random card
     */
    private function getRandomCard($rarity = null, $cardType = null, $element = null) {
        $cards = $this->cardManager->getAllCards();
        
        // Filter by criteria
        if ($rarity) {
            $cards = array_filter($cards, function($card) use ($rarity) {
                return $card['rarity'] === $rarity;
            });
        }
        
        if ($cardType) {
            $cards = array_filter($cards, function($card) use ($cardType) {
                return $card['type'] === $cardType;
            });
        }
        
        if ($element) {
            $cards = array_filter($cards, function($card) use ($element) {
                return $card['element'] === $element;
            });
        }
        
        if (empty($cards)) {
            return null;
        }
        
        return $cards[array_rand($cards)];
    }
    
    /**
     * Get weighted rarity
     * @param array $weights Rarity weights
     * @return string Selected rarity
     */
    private function getWeightedRarity($weights) {
        $total = array_sum($weights);
        $random = mt_rand(1, $total);
        
        $cumulative = 0;
        foreach ($weights as $rarity => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $rarity;
            }
        }
        
        return array_keys($weights)[0]; // Fallback
    }
    
    /**
     * Store reward in database
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param string $type Reward type
     * @param array $data Reward data
     */
    private function storeReward($userId, $storyId, $type, $data) {
        try {
            $sql = "INSERT INTO story_rewards (user_id, story_id, reward_type, reward_data, granted_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            
            $this->db->execute($sql, [
                $userId,
                $storyId,
                $type,
                json_encode($data)
            ]);
        } catch (Exception $e) {
            // Table might not exist yet, log but don't fail
            error_log("Failed to store reward: " . $e->getMessage());
        }
    }
    
    /**
     * Log reward for analytics
     * @param int $userId User ID
     * @param string $storyId Story ID
     * @param array $reward Original reward data
     * @param array $result Processing result
     */
    private function logReward($userId, $storyId, $reward, $result) {
        try {
            $sql = "INSERT INTO story_analytics (story_id, event_type, user_id, event_data, created_at) 
                    VALUES (?, 'reward_earned', ?, ?, NOW())";
            
            $this->db->execute($sql, [
                $storyId,
                $userId,
                json_encode([
                    'reward' => $reward,
                    'result' => $result
                ])
            ]);
        } catch (Exception $e) {
            // Table might not exist yet, log but don't fail
            error_log("Failed to log reward: " . $e->getMessage());
        }
    }
    
    /**
     * Get player's pending rewards
     * @param int $userId User ID
     * @return array Pending rewards
     */
    public function getPendingRewards($userId) {
        try {
            $sql = "SELECT * FROM story_rewards 
                    WHERE user_id = ? AND is_claimed = 0 
                    ORDER BY granted_at DESC";
            
            return $this->db->fetchAll($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Failed to get pending rewards: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Claim rewards
     * @param int $userId User ID
     * @param array $rewardIds Reward IDs to claim
     * @return bool Success status
     */
    public function claimRewards($userId, $rewardIds) {
        try {
            $placeholders = str_repeat('?,', count($rewardIds) - 1) . '?';
            $sql = "UPDATE story_rewards 
                    SET is_claimed = 1, claimed_at = NOW() 
                    WHERE user_id = ? AND id IN ($placeholders)";
            
            $params = array_merge([$userId], $rewardIds);
            $this->db->execute($sql, $params);
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to claim rewards: " . $e->getMessage());
            return false;
        }
    }
}
?>