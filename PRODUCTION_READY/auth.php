<?php
// auth.php - MySQL-based Authentication

// Include environment configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/User.php';

// ✅ Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    // Redirect to login
    header('Location: ' . url('login.php'));
    exit;
}

// Validate session if session_id is present
if (isset($_SESSION['session_id'])) {
    try {
        $userManager = new User();
        $sessionUser = $userManager->validateSession($_SESSION['session_id']);
        
        if (!$sessionUser) {
            // Invalid session, clear and redirect
            session_destroy();
            header('Location: ' . url('login.php'));
            exit;
        }
        
        // Update session data with fresh user info
        $_SESSION['user_id'] = $sessionUser['id'];
        $_SESSION['username'] = $sessionUser['username'];
        $_SESSION['display_name'] = $sessionUser['display_name'];
        $_SESSION['role'] = $sessionUser['role'];
        
    } catch (Exception $e) {
        // Database error, but don't break the app - just log it
        error_log("Session validation error: " . $e->getMessage());
        
        // For development, you might want to show the error
        if (ENVIRONMENT === 'development') {
            echo "<!-- Session validation warning: " . htmlspecialchars($e->getMessage()) . " -->";
        }
    }
}

// Ensure minimum required session data exists
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // Missing essential data, force re-login
    session_destroy();
    header('Location: ' . url('login.php'));
    exit;
}

