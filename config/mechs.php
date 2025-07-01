<?php
// ===================================================================
// NRD SANDBOX - MECH CONFIGURATION & IMAGE UPLOAD INTERFACE
// ===================================================================
require '../auth.php';

// Initialize mech data from session with complete defaults
$defaultPlayerMech = [
    'HP' => 100, 
    'ATK' => 30, 
    'DEF' => 15, 
    'MAX_HP' => 100, 
    'companion' => 'Pilot-Alpha',
    'name' => 'Player Mech',
    'image' => null
];

$defaultEnemyMech = [
    'HP' => 100, 
    'ATK' => 25, 
    'DEF' => 10, 
    'MAX_HP' => 100, 
    'companion' => 'AI-Core',
    'name' => 'Enemy Mech',
    'image' => null
];

// Merge session data with defaults to ensure all keys exist
$playerMech = array_merge($defaultPlayerMech, $_SESSION['playerMech'] ?? []);
$enemyMech = array_merge($defaultEnemyMech, $_SESSION['enemyMech'] ?? []);

$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            
            case 'update_player_mech':
                $playerMech['name'] = trim($_POST['player_name'] ?? 'Player Mech');
                $playerMech['companion'] = trim($_POST['player_companion'] ?? 'Pilot-Alpha');
                $playerMech['HP'] = intval($_POST['player_hp'] ?? 100);
                $playerMech['MAX_HP'] = intval($_POST['player_max_hp'] ?? 100);
                $playerMech['ATK'] = intval($_POST['player_atk'] ?? 30);
                $playerMech['DEF'] = intval($_POST['player_def'] ?? 15);
                
                // Handle image upload
                if (!empty($_POST['player_image_data'])) {
                    $imagePath = saveMechImage('player', $_POST['player_image_data']);
                    if ($imagePath) {
                        $playerMech['image'] = $imagePath;
                    }
                }
                
                $_SESSION['playerMech'] = $playerMech;
                $successMessage = 'Player mech updated successfully!';
                break;
                
            case 'update_enemy_mech':
                $enemyMech['name'] = trim($_POST['enemy_name'] ?? 'Enemy Mech');
                $enemyMech['companion'] = trim($_POST['enemy_companion'] ?? 'AI-Core');
                $enemyMech['HP'] = intval($_POST['enemy_hp'] ?? 100);
                $enemyMech['MAX_HP'] = intval($_POST['enemy_max_hp'] ?? 100);
                $enemyMech['ATK'] = intval($_POST['enemy_atk'] ?? 25);
                $enemyMech['DEF'] = intval($_POST['enemy_def'] ?? 10);
                
                // Handle image upload
                if (!empty($_POST['enemy_image_data'])) {
                    $imagePath = saveMechImage('enemy', $_POST['enemy_image_data']);
                    if ($imagePath) {
                        $enemyMech['image'] = $imagePath;
                    }
                }
                
                $_SESSION['enemyMech'] = $enemyMech;
                $successMessage = 'Enemy mech updated successfully!';
                break;
                
            case 'remove_player_image':
                if (!empty($playerMech['image']) && file_exists('../' . $playerMech['image'])) {
                    unlink('../' . $playerMech['image']);
                }
                $playerMech['image'] = null;
                $_SESSION['playerMech'] = $playerMech;
                $successMessage = 'Player mech image removed!';
                break;
                
            case 'remove_enemy_image':
                if (!empty($enemyMech['image']) && file_exists('../' . $enemyMech['image'])) {
                    unlink('../' . $enemyMech['image']);
                }
                $enemyMech['image'] = null;
                $_SESSION['enemyMech'] = $enemyMech;
                $successMessage = 'Enemy mech image removed!';
                break;
        }
    }
}

/**
 * Save mech image from base64 data
 */
