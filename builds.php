<?php
// ===================================================================
// NRD SANDBOX - BUILD INFORMATION DATA
// ===================================================================

return [
    'version' => 'v0.6.0',
    'date'    => '2025-06-27',
    'notes'   => 'Added interactive card detail modal system with enhanced card interaction',
    
    // Additional build metadata
    'build_name' => 'Card Detail System',
    'php_required' => '7.4+',
    'features' => [
        'Authentication system',
        'Real-time mech combat with weapon/armor cards',
        'Interactive card battle system with detail modals',
        'JSON card storage with persistent data',
        'Card Creator interface with live preview',
        'Companion pog system for mechs',
        'Configurable hand and deck sizes',
        'Equipment card mechanics (weapons/armor)',
        'Game state persistence',
        'Responsive tactical battlefield layout',
        'Health status indicators with animations',
        'Combat action logging',
        'Configurable mech stats',
        'Face-up/face-down card display logic',
        'Card detail modal system',
        'Enhanced card interaction and visualization'
    ],
    
    // Changelog for this version
    'changelog' => [
        'ADDED: Interactive card detail modal system',
        'ADDED: Large card preview with full information display',
        'ADDED: Card metadata viewing (creation date, creator, ID)',
        'ADDED: Enhanced card interaction with click-to-view functionality',
        'IMPROVED: Card visual presentation and type-specific styling',
        'IMPROVED: Action logging for card interactions',
        'IMPROVED: Modal animation and user experience',
        'PREPARED: Foundation for Phase 3 game rules configuration'
    ],
    
    // Previous versions for reference
    'previous_versions' => [
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