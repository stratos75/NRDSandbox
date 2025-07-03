<?php
// ===================================================================
// NRD SANDBOX - DEBUG & SYSTEM DIAGNOSTICS
// ===================================================================
require '../auth.php';

// Simple version for display purposes
$version = 'v1.0';

// Handle debug actions
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'reset_mechs':
            $_SESSION['playerMech'] = [
                'HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 
                'companion' => 'Pilot-Alpha', 'name' => 'Player Mech',
                'image' => $_SESSION['playerMech']['image'] ?? null
            ];
            $_SESSION['enemyMech'] = [
                'HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 
                'companion' => 'AI-Core', 'name' => 'Enemy Mech',
                'image' => $_SESSION['enemyMech']['image'] ?? null
            ];
            $response = ['success' => true, 'message' => 'Mechs reset to full health'];
            break;
            
        case 'reset_hands':
            $_SESSION['player_hand'] = [];
            $_SESSION['enemy_hand'] = [];
            $response = ['success' => true, 'message' => 'Player and enemy hands cleared'];
            break;
            
        case 'clear_log':
            $_SESSION['log'] = [];
            $response = ['success' => true, 'message' => 'Action log cleared'];
            break;
            
        case 'reset_all':
            // Reset mechs
            $_SESSION['playerMech'] = [
                'HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 
                'companion' => 'Pilot-Alpha', 'name' => 'Player Mech',
                'image' => $_SESSION['playerMech']['image'] ?? null
            ];
            $_SESSION['enemyMech'] = [
                'HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 
                'companion' => 'AI-Core', 'name' => 'Enemy Mech',
                'image' => $_SESSION['enemyMech']['image'] ?? null
            ];
            // Reset hands
            $_SESSION['player_hand'] = [];
            $_SESSION['enemy_hand'] = [];
            // Reset equipment
            $_SESSION['playerEquipment'] = ['weapon' => null, 'armor' => null, 'weapon_special' => null];
            $_SESSION['enemyEquipment'] = ['weapon' => null, 'armor' => null, 'weapon_special' => null];
            // Clear log
            $_SESSION['log'] = [];
            $response = ['success' => true, 'message' => 'All game state reset to defaults'];
            break;
    }
    
    if ($response['success']) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($response['message']));
        exit;
    }
}

// Get success message from redirect
$successMessage = isset($_GET['msg']) ? $_GET['msg'] : '';

// ===================================================================
// SYSTEM DIAGNOSTICS
// ===================================================================
function getSystemDiagnostics() {
    $diagnostics = [];
    
    // Authentication
    $diagnostics['auth'] = [
        'logged_in' => isset($_SESSION['username']),
        'username' => $_SESSION['username'] ?? 'Not logged in',
        'session_id' => substr(session_id(), 0, 8) . '...'
    ];
    
    // Game state
    $playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100];
    $enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100];
    $playerHand = $_SESSION['player_hand'] ?? [];
    $enemyHand = $_SESSION['enemy_hand'] ?? [];
    $playerEquipment = $_SESSION['playerEquipment'] ?? [];
    $enemyEquipment = $_SESSION['enemyEquipment'] ?? [];
    $log = $_SESSION['log'] ?? [];
    
    $diagnostics['game_state'] = [
        'player_mech' => $playerMech,
        'enemy_mech' => $enemyMech,
        'player_hand_count' => count($playerHand),
        'enemy_hand_count' => count($enemyHand),
        'player_equipment' => $playerEquipment,
        'enemy_equipment' => $enemyEquipment,
        'log_entries' => count($log),
        'last_log_entry' => !empty($log) ? end($log) : 'No entries'
    ];
    
    // Cards system
    $cardsFile = '../data/cards.json';
    $diagnostics['cards_system'] = [
        'file_exists' => file_exists($cardsFile),
        'file_readable' => file_exists($cardsFile) && is_readable($cardsFile),
        'file_writable' => file_exists($cardsFile) && is_writable($cardsFile),
        'file_size' => file_exists($cardsFile) ? filesize($cardsFile) : 0
    ];
    
    if ($diagnostics['cards_system']['file_exists']) {
        $jsonContent = file_get_contents($cardsFile);
        $cardsData = json_decode($jsonContent, true);
        $diagnostics['cards_system']['json_valid'] = ($cardsData !== null);
        $diagnostics['cards_system']['cards_count'] = isset($cardsData['cards']) ? count($cardsData['cards']) : 0;
        $diagnostics['cards_system']['json_error'] = json_last_error_msg();
    }
    
    // Image system
    $imageDir = '../data/images/';
    $diagnostics['image_system'] = [
        'dir_exists' => is_dir($imageDir),
        'dir_writable' => is_dir($imageDir) && is_writable($imageDir),
        'card_images_count' => 0,
        'mech_images_count' => 0
    ];
    
    if (is_dir($imageDir)) {
        $diagnostics['image_system']['card_images_count'] = count(glob($imageDir . '*.{png,jpg,jpeg}', GLOB_BRACE));
        $mechDir = $imageDir . 'mechs/';
        if (is_dir($mechDir)) {
            $diagnostics['image_system']['mech_images_count'] = count(glob($mechDir . '*.{png,jpg,jpeg}', GLOB_BRACE));
        }
    }
    
    // System info
    $diagnostics['system'] = [
        'php_version' => phpversion(),
        'session_status' => session_status(),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'time' => date('Y-m-d H:i:s')
    ];
    
    return $diagnostics;
}

