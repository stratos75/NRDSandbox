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
$playerMech = $_SESSION['playerMech'] ?? ['HP' => 75, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 75, 'companion' => 'Pilot-Alpha'];
$enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 75, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 75, 'companion' => 'AI-Core'];
$playerEquipment = $_SESSION['playerEquipment'] ?? ['weapon' => null, 'armor' => null, 'weapon_special' => null];
$enemyEquipment = $_SESSION['enemyEquipment'] ?? ['weapon' => null, 'armor' => null, 'weapon_special' => null];
$gameLog = $_SESSION['log'] ?? [];

// ===================================================================
// RARITY SCALING HELPER FUNCTIONS
// ===================================================================

// Get numeric rarity level for comparison
function getRarityLevel($rarity) {
    switch ($rarity) {
        case 'common': return 1;
        case 'uncommon': return 2;
        case 'rare': return 3;
        case 'epic': return 4;
        case 'legendary': return 5;
        default: return 1;
    }
}

// Get status effect multiplier based on rarity
function getRarityStatusMultiplier($rarity) {
    switch ($rarity) {
        case 'uncommon': return 1.2;
        case 'rare': return 1.4;
        case 'epic': return 1.7;
        case 'legendary': return 2.0;
        default: return 1.0;
    }
}

// ===================================================================
// STATUS EFFECTS SYSTEM INITIALIZATION
// ===================================================================
// Initialize status effects arrays if not present
if (!isset($_SESSION['playerStatusEffects'])) {
    $_SESSION['playerStatusEffects'] = [];
}
if (!isset($_SESSION['enemyStatusEffects'])) {
    $_SESSION['enemyStatusEffects'] = [];
}
// Initialize turn counter for status effect tracking
if (!isset($_SESSION['currentTurn'])) {
    $_SESSION['currentTurn'] = 1;
}

$playerStatusEffects = $_SESSION['playerStatusEffects'];
$enemyStatusEffects = $_SESSION['enemyStatusEffects'];

// ===================================================================
// STATUS EFFECTS MANAGEMENT FUNCTIONS
// ===================================================================

// Add a status effect to a target (player or enemy)
function addStatusEffect($target, $effect) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    
    // Create unique effect ID
    $effectId = uniqid($effect['type'] . '_');
    $effect['id'] = $effectId;
    $effect['applied_turn'] = $_SESSION['currentTurn'] ?? 1;
    
    // Add to session
    $_SESSION[$targetKey][] = $effect;
    
    return $effectId;
}

// Remove a specific status effect by ID
function removeStatusEffect($target, $effectId) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    
    foreach ($_SESSION[$targetKey] as $index => $effect) {
        if ($effect['id'] === $effectId) {
            unset($_SESSION[$targetKey][$index]);
            $_SESSION[$targetKey] = array_values($_SESSION[$targetKey]); // Reindex
            return true;
        }
    }
    return false;
}

// Process all status effects for a target at turn start
function processStatusEffects($target, &$targetMech, &$gameLog) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    $targetName = $target === 'player' ? 'Player' : 'Enemy';
    $effectsToRemove = [];
    
    foreach ($_SESSION[$targetKey] as $index => $effect) {
        $effectName = $effect['name'] ?? ucfirst($effect['type']);
        
        // Process effect based on type
        switch ($effect['type']) {
            case 'poison':
                $damage = $effect['value'] ?? 5;
                $targetMech['HP'] = max(0, $targetMech['HP'] - $damage);
                $gameLog[] = "[" . date('H:i:s') . "] 🟢 {$targetName} takes {$damage} poison damage from {$effectName}";
                break;
                
            case 'regeneration':
                $healing = $effect['value'] ?? 3;
                $targetMech['HP'] = min($targetMech['MAX_HP'], $targetMech['HP'] + $healing);
                $gameLog[] = "[" . date('H:i:s') . "] 💚 {$targetName} heals {$healing} HP from {$effectName}";
                break;
                
            case 'burn':
                $damage = $effect['value'] ?? 3;
                $targetMech['HP'] = max(0, $targetMech['HP'] - $damage);
                $gameLog[] = "[" . date('H:i:s') . "] 🔥 {$targetName} takes {$damage} burn damage from {$effectName}";
                break;
                
            case 'freeze':
                // Energy reduction effect
                $energyReduction = $effect['value'] ?? 1;
                if ($target === 'player') {
                    $_SESSION['playerEnergy'] = max(0, ($_SESSION['playerEnergy'] ?? 0) - $energyReduction);
                    $gameLog[] = "[" . date('H:i:s') . "] 🧊 {$targetName} loses {$energyReduction} energy from {$effectName}";
                } else {
                    $_SESSION['enemyEnergy'] = max(0, ($_SESSION['enemyEnergy'] ?? 0) - $energyReduction);
                    $gameLog[] = "[" . date('H:i:s') . "] 🧊 {$targetName} loses {$energyReduction} energy from {$effectName}";
                }
                break;
                
            case 'energy_drain':
                // Plasma energy drain effect
                $energyDrain = $effect['value'] ?? 1;
                if ($target === 'player') {
                    $_SESSION['playerEnergy'] = max(0, ($_SESSION['playerEnergy'] ?? 0) - $energyDrain);
                    $gameLog[] = "[" . date('H:i:s') . "] ⚡ {$targetName} loses {$energyDrain} energy from {$effectName}";
                } else {
                    $_SESSION['enemyEnergy'] = max(0, ($_SESSION['enemyEnergy'] ?? 0) - $energyDrain);
                    $gameLog[] = "[" . date('H:i:s') . "] ⚡ {$targetName} loses {$energyDrain} energy from {$effectName}";
                }
                break;
                
            // Stat modifying effects are handled in calculateDamage()
            case 'attack_boost':
            case 'defense_boost':
            case 'defense_debuff':
            case 'shield':
            case 'shield_disruption':
                // These are processed during combat calculations
                break;
        }
        
        // Decrement duration
        $_SESSION[$targetKey][$index]['duration']--;
        
        // Mark for removal if expired
        if ($_SESSION[$targetKey][$index]['duration'] <= 0) {
            $effectsToRemove[] = $effect['id'];
            $gameLog[] = "[" . date('H:i:s') . "] ⏰ {$effectName} expires on {$targetName}";
        }
    }
    
    // Remove expired effects
    foreach ($effectsToRemove as $effectId) {
        removeStatusEffect($target, $effectId);
    }
}

// Get total stat modification from status effects
function getStatusEffectStatModifier($target, $statType) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    $totalModifier = 0;
    
    foreach ($_SESSION[$targetKey] as $effect) {
        if ($effect['type'] === $statType) {
            $totalModifier += $effect['value'] ?? 0;
        }
        // Handle defense debuffs
        if ($statType === 'defense_boost' && $effect['type'] === 'defense_debuff') {
            $totalModifier += $effect['value'] ?? 0; // Already negative value
        }
    }
    
    return $totalModifier;
}

