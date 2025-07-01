<?php
// ===================================================================
// MECH IMAGE RESTORATION SCRIPT - Run once to restore session data
// ===================================================================
require 'auth.php';

$restored = false;
$message = '';

// Check if mech images exist but session data is missing
$playerImagePath = 'data/images/mechs/player_mech.png';
$enemyImagePath = 'data/images/mechs/enemy_mech.png';

$playerImageExists = file_exists($playerImagePath);
$enemyImageExists = file_exists($enemyImagePath);

$playerMech = $_SESSION['playerMech'] ?? [];
$enemyMech = $_SESSION['enemyMech'] ?? [];

$playerImageMissing = empty($playerMech['image']) && $playerImageExists;
$enemyImageMissing = empty($enemyMech['image']) && $enemyImageExists;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    // Restore player mech with image
    if ($playerImageExists) {
        $_SESSION['playerMech'] = [
            'HP' => 100, 
            'ATK' => 30, 
            'DEF' => 15, 
            'MAX_HP' => 100, 
            'companion' => 'Pilot-Alpha',
            'name' => 'Player Mech',
            'image' => $playerImagePath
        ];
        $restored = true;
    }
    
    // Restore enemy mech with image
    if ($enemyImageExists) {
        $_SESSION['enemyMech'] = [
            'HP' => 100, 
            'ATK' => 25, 
            'DEF' => 10, 
            'MAX_HP' => 100, 
            'companion' => 'AI-Core',
            'name' => 'Enemy Mech',
            'image' => $enemyImagePath
        ];
        $restored = true;
    }
    
    if ($restored) {
        $message = 'Mech images restored successfully!';
    } else {
        $message = 'No mech images found to restore.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mech Image Restoration - NRD Sandbox</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="battlefield-container">
        <header class="top-bar">
            <div class="nav-left">
                <a href="index.php" class="config-link">🏠 Back to Game</a>
                <a href="config/index.php" class="config-link">⚙️ Config</a>
            </div>
            <div class="nav-center">
                <h1 class="game-title">MECH IMAGE RESTORATION</h1>
            </div>
            <div class="nav-right">
                <span class="user-info">👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Unknown') ?></span>
            </div>
        </header>

        <div style="max-width: 800px; margin: 50px auto; padding: 20px; background: rgba(0, 0, 0, 0.8); border-radius: 10px;">
            <h2>Mech Image Status</h2>
            
            <?php if ($message): ?>
                <div style="padding: 15px; margin: 15px 0; background: rgba(0, 212, 255, 0.1); border: 1px solid #00d4ff; border-radius: 5px; color: #00d4ff;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <div>
                    <h3>Player Mech</h3>
                    <p><strong>Image File:</strong> <?= $playerImageExists ? '✅ Found' : '❌ Missing' ?></p>
                    <p><strong>Session Data:</strong> <?= !empty($playerMech['image']) ? '✅ Linked' : '❌ Missing' ?></p>
                    <?php if ($playerImageExists): ?>
                        <img src="<?= htmlspecialchars($playerImagePath) ?>" alt="Player Mech" style="max-width: 150px; border-radius: 5px; margin-top: 10px;">
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3>Enemy Mech</h3>
                    <p><strong>Image File:</strong> <?= $enemyImageExists ? '✅ Found' : '❌ Missing' ?></p>
                    <p><strong>Session Data:</strong> <?= !empty($enemyMech['image']) ? '✅ Linked' : '❌ Missing' ?></p>
                    <?php if ($enemyImageExists): ?>
                        <img src="<?= htmlspecialchars($enemyImagePath) ?>" alt="Enemy Mech" style="max-width: 150px; border-radius: 5px; margin-top: 10px;">
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($playerImageMissing || $enemyImageMissing): ?>
                <form method="POST" style="text-align: center; margin-top: 30px;">
                    <button type="submit" name="restore" class="btn-primary" style="padding: 15px 30px; font-size: 16px; background: #00d4ff; color: #000; border: none; border-radius: 5px; cursor: pointer;">
                        🔧 Restore Mech Images to Session
                    </button>
                </form>
                
                <p style="text-align: center; color: #aaa; margin-top: 15px;">
                    This will restore the link between existing mech image files and your session data.
                </p>
            <?php else: ?>
                <p style="text-align: center; color: #00d4ff; font-size: 18px; margin-top: 30px;">
                    ✅ All mech images are properly linked!
                </p>
                <p style="text-align: center; margin-top: 15px;">
                    <a href="index.php" class="config-link">Return to Game</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>