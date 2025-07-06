<?php
// ===================================================================
// NRD SANDBOX - SINGLE IMAGE OPTIMIZATION TOOL
// Optimizes individual images for web performance
// Usage: php tools/optimize_single_image.php path/to/image.png
// ===================================================================

if ($argc < 2) {
    echo "Usage: php optimize_single_image.php <image_path>\n";
    echo "Example: php optimize_single_image.php data/images/new_card.png\n";
    exit(1);
}

$inputImage = $argv[1];
$baseDir = dirname(__DIR__); // Parent directory of tools/

// Check if input file exists
if (!file_exists($baseDir . '/' . $inputImage)) {
    echo "❌ Error: Image file not found: $inputImage\n";
    exit(1);
}

// Configuration
$optimizedDir = $baseDir . '/data/images/optimized';
$quality = 85;

// Determine target size based on image type
$targetSize = 256; // Default for cards
if (strpos($inputImage, '/mechs/') !== false) {
    $targetSize = 512; // Larger for mechs
}

// Generate output paths
$relativePath = str_replace('data/images/', '', $inputImage);
$outputDir = $optimizedDir . '/' . dirname($relativePath);
$outputBase = $optimizedDir . '/' . $relativePath;

// Create output directory if needed
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
    echo "📁 Created directory: $outputDir\n";
}

$fullInputPath = $baseDir . '/' . $inputImage;
$originalSize = filesize($fullInputPath);

echo "🔄 Optimizing: $inputImage\n";
echo "   Original size: " . formatBytes($originalSize) . "\n";
echo "   Target size: {$targetSize}x{$targetSize}px\n";

// Optimize PNG
$pngOutput = $outputBase;
$sipsCommand = "sips -s format png -s formatOptions 70 -Z {$targetSize} '{$fullInputPath}' --out '{$pngOutput}' 2>/dev/null";
exec($sipsCommand, $sipsResult, $sipsReturn);

if ($sipsReturn === 0 && file_exists($pngOutput)) {
    $pngSize = filesize($pngOutput);
    $pngSavings = round((($originalSize - $pngSize) / $originalSize) * 100, 1);
    echo "   ✅ PNG: " . formatBytes($pngSize) . " ({$pngSavings}% smaller)\n";
    
    // Create WebP version
    $webpOutput = str_replace('.png', '.webp', $outputBase);
    $webpCommand = "cwebp -q {$quality} -resize {$targetSize} {$targetSize} '{$fullInputPath}' -o '{$webpOutput}' 2>/dev/null";
    exec($webpCommand, $webpResult, $webpReturn);
    
    if ($webpReturn === 0 && file_exists($webpOutput)) {
        $webpSize = filesize($webpOutput);
        $webpSavings = round((($originalSize - $webpSize) / $originalSize) * 100, 1);
        echo "   ✅ WebP: " . formatBytes($webpSize) . " ({$webpSavings}% smaller)\n";
    } else {
        echo "   ⚠️  WebP conversion failed (cwebp not found?)\n";
    }
} else {
    echo "   ❌ PNG optimization failed\n";
    exit(1);
}

echo "✅ Optimization complete!\n";

function formatBytes($bytes, $precision = 1) {
    $units = array('B', 'KB', 'MB', 'GB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>