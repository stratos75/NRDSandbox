<?php
require '../auth.php';

$gameConfig = ['hand_size' => 5, 'draw_deck_size' => 20, 'enable_companions' => true];

// Load game rules from session with defaults
$gameRules = [
    'starting_hand_size' => $_SESSION['starting_hand_size'] ?? 5,
    'max_hand_size' => $_SESSION['max_hand_size'] ?? 7,
    'deck_size' => $_SESSION['deck_size'] ?? 20,
    'cards_drawn_per_turn' => $_SESSION['cards_drawn_per_turn'] ?? 1,
    'starting_player' => $_SESSION['starting_player'] ?? 'player'
];

// Load cards from JSON
$cards = [];
if (file_exists('data/cards.json')) {
    $cardData = json_decode(file_get_contents('data/cards.json'), true);
    $cards = $cardData['cards'] ?? [];
}

// Initialize basic mech data
$playerMech = $_SESSION['playerMech'] ?? ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 'companion' => 'Pilot-Alpha'];
$enemyMech = $_SESSION['enemyMech'] ?? ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 'companion' => 'AI-Core'];

$playerWeapon = $_SESSION['playerWeapon'] ?? ['name' => 'Plasma Rifle', 'atk' => 15, 'durability' => 100];
$playerArmor = $_SESSION['playerArmor'] ?? ['name' => 'Shield Array', 'def' => 10, 'durability' => 100];
$enemyWeapon = $_SESSION['enemyWeapon'] ?? ['name' => 'Ion Cannon', 'atk' => 12, 'durability' => 100];
$enemyArmor = $_SESSION['enemyArmor'] ?? ['name' => 'Reactive Plating', 'def' => 8, 'durability' => 100];

$gameLog = $_SESSION['log'] ?? [];

// FORM PROCESSING
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle damage actions
    if (isset($_POST['damage'])) {
        $target = $_POST['damage'];
        $damageAmount = 10; // Base damage
        
        if ($target === 'enemy') {
            $enemyMech['HP'] = max(0, $enemyMech['HP'] - $damageAmount);
            $gameLog[] = "[" . date('H:i:s') . "] Player attacks Enemy for {$damageAmount} damage!";
        } elseif ($target === 'player') {
            $playerMech['HP'] = max(0, $playerMech['HP'] - $damageAmount);
            $gameLog[] = "[" . date('H:i:s') . "] Enemy attacks Player for {$damageAmount} damage!";
        }
    }
    
    // Handle card clicks
    if (isset($_POST['card_click'])) {
        $cardInfo = htmlspecialchars($_POST['card_click']);
        $gameLog[] = "[" . date('H:i:s') . "] Card activated: {$cardInfo}";
    }
    
    // Handle equipment clicks
    if (isset($_POST['equipment_click'])) {
        $equipInfo = htmlspecialchars($_POST['equipment_click']);
        $gameLog[] = "[" . date('H:i:s') . "] Equipment used: {$equipInfo}";
    }
    
    // Handle log clearing
    if (isset($_POST['clear_log'])) {
        $gameLog = [];
    }
    
    // Handle mech reset
    if (isset($_POST['reset_mechs'])) {
        $playerMech = ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100, 'companion' => 'Pilot-Alpha'];
        $enemyMech = ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100, 'companion' => 'AI-Core'];
        $gameLog[] = "[" . date('H:i:s') . "] Mechs reset to full health!";
    }
    
    // Save state back to session
    $_SESSION['playerMech'] = $playerMech;
    $_SESSION['enemyMech'] = $enemyMech;
    $_SESSION['playerWeapon'] = $playerWeapon;
    $_SESSION['playerArmor'] = $playerArmor;
    $_SESSION['enemyWeapon'] = $enemyWeapon;
    $_SESSION['enemyArmor'] = $enemyArmor;
    $_SESSION['log'] = $gameLog;
    
    // Prevent form resubmission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// HELPER FUNCTIONS
function getMechHealthPercent($currentHP, $maxHP) {
    if (!$maxHP || $maxHP <= 0) {
        return 100;
    }
    return max(0, min(100, ($currentHP / $maxHP) * 100));
}

function getMechStatusClass($currentHP, $maxHP) {
    $percent = getMechHealthPercent($currentHP, $maxHP);
    if ($percent > 60) return 'healthy';
    if ($percent > 30) return 'damaged';
    return 'critical';
}

