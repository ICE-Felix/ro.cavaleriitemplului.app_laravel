#!/bin/bash

# ICE-Felix Project Creator with Core Submodule
# This script creates a new project using admin.app.icefelix.com as a Git submodule

set -e

if [ $# -eq 0 ]; then
    echo "Usage: $0 <project-name> [project-repo-url]"
    echo "Example: $0 my-awesome-project git@github.com:ICE-Felix/my-awesome-project.git"
    exit 1
fi

PROJECT_NAME="$1"
PROJECT_REPO="${2:-}"

echo "🚀 Creating new ICE-Felix project: $PROJECT_NAME"
echo "============================================="

# Create project directory
mkdir -p "$PROJECT_NAME"
cd "$PROJECT_NAME"

# Initialize git
echo "📦 Initializing git repository..."
git init
git branch -M main

# Add core as submodule
echo "🔗 Adding core submodule..."
git submodule add git@github.com:ICE-Felix/admin.app.icefelix.com.git core

# Create project structure
echo "📁 Creating project structure..."
mkdir -p app/Http/Controllers
mkdir -p app/Models
mkdir -p app/Services
mkdir -p config
mkdir -p resources/views
mkdir -p resources/js
mkdir -p resources/css
mkdir -p database/migrations
mkdir -p routes

# Create composer.json
echo "📝 Creating composer.json..."
cat > composer.json << EOF
{
    "name": "ice-felix/${PROJECT_NAME}",
    "description": "${PROJECT_NAME} built on ICE-Felix Core",
    "type": "project",
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0"
    },
    "autoload": {
        "psr-4": {
            "App\\\\": "app/",
            "Core\\\\": "core/app/",
            "Database\\\\Factories\\\\": "database/factories/",
            "Database\\\\Seeders\\\\": "database/seeders/"
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
EOF

# Create package.json
echo "📦 Creating package.json..."
cat > package.json << EOF
{
    "name": "${PROJECT_NAME}",
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite"
    },
    "devDependencies": {
        "vite": "^4.0.0",
        "laravel-vite-plugin": "^0.8.0"
    }
}
EOF

# Create .env file
echo "⚙️  Creating .env file..."
cp core/.env.sample .env
sed -i '' "s/APP_NAME=\"App Mommy HAI\"/APP_NAME=\"${PROJECT_NAME}\"/" .env

# Create routes
echo "🛤️  Creating routes..."
cat > routes/web.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Load core routes
require __DIR__.'/../core/routes/web.php';
EOF

cat > routes/api.php << 'EOF'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Load core API routes
require __DIR__.'/../core/routes/api.php';
EOF

# Create welcome view
echo "🎨 Creating welcome view..."
mkdir -p resources/views
cat > resources/views/welcome.blade.php << EOF
<!DOCTYPE html>
<html>
<head>
    <title>${PROJECT_NAME}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; }
        .feature { background: #f5f5f5; padding: 20px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 ${PROJECT_NAME}</h1>
            <p>Built on ICE-Felix Core</p>
        </div>
        
        <div class="feature">
            <h3>✅ Core Integration</h3>
            <p>This project uses ICE-Felix Core as a Git submodule for shared functionality.</p>
        </div>
        
        <div class="feature">
            <h3>🔗 Linked Repository</h3>
            <p>Core updates can be pulled independently while maintaining project-specific code.</p>
        </div>
        
        <div class="feature">
            <h3>🛠️ Next Steps</h3>
            <ul>
                <li>Run: <code>composer install</code></li>
                <li>Run: <code>npm install</code></li>
                <li>Run: <code>php artisan key:generate</code></li>
                <li>Run: <code>php artisan serve</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
EOF

# Create example controller
echo "🎮 Creating example controller..."
cat > app/Http/Controllers/ProjectController.php << 'EOF'
<?php

namespace App\Http\Controllers;

use Core\Http\Controllers\Controller as CoreController;

class ProjectController extends CoreController
{
    public function index()
    {
        return view('welcome');
    }
    
    public function dashboard()
    {
        // Use core functionality
        return view('dashboard');
    }
}
EOF

# Create .gitignore
echo "📝 Creating .gitignore..."
cat > .gitignore << 'EOF'
.env
.idea/
node_modules/
public/build/
vendor/
bootstrap/cache/
storage/app/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
EOF

# Create README for the project
echo "📚 Creating project README..."
cat > README.md << EOF
# ${PROJECT_NAME}

Built on **ICE-Felix Core** using Git submodules.

## 🚀 Quick Start

\`\`\`bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Build assets
npm run build

# Start development server
php artisan serve
\`\`\`

## 🔗 Core Integration

This project uses [ICE-Felix Core](https://github.com/ICE-Felix/admin.app.icefelix.com) as a Git submodule in the \`core/\` directory.

### Updating Core

\`\`\`bash
# Update to latest core version
cd core
git checkout main
git pull origin main
cd ..

# Commit the update
git add core
git commit -m "Update core submodule"
git push origin main
\`\`\`

### Core Components

You can use core components in your views:

\`\`\`php
<x-core::input name="title" />
<x-core::select name="category" :options="\\$categories" />
\`\`\`

## 📁 Project Structure

\`\`\`
${PROJECT_NAME}/
├── core/                    # Git submodule → ICE-Felix Core
├── app/                     # Project-specific code
├── resources/               # Project views & assets
├── config/                  # Project configuration
└── routes/                  # Project routes
\`\`\`

## 🛠️ Development

- **Core changes**: Don't modify files in \`core/\` directly
- **Project code**: Add your code in \`app/\`, \`resources/\`, etc.
- **Configuration**: Override core config in your project config files
- **Routes**: Add project routes in \`routes/\` files

## 📞 Support

- **Core issues**: Report to [ICE-Felix Core](https://github.com/ICE-Felix/admin.app.icefelix.com)
- **Project issues**: Handle in this repository
EOF

# Initialize submodules
echo "🔄 Initializing submodules..."
git submodule update --init --recursive

# Initial commit
echo "📝 Creating initial commit..."
git add .
git commit -m "Initial commit: ${PROJECT_NAME} with ICE-Felix Core submodule"

# Add remote if provided
if [ -n "$PROJECT_REPO" ]; then
    echo "🌐 Adding remote repository..."
    git remote add origin "$PROJECT_REPO"
    echo "💡 Don't forget to push: git push -u origin main"
fi

echo ""
echo "🎉 Project '$PROJECT_NAME' created successfully!"
echo ""
echo "📋 Next steps:"
echo "1. cd $PROJECT_NAME"
echo "2. composer install"
echo "3. npm install"
echo "4. php artisan key:generate"
echo "5. php artisan serve"
echo ""
echo "🔗 Core submodule: core/ → admin.app.icefelix.com"
echo "📖 Documentation: See README.md and core/SUBMODULE_USAGE.md" 