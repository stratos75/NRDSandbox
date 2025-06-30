# NRD Sandbox - Complete AI Context Handoff v1.1
**Generated:** 2025-06-30 | **For:** Next Claude Session
**Project Status:** Stable, AJAX Combat Implemented, Ready for Card Effects

## 🎯 **WHAT THIS IS**
A PHP-based web tool for prototyping tactical card battle games. Think "card game development sandbox" - not a finished game, but a tool for testing game mechanics, card balance, and UI concepts.

## ⚡ **IMMEDIATE CONTEXT (What works RIGHT NOW)**

### **Authentication System** ✅
- Login: `admin/password123` or `tester/testpass` (see `users.php`)
- Files: `auth.php`, `login.php`, `logout.php`
- Session-based, works perfectly

### **Main Battlefield Interface** ✅ 
- File: `index.php` (main game interface)
- Features: Player/Enemy mechs, health bars, fan-style card layout
- Combat: **NOW AJAX-BASED** - no more page reloads!
- Cards: Displays real cards from JSON, clickable for details modal
- Equipment: Working weapon/armor card system with equipping

### **AJAX Combat System** ✅ **NEW!**
- File: `combat-manager.php` (handles all combat requests)
- Real-time HP updates without page reloads
- Visual feedback with success/error messages
- Dynamic mech card status changes (healthy/damaged/critical)
- Debug panel integration for real-time testing

### **Card Creator System** ✅
- Slide-in panel from right side
- Live preview as you type
- Saves to `data/cards.json`
- CRUD operations work perfectly
- Pattern: AJAX-based, smooth UX

### **Debug Panel System** ✅
- Slide-in panel from left side (opposite of card creator)
- Toggle with 🐛 Debug button in navigation
- Shows: System status, game state, mech HP, hand counts
- Reset functions: Mech health, card hands, game log, everything
- Action log with real-time combat updates
- Technical info: version, session ID, equipment status

### **Configuration System** ✅
- Location: `/config/` directory
- Dashboard: `config/index.php`
- Mech Stats: `config/mechs.php` 
- Game Rules: `config/rules.php`
- AI Context: `config/ai-context.php` (this page)
- Shared Functions: `config/shared.php`

## 📁 **FILE STRUCTURE (What each file does)**

```
NRDSandbox/
├── index.php              # Main battlefield interface (AJAX-enabled)
├── combat-manager.php      # NEW: AJAX combat endpoint
├── auth.php               # Authentication logic  
├── login.php              # Login page
├── logout.php             # Logout functionality
├── users.php              # User credentials array
├── style.css              # ALL styling (includes debug panel CSS)
├── card-manager.php       # Card CRUD operations (JSON-based)
├── build-data.php         # Build information data (CLEANED UP)
├── build-info.php         # Build information display
├── push.sh                # Git deployment script
├── config/
│   ├── index.php          # Configuration dashboard
│   ├── shared.php         # Shared config functions
│   ├── mechs.php          # Mech stat configuration
│   ├── rules.php          # Game rules configuration
│   └── ai-context.php     # AI handoff generator (this file)
├── data/
│   └── cards.json         # Persistent card storage
└── docs/                  # Documentation (suggested)
```

## 🎮 **CURRENT GAME STATE**

### **Cards in System:**
- **Count:** 10 cards in `data/cards.json`
- **Types:** Spell, Weapon, Armor, Creature, Support
- **Sample card structure:**
```json
{
  "id": "card_123456",
  "name": "Lightning Bolt", 
  "cost": 3,
  "type": "spell",
  "damage": 5,
  "description": "Deal 5 damage",
  "rarity": "common",
  "created_at": "2025-06-27 13:17:44",
  "created_by": "admin"
}
```

### **Game Rules:**
- Starting Hand: 5 cards
- Max Hand: 7 cards
- Deck Size: 20 cards
- Draw Per Turn: 1
- Starting Player: player

### **Mech Configuration:**
- Player: HP 100, ATK 30, DEF 15
- Enemy: HP 100, ATK 25, DEF 10

