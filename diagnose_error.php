<?php
// ===================================================================
// ERROR DIAGNOSTIC TOOL
// Access via: newretrodawn.dev/NRDSandbox/diagnose_error.php
// ===================================================================

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h2>NRDSandbox Error Diagnosis</h2>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#fff;} .error{color:#ff6666;} .success{color:#00ff66;} .info{color:#00d4ff;}</style>";

echo "<h3>1. PHP Environment Check</h3>";
echo "<div class='info'>PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "</div>";
echo "<div class='info'>Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "</div>";

echo "<h3>2. File Existence Check</h3>";
$criticalFiles = [
    'login.php',
    'auth.php',
    'config.php',
    'database/Database.php',
    'database/User.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists</div>";
    } else {
        echo "<div class='error'>❌ $file MISSING</div>";
    }
}

echo "<h3>3. Database Connection Test</h3>";
try {
    if (file_exists('database/Database.php')) {
        require_once 'database/Database.php';
        $db = Database::getInstance();
        $result = $db->testConnection();
        
        if ($result['status'] === 'success') {
            echo "<div class='success'>✅ Database connection successful</div>";
            echo "<div class='info'>Host: " . htmlspecialchars($result['environment']['host']) . "</div>";
            echo "<div class='info'>Database: " . htmlspecialchars($result['environment']['database']) . "</div>";
        } else {
            echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($result['message']) . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Database.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>4. User Authentication Test</h3>";
try {
    if (file_exists('database/User.php')) {
        require_once 'database/User.php';
        $userManager = new User();
        $user = $userManager->authenticate('admin', 'password123');
        
        if ($user) {
            echo "<div class='success'>✅ User authentication working</div>";
            echo "<div class='info'>Admin user found: " . htmlspecialchars($user['username']) . "</div>";
        } else {
            echo "<div class='error'>❌ Admin user authentication failed</div>";
        }
    } else {
        echo "<div class='error'>❌ User.php not found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ User system error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>5. Login Page Test</h3>";
try {
    // Capture output from login.php
    ob_start();
    $_SERVER['REQUEST_METHOD'] = 'GET'; // Simulate GET request
    include 'login.php';
    $loginOutput = ob_get_clean();
    
    if (strlen($loginOutput) > 100) {
        echo "<div class='success'>✅ Login page loads without fatal errors</div>";
        echo "<div class='info'>Output length: " . strlen($loginOutput) . " characters</div>";
    } else {
        echo "<div class='error'>❌ Login page output too short or empty</div>";
        echo "<div class='error'>Output: " . htmlspecialchars(substr($loginOutput, 0, 200)) . "</div>";
    }
} catch (ParseError $e) {
    echo "<div class='error'>❌ PHP Parse Error in login.php: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Login page error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>6. Session Test</h3>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "<div class='success'>✅ Sessions working</div>";
        echo "<div class='info'>Session ID: " . session_id() . "</div>";
    } else {
        echo "<div class='info'>Session already started</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<h3>7. File Permissions Check</h3>";
$filePerms = [
    'login.php' => is_readable('login.php'),
    'auth.php' => is_readable('auth.php'),
    'database/Database.php' => is_readable('database/Database.php'),
    'database/User.php' => is_readable('database/User.php')
];

foreach ($filePerms as $file => $readable) {
    if ($readable) {
        echo "<div class='success'>✅ $file is readable</div>";
    } else {
        echo "<div class='error'>❌ $file is NOT readable</div>";
    }
}

echo "<h3>8. Next Steps</h3>";
echo "<div class='info'>1. Fix any red ❌ errors shown above</div>";
echo "<div class='info'>2. Check server error logs if available</div>";
echo "<div class='info'>3. Verify all files uploaded correctly</div>";
echo "<div class='info'>4. Test login.php again after fixes</div>";

echo "<h3>9. Quick Links</h3>";
echo "<div class='info'><a href='login.php' style='color:#00d4ff;'>Test login.php</a></div>";
echo "<div class='info'><a href='setup_production.php?key=nrd_setup_2024' style='color:#00d4ff;'>Run setup again</a></div>";
echo "<div class='info'><a href='index.php' style='color:#00d4ff;'>Test main application</a></div>";
?>