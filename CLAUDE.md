# CLAUDE.md - NRD Sandbox: Tactical Card Battle Development Tool

## 🎯 Project Overview
**NRD Sandbox** is a PHP-based web development tool for prototyping and testing tactical card battle games. This is NOT a finished game, but a comprehensive sandbox environment for designing, balancing, and iterating on card game mechanics.

**Core Purpose:** Enable rapid prototyping of card battle systems with real-time testing capabilities.

**Key Features:**
- Interactive card creator with live preview
- Equipment system (weapons/armor) with equip/unequip mechanics  
- AJAX-based combat system with mech battles
- Debug panels with real-time diagnostics
- Configuration system for game rules and mech stats
- JSON-based persistent card storage

---

## 🛠 Tech Stack & Architecture

**Backend:** PHP 7.4+ with session-based state management  
**Frontend:** Vanilla JavaScript with AJAX patterns  
**Styling:** Single CSS file with custom properties  
**Data Storage:** JSON files (transitioning to MySQL later)  
**Development:** Local LAMP stack, VS Code, Git deployment  

**Architecture Pattern:** MVC-adjacent with clear separation:
- `index.php` - Main game interface (View/Controller)
- `*-manager.php` - Business logic endpoints (Controller)  
- `data/cards.json` - Data persistence (Model)
- Session variables for game state management

---

## 📁 Project Structure

```
NRDSandbox/
├── index.php              # Main battlefield interface
├── auth.php               # Authentication logic
├── combat-manager.php     # AJAX combat endpoints  
├── card-manager.php       # Card CRUD operations
├── style.css              # All styling (single file)
├── config/                # Configuration system
│   ├── index.php          # Config dashboard
│   ├── mechs.php          # Mech stat configuration
│   ├── cards.php          # Card management interface
│   ├── debug.php          # Debug & system diagnostics
│   └── shared.php         # Shared configuration functions
├── data/
│   ├── cards.json         # Persistent card storage
│   └── images/            # Card and mech images
├── images/                # Additional game images
│   └── companions/        # Companion pilot images
├── users.php              # User authentication data
├── login.php              # Login interface
├── logout.php             # Logout functionality
└── push.sh                # Deployment script
```

**Critical Files:**
- `index.php` - Complete battlefield UI with equipment system and turn-based combat
- `card-manager.php` - Handles all card CRUD operations via AJAX
- `combat-manager.php` - AJAX combat endpoints and battle logic
- `auth.php` - Authentication logic and session management
- `style.css` - Contains ALL project styling (no other CSS files)
- `config/index.php` - Configuration dashboard with game rules management
- `config/mechs.php` - Mech stat configuration with image upload
- `config/cards.php` - Card management interface
- `config/debug.php` - Debug tools and system diagnostics
- `data/cards.json` - Card library with weapons, armor, and special cards
- `users.php` - User authentication data

---

## 🎮 Game Systems

### **Card System**
- **Types:** Spell, Weapon, Armor, Creature, Support
- **Properties:** ID, name, cost, type, damage, description, rarity
- **Storage:** JSON format with metadata (created_at, created_by)
- **Management:** Live card creator with instant preview

### **Equipment System**
- **Equipping:** Click weapon/armor cards in hand to equip
- **Unequipping:** Red X button on equipped items (player only)
- **Return Logic:** Unequipped cards automatically return to hand
- **Slots:** Player has weapon/armor slots, enemy starts pre-equipped

### **Combat System**  
- **Mechs:** Player vs Enemy with HP/ATK/DEF stats
- **AJAX Actions:** Attack, defend, reset without page reload
- **Health Display:** Real-time HP updates with status indicators
- **Logging:** All combat actions logged with timestamps

### **Configuration**
- **Game Rules:** Hand size, deck size, draw per turn, starting player, energy system
- **Mech Stats:** Configurable HP/ATK/DEF for player and enemy with image upload
- **Debug Tools:** System diagnostics, session state inspection, reset functions
- **Card Management:** Full CRUD interface for card library management
- **Presets:** Balanced, tank, glass cannon, endurance configurations

### **Turn-Based System**
- **Energy System:** 5 energy per turn, cards cost energy to play
- **Turn Management:** Player vs AI turns with automatic progression
- **AI Behavior:** Enemy automatically plays cards and attacks
- **Hand Management:** Automatic card drawing, hand size limits

---

## 📝 Code Style & Conventions

**PHP Standards:**
- PSR-4 autoloading patterns where applicable
- `htmlspecialchars()` for all user output
- Session variables for game state: `$_SESSION['playerMech']`
- Consistent form processing with POST validation

**JavaScript Patterns:**
```javascript
// AJAX Pattern (standard across project)
fetch('endpoint.php', {
    method: 'POST', 
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) { /* update UI */ }
});
```