// Check if target has shield protection
function getShieldProtection($target) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    $totalShield = 0;
    
    foreach ($_SESSION[$targetKey] as $effect) {
        if ($effect['type'] === 'shield') {
            $totalShield += $effect['value'] ?? 0;
        }
    }
    
    return $totalShield;
}

// Apply damage to shields first, then to HP
function applyDamageWithShields($target, $damage, &$targetMech, &$gameLog) {
    $targetKey = $target === 'player' ? 'playerStatusEffects' : 'enemyStatusEffects';
    $targetName = $target === 'player' ? 'Player' : 'Enemy';
    $remainingDamage = $damage;
    
    // Process shields in order
    foreach ($_SESSION[$targetKey] as $index => $effect) {
        if ($effect['type'] === 'shield' && $effect['value'] > 0 && $remainingDamage > 0) {
            $shieldAbsorbed = min($effect['value'], $remainingDamage);
            $_SESSION[$targetKey][$index]['value'] -= $shieldAbsorbed;
            $remainingDamage -= $shieldAbsorbed;
            
            $gameLog[] = "[" . date('H:i:s') . "] 🛡️ Shield absorbs {$shieldAbsorbed} damage for {$targetName}";
            
            // Remove shield if depleted
            if ($_SESSION[$targetKey][$index]['value'] <= 0) {
                removeStatusEffect($target, $effect['id']);
                $gameLog[] = "[" . date('H:i:s') . "] 💥 Shield destroyed on {$targetName}";
            }
        }
    }
    
    // Apply remaining damage to HP
    if ($remainingDamage > 0) {
        $targetMech['HP'] = max(0, $targetMech['HP'] - $remainingDamage);
    }
    
    return $remainingDamage;
}