## 🔧 **HOW THINGS WORK (Code Patterns)**

### **AJAX Combat Pattern (NEW):**
```javascript
function performCombatAction(action) {
    const formData = new FormData();
    formData.append('action', action);
    
    fetch('combat-manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCombatUI(data.data);
            addLogEntry(data.data.logEntry);
            showCombatMessage(data.message, 'success');
        }
    });
}
```

### **AJAX Pattern (Used in Card Creator):**
```javascript
// Send request
fetch('card-manager.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update UI without page reload
    }
});
```

### **Form Pattern (Used for non-combat actions):**
```php
if ($_POST['draw_cards']) {
    // Update game state
    $_SESSION['player_hand'] = $playerHand;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
```

### **JSON Data Pattern:**
```php
// Load cards
$data = json_decode(file_get_contents('data/cards.json'), true);
// Save cards  
file_put_contents('data/cards.json', json_encode($data, JSON_PRETTY_PRINT));
```

## 🚀 **NEXT LOGICAL STEPS (Priority Order)**

### **1. Card Effects System** (Ready to implement)
- **Status:** Combat system is AJAX-ready, perfect foundation
- **Goal:** Make spell cards actually do things when played
- **Implementation:** 
  - Add `playCard()` function that calls combat-manager.php
  - Create spell effect handlers (healing, damage, buffs)
  - Update card modal "Play Card" button to be functional
- **Files to modify:** `index.php`, `combat-manager.php`

### **2. Equipment Bonuses in Combat** (Easy enhancement)
- **Status:** Equipment system exists, combat is AJAX
- **Goal:** Equipped weapons/armor affect damage calculations
- **Implementation:**
  - Modify damage calculation in `combat-manager.php`
  - Add equipment bonus display in UI
  - Show total attack/defense including equipment
- **Estimated time:** 30 minutes

### **3. Advanced Deck Management** (Natural next step)
- **Status:** Card library exists, hand management works
- **Goal:** Proper deck building and shuffling
- **Implementation:**
  - Create deck construction interface
  - Implement proper draw mechanics (shuffle, deck depletion)
  - Add deck archetypes and restrictions
- **Files needed:** New `deck-manager.php`

### **4. Turn-Based System** (Big feature)
- **Status:** Foundation ready, AJAX combat perfect for this
- **Goal:** Proper turn sequence with phases
- **Implementation:**
  - Add turn state tracking
  - Implement action points/mana system
  - Create end turn functionality
- **Complexity:** High, but AJAX system makes it feasible

## 🐛 **KNOWN ISSUES & SOLUTIONS**

### **Problem:** Combat buttons caused page reloads
- **Solution:** Converted to AJAX using `combat-manager.php`
- **Pattern:** Always use `fetch()` for real-time updates
- **Status:** ✅ SOLVED

### **Problem:** Debug panel missing after updates
- **Solution:** Lost JavaScript functions, restored with proper IDs
- **Pattern:** Always include all necessary element IDs for AJAX updates
- **Status:** ✅ SOLVED

### **Problem:** Card equipping needs page reload
- **Solution:** Still uses forms, but works well for infrequent actions
- **Pattern:** Use AJAX for frequent actions, forms for state changes
- **Status:** ⚠️ ACCEPTABLE (could be enhanced later)

### **Problem:** Mobile layout not optimized
- **Solution:** CSS media queries exist but need testing/refinement
- **Pattern:** Test on mobile device, adjust CSS grid layouts
- **Status:** 🔄 DEFERRED

## ✅ **TESTING CHECKLIST**

### **Combat System:**
```
□ Click "Attack Enemy" - HP drops without reload
□ Click "Enemy Attacks" - Player HP drops without reload  
□ Click "Reset Mechs" - Both mechs return to full HP
□ Mech cards change color based on health (green/yellow/red)
□ Success messages appear briefly on screen
□ Debug panel shows real-time updates
```

