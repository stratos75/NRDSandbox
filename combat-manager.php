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
$gameLog = $_SESSION['log'] ?? [];

// Process different combat actions
switch ($action) {
    
    case 'attack_enemy':
        $damageAmount = 10; // Base damage - can be enhanced later with equipment bonuses
        $enemyMech['HP'] = max(0, $enemyMech['HP'] - $damageAmount);
        $gameLog[] = "[" . date('H:i:s') . "] Player attacks Enemy for {$damageAmount} damage!";
        
        $response['success'] = true;
        $response['message'] = "Enemy takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'logEntry' => end($gameLog)
        ];
        break;
        
    case 'enemy_attack':
        $damageAmount = 10; // Base damage - can be enhanced later with equipment bonuses  
        $playerMech['HP'] = max(0, $playerMech['HP'] - $damageAmount);
        $gameLog[] = "[" . date('H:i:s') . "] Enemy attacks Player for {$damageAmount} damage!";
        
        $response['success'] = true;
        $response['message'] = "Player takes {$damageAmount} damage!";
        $response['data'] = [
            'playerMech' => $playerMech,
            'enemyMech' => $enemyMech,
            'logEntry' => end($gameLog)
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
        
    default:
        $response['message'] = "Unknown action: {$action}";
        echo json_encode($response);
        exit;
}

// Save updated state back to session
$_SESSION['playerMech'] = $playerMech;
$_SESSION['enemyMech'] = $enemyMech;
$_SESSION['log'] = $gameLog;

// Return JSON response
echo json_encode($response);
exit;
?>