// Helper function to calculate actual damage
function calculateDamage($attackerMech, $attackerEquipment, $defenderMech, $defenderEquipment, $isPlayerAttacker = true) {
    // Base damage from mech stats
    $baseDamage = $attackerMech['ATK'] ?? 20;
    
    // Add weapon bonus if equipped (with rarity scaling)
    $weaponBonus = 0;
    if (!empty($attackerEquipment['weapon'])) {
        $baseWeaponDamage = intval($attackerEquipment['weapon']['atk'] ?? $attackerEquipment['weapon']['damage'] ?? 0);
        
        // Apply rarity multiplier for weapon damage
        $rarityMultiplier = 1.0;
        $weaponRarity = $attackerEquipment['weapon']['rarity'] ?? 'common';
        switch ($weaponRarity) {
            case 'uncommon': $rarityMultiplier = 1.10; break;
            case 'rare': $rarityMultiplier = 1.25; break;
            case 'epic': $rarityMultiplier = 1.40; break;
            case 'legendary': $rarityMultiplier = 1.60; break;
        }
        
        $weaponBonus = round($baseWeaponDamage * $rarityMultiplier);
    }
    
    // Add companion attack bonus if attacker is player and companion is active
    $companionAtkBonus = 0;
    if ($isPlayerAttacker && ($_SESSION['playerCompanionActive'] ?? false)) {
        $companionBonuses = $_SESSION['companionBonuses'] ?? [];
        $companionAtkBonus = $companionBonuses['atk_bonus'] ?? 0;
    }
    
    // Add status effect attack bonuses
    $attackerTarget = $isPlayerAttacker ? 'player' : 'enemy';
    $statusAtkBonus = getStatusEffectStatModifier($attackerTarget, 'attack_boost');
    
    // Calculate total attack
    $totalAttack = $baseDamage + $weaponBonus + $companionAtkBonus + $statusAtkBonus;
    
    // Calculate defense (with rarity scaling)
    $baseDefense = $defenderMech['DEF'] ?? 10;
    $armorBonus = 0;
    if (!empty($defenderEquipment['armor'])) {
        $baseArmorDefense = intval($defenderEquipment['armor']['def'] ?? $defenderEquipment['armor']['defense'] ?? 0);
        
        // Apply rarity multiplier for armor defense
        $rarityMultiplier = 1.0;
        $armorRarity = $defenderEquipment['armor']['rarity'] ?? 'common';
        switch ($armorRarity) {
            case 'uncommon': $rarityMultiplier = 1.15; break;
            case 'rare': $rarityMultiplier = 1.30; break;
            case 'epic': $rarityMultiplier = 1.50; break;
            case 'legendary': $rarityMultiplier = 1.75; break;
        }
        
        $armorBonus = round($baseArmorDefense * $rarityMultiplier);
    }
    
    // Add companion defense bonus if defender is player and companion is active
    $companionDefBonus = 0;
    if (!$isPlayerAttacker && ($_SESSION['playerCompanionActive'] ?? false)) {
        $companionBonuses = $_SESSION['companionBonuses'] ?? [];
        $companionDefBonus = $companionBonuses['def_bonus'] ?? 0;
    }
    
    // Add status effect defense bonuses
    $defenderTarget = $isPlayerAttacker ? 'enemy' : 'player';
    $statusDefBonus = getStatusEffectStatModifier($defenderTarget, 'defense_boost');
    
    $totalDefense = $baseDefense + $armorBonus + $companionDefBonus + $statusDefBonus;
    
    // Calculate raw damage
    $rawDamage = max(1, $totalAttack - $totalDefense);
    
    // ===================================================================
    // ELEMENTAL DAMAGE BONUSES
    // ===================================================================
    $elementalBonus = 0;
    $elementalSetBonus = 0;
    $elementalResistance = 0;
    $elementalAdvantage = '';
    
    // Get attacker weapon element
    $attackerWeaponElement = $attackerEquipment['weapon']['element'] ?? null;
    $attackerSpecialElement = $attackerEquipment['weapon_special']['element'] ?? null;
    $attackerArmorElement = $attackerEquipment['armor']['element'] ?? null;
    
    // Get defender armor element  
    $defenderArmorElement = $defenderEquipment['armor']['element'] ?? null;
    $defenderWeaponElement = $defenderEquipment['weapon']['element'] ?? null;
    $defenderSpecialElement = $defenderEquipment['weapon_special']['element'] ?? null;
    
    // Enhanced Elemental Interaction Matrix
    $elementalAdvantages = [
        'fire' => ['ice', 'poison'],     // Fire melts ice, burns toxins
        'ice' => ['fire', 'plasma'],     // Ice extinguishes fire, freezes plasma
        'plasma' => ['fire', 'ice'],     // Plasma superheats and disrupts
        'poison' => ['plasma', 'poison'] // Poison corrodes plasma, compounds with itself
    ];
    
    // Element vulnerability multipliers (more nuanced than simple advantage)
    $vulnerabilityMultipliers = [
        'fire' => ['ice' => 1.35, 'poison' => 1.20],
        'ice' => ['fire' => 1.30, 'plasma' => 1.25], 
        'plasma' => ['fire' => 1.25, 'ice' => 1.25],
        'poison' => ['plasma' => 1.30, 'poison' => 1.15]
    ];
    
    // Element resistance percentages (armor of same element resists more)
    $resistancePercentages = [
        'same_element' => 0.20,  // 20% resistance to same element attacks
        'partial_match' => 0.10,  // 10% resistance if one piece matches
        'full_mismatch' => 0.05   // 5% base resistance
    ];
    
    // Advanced elemental advantage calculation
    $attackingElements = array_filter([$attackerWeaponElement, $attackerSpecialElement]);
    $defendingElements = array_filter([$defenderArmorElement, $defenderWeaponElement, $defenderSpecialElement]);
    
    foreach ($attackingElements as $attackElement) {
        if (!$attackElement) continue;
        
        foreach ($defendingElements as $defendElement) {
            if (!$defendElement) continue;
            
            // Check vulnerability multipliers for more nuanced damage
            if (isset($vulnerabilityMultipliers[$attackElement][$defendElement])) {
                $multiplier = $vulnerabilityMultipliers[$attackElement][$defendElement];
                $vulnerabilityBonus = round($rawDamage * ($multiplier - 1));
                
                if ($vulnerabilityBonus > $elementalBonus) {
                    $elementalBonus = $vulnerabilityBonus;
                    $elementalAdvantage = ucfirst($attackElement) . ' exploits ' . ucfirst($defendElement) . ' (' . round(($multiplier - 1) * 100) . '% bonus)';
                }
            }
            // Fallback to basic advantage system
            elseif (isset($elementalAdvantages[$attackElement]) && in_array($defendElement, $elementalAdvantages[$attackElement])) {
                $basicBonus = round($rawDamage * 0.20); // 20% basic bonus
                if ($basicBonus > $elementalBonus) {
                    $elementalBonus = $basicBonus;
                    $elementalAdvantage = ucfirst($attackElement) . ' vs ' . ucfirst($defendElement);
                }
            }
        }
    }
    
    // Elemental Set Bonuses
    $attackerElements = array_filter([$attackerWeaponElement, $attackerArmorElement, $attackerSpecialElement]);
    $uniqueElements = array_unique($attackerElements);
    
    if (count($attackerElements) >= 3 && count($uniqueElements) === 1) {
        // 3-Piece Elemental Set Bonus (15% total damage bonus)
        $elementalSetBonus = round($rawDamage * 0.15); // 15% set bonus
    } elseif (count($attackerElements) >= 2 && count($uniqueElements) === 1) {
        // 2-Piece Elemental Set Bonus (8% total damage bonus)
        $elementalSetBonus = round($rawDamage * 0.08); // 8% set bonus
    }
    
    // Advanced Elemental Resistance System
    $resistanceReduction = 0;
    
    // Count how many defender pieces match attacking elements
    $attackingElementsForResistance = array_filter([$attackerWeaponElement, $attackerSpecialElement]);
    $defendingElementsForResistance = array_filter([$defenderArmorElement, $defenderWeaponElement, $defenderSpecialElement]);
    
    foreach ($attackingElementsForResistance as $attackElement) {
        $matchingDefenderPieces = 0;
        foreach ($defendingElementsForResistance as $defendElement) {
            if ($attackElement === $defendElement) {
                $matchingDefenderPieces++;
            }
        }
        
        // Calculate resistance based on number of matching pieces
        if ($matchingDefenderPieces >= 2) {
            $resistancePercent = $resistancePercentages['same_element']; // 20% for 2+ pieces
        } elseif ($matchingDefenderPieces === 1) {
            $resistancePercent = $resistancePercentages['partial_match']; // 10% for 1 piece
        } else {
            $resistancePercent = $resistancePercentages['full_mismatch']; // 5% base
        }
        
        $currentResistance = round($rawDamage * $resistancePercent);
        if ($currentResistance > $resistanceReduction) {
            $resistanceReduction = $currentResistance;
        }
    }
    
    $elementalResistance = $resistanceReduction;
    
    // Apply elemental modifiers
    $finalDamage = $rawDamage + $elementalBonus + $elementalSetBonus - $elementalResistance;
    $finalDamage = max(1, $finalDamage); // Minimum 1 damage
    
    // Apply companion damage reduction if defender is player and companion is active
    if (!$isPlayerAttacker && ($_SESSION['playerCompanionActive'] ?? false)) {
        $companionBonuses = $_SESSION['companionBonuses'] ?? [];
        $damageReduction = $companionBonuses['damage_reduction'] ?? 0;
        if ($damageReduction > 0) {
            $finalDamage = max(1, round($rawDamage * (1 - $damageReduction / 100)));
        }
    }
    
    return [
        'damage' => $finalDamage,
        'rawDamage' => $rawDamage,
        'totalAttack' => $totalAttack,
        'totalDefense' => $totalDefense,
        'weaponBonus' => $weaponBonus,
        'armorBonus' => $armorBonus,
        'companionAtkBonus' => $companionAtkBonus,
        'companionDefBonus' => $companionDefBonus,
        'damageReduction' => ($finalDamage < $rawDamage) ? ($rawDamage - $finalDamage) : 0,
        'elementalBonus' => $elementalBonus,
        'elementalSetBonus' => $elementalSetBonus,
        'elementalResistance' => $elementalResistance,
        'elementalAdvantage' => $elementalAdvantage,
        'synergyPieces' => count($attackerElements) >= 2 && count($uniqueElements) === 1 ? count($attackerElements) : 0,
        'synergyElement' => count($attackerElements) >= 2 && count($uniqueElements) === 1 ? $uniqueElements[0] : null,
        'statusAtkBonus' => $statusAtkBonus,
        'statusDefBonus' => $statusDefBonus,
        'attackerElements' => $attackerElements,
        'defenderElements' => $defendingElements,
        'attackerEquipment' => ['weapon' => $attackerWeaponElement, 'armor' => $attackerArmorElement, 'special' => $attackerSpecialElement],
        'defenderEquipment' => ['weapon' => $defenderWeaponElement, 'armor' => $defenderArmorElement, 'special' => $defenderSpecialElement],
        'damageMultiplier' => $finalDamage / max(1, $rawDamage),
        'isCriticalHit' => $elementalBonus > ($rawDamage * 0.25), // More than 25% bonus = critical
        'isEffectiveHit' => $elementalBonus > 0, // Any elemental bonus = effective
        'isResistedHit' => $elementalResistance > ($rawDamage * 0.15), // More than 15% resistance = resisted
        'weaponRarity' => $attackerEquipment['weapon']['rarity'] ?? 'none',
        'armorRarity' => $defenderEquipment['armor']['rarity'] ?? 'none',
        'hasRarityBonus' => ($attackerEquipment['weapon']['rarity'] ?? 'common') !== 'common' || 
                           ($defenderEquipment['armor']['rarity'] ?? 'common') !== 'common'
    ];
}

