# Mommy HAI Application

A modern Laravel-based web application for comprehensive business management.

## About This Project

This application is built with Laravel 10 and provides a complete business management solution with dynamic data handling, user management, and modern web interfaces. The application uses JSON-based configuration files for flexible schema management.

## Features

- **Laravel 10** - Modern PHP framework with robust features
- **Dynamic Data Management** - JSON-based schema configuration for flexible data models
- **Modern UI** - Built with Tailwind CSS for responsive design
- **Component Architecture** - Reusable Blade components for consistent UI
- **Vite Build System** - Fast asset compilation and hot module replacement
- **Alpine.js** - Lightweight JavaScript framework for interactivity
- **Automated Deployment** - GitHub Actions workflow for seamless deployments
- **Permission System** - Role-based access control
- **Multi-Model Support** - Support for various business entities (users, contacts, products, etc.)

## Architecture

### Data Models
The application supports multiple business entities through JSON configuration:
- **User Management** - Users, roles, permissions
- **Contact Management** - Contacts, agents, students
- **Partner Management** - Business partners with comprehensive company details, tax information, and banking
- **Business Operations** - Contracts, subscriptions, enrollments
- **Financial Management** - Payments, invoices, transactions, wallets
- **Product Management** - Products, categories, gateways
- **Event Management** - Events, timeslots, series

### Configuration System
Each data model is defined in `/storage/app/json/` with schema definitions that include:
- Field types and validation
- Display labels and formatting
- API endpoint configurations
- UI visibility and permissions

## Local Development Setup

### Prerequisites

- **PHP 8.1+** with required extensions
- **Composer** for PHP dependency management
- **Node.js 16+** and npm for frontend assets
- **Git** for version control

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/ICE-Felix/app.mommyhai.com.git
   cd app.mommyhai.com
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   ```bash
   cp .env.sample .env
   ```
   Edit `.env` with your configuration values

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Set up storage permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

7. **Clear and optimize caches**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   php artisan route:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

8. **Start development servers**
   
   **Terminal 1 - Laravel Server:**
   ```bash
   php artisan serve
   ```
   
   **Terminal 2 - Vite Development Server:**
   ```bash
   npm run dev
   ```

9. **Access the application**
   - Navigate to `http://localhost:8000`

## Deployment

The application includes automated deployment via GitHub Actions:

- **Target Environment**: Bitnami on AWS Lightsail
- **Deployment Trigger**: Push to main branch
- **Process**: Automated file transfer, dependency installation, and cache optimization
- **Permissions**: Automatically configured for Laravel requirements

## Configuration Files

### JSON Schema Files
Located in `/storage/app/json/`, these files define:
- Data structure and validation rules
- Form field configurations
- API endpoint mappings
- Display formats and labels

### Key Configuration Files
- `users.json` - User management schema
- `contacts.json` - Contact management configuration
- `partners.json` - Business partner management with company and financial details
- `products.json` - Product catalog structure
- `payments.json` - Payment processing schema
- And 29+ other entity configurations

## Development Workflow

1. **Backend Development**
   - Modify controllers in `/app/Http/Controllers/`
   - Update service classes in `/app/Services/`
   - Configure data schemas in `/storage/app/json/`

2. **Frontend Development**
   - Edit Blade templates in `/resources/views/`
   - Modify components in `/resources/views/components/`
   - Update styles and assets in `/resources/`

3. **Schema Management**
   - Modify JSON configuration files for data models
   - Update field definitions and validation rules
   - Configure display options and permissions

## Support & Documentation

- **Application**: [https://app.mommyhai.com](https://app.mommyhai.com)
- **Support**: [https://support.mommyhai.com](https://support.mommyhai.com)
- **Documentation**: [https://docs.app.mommyhai.com](https://docs.app.mommyhai.com)

## License

This project is proprietary software. All rights reserved.