function saveMechImage($mechType, $imageData) {
    try {
        // Ensure images directory exists
        $imageDir = '../data/images/mechs';
        if (!file_exists($imageDir)) {
            mkdir($imageDir, 0755, true);
        }
        
        // Decode base64 image data
        if (strpos($imageData, 'data:image/') === 0) {
            $imageInfo = explode(',', $imageData);
            $imageType = $imageInfo[0];
            $imageData = $imageInfo[1];
            
            // Get file extension from MIME type
            $extension = 'jpg'; // default
            if (strpos($imageType, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($imageType, 'gif') !== false) {
                $extension = 'gif';
            } elseif (strpos($imageType, 'webp') !== false) {
                $extension = 'webp';
            }
            
            $fileName = $mechType . '_mech.' . $extension;
            $filePath = $imageDir . '/' . $fileName;
            $relativePath = 'data/images/mechs/' . $fileName;
            
            // Decode and save the image
            $decodedData = base64_decode($imageData);
            if ($decodedData && file_put_contents($filePath, $decodedData)) {
                return $relativePath;
            }
        }
    } catch (Exception $e) {
        error_log('Error saving mech image: ' . $e->getMessage());
    }
    
    return null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mech Configuration - NRD Sandbox</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .battlefield-container {
            overflow-y: auto !important;
            height: auto !important;
            min-height: 100vh;
        }
        
        .mech-config-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .mech-panel {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .mech-header {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px 20px;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mech-header h2 {
            color: #00d4ff;
            margin: 0;
            font-size: 18px;
        }
        
        .mech-content {
            padding: 20px;
        }
        
        .mech-preview {
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed #444;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 200px;
            position: relative;
        }
        
        .mech-preview.has-image {
            border-style: solid;
            border-color: #00d4ff;
        }
        
        .mech-image {
            max-width: 100%;
            max-height: 160px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        
        .mech-placeholder {
            color: #666;
            font-style: italic;
            text-align: center;
            margin: auto 0;
        }
        
        .mech-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
        }
        
        .stat-item {
            text-align: center;
            color: #ddd;
            font-size: 12px;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #00d4ff;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            color: #ddd;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #444;
            border-radius: 4px;
            padding: 8px 10px;
            color: #fff;
            font-size: 12px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.3);
        }
        
        .image-upload-section {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .image-upload-section h4 {
            color: #00d4ff;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .image-upload-hint {
            font-size: 11px;
            color: #888;
            margin-bottom: 10px;
            font-style: italic;
        }
        
        .image-preview {
            margin-top: 10px;
            border: 1px solid #444;
            border-radius: 8px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 120px;
            border-radius: 6px;
            display: block;
            margin: 0 auto;
        }
        
        .remove-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .remove-image-btn:hover {
            background: #c82333;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .btn-primary {
            background: linear-gradient(145deg, #00d4ff 0%, #0099cc 100%);
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
            font-size: 12px;
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .mech-config-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
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
            <a href="index.php" class="config-link">🎛️ Config Home</a>
        </div>
        <div class="nav-center">
            <h1 class="game-title">🤖 MECH CONFIGURATION</h1>
        </div>
        <div class="nav-right">
            <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
            <a href="../logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- Success/Error Messages -->
    <?php if ($successMessage): ?>
        <div class="success-message">
            ✅ <?= htmlspecialchars($successMessage) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
        <div class="error-message">
            ❌ <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="mech-config-container">
        
        <!-- Player Mech Configuration -->
        <div class="mech-panel">
            <div class="mech-header">
                <h2>🛡️ Player Mech</h2>
            </div>
            <div class="mech-content">
                
                <!-- Mech Preview -->
                <div class="mech-preview <?= !empty($playerMech['image']) ? 'has-image' : '' ?>">
                    <?php if (!empty($playerMech['image']) && file_exists('../' . $playerMech['image'])): ?>
                        <img src="../<?= htmlspecialchars($playerMech['image']) ?>" alt="Player Mech" class="mech-image">
                        <div style="font-size: 12px; color: #00d4ff; font-weight: bold;">
                            <?= htmlspecialchars($playerMech['name'] ?? 'Player Mech') ?>
                        </div>
                    <?php else: ?>
                        <div class="mech-placeholder">
                            🤖<br>
                            <strong><?= htmlspecialchars($playerMech['name'] ?? 'Player Mech') ?></strong><br>
                            <small>No image uploaded</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mech-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= $playerMech['HP'] ?>/<?= $playerMech['MAX_HP'] ?></div>
                            <div>HP</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $playerMech['ATK'] ?></div>
                            <div>ATK</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $playerMech['DEF'] ?></div>
                            <div>DEF</div>
                        </div>
                    </div>
                </div>
                
                <!-- Player Mech Form -->
                <form method="post" id="playerMechForm">
                    <input type="hidden" name="action" value="update_player_mech">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Mech Name:</label>
                            <input type="text" name="player_name" class="form-input" value="<?= htmlspecialchars($playerMech['name'] ?? 'Player Mech') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Companion:</label>
                            <input type="text" name="player_companion" class="form-input" value="<?= htmlspecialchars($playerMech['companion'] ?? 'Pilot-Alpha') ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Current HP:</label>
                            <input type="number" name="player_hp" class="form-input" min="0" max="999" value="<?= $playerMech['HP'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max HP:</label>
                            <input type="number" name="player_max_hp" class="form-input" min="1" max="999" value="<?= $playerMech['MAX_HP'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Attack:</label>
                            <input type="number" name="player_atk" class="form-input" min="0" max="999" value="<?= $playerMech['ATK'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Defense:</label>
                            <input type="number" name="player_def" class="form-input" min="0" max="999" value="<?= $playerMech['DEF'] ?>">
                        </div>
                    </div>
                    
                    <div class="image-upload-section">
                        <h4>🖼️ Mech Image</h4>
                        <div class="image-upload-hint">
                            Recommended: 400x300px (PNG/JPG, max 2MB)
                        </div>
                        <input type="file" id="playerImageInput" accept="image/*" onchange="handleMechImageUpload(this, 'player')">
                        <input type="hidden" name="player_image_data" id="playerImageData">
                        
                        <div id="playerImagePreview" class="image-preview" style="display: none;">
                            <img id="playerPreviewImg" src="" alt="Player Mech Preview">
                            <button type="button" onclick="removeMechImage('player')" class="remove-image-btn">✕</button>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Update Player Mech</button>
                        <?php if (!empty($playerMech['image'])): ?>
                            <button type="button" onclick="removeExistingImage('player')" class="btn btn-danger">🗑️ Remove Image</button>
                        <?php endif; ?>
                    </div>
                </form>
                
            </div>
        </div>

        <!-- Enemy Mech Configuration -->
        <div class="mech-panel">
            <div class="mech-header">
                <h2>⚔️ Enemy Mech</h2>
            </div>
            <div class="mech-content">
                
                <!-- Mech Preview -->
                <div class="mech-preview <?= !empty($enemyMech['image']) ? 'has-image' : '' ?>">
                    <?php if (!empty($enemyMech['image']) && file_exists('../' . $enemyMech['image'])): ?>
                        <img src="../<?= htmlspecialchars($enemyMech['image']) ?>" alt="Enemy Mech" class="mech-image">
                        <div style="font-size: 12px; color: #dc3545; font-weight: bold;">
                            <?= htmlspecialchars($enemyMech['name'] ?? 'Enemy Mech') ?>
                        </div>
                    <?php else: ?>
                        <div class="mech-placeholder">
                            🤖<br>
                            <strong><?= htmlspecialchars($enemyMech['name'] ?? 'Enemy Mech') ?></strong><br>
                            <small>No image uploaded</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mech-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?= $enemyMech['HP'] ?>/<?= $enemyMech['MAX_HP'] ?></div>
                            <div>HP</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $enemyMech['ATK'] ?></div>
                            <div>ATK</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $enemyMech['DEF'] ?></div>
                            <div>DEF</div>
                        </div>
                    </div>
                </div>
                
                <!-- Enemy Mech Form -->
                <form method="post" id="enemyMechForm">
                    <input type="hidden" name="action" value="update_enemy_mech">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Mech Name:</label>
                            <input type="text" name="enemy_name" class="form-input" value="<?= htmlspecialchars($enemyMech['name'] ?? 'Enemy Mech') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Companion:</label>
                            <input type="text" name="enemy_companion" class="form-input" value="<?= htmlspecialchars($enemyMech['companion'] ?? 'AI-Core') ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Current HP:</label>
                            <input type="number" name="enemy_hp" class="form-input" min="0" max="999" value="<?= $enemyMech['HP'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max HP:</label>
                            <input type="number" name="enemy_max_hp" class="form-input" min="1" max="999" value="<?= $enemyMech['MAX_HP'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Attack:</label>
                            <input type="number" name="enemy_atk" class="form-input" min="0" max="999" value="<?= $enemyMech['ATK'] ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Defense:</label>
                            <input type="number" name="enemy_def" class="form-input" min="0" max="999" value="<?= $enemyMech['DEF'] ?>">
                        </div>
                    </div>
                    
                    <div class="image-upload-section">
                        <h4>🖼️ Mech Image</h4>
                        <div class="image-upload-hint">
                            Recommended: 400x300px (PNG/JPG, max 2MB)
                        </div>
                        <input type="file" id="enemyImageInput" accept="image/*" onchange="handleMechImageUpload(this, 'enemy')">
                        <input type="hidden" name="enemy_image_data" id="enemyImageData">
                        
                        <div id="enemyImagePreview" class="image-preview" style="display: none;">
                            <img id="enemyPreviewImg" src="" alt="Enemy Mech Preview">
                            <button type="button" onclick="removeMechImage('enemy')" class="remove-image-btn">✕</button>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Update Enemy Mech</button>
                        <?php if (!empty($enemyMech['image'])): ?>
                            <button type="button" onclick="removeExistingImage('enemy')" class="btn btn-danger">🗑️ Remove Image</button>
                        <?php endif; ?>
                    </div>
                </form>
                
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="game-footer">
        <div class="build-info">
            Mech Configuration Interface | NRD Sandbox | Configure mech stats and images
        </div>
    </footer>
</div>

<script>
// Mech image upload handling
function handleMechImageUpload(input, mechType) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        alert('Please select a valid image file (PNG, JPG, etc.)');
        input.value = '';
        return;
    }
    
    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        alert('Image file is too large. Please select an image smaller than 2MB.');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const imageData = e.target.result;
        
        // Store image data in hidden field
        document.getElementById(mechType + 'ImageData').value = imageData;
        
        // Show preview
        const previewImg = document.getElementById(mechType + 'PreviewImg');
        const imagePreview = document.getElementById(mechType + 'ImagePreview');
        previewImg.src = imageData;
        imagePreview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function removeMechImage(mechType) {
    // Clear the file input and hidden data
    document.getElementById(mechType + 'ImageInput').value = '';
    document.getElementById(mechType + 'ImageData').value = '';
    
    // Hide preview
    document.getElementById(mechType + 'ImagePreview').style.display = 'none';
}

function removeExistingImage(mechType) {
    if (confirm('Are you sure you want to remove the current mech image?')) {
        // Create a form to remove the existing image
        const form = document.createElement('form');
        form.method = 'post';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'remove_' + mechType + '_image';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>