<?php
// ===================================================================
// NRD SANDBOX - PRODUCTION SETUP SCRIPT
// Run this ONCE after uploading to newretrodawn.dev/nrdsandbox/
// ===================================================================

// Security: Only allow access from specific hosts or with secret key
$secretKey = $_GET['key'] ?? '';
$validKey = 'nrd_setup_2024'; // Change this!

if ($secretKey !== $validKey) {
    if (!in_array($_SERVER['HTTP_HOST'] ?? '', ['newretrodawn.dev', 'www.newretrodawn.dev'])) {
        die('Setup access denied. Use: setup_production.php?key=nrd_setup_2024');
    }
}

require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/User.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Setup - NRDSandbox</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container { max-width: 800px; margin: 50px auto; padding: 20px; }
        .setup-result { margin: 20px 0; padding: 15px; border-radius: 8px; }
        .success { background: rgba(0, 255, 100, 0.1); border: 1px solid #00cc66; color: #00ff66; }
        .error { background: rgba(255, 50, 50, 0.1); border: 1px solid #ff3333; color: #ff6666; }
        .info { background: rgba(0, 212, 255, 0.1); border: 1px solid #00d4ff; color: #00d4ff; }
        .setup-step { margin: 10px 0; padding: 10px; background: rgba(255, 255, 255, 0.05); border-radius: 4px; }
        pre { background: rgba(0, 0, 0, 0.3); padding: 10px; border-radius: 4px; overflow-x: auto; }
        .action-btn { 
            background: linear-gradient(135deg, #00d4ff, #0099cc); 
            color: #000; border: none; padding: 10px 20px; border-radius: 4px; 
            margin: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block;
        }
        .action-btn:hover { background: linear-gradient(135deg, #00b8e6, #007399); }
    </style>
</head>
<body style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); color: #fff; font-family: 'Segoe UI', sans-serif; min-height: 100vh;">
    <div class="setup-container">
        <h1 style="text-align: center; color: #00d4ff; margin-bottom: 30px;">
            🚀 NRDSandbox Production Setup
        </h1>
        
        <div class="setup-step">
            <h3>Environment Detection</h3>
            <?php
            $isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
            if ($isProduction) {
                echo "<div class='setup-result success'>✅ Production environment detected: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</div>";
            } else {
                echo "<div class='setup-result info'>ℹ️ Local development environment detected</div>";
            }
            ?>
        </div>

        <div class="setup-step">
            <h3>Database Connection Test</h3>
            <?php
            try {
                $db = Database::getInstance();
                $result = $db->testConnection();
                
                if ($result['status'] === 'success') {
                    echo "<div class='setup-result success'>";
                    echo "✅ Database connection successful!<br>";
                    echo "Host: " . htmlspecialchars($result['environment']['host']) . "<br>";
                    echo "Database: " . htmlspecialchars($result['environment']['database']) . "<br>";
                    echo "Driver: " . htmlspecialchars($result['environment']['driver']) . "<br>";
                    echo "</div>";
                } else {
                    echo "<div class='setup-result error'>";
                    echo "❌ Database connection failed: " . htmlspecialchars($result['message']);
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='setup-result error'>";
                echo "❌ Database error: " . htmlspecialchars($e->getMessage());
                echo "</div>";
            }
            ?>
        </div>

        <div class="setup-step">
            <h3>Schema Initialization</h3>
            <?php
            if (isset($db) && $result['status'] === 'success') {
                try {
                    $db->initializeSchema();
                    echo "<div class='setup-result success'>✅ Database schema initialized successfully!</div>";
                } catch (Exception $e) {
                    echo "<div class='setup-result error'>❌ Schema initialization failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                echo "<div class='setup-result error'>❌ Cannot initialize schema - database connection failed</div>";
            }
            ?>
        </div>

        <div class="setup-step">
            <h3>User System Test</h3>
            <?php
            if (isset($db) && $result['status'] === 'success') {
                try {
                    $userManager = new User();
                    $adminUser = $userManager->authenticate('admin', 'password123');
                    
                    if ($adminUser) {
                        echo "<div class='setup-result success'>";
                        echo "✅ User authentication working!<br>";
                        echo "Admin user ID: " . $adminUser['id'] . "<br>";
                        echo "Username: " . htmlspecialchars($adminUser['username']) . "<br>";
                        echo "Role: " . htmlspecialchars($adminUser['role']) . "<br>";
                        echo "</div>";
                    } else {
                        echo "<div class='setup-result error'>❌ Admin user authentication failed</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='setup-result error'>❌ User system error: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            } else {
                echo "<div class='setup-result error'>❌ Cannot test user system - database not ready</div>";
            }
            ?>
        </div>

        <div class="setup-step">
            <h3>Security Check</h3>
            <div class="setup-result info">
                ℹ️ Security recommendations:<br>
                • Change default admin password immediately<br>
                • Update signup access key in signup.php<br>
                • Delete this setup file after completion<br>
                • Enable HTTPS for production use<br>
                • Set up regular database backups
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="login.php" class="action-btn">🎮 Go to Login</a>
            <a href="signup.php?access_key=nrd_admin_2024" class="action-btn">👨‍✈️ Create Users</a>
            <a href="index.php" class="action-btn">🚀 Launch Sandbox</a>
        </div>

        <div class="setup-result info">
            <strong>Setup Complete!</strong><br>
            Your NRDSandbox is now running on MySQL in production mode. 
            The system will automatically detect the environment and use the appropriate database configuration.
        </div>
    </div>
</body>
</html>