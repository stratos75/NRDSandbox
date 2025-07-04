# CLAUDE.md - NRDSandbox: Tactical Card Battle Development Platform

## 🎯 Project Overview
**NRDSandbox** is a PHP-based web development platform for prototyping and testing tactical card battle games. This is NOT a finished game, but a comprehensive sandbox environment for designing, balancing, and iterating on card game mechanics with enterprise-grade user authentication.

**Core Purpose:** Enable rapid prototyping of card battle systems with real-time testing capabilities and secure user management.

**Key Features:**
- Interactive card creator with live preview
- Equipment system (weapons/armor) with equip/unequip mechanics  
- AJAX-based combat system with mech battles
- Audio narration system with "old man" instructor voice
- Debug panels with real-time diagnostics
- Configuration system for game rules and mech stats
- Database-driven user authentication and profiles
- Production-ready deployment system

---

## 🛠 Tech Stack & Architecture

**Backend:** PHP 8.4+ with database-backed authentication  
**Database:** MySQL (production) / SQLite (local development)  
**Frontend:** Vanilla JavaScript with AJAX patterns  
**Styling:** Single CSS file with custom properties  
**Authentication:** Secure session-based user management  
**Development:** Local environment with production deployment tools  

**Architecture Pattern:** MVC-adjacent with clear separation:
- `index.php` - Main game interface (View/Controller)
- `*-manager.php` - Business logic endpoints (Controller)  
- `database/` - Authentication and user management (Model)
- `data/cards.json` - Card library persistence
- Session variables for game state management

---

## 📁 Project Structure

```
NRDSandbox/
├── index.php              # Main battlefield interface
├── login.php              # User authentication
├── logout.php             # Session cleanup
├── signup.php             # Hidden user registration
├── auth.php               # Authentication guard
├── config.php             # Environment configuration
├── combat-manager.php     # AJAX combat endpoints  
├── card-manager.php       # Card CRUD operations
├── style.css              # All styling (single file)
├── database/              # Authentication system
│   ├── Database.php       # Database connection manager
│   ├── User.php           # User authentication & profiles
│   ├── schema.sql         # MySQL database schema
│   └── .htaccess          # Database directory protection
├── config/                # Configuration system
│   ├── index.php          # Config dashboard
│   ├── mechs.php          # Mech stat configuration
│   ├── cards.php          # Card management interface
│   ├── debug.php          # Debug & system diagnostics
│   └── shared.php         # Shared configuration functions
├── data/
│   ├── cards.json         # Persistent card storage
│   ├── images/            # Game assets and card images
│   │   └── mechs/         # Mech battle images
│   └── audio/             # Audio narration system
│       └── oldman/        # Old man instructor voice files
├── PRODUCTION_READY/      # Clean production deployment files
└── .htaccess              # Security and access control
```

**Critical Files:**
- `index.php` - Complete battlefield UI with equipment system
- `login.php` - Database-driven user authentication
- `database/Database.php` - MySQL/SQLite connection abstraction
- `database/User.php` - User management and session handling
- `style.css` - Contains ALL project styling (no other CSS files)
- `data/cards.json` - Card library with 10+ sample cards

---

## 🎮 Game Systems

### **Authentication System**
- **Database-driven**: MySQL production, SQLite local development
- **User roles**: Admin, Developer, Tester, User
- **Session management**: Secure database-stored sessions
- **Hidden signup**: Access-key protected user registration
- **Security**: Prepared statements, input validation, error sanitization

### **Card System**
- **Types:** Spell, Weapon, Armor, Creature, Support, Special Attack
- **Properties:** ID, name, cost, type, damage, description, rarity, image
- **Storage:** JSON format with metadata (created_at, created_by)
- **Management:** Live card creator with instant preview and image upload

### **Equipment System**
- **Equipping:** Click weapon/armor cards in hand to equip
- **Unequipping:** Red X button on equipped items (player only)
- **Return Logic:** Unequipped cards automatically return to hand
- **Slots:** Player has weapon/armor slots, enemy starts pre-equipped
- **Audio Integration:** Equipment actions trigger narrator responses

### **Combat System**  
- **Mechs:** Player vs Enemy with HP/ATK/DEF stats
- **AJAX Actions:** Attack, defend, reset without page reload
- **Health Display:** Real-time HP updates with status indicators
- **Audio Narration:** "Old man" instructor provides combat commentary
- **Logging:** All combat actions logged with timestamps

