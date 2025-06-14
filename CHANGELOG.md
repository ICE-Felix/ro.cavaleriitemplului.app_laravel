# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Changed
- Updated favicon images (android-chrome-192x192.png, android-chrome-512x512.png, apple-touch-icon.png, favicon-16x16.png, favicon-32x32.png)
- Added new favicon.png
- Updated favicon.ico
- Modified logo.png
- Updated application logo component in resources/views/components/application-logo.blade.php

### Fixed
- Fixed duplicate logout route names in routes/auth.php
  - Renamed GET logout route to 'logout.get' to avoid naming conflicts
  - Resolved Laravel route caching errors during deployment
- Created missing secondary-button.blade.php component
  - Fixed "Unable to locate a class or view for component [secondary-button]" error
  - Added proper Tailwind CSS styling for secondary button appearance
- Fixed Laravel deployment permissions for Bitnami AWS Lightsail
  - Added sudo chown/chmod commands to deployment workflow
  - Resolved "Operation not permitted" errors during deployment
  - Fixed UnexpectedValueException in storage/logs/laravel.log access

### Removed
- Removed Supabase integration and all related files
  - Deleted supabase configuration files (.gitignore, config.toml, deno.json, tsconfig.json)
  - Removed all Supabase Edge Functions (create_contract, create_enrollment, create_invoice, create_subscription, cron_payment_reminder, cron_renew_subscriptions, record_form_data, send_email, send_sms)
  - Deleted Supabase message templates and utility functions
  - Removed Oblio service integration files
  - Deleted database seed file (seed.sql)
  - Cleaned up VS Code workspace settings for Supabase

### Added
- Added .env.sample file for environment configuration template
- Created secondary-button.blade.php component for UI consistency
- Added JSON configuration files to git tracking (storage/app/json/)
  - 32 schema definition files for application data structures
  - Database table configurations, form field definitions, and API endpoints
  - Essential configuration files for application functionality

### Technical
- Updated package-lock.json dependencies
- Updated various asset metadata files (.DS_Store files)
- Project structure cleanup and simplification
- Improved deployment workflow with Laravel cache optimization steps
- Enhanced permission handling for Bitnami server deployments 