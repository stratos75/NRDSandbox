# NRDSandbox Narratives System

This directory contains the Arrow narrative integration system for NRDSandbox.

## Directory Structure

```
narratives/
├── data/           # Story JSON data files
├── assets/         # Story-specific images/audio
├── exports/        # Arrow HTML export files
├── processor.php   # Arrow export processor
└── StoryManager.php # Story management system
```

## How to Use

1. **Create Story in Arrow**: Use the Arrow visual editor to create your narrative
2. **Export HTML**: Export your story as a playable HTML document
3. **Process**: Place the HTML file in `/exports/` and process it
4. **Integrate**: The story will be integrated with the existing game systems

## Story Integration Features

- **Decision Trees**: Arrow choices affect game mechanics
- **Character Dialogue**: Connects to existing NarrativeGuide system
- **Loot Rewards**: Story outcomes influence card drops
- **Audio Integration**: Works with existing old man narrator
- **Progress Tracking**: Saves story choices across sessions

## Development Notes

- All exported Arrow files should be placed in `/exports/`
- Story assets (images, audio) go in `/assets/`
- Processed story data is stored in `/data/`
- System follows existing NRDSandbox security patterns