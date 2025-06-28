<?php
// ===================================================================
// NRD SANDBOX - BUILD INFORMATION DATA
// ===================================================================

return [
    'version' => 'v0.8.0',
    'date'    => '2025-06-27',
    'notes'   => 'Major refactor: Created organized configuration system with dedicated pages and clean architecture',
    
    // Additional build metadata
    'build_name' => 'Configuration System Refactor',
    'php_required' => '7.4+',
    'features' => [
        'Authentication system',
        'Real-time mech combat with weapon/armor cards',
        'Interactive card battle system with detail modals',
        'JSON card storage with persistent data',
        'Card Creator interface with live preview',
        'Companion pog system for mechs',
        'Organized configuration system with dedicated hub',
        'Dedicated mech configuration page with presets',
        'Dedicated game rules configuration with analysis',
        'Configuration dashboard with system overview',
        'Export/import configuration functionality',
        'Hand size and deck size management',
        'Turn system configuration',
        'Scenario creation variables',
        'Equipment card mechanics (weapons/armor)',
        'Game state persistence',
        'Responsive tactical battlefield layout',
        'Health status indicators with animations',
        'Combat action logging',
        'Configurable mech stats with presets',
        'Face-up/face-down card display logic',
        'Card detail modal system',
        'Enhanced card interaction and visualization'
    ],
    
    // Changelog for this version
    'changelog' => [
        'REFACTORED: Configuration system into organized /config/ subdirectory',
        'ADDED: Configuration dashboard hub with system overview',
        'ADDED: Dedicated mech configuration page with enhanced presets',
        'ADDED: Dedicated game rules configuration with balance analysis',
        'ADDED: Shared configuration functions and consistent styling',
        'ADDED: Export/import configuration functionality',
        'ADDED: Reset all settings functionality',
        'IMPROVED: Separation of concerns with focused pages',
        'IMPROVED: Navigation structure and user experience',
        'IMPROVED: Code organization and maintainability',
        'PREPARED: Foundation for future configuration modules (cards, AI)',
        'CONSOLIDATED: All configuration logic into logical sections'
    ],
    
    // Previous versions for reference
    'previous_versions' => [
        'v0.7.0' => '2025-06-27 - Added comprehensive game rules configuration system for scenario creation',
        'v0.6.0' => '2025-06-27 - Added interactive card detail modal system with enhanced card interaction',
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