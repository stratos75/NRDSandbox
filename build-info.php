<?php
// ===================================================================
// NRD SANDBOX - BUILD INFORMATION DISPLAY PAGE
// ===================================================================
require 'auth.php';

// Load build information from builds.php
$build = require 'builds.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Build Information - NRD Sandbox</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="battlefield-container">

    <!-- ===================================================================
         TOP NAVIGATION BAR
         =================================================================== -->
    <header class="top-bar">
        <div class="nav-left">
            <a href="index.php" class="config-link">🏠 Back to Game</a>
            <button type="button" class="config-link card-creator-btn" onclick="window.location.href='index.php'">🃏 Card Creator</button>
        </div>
        <div class="nav-center">
            <h1 class="game-title">BUILD INFORMATION</h1>
        </div>
        <div class="nav-right">
            <span class="version-badge"><?= htmlspecialchars($build['version']) ?></span>
            <a href="logout.php" class="logout-link">🚪 Logout</a>
        </div>
    </header>

    <!-- ===================================================================
         BUILD INFORMATION CONTENT
         =================================================================== -->
    <main class="build-content">
        
        <!-- Current Build Info -->
        <section class="build-section">
            <div class="build-card current-build">
                <div class="build-header">
                    <h2>📦 Current Build</h2>
                    <div class="build-badge"><?= htmlspecialchars($build['version']) ?></div>
                </div>
                
                <div class="build-details">
                    <div class="detail-row">
                        <span class="detail-label">Build Name:</span>
                        <span class="detail-value"><?= htmlspecialchars($build['build_name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Release Date:</span>
                        <span class="detail-value"><?= htmlspecialchars($build['date']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">PHP Required:</span>
                        <span class="detail-value"><?= htmlspecialchars($build['php_required']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Notes:</span>
                        <span class="detail-value"><?= htmlspecialchars($build['notes']) ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features List -->
        <section class="build-section">
            <div class="build-card">
                <div class="build-header">
                    <h2>🚀 Features</h2>
                </div>
                
                <div class="features-grid">
                    <?php foreach ($build['features'] as $feature): ?>
                        <div class="feature-item">
                            <span class="feature-icon">✅</span>
                            <span class="feature-text"><?= htmlspecialchars($feature) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Changelog -->
        <section class="build-section">
            <div class="build-card">
                <div class="build-header">
                    <h2>📝 Changelog</h2>
                </div>
                
                <div class="changelog-list">
                    <?php foreach ($build['changelog'] as $change): ?>
                        <div class="changelog-item">
                            <?php
                            // Color code based on change type
                            $type = '';
                            $icon = '';
                            if (strpos($change, 'FIXED:') === 0) {
                                $type = 'fix';
                                $icon = '🔧';
                            } elseif (strpos($change, 'ADDED:') === 0) {
                                $type = 'add';
                                $icon = '✨';
                            } elseif (strpos($change, 'IMPROVED:') === 0) {
                                $type = 'improve';
                                $icon = '⚡';
                            } elseif (strpos($change, 'PREPARED:') === 0) {
                                $type = 'prepare';
                                $icon = '🚀';
                            } elseif (strpos($change, 'REMOVED:') === 0) {
                                $type = 'remove';
                                $icon = '🗑️';
                            } elseif (strpos($change, 'CONSOLIDATED:') === 0) {
                                $type = 'consolidate';
                                $icon = '📦';
                            } elseif (strpos($change, 'FOCUSED:') === 0) {
                                $type = 'focus';
                                $icon = '🎯';
                            } else {
                                $type = 'general';
                                $icon = '📌';
                            }
                            ?>
                            <span class="change-icon"><?= $icon ?></span>
                            <span class="change-text <?= $type ?>"><?= htmlspecialchars($change) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Version History -->
        <section class="build-section">
            <div class="build-card">
                <div class="build-header">
                    <h2>📚 Version History</h2>
                </div>
                
                <div class="version-history">
                    <?php foreach ($build['previous_versions'] as $version => $description): ?>
                        <div class="version-item">
                            <div class="version-number"><?= htmlspecialchars($version) ?></div>
                            <div class="version-description"><?= htmlspecialchars($description) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

    </main>

    <!-- ===================================================================
         FOOTER
         =================================================================== -->
    <footer class="game-footer">
        <div class="build-info">
            NRD Tactical Sandbox | Build <?= htmlspecialchars($build['version']) ?> | 
            Released <?= htmlspecialchars($build['date']) ?> | <?= htmlspecialchars($build['build_name']) ?>
        </div>
    </footer>

</div>

<style>
/* Build page specific styles */
.build-content {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.build-section {
    margin-bottom: 25px;
}

.build-card {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid #333;
    border-radius: 12px;
    overflow: hidden;
}

.current-build {
    border-color: #28a745;
    box-shadow: 0 0 20px rgba(40, 167, 69, 0.2);
}

.build-header {
    background: rgba(0, 0, 0, 0.5);
    padding: 15px 20px;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.build-header h2 {
    color: #00d4ff;
    font-size: 18px;
    margin: 0;
}

.build-badge {
    background: #28a745;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.build-details {
    padding: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: bold;
    color: #aaa;
    min-width: 120px;
}

.detail-value {
    color: #fff;
    text-align: right;
    flex: 1;
}

.features-grid {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 10px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
}

.feature-icon {
    font-size: 14px;
}

.feature-text {
    color: #ddd;
    font-size: 14px;
}

.changelog-list {
    padding: 20px;
}

.changelog-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.changelog-item:last-child {
    border-bottom: none;
}

.change-icon {
    font-size: 16px;
    margin-top: 2px;
}

.change-text {
    color: #ddd;
    line-height: 1.4;
}

.change-text.fix {
    color: #ffc107;
}

.change-text.add {
    color: #28a745;
}

.change-text.improve {
    color: #17a2b8;
}

.change-text.prepare {
    color: #fd7e14;
}

.change-text.remove {
    color: #dc3545;
}

.change-text.consolidate {
    color: #6f42c1;
}

.change-text.focus {
    color: #20c997;
}

.version-history {
    padding: 20px;
}

.version-item {
    display: flex;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.version-item:last-child {
    border-bottom: none;
}

.version-number {
    color: #00d4ff;
    font-weight: bold;
    min-width: 80px;
    font-size: 14px;
}

.version-description {
    color: #ccc;
    font-size: 14px;
    line-height: 1.4;
}

@media (max-width: 768px) {
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .detail-value {
        text-align: left;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .version-item {
        flex-direction: column;
        gap: 5px;
    }
}
</style>

</body>
</html>