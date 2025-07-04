<?php
session_start();

// Include database classes
require_once __DIR__ . '/database/User.php';

$error = '';
$userManager = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            // Attempt authentication
            $user = $userManager->authenticate($username, $password);
            
            if ($user) {
                // Create session
                $sessionId = $userManager->createSession(
                    $user['id'], 
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                );
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['session_id'] = $sessionId;
                $_SESSION['authenticated'] = true;
                
                // Redirect to main application
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login system temporarily unavailable. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - NRDSandbox Tactical Platform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-panel">
      <div class="login-header">
        <h1 class="login-title">NRDSANDBOX TACTICAL</h1>
        <p class="login-subtitle">Tactical Card Battle Development Platform</p>
      </div>
      
      <div class="login-form-container">
        <h2 class="login-form-title">PILOT AUTHENTICATION</h2>
        
        <?php if ($error): ?>
          <div class="login-error">
            <span class="error-icon">⚠️</span>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
          <div class="input-group">
            <label for="username" class="input-label">PILOT ID</label>
            <input type="text" id="username" name="username" class="login-input" required autocomplete="username">
          </div>
          
          <div class="input-group">
            <label for="password" class="input-label">ACCESS CODE</label>
            <input type="password" id="password" name="password" class="login-input" required autocomplete="current-password">
          </div>
          
          <button type="submit" class="login-button">
            <span class="button-icon">🚀</span>
            ENTER SIMULATION
          </button>
        </form>
        
        <div class="login-footer">
          <p class="login-hint">Default: admin / password123</p>
          <p class="version-info">v1.0 • Tactical Combat Simulator</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
