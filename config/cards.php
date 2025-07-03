<?php
// ===================================================================
// NRD SANDBOX - CARD MANAGEMENT CONFIGURATION
// ===================================================================
require '../auth.php';

// Simple version for display purposes
$version = 'v1.0';

// Load card data
$cardsFile = '../data/cards.json';
$cardLibrary = [];
$cardsData = null;

if (file_exists($cardsFile)) {
    $jsonContent = file_get_contents($cardsFile);
    $cardsData = json_decode($jsonContent, true);
    if ($cardsData && isset($cardsData['cards'])) {
        $cardLibrary = $cardsData['cards'];
    }
}

// Handle card operations via AJAX-style processing
$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle card creation/editing
    if (isset($_POST['action']) && $_POST['action'] === 'save_card') {
        $cardData = [
            'id' => $_POST['card_id'] ?? uniqid('card_'),
            'name' => trim($_POST['card_name'] ?? ''),
            'cost' => intval($_POST['card_cost'] ?? 0),
            'type' => $_POST['card_type'] ?? 'spell',
            'damage' => intval($_POST['card_damage'] ?? 0),
            'defense' => intval($_POST['card_defense'] ?? 0),
            'description' => trim($_POST['card_description'] ?? ''),
            'rarity' => $_POST['card_rarity'] ?? 'common',
            'element' => $_POST['card_element'] ?? 'fire',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['username'] ?? 'admin'
        ];
        
        // Handle image upload
        if (!empty($_POST['card_image_data'])) {
            $imageData = $_POST['card_image_data'];
            if (strpos($imageData, 'data:image/') === 0) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $imageData = base64_decode($imageData);
                
                $imagePath = 'data/images/card_' . $cardData['id'] . '.png';
                if (file_put_contents('../' . $imagePath, $imageData)) {
                    $cardData['image'] = $imagePath;
                }
            }
        }
        
        // Validate required fields
        if (empty($cardData['name'])) {
            $response['message'] = 'Card name is required';
        } else {
            // Add or update card
            $updated = false;
            for ($i = 0; $i < count($cardLibrary); $i++) {
                if ($cardLibrary[$i]['id'] === $cardData['id']) {
                    $cardLibrary[$i] = $cardData;
                    $updated = true;
                    break;
                }
            }
            
            if (!$updated) {
                $cardLibrary[] = $cardData;
            }
            
            // Save to file
            $saveData = [
                'cards' => $cardLibrary,
                'meta' => [
                    'created' => date('Y-m-d H:i:s'),
                    'version' => '1.1',
                    'total_cards' => count($cardLibrary),
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ];
            
            if (file_put_contents($cardsFile, json_encode($saveData, JSON_PRETTY_PRINT))) {
                $response['success'] = true;
                $response['message'] = $updated ? 'Card updated successfully!' : 'Card created successfully!';
                $response['data'] = $cardData;
            } else {
                $response['message'] = 'Failed to save card to file';
            }
        }
    }
    
    // Handle card deletion
    if (isset($_POST['action']) && $_POST['action'] === 'delete_card') {
        $cardId = $_POST['card_id'] ?? '';
        $found = false;
        
        for ($i = 0; $i < count($cardLibrary); $i++) {
            if ($cardLibrary[$i]['id'] === $cardId) {
                // Delete image if exists
                if (!empty($cardLibrary[$i]['image']) && file_exists('../' . $cardLibrary[$i]['image'])) {
                    unlink('../' . $cardLibrary[$i]['image']);
                }
                
                array_splice($cardLibrary, $i, 1);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            $saveData = [
                'cards' => $cardLibrary,
                'meta' => [
                    'created' => date('Y-m-d H:i:s'),
                    'version' => '1.1',
                    'total_cards' => count($cardLibrary),
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ];
            
            if (file_put_contents($cardsFile, json_encode($saveData, JSON_PRETTY_PRINT))) {
                $response['success'] = true;
                $response['message'] = 'Card deleted successfully!';
            } else {
                $response['message'] = 'Failed to save changes';
            }
        } else {
            $response['message'] = 'Card not found';
        }
    }
    
    // Redirect to prevent form resubmission
    if ($response['success']) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($response['message']));
        exit;
    }
}

