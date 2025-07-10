<?php
/**
 * Story Selection Interface for NRDSandbox Arrow Integration
 * Main interface for selecting, importing, and managing stories
 */

require_once '../auth.php';
require_once 'StoryManager.php';
require_once 'processor.php';

$storyManager = new StoryManager();
$processor = new ArrowProcessor();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'import_story':
            if (isset($_FILES['story_file']) && $_FILES['story_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/exports/';
                $uploadFile = $uploadDir . basename($_FILES['story_file']['name']);
                
                if (move_uploaded_file($_FILES['story_file']['tmp_name'], $uploadFile)) {
                    $result = $processor->processArrowExport($uploadFile);
                    
                    if ($result['success']) {
                        $message = "Story imported successfully: " . $result['title'];
                    } else {
                        $error = "Import failed: " . $result['error'];
                    }
                } else {
                    $error = "Failed to upload file";
                }
            } else {
                $error = "No file uploaded or upload error";
            }
            break;
            
        case 'delete_story':
            $storyId = $_POST['story_id'] ?? '';
            if ($storyId) {
                $dataFile = __DIR__ . '/data/' . $storyId . '.json';
                if (file_exists($dataFile)) {
                    unlink($dataFile);
                    $message = "Story deleted successfully";
                } else {
                    $error = "Story not found";
                }
            }
            break;
            
        case 'reset_progress':
            $storyId = $_POST['story_id'] ?? '';
            if ($storyId) {
                $storyManager->resetProgress($storyId);
                $message = "Progress reset successfully";
            }
            break;
    }
}

// Get available stories
$stories = $storyManager->getAvailableStories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Story Manager - NRDSandbox</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="story-styles.css">
    <style>
        .story-manager {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #e0e0e0;
            min-height: 100vh;
        }
        
        .story-manager h1 {
            color: #00d4ff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        
        .story-manager h2 {
            color: #00d4ff;
            border-bottom: 2px solid #00d4ff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .story-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .story-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .story-card:hover {
            border-color: #00d4ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 212, 255, 0.2);
        }
        
        .story-card h3 {
            color: #00d4ff;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .story-card p {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .story-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            color: #999;
            margin-bottom: 15px;
        }
        
        .story-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0099cc 0%, #007aa3 100%);
        }
        
        .btn-secondary {
            background: #555;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #666;
        }
        
        .btn-danger {
            background: #cc0000;
            color: white;
        }
        
        .btn-danger:hover {
            background: #aa0000;
        }
        
        .import-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 40px;
        }
        
        .upload-area {
            border: 2px dashed #00d4ff;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            background: rgba(0, 212, 255, 0.1);
        }
        
        .upload-area input[type="file"] {
            margin-bottom: 20px;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00cc00;
            color: #00ff00;
        }
        
        .message.error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #cc0000;
            color: #ff4444;
        }
        
        .nav-back {
            margin-bottom: 20px;
        }
        
        .nav-back a {
            color: #00d4ff;
            text-decoration: none;
            font-size: 1.1em;
        }
        
        .nav-back a:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state h3 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .progress-indicator {
            width: 100%;
            height: 6px;
            background: #333;
            border-radius: 3px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #00d4ff, #0099cc);
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="story-manager">
        <div class="nav-back">
            <a href="../index.php">← Back to Game</a>
        </div>
        
        <h1>📖 Story Manager</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Import Section -->
        <div class="import-section">
            <h2>Import Arrow Story</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="upload-area">
                    <h3>Upload Arrow HTML Export</h3>
                    <p>Select an Arrow HTML export file to import into NRDSandbox</p>
                    <input type="file" name="story_file" accept=".html,.htm" required>
                    <br>
                    <button type="submit" name="action" value="import_story" class="btn btn-primary">
                        Import Story
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Stories Section -->
        <div class="stories-section">
            <h2>Available Stories</h2>
            
            <?php if (empty($stories)): ?>
                <div class="empty-state">
                    <h3>No stories available</h3>
                    <p>Import an Arrow HTML export to get started with narrative experiences.</p>
                </div>
            <?php else: ?>
                <div class="story-grid">
                    <?php foreach ($stories as $story): ?>
                        <div class="story-card">
                            <h3><?= htmlspecialchars($story['title']) ?></h3>
                            <p><?= htmlspecialchars($story['description']) ?></p>
                            
                            <div class="story-meta">
                                <span>Nodes: <?= $story['nodes_count'] ?></span>
                                <span>Version: <?= htmlspecialchars($story['version']) ?></span>
                            </div>
                            
                            <div class="story-actions">
                                <a href="../index.php?start_story=<?= urlencode($story['id']) ?>" class="btn btn-primary">
                                    🎮 Play Story
                                </a>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="story_id" value="<?= htmlspecialchars($story['id']) ?>">
                                    <button type="submit" name="action" value="reset_progress" class="btn btn-secondary" 
                                            onclick="return confirm('Reset progress for this story?')">
                                        🔄 Reset Progress
                                    </button>
                                </form>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="story_id" value="<?= htmlspecialchars($story['id']) ?>">
                                    <button type="submit" name="action" value="delete_story" class="btn btn-danger"
                                            onclick="return confirm('Delete this story? This cannot be undone.')">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Help Section -->
        <div class="import-section">
            <h2>How to Use</h2>
            <p><strong>📖 <a href="ARROW_GUIDE.md" target="_blank" style="color: #00d4ff;">Complete Arrow Integration Guide</a></strong> - Detailed instructions for creating stories with Arrow</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4>1. Create in Arrow</h4>
                    <p>Use the Arrow visual editor to create your interactive narrative with decision trees, character dialogue, and branching storylines.</p>
                </div>
                <div>
                    <h4>2. Export HTML</h4>
                    <p>Export your completed story as an HTML document from Arrow. This creates a playable web version of your narrative.</p>
                </div>
                <div>
                    <h4>3. Import Here</h4>
                    <p>Upload the HTML file using the import form above. The system will automatically process and integrate it with the game.</p>
                </div>
                <div>
                    <h4>4. Play & Enjoy</h4>
                    <p>Your story is now available in the game! Choices will affect card rewards, equipment, and gameplay outcomes.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add some interactivity to the upload area
        document.querySelector('.upload-area').addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = 'rgba(0, 212, 255, 0.1)';
        });
        
        document.querySelector('.upload-area').addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
        });
        
        document.querySelector('.upload-area').addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const fileInput = this.querySelector('input[type="file"]');
                fileInput.files = files;
                
                // Update file name display
                const fileName = files[0].name;
                const p = this.querySelector('p');
                p.textContent = `Selected: ${fileName}`;
            }
        });
        
        // File input change handler
        document.querySelector('input[type="file"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const p = this.closest('.upload-area').querySelector('p');
                p.textContent = `Selected: ${file.name}`;
            }
        });
    </script>
</body>
</html>