<?php
// ===================================================================
// NRD SANDBOX - SHARED CONFIGURATION FUNCTIONS AND STYLES
// ===================================================================

// Handle CSS delivery
if (isset($_GET['css'])) {
    header('Content-Type: text/css');
    echo getConfigCSS();
    exit;
}

// Handle AJAX requests ONLY (those with 'action' parameter)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Only start session if not already active
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $response = ['success' => false, 'message' => ''];
    
    $action = $_POST['action'];
    
    if ($action === 'reset_all_config') {
        // Reset all configuration to defaults
        $keysToReset = [
            'player_hp', 'player_atk', 'player_def',
            'enemy_hp', 'enemy_atk', 'enemy_def',
            'starting_hand_size', 'max_hand_size', 'deck_size',
            'cards_drawn_per_turn', 'starting_player'
        ];
        
        foreach ($keysToReset as $key) {
            unset($_SESSION[$key]);
        }
        
        // Preserve mech images when clearing mech data
        $playerImage = $_SESSION['playerMech']['image'] ?? null;
        $enemyImage = $_SESSION['enemyMech']['image'] ?? null;
        
        // Reset mech data but preserve images
        $_SESSION['playerMech'] = [
            'HP' => 100, 
            'ATK' => 30, 
            'DEF' => 15, 
            'MAX_HP' => 100, 
            'companion' => 'Pilot-Alpha',
            'name' => 'Player Mech',
            'image' => $playerImage
        ];
        $_SESSION['enemyMech'] = [
            'HP' => 100, 
            'ATK' => 25, 
            'DEF' => 10, 
            'MAX_HP' => 100, 
            'companion' => 'AI-Core',
            'name' => 'Enemy Mech',
            'image' => $enemyImage
        ];
        
        $response['success'] = true;
        $response['message'] = 'All settings reset to defaults';
        
    } elseif ($action === 'export_config') {
        // Export current configuration
        $config = [
            'exported_at' => date('Y-m-d H:i:s'),
            'version' => 'v0.7.0',
            'mech_stats' => [
                'player_hp' => $_SESSION['player_hp'] ?? 100,
                'player_atk' => $_SESSION['player_atk'] ?? 30,
                'player_def' => $_SESSION['player_def'] ?? 15,
                'enemy_hp' => $_SESSION['enemy_hp'] ?? 100,
                'enemy_atk' => $_SESSION['enemy_atk'] ?? 25,
                'enemy_def' => $_SESSION['enemy_def'] ?? 10,
            ],
            'game_rules' => [
                'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
                'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
                'deck_size' => $_SESSION['deck_size'] ?? 20,
                'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
                'starting_player' => $_SESSION['starting_player'] ?? 'player',
            ]
        ];
        
        $response['success'] = true;
        $response['config'] = $config;
        
    } else {
        // Handle invalid or missing action
        $response['message'] = 'Invalid or missing action parameter. Received: ' . $action;
        $response['debug'] = [
            'post_data' => $_POST,
            'received_action' => $action,
            'method' => $_SERVER['REQUEST_METHOD']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ===================================================================
// SHARED FUNCTIONS
// ===================================================================

function renderConfigHeader($build, $pageTitle = 'Configuration') {
    ?>
    <header class="top-bar">
        <div class="nav-left">
            <a href="../index.php" class="config-link">🏠 Back to Game</a>
            <a href="index.php" class="config-link">⚙️ Config Hub</a>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title"><?= strtoupper($pageTitle) ?></h1>
        </div>
        <div class="nav-right">
            <a href="../build-info.php" class="version-badge" title="View Build Information"><?= htmlspecialchars($build['version']) ?></a>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>
    <?php
}

function renderConfigFooter($build) {
    ?>
    <footer class="game-footer">
        <div class="build-info">
            NRD Configuration System | Build <?= htmlspecialchars($build['version']) ?> | 
            <?= htmlspecialchars($build['build_name']) ?>
        </div>
    </footer>
    <?php
}

function getCurrentConfigurationOverview() {
    // Get current session values or defaults
    $config = [
        'scenario_name' => 'Default Scenario',
        'player_hp' => $_SESSION['player_hp'] ?? 100,
        'enemy_hp' => $_SESSION['enemy_hp'] ?? 100,
        'hand_size' => $_SESSION['starting_hand_size'] ?? 5,
        'deck_size' => $_SESSION['deck_size'] ?? 20,
        'starting_player' => $_SESSION['starting_player'] ?? 'player',
    ];
    
    // Determine combat mode
    $player_total = ($config['player_hp'] + ($_SESSION['player_atk'] ?? 30) + ($_SESSION['player_def'] ?? 15));
    $enemy_total = ($config['enemy_hp'] + ($_SESSION['enemy_atk'] ?? 25) + ($_SESSION['enemy_def'] ?? 10));
    
    if (abs($player_total - $enemy_total) <= 20) {
        $config['combat_mode'] = 'Balanced';
    } elseif ($player_total > $enemy_total) {
        $config['combat_mode'] = 'Player Advantage';
    } else {
        $config['combat_mode'] = 'Enemy Advantage';
    }
    
    // Get card statistics
    $cardData = @file_get_contents('../data/cards.json');
    if ($cardData) {
        $cards = json_decode($cardData, true);
        $config['total_cards'] = count($cards['cards'] ?? []);
        
        $types = [];
        foreach ($cards['cards'] ?? [] as $card) {
            $types[$card['type']] = true;
        }
        $config['card_types'] = count($types) . ' types';
    } else {
        $config['total_cards'] = 0;
        $config['card_types'] = '0 types';
    }
    
    return $config;
}

function getConfigCSS() {
    return '
/* ===================================================================
   CONFIGURATION SYSTEM STYLES
   =================================================================== */

.config-dashboard {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
    max-width: 1200px;
    margin: 0 auto;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 12px;
    border: 1px solid #333;
}

.dashboard-title h1 {
    color: #00d4ff;
    font-size: 24px;
    margin: 0 0 8px 0;
}

.dashboard-title p {
    color: #aaa;
    margin: 0;
    font-size: 14px;
}

.dashboard-summary {
    display: flex;
    gap: 15px;
}

.summary-card {
    background: rgba(255, 255, 255, 0.05);
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #444;
    text-align: center;
}

.summary-label {
    display: block;
    font-size: 11px;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 4px;
}

.summary-value {
    display: block;
    font-size: 14px;
    color: #00d4ff;
    font-weight: bold;
}

/* Configuration Cards Grid */
.config-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.config-dashboard-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid #333;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.config-dashboard-card:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
}

.config-dashboard-card.mech-card {
    border-left: 4px solid #28a745;
}

.config-dashboard-card.rules-card {
    border-left: 4px solid #17a2b8;
}

.config-dashboard-card.card-mgmt-card {
    border-left: 4px solid #ffc107;
}

.config-dashboard-card.ai-card {
    border-left: 4px solid #9c27b0;
}

.config-dashboard-card.disabled {
    opacity: 0.6;
    filter: grayscale(50%);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: rgba(0, 0, 0, 0.2);
    border-bottom: 1px solid #333;
}

.card-icon {
    font-size: 32px;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.card-title h3 {
    color: #00d4ff;
    font-size: 18px;
    margin: 0 0 4px 0;
}

.card-title p {
    color: #aaa;
    margin: 0;
    font-size: 13px;
}

.card-content {
    padding: 20px;
}

.config-preview {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.preview-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.preview-row:last-child {
    border-bottom: none;
}

.preview-label {
    color: #aaa;
    font-size: 13px;
}

.preview-value {
    color: #fff;
    font-size: 13px;
    font-weight: bold;
}

.card-actions {
    padding: 15px 20px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid #333;
}

.config-btn {
    display: inline-block;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
    text-align: center;
}

.primary-btn {
    background: linear-gradient(145deg, #00d4ff 0%, #0099cc 100%);
    color: #000;
}

.primary-btn:hover {
    background: linear-gradient(145deg, #33ddff 0%, #00b3e6 100%);
    transform: translateY(-1px);
}

.disabled-btn {
    background: linear-gradient(145deg, #6c757d 0%, #495057 100%);
    color: #fff;
    cursor: not-allowed;
}

/* Quick Actions */
.quick-actions {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid #333;
    border-radius: 12px;
    padding: 20px;
}

.actions-header h3 {
    color: #00d4ff;
    font-size: 18px;
    margin: 0 0 15px 0;
    border-bottom: 1px solid #333;
    padding-bottom: 8px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.action-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    display: inline-block;
}

.return-btn {
    background: linear-gradient(145deg, #28a745 0%, #1e7e34 100%);
    color: white;
}

.return-btn:hover {
    background: linear-gradient(145deg, #34ce57 0%, #257e42 100%);
    transform: translateY(-1px);
}

.reset-btn {
    background: linear-gradient(145deg, #dc3545 0%, #a71e2a 100%);
    color: white;
}

.reset-btn:hover {
    background: linear-gradient(145deg, #e74c5c 0%, #c0392b 100%);
    transform: translateY(-1px);
}

.info-btn {
    background: linear-gradient(145deg, #17a2b8 0%, #117a8b 100%);
    color: white;
}

.info-btn:hover {
    background: linear-gradient(145deg, #1fc8e3 0%, #138496 100%);
    transform: translateY(-1px);
}

.export-btn {
    background: linear-gradient(145deg, #ffc107 0%, #d39e00 100%);
    color: #000;
}

.export-btn:hover {
    background: linear-gradient(145deg, #ffcd39 0%, #e6ac00 100%);
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .config-cards-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
}
';
}
?>