// Track combat statistics for persistent user data
function trackCombatStatistics($combatResult, $isPlayerAttacker, $targetHP, $initialTargetHP) {
    // Initialize session stats if not present
    if (!isset($_SESSION['combatStats'])) {
        $_SESSION['combatStats'] = [
            'totalDamageDealt' => 0,
            'totalDamageTaken' => 0,
            'criticalHits' => 0,
            'effectiveHits' => 0,
            'resistedHits' => 0,
            'synergiesUsed' => 0,
            'statusEffectsApplied' => 0,
            'elementsUsed' => [],
            'maxSingleHit' => 0,
            'combatsWon' => 0,
            'combatsLost' => 0,
            'totalCombats' => 0,
            'averageDamagePerHit' => 0,
            'elementalPreference' => null
        ];
    }
    
    $stats = &$_SESSION['combatStats'];
    $damage = $combatResult['damage'];
    
    if ($isPlayerAttacker) {
        // Player is attacking
        $stats['totalDamageDealt'] += $damage;
        
        if ($combatResult['isCriticalHit']) {
            $stats['criticalHits']++;
        }
        
        if ($combatResult['isEffectiveHit']) {
            $stats['effectiveHits']++;
        }
        
        if ($combatResult['synergyPieces'] >= 2) {
            $stats['synergiesUsed']++;
        }
        
        // Track elemental usage
        if ($combatResult['synergyElement']) {
            if (!isset($stats['elementsUsed'][$combatResult['synergyElement']])) {
                $stats['elementsUsed'][$combatResult['synergyElement']] = 0;
            }
            $stats['elementsUsed'][$combatResult['synergyElement']]++;
        }
        
        // Track max single hit
        if ($damage > $stats['maxSingleHit']) {
            $stats['maxSingleHit'] = $damage;
        }
        
        // Check for combat victory
        if ($targetHP <= 0) {
            $stats['combatsWon']++;
            $stats['totalCombats']++;
        }
    } else {
        // Player is defending (taking damage)
        $stats['totalDamageTaken'] += $damage;
        
        if ($combatResult['isResistedHit']) {
            $stats['resistedHits']++;
        }
        
        // Check for combat loss
        if ($targetHP <= 0) {
            $stats['combatsLost']++;
            $stats['totalCombats']++;
        }
    }
    
    // Calculate average damage per hit
    $totalHits = $stats['criticalHits'] + $stats['effectiveHits'] + max(1, $stats['totalDamageDealt'] / 10);
    $stats['averageDamagePerHit'] = $stats['totalDamageDealt'] / max(1, $totalHits);
    
    // Determine elemental preference
    if (!empty($stats['elementsUsed'])) {
        $stats['elementalPreference'] = array_search(max($stats['elementsUsed']), $stats['elementsUsed']);
    }
    
    return $stats;
}

// Save combat statistics to database
function saveCombatStatistics($userId) {
    if (!isset($_SESSION['combatStats'])) {
        return false;
    }
    
    $stats = $_SESSION['combatStats'];
    
    try {
        $db = Database::getInstance();
        
        // Calculate additional metrics
        $winRate = $stats['totalCombats'] > 0 ? ($stats['combatsWon'] / $stats['totalCombats']) * 100 : 0;
        $damageEfficiency = $stats['totalDamageTaken'] > 0 ? $stats['totalDamageDealt'] / $stats['totalDamageTaken'] : 0;
        
        // Insert or update combat statistics
        $sql = "INSERT INTO combat_statistics (
            user_id, session_id, total_damage_dealt, total_damage_taken, 
            critical_hits, effective_hits, resisted_hits, synergies_used,
            status_effects_applied, max_single_hit, combats_won, combats_lost,
            total_combats, average_damage_per_hit, elemental_preference, 
            elements_used, win_rate, damage_efficiency
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            total_damage_dealt = total_damage_dealt + VALUES(total_damage_dealt),
            total_damage_taken = total_damage_taken + VALUES(total_damage_taken),
            critical_hits = critical_hits + VALUES(critical_hits),
            effective_hits = effective_hits + VALUES(effective_hits),
            resisted_hits = resisted_hits + VALUES(resisted_hits),
            synergies_used = synergies_used + VALUES(synergies_used),
            status_effects_applied = status_effects_applied + VALUES(status_effects_applied),
            max_single_hit = GREATEST(max_single_hit, VALUES(max_single_hit)),
            combats_won = combats_won + VALUES(combats_won),
            combats_lost = combats_lost + VALUES(combats_lost),
            total_combats = total_combats + VALUES(total_combats),
            average_damage_per_hit = VALUES(average_damage_per_hit),
            elemental_preference = VALUES(elemental_preference),
            elements_used = VALUES(elements_used),
            win_rate = VALUES(win_rate),
            damage_efficiency = VALUES(damage_efficiency),
            updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $userId,
            session_id(),
            $stats['totalDamageDealt'],
            $stats['totalDamageTaken'],
            $stats['criticalHits'],
            $stats['effectiveHits'],
            $stats['resistedHits'],
            $stats['synergiesUsed'],
            $stats['statusEffectsApplied'],
            $stats['maxSingleHit'],
            $stats['combatsWon'],
            $stats['combatsLost'],
            $stats['totalCombats'],
            $stats['averageDamagePerHit'],
            $stats['elementalPreference'],
            json_encode($stats['elementsUsed']),
            $winRate,
            $damageEfficiency
        ]);
        
        // Check for new high scores
        updateHighScores($userId, $stats);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to save combat statistics: " . $e->getMessage());
        return false;
    }
}

// Update high scores based on current performance
function updateHighScores($userId, $stats) {
    try {
        $db = Database::getInstance();
        
        // Max damage high score
        if ($stats['maxSingleHit'] > 0) {
            $sql = "INSERT INTO high_scores (user_id, score_type, score_value, score_details)
                    VALUES (?, 'max_damage', ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    score_value = GREATEST(score_value, VALUES(score_value)),
                    score_details = IF(VALUES(score_value) > score_value, VALUES(score_details), score_details),
                    achieved_at = IF(VALUES(score_value) > score_value, CURRENT_TIMESTAMP, achieved_at)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId, 
                $stats['maxSingleHit'],
                json_encode(['damage' => $stats['maxSingleHit'], 'element' => $stats['elementalPreference']])
            ]);
        }
        
        // Total damage high score
        if ($stats['totalDamageDealt'] > 0) {
            $sql = "INSERT INTO high_scores (user_id, score_type, score_value, score_details)
                    VALUES (?, 'total_damage', ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    score_value = GREATEST(score_value, VALUES(score_value)),
                    score_details = VALUES(score_details),
                    achieved_at = IF(VALUES(score_value) > score_value, CURRENT_TIMESTAMP, achieved_at)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $stats['totalDamageDealt'],
                json_encode(['totalDamage' => $stats['totalDamageDealt'], 'combats' => $stats['totalCombats']])
            ]);
        }
        
        // Synergy master score (synergies used)
        if ($stats['synergiesUsed'] > 0) {
            $sql = "INSERT INTO high_scores (user_id, score_type, score_value, score_details)
                    VALUES (?, 'synergy_master', ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    score_value = GREATEST(score_value, VALUES(score_value)),
                    score_details = VALUES(score_details),
                    achieved_at = IF(VALUES(score_value) > score_value, CURRENT_TIMESTAMP, achieved_at)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $userId,
                $stats['synergiesUsed'],
                json_encode(['synergies' => $stats['synergiesUsed'], 'preference' => $stats['elementalPreference']])
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Failed to update high scores: " . $e->getMessage());
    }
}

