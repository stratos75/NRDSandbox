# NRD Sandbox - Complete AI Context Handoff v1.0
**Generated:** 2025-06-30 | **For:** Next Claude Session
**Project Status:** Stable, Ready for Feature Development

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
- Combat: Basic attack/defend buttons (currently form-based, NEEDS AJAX conversion)
- Cards: Displays real cards from JSON, clickable for details modal

### **Card Creator System** ✅
- Slide-in panel from right side
- Live preview as you type
- Saves to `data/cards.json`
- CRUD operations work perfectly
- Pattern: AJAX-based, smooth UX

### **Configuration System** ✅
- Location: `/config/` directory
- Dashboard: `config/index.php`
- Mech Stats: `config/mechs.php` 
- Game Rules: `config/rules.php`
- Shared Functions: `config/shared.php`

## 📁 **FILE STRUCTURE (What each file does)**

```
NRDSandbox/
├── index.php              # Main battlefield interface
├── auth.php               # Authentication logic  
├── login.php              # Login page
├── logout.php             # Logout functionality
├── users.php              # User credentials array
├── style.css              # ALL styling (no other CSS files)
├── card-manager.php       # Card CRUD operations (JSON-based)
├── build-info.php         # Build information display
├── push.sh                # Git deployment script
├── config/
│   ├── index.php          # Configuration dashboard
│   ├── shared.php         # Shared config functions
│   ├── mechs.php          # Mech stat configuration
│   ├── rules.php          # Game rules configuration
│   └── ai-context.php     # AI handoff generator
├── data/
│   └── cards.json         # Persistent card storage
└── docs/                  # Documentation (contains this file))
```

## 🎮 **CURRENT GAME STATE**

### **Cards in System:**
- **Count:** 10+ cards in `data/cards.json`
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

### **Form Pattern (Used in Combat - NEEDS CONVERSION):**
```php
if ($_POST['damage']) {
    // Update game state
    $_SESSION['playerMech'] = $playerMech;
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

### **1. Convert Combat to AJAX** (Immediate)
- File: `index.php` 
- Convert form-based combat buttons to AJAX
- Follow card creator pattern
- Remove `window.location.reload()`

### **2. Deck Building System** (Next Phase)
- Assign cards to player/enemy decks
- Deck composition rules
- Scenario-specific card pools

### **3. Card Effects System** (Future)
- Implement card abilities
- Target selection
- Effect resolution

## 🧪 **HOW TO TEST THINGS**

### **Authentication:**
```
1. Go to /login.php
2. Use: admin/password123
3. Should redirect to index.php
```

### **Card Creator:**
```
1. Click "🃏 Card Creator" button
2. Fill out card form
3. Should see live preview update
4. Click "Save Card" - should save to JSON
5. Check card library shows new card
```

### **Mech Configuration:**
```
1. Go to /config/mechs.php  
2. Change HP values
3. Click save
4. Return to main game - should see new HP values
```

### **Combat System:**
```
1. Click "Attack Enemy" button
2. Should reduce enemy HP by 10
3. Currently triggers page reload (NEEDS AJAX)
```

## 🐛 **KNOWN ISSUES**

1. **Combat buttons cause page reload** (form-based, not AJAX)
2. **No card effects implemented** (cards are just data)
3. **No actual deck building** (all cards in hand)
4. **Mobile warning** but no mobile optimization

## 💾 **SESSION DATA STRUCTURE**
```php
$_SESSION = [
    'username' => 'admin',
    'playerMech' => ['HP' => 100, 'ATK' => 30, 'DEF' => 15, 'MAX_HP' => 100],
    'enemyMech' => ['HP' => 100, 'ATK' => 25, 'DEF' => 10, 'MAX_HP' => 100],
    'log' => ['array of game events']
];
```

## 🎨 **STYLING NOTES**
- Single CSS file: `style.css`
- Uses CSS custom properties (variables)
- Responsive grid layout
- Dark theme with blue accents
- Card animations and hover effects

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

---

## 🆘 **IF SOMETHING BREAKS**
1. **Cards not saving:** Check `data/` directory permissions
2. **Login fails:** Check `users.php` credentials  
3. **Page doesn't load:** Check PHP syntax errors
4. **Config not working:** Check `config/shared.php` functions
5. **CSS broken:** Check `style.css` path

---

**💡 TIP FOR AI:** This is a development tool, not a polished game. Focus on functionality over aesthetics. User can test changes immediately on localhost.