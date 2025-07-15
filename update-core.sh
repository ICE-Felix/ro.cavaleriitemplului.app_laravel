#!/bin/bash

# Core Repository Update Script
# This script helps projects pull updates from the core repository

set -e

echo "🔄 ICE-Felix Core Repository Update Script"
echo "=========================================="

# Check if upstream remote exists
if ! git remote | grep -q "upstream"; then
    echo "❌ No 'upstream' remote found. Adding core repository as upstream..."
    git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git
    echo "✅ Added upstream remote"
fi

# Fetch latest changes from upstream
echo "📥 Fetching latest changes from core repository..."
git fetch upstream

# Check current branch
CURRENT_BRANCH=$(git branch --show-current)
echo "📍 Current branch: $CURRENT_BRANCH"

# Create update branch
UPDATE_BRANCH="core-update-$(date +%Y%m%d-%H%M%S)"
echo "🔀 Creating update branch: $UPDATE_BRANCH"
git checkout -b $UPDATE_BRANCH

# Check for uncommitted changes
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "⚠️  You have uncommitted changes. Please commit or stash them first."
    echo "Uncommitted changes:"
    git status --short
    exit 1
fi

# Pull core updates
echo "⬇️  Pulling core updates..."
if git rebase upstream/main; then
    echo "✅ Core updates applied successfully!"
    echo ""
    echo "📋 What's next:"
    echo "1. Review the changes: git log --oneline $CURRENT_BRANCH..HEAD"
    echo "2. Test your application thoroughly"
    echo "3. If everything looks good, merge to main:"
    echo "   git checkout $CURRENT_BRANCH"
    echo "   git merge $UPDATE_BRANCH"
    echo "   git push origin $CURRENT_BRANCH"
    echo "4. Clean up: git branch -d $UPDATE_BRANCH"
else
    echo "❌ Conflicts detected during rebase!"
    echo ""
    echo "🔧 To resolve:"
    echo "1. Fix conflicts in the listed files"
    echo "2. Run: git add <resolved-files>"
    echo "3. Continue: git rebase --continue"
    echo "4. If you want to abort: git rebase --abort"
fi

echo ""
echo "📊 Summary of changes:"
git log --oneline --graph $CURRENT_BRANCH..HEAD 2>/dev/null || echo "No changes to show" 