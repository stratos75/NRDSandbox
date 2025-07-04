<?php
// ===================================================================
// NRD SANDBOX - ENVIRONMENT CONFIGURATION
// ===================================================================

// Environment detection
$isLocalhost = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']) ||
               (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

// Environment-specific configuration
if ($isLocalhost) {
    // Local development settings
    define('ENVIRONMENT', 'development');
    define('BASE_URL', '');
    define('BASE_PATH', '');
    
    // Debug settings for development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    
} else {
    // Production settings (DreamHost)
    define('ENVIRONMENT', 'production');
    define('BASE_URL', '/NRDSandbox');
    define('BASE_PATH', '/NRDSandbox');
    
    // Production error handling
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('log_errors_max_len', 1024);
}

// Common paths (work in both environments)
define('DATA_DIR', __DIR__ . '/data/');
define('CARDS_FILE', DATA_DIR . 'cards.json');
define('IMAGES_DIR', DATA_DIR . 'images/');
define('AUDIO_DIR', DATA_DIR . 'audio/');
define('CONFIG_DIR', __DIR__ . '/config/');

// Security settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_AUDIO_TYPES', ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg']);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', !$isLocalhost ? 1 : 0); // HTTPS in production only
ini_set('session.use_strict_mode', 1);

// CSRF protection helper
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function for safe file operations
function ensureDataDirectoryExists() {
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    if (!file_exists(IMAGES_DIR)) {
        mkdir(IMAGES_DIR, 0755, true);
    }
    
    if (!file_exists(AUDIO_DIR)) {
        mkdir(AUDIO_DIR, 0755, true);
    }
}

// URL helper function
function url($path = '') {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

// Debug information (only in development)
if (ENVIRONMENT === 'development') {
    $debugInfo = [
        'environment' => ENVIRONMENT,
        'server_name' => $_SERVER['SERVER_NAME'],
        'base_url' => BASE_URL,
        'data_dir' => DATA_DIR,
        'cards_file' => CARDS_FILE
    ];
}

// Initialize data directory
ensureDataDirectoryExists();
?>