// Apply elemental status effects based on equipment synergies
function applyElementalStatusEffects($combatResult, $targetPlayer, &$gameLog) {
    $appliedEffects = [];
    $synergyPieces = $combatResult['synergyPieces'] ?? 0;
    $synergyElement = $combatResult['synergyElement'] ?? null;
    
    // Only apply status effects if there's elemental synergy (2+ matching pieces)
    if ($synergyPieces < 2 || !$synergyElement) {
        return $appliedEffects;
    }
    
    $targetName = $targetPlayer === 'player' ? 'Player' : 'Enemy';
    $effectStrength = $synergyPieces >= 3 ? 'strong' : 'normal';
    
    switch ($synergyElement) {
        case 'poison':
            // Poison DoT - scales with synergy pieces
            $poisonDamage = $synergyPieces >= 3 ? 8 : 5;
            $poisonDuration = $synergyPieces >= 3 ? 4 : 3;
            
            $statusEffect = [
                'type' => 'poison',
                'name' => $synergyPieces >= 3 ? 'Virulent Poison' : 'Toxic Poison',
                'value' => $poisonDamage,
                'duration' => $poisonDuration,
                'source' => 'elemental_synergy',
                'description' => "Poison damage from {$synergyPieces}-piece poison synergy"
            ];
            
            $effectId = addStatusEffect($targetPlayer, $statusEffect);
            $appliedEffects[] = $statusEffect;
            $gameLog[] = "[" . date('H:i:s') . "] 🟢 {$statusEffect['name']} applied to {$targetName} ({$poisonDamage} dmg/turn for {$poisonDuration} turns)";
            break;
            
        case 'fire':
            // Burn DoT - can stack
            $burnDamage = $synergyPieces >= 3 ? 6 : 4;
            $burnDuration = $synergyPieces >= 3 ? 3 : 2;
            
            $statusEffect = [
                'type' => 'burn',
                'name' => $synergyPieces >= 3 ? 'Inferno Burn' : 'Searing Burn',
                'value' => $burnDamage,
                'duration' => $burnDuration,
                'source' => 'elemental_synergy',
                'description' => "Burn damage from {$synergyPieces}-piece fire synergy"
            ];
            
            $effectId = addStatusEffect($targetPlayer, $statusEffect);
            $appliedEffects[] = $statusEffect;
            $gameLog[] = "[" . date('H:i:s') . "] 🔥 {$statusEffect['name']} applied to {$targetName} ({$burnDamage} dmg/turn for {$burnDuration} turns)";
            break;
            
        case 'ice':
            // Freeze/Slow effect - reduces energy and/or defense
            if ($synergyPieces >= 3) {
                // 3-piece: Freeze (energy reduction)
                $statusEffect = [
                    'type' => 'freeze',
                    'name' => 'Deep Freeze',
                    'value' => 2, // Energy reduction per turn
                    'duration' => 2,
                    'source' => 'elemental_synergy',
                    'description' => "Energy reduction from 3-piece ice synergy"
                ];
                $gameLog[] = "[" . date('H:i:s') . "] 🧊 Deep Freeze applied to {$targetName} (-2 energy/turn for 2 turns)";
            } else {
                // 2-piece: Slow (defense reduction)
                $statusEffect = [
                    'type' => 'defense_debuff',
                    'name' => 'Frost Slow',
                    'value' => -3, // Defense reduction
                    'duration' => 3,
                    'source' => 'elemental_synergy',
                    'description' => "Defense reduction from 2-piece ice synergy"
                ];
                $gameLog[] = "[" . date('H:i:s') . "] ❄️ Frost Slow applied to {$targetName} (-3 defense for 3 turns)";
            }
            
            $effectId = addStatusEffect($targetPlayer, $statusEffect);
            $appliedEffects[] = $statusEffect;
            break;
            
        case 'plasma':
            // Shield penetration / Energy disruption
            if ($synergyPieces >= 3) {
                // 3-piece: Energy drain
                $statusEffect = [
                    'type' => 'energy_drain',
                    'name' => 'Plasma Storm',
                    'value' => 1, // Energy drained per turn
                    'duration' => 3,
                    'source' => 'elemental_synergy',
                    'description' => "Energy drain from 3-piece plasma synergy"
                ];
                $gameLog[] = "[" . date('H:i:s') . "] ⚡ Plasma Storm applied to {$targetName} (-1 energy/turn for 3 turns)";
            } else {
                // 2-piece: Shield disruption (bonus damage to shields)
                $statusEffect = [
                    'type' => 'shield_disruption',
                    'name' => 'Plasma Disruption',
                    'value' => 50, // 50% bonus damage to shields
                    'duration' => 2,
                    'source' => 'elemental_synergy',
                    'description' => "Shield disruption from 2-piece plasma synergy"
                ];
                $gameLog[] = "[" . date('H:i:s') . "] 💫 Plasma Disruption applied to {$targetName} (+50% damage to shields for 2 turns)";
            }
            
            $effectId = addStatusEffect($targetPlayer, $statusEffect);
            $appliedEffects[] = $statusEffect;
            break;
    }
    
    return $appliedEffects;
}

