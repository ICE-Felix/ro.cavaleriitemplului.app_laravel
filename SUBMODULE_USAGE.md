# Git Submodules Usage Guide

This repository can be used as a **Git Submodule** in other projects, providing a clean separation between core functionality and project-specific code.

## 🎯 **Submodule Architecture**

```
your-project/
├── core/                    # Git submodule → admin.app.icefelix.com
│   ├── app/                 # Core Laravel application
│   ├── resources/           # Core views & components
│   ├── config/             # Core configuration
│   └── ...                  # All core functionality
├── app/                     # Project-specific controllers/models
├── config/                  # Project-specific configuration
├── resources/               # Project-specific views/assets
├── .gitmodules             # Submodule configuration
├── composer.json           # Project dependencies
└── bootstrap.php           # Bootstrap that loads core + project
```

## 🚀 **Setting Up a New Project with Core Submodule**

### **1. Create New Project Repository**

```bash
# Create new project directory
mkdir my-new-project
cd my-new-project

# Initialize git
git init
git branch -M main

# Add core as submodule
git submodule add git@github.com:ICE-Felix/admin.app.icefelix.com.git core

# Initialize submodule
git submodule update --init --recursive
```

### **2. Create Project Structure**

```bash
# Create project-specific directories
mkdir -p app/Http/Controllers
mkdir -p app/Models
mkdir -p config
mkdir -p resources/views
mkdir -p resources/js
mkdir -p resources/css
mkdir -p database/migrations
mkdir -p routes
```

### **3. Create Bootstrap Configuration**

Create `bootstrap/app.php`:
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load core application
require_once __DIR__.'/../core/bootstrap/app.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add project-specific middleware
        $middleware->alias([
            'project.auth' => \App\Http\Middleware\ProjectAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Project-specific exception handling
    })
    ->create();
```

### **4. Configure Composer.json**

```json
{
    "name": "ice-felix/my-project",
    "description": "My project built on ICE-Felix Core",
    "type": "project",
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Core\\": "core/app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true
    },
    "scripts": {
        "post-install-cmd": [
            "git submodule update --init --recursive"
        ],
        "post-update-cmd": [
            "git submodule update --recursive"
        ]
    }
}
```

## 🔄 **Working with Submodules**

### **Updating Core Submodule**

```bash
# Update to latest core version
cd core
git checkout main
git pull origin main
cd ..

# Commit the submodule update
git add core
git commit -m "Update core submodule to latest version"
git push origin main
```

### **Pinning to Specific Core Version**

```bash
# Pin to specific commit/tag
cd core
git checkout v1.2.3  # or specific commit hash
cd ..

# Commit the pinned version
git add core
git commit -m "Pin core to version 1.2.3"
git push origin main
```

### **Cloning Project with Submodules**

```bash
# Clone with submodules
git clone --recurse-submodules git@github.com:ICE-Felix/my-project.git

# Or clone first, then init submodules
git clone git@github.com:ICE-Felix/my-project.git
cd my-project
git submodule update --init --recursive
```

## 🛠️ **Project-Specific Development**

### **Directory Structure**

```
my-project/
├── core/                         # Core submodule (read-only)
│   ├── app/View/Components/      # Core components
│   ├── app/Services/            # Core services
│   └── resources/views/core/    # Core views
├── app/
│   ├── Http/Controllers/        # Project controllers
│   ├── Models/                  # Project models
│   └── Services/                # Project services
├── resources/
│   ├── views/                   # Project views
│   ├── js/                      # Project JavaScript
│   └── css/                     # Project styles
└── config/
    └── app.php                  # Project configuration
```

### **Using Core Components in Project**

```php
// In project view
@extends('core::layouts.app')

@section('content')
    <x-core::input name="title" />
    <x-project::custom-component />
@endsection
```

### **Extending Core Controllers**

```php
// app/Http/Controllers/ProjectController.php
<?php

namespace App\Http\Controllers;

use Core\Http\Controllers\Controller as CoreController;

class ProjectController extends CoreController
{
    public function index()
    {
        // Use core functionality
        return $this->coreMethod();
    }
}
```

## 🔧 **Configuration Management**

### **Core Configuration Override**

```php
// config/app.php
return [
    // Load core config first
    ...require(__DIR__ . '/../core/config/app.php'),
    
    // Override with project-specific values
    'name' => 'My Project Name',
    'url' => env('APP_URL', 'https://my-project.com'),
    
    // Add project-specific providers
    'providers' => [
        ...require(__DIR__ . '/../core/config/app.php')['providers'],
        App\Providers\ProjectServiceProvider::class,
    ],
];
```

### **Environment Configuration**

```bash
# .env (project-specific)
APP_NAME="My Project"
APP_URL=https://my-project.com

# Core configurations are inherited
DB_CONNECTION=mysql
DB_HOST=localhost
```

## 📋 **Automated Scripts**

### **Update Script** (`update-core.sh`)

```bash
#!/bin/bash

echo "🔄 Updating core submodule..."

# Update core submodule
cd core
git checkout main
git pull origin main
cd ..

# Show changes
echo "📋 Core updates:"
git diff --submodule core

# Commit update
git add core
git commit -m "Update core submodule: $(cd core && git log --oneline -1)"

echo "✅ Core updated successfully!"
echo "🚀 Don't forget to test and push: git push origin main"
```

### **Development Setup Script** (`setup.sh`)

```bash
#!/bin/bash

echo "🚀 Setting up project development environment..."

# Initialize submodules
git submodule update --init --recursive

# Install dependencies
composer install
npm install

# Copy environment
cp .env.example .env

# Generate key
php artisan key:generate

# Build assets
npm run build

echo "✅ Development environment ready!"
echo "🌐 Run: php artisan serve"
```

## 🚨 **Best Practices**

### **DO:**
- ✅ Always commit submodule updates
- ✅ Pin to specific versions for production
- ✅ Test after core updates
- ✅ Document core version requirements
- ✅ Use core components via namespaces

### **DON'T:**
- ❌ Modify core submodule files directly
- ❌ Commit changes inside core directory
- ❌ Use latest core in production without testing
- ❌ Mix core and project code

## 🔄 **Workflow Examples**

### **Regular Development**

```bash
# Daily workflow
git submodule update --remote core    # Get latest core
composer install                     # Update dependencies
npm run dev                          # Build assets
php artisan serve                    # Start development
```

### **Release Workflow**

```bash
# Before release
cd core
git checkout v1.5.0                 # Pin to stable version
cd ..
git add core
git commit -m "Release: pin core to v1.5.0"

# Test thoroughly
php artisan test

# Deploy
git push origin main
```

## 📞 **Support**

- **Core Issues**: Report to admin.app.icefelix.com repository
- **Project Issues**: Handle in project repository
- **Integration Issues**: Document in project with core version details

## 🎯 **Benefits of Submodule Approach**

1. **🔗 Clear Separation** - Core vs project code
2. **📌 Version Control** - Pin to specific core versions
3. **🔄 Easy Updates** - Update core independently
4. **🧪 Testing** - Test core updates before applying
5. **📦 Modularity** - Multiple projects can use same core
6. **🚀 Deployment** - Core is always included automatically 