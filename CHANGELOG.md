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

### Technical
- Updated package-lock.json dependencies
- Updated various asset metadata files (.DS_Store files)
- Project structure cleanup and simplification 