#!/bin/bash

# Colored output helper
green() { echo -e "\033[1;32m$1\033[0m"; }
red()   { echo -e "\033[1;31m$1\033[0m"; }

# Confirm where we are
green "📁 Current directory: $(pwd)"

# Stage files
green "📂 Staging all files..."
git add . || { red "❌ Failed to add files."; exit 1; }

# Prompt for commit message
read -p "💬 Commit message: " commit_message

# Commit
green "📝 Committing..."
git commit -m "$commit_message" || { red "❌ Commit failed."; exit 1; }

# Push
green "🚀 Pushing to GitHub..."
git push origin main || { red "❌ Push failed."; exit 1; }

green "✅ Push complete!"

# Optional: Pause so you can see the message if double-clicked in Finder
read -p "Press Enter to close..."

