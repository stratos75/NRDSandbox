# NRD Sandbox Deployment Guide

## 🚀 Hybrid Development Environment

The NRD Sandbox supports both **local development** and **production deployment** to DreamHost shared hosting.

### Environment Configuration

- **Local Development**: `http://localhost:8000/` (or your local server)
- **Production**: `https://newretrodawn.dev/nrdsandbox/`

## 📋 Pre-Deployment Checklist

### ✅ Local Development Setup
- [ ] PHP 7.4+ installed
- [ ] Local web server running (MAMP, XAMPP, or `php -S localhost:8000`)
- [ ] Git repository initialized
- [ ] All audio files recorded and placed in `/data/audio/oldman/`

### ✅ Production Requirements
- [ ] SSH access to newretrodawn.dev
- [ ] `rsync` installed on local machine
- [ ] DreamHost account with PHP 7.4+ support
- [ ] Domain configured: `newretrodawn.dev/nrdsandbox/`

## 🛠 Deployment Process

### Option 1: Quick Deployment (Recommended)
```bash
./push.sh
# Choose option 3 (Both local and production)
```

### Option 2: Local Development Only
```bash
./push.sh
# Choose option 1 (Local git commit/push only)
```

### Option 3: Production Only
```bash
./push.sh
# Choose option 2 (Deploy to newretrodawn.dev only)
```

## 🔧 Manual Production Setup

If automatic deployment fails, follow these manual steps:

### 1. Upload Files via FTP/SFTP
Upload all files **except**:
- `.git/` directory
- `push.sh` script
- `README.md`
- `.DS_Store` files

### 2. Set File Permissions
```bash
ssh username@newretrodawn.dev
cd ~/nrdsandbox
chmod -R 755 .
chmod -R 755 data/
chmod 644 data/*.json
```

### 3. Verify Directory Structure
```
nrdsandbox/
├── config.php          # Environment configuration
├── auth.php            # Authentication
├── index.php           # Main game interface
├── login.php           # Login page
├── style.css           # Styling
├── data/
│   ├── .htaccess       # Security protection
│   ├── cards.json      # Game data
│   ├── images/         # Card images
│   └── audio/          # Voice files
└── config/             # Configuration interfaces
```

## 🔐 Security Features

### Production Security Enhancements
- **Data directory protection** via `.htaccess`
- **Environment detection** for error handling
- **Session security** with HTTP-only cookies
- **CSRF protection** helpers available
- **File upload validation** for images/audio

### File Protection
The `/data/.htaccess` file prevents direct access to:
- `*.json` files (game data)
- `*.bak` files (backups)
- `*.tmp` files (temporary)
- System files (`.DS_Store`, etc.)

## 🧪 Testing Deployment

### Local Testing
1. Visit: `http://localhost:8000/`
2. Login: `admin` / `password123`
3. Test all game features
4. Check browser console for errors

### Production Testing
1. Visit: `https://newretrodawn.dev/nrdsandbox/`
2. Login: `admin` / `password123`
3. Test core functionality:
   - [ ] Login works
   - [ ] Card equipping
   - [ ] Combat system
   - [ ] Audio playback
   - [ ] Configuration panels

## 🔄 Environment Differences

| Feature | Local Development | Production |
|---------|------------------|------------|
| **Error Display** | Full errors shown | Errors logged only |
| **File Paths** | Relative paths | Relative paths |
| **Database** | JSON files | JSON files |
| **Sessions** | HTTP cookies | HTTPS cookies |
| **Audio** | Local files | Remote files |
| **Debugging** | Console enabled | Limited logging |

## 🛠 Troubleshooting

### Common Issues

**1. File Permissions Error**
```bash
chmod -R 755 ~/nrdsandbox
chmod -R 755 ~/nrdsandbox/data
```

**2. Audio Files Not Loading**
- Check file permissions: `chmod 644 data/audio/oldman/*.mp3`
- Verify file names match exactly (no extra spaces)

**3. Login Not Working**
- Verify `users.php` uploaded correctly
- Check session configuration in PHP

**4. Cards Not Saving**
- Ensure `data/` directory is writable: `chmod 755 data/`
- Check `cards.json` permissions: `chmod 644 data/cards.json`

### Debug Information

**Local Development:**
- Error details shown in browser
- Full PHP error reporting enabled
- Console debugging available

**Production:**
- Errors logged to server logs
- Limited error display for security
- Contact hosting support if needed

## 📞 Support

### Development Issues
- Check browser console (F12)
- Review local PHP error logs
- Test with different browsers

### Production Issues
- SSH into server: `ssh username@newretrodawn.dev`
- Check error logs: `tail -f ~/logs/error.log`
- Verify file permissions and paths

### DreamHost Specific
- PHP version: 7.4+
- Memory limit: Usually sufficient for JSON file operations
- Upload limits: Check if large audio files need adjustment

## 🎯 Next Steps

After successful deployment:

1. **Test all functionality** on production
2. **Monitor error logs** for issues
3. **Set up backup strategy** for `data/cards.json`
4. **Consider CDN** for audio files if needed
5. **Update CLAUDE.md** with deployment notes