// AI Turn Logic - Performs complete AI turn and returns actions for visualization
function performAITurn(&$playerMech, &$enemyMech, &$enemyHand, &$enemyEnergy, $maxEnergy, &$gameLog) {
    $ai_actions = [];
    $enemyEnergy = $maxEnergy; // AI starts its turn with full energy.

    // AI Decision 1: Play the highest-damage card it can afford.
    $bestCardIndex = -1;
    $maxDamage = -1;
    foreach ($enemyHand as $index => $card) {
        if (isset($card['cost']) && isset($card['damage']) && intval($card['cost']) <= $enemyEnergy && intval($card['damage']) > $maxDamage) {
            $maxDamage = intval($card['damage']);
            $bestCardIndex = $index;
        }
    }

    if ($bestCardIndex > -1) {
        $cardToPlay = $enemyHand[$bestCardIndex];
        $enemyEnergy -= intval($cardToPlay['cost']);
        $playerMech['HP'] = max(0, $playerMech['HP'] - intval($cardToPlay['damage']));

        $playedCard = array_splice($enemyHand, $bestCardIndex, 1)[0];
        $logMessage = "[" . date('H:i:s') . "] AI played {$playedCard['name']} dealing {$playedCard['damage']} damage.";
        $gameLog[] = $logMessage;
        $ai_actions[] = ['type' => 'play_card', 'card' => $playedCard, 'log' => $logMessage];
        
        // Check for game over after card play
        if ($playerMech['HP'] <= 0) {
            $ai_actions[] = ['type' => 'game_over', 'winner' => 'enemy'];
            return $ai_actions;
        }
    }

    // AI Decision 2: Always perform a base attack.
    $baseAttackDamage = $enemyMech['ATK'] ?? 25;
    $playerMech['HP'] = max(0, $playerMech['HP'] - $baseAttackDamage);
    $logMessage = "[" . date('H:i:s') . "] AI attacks for {$baseAttackDamage} damage.";
    $gameLog[] = $logMessage;
    $ai_actions[] = ['type' => 'attack', 'damage' => $baseAttackDamage, 'log' => $logMessage];
    
    // Check for game over after attack
    if ($playerMech['HP'] <= 0) {
        $ai_actions[] = ['type' => 'game_over', 'winner' => 'enemy'];
    }

    return $ai_actions;
}

