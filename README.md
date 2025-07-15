# ICE-Felix Core Application Repository

Welcome to the **ICE-Felix Core Application Repository** - the central template and foundation for all ICE-Felix applications.

## 🎯 Purpose

This repository serves as the **core/template** for all ICE-Felix applications, providing:

- **Shared components** and functionality
- **Consistent application structure**
- **Reusable services and helpers**
- **Standardized configuration**
- **Common UI components**

## 🚀 Quick Start

### For New Projects

```bash
# Clone this repository
git clone git@github.com:ICE-Felix/admin.app.icefelix.com.git your-new-project
cd your-new-project

# Set up for your project
git remote remove origin
git remote add origin git@github.com:ICE-Felix/your-new-project.git
git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git

# Push to your new repository
git push -u origin main
```

### For Existing Projects

```bash
# Add core repository as upstream
git remote add upstream git@github.com:ICE-Felix/admin.app.icefelix.com.git

# Use the update script
chmod +x update-core.sh
./update-core.sh
```

## 📋 Laravel Application Features

### Core Components
- **Authentication System** with Supabase integration
- **Dynamic CRUD Operations** with JSON configuration
- **Reusable UI Components** (Blade Components)
- **File Management** with browser component
- **Form Builders** with validation
- **Template Parser Service**
- **OpenAI Integration Service**

### Included Middleware
- JSON Props Middleware
- Supabase Authentication
- Permission Management
- Request Validation

### Services
- **SupabaseService** - Database operations
- **OpenAIService** - AI integrations
- **TemplateParserService** - Dynamic content

## 🔧 Installation

```bash
# Install dependencies
composer install
npm install

# Copy environment configuration
cp .env.sample .env

# Generate application key
php artisan key:generate

# Configure your environment
# Update .env with your Supabase credentials

# Build assets
npm run build

# Start development server
php artisan serve
```

## 🌟 Core Features

### Dynamic CRUD System
Configure entities through JSON files in `storage/app/json/`:
- Automatic form generation
- Validation rules
- Relationship handling
- Custom field types

### Component Library
Reusable Blade components:
- `<x-input>` - Enhanced input fields
- `<x-select>` - Dynamic select dropdowns
- `<x-file-browser>` - File management
- `<x-date-input>` - Date pickers
- `<x-location-picker>` - Location selection
- `<x-trix-editor>` - Rich text editor

### Supabase Integration
- Authentication
- Real-time database operations
- Row-level security
- File storage

## 📚 Documentation

- **[Core Usage Guide](CORE_USAGE.md)** - How to use this as a template
- **[Dynamic CRUD Documentation](documentations/DYNAMIC_CRUD_DOCUMENTATION.md)** - CRUD system details
- **[Changelog](CHANGELOG.md)** - Version history

## 🔄 Staying Updated

### Regular Updates
Run the update script regularly to get the latest core improvements:

```bash
./update-core.sh
```

### Manual Updates
```bash
git fetch upstream
git checkout -b core-updates
git rebase upstream/main
# Resolve conflicts if any
git checkout main
git merge core-updates
```

## 🛠️ Development Workflow

### For Core Maintainers
1. Make changes to core functionality
2. Test thoroughly
3. Update version and changelog
4. Push to main branch
5. Notify project maintainers

### For Project Maintainers
1. Pull core updates regularly
2. Test in staging environment
3. Resolve any conflicts
4. Deploy to production
5. Report issues back to core

## 📁 Project Structure

```
admin.app.icefelix.com/
├── app/
│   ├── Http/Controllers/         # Shared Controllers
│   ├── View/Components/          # Reusable Components
│   ├── Helpers/                  # Helper Functions
│   ├── Services/                 # Core Services
│   └── Providers/                # Service Providers
├── resources/
│   ├── views/                    # Blade Templates
│   ├── js/                       # JavaScript Assets
│   └── css/                      # Stylesheets
├── config/                       # Configuration Files
├── storage/app/json/             # CRUD Configurations
├── .env.sample                   # Environment Template
├── update-core.sh                # Update Script
└── CORE_USAGE.md                 # Usage Documentation
```

## 🔒 Security

- Environment variables are properly ignored
- Sensitive configurations excluded from repository
- Supabase handles authentication and authorization
- Regular security updates through core updates

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

- **Issues**: Create an issue in this repository
- **Questions**: Contact the core maintainers
- **Updates**: Check the changelog for breaking changes

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Made with ❤️ by ICE-Felix Team**

admin.app.icefelix.com

# admin.app.icefelix.com
