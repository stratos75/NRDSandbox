# 🚀 NRDSandbox - Local vs Production File Separation

## 🏠 **LOCAL DEVELOPMENT ONLY** (Do NOT Upload)

### **Test & Debug Files:**
```
test_db_cli.php
test_login_debug.php  
test_user_auth.php
test_password.php
test_schema.php
test_login_flow.php
diagnose_error.php
debug_connection.php
verify_config.php
```

### **Setup & Documentation:**
```
setup_production.php          # Delete after first use
DATABASE_SETUP_COMPLETE.md
DEPLOYMENT_GUIDE.md
DEPLOYMENT_SEPARATION.md
CLAUDE.md
*.sql files (schema files)
PRODUCTION_SQL_SETUP*.sql
DREAMHOST_COMPLETE_SQL.sql
UPDATED_SQL_WITH_NRDSANDBOX.sql
```

### **Database Development:**
```
database/schema.sql
database/schema_sqlite.sql
database/test_connection.php   # Keep for local testing only
data/nrd_sandbox.sqlite        # Local SQLite database
```

---

## 🌐 **PRODUCTION UPLOAD** (Upload These)

### **Core Application Files:**
```
index.php                     # Main game interface
login.php                     # Authentication page
logout.php                    # Logout handler
auth.php                      # Authentication guard
signup.php                    # Hidden user registration
config.php                    # Environment configuration
```

### **Game Logic:**
```
combat-manager.php            # Combat system
card-manager.php              # Card management
```

### **Database System:**
```
database/Database.php         # Database connection class
database/User.php             # User authentication class
```

### **Configuration Interface:**
```
config/index.php              # Config dashboard
config/mechs.php              # Mech configuration
config/cards.php              # Card management
config/debug.php              # Debug tools
config/shared.php             # Shared config functions
```

### **Assets & Data:**
```
style.css                     # All styling
data/cards.json               # Card library
data/images/                  # All game images
data/audio/                   # Audio files
```

### **Security:**
```
.htaccess                     # Root security
database/.htaccess            # Database protection
```

---

## 🔧 **Configuration Changes Needed**

### **1. Update Database.php for Production**
The local/production detection is causing issues. Let me fix this: