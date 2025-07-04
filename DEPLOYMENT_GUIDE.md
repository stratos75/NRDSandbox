# 🚀 Production Deployment Guide

## ✅ Configuration Verified ✅

Your NRD Sandbox is ready for deployment to **newretrodawn.dev/nrdsandbox/** with these MySQL credentials:

```
Host: mysql.newretrodawn.dev
Username: nrd_dev
Database: nrdsb
Password: @NRDSandBoxAdmin
```

---

## 📤 Deployment Steps

### **1. Upload Files to DreamHost**
Upload all files from `/NRDSandbox/` to your DreamHost directory:
```
newretrodawn.dev/nrdsandbox/
```

### **2. Run Production Setup**
Access this URL once after upload:
```
https://newretrodawn.dev/nrdsandbox/setup_production.php?key=nrd_setup_2024
```

This will:
- Test MySQL connection
- Initialize database schema
- Create default admin/tester users
- Verify authentication system

### **3. Test Login System**
Visit: `https://newretrodawn.dev/nrdsandbox/login.php`

**Default credentials:**
- Username: `admin`
- Password: `password123`

### **4. Create Additional Users**
Hidden signup URL:
```
https://newretrodawn.dev/nrdsandbox/signup.php?access_key=nrd_admin_2024
```

### **5. Security Cleanup**
After successful deployment:
- Delete `setup_production.php`
- Change default admin password
- Update signup access key

---

## 🛡️ Security Features Included

### **✅ File Protection:**
- `.htaccess` files protect sensitive directories
- Test files blocked from public access
- Database directory access denied

### **✅ Data Security:**
- Passwords hashed with bcrypt
- SQL injection prevention via PDO
- Session validation with database storage
- CSRF protection helpers available

### **✅ Environment Detection:**
- Automatically uses SQLite for local development
- Switches to MySQL for production domains
- Environment-specific error handling

---

## 🎮 User Experience

### **Seamless Integration:**
- All existing game functionality preserved
- Audio system, equipment, combat unchanged
- Enhanced with user profiles and authentication
- Ready for future multiplayer features

### **Default User Accounts:**
```
Administrator:
- Username: admin
- Password: password123
- Role: admin
- Pilot Callsign: admin

Test User:
- Username: tester  
- Password: password123
- Role: tester
- Pilot Callsign: tester
```

---

## 🔧 Troubleshooting

### **If Setup Fails:**
1. Check MySQL credentials in `/database/Database.php`
2. Verify database `nrdsb` exists in DreamHost panel
3. Ensure user `nrd_dev` has full permissions on database
4. Check file permissions are correct (644 for files, 755 for directories)

### **If Login Fails:**
1. Run setup script again to reinitialize
2. Check browser console for JavaScript errors
3. Verify `.htaccess` files uploaded correctly

### **Database Issues:**
- Access phpMyAdmin via DreamHost panel
- Check if tables exist in `nrdsb` database
- Verify default users were created correctly

---

## 📊 Database Schema

The system will automatically create these tables:
- **users** - Authentication and profiles
- **user_sessions** - Session management
- **user_profiles** - Game preferences
- **game_stats** - Battle statistics (ready for future use)

---

## 🎯 Next Steps After Deployment

1. **Test thoroughly** - Try login, gameplay, equipment system
2. **Change passwords** - Update default admin credentials
3. **Create pilot accounts** - Use hidden signup for team members
4. **Monitor performance** - Check database connection stability
5. **Plan enhancements** - Ready for game statistics, multiplayer features

---

## 🎉 Success!

Your tactical card battle sandbox will have enterprise-grade authentication while maintaining the engaging gameplay experience. The system scales from development to production seamlessly.

**Support:** All authentication happens transparently - existing players won't notice any change to gameplay, just enhanced security and user management.

Ready for takeoff! 🚀