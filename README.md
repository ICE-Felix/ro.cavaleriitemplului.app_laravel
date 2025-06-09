# Mommy HAI Application

A Laravel-based application with Supabase integration for modern web development.

## About This Project

This application is built with Laravel and uses Supabase as the backend database service. All data management is handled through Supabase, eliminating the need for local database setup.

## Features

- Laravel 10 framework
- Supabase integration for database operations
- Modern UI with Tailwind CSS
- Vite for asset compilation
- Alpine.js for frontend interactivity

## Local Development Setup

Follow these steps to set up the application for local development:

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 16+ and npm
- Git

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
   - Copy `env.example` to `.env`: `cp env.example .env`
   - Update the configuration values as needed (see Configuration section below)

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Start the development servers**
   
   **Terminal 1 - Laravel Server:**
   ```bash
   php artisan serve
   ```
   
   **Terminal 2 - Vite Development Server:**
   ```bash
   npm run dev
   ```

7. **Access the application**
   - Open your browser and navigate to `http://localhost:8000`

## Configuration

The application uses the following key configuration:

- **Database**: All database operations are managed by Supabase (no local database required)
- **Supabase URL**: Configure your Supabase project URL
- **Supabase Key**: Add your Supabase anon key
- **App Settings**: Customize app name, logo, and other branding elements

See `env.example` for all available configuration options.

## Development Workflow

1. **Backend Development**: Use Laravel's built-in features with Supabase as the data layer
2. **Frontend Development**: Assets are compiled with Vite - changes will hot-reload automatically
3. **Database Operations**: All database queries go through Supabase - no local migrations needed

## Support & Documentation

- **Support**: [https://support.mommyhai.com](https://support.mommyhai.com)
- **User Guide**: [https://docs.app.mommyhai.com/user-guide](https://docs.app.mommyhai.com/user-guide)
- **API Documentation**: [https://docs.app.mommyhai.com/api](https://docs.app.mommyhai.com/api)

## License

This project is proprietary software. All rights reserved.
