<?php
session_start();

// Include database classes for session cleanup
require_once __DIR__ . '/database/User.php';

try {
    // Destroy database session if it exists
    if (isset($_SESSION['session_id'])) {
        $userManager = new User();
        $userManager->destroySession($_SESSION['session_id']);
    }
} catch (Exception $e) {
    // Log error but continue with logout
    error_log("Logout session cleanup error: " . $e->getMessage());
}

// Destroy PHP session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