function safeHtmlOutput($value, $default = 'Unknown') {
    if (empty($value) || $value === null) {
        return htmlspecialchars($default);
    }
    return htmlspecialchars($value);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NRD Sandbox - Tactical Card Battle Interface</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="battlefield-container">

    <!-- ===================================================================
         TOP NAVIGATION BAR
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="config/index.php" class="config-link">⚙️ Configure</a>
            <button type="button" class="config-link card-creator-btn" onclick="toggleCardCreator()">🃏 Card Creator</button>
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
        </div>
        <div class="nav-center">
            <h1 class="game-title">NRD TACTICAL SANDBOX</h1>
        </div>
        <div class="nav-right">
            <a href="build-info.php" class="version-badge">v0.9.0</a>
            <a href="logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Game Rules Summary Bar -->
    <div class="rules-summary-bar">
        <div class="rules-summary-content">
            <span class="rules-item">📋 Hand: <?= $gameRules['starting_hand_size'] ?>/<?= $gameRules['max_hand_size'] ?></span>
            <span class="rules-item">📚 Deck: <?= $gameRules['deck_size'] ?> cards</span>
            <span class="rules-item">🎯 Draw: <?= $gameRules['cards_drawn_per_turn'] ?>/turn</span>
            <span class="rules-item">🎮 Start: <?= ucfirst($gameRules['starting_player']) ?></span>
            <a href="config/rules.php" class="rules-config-link">⚙️ Configure Rules</a>
        </div>
    </div>

    <!-- ===================================================================
         MAIN BATTLEFIELD LAYOUT
         =================================================================== -->
    <main class="battlefield">

        <!-- ENEMY SECTION (TOP) -->
        <section class="combat-zone enemy-zone">
            <div class="zone-label">ENEMY TERRITORY</div>
            
            <!-- Enemy Hand (Top - Hidden Cards in Fan Layout) -->
            <div class="hand-section enemy-hand-section">
                <div class="hand-cards-fan">
                    <?php for ($i = 1; $i <= $gameRules['starting_hand_size']; $i++): ?>
                        <div class="hand-card face-down fan-card" style="--card-index: <?= $i-1 ?>"></div>
                    <?php endfor; ?>
                </div>
                <div class="hand-label">Enemy Hand (<?= $gameRules['starting_hand_size'] ?>)</div>
            </div>
            
            <div class="battlefield-layout">
                <!-- Enemy Draw Deck (Far Left) -->
                <div class="draw-deck-area enemy-deck">
                    <div class="draw-pile">
                        <?php for ($i = 0; $i < min(5, $gameRules['deck_size']); $i++): ?>
                            <div class="draw-card face-down" style="z-index: <?= 5-$i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="deck-label">Draw (<?= $gameRules['deck_size'] ?>)</div>
                </div>

                <!-- Enemy Weapon Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Enemy Weapon" class="equipment-card weapon-card enemy-equipment">
                            <div class="card-type">WEAPON</div>
                            <div class="card-name"><?= htmlspecialchars($enemyWeapon['name']) ?></div>
                            <div class="card-stats">ATK: +<?= $enemyWeapon['atk'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Enemy Mech (Center) -->
                <div class="mech-area enemy-mech">
                    <div class="mech-card <?= getMechStatusClass($enemyMech['HP'], $enemyMech['MAX_HP']) ?>">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog enemy-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($enemyMech['companion'], 'AI-Core') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">E</div>
                        <div class="mech-body"></div>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value"><?= $enemyMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name">Enemy Mech</div>
                        <div class="mech-stats">
                            <div class="stat">HP: <?= $enemyMech['HP'] ?>/<?= $enemyMech['MAX_HP'] ?></div>
                            <div class="stat">ATK: <?= $enemyMech['ATK'] ?></div>
                            <div class="stat">DEF: <?= $enemyMech['DEF'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Enemy Armor Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Enemy Armor" class="equipment-card armor-card enemy-equipment">
                            <div class="card-type">ARMOR</div>
                            <div class="card-name"><?= htmlspecialchars($enemyArmor['name']) ?></div>
                            <div class="card-stats">DEF: +<?= $enemyArmor['def'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Spacer -->
                <div class="spacer"></div>
            </div>
        </section>

        <!-- BATTLEFIELD DIVIDER -->
        <div class="battlefield-divider">
            <div class="divider-line"></div>
            <div class="divider-label">COMBAT ZONE</div>
        </div>

        <!-- PLAYER SECTION (BOTTOM) -->
        <section class="combat-zone player-zone">
            <div class="zone-label">PLAYER TERRITORY</div>
            
            <div class="battlefield-layout">
                <!-- Player Draw Deck (Far Left) -->
                <div class="draw-deck-area player-deck">
                    <div class="draw-pile">
                        <?php for ($i = 0; $i < min(5, $gameRules['deck_size']); $i++): ?>
                            <div class="draw-card face-down" style="z-index: <?= 5-$i ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <div class="deck-label">Draw (<?= $gameRules['deck_size'] ?>)</div>
                </div>

                <!-- Player Weapon Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Player Weapon" class="equipment-card weapon-card player-equipment">
                            <div class="card-type">WEAPON</div>
                            <div class="card-name"><?= htmlspecialchars($playerWeapon['name']) ?></div>
                            <div class="card-stats">ATK: +<?= $playerWeapon['atk'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Player Mech (Center) -->
                <div class="mech-area player-mech">
                    <div class="mech-card <?= getMechStatusClass($playerMech['HP'], $playerMech['MAX_HP']) ?>">
                        <?php if ($gameConfig['enable_companions']): ?>
                            <div class="companion-pog player-companion">
                                <div class="pog-content">
                                    <div class="pog-name"><?= safeHtmlOutput($playerMech['companion'], 'Pilot-Alpha') ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mech-faction-label">P</div>
                        <div class="mech-body"></div>
                    </div>
                    
                    <div class="mech-hp-circle">
                        <span class="hp-value"><?= $playerMech['HP'] ?></span>
                    </div>
                    
                    <div class="mech-info">
                        <div class="mech-name">Your Mech</div>
                        <div class="mech-stats">
                            <div class="stat">HP: <?= $playerMech['HP'] ?>/<?= $playerMech['MAX_HP'] ?></div>
                            <div class="stat">ATK: <?= $playerMech['ATK'] ?></div>
                            <div class="stat">DEF: <?= $playerMech['DEF'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Player Armor Card -->
                <div class="equipment-area">
                    <form method="post">
                        <button type="submit" name="equipment_click" value="Player Armor" class="equipment-card armor-card player-equipment">
                            <div class="card-type">ARMOR</div>
                            <div class="card-name"><?= htmlspecialchars($playerArmor['name']) ?></div>
                            <div class="card-stats">DEF: +<?= $playerArmor['def'] ?></div>
                        </button>
                    </form>
                </div>

                <!-- Spacer -->
                <div class="spacer"></div>
            </div>
            
            <!-- Player Hand (Bottom - Visible Cards in Fan Layout) -->
            <div class="hand-section player-hand-section">
                <div class="hand-label">Your Hand (<?= count($cards) ?>/<?= $gameRules['starting_hand_size'] ?>)</div>
                <div class="hand-cards-fan">
                    <?php 
                    // Display real cards up to hand size
                    for ($i = 0; $i < $gameRules['starting_hand_size']; $i++): 
                        if ($i < count($cards)):
                            $card = $cards[$i];
                    ?>
                        <button type="button" onclick="showCardDetails(<?= htmlspecialchars(json_encode($card), ENT_QUOTES, 'UTF-8') ?>)" class="hand-card face-up fan-card <?= $card['type'] ?>-card" style="--card-index: <?= $i ?>">
                            <div class="card-mini-name"><?= htmlspecialchars($card['name']) ?></div>
                            <div class="card-mini-cost"><?= $card['cost'] ?></div>
                            <?php if ($card['damage'] > 0): ?>
                                <div class="card-mini-damage">💥<?= $card['damage'] ?></div>
                            <?php endif; ?>
                            <div class="card-type-icon">
                                <?php
                                $typeIcons = [
                                    'spell' => '✨',
                                    'weapon' => '⚔️', 
                                    'armor' => '🛡️',
                                    'creature' => '👾',
                                    'support' => '🔧'
                                ];
                                echo $typeIcons[$card['type']] ?? '❓';
                                ?>
                            </div>
                        </button>
                    <?php else: ?>
                        <div class="hand-card empty-card-slot fan-card" style="--card-index: <?= $i ?>">
                            <div class="empty-card-content">
                                <div class="empty-card-icon">+</div>
                                <div class="empty-card-text">Empty</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

    </main>

    <!-- ===================================================================
         GAME CONTROLS PANEL
         =================================================================== -->
    <section class="controls-panel">
        <div class="control-group">
            <h3>Combat Actions</h3>
            <form method="post" class="action-buttons">
                <button type="submit" name="damage" value="enemy" class="action-btn attack-btn">
                    ⚔️ Attack Enemy
                </button>
                <button type="submit" name="damage" value="player" class="action-btn defend-btn">
                    🛡️ Enemy Attacks
                </button>
                <button type="submit" name="reset_mechs" value="1" class="action-btn reset-btn">
                    🔄 Reset Mechs
                </button>
            </form>
        </div>
    </section>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            NRD Tactical Sandbox | Game Interface | Build v0.9.0
        </div>
    </footer>

</div>

<!-- ===================================================================
     CARD DETAIL MODAL
     =================================================================== -->
<div id="cardDetailModal" class="card-detail-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🃏 Card Details</h2>
            <button type="button" class="close-btn" onclick="closeCardDetails()">✕</button>
        </div>
        
        <div class="modal-body">
            <div class="card-detail-layout">
                <!-- Large Card Preview -->
                <div class="card-preview-section">
                    <div id="modalCardPreview" class="modal-card-preview">
                        <div class="modal-card-cost">0</div>
                        <div class="modal-card-name">Card Name</div>
                        <div class="modal-card-type">TYPE</div>
                        <div class="modal-card-art">[Card Art]</div>
                        <div class="modal-card-damage"></div>
                        <div class="modal-card-description">Card description...</div>
                        <div class="modal-card-rarity">Common</div>
                    </div>
                </div>
                
                <!-- Card Information Panel -->
                <div class="card-info-section">
                    <h3>Card Information</h3>
                    <div class="card-info-grid">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span id="modalCardName" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Type:</span>
                            <span id="modalCardType" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cost:</span>
                            <span id="modalCardCost" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Damage:</span>
                            <span id="modalCardDamage" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Rarity:</span>
                            <span id="modalCardRarity" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Description:</span>
                            <span id="modalCardDescription" class="info-value">-</span>
                        </div>
                    </div>
                    
                    <h3>Metadata</h3>
                    <div class="card-info-grid">
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span id="modalCardCreated" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Creator:</span>
                            <span id="modalCardCreator" class="info-value">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Card ID:</span>
                            <span id="modalCardId" class="info-value">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" onclick="playCard()" class="action-btn attack-btn">⚡ Play Card</button>
            <button type="button" onclick="closeCardDetails()" class="action-btn cancel-btn">Close</button>
        </div>
    </div>
</div>

<!-- Card Detail Modal Overlay -->
<div id="cardDetailOverlay" class="card-detail-overlay" onclick="closeCardDetails()"></div>

<!-- ===================================================================
     CARD CREATOR PANEL (SLIDE-IN)
     =================================================================== -->
<div id="cardCreatorPanel" class="card-creator-panel">
    <div class="card-creator-header">
        <h2>🃏 Card Creator</h2>
        <button type="button" class="close-btn" onclick="toggleCardCreator()">✕</button>
    </div>
    
    <div class="card-creator-content">
        <!-- Card Form -->
        <div class="card-form-section">
            <h3>Card Properties</h3>
            <form id="cardCreatorForm" class="card-form">
                <div class="form-group">
                    <label for="cardName">Card Name:</label>
                    <input type="text" id="cardName" name="cardName" placeholder="Lightning Bolt" oninput="updateCardPreview()">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardCost">Cost:</label>
                        <input type="number" id="cardCost" name="cardCost" min="0" max="10" value="3" oninput="updateCardPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="cardType">Type:</label>
                        <select id="cardType" name="cardType" onchange="updateCardPreview()">
                            <option value="spell">Spell</option>
                            <option value="weapon">Weapon</option>
                            <option value="armor">Armor</option>
                            <option value="creature">Creature</option>
                            <option value="support">Support</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cardDamage">Damage:</label>
                        <input type="number" id="cardDamage" name="cardDamage" min="0" max="50" value="5" oninput="updateCardPreview()">
                    </div>
                    
                    <div class="form-group">
                        <label for="cardRarity">Rarity:</label>
                        <select id="cardRarity" name="cardRarity" onchange="updateCardPreview()">
                            <option value="common">Common</option>
                            <option value="uncommon">Uncommon</option>
                            <option value="rare">Rare</option>
                            <option value="legendary">Legendary</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="cardDescription">Description:</label>
                    <textarea id="cardDescription" name="cardDescription" placeholder="Deal damage to target enemy..." oninput="updateCardPreview()"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="action-btn reset-btn" onclick="resetCardForm()">🔄 Reset</button>
                    <button type="button" class="action-btn attack-btn" onclick="saveCard()">💾 Save Card</button>
                </div>
            </form>
        </div>
        
        <!-- Card Preview -->
        <div class="card-preview-section">
            <h3>Live Preview</h3>
            <div class="card-preview-container">
                <div id="cardPreview" class="preview-card spell-card">
                    <div class="preview-cost">3</div>
                    <div class="preview-name">Lightning Bolt</div>
                    <div class="preview-type">SPELL</div>
                    <div class="preview-art">[Art]</div>
                    <div class="preview-damage">💥 5</div>
                    <div class="preview-description">Deal damage to target enemy...</div>
                    <div class="preview-rarity common-rarity">Common</div>
                </div>
            </div>
        </div>
        
        <!-- Card Library -->
        <div class="card-library-section">
            <h3>Your Card Library</h3>
            <div class="library-stats">
                <span id="cardCount">0 cards</span> | 
                <button type="button" onclick="loadCardLibrary()" class="refresh-btn">🔄 Refresh</button>
            </div>
            <div id="cardLibrary" class="card-library">
                <div class="library-empty">No cards created yet. Create your first card above!</div>
            </div>
        </div>
    </div>
</div>

<!-- Card Creator Overlay -->
<div id="cardCreatorOverlay" class="card-creator-overlay" onclick="toggleCardCreator()"></div>

<script>
// Card Detail Modal Functions
function showCardDetails(cardData) {
    console.log('Showing card details for:', cardData); // Debug log
    
    // Update card preview
    const preview = document.getElementById('modalCardPreview');
    const typeClasses = ['spell-card', 'weapon-card', 'armor-card', 'creature-card', 'support-card'];
    preview.className = 'modal-card-preview ' + (cardData.type || 'spell') + '-card';
    
    // Update preview elements
    preview.querySelector('.modal-card-cost').textContent = cardData.cost || '0';
    preview.querySelector('.modal-card-name').textContent = cardData.name || 'Unknown Card';
    preview.querySelector('.modal-card-type').textContent = (cardData.type || 'unknown').toUpperCase();
    preview.querySelector('.modal-card-damage').textContent = cardData.damage > 0 ? `💥 ${cardData.damage}` : '';
    preview.querySelector('.modal-card-description').textContent = cardData.description || 'No description available';
    
    const rarityElement = preview.querySelector('.modal-card-rarity');
    rarityElement.textContent = (cardData.rarity || 'common').charAt(0).toUpperCase() + (cardData.rarity || 'common').slice(1);
    rarityElement.className = 'modal-card-rarity ' + (cardData.rarity || 'common') + '-rarity';
    
    // Update information panel
    document.getElementById('modalCardName').textContent = cardData.name || 'Unknown';
    document.getElementById('modalCardType').textContent = (cardData.type || 'Unknown').charAt(0).toUpperCase() + (cardData.type || 'Unknown').slice(1);
    document.getElementById('modalCardCost').textContent = cardData.cost || '0';
    document.getElementById('modalCardDamage').textContent = cardData.damage || '0';
    document.getElementById('modalCardRarity').textContent = (cardData.rarity || 'Unknown').charAt(0).toUpperCase() + (cardData.rarity || 'Unknown').slice(1);
    document.getElementById('modalCardDescription').textContent = cardData.description || 'No description available';
    document.getElementById('modalCardCreated').textContent = cardData.created_at || 'Unknown';
    document.getElementById('modalCardCreator').textContent = cardData.created_by || 'Unknown';
    document.getElementById('modalCardId').textContent = cardData.id || 'Unknown';
    
    // Show modal
    document.getElementById('cardDetailModal').classList.add('active');
    document.getElementById('cardDetailOverlay').classList.add('active');
}

function closeCardDetails() {
    document.getElementById('cardDetailModal').classList.remove('active');
    document.getElementById('cardDetailOverlay').classList.remove('active');
}

function playCard() {
    // Log card play action
    console.log('Playing card');
    // TODO: Implement card play mechanics
    closeCardDetails();
}

// Card Creator JavaScript Functions
function toggleCardCreator() {
    const panel = document.getElementById('cardCreatorPanel');
    const overlay = document.getElementById('cardCreatorOverlay');
    
    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        panel.classList.add('active');
        overlay.classList.add('active');
    }
}

function updateCardPreview() {
    const preview = document.getElementById('cardPreview');
    const name = document.getElementById('cardName').value || 'New Card';
    const cost = document.getElementById('cardCost').value || '0';
    const type = document.getElementById('cardType').value || 'spell';
    const damage = document.getElementById('cardDamage').value || '0';
    const description = document.getElementById('cardDescription').value || 'Card description...';
    const rarity = document.getElementById('cardRarity').value || 'common';
    
    // Update preview card
    preview.querySelector('.preview-cost').textContent = cost;
    preview.querySelector('.preview-name').textContent = name;
    preview.querySelector('.preview-type').textContent = type.toUpperCase();
    preview.querySelector('.preview-damage').textContent = damage > 0 ? `💥 ${damage}` : '';
    preview.querySelector('.preview-description').textContent = description;
    preview.querySelector('.preview-rarity').textContent = rarity.charAt(0).toUpperCase() + rarity.slice(1);
    
    // Update card styling based on type
    preview.className = `preview-card ${type}-card`;
    preview.querySelector('.preview-rarity').className = `preview-rarity ${rarity}-rarity`;
}

function resetCardForm() {
    document.getElementById('cardCreatorForm').reset();
    document.getElementById('cardCost').value = '3';
    document.getElementById('cardDamage').value = '5';
    updateCardPreview();
}

function saveCard() {
    const cardData = {
        name: document.getElementById('cardName').value,
        cost: document.getElementById('cardCost').value,
        type: document.getElementById('cardType').value,
        damage: document.getElementById('cardDamage').value,
        description: document.getElementById('cardDescription').value,
        rarity: document.getElementById('cardRarity').value
    };
    
    // Validate required fields
    if (!cardData.name.trim()) {
        alert('Please enter a card name');
        return;
    }
    
    // Prepare form data for submission
    const formData = new FormData();
    formData.append('action', 'save_card');
    formData.append('name', cardData.name);
    formData.append('cost', cardData.cost);
    formData.append('type', cardData.type);
    formData.append('damage', cardData.damage);
    formData.append('description', cardData.description);
    formData.append('rarity', cardData.rarity);
    
    // Send to server
    fetch('card-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Card saved successfully!\n\nCard ID: ' + data.data.id + '\nName: ' + data.data.name);
            // Reset form after successful save
            resetCardForm();
            // Update card library
            loadCardLibrary();
            // Reload the page to show new card in hand
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Error saving card: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error while saving card. Please try again.');
    });
}

function loadCardLibrary() {
    // Load saved cards from server
    fetch('card-manager.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_all_cards'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCardLibrary(data.data);
            updateCardCount(data.data.length);
        }
    })
    .catch(error => {
        console.error('Error loading cards:', error);
        document.getElementById('cardLibrary').innerHTML = '<div class="library-error">Error loading cards</div>';
    });
}

function displayCardLibrary(cards) {
    const libraryContainer = document.getElementById('cardLibrary');
    
    if (cards.length === 0) {
        libraryContainer.innerHTML = '<div class="library-empty">No cards created yet. Create your first card above!</div>';
        return;
    }
    
    let html = '';
    cards.forEach(card => {
        html += `
            <div class="library-card ${card.type}-card" data-card-id="${card.id}">
                <div class="library-card-header">
                    <span class="library-card-name">${card.name}</span>
                    <span class="library-card-cost">${card.cost}</span>
                </div>
                <div class="library-card-type">${card.type.toUpperCase()}</div>
                <div class="library-card-damage">${card.damage > 0 ? '💥 ' + card.damage : ''}</div>
                <div class="library-card-description">${card.description}</div>
                <div class="library-card-footer">
                    <span class="library-card-rarity ${card.rarity}-rarity">${card.rarity}</span>
                    <div class="library-card-actions">
                        <button onclick="editCard('${card.id}')" class="edit-btn">✏️</button>
                        <button onclick="deleteCard('${card.id}')" class="delete-btn">🗑️</button>
                    </div>
                </div>
            </div>
        `;
    });
    
    libraryContainer.innerHTML = html;
}

function updateCardCount(count) {
    document.getElementById('cardCount').textContent = count + (count === 1 ? ' card' : ' cards');
}

function editCard(cardId) {
    // TODO: Load card data into form for editing
    alert('Edit card feature coming soon! Card ID: ' + cardId);
}

function deleteCard(cardId) {
    if (confirm('Are you sure you want to delete this card?')) {
        const formData = new FormData();
        formData.append('action', 'delete_card');
        formData.append('card_id', cardId);
        
        fetch('card-manager.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCardLibrary(); // Refresh the library
                // Reload page to update hand display
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Error deleting card: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Network error while deleting card.');
        });
    }
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCardPreview();
    loadCardLibrary(); // Load existing cards when page loads
});
</script>

</body>
</html>