**CSS Conventions:**
- Single `style.css` file for entire project
- CSS custom properties for theming
- BEM-like naming: `.equipment-card`, `.debug-panel`
- Dark theme with blue accents (#00d4ff primary)

**Naming Conventions:**
- Files: kebab-case (`card-manager.php`)
- Functions: camelCase (`handleCardClick()`)
- CSS classes: kebab-case with semantic prefixes

---

## ⚡ Common Commands

**Development:**
```bash
# Local development server
php -S localhost:8000

# Deploy to production  
./push.sh

# File permissions (if cards not saving)
chmod 755 data/
chmod 644 data/cards.json
```

**Testing URLs:**
- Main interface: `http://localhost/NRDSandbox/`
- Config dashboard: `http://localhost/NRDSandbox/config/`
- Mech configuration: `http://localhost/NRDSandbox/config/mechs.php`
- Card management: `http://localhost/NRDSandbox/config/cards.php`
- Debug tools: `http://localhost/NRDSandbox/config/debug.php`
- Login page: `http://localhost/NRDSandbox/login.php`

---

## 🔄 Development Workflow

**Environment:** Mac + VS Code + Local LAMP  
**Testing:** `localhost/NRDSandbox/` for immediate feedback  
**Version Control:** Git with `push.sh` deployment script  
**Deployment:** Manual upload to DreamHost hosting  

**Typical Session:**
1. Test current functionality (2-minute health check)
2. Implement new feature with appropriate pattern
3. Test locally with browser dev tools
4. Commit changes and deploy if stable

**Quick Health Check:**
- Login test: `admin/password123`
- Interface loads without errors
- AJAX combat buttons work
- Card creator panel opens
- Debug panel shows system status

---

## 🧪 Testing Guidelines

**Manual Testing Approach:**
- **Equipment:** Equip/unequip cards, verify return to hand
- **Combat:** AJAX actions update HP without page reload  
- **Cards:** Create, save, delete from JSON storage
- **Config:** Change settings, verify persistence

**Error Monitoring:**
- Browser console for JavaScript errors
- PHP error logs for backend issues  
- JSON validation for data corruption
- Session state verification via debug panel

**No Automated Testing:** Project uses manual testing with debug panels for real-time state inspection.

---

## 🏗 Key Design Patterns

**AJAX + Form Hybrid:**
- Combat uses AJAX for dynamic updates
- Equipment uses traditional forms for simplicity
- Card creator uses AJAX for live preview

**Session-Based State:**
- Game state stored in `$_SESSION` variables
- Persistent across page reloads
- Real-time diagnostic access via debug panel

**JSON Data Strategy:**
- Cards stored in `data/cards.json` for simplicity
- Works both locally and on shared hosting
- Avoids MySQL complexity during prototyping phase

**Slide Panel UI:**
- Debug panel slides from left
- Card creator slides from right  
- Non-intrusive overlay system

---

## 🚨 Troubleshooting

**Cards Not Saving:**
- Check `data/` directory permissions (must be writable)
- Verify JSON syntax with browser dev tools
- Ensure file_put_contents() permissions

**AJAX Not Working:**
- Check browser console for JavaScript errors
- Verify endpoint URLs and form data
- Test with network tab in dev tools

**Equipment Issues:**
- Ensure cards have `card_data` when equipped
- Check for `equipped` CSS class on items
- Verify unequip form exists with correct IDs

**Session Problems:**
- Clear browser cache/cookies
- Check PHP session configuration
- Verify `session_start()` in auth.php

---

## 🤖 AI Assistant Guidelines

### **Current Development Status**
- **✅ Completed:** Authentication, card creator, equipment system, AJAX combat, debug panels, turn-based system, energy management, AI enemy behavior, configuration dashboard, image upload system
- **🎯 Next Priority:** Card effects implementation (make cards actually DO things), targeting system for spells
- **🔜 Future:** Deck building interface, advanced combat with elemental damage, companion system integration

### **Key Context Points**
- This is a **development tool**, not a polished game
- Focus on **functionality over aesthetics**  
- User can test changes **immediately on localhost**
- All major systems are **stable and working**

### **Development Approach**
- **Build incrementally** - add one feature at a time
- **Test immediately** - user has 2-minute health check routine
- **Use existing patterns** - AJAX for dynamic, forms for simple
- **Maintain documentation** - update AI context after changes

### **Code Integration**
- **Follow existing patterns** established in codebase
- **Add to existing files** rather than creating new ones when possible
- **Test integration points** with equipment, combat, card systems
- **Update style.css** for any new UI components

### **Debugging Support**
- **Use debug panel** for real-time state inspection
- **Check browser console** for JavaScript errors
- **Verify JSON validity** after data changes
- **Test form submissions** with network debugging

### **Priority Features for Implementation**
1. **Card Effects System** - Make spells/abilities functional with damage/healing/buffs
2. **Targeting System** - Select spell targets (self, enemy, all)
3. **Elemental Damage** - Implement poison, ice, fire damage types
4. **Companion Integration** - Make companion system functional in combat
5. **Deck Building** - Assign specific cards to player/enemy decks
6. **Advanced Combat** - Status effects, buffs/debuffs, multi-turn effects

---

**💡 Remember:** This is a sandbox for rapid prototyping. Prioritize working functionality that enables testing of game mechanics over perfect code architecture.