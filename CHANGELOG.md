# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
- Implemented comprehensive debugging system for dynamic CRUD operations
  - Added configurable debug flags in entity JSON files (`"debug": ["GET", "POST", "UPDATE", "DELETE"]`)
  - Added granular debug output for each CRUD operation stage
  - Implemented step-by-step debug flow: request data → Supabase communication → final state
  - Added final debug dumps before views/redirects for complete operation visibility
  - Debug output includes raw request data, processed data, Supabase requests/responses, and final states
- Created comprehensive Dynamic CRUD System Documentation (`DYNAMIC_CRUD_DOCUMENTATION.md`)
  - Complete developer guide for creating new entities
  - Detailed component documentation for all field types (text, trix, numeric, select, checkbox, image, date)
  - Architecture overview and communication flow diagrams
  - Step-by-step entity creation process with examples
  - Debugging system usage guide and troubleshooting section
  - Best practices, common issues, and advanced features documentation
- Enhanced error handling in blade templates
  - Added null checks for array keys to prevent "Undefined array key" errors
  - Improved graceful handling of missing ID fields in data tables
  - Added fallback displays for records without proper ID fields
- Added location picker component to edit form
  - Integrated location picker in edit views
  - Added support for existing location data display
  - Implemented fallback coordinates (44.4268, 26.1025)
  - Added proper error handling and success messages
- Implemented Trix rich text editor component
  - Added TrixEditor blade component with error handling
  - Integrated Trix editor in create and edit forms
  - Added custom CSS styling for Trix editor
  - Added proper value handling and HTML entity decoding

### Changed
- Enhanced SupabaseService with configurable debug output
  - Updated all edge function methods (create_edge, read_edge, update_edge, delete_edge) to accept debug parameters
  - Added conditional debug output using dump() for informational data and dd() for final error states
  - Improved error handling with detailed debug information for HTTP errors and exceptions
  - Added request/response logging for better troubleshooting capabilities
- Upgraded GeneralController debugging capabilities
  - Added debug checks in all CRUD operations (index, store, update, destroy, edit)
  - Implemented final debug dumps before redirects and view rendering
  - Added comprehensive data inspection at each processing stage
  - Enhanced error tracking and state validation for all operations
- Updated news.json configuration with debug flags for targeted debugging control
- Added text excerpt functionality for Trix editor content in data tables
  - Limited Trix content display to 100 characters
  - Added automatic ellipsis for truncated content
  - Stripped HTML tags for cleaner excerpt display
- Updated `storage/app/json/locale.json` schema configuration
  - Fixed singular name from "local" to "locale" for consistency
  - Updated placeholder text to be more descriptive ("en" for code, "English" for label)
- Updated `storage/app/json/users.json` schema configuration
  - Restructured user fields: split name into first_name and last_name
  - Added phone field for better contact information management
  - Changed role field to userrole for better naming convention
  - Simplified edge permissions to use consistent 'edge' pattern
- Improved data table display with enhanced null checking
  - Added proper null checking in data index view to prevent display errors
  - Enhanced conditional rendering for select field data sources
- Enhanced user avatar display with dynamic initials generation
  - Avatar now displays initials based on session user's name
  - Supports both full name format (First Last → FL) and email format (user@domain.com → US)
  - Falls back to single name first two characters or 'U' if no name available
  - Automatically converts initials to uppercase for consistency
- Updated favicon images (android-chrome-192x192.png, android-chrome-512x512.png, apple-touch-icon.png, favicon-16x16.png, favicon-32x32.png)
- Added new favicon.png
- Updated favicon.ico
- Modified logo.png
- Updated application logo component in resources/views/components/application-logo.blade.php
- Refactored Supabase API endpoints and methods
  - Simplified edge function method mapping in GeneralController
  - Updated CRUD operations to use consistent URL patterns
  - Improved error handling and response parsing
  - Enhanced data validation and processing
- Updated contacts.json schema configuration
  - Simplified contact fields for better usability
  - Changed from mixed API endpoints to consistent 'edge' pattern
- Improved data table display formatting
  - Removed automatic ucfirst() formatting for better data presentation
- Enhanced form handling for select fields
  - Improved edge function support in data source resolution
  - Fixed boolean field casting in edit forms (true/false to 1/0)
  - Better error handling for dropdown data population
- Updated `storage/app/json/news.json` schema/config. See file for details.

### Fixed
- Fixed "Undefined array key 'id'" errors in data index view
  - Added proper null checks for ID fields in blade templates
  - Enhanced error handling for records missing required keys
  - Improved graceful fallback for invalid data structures
- Resolved debugging output inconsistencies in CRUD operations
  - Standardized dump() vs dd() usage: dump() for informational output, dd() for final states
  - Fixed success case debugging to allow continued execution to views
  - Ensured error case debugging properly halts execution for inspection
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
- Improved Supabase service error handling
  - Added proper exception handling for edge functions
  - Enhanced API response validation and error reporting
  - Fixed method parameter consistency across CRUD operations

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
- Added Partner Management System
  - New partners.json schema configuration for business partner management
  - Comprehensive partner fields: company details, tax information, banking, contacts
  - Boolean field support with proper casting for active/inactive status
  - Integration with contacts system for administrator assignment

### Technical
- Updated package-lock.json dependencies
- Updated various asset metadata files (.DS_Store files)
- Project structure cleanup and simplification
- Improved deployment workflow with Laravel cache optimization steps
- Enhanced permission handling for Bitnami server deployments 