### **Audio Narration System**
- **Character:** Gruff military instructor "old man" 
- **Triggers:** Game events (combat, equipment, wins/losses)
- **Files:** 16 MP3 recordings with fade transitions
- **Integration:** JavaScript coordination with game events
- **Control:** Mute/unmute functionality

### **Configuration System**
- **Game Rules:** Hand size, deck size, draw per turn, starting player
- **Mech Stats:** Configurable HP/ATK/DEF for player and enemy
- **Presets:** Balanced, tank, glass cannon, endurance configurations
- **Debug Tools:** Real-time system diagnostics and game state inspection

---

## 📝 Code Style & Conventions

**PHP Standards:**
- PSR-4 autoloading patterns where applicable
- `htmlspecialchars()` for all user output
- Session variables for game state: `$_SESSION['playerMech']`
- PDO prepared statements for all database queries
- Consistent form processing with POST validation

**Database Patterns:**
```php
// Authentication check (all protected pages)
require_once 'auth.php';

// Database operations
$db = Database::getInstance();
$user = $userManager->authenticate($username, $password);
```

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

// Audio Integration
NarrativeGuide.trigger('game_event_name');
```

**CSS Conventions:**
- Single `style.css` file for entire project
- CSS custom properties for theming
- BEM-like naming: `.equipment-card`, `.debug-panel`
- Dark theme with blue accents (#00d4ff primary)
- Responsive design for various screen sizes

**Naming Conventions:**
- Files: kebab-case (`card-manager.php`)
- Functions: camelCase (`handleCardClick()`)
- CSS classes: kebab-case with semantic prefixes
- Database tables: snake_case (`user_sessions`)

---

## ⚡ Common Commands

**Development:**
```bash
# Local development server
php -S localhost:8000

# Prepare production files
php prepare_production.php

# Database testing (local only)
php test_db_cli.php
```

**Production Deployment:**
```bash
# Upload files from PRODUCTION_READY/ to:
# newretrodawn.dev/NRDSandbox/

# Database setup via phpMyAdmin:
# Run UPDATED_SQL_WITH_NRDSANDBOX.sql
```

**Testing URLs:**
- **Local**: `http://localhost:8000/`
- **Production**: `https://newretrodawn.dev/NRDSandbox/`
- **Login**: `https://newretrodawn.dev/NRDSandbox/login.php`
- **Config**: `https://newretrodawn.dev/NRDSandbox/config/`
- **Hidden Signup**: `https://newretrodawn.dev/NRDSandbox/signup.php?access_key=nrd_admin_2024`

---

## 🔄 Development Workflow

**Environment:** Mac + VS Code + Local Development + DreamHost Production  
**Local Testing:** SQLite database, full development features  
**Production:** MySQL database, security-hardened deployment  
**Version Control:** Git with production deployment separation  

**Typical Session:**
1. Test current functionality (2-minute health check)
2. Implement new feature with appropriate pattern
3. Test locally with browser dev tools and audio system
4. Run security validation
5. Deploy via PRODUCTION_READY directory
6. Verify production functionality

**Quick Health Check:**
- Login test: `admin/password123`
- Interface loads without errors
- AJAX combat buttons work
- Audio narration plays correctly
- Equipment system functions
- Database connection stable

---

## 🧪 Testing Guidelines

**Manual Testing Approach:**
- **Authentication:** Login/logout, session management, user creation
- **Equipment:** Equip/unequip cards, verify return to hand, audio triggers
- **Combat:** AJAX actions update HP without page reload, narrator responds  
- **Cards:** Create, save, delete from JSON storage
- **Config:** Change settings, verify persistence
- **Audio:** Mute/unmute, event triggering, fade transitions

**Error Monitoring:**
- Browser console for JavaScript errors
- Database connection validation
- Authentication failure handling
- Audio system error states
- Session state verification via debug panel

**Security Testing:**
- File access protection (.htaccess validation)
- Database credential protection
- Input validation and SQL injection prevention
- Error message sanitization
- Session security

**No Automated Testing:** Project uses manual testing with debug panels for real-time state inspection.

---

## 🏗 Key Design Patterns

