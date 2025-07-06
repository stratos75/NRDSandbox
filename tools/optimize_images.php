<?php
// ===================================================================
// NRD SANDBOX - IMAGE OPTIMIZATION SCRIPT
// Optimizes card images for web performance
// ===================================================================

echo "🎨 NRD Sandbox Image Optimization Tool\n";
echo "======================================\n\n";

// Configuration
$imageDir = __DIR__ . '/data/images';
$optimizedDir = __DIR__ . '/data/images/optimized';
$targetSizes = [
    'card' => 256,    // Card images: 256x256
    'mech' => 512,    // Mech images: 512x512  
];
$quality = 85; // JPEG/WebP quality

// Create optimized directory
if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
    echo "📁 Created optimized directory\n";
}

// Create subdirectories
$subdirs = ['mechs'];
foreach ($subdirs as $subdir) {
    $path = $optimizedDir . '/' . $subdir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "📁 Created $subdir subdirectory\n";
    }
}

/**
 * Optimize a single image
 */
function optimizeImage($inputPath, $outputPath, $maxSize, $quality) {
    $relativePath = str_replace(__DIR__ . '/data/images/', '', $inputPath);
    echo "🔄 Processing: $relativePath\n";
    
    // Get original size
    $originalSize = filesize($inputPath);
    
    // Optimize PNG with sips (resize and optimize)
    $pngOutput = str_replace('.png', '.png', $outputPath);
    $sipsCommand = "sips -s format png -s formatOptions 70 -Z {$maxSize} '{$inputPath}' --out '{$pngOutput}' 2>/dev/null";
    exec($sipsCommand, $sipsResult, $sipsReturn);
    
    if ($sipsReturn === 0 && file_exists($pngOutput)) {
        $pngSize = filesize($pngOutput);
        $pngSavings = round((($originalSize - $pngSize) / $originalSize) * 100, 1);
        echo "  ✅ PNG: " . formatBytes($originalSize) . " → " . formatBytes($pngSize) . " ({$pngSavings}% smaller)\n";
        
        // Create WebP version if cwebp is available
        $webpOutput = str_replace('.png', '.webp', $outputPath);
        $webpCommand = "cwebp -q {$quality} -resize {$maxSize} {$maxSize} '{$inputPath}' -o '{$webpOutput}' 2>/dev/null";
        exec($webpCommand, $webpResult, $webpReturn);
        
        if ($webpReturn === 0 && file_exists($webpOutput)) {
            $webpSize = filesize($webpOutput);
            $webpSavings = round((($originalSize - $webpSize) / $originalSize) * 100, 1);
            echo "  ✅ WebP: " . formatBytes($originalSize) . " → " . formatBytes($webpSize) . " ({$webpSavings}% smaller)\n";
        } else {
            echo "  ⚠️  WebP conversion failed\n";
        }
    } else {
        echo "  ❌ PNG optimization failed\n";
        return false;
    }
    
    echo "\n";
    return true;
}

/**
 * Format bytes for display
 */
function formatBytes($bytes, $precision = 1) {
    $units = array('B', 'KB', 'MB', 'GB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Scan directory for images
 */
function scanForImages($dir, $pattern = '*.png') {
    $images = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'png') {
            // Skip already optimized images
            if (strpos($file->getPathname(), '/optimized/') === false) {
                $images[] = $file->getPathname();
            }
        }
    }
    
    return $images;
}

// Main optimization process
echo "🔍 Scanning for images...\n";
$images = scanForImages($imageDir);
echo "Found " . count($images) . " images to optimize\n\n";

$totalOriginal = 0;
$totalOptimized = 0;
$successCount = 0;

foreach ($images as $imagePath) {
    $originalSize = filesize($imagePath);
    $totalOriginal += $originalSize;
    
    // Determine target size based on image type
    $targetSize = $targetSizes['card']; // Default to card size
    if (strpos($imagePath, '/mechs/') !== false) {
        $targetSize = $targetSizes['mech'];
    }
    
    // Generate output path
    $relativePath = str_replace($imageDir . '/', '', $imagePath);
    $outputPath = $optimizedDir . '/' . $relativePath;
    
    // Ensure output directory exists
    $outputDir = dirname($outputPath);
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }
    
    // Optimize the image
    if (optimizeImage($imagePath, $outputPath, $targetSize, $quality)) {
        $optimizedSize = file_exists(str_replace('.png', '.png', $outputPath)) ? filesize(str_replace('.png', '.png', $outputPath)) : 0;
        $totalOptimized += $optimizedSize;
        $successCount++;
    }
}

// Summary
echo "📊 OPTIMIZATION SUMMARY\n";
echo "=======================\n";
echo "Images processed: $successCount/" . count($images) . "\n";
echo "Original total size: " . formatBytes($totalOriginal) . "\n";
echo "Optimized total size: " . formatBytes($totalOptimized) . "\n";

if ($totalOriginal > 0) {
    $totalSavings = round((($totalOriginal - $totalOptimized) / $totalOriginal) * 100, 1);
    $spaceSaved = $totalOriginal - $totalOptimized;
    echo "Total space saved: " . formatBytes($spaceSaved) . " ({$totalSavings}%)\n";
}

echo "\n✅ Image optimization complete!\n";
echo "Optimized images are in: data/images/optimized/\n";
echo "\nNext steps:\n";
echo "1. Review optimized images for quality\n";
echo "2. Update application to use optimized versions\n";
echo "3. Add WebP support with PNG fallback\n";
?>