### **Card System:**
```
□ Click deck to draw cards
□ Cards appear in hand with proper styling
□ Click weapon/armor cards to equip them
□ Click other cards to show details modal
□ Card Creator saves new cards to JSON
□ Card library displays all created cards
```

### **Debug Panel:**
```
□ Click "🐛 Debug" button opens panel from left
□ Shows current HP values
□ Reset buttons work properly
□ Action log updates in real-time
□ Technical info displays correctly
```

## 🏗️ **ARCHITECTURE DECISIONS**

### **Why AJAX for Combat:**
- **Decision:** Convert combat from forms to AJAX
- **Reason:** Provides immediate feedback, better UX
- **Trade-off:** Slight complexity increase, but worth it

### **Why JSON for Card Storage:**
- **Decision:** Use JSON files instead of MySQL database
- **Reason:** Works locally and on server, easier development
- **Trade-off:** Less scalable, but perfect for prototyping

### **Why Session for Game State:**
- **Decision:** Store game state in PHP sessions
- **Reason:** Simple, works across page loads
- **Trade-off:** Lost on browser close, but good for sandbox testing

### **Why Dual Panel System:**
- **Decision:** Debug panel (left) + Card Creator (right)
- **Reason:** Separate concerns, avoid UI conflicts
- **Trade-off:** More complex CSS, but better organization

## 🔍 **COMMON GOTCHAS FOR NEW AI**

1. **Always create `combat-manager.php`** when working on combat features
2. **Check for element IDs** when updating UI via JavaScript
3. **JSON structure matters** - maintain `cards` array and `meta` object
4. **Debug panel needs specific IDs** for real-time updates
5. **AJAX responses must be JSON** with `success`, `message`, `data` structure
6. **Form resubmission prevention** - always redirect after POST
7. **Card indexing** - hand cards use array indices, can shift when cards removed

## 💾 **SESSION DATA STRUCTURE**
```php
$_SESSION = [
    'username' => 'admin',
    'playerMech' => ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100],
    'enemyMech' => ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100],
    'player_hand' => [/* array of card objects */],
    'playerEquipment' => ['weapon' => null, 'armor' => null],
    'enemyEquipment' => ['weapon' => {...}, 'armor' => {...}],
    'log' => ['array of game events']
];
```

## 🎨 **STYLING NOTES**
- Single CSS file: `style.css`
- Uses CSS custom properties (variables)
- Responsive grid layout
- Dark theme with blue accents
- Card animations and hover effects
- **NEW:** Debug panel styles included (left-side slide-in)
- **NEW:** AJAX message styling for combat feedback

## 🔄 **DEVELOPMENT WORKFLOW**
1. **Local:** Mac with VS Code at `/Volumes/Samples/NRDSandbox/`
2. **Testing:** http://localhost/NRDSandbox/
3. **Version Control:** Git with `push.sh` script
4. **Deployment:** Manual upload to newretrodawn.dev/NRDSandbox
5. **Database:** JSON files (not MySQL yet)

## 📝 **CODE STANDARDS**
- PSR-4 autoloading where possible
- Input sanitization with `htmlspecialchars()`
- Error handling with try-catch
- Consistent function naming
- Inline documentation for complex logic
- **NEW:** AJAX endpoints return JSON with consistent structure

---

## 🆘 **IF SOMETHING BREAKS**
1. **Combat not working:** Check if `combat-manager.php` exists
2. **Debug panel not showing:** Check if debug CSS is in `style.css`
3. **Cards not saving:** Check `data/` directory permissions
4. **Login fails:** Check `users.php` credentials  
5. **Page doesn't load:** Check PHP syntax errors
6. **AJAX errors:** Check browser console and network tab

---

**💡 TIP FOR AI:** This is a development tool, not a polished game. Focus on functionality over aesthetics. User can test changes immediately on localhost. The AJAX combat system is the perfect foundation for implementing card effects!

**🎯 IMMEDIATE WIN:** Implement spell card effects using the established AJAX combat pattern. The infrastructure is ready!