// Get success message from redirect
$successMessage = isset($_GET['msg']) ? $_GET['msg'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Management - NRD Sandbox <?= htmlspecialchars($version) ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .battlefield-container {
            height: auto;
            min-height: 100vh;
            overflow-y: auto;
            padding-bottom: 50px;
        }
        
        .cards-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .card-creator-section, .card-library-section {
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
            color: #00d4ff;
            margin: 0;
            font-size: 20px;
        }
        
        .form-section {
            margin-bottom: 25px;
            background: rgba(0, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
        }
        
        .form-section h3 {
            color: #00d4ff;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            color: #fff;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 8px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid #666;
            border-radius: 4px;
            color: #fff;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #00d4ff;
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
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
        
        .btn-danger {
            background: linear-gradient(145deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(145deg, #ffc107 0%, #e0a800 100%);
            color: #000;
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
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .card-preview {
            background: linear-gradient(145deg, #2d4a87 0%, #1e3a5f 100%);
            border: 2px solid #333;
            border-radius: 12px;
            padding: 15px;
            width: 200px;
            height: 280px;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        .preview-cost {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: #ffc107;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .preview-name {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.8);
        }
        
        .preview-type {
            font-size: 11px;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            letter-spacing: 1px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .preview-art {
            height: 120px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-style: italic;
            font-size: 12px;
        }
        
        .preview-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .preview-damage, .preview-defense {
            font-size: 12px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            background: rgba(0, 0, 0, 0.4);
        }
        
        .preview-damage {
            color: #dc3545;
        }
        
        .preview-defense {
            color: #17a2b8;
        }
        
        .preview-description {
            font-size: 11px;
            text-align: center;
            line-height: 1.3;
            color: #ddd;
            margin-bottom: 8px;
            min-height: 40px;
        }
        
        .library-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .library-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.2s ease;
        }
        
        .library-card:hover {
            border-color: #00d4ff;
            transform: translateY(-2px);
        }
        
        .library-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .library-card-name {
            font-weight: bold;
            color: #fff;
            font-size: 16px;
        }
        
        .library-card-actions {
            display: flex;
            gap: 5px;
        }
        
        .library-card-actions .btn {
            padding: 5px 10px;
            font-size: 12px;
            margin: 0;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #00d4ff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: #aaa;
            text-transform: uppercase;
        }
        
        .image-upload {
            border: 2px dashed #666;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .image-upload:hover {
            border-color: #00d4ff;
            background: rgba(0, 212, 255, 0.1);
        }
        
        .image-upload input {
            display: none;
        }
        
        @media (max-width: 768px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .form-row, .form-row-3 {
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
            <h1 class="game-title">🃏 CARD MANAGEMENT</h1>
        </div>
        <div class="nav-right">
            <span class="version-badge"><?= htmlspecialchars($version) ?></span>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Success/Error Messages -->
    <?php if (!empty($successMessage)): ?>
        <div class="success-message">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$response['success'] && !empty($response['message'])): ?>
        <div class="error-message">
            ❌ <?= htmlspecialchars($response['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="cards-container">
        
        <!-- Card Creator Section -->
        <div class="card-creator-section">
            <div class="section-header">
                <h2>🎨 Card Creator</h2>
            </div>
            
            <form method="post" id="cardCreatorForm">
                <input type="hidden" name="action" value="save_card">
                <input type="hidden" name="card_id" id="cardId">
                <input type="hidden" name="card_image_data" id="cardImageData">
                
                <div class="form-section">
                    <h3>Basic Properties</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Card Name</label>
                        <input type="text" name="card_name" id="cardName" class="form-input" 
                               placeholder="Enter card name" oninput="updatePreview()" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="card_type" id="cardType" class="form-select" onchange="updatePreview()">
                                <option value="spell">Spell</option>
                                <option value="weapon">Weapon</option>
                                <option value="armor">Armor</option>
                                <option value="creature">Creature</option>
                                <option value="support">Support</option>
                                <option value="special">Special</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cost</label>
                            <input type="number" name="card_cost" id="cardCost" class="form-input" 
                                   min="0" max="20" value="1" oninput="updatePreview()">
                        </div>
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label class="form-label">Damage</label>
                            <input type="number" name="card_damage" id="cardDamage" class="form-input" 
                                   min="0" max="50" value="0" oninput="updatePreview()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Defense</label>
                            <input type="number" name="card_defense" id="cardDefense" class="form-input" 
                                   min="0" max="50" value="0" oninput="updatePreview()">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Rarity</label>
                            <select name="card_rarity" id="cardRarity" class="form-select" onchange="updatePreview()">
                                <option value="common">Common</option>
                                <option value="uncommon">Uncommon</option>
                                <option value="rare">Rare</option>
                                <option value="epic">Epic</option>
                                <option value="legendary">Legendary</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Description & Element</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="card_description" id="cardDescription" class="form-textarea" 
                                  placeholder="Enter card description or effect" oninput="updatePreview()"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Element</label>
                        <select name="card_element" id="cardElement" class="form-select" onchange="updatePreview()">
                            <option value="fire">Fire</option>
                            <option value="ice">Ice</option>
                            <option value="poison">Poison</option>
                            <option value="plasma">Plasma</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Card Image</h3>
                    <div class="image-upload" onclick="document.getElementById('imageInput').click()">
                        <input type="file" id="imageInput" accept="image/*" onchange="handleImageUpload(this)">
                        <div id="uploadText">📸 Click to upload card image</div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">💾 Save Card</button>
                    <button type="button" class="btn btn-warning" onclick="resetForm()">🔄 Reset Form</button>
                </div>
            </form>
            
            <!-- Live Preview -->
            <div class="form-section">
                <h3>Live Preview</h3>
                <div class="card-preview" id="cardPreview">
                    <div class="preview-cost" id="previewCost">1</div>
                    <div class="preview-name" id="previewName">Card Name</div>
                    <div class="preview-type" id="previewType">SPELL</div>
                    <div class="preview-art" id="previewArt">Card Art</div>
                    <div class="preview-stats">
                        <div class="preview-damage" id="previewDamage">DMG: 0</div>
                        <div class="preview-defense" id="previewDefense">DEF: 0</div>
                    </div>
                    <div class="preview-description" id="previewDescription">Enter card description...</div>
                </div>
            </div>
        </div>

        <!-- Card Library Section -->
        <div class="card-library-section">
            <div class="section-header">
                <h2>📚 Card Library</h2>
            </div>
            
            <!-- Statistics Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-value"><?= count($cardLibrary) ?></div>
                    <div class="stat-label">Total Cards</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_filter($cardLibrary, function($c) { return $c['type'] === 'spell'; })) ?></div>
                    <div class="stat-label">Spells</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_filter($cardLibrary, function($c) { return $c['type'] === 'weapon'; })) ?></div>
                    <div class="stat-label">Weapons</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_filter($cardLibrary, function($c) { return !empty($c['image']); })) ?></div>
                    <div class="stat-label">With Images</div>
                </div>
            </div>
            
            <!-- Card Library Grid -->
            <div class="library-grid">
                <?php foreach ($cardLibrary as $card): ?>
                    <div class="library-card">
                        <div class="library-card-header">
                            <div class="library-card-name"><?= htmlspecialchars($card['name']) ?></div>
                            <div class="library-card-actions">
                                <button type="button" class="btn btn-primary" onclick="editCard('<?= htmlspecialchars($card['id']) ?>')">✏️</button>
                                <button type="button" class="btn btn-danger" onclick="deleteCard('<?= htmlspecialchars($card['id']) ?>', '<?= htmlspecialchars($card['name']) ?>')">🗑️</button>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #aaa; margin-bottom: 8px;">
                            <strong>Type:</strong> <?= ucfirst($card['type']) ?> | 
                            <strong>Cost:</strong> <?= $card['cost'] ?> | 
                            <strong>Rarity:</strong> <?= ucfirst($card['rarity']) ?>
                        </div>
                        <?php if (($card['damage'] ?? 0) > 0 || ($card['defense'] ?? 0) > 0): ?>
                            <div style="font-size: 12px; color: #ddd; margin-bottom: 8px;">
                                <?php if (($card['damage'] ?? 0) > 0): ?>
                                    <span style="color: #dc3545;">⚔️ <?= $card['damage'] ?> DMG</span>
                                <?php endif; ?>
                                <?php if (($card['defense'] ?? 0) > 0): ?>
                                    <span style="color: #17a2b8;">🛡️ <?= $card['defense'] ?> DEF</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 11px; color: #bbb; line-height: 1.3;">
                            <?= htmlspecialchars(substr($card['description'], 0, 100)) ?>
                            <?= strlen($card['description']) > 100 ? '...' : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($cardLibrary)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; color: #aaa; padding: 40px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">🃏</div>
                        <div>No cards in library yet. Create your first card!</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            Card Management System | <?= htmlspecialchars($version) ?> | 
            Create, Edit & Manage Game Cards
        </div>
    </footer>
</div>

<script>
function updatePreview() {
    const name = document.getElementById('cardName').value || 'Card Name';
    const type = document.getElementById('cardType').value || 'spell';
    const cost = document.getElementById('cardCost').value || '0';
    const damage = document.getElementById('cardDamage').value || '0';
    const defense = document.getElementById('cardDefense').value || '0';
    const description = document.getElementById('cardDescription').value || 'Enter card description...';
    
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewType').textContent = type.toUpperCase();
    document.getElementById('previewCost').textContent = cost;
    document.getElementById('previewDamage').textContent = 'DMG: ' + damage;
    document.getElementById('previewDefense').textContent = 'DEF: ' + defense;
    document.getElementById('previewDescription').textContent = description;
    
    // Update element styling
    const element = document.getElementById('cardElement').value;
    const preview = document.getElementById('cardPreview');
    preview.className = 'card-preview ' + element + '-element';
}

function handleImageUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('cardImageData').value = e.target.result;
            document.getElementById('uploadText').innerHTML = '✅ Image uploaded: ' + file.name;
            
            // Show preview
            const previewArt = document.getElementById('previewArt');
            previewArt.style.backgroundImage = 'url(' + e.target.result + ')';
            previewArt.style.backgroundSize = 'cover';
            previewArt.style.backgroundPosition = 'center';
            previewArt.textContent = '';
        };
        
        reader.readAsDataURL(file);
    }
}

function resetForm() {
    document.getElementById('cardCreatorForm').reset();
    document.getElementById('cardId').value = '';
    document.getElementById('cardImageData').value = '';
    document.getElementById('uploadText').innerHTML = '📸 Click to upload card image';
    
    const previewArt = document.getElementById('previewArt');
    previewArt.style.backgroundImage = '';
    previewArt.textContent = 'Card Art';
    
    updatePreview();
}

function editCard(cardId) {
    // Find card data
    const cards = <?= json_encode($cardLibrary) ?>;
    const card = cards.find(c => c.id === cardId);
    
    if (card) {
        // Populate form
        document.getElementById('cardId').value = card.id;
        document.getElementById('cardName').value = card.name;
        document.getElementById('cardType').value = card.type;
        document.getElementById('cardCost').value = card.cost;
        document.getElementById('cardDamage').value = card.damage || 0;
        document.getElementById('cardDefense').value = card.defense || 0;
        document.getElementById('cardDescription').value = card.description;
        document.getElementById('cardRarity').value = card.rarity;
        document.getElementById('cardElement').value = card.element || 'fire';
        
        updatePreview();
        
        // Scroll to form
        document.querySelector('.card-creator-section').scrollIntoView({ behavior: 'smooth' });
    }
}

function deleteCard(cardId, cardName) {
    if (confirm('Are you sure you want to delete "' + cardName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_card"><input type="hidden" name="card_id" value="' + cardId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
});
</script>

</body>
</html>