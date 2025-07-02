<?php
// ===================================================================
// NRD SANDBOX - COMBAT MANAGEMENT SYSTEM (AJAX ENDPOINT)
// ===================================================================
require 'auth.php';

// Set JSON response header
header('Content-Type: application/json');

// Initialize response structure
$response = ['success' => false, 'message' => '', 'data' => null];

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get the action from POST data
$action = $_POST['action'] ?? '';

// Load current game state from session
$playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 'companion' => 'Pilot-Alpha'];
$enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 'companion' => 'AI-Core'];
$playerEquipment = $_SESSION['playerEquipment'] ?? ['weapon' => null, 'armor' => null, 'weapon_special' => null];
$enemyEquipment = $_SESSION['enemyEquipment'] ?? ['weapon' => null, 'armor' => null];
$gameLog = $_SESSION['log'] ?? [];

// Helper function to calculate actual damage
function calculateDamage($attackerMech, $attackerEquipment, $defenderMech, $defenderEquipment) {
    // Base damage from mech stats
    $baseDamage = $attackerMech['ATK'] ?? 20;
    
    // Add weapon bonus if equipped
    $weaponBonus = 0;
    if (!empty($attackerEquipment['weapon'])) {
        $weaponBonus = intval($attackerEquipment['weapon']['atk'] ?? $attackerEquipment['weapon']['damage'] ?? 0);
    }
    
    // Calculate total attack
    $totalAttack = $baseDamage + $weaponBonus;
    
    // Calculate defense
    $baseDefense = $defenderMech['DEF'] ?? 10;
    $armorBonus = 0;
    if (!empty($defenderEquipment['armor'])) {
        $armorBonus = intval($defenderEquipment['armor']['def'] ?? $defenderEquipment['armor']['defense'] ?? 0);
    }
    
    $totalDefense = $baseDefense + $armorBonus;
    
    // Final damage calculation (minimum 1 damage)
    $finalDamage = max(1, $totalAttack - $totalDefense);
    
    return [
        'damage' => $finalDamage,
        'totalAttack' => $totalAttack,
        'totalDefense' => $totalDefense,
        'weaponBonus' => $weaponBonus,
        'armorBonus' => $armorBonus
    ];
}