$diagnostics = getSystemDiagnostics();
$gameState = $diagnostics['game_state'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug & Diagnostics - NRD Sandbox <?= htmlspecialchars($version) ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .battlefield-container {
            height: auto;
            min-height: 100vh;
            overflow-y: auto;
            padding-bottom: 50px;
        }
        
        .debug-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .debug-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .debug-section {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
        }
        
        .section-header {
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: #ffc107;
            margin: 0;
            font-size: 20px;
        }
        
        .section-header h3 {
            color: #ffc107;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .status-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .status-card.warning {
            border-color: #ffc107;
        }
        
        .status-card.error {
            border-color: #dc3545;
        }
        
        .status-card.success {
            border-color: #28a745;
        }
        
        .status-value {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .status-label {
            font-size: 12px;
            color: #aaa;
            text-transform: uppercase;
        }
        
        .data-table {
            width: 100%;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .data-table th,
        .data-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #333;
            font-size: 12px;
        }
        
        .data-table th {
            background: rgba(0, 0, 0, 0.6);
            color: #ffc107;
            font-weight: bold;
        }
        
        .data-table td {
            color: #ddd;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: linear-gradient(145deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(145deg, #ffc107 0%, #e0a800 100%);
            color: #000;
        }
        
        .btn-danger {
            background: linear-gradient(145deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3);
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .log-container {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        
        .log-entry {
            padding: 2px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: #ddd;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-empty {
            color: #888;
            text-align: center;
            font-style: italic;
            padding: 20px;
        }
        
        .state-inspector {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 11px;
            color: #ddd;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .mech-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .mech-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .mech-name {
            font-size: 16px;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 10px;
        }
        
        .mech-stat {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .debug-grid {
                grid-template-columns: 1fr;
            }
            
            .mech-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="battlefield-container">
    <!-- Top Navigation -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="../index.php" class="config-link">🏠 Back to Game</a>
            <a href="index.php" class="config-link">⚙️ Config Dashboard</a>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">🐛 DEBUG & DIAGNOSTICS</h1>
        </div>
        <div class="nav-right">
            <span class="version-badge"><?= htmlspecialchars($version) ?></span>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Success Message -->
    <?php if (!empty($successMessage)): ?>
        <div class="success-message">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="debug-container">
        
        <!-- System Status Overview -->
        <div class="debug-section">
            <div class="section-header">
                <h2>📊 System Status</h2>
            </div>
            
            <div class="status-grid">
                <div class="status-card <?= $diagnostics['cards_system']['json_valid'] ? 'success' : 'error' ?>">
                    <div class="status-value"><?= $diagnostics['cards_system']['json_valid'] ? 'WORKING' : 'ERROR' ?></div>
                    <div class="status-label">Cards System</div>
                </div>
                <div class="status-card <?= $diagnostics['image_system']['dir_writable'] ? 'success' : 'warning' ?>">
                    <div class="status-value"><?= $diagnostics['image_system']['dir_writable'] ? 'WORKING' : 'WARNING' ?></div>
                    <div class="status-label">Image System</div>
                </div>
                <div class="status-card success">
                    <div class="status-value"><?= $diagnostics['cards_system']['cards_count'] ?></div>
                    <div class="status-label">Total Cards</div>
                </div>
                <div class="status-card">
                    <div class="status-value"><?= $gameState['player_hand_count'] ?> / <?= $gameState['enemy_hand_count'] ?></div>
                    <div class="status-label">Hand Cards (P/E)</div>
                </div>
                <div class="status-card">
                    <div class="status-value"><?= $gameState['log_entries'] ?></div>
                    <div class="status-label">Log Entries</div>
                </div>
                <div class="status-card">
                    <div class="status-value"><?= number_format($diagnostics['system']['memory_usage'] / 1024 / 1024, 1) ?>MB</div>
                    <div class="status-label">Memory Usage</div>
                </div>
            </div>
        </div>
        
        <!-- Debug Grid -->
        <div class="debug-grid">
            
            <!-- Game State Inspector -->
            <div class="debug-section">
                <div class="section-header">
                    <h3>🎮 Game State</h3>
                </div>
                
                <!-- Mech Status -->
                <div class="mech-stats">
                    <div class="mech-card">
                        <div class="mech-name">Player Mech</div>
                        <div class="mech-stat">
                            <span>HP:</span>
                            <span><?= $gameState['player_mech']['HP'] ?>/<?= $gameState['player_mech']['MAX_HP'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>ATK:</span>
                            <span><?= $gameState['player_mech']['ATK'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>DEF:</span>
                            <span><?= $gameState['player_mech']['DEF'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>Image:</span>
                            <span><?= !empty($gameState['player_mech']['image']) ? '✅' : '❌' ?></span>
                        </div>
                    </div>
                    
                    <div class="mech-card">
                        <div class="mech-name">Enemy Mech</div>
                        <div class="mech-stat">
                            <span>HP:</span>
                            <span><?= $gameState['enemy_mech']['HP'] ?>/<?= $gameState['enemy_mech']['MAX_HP'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>ATK:</span>
                            <span><?= $gameState['enemy_mech']['ATK'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>DEF:</span>
                            <span><?= $gameState['enemy_mech']['DEF'] ?></span>
                        </div>
                        <div class="mech-stat">
                            <span>Image:</span>
                            <span><?= !empty($gameState['enemy_mech']['image']) ? '✅' : '❌' ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Equipment Status -->
                <h4 style="color: #ffc107; margin-bottom: 10px;">Equipment Status</h4>
                <table class="data-table">
                    <tr>
                        <th>Player</th>
                        <th>Weapon</th>
                        <th>Armor</th>
                    </tr>
                    <tr>
                        <td>Player</td>
                        <td><?= !empty($gameState['player_equipment']['weapon']) ? $gameState['player_equipment']['weapon']['name'] ?? 'Equipped' : 'None' ?></td>
                        <td><?= !empty($gameState['player_equipment']['armor']) ? $gameState['player_equipment']['armor']['name'] ?? 'Equipped' : 'None' ?></td>
                    </tr>
                    <tr>
                        <td>Enemy</td>
                        <td><?= !empty($gameState['enemy_equipment']['weapon']) ? $gameState['enemy_equipment']['weapon']['name'] ?? 'Equipped' : 'None' ?></td>
                        <td><?= !empty($gameState['enemy_equipment']['armor']) ? $gameState['enemy_equipment']['armor']['name'] ?? 'Equipped' : 'None' ?></td>
                    </tr>
                </table>
            </div>

            <!-- System Diagnostics -->
            <div class="debug-section">
                <div class="section-header">
                    <h3>🔧 System Diagnostics</h3>
                </div>
                
                <h4 style="color: #ffc107; margin-bottom: 10px;">File System</h4>
                <table class="data-table">
                    <tr>
                        <th>Component</th>
                        <th>Status</th>
                        <th>Details</th>
                    </tr>
                    <tr>
                        <td>Cards JSON</td>
                        <td><?= $diagnostics['cards_system']['file_exists'] ? '✅' : '❌' ?></td>
                        <td><?= $diagnostics['cards_system']['file_size'] ?> bytes</td>
                    </tr>
                    <tr>
                        <td>JSON Valid</td>
                        <td><?= $diagnostics['cards_system']['json_valid'] ? '✅' : '❌' ?></td>
                        <td><?= $diagnostics['cards_system']['json_error'] ?></td>
                    </tr>
                    <tr>
                        <td>Images Dir</td>
                        <td><?= $diagnostics['image_system']['dir_exists'] ? '✅' : '❌' ?></td>
                        <td><?= $diagnostics['image_system']['card_images_count'] ?> card images</td>
                    </tr>
                    <tr>
                        <td>Mech Images</td>
                        <td><?= $diagnostics['image_system']['mech_images_count'] > 0 ? '✅' : '❌' ?></td>
                        <td><?= $diagnostics['image_system']['mech_images_count'] ?> files</td>
                    </tr>
                </table>
                
                <h4 style="color: #ffc107; margin: 15px 0 10px 0;">Session Info</h4>
                <table class="data-table">
                    <tr>
                        <th>Property</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>User</td>
                        <td><?= htmlspecialchars($diagnostics['auth']['username']) ?></td>
                    </tr>
                    <tr>
                        <td>Session ID</td>
                        <td><?= $diagnostics['auth']['session_id'] ?></td>
                    </tr>
                    <tr>
                        <td>PHP Version</td>
                        <td><?= $diagnostics['system']['php_version'] ?></td>
                    </tr>
                    <tr>
                        <td>Memory Peak</td>
                        <td><?= number_format($diagnostics['system']['memory_peak'] / 1024 / 1024, 1) ?>MB</td>
                    </tr>
                </table>
            </div>
            
        </div>
        
        <!-- Action Log -->
        <div class="debug-section">
            <div class="section-header">
                <h3>📝 Action Log</h3>
            </div>
            
            <div class="log-container">
                <?php if (!empty($_SESSION['log'])): ?>
                    <?php foreach (array_reverse($_SESSION['log']) as $logEntry): ?>
                        <div class="log-entry"><?= htmlspecialchars($logEntry) ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="log-empty">No log entries yet. Actions will appear here.</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Debug Actions -->
        <div class="debug-section">
            <div class="section-header">
                <h3>🎛️ Debug Actions</h3>
            </div>
            
            <div class="action-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="reset_mechs">
                    <button type="submit" class="btn btn-success">🔄 Reset Mechs</button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="reset_hands">
                    <button type="submit" class="btn btn-warning">🃏 Clear Hands</button>
                </form>
                
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_log">
                    <button type="submit" class="btn btn-primary">📝 Clear Log</button>
                </form>
                
                <form method="post" style="display: inline;" onsubmit="return confirm('Reset ALL game state? This cannot be undone.')">
                    <input type="hidden" name="action" value="reset_all">
                    <button type="submit" class="btn btn-danger">🚨 Reset Everything</button>
                </form>
                
                <button type="button" class="btn btn-primary" onclick="window.location.reload()">🔄 Refresh Status</button>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            Debug & Diagnostics Dashboard | <?= htmlspecialchars($version) ?> | 
            System Monitoring & Game State Inspector
        </div>
    </footer>
</div>

<script>
// Auto-refresh every 30 seconds
setInterval(function() {
    // Only refresh if no forms are being submitted
    if (!document.querySelector('form[data-submitting="true"]')) {
        window.location.reload();
    }
}, 30000);

// Mark forms as submitting to prevent auto-refresh during submission
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            this.setAttribute('data-submitting', 'true');
        });
    });
});
</script>

</body>
</html>