// Process different combat actions
switch ($action) {
    
    case 'attack_enemy':
        // Check if player has enough energy (attacking costs 1 energy)
        $attackCost = 1;
        $playerEnergy = $_SESSION['playerEnergy'] ?? 0;
        
        if ($playerEnergy < $attackCost) {
            $response['message'] = 'Not enough energy to attack! (Need 1 energy)';
            break;
        }
        
        // Deduct energy for attacking
        $_SESSION['playerEnergy'] -= $attackCost;
        error_log("🔋 DEBUG: Attack consumed {$attackCost} energy. Energy: {$playerEnergy} → {$_SESSION['playerEnergy']}");
        
        $combatResult = calculateDamage($playerMech, $playerEquipment, $enemyMech, $enemyEquipment, true);
        $damageAmount = $combatResult['damage'];
        
        $enemyMech['HP'] = max(0, $enemyMech['HP'] - $damageAmount);
        
        // Apply elemental status effects based on synergies
        $appliedEffects = applyElementalStatusEffects($combatResult, 'enemy', $gameLog);
        
        // Track combat statistics
        $combatStats = trackCombatStatistics($combatResult, true, $enemyMech['HP'], $enemyMech['MAX_HP']);
        
        // Enhanced combat log with equipment and elemental details
        $weaponName = $playerEquipment['weapon']['name'] ?? 'bare hands';
        $logMessage = "[" . date('H:i:s') . "] Player attacks with {$weaponName} for {$damageAmount} damage! (ATK: {$combatResult['totalAttack']} vs DEF: {$combatResult['totalDefense']})";
        
        // Add elemental information to log
        if ($combatResult['elementalAdvantage']) {
            $logMessage .= " ⚡ {$combatResult['elementalAdvantage']} advantage (+{$combatResult['elementalBonus']} dmg)";
        }
        if ($combatResult['elementalSetBonus'] > 0) {
            $synergyText = $combatResult['synergyPieces'] >= 3 ? 'Elemental mastery' : 'Elemental synergy';
            $logMessage .= " 🎯 {$synergyText} (+{$combatResult['elementalSetBonus']} dmg)";
        }
        if ($combatResult['elementalResistance'] > 0) {
            $logMessage .= " 🛡️ Elemental resistance (-{$combatResult['elementalResistance']} dmg)";
        }
        
        $gameLog[] = $logMessage;
        
        $response['success'] = true;
        $response['message'] = "Enemy takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'playerEquipment' => $playerEquipment,
            'enemyEquipment' => $enemyEquipment,
            'playerEnergy' => $_SESSION['playerEnergy'],
            'logEntry' => end($gameLog),
            'combatDetails' => $combatResult,
            'appliedEffects' => $appliedEffects ?? [],
            'enemyStatusEffects' => $_SESSION['enemyStatusEffects'] ?? [],
            'combatStats' => $combatStats ?? []
        ];
        
        // Check for game over
        if ($enemyMech['HP'] <= 0) {
            $response['data']['gameOver'] = 'player_wins';
            
            // Save combat statistics to database
            if (isset($_SESSION['user_id'])) {
                saveCombatStatistics($_SESSION['user_id']);
            }
        }
        break;
        
    case 'enemy_attack':
        $combatResult = calculateDamage($enemyMech, $enemyEquipment, $playerMech, $playerEquipment, false);
        $damageAmount = $combatResult['damage'];
        
        $playerMech['HP'] = max(0, $playerMech['HP'] - $damageAmount);
        
        // Apply elemental status effects based on synergies
        $appliedEffects = applyElementalStatusEffects($combatResult, 'player', $gameLog);
        
        // Track combat statistics (player defending)
        $combatStats = trackCombatStatistics($combatResult, false, $playerMech['HP'], $playerMech['MAX_HP']);
        
        // Enhanced combat log with equipment and elemental details
        $weaponName = $enemyEquipment['weapon']['name'] ?? 'basic weapon';
        $logMessage = "[" . date('H:i:s') . "] Enemy attacks with {$weaponName} for {$damageAmount} damage! (ATK: {$combatResult['totalAttack']} vs DEF: {$combatResult['totalDefense']})";
        
        // Add elemental information to log
        if ($combatResult['elementalAdvantage']) {
            $logMessage .= " ⚡ {$combatResult['elementalAdvantage']} advantage (+{$combatResult['elementalBonus']} dmg)";
        }
        if ($combatResult['elementalSetBonus'] > 0) {
            $synergyText = $combatResult['synergyPieces'] >= 3 ? 'Elemental mastery' : 'Elemental synergy';
            $logMessage .= " 🎯 {$synergyText} (+{$combatResult['elementalSetBonus']} dmg)";
        }
        if ($combatResult['elementalResistance'] > 0) {
            $logMessage .= " 🛡️ Elemental resistance (-{$combatResult['elementalResistance']} dmg)";
        }
        
        $gameLog[] = $logMessage;
        
        $response['success'] = true;
        $response['message'] = "Player takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'playerEquipment' => $playerEquipment,
            'enemyEquipment' => $enemyEquipment,
            'playerEnergy' => $_SESSION['playerEnergy'],
            'logEntry' => end($gameLog),
            'combatDetails' => $combatResult,
            'appliedEffects' => $appliedEffects ?? [],
            'playerStatusEffects' => $_SESSION['playerStatusEffects'] ?? [],
            'combatStats' => $combatStats ?? []
        ];
        
        // Check for game over
        if ($playerMech['HP'] <= 0) {
            $response['data']['gameOver'] = 'enemy_wins';
            
            // Save combat statistics to database
            if (isset($_SESSION['user_id'])) {
                saveCombatStatistics($_SESSION['user_id']);
            }
        }
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
            'playerEnergy' => $_SESSION['playerEnergy'],
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
            'playerEnergy' => $_SESSION['playerEnergy'],
            'recentLog' => array_slice($gameLog, -5) // Last 5 log entries
        ];
        break;
        
    case 'end_turn':
        // AI takes its turn - pass by reference to update session data
        $ai_actions = performAITurn($playerMech, $enemyMech, $_SESSION['enemy_hand'], $_SESSION['enemyEnergy'], $_SESSION['maxEnergy'], $gameLog);

        // Process status effects at end of turn cycle (affects both player and enemy)
        processStatusEffects('player', $playerMech, $gameLog);
        processStatusEffects('enemy', $enemyMech, $gameLog);

        // Increment turn counter
        $_SESSION['currentTurn'] = ($_SESSION['currentTurn'] ?? 1) + 1;

        // Then, it becomes the player's turn again
        $_SESSION['currentPlayer'] = 'player';
        $_SESSION['playerEnergy'] = $_SESSION['maxEnergy'] ?? 5;

        $response['success'] = true;
        $response['message'] = "AI turn finished. Player's turn.";
        $response['data'] = [
            'ai_actions' => $ai_actions,
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'playerEnergy' => $_SESSION['playerEnergy'],
            'enemyHandCount' => count($_SESSION['enemy_hand']),
            'playerStatusEffects' => $_SESSION['playerStatusEffects'],
            'enemyStatusEffects' => $_SESSION['enemyStatusEffects']
        ];
        
        // Check if AI actions resulted in game over
        foreach ($ai_actions as $action) {
            if ($action['type'] === 'game_over') {
                $response['data']['gameOver'] = ($action['winner'] === 'enemy') ? 'enemy_wins' : 'player_wins';
                break;
            }
        }
        break;
        
    case 'play_card':
        error_log("🎮 DEBUG: play_card action started");
        $cardIndex = intval($_POST['card_index'] ?? -1);
        $playerHand = $_SESSION['player_hand'] ?? [];
        $playerEnergy = $_SESSION['playerEnergy'] ?? 0;
        error_log("🔋 DEBUG: Initial energy from session: {$playerEnergy}");

        if ($cardIndex >= 0 && isset($playerHand[$cardIndex])) {
            $card = $playerHand[$cardIndex];
            
            // NEW ENERGY ECONOMY: Equipment costs energy, other cards use their cost
            if ($card['type'] === 'weapon' || $card['type'] === 'armor') {
                $cardCost = 1; // Basic equipment costs 1 energy
                error_log("🎯 DEBUG: Equipment card - using fixed cost of 1 energy");
            } elseif ($card['type'] === 'special attack') {
                $cardCost = 2; // Special attacks cost 2 energy when equipped
                error_log("🎯 DEBUG: Special attack card - using fixed cost of 2 energy");
            } else {
                $cardCost = intval($card['cost'] ?? 1); // Other cards use their card cost (minimum 1)
                error_log("🎯 DEBUG: Non-equipment card (type: {$card['type']}) - using card cost: {$cardCost}");
            }

            if ($playerEnergy >= $cardCost) {
                $_SESSION['playerEnergy'] -= $cardCost;
                error_log("🔋 DEBUG: Energy after deduction: {$_SESSION['playerEnergy']} (was {$playerEnergy}, cost was {$cardCost})");

                // Apply card damage to enemy if applicable
                $cardDamage = intval($card['damage'] ?? 0);
                if ($cardDamage > 0) {
                    $enemyMech['HP'] = max(0, $enemyMech['HP'] - $cardDamage);
                    $_SESSION['enemyMech'] = $enemyMech;
                }

                // Remove card from hand
                $playedCard = array_splice($_SESSION['player_hand'], $cardIndex, 1);

                $logMessage = "[" . date('H:i:s') . "] Player played {$card['name']} for {$cardCost} energy";
                if ($cardDamage > 0) {
                    $logMessage .= " dealing {$cardDamage} damage";
                }
                $logMessage .= ".";
                $gameLog[] = $logMessage;
                $_SESSION['log'] = $gameLog;

                $response['success'] = true;
                $response['message'] = "Played {$card['name']}";
                $response['data'] = [
                    'playerEnergy' => $_SESSION['playerEnergy'],
                    'playerHand' => $_SESSION['player_hand'],
                    'enemyMech' => $enemyMech,
                    'playedCard' => $playedCard[0], // Add the card that was just played for tutorial system
                    'playerEquipment' => $playerEquipment // Add equipment state for tutorial system
                ];
                error_log("🔋 DEBUG: Sending playerEnergy in response: {$_SESSION['playerEnergy']}");
                
                // Debug logging
                error_log("🔋 DEBUG: Card cost: {$cardCost}, Energy before: {$playerEnergy}, Energy after: {$_SESSION['playerEnergy']}");
                
                // Check for game over
                if ($cardDamage > 0 && $enemyMech['HP'] <= 0) {
                    $response['data']['gameOver'] = 'player_wins';
                }
            } else {
                $response['message'] = 'Not enough energy!';
                // Add cost and energy data for tutorial system
                $response['data'] = ['cost' => $cardCost, 'energy' => $playerEnergy];
            }
        } else {
            $response['message'] = 'Invalid card selected.';
        }
        break;
        
    case 'assign_enemy_equipment':
        $slotType = $_POST['slot_type'] ?? '';
        $cardId = $_POST['card_id'] ?? '';
        
        if (!in_array($slotType, ['weapon', 'armor', 'weapon_special'])) {
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
        if ($slotType === 'weapon_special' && $cardData['type'] !== 'special attack') {
            $response['message'] = 'Only special attack cards can be assigned to weapon_special slots';
            break;
        } elseif ($slotType !== 'weapon_special' && $cardData['type'] !== $slotType) {
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
        } elseif ($slotType === 'weapon_special') {
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
        
        if (!in_array($slotType, ['weapon', 'armor', 'weapon_special'])) {
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
        
        // Mark enemy as manually equipped for tutorial
        $tutorialState = $_SESSION['tutorialState'] ?? [];
        $tutorialState['enemyManuallyEquipped'] = true;
        $_SESSION['tutorialState'] = $tutorialState;
        
        $weaponName = $enemyEquipment['weapon']['name'] ?? 'none';
        $armorName = $enemyEquipment['armor']['name'] ?? 'none';
        $gameLog[] = "[" . date('H:i:s') . "] Enemy random loadout: {$weaponName} + {$armorName}";
        
        $response['success'] = true;
        $response['message'] = "Random loadout: {$weaponName} + {$armorName}";
        $response['data'] = ['enemyEquipment' => $enemyEquipment];
        break;
        
    case 'debug_change_energy':
        $amount = intval($_POST['amount'] ?? 0);
        $currentEnergy = $_SESSION['playerEnergy'] ?? 0;
        $maxEnergy = $_SESSION['maxEnergy'] ?? 5;
        
        // Calculate new energy (clamp between 0 and max)
        $newEnergy = max(0, min($maxEnergy, $currentEnergy + $amount));
        $_SESSION['playerEnergy'] = $newEnergy;
        
        $response['success'] = true;
        $response['message'] = "Energy changed by {$amount}";
        $response['data'] = ['playerEnergy' => $newEnergy];
        break;
        
    case 'debug_reset_energy':
        $maxEnergy = $_SESSION['maxEnergy'] ?? 5;
        $_SESSION['playerEnergy'] = $maxEnergy;
        
        $response['success'] = true;
        $response['message'] = "Energy reset to maximum";
        $response['data'] = ['playerEnergy' => $maxEnergy];
        break;
        
    case 'discard_card':
        $cardIndex = intval($_POST['card_index'] ?? -1);
        $playerHand = $_SESSION['player_hand'] ?? [];
        
        if ($cardIndex >= 0 && isset($playerHand[$cardIndex])) {
            $discardedCard = array_splice($_SESSION['player_hand'], $cardIndex, 1)[0];
            
            $logMessage = "[" . date('H:i:s') . "] Player discarded {$discardedCard['name']}.";
            $gameLog[] = $logMessage;
            $_SESSION['log'] = $gameLog;
            
            $response['success'] = true;
            $response['message'] = "Discarded {$discardedCard['name']}";
            $response['data'] = [
                'playerHand' => $_SESSION['player_hand'],
                'discardedCard' => $discardedCard
            ];
        } else {
            $response['message'] = 'Invalid card selected for discard.';
        }
        break;
        
    case 'activate_companion':
        $owner = $_POST['owner'] ?? '';
        $currentEnergy = $_SESSION['playerEnergy'] ?? 0;
        $companionCost = 2;
        
        if ($owner !== 'player') {
            $response['message'] = 'Only player companions can be activated';
            break;
        }
        
        // Check if companion is already active
        if ($_SESSION['playerCompanionActive'] ?? false) {
            $response['message'] = 'Companion is already active!';
            break;
        }
        
        // Check if player has enough energy
        if ($currentEnergy < $companionCost) {
            $response['message'] = "Not enough energy! Need {$companionCost} energy, have {$currentEnergy}";
            break;
        }
        
        // Load companion data
        $companionLibrary = [
            'Jack' => [
                'name' => 'Jack',
                'full_name' => 'Jack the Super-Intelligent Terrier',
                'energy_bonus' => 1,
                'atk_bonus' => 3,
                'def_bonus' => 2,
                'heal_per_turn' => 0,
                'damage_reduction' => 5,
                'special_ability' => 'tactical_analysis'
            ],
            'AI-Core' => [
                'name' => 'AI-Core',
                'full_name' => 'Tactical AI Core',
                'energy_bonus' => 0,
                'atk_bonus' => 2,
                'def_bonus' => 4,
                'heal_per_turn' => 1,
                'damage_reduction' => 0,
                'special_ability' => 'shield_boost'
            ]
        ];
        
        $companionName = $playerMech['companion'] ?? 'Jack';
        $companionData = $companionLibrary[$companionName] ?? $companionLibrary['Jack'];
        
        // Deduct energy
        $_SESSION['playerEnergy'] -= $companionCost;
        
        // Activate companion
        $_SESSION['playerCompanionActive'] = true;
        
        // Apply companion bonuses (these will be used in combat calculations)
        $_SESSION['companionBonuses'] = [
            'atk_bonus' => $companionData['atk_bonus'],
            'def_bonus' => $companionData['def_bonus'],
            'damage_reduction' => $companionData['damage_reduction'],
            'heal_per_turn' => $companionData['heal_per_turn'],
            'special_ability' => $companionData['special_ability']
        ];
        
        $gameLog[] = "[" . date('H:i:s') . "] {$companionData['full_name']} activated! Bonuses applied.";
        
        $response['success'] = true;
        $response['message'] = "{$companionData['name']} activated! +{$companionData['atk_bonus']} ATK, +{$companionData['def_bonus']} DEF, {$companionData['damage_reduction']}% damage reduction";
        $response['data'] = [
            'playerEnergy' => $_SESSION['playerEnergy'],
            'companionActive' => true,
            'bonuses' => $_SESSION['companionBonuses']
        ];
        break;
        
    case 'apply_status_effect':
        $target = $_POST['target'] ?? 'player'; // 'player' or 'enemy'
        $effectType = $_POST['effect_type'] ?? 'poison';
        $effectValue = intval($_POST['effect_value'] ?? 5);
        $effectDuration = intval($_POST['effect_duration'] ?? 3);
        $effectName = $_POST['effect_name'] ?? ucfirst($effectType);
        
        // Validate target
        if (!in_array($target, ['player', 'enemy'])) {
            $response['message'] = 'Invalid target. Must be player or enemy.';
            break;
        }
        
        // Validate effect type
        $validEffects = ['poison', 'regeneration', 'burn', 'attack_boost', 'defense_boost', 'freeze', 'shield'];
        if (!in_array($effectType, $validEffects)) {
            $response['message'] = 'Invalid effect type. Valid types: ' . implode(', ', $validEffects);
            break;
        }
        
        // Create status effect
        $statusEffect = [
            'type' => $effectType,
            'name' => $effectName,
            'value' => $effectValue,
            'duration' => $effectDuration,
            'source' => 'test_action',
            'description' => "Test {$effectType} effect"
        ];
        
        // Apply the effect
        $effectId = addStatusEffect($target, $statusEffect);
        
        $targetName = $target === 'player' ? 'Player' : 'Enemy';
        $gameLog[] = "[" . date('H:i:s') . "] 🧪 {$effectName} applied to {$targetName} ({$effectValue} value, {$effectDuration} turns)";
        
        $response['success'] = true;
        $response['message'] = "Applied {$effectName} to {$targetName}";
        $response['data'] = [
            'target' => $target,
            'effect' => $statusEffect,
            'effectId' => $effectId,
            'playerStatusEffects' => $_SESSION['playerStatusEffects'],
            'enemyStatusEffects' => $_SESSION['enemyStatusEffects']
        ];
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

// Debug final session state
error_log("🔋 DEBUG: Final session playerEnergy: " . ($_SESSION['playerEnergy'] ?? 'NOT SET'));

// Return JSON response
echo json_encode($response);
exit;
?>