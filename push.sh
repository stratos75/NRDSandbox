#!/bin/bash

# NRD Sandbox Deployment Script
# Supports local development and DreamHost production deployment

# Colored output helper
green() { echo -e "\033[1;32m$1\033[0m"; }
red()   { echo -e "\033[1;31m$1\033[0m"; }
blue()  { echo -e "\033[1;34m$1\033[0m"; }
yellow() { echo -e "\033[1;33m$1\033[0m"; }

# Banner
blue "🎮 NRD TACTICAL SANDBOX DEPLOYMENT 🎮"
echo ""

# Confirm where we are
green "📁 Current directory: $(pwd)"

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    yellow "⚠️  Uncommitted changes detected:"
    git status -s
    echo ""
fi

# Deployment options
echo "🚀 Deployment Options:"
echo "1) Local development only (git commit/push)"
echo "2) Deploy to production (newretrodawn.dev/nrdsandbox)"
echo "3) Both (recommended)"
echo ""
read -p "Choose deployment type (1-3): " deploy_type

case $deploy_type in
    1)
        DEPLOY_LOCAL=true
        DEPLOY_PROD=false
        ;;
    2)
        DEPLOY_LOCAL=false
        DEPLOY_PROD=true
        ;;
    3)
        DEPLOY_LOCAL=true
        DEPLOY_PROD=true
        ;;
    *)
        red "❌ Invalid option. Exiting."
        exit 1
        ;;
esac

# Stage files
green "📂 Staging all files..."
git add . || { red "❌ Failed to add files."; exit 1; }

# Prompt for commit message
read -p "💬 Commit message: " commit_message

if [ "$DEPLOY_LOCAL" = true ]; then
    # Commit
    green "📝 Committing to local repository..."
    git commit -m "$commit_message" || { red "❌ Commit failed."; exit 1; }

    # Push to GitHub
    green "🚀 Pushing to GitHub..."
    git push origin main || { red "❌ Push failed."; exit 1; }
    
    green "✅ Local deployment complete!"
fi

if [ "$DEPLOY_PROD" = true ]; then
    echo ""
    blue "🌐 Production Deployment to newretrodawn.dev/nrdsandbox"
    
    # Check if rsync is available
    if ! command -v rsync &> /dev/null; then
        red "❌ rsync not found. Please install rsync for production deployment."
        exit 1
    fi
    
    # Use configured SSH credentials
    echo "📋 DreamHost deployment using SSH: nrddev@newretrodawn.dev"
    ssh_user="nrddev"
    
    # Sync files to production (excluding development files)
    green "📤 Syncing files to production server..."
    
    rsync -avz --delete \
        --exclude='.git' \
        --exclude='.DS_Store' \
        --exclude='*.log' \
        --exclude='node_modules' \
        --exclude='push.sh' \
        --exclude='README.md' \
        --exclude='.gitignore' \
        --exclude='*.tmp' \
        ./ "$ssh_user@newretrodawn.dev:~/nrdsandbox/" || { 
        red "❌ Production deployment failed."; 
        echo "💡 Make sure you have SSH access to newretrodawn.dev"
        echo "💡 Try: ssh nrddev@newretrodawn.dev"
        exit 1; 
    }
    
    # Set proper permissions on production
    green "🔧 Setting file permissions on production..."
    ssh "$ssh_user@newretrodawn.dev" "cd ~/nrdsandbox && chmod -R 755 . && chmod -R 755 data/ && chmod 644 data/*.json" || {
        yellow "⚠️  Could not set file permissions automatically."
        echo "💡 Manually run: chmod -R 755 ~/nrdsandbox && chmod -R 755 ~/nrdsandbox/data/"
    }
    
    green "✅ Production deployment complete!"
    echo ""
    blue "🌐 Live site: https://newretrodawn.dev/nrdsandbox/"
fi

echo ""
green "🎉 All deployments completed successfully!"

# Show next steps
echo ""
blue "📋 Next Steps:"
if [ "$DEPLOY_PROD" = true ]; then
    echo "• Visit: https://newretrodawn.dev/nrdsandbox/"
    echo "• Test login: admin / password123"
    echo "• Check file permissions if needed"
fi
echo "• Local development: http://localhost:8000 (or your local server)"

echo ""
# Optional: Pause so you can see the message if double-clicked in Finder
read -p "Press Enter to close..."