**Authentication + AJAX Hybrid:**
- User authentication with database sessions
- Combat uses AJAX for dynamic updates
- Equipment uses traditional forms for simplicity
- Card creator uses AJAX for live preview

**Database Abstraction:**
- Environment-aware connection (SQLite local, MySQL production)
- Singleton pattern for database connections
- PDO prepared statements for security
- User session management with database storage

**Audio Integration:**
- Event-driven narration system
- JavaScript coordination with game events
- Fade transitions and duplicate prevention
- Mute/unmute state persistence

**Production Separation:**
- Clean production file deployment
- Development vs production file separation
- Security-hardened production configuration
- Environment-specific database connections

**Slide Panel UI:**
- Debug panel slides from left
- Card creator slides from right  
- Authentication-protected configuration panels
- Non-intrusive overlay system

---

## 🚨 Troubleshooting

**Authentication Issues:**
- Check database connection and table structure
- Verify user exists with correct password hash
- Check session management and cleanup
- Validate .htaccess protection is working

**Database Problems:**
- Local: Check SQLite file permissions and creation
- Production: Verify MySQL credentials and table structure
- Connection: Test with database diagnostic tools
- Sessions: Check session table and cleanup procedures

**Audio Not Working:**
- Verify audio files exist in `data/audio/oldman/`
- Check browser console for loading errors
- Test mute/unmute functionality
- Verify file naming matches trigger system

**Production Deployment:**
- Ensure all files from PRODUCTION_READY uploaded
- Check .htaccess files are active
- Verify database credentials correct
- Test security headers and file protections

**Game Functionality:**
- Equipment issues: Check card data structure and session state
- Combat problems: Verify AJAX endpoints and response handling
- Card creator: Check file upload permissions and image processing

---

## 🤖 AI Assistant Guidelines

### **Current Development Status**
- **✅ Completed:** Authentication system, database integration, audio narration, equipment system, AJAX combat, production deployment, security hardening
- **🎯 Current Priority:** System maintenance and feature refinement
- **🔜 Future:** Enhanced game mechanics, multiplayer features, advanced statistics

### **Key Context Points**
- This is a **development platform**, not a finished game
- Focus on **functionality and security** over aesthetics  
- User can test changes **immediately on localhost**
- Production deployment is **secure and working**
- All major systems are **stable and audited**

### **Development Approach**
- **Build incrementally** - add one feature at a time
- **Test immediately** - user has 2-minute health check routine
- **Maintain separation** - keep local development and production deployment separate
- **Security first** - validate all changes for security implications
- **Update documentation** - maintain accurate system documentation

### **Code Integration**
- **Follow existing patterns** established in codebase
- **Use database abstraction** for all data operations
- **Maintain authentication** on all protected resources
- **Test audio integration** with new game events
- **Update style.css** for any new UI components
- **Prepare production files** for any deployment changes

### **Debugging Support**
- **Use debug panel** for real-time state inspection
- **Check browser console** for JavaScript and audio errors
- **Verify database connections** with test utilities
- **Test form submissions** with network debugging
- **Monitor authentication** and session management

### **Security Considerations**
- **Validate input** on all user-provided data
- **Sanitize output** for all user-facing content  
- **Protect credentials** and sensitive information
- **Test file access** controls and .htaccess protection
- **Monitor error handling** for information disclosure

### **Production Deployment**
- **Use PRODUCTION_READY** directory for clean deployments
- **Exclude development files** from production
- **Test security configuration** before deployment
- **Verify database credentials** for production environment
- **Maintain file separation** between local and production

---

## 🔐 Security Status

**Security Audit:** ✅ **PASSED** (Latest audit date: Current session)

**Security Features Active:**
- Database credential protection via .htaccess
- Input validation and prepared statements  
- Error message sanitization
- File access controls and directory protection
- Security headers (XSS, frame options, content type)
- Authentication system with secure sessions

**Access Controls:**
- Hidden signup system with access key protection
- Authentication required for all configuration panels
- Database directory access blocked
- Test files excluded from production deployment

---

**💡 Remember:** This is a secure, production-ready sandbox for rapid prototyping of tactical card battle mechanics. Prioritize working functionality and maintain security standards while enabling fast iteration and testing of game concepts.

**🚀 Production Status:** Deployed and secure at `https://newretrodawn.dev/NRDSandbox/`