<?php
// ===================================================================
// NRD SANDBOX - BUILD DATA (Pure Data File)
// ===================================================================
// This file contains ONLY the build data array
// Used by: config/index.php, build-info.php, and other files needing version info

return [
    'version' => 'v1.0.0',
    'date'    => '2025-07-01',
    'notes'   => 'Image Upload System Complete: Full card and mech image upload functionality with visual effects',
    
    // Additional build metadata
    'build_name' => 'Image Upload Release',
    'php_required' => '7.4+',
    'features' => [
        'Authentication system',
        'Real-time mech combat with weapon/armor cards',
        'Interactive card battle system',
        'Card Creator with JSON storage + IMAGE UPLOAD',
        'Mech Configuration with IMAGE UPLOAD',
        'Complete image system (cards 300x400px, mechs 400x300px)',
        'Visual effects: health-based image filters',
        'Companion pog system for mechs',
        'Configurable hand and deck sizes',
        'Equipment card mechanics (weapons/armor)',
        'Game state persistence',
        'Responsive tactical battlefield layout',
        'Health status indicators with animations',
        'Combat action logging',
        'Configurable mech stats',
        'Configuration dashboard system',
        'AI context handoff system',
        'Image storage and management system'
    ],
    
    // Changelog for this version
    'changelog' => [
        'MAJOR: Complete image upload system for cards and mechs',
        'ADDED: Card image upload (300x400px) with live preview',
        'ADDED: Mech image upload (400x300px) with configuration interface',
        'ADDED: Visual effects system with health-based image filters',
        'ADDED: Image storage management in data/images/ directory',
        'ADDED: Base64 image encoding for AJAX uploads',
        'ENHANCED: Card creator with image preview and validation',
        'ENHANCED: Mech configuration interface (config/mechs.php)',
        'ENHANCED: AI handoff dashboard with image system diagnostics',
        'IMPROVED: Data structure handling with null coalescing',
        'FIXED: PHP warnings for undefined array keys',
        'TESTED: Full image upload functionality validated'
    ],
    
    // Previous versions for reference
    'previous_versions' => [
        'v0.9.1' => '2025-06-30 - Cleanup & Stability: Code cleanup, AJAX combat conversion, comprehensive AI handoff',
        'v0.9.0' => '2025-06-29 - AI Context System: Automated project handoff documentation',
        'v0.5.3' => '2025-06-26 - Cleanup: Removed log panel, consolidated build files, focused on card system',
        'v0.5.2' => '2025-06-26 - Added JSON file storage system for persistent card data',
        'v0.5.1' => '2025-06-26 - Moved game log to dedicated right-side panel, simplified controls panel',
        'v0.5.0' => '2025-06-26 - Added Card Creator interface - Phase 1 of game creation tools',
        'v0.4.1' => '2025-06-26 - Fixed log panel interference with fan card layout - made controls compact',
        'v0.4.0' => '2025-06-26 - Added fan card layout for hands with realistic fan effect',
        'v0.3.2' => '2025-06-26 - Fixed companion pog positioning and alignment issues',
        'v0.3.1' => '2025-06-26 - Critical bugfix: Fixed companion pog errors, added null safety checks',
        'v0.3.0' => '2025-06-26 - Complete layout restructure: Centered mechs, weapon/armor cards, companion pogs',
        'v0.2.0' => '2025-06-26 - Major UI overhaul: Fixed P/E alignment, HP in circles, improved layout',
        'v0.1.4' => '2025-06-26 - Overlay labels aligned, HP in circles, log + buttons stable',
        'v0.1.1' => '2025-06-26 - Improved layout; HP in mech circles; P/E deck labels',
        'v0.1.0' => '2025-06-25 - Initial battlefield prototype'
    ]
];
?>