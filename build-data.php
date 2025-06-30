<?php
// ===================================================================
// NRD SANDBOX - BUILD DATA (Pure Data File)
// ===================================================================
// This file contains ONLY the build data array
// Used by: config/index.php, build-info.php, and other files needing version info

return [
    'version' => 'v0.9.1',
    'date'    => '2025-06-30',
    'notes'   => 'Cleanup & Stability: Code cleanup, AJAX combat conversion, comprehensive AI handoff',
    
    // Additional build metadata
    'build_name' => 'Stability & Testing',
    'php_required' => '7.4+',
    'features' => [
        'Authentication system',
        'Real-time mech combat with weapon/armor cards',
        'Interactive card battle system',
        'Card Creator with JSON storage',
        'Companion pog system for mechs',
        'Configurable hand and deck sizes',
        'Equipment card mechanics (weapons/armor)',
        'Game state persistence',
        'Responsive tactical battlefield layout',
        'Health status indicators with animations',
        'Combat action logging',
        'Configurable mech stats',
        'Configuration dashboard system',
        'AI context handoff system'
    ],
    
    // Changelog for this version
    'changelog' => [
        'CLEANUP: Removed redundant files and organized structure',
        'IMPROVED: Fixed config system dependencies',
        'ADDED: Comprehensive AI handoff documentation',
        'IMPROVED: Code consistency and standards compliance',
        'PREPARED: AJAX combat conversion foundation',
        'ENHANCED: Configuration dashboard functionality',
        'TESTED: All core systems validated'
    ],
    
    // Previous versions for reference
    'previous_versions' => [
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