<?php
// ===================================================================
// PRODUCTION DEPLOYMENT PREPARATION SCRIPT
// Run this locally to prepare files for upload
// ===================================================================

echo "=== NRDSandbox Production Preparation ===\n";

// Create production directory
$prodDir = __DIR__ . '/PRODUCTION_READY';
if (!is_dir($prodDir)) {
    mkdir($prodDir, 0755, true);
    echo "✅ Created production directory: $prodDir\n";
}

// Files to copy to production
$productionFiles = [
    // Core application
    'index.php',
    'login.php', 
    'logout.php',
    'auth.php',
    'signup.php',
    'config.php',
    
    // Game logic
    'combat-manager.php',
    'card-manager.php',
    
    // Database (will be replaced with production version)
    'database/User.php',
    
    // Configuration interface
    'config/index.php',
    'config/mechs.php', 
    'config/cards.php',
    'config/debug.php',
    'config/shared.php',
    
    // Assets
    'style.css',
    'data/cards.json',
    
    // Security
    '.htaccess',
    'database/.htaccess'
];

// Directories to copy recursively
$productionDirs = [
    'data/images',
    'data/audio'
];

// Copy files
foreach ($productionFiles as $file) {
    $srcPath = __DIR__ . '/' . $file;
    $destPath = $prodDir . '/' . $file;
    
    // Create directory if needed
    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    if (file_exists($srcPath)) {
        copy($srcPath, $destPath);
        echo "✅ Copied: $file\n";
    } else {
        echo "⚠️  Missing: $file\n";
    }
}

// Copy directories
foreach ($productionDirs as $dir) {
    $srcPath = __DIR__ . '/' . $dir;
    $destPath = $prodDir . '/' . $dir;
    
    if (is_dir($srcPath)) {
        copyDirectory($srcPath, $destPath);
        echo "✅ Copied directory: $dir\n";
    } else {
        echo "⚠️  Missing directory: $dir\n";
    }
}

// Replace Database.php with production version
$prodDbPath = $prodDir . '/database/Database.php';
$srcDbPath = __DIR__ . '/database/Database_PRODUCTION.php';

if (file_exists($srcDbPath)) {
    copy($srcDbPath, $prodDbPath);
    echo "✅ Installed production Database.php\n";
} else {
    echo "❌ Production Database.php not found!\n";
}

// Create production-specific files
file_put_contents($prodDir . '/README_PRODUCTION.txt', 
"NRDSandbox Production Files
===========================

1. Upload ALL files in this directory to: newretrodawn.dev/NRDSandbox/
2. Ensure MySQL database 'nrdsb' exists with tables created
3. Test: https://newretrodawn.dev/NRDSandbox/login.php
4. Login with: admin / password123

Database credentials are in database/Database.php
");

echo "\n🚀 Production files ready in: $prodDir\n";
echo "📋 Next steps:\n";
echo "1. Upload ALL files from PRODUCTION_READY/ to newretrodawn.dev/NRDSandbox/\n";
echo "2. Test the login page\n";
echo "3. Verify database connection\n";

function copyDirectory($src, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $destPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }
        } else {
            copy($item, $destPath);
        }
    }
}
?>