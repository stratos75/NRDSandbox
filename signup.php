<?php
// ===================================================================
// NRD SANDBOX - HIDDEN USER REGISTRATION
// Access via: /signup.php?access_key=nrd_admin_2024
// ===================================================================

session_start();

// Include database classes
require_once __DIR__ . '/database/User.php';
require_once __DIR__ . '/config.php';

// Hidden access control
$accessKey = $_GET['access_key'] ?? '';
$validAccessKey = 'nrd_admin_2024'; // Change this to something secure

if ($accessKey !== $validAccessKey) {
    // Redirect to login if no valid access key
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$userManager = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $displayName = trim($_POST['display_name'] ?? '');
    $pilotCallsign = trim($_POST['pilot_callsign'] ?? '');
    $role = $_POST['role'] ?? 'user';

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Username, email, and password are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } else {
        try {
            $userData = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'display_name' => $displayName ?: $username,
                'pilot_callsign' => $pilotCallsign ?: $username,
                'role' => $role,
                'status' => 'active'
            ];

            $userId = $userManager->createUser($userData);
            
            if ($userId) {
                $success = "User account created successfully! User ID: {$userId}";
                // Clear form
                $username = $email = $password = $confirmPassword = $displayName = $pilotCallsign = '';
            } else {
                $error = 'Failed to create user account. Username or email may already exist.';
            }
        } catch (Exception $e) {
            error_log("Signup error: " . $e->getMessage());
            $error = 'Registration system temporarily unavailable. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Registration - NRDSandbox Platform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="login-container">
    <div class="login-panel">
      <div class="login-header">
        <h1 class="login-title">ADMIN REGISTRATION</h1>
        <p class="login-subtitle">Create New Pilot Account</p>
      </div>
      
      <div class="login-form-container">
        <h2 class="login-form-title">NEW PILOT REGISTRATION</h2>
        
        <?php if ($error): ?>
          <div class="login-error">
            <span class="error-icon">⚠️</span>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="login-success">
            <span class="success-icon">✅</span>
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        
        <form method="post" class="login-form">
          <div class="input-group">
            <label for="username" class="input-label">USERNAME *</label>
            <input type="text" id="username" name="username" class="login-input" 
                   value="<?= htmlspecialchars($username ?? '') ?>" required>
            <small class="input-help">Letters, numbers, and underscores only</small>
          </div>
          
          <div class="input-group">
            <label for="email" class="input-label">EMAIL ADDRESS *</label>
            <input type="email" id="email" name="email" class="login-input" 
                   value="<?= htmlspecialchars($email ?? '') ?>" required>
          </div>
          
          <div class="input-group">
            <label for="display_name" class="input-label">DISPLAY NAME</label>
            <input type="text" id="display_name" name="display_name" class="login-input" 
                   value="<?= htmlspecialchars($displayName ?? '') ?>">
            <small class="input-help">How your name appears in the game</small>
          </div>
          
          <div class="input-group">
            <label for="pilot_callsign" class="input-label">PILOT CALLSIGN</label>
            <input type="text" id="pilot_callsign" name="pilot_callsign" class="login-input" 
                   value="<?= htmlspecialchars($pilotCallsign ?? '') ?>">
            <small class="input-help">Your tactical combat identifier</small>
          </div>
          
          <div class="input-group">
            <label for="role" class="input-label">USER ROLE</label>
            <select id="role" name="role" class="login-input">
              <option value="user" <?= ($role ?? '') === 'user' ? 'selected' : '' ?>>User</option>
              <option value="tester" <?= ($role ?? '') === 'tester' ? 'selected' : '' ?>>Tester</option>
              <option value="developer" <?= ($role ?? '') === 'developer' ? 'selected' : '' ?>>Developer</option>
              <option value="admin" <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
          </div>
          
          <div class="input-group">
            <label for="password" class="input-label">PASSWORD *</label>
            <input type="password" id="password" name="password" class="login-input" required>
            <small class="input-help">Minimum 8 characters</small>
          </div>
          
          <div class="input-group">
            <label for="confirm_password" class="input-label">CONFIRM PASSWORD *</label>
            <input type="password" id="confirm_password" name="confirm_password" class="login-input" required>
          </div>
          
          <button type="submit" class="login-button">
            <span class="button-icon">👨‍✈️</span>
            CREATE PILOT ACCOUNT
          </button>
        </form>
        
        <div class="login-footer">
          <p class="login-hint">
            <a href="login.php" style="color: #00d4ff;">← Back to Login</a>
          </p>
          <p class="version-info">Admin Registration • NRDSandbox Platform</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>