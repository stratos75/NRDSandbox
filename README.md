# 🎯 NRDSandbox: Tactical Card Battle Development Platform

[![Version](https://img.shields.io/badge/version-2.0-blue.svg)](https://github.com/user/NRDSandbox)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4.svg)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-orange.svg)](https://sqlite.org)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-green.svg)](https://newretrodawn.dev/NRDSandbox/)

**NRDSandbox** is a sophisticated PHP-based web development platform for prototyping and testing tactical card battle games. This is **not a finished game**, but a comprehensive sandbox environment for designing, balancing, and iterating on card game mechanics with enterprise-grade features.

🌐 **Live Demo:** [newretrodawn.dev/NRDSandbox](https://newretrodawn.dev/NRDSandbox/)  
🎮 **Login:** `admin` / `password123`

---

## ✨ Key Features

### 🃏 **Advanced Card Management System**
- **Database-driven card library** with MySQL/SQLite support
- **Rarity-weighted distribution** (60% common, 25% uncommon, 12% rare, 2.5% epic, 0.5% legendary)
- **Live rarity editing** through web interface
- **Interactive card creator** with live preview and image upload
- **Balanced dealing algorithms** that prevent duplicates and ensure equipment variety

### ⚔️ **Tactical Combat Engine**
- **Elemental synergy system** with 2-piece (8% bonus) and 3-piece (15% bonus) matching
- **Status effect mechanics** (poison DoT, fire burn, ice freeze, plasma energy drain)
- **Equipment system** with weapons, armor, and special attacks
- **AJAX-based combat** with real-time HP updates
- **Advanced damage calculations** with vulnerability multipliers and resistance

### 🎵 **Immersive Audio System**
- **16 professional voice recordings** by a "gruff military instructor"
- **Event-driven narration** for combat, equipment, wins/losses
- **Fade transitions** and duplicate prevention
- **Mute/unmute controls** with state persistence

### 🔐 **Enterprise Authentication**
- **Secure user management** with password hashing and sessions
- **Role-based access** (Admin, Developer, Tester, User)
- **Session security** with database storage and cleanup
- **Hidden signup system** with access key protection

### 📊 **Professional Development Tools**
- **Real-time debug panels** with slide-out interface
- **Configuration management** for game rules and mech stats
- **Performance statistics** tracking combat effectiveness
- **Production deployment** system with security hardening

---

## 🚀 Quick Start

### Prerequisites
- **PHP 8.4+** with PDO extensions
- **Web server** (Apache/Nginx) or PHP built-in server
- **Database:** MySQL (production) or SQLite (local development)
- **Modern browser** with JavaScript enabled

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/user/NRDSandbox.git
   cd NRDSandbox
   ```

2. **Start local development server:**
   ```bash
   php -S localhost:8000
   ```

3. **Access the application:**
   - **URL:** `http://localhost:8000`
   - **Login:** `admin` / `password123`

The SQLite database will be automatically created with sample cards and proper rarity distribution.

### Production Deployment

1. **Use the production-ready files:**
   ```bash
   # Upload files from PRODUCTION_READY/ directory to your web server
   cp -r PRODUCTION_READY/* /path/to/webroot/
   ```

2. **Configure MySQL database:**
   - Import `UPDATED_SQL_WITH_NRDSANDBOX.sql`
   - Set up `.env` file with database credentials
   - Ensure `.htaccess` files are active

3. **Access your deployment:**
   - Production URL with secure authentication
   - All development tools available to authorized users

---

## 🎮 Game Mechanics

### Card System
- **Types:** Weapon, Armor, Special Attack, Spell, Creature, Support
- **Elements:** Fire, Ice, Poison, Plasma, Neutral
- **Rarities:** Common → Uncommon → Rare → Epic → Legendary
- **Properties:** Cost, Damage, Defense, Special Effects

### Combat Flow
1. **Equipment Phase:** Equip weapons, armor, and special attacks
2. **Synergy Calculation:** Automatic bonuses for matching elements
3. **Combat Phase:** AJAX-powered turn-based battles
4. **Status Effects:** Persistent effects that modify gameplay
5. **Victory Conditions:** Reduce enemy HP to zero

### Synergy Bonuses
- **2-Piece Matching:** 8% damage bonus + element-specific status effects
- **3-Piece Matching:** 15% damage bonus + enhanced status effects
- **Element Effects:** Poison (DoT), Fire (Burn), Ice (Freeze), Plasma (Energy Drain)

---

## 🛠 Architecture

### Technology Stack
- **Backend:** PHP 8.4+ with MVC-adjacent pattern
- **Database:** MySQL (production) / SQLite (local development)
- **Frontend:** Vanilla JavaScript with AJAX patterns
- **Styling:** Single CSS file with custom properties
- **Authentication:** Secure session-based user management

### Project Structure
```
NRDSandbox/
├── index.php              # Main battlefield interface
├── login.php              # User authentication
├── combat-manager.php     # AJAX combat endpoints
├── database/
│   ├── CardManager.php    # Database-driven card system
│   ├── Database.php       # Connection abstraction
│   └── schema_sqlite.sql  # Database schema
├── config/                # Admin configuration panels
│   ├── cards.php          # Card management interface
│   ├── mechs.php          # Mech stat configuration
│   └── debug.php          # Debug diagnostics
├── data/
│   ├── audio/oldman/      # Audio narration files
│   ├── images/            # Game assets
│   └── nrd_sandbox.sqlite # Local database
└── PRODUCTION_READY/      # Clean deployment files
```

### Database Schema
- **Card Management:** `cards`, `card_rarities`, `card_quantities`
- **User System:** `users`, `user_profiles`, `user_sessions`
- **Game Data:** `combat_statistics`, `game_stats`, `high_scores`

---

## 🔧 Development

### Local Development
```bash
# Start development server
php -S localhost:8000

# Initialize database (if needed)
php database/Database.php

# Optimize images
php tools/optimize_images.php
```

### Configuration Management
- **Game Rules:** Hand size, deck size, draw mechanics
- **Mech Stats:** HP/ATK/DEF for player and enemy
- **Card Rarities:** Weights, drop rates, power multipliers
- **Audio System:** Event triggers and narrator responses

### Debug Tools
- **Slide Panel Interface:** Real-time game state inspection
- **Combat Statistics:** Performance tracking and analysis
- **Database Diagnostics:** Connection status and query monitoring

---

## 🎯 Godot Transition

This PHP prototype serves as the **design foundation** for a full Godot game engine implementation. Key elements ready for transition:

### Data Models
- **Card definitions** with full metadata and relationships
- **Rarity system** with balanced distribution weights
- **Combat mechanics** with elemental interactions
- **User progression** and statistics tracking

### Game Systems
- **Turn-based combat** with status effects
- **Equipment management** with synergy bonuses
- **Audio integration** with event-driven narration
- **UI patterns** optimized for tactical gameplay

### Technical Architecture
- **Database schemas** adaptable to Godot's data systems
- **State management** patterns for multiplayer support
- **Security models** for user authentication
- **Performance optimizations** for real-time gameplay

See `GODOT_TRANSITION.md` for detailed migration guidelines.

---

## 📈 Current Status

### ✅ Completed Features
- Database-driven card management with rarity controls
- Elemental synergy system with status effects
- Professional audio narration system
- Secure user authentication and sessions
- Real-time combat with AJAX updates
- Production deployment system

### 🚧 Enhancement Opportunities
- Deck building constraints based on rarity limits
- Multiplayer combat sessions
- Advanced AI opponent behaviors
- Enhanced visual effects and animations
- Mobile-responsive interface improvements

---

## 🤝 Contributing

This project is primarily a **design prototype** for Godot game development. Contributions welcome for:

- **Game balance improvements**
- **Audio system enhancements**
- **Database optimization**
- **Security hardening**
- **Documentation updates**

### Development Guidelines
- Follow PSR-4 autoloading patterns
- Use prepared statements for all database operations
- Maintain single CSS file architecture
- Test all changes with 2-minute health check routine

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

## 🌟 Acknowledgments

- **New Retro Dawn** - Game concept and design
- **Claude AI** - Development assistance and code generation
- **PHP Community** - Robust development framework
- **Game Design Community** - Tactical card game inspiration

---

**🚀 Ready to transition to Godot for full game development!**

For questions, issues, or collaboration opportunities, please open an issue or contact the development team.