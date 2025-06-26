<?php
// ===================================================================
// NRD SANDBOX - BUILD & VERSION INFORMATION
// ===================================================================
// This is the single source of truth for version management
// Replaces the old version.php approach for cleaner organization

return [
    'version' => 'v0.2.0',
    'date'    => '2025-06-26',
    'notes'   => 'Major UI overhaul: Fixed P/E alignment, HP in circles, improved layout, consolidated versioning',
    
    // Additional build metadata
    'build_name' => 'Tactical Rebalance',
    'php_required' => '7.4+',
    'features' => [
        'Authentication system',
        'Real-time mech combat',
        'Interactive card system',
        'Game state persistence',
        'Responsive battlefield layout',
        'Health status indicators',
        'Combat action logging',
        'Configurable mech stats'
    ],
    
    // Changelog for this version
    'changelog' => [
        'FIXED: P/E faction labels now properly aligned on mech cards',
        'FIXED: HP values now display inside circular health indicators',
        'IMPROVED: Complete UI redesign with modern CSS gradients',
        'IMPROVED: Better responsive layout for mobile devices',
        'IMPROVED: Enhanced visual feedback for mech health states',
        'IMPROVED: Consolidated version management (removed version.php)',
        'ADDED: Combat zone visual separation',
        'ADDED: Proper section organization with clear comments',
        'ADDED: Animated health status (critical health pulses)',
        'ADDED: Enhanced card hover effects and interactions'
    ],
    
    // Previous versions for reference
    'previous_versions' => [
        'v0.1.4' => '2025-06-26 - Overlay labels aligned, HP in circles, log + buttons stable',
        'v0.1.1' => '2025-06-26 - Improved layout; HP in mech circles; P/E deck labels',
        'v0.1.0' => '2025-06-25 - Initial battlefield prototype'
    ]
];