# Core Repository Usage Guide

This repository serves as the **core/template** for all ICE-Felix applications. Other projects can pull updates from this core to stay synchronized with the latest features and improvements.

## 🎯 Repository Structure

- **`main` branch**: Latest stable core version
- **`core` branch**: Clean template branch for new projects
- **`develop` branch**: Development/testing branch (optional)

## 🚀 For New Projects

### 1. Create New Project from Core

```bash
# Clone the core repository
git clone git@github.com:ICE-Felix/admin.app.icefelix.com.git your-new-project
cd your-new-project

# Remove core remote and add your project remote
git remote remove origin
git remote add origin git@github.com:ICE-Felix/your-new-project.git

# Add core as upstream for future updates
git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git

# Push to your new project
git push -u origin main
```

### 2. GitHub Template (Alternative)

Make this repository a GitHub template:
1. Go to repository settings
2. Check "Template repository"
3. Use "Use this template" button for new projects

## 🔄 For Existing Projects

### 1. Add Core as Upstream

```bash
# Add core repository as upstream
git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git

# Fetch core updates
git fetch upstream
```

### 2. Pull Core Updates

```bash
# Create a new branch for updates
git checkout -b core-updates

# Pull latest core changes
git pull upstream main

# Review changes and resolve conflicts
git status
git diff

# Merge back to main
git checkout main
git merge core-updates

# Push updates
git push origin main
```

## 📋 Workflow for Core Updates

### For Core Repository Maintainers:

```bash
# Make changes to core
git checkout main
# ... make changes ...
git add .
git commit -m "feat: add new core feature"
git push admin main

# Update core branch
git checkout core
git rebase main
git push admin core
```

### For Project Maintainers:

```bash
# Regular update cycle (weekly/monthly)
git fetch upstream
git checkout -b sync-core-$(date +%Y%m%d)
git rebase upstream/main
# Resolve conflicts if any
git checkout main
git merge sync-core-$(date +%Y%m%d)
git push origin main
```

## 🛠️ Customization Guidelines

### What to Customize per Project:
- Environment variables (`.env`)
- Configuration files (`config/`)
- Project-specific routes (`routes/`)
- Database migrations (project-specific)
- Assets and branding (`public/assets/`)

### What to Keep Synced:
- Core application structure
- Shared components (`app/View/Components/`)
- Helper functions (`app/Helpers/`)
- Middleware (`app/Http/Middleware/`)
- Service providers (`app/Providers/`)

## 🔧 Handling Conflicts

### Common Conflict Resolution:

```bash
# If you have conflicts during pull
git status
# Edit conflicted files
git add .
git commit -m "resolve: merge conflicts with core updates"
```

### Recommended Merge Strategy:

```bash
# Use rebase for cleaner history
git config pull.rebase true
git config rebase.autoStash true
```

## 📁 Directory Structure

```
admin.app.icefelix.com/          # Core Repository
├── app/                         # Laravel Application
│   ├── Http/Controllers/        # Shared Controllers
│   ├── View/Components/         # Reusable Components
│   ├── Helpers/                 # Helper Functions
│   └── Services/                # Core Services
├── resources/                   # Views and Assets
├── config/                      # Configuration Templates
├── .env.sample                  # Environment Template
├── CORE_USAGE.md               # This File
└── README.md                    # Project Documentation
```

## 🚨 Important Notes

1. **Never commit sensitive data** to the core repository
2. **Test thoroughly** before pulling core updates to production
3. **Keep local customizations** in separate commits for easy conflict resolution
4. **Use feature branches** for experimental core features
5. **Document breaking changes** in release notes

## 🔄 Update Scripts

### Quick Update Script (`update-core.sh`):

```bash
#!/bin/bash
echo "🔄 Updating from core repository..."
git fetch upstream
git checkout -b core-update-$(date +%Y%m%d)
git rebase upstream/main
echo "✅ Core updates pulled. Review changes and merge manually."
```

### Automated Sync (CI/CD):

```yaml
# .github/workflows/sync-core.yml
name: Sync Core Updates
on:
  schedule:
    - cron: '0 2 * * 1'  # Monday 2 AM
  workflow_dispatch:
jobs:
  sync:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Sync from core
        run: |
          git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git
          git fetch upstream
          git rebase upstream/main
```

## 📞 Support

For questions about core updates or conflicts:
- Create an issue in the core repository
- Contact the core maintainers
- Check the changelog for breaking changes 