// Process different combat actions
switch ($action) {
    
    case 'attack_enemy':
        $combatResult = calculateDamage($playerMech, $playerEquipment, $enemyMech, $enemyEquipment);
        $damageAmount = $combatResult['damage'];
        
        $enemyMech['HP'] = max(0, $enemyMech['HP'] - $damageAmount);
        
        // Enhanced combat log with equipment details
        $weaponName = $playerEquipment['weapon']['name'] ?? 'bare hands';
        $logMessage = "[" . date('H:i:s') . "] Player attacks with {$weaponName} for {$damageAmount} damage! (ATK: {$combatResult['totalAttack']} vs DEF: {$combatResult['totalDefense']})";
        $gameLog[] = $logMessage;
        
        $response['success'] = true;
        $response['message'] = "Enemy takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'playerEquipment' => $playerEquipment,
            'enemyEquipment' => $enemyEquipment,
            'logEntry' => end($gameLog),
            'combatDetails' => $combatResult
        ];
        break;
        
    case 'enemy_attack':
        $combatResult = calculateDamage($enemyMech, $enemyEquipment, $playerMech, $playerEquipment);
        $damageAmount = $combatResult['damage'];
        
        $playerMech['HP'] = max(0, $playerMech['HP'] - $damageAmount);
        
        // Enhanced combat log with equipment details
        $weaponName = $enemyEquipment['weapon']['name'] ?? 'basic weapon';
        $logMessage = "[" . date('H:i:s') . "] Enemy attacks with {$weaponName} for {$damageAmount} damage! (ATK: {$combatResult['totalAttack']} vs DEF: {$combatResult['totalDefense']})";
        $gameLog[] = $logMessage;
        
        $response['success'] = true;
        $response['message'] = "Player takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'playerEquipment' => $playerEquipment,
            'enemyEquipment' => $enemyEquipment,
            'logEntry' => end($gameLog),
            'combatDetails' => $combatResult
        ];
        break;
        
    case 'reset_mechs':
        $playerMech['HP'] = $playerMech['MAX_HP'];
        $enemyMech['HP'] = $enemyMech['MAX_HP'];
        $gameLog[] = "[" . date('H:i:s') . "] Mechs reset to full health!";
        
        $response['success'] = true;
        $response['message'] = "Mechs reset to full health!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'logEntry' => end($gameLog)
        ];
        break;
        
    case 'get_combat_status':
        // Just return current combat state (useful for debugging)
        $response['success'] = true;
        $response['message'] = "Combat status retrieved";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'recentLog' => array_slice($gameLog, -5) // Last 5 log entries
        ];
        break;
        
    case 'end_turn':
        // For now, just switch back to player and reset energy
        $_SESSION['currentPlayer'] = 'player';
        $_SESSION['playerEnergy'] = $_SESSION['maxEnergy'] ?? 5;

        $gameLog[] = "[" . date('H:i:s') . "] Turn ended. Energy restored to " . $_SESSION['playerEnergy'] . ".";

        $response['success'] = true;
        $response['message'] = "Player's turn started.";
        $response['data'] = [
            'currentPlayer' => $_SESSION['currentPlayer'],
            'playerEnergy' => $_SESSION['playerEnergy'],
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'logEntry' => end($gameLog)
        ];
        break;
        
    case 'play_card':
        $cardIndex = intval($_POST['card_index'] ?? -1);
        $playerHand = $_SESSION['player_hand'] ?? [];
        $playerEnergy = $_SESSION['playerEnergy'] ?? 0;

        if ($cardIndex >= 0 && isset($playerHand[$cardIndex])) {
            $card = $playerHand[$cardIndex];
            $cardCost = intval($card['cost'] ?? 0);

            if ($playerEnergy >= $cardCost) {
                $_SESSION['playerEnergy'] -= $cardCost;

                // Remove card from hand
                $playedCard = array_splice($_SESSION['player_hand'], $cardIndex, 1);

                $gameLog[] = "[" . date('H:i:s') . "] Player played {$card['name']} for {$cardCost} energy.";
                $_SESSION['log'] = $gameLog;

                $response['success'] = true;
                $response['message'] = "Played {$card['name']}";
                $response['data'] = [
                    'playerEnergy' => $_SESSION['playerEnergy'],
                    'playerHand' => $_SESSION['player_hand']
                ];
            } else {
                $response['message'] = 'Not enough energy!';
            }
        } else {
            $response['message'] = 'Invalid card selected.';
        }
        break;
        
    case 'assign_enemy_equipment':
        $slotType = $_POST['slot_type'] ?? '';
        $cardId = $_POST['card_id'] ?? '';
        
        if (!in_array($slotType, ['weapon', 'armor'])) {
            $response['message'] = 'Invalid slot type';
            break;
        }
        
        // Find the card in the library
        $cardData = null;
        $cardsFile = 'data/cards.json';
        if (file_exists($cardsFile)) {
            $cardsContent = file_get_contents($cardsFile);
            $cardsJson = json_decode($cardsContent, true);
            if ($cardsJson && isset($cardsJson['cards'])) {
                foreach ($cardsJson['cards'] as $card) {
                    if ($card['id'] === $cardId) {
                        $cardData = $card;
                        break;
                    }
                }
            }
        }
        
        if (!$cardData) {
            $response['message'] = 'Card not found';
            break;
        }
        
        // Validate card type matches slot
        if ($cardData['type'] !== $slotType) {
            $response['message'] = 'Card type does not match slot type';
            break;
        }
        
        // Assign the equipment with proper ATK/DEF values
        if ($slotType === 'weapon') {
            $enemyEquipment[$slotType] = [
                'id' => $cardData['id'],
                'name' => $cardData['name'],
                'atk' => $cardData['damage'] ?? 0,
                'def' => 0,
                'type' => $cardData['type'],
                'element' => $cardData['element'] ?? 'fire',
                'card_data' => $cardData
            ];
        } else { // armor
            $enemyEquipment[$slotType] = [
                'id' => $cardData['id'],
                'name' => $cardData['name'],
                'atk' => 0,
                'def' => $cardData['defense'] ?? $cardData['damage'] ?? 0,
                'type' => $cardData['type'],
                'element' => $cardData['element'] ?? 'fire',
                'card_data' => $cardData
            ];
        }
        
        $_SESSION['enemyEquipment'] = $enemyEquipment;
        
        $gameLog[] = "[" . date('H:i:s') . "] Enemy equipped {$cardData['name']}";
        
        $response['success'] = true;
        $response['message'] = "Enemy equipped {$cardData['name']}";
        $response['data'] = ['enemyEquipment' => $enemyEquipment];
        break;
        
    case 'clear_enemy_equipment':
        $slotType = $_POST['slot_type'] ?? '';
        
        if (!in_array($slotType, ['weapon', 'armor'])) {
            $response['message'] = 'Invalid slot type';
            break;
        }
        
        $oldItem = $enemyEquipment[$slotType]['name'] ?? 'nothing';
        $enemyEquipment[$slotType] = null;
        $_SESSION['enemyEquipment'] = $enemyEquipment;
        
        $gameLog[] = "[" . date('H:i:s') . "] Enemy unequipped {$oldItem}";
        
        $response['success'] = true;
        $response['message'] = "Enemy unequipped {$oldItem}";
        $response['data'] = ['enemyEquipment' => $enemyEquipment];
        break;
        
    case 'random_enemy_loadout':
        // Get all weapons and armor from card library
        $weapons = [];
        $armor = [];
        
        $cardsFile = 'data/cards.json';
        if (file_exists($cardsFile)) {
            $cardsContent = file_get_contents($cardsFile);
            $cardsJson = json_decode($cardsContent, true);
            if ($cardsJson && isset($cardsJson['cards'])) {
                foreach ($cardsJson['cards'] as $card) {
                    if ($card['type'] === 'weapon') {
                        $weapons[] = $card;
                    } elseif ($card['type'] === 'armor') {
                        $armor[] = $card;
                    }
                }
            }
        }
        
        // Randomly select weapon and armor
        if (!empty($weapons)) {
            $randomWeapon = $weapons[array_rand($weapons)];
            $enemyEquipment['weapon'] = [
                'id' => $randomWeapon['id'],
                'name' => $randomWeapon['name'],
                'atk' => $randomWeapon['damage'] ?? 0,
                'def' => 0,
                'type' => $randomWeapon['type'],
                'element' => $randomWeapon['element'] ?? 'fire',
                'card_data' => $randomWeapon
            ];
        }
        
        if (!empty($armor)) {
            $randomArmor = $armor[array_rand($armor)];
            $enemyEquipment['armor'] = [
                'id' => $randomArmor['id'],
                'name' => $randomArmor['name'],
                'atk' => 0,
                'def' => $randomArmor['defense'] ?? $randomArmor['damage'] ?? 0,
                'type' => $randomArmor['type'],
                'element' => $randomArmor['element'] ?? 'fire',
                'card_data' => $randomArmor
            ];
        }
        
        $_SESSION['enemyEquipment'] = $enemyEquipment;
        
        $weaponName = $enemyEquipment['weapon']['name'] ?? 'none';
        $armorName = $enemyEquipment['armor']['name'] ?? 'none';
        $gameLog[] = "[" . date('H:i:s') . "] Enemy random loadout: {$weaponName} + {$armorName}";
        
        $response['success'] = true;
        $response['message'] = "Random loadout: {$weaponName} + {$armorName}";
        $response['data'] = ['enemyEquipment' => $enemyEquipment];
        break;
        
    default:
        $response['message'] = "Unknown action: {$action}";
        echo json_encode($response);
        exit;
}

// Save updated state back to session
$_SESSION['playerMech'] = $playerMech;
$_SESSION['enemyMech'] = $enemyMech;
$_SESSION['playerEquipment'] = $playerEquipment;
$_SESSION['enemyEquipment'] = $enemyEquipment;
$_SESSION['log'] = $gameLog;

// Return JSON response
echo json_encode($response);
exit;
?>