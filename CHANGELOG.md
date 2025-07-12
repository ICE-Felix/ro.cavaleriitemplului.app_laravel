# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Added
- **Hierarchical Category Selection System for Venues**
  - Created new `hierarchical_checkbox` field type for dynamic parent-child category selection
  - Implemented `read_edge_filtered` method in SupabaseService for filtered database queries
  - Added support for PostgREST-style query parameters (`parent_id=is.null`, `parent_id=eq.{id}`)
  - Built dynamic AJAX-based subcategory loading system
  - Created `/api/subcategories/{table}` endpoint for real-time subcategory fetching

- **Enhanced Venue Category Management**
  - Updated `venue_categories.json` to use filtered queries for parent category selection
  - Modified `venues.json` to support hierarchical category selection with `hierarchical_checkbox` type
  - Added `subcategory_source` configuration for defining child category data sources
  - Implemented parent-child relationship filtering using `parent_id` field

- **New Blade Component: hierarchical-checkbox.blade.php**
  - Interactive checkbox component with expandable subcategory sections
  - Real-time subcategory loading with loading states and error handling
  - Automatic parent-child checkbox relationship management
  - Support for multiple parent category selection with independent subcategory lists
  - Responsive design with proper indentation and visual hierarchy
  - Form validation support with state preservation

- **Switch Component for Active/Inactive States**
  - New `switch` component type for boolean toggle functionality (`resources/views/components/switch.blade.php`)
  - Modern toggle switch UI with smooth CSS animations and visual feedback
  - Customizable ON/OFF labels (default: "Active"/"Inactive")
  - Automatic boolean conversion: ON state saves as `true`, OFF state saves as `false`
  - Accessible design with keyboard navigation support and ARIA attributes
  - Error handling and validation integration with form error display
  - Proper form submission with hidden input fallback for unchecked states
  - Responsive design with consistent styling across all screen sizes

- **Schedule Component for Business Hours Management**
  - New `schedule` component type for weekly business hours configuration (`resources/views/components/schedule.blade.php`)
  - Interactive weekly schedule with day-by-day enable/disable functionality
  - Individual time pickers for opening and closing hours for each day of the week
  - Quick action buttons: "Enable All Days", "Disable All Days", "Set Business Hours (Mon-Fri 9-17)"
  - Real-time schedule preview showing complete weekly schedule summary
  - Visual feedback with green highlighting for open days and gray for closed days
  - Status badges showing "( Open )" or "( Closed )" for each day
  - Responsive design optimized for both desktop and mobile devices
  - JSON data storage in Supabase with flexible structure for complex queries
  - Smooth animations and transitions for enhanced user experience

### Enhanced
- **Supabase Service Layer Improvements**
  - Extended `SupabaseService` with `read_edge_filtered()` method for dynamic query filtering
  - Added array-based filter processing (`["field", "operator", "value"]` format)
  - Enhanced query parameter handling for PostgREST-style filtering
  - Improved debug logging for filtered queries with detailed parameter inspection
  - Added `is_assoc()` helper method for proper array type detection

- **GeneralController Enhancements**
  - Extended `getSourceData()` method to handle filtered data sources
  - Added `getSubcategories()` API method for AJAX subcategory loading
  - Enhanced form field processing to support `hierarchical_checkbox` type
  - Improved filter extraction from JSON schema configurations
  - Added comprehensive debug logging for filter processing pipeline
  - **Switch Component Support**: Added boolean type casting for `switch` field types
    - Automatic conversion of form values ('1', 1, true, 'true') to boolean true
    - Proper handling of unchecked switch states (false, '0', 0) to boolean false
    - Integrated switch processing in both `store()` and `update()` methods
  - **Schedule Component Support**: Added JSON data processing for `schedule` field types
    - Automatic JSON string decoding for schedule data from form submissions
    - Proper handling of schedule data structure with day-by-day configuration
    - Integrated schedule processing in both `store()` and `update()` methods
    - Support for null/empty schedule data with proper fallback handling

- **Form View Improvements**
  - Updated `create.blade.php` and `edit.blade.php` to support hierarchical checkbox rendering
  - Added proper value handling for nested category selections
  - Enhanced form state preservation during validation errors
  - Improved component naming for multiple hierarchical checkbox instances
  - **Switch Component Integration**: Added switch field type support in form views
    - Integrated switch component rendering in both create and edit forms
    - Added proper value handling for boolean states in edit mode
    - Enhanced form validation error display for switch fields
  - **Schedule Component Integration**: Added schedule field type support in form views
    - Integrated schedule component rendering in both create and edit forms
    - Added proper value handling for JSON schedule data in edit mode
    - Enhanced form validation error display for schedule fields
    - Support for old() form data preservation during validation errors

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
- **Updated JSON Schema Configurations for Switch Component**
  - Updated `storage/app/json/venues.json`: Replaced `is_active` checkbox with switch component
    - Changed from `"type": "checkbox"` to `"type": "switch"`
    - Added `"on_label": "Active"` and `"off_label": "Inactive"` for better UX
    - Updated label from "Active" to "Venue Status" for clarity
  - Updated `storage/app/json/venue_categories.json`: Replaced `active` checkbox with switch component
    - Changed from `"type": "checkbox"` to `"type": "switch"`
    - Added `"on_label": "Active"` and `"off_label": "Inactive"` for better UX
    - Updated label from "Active" to "Category Status" for clarity
  - Updated `storage/app/json/venues.json`: Added schedule component for business hours
    - Added new `business_hours` field with `"type": "schedule"`
    - Configured for weekly business hours management with JSON data storage
    - Set as non-required field with null default value for flexible venue configuration

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
- Updated `storage/app/json/contacts.json` schema configuration
  - Enhanced contact structure with separate first_name and last_name fields
  - Added comprehensive department type selection with predefined options
  - Included department types: vanzari, management, operational, tehnic, financiar, suport-clienti, administrator
  - Added proper labeling for singular/plural forms
- Updated `storage/app/json/contracts.json` schema configuration
  - Added partner selection linked to partners table via partner_id
  - Integrated contract type selection linked to contract_types table
  - Added file_url field for contract document management
  - Implemented approval system with checkbox for is_active status
  - Enhanced contract numbering and commenting system
- Created new `storage/app/json/contract_types.json` configuration
  - Simple contract type management with id and name fields
  - Proper singular/plural labeling structure
  - Full CRUD operations support via edge functions
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
- **Enhanced Dynamic CRUD Documentation**
  - Added comprehensive documentation for new Schedule component in `DYNAMIC_CRUD_DOCUMENTATION.md`
  - Documented complete schedule data structure with JSON examples
  - Added usage examples for different business types (restaurant, retail, office, 24/7)
  - Documented visual features, quick actions, and responsive design capabilities
  - Updated section numbering for all subsequent field types (9. Image Upload, 10. Date Input, 11. Hidden Fields)

### Fixed
- **Fixed Hierarchical Category Selection System Issues**
  - **API Route Authentication**: Fixed `/api/subcategories/{table}` route not being protected by authentication middleware
    - Moved API route inside `middleware(['supabase.auth', 'supabase.permissions'])` group in `routes/web.php`
    - Resolved "Error loading subcategories" due to unauthenticated requests
  - **Dynamic Table Name Resolution**: Fixed hardcoded table name in hierarchical checkbox JavaScript
    - Implemented dynamic table name extraction from `subcategorySource` configuration
    - Added `data-table-name` attribute to hierarchical checkbox container
    - JavaScript now uses `fetch(\`/api/subcategories/\${tableName}?parent_id=\${parentId}\`)` instead of hardcoded `venue_categories`
  - **Route Cache Issues**: Cleared route cache to ensure new API routes are properly registered
    - Executed `php artisan route:clear` to resolve route registration problems
  - **Enhanced Debugging**: Added comprehensive logging to `getSubcategories()` method
    - Added request logging with table, parent_id, headers, and authentication status
    - Added detailed error logging with stack traces for troubleshooting
    - Added success logging with subcategory count and data for verification

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
- **Hierarchical Category Filtering Logic Implementation**
  - **Filter Array Format**: Implemented support for `["field", "operator", "value"]` filter arrays in JSON configurations
  - **PostgREST Query Translation**: Added automatic conversion from filter arrays to PostgREST query parameters:
    - `["parent_id", "is", null]` → `parent_id=is.null`
    - `["parent_id", "eq", "123"]` → `parent_id=eq.123`
    - `["parent_id", "neq", "123"]` → `parent_id=neq.123`
  - **Edge Function Integration**: Enhanced Supabase Edge Functions to handle filtered queries:
    - Parse query parameters (`parent_id=is.null`)
    - Convert to SQL conditions (`WHERE parent_id IS NULL`)
    - Return filtered results with proper success/error responses
  - **AJAX Loading Pipeline**: Implemented real-time subcategory loading:
    1. User checks parent category checkbox
    2. JavaScript triggers AJAX request to `/api/subcategories/{table}?parent_id={id}`
    3. Laravel controller calls `read_edge_filtered()` with `["parent_id", "eq", "{id}"]`
    4. Supabase Edge Function executes filtered query
    5. Results rendered as nested checkboxes with proper form integration
  - **Component State Management**: Added sophisticated checkbox state handling:
    - Parent checkbox controls subcategory visibility
    - Unchecking parent automatically unchecks all children
    - Multiple parent selections maintain independent subcategory lists
    - Form validation preserves selected state across page reloads

- Updated package-lock.json dependencies
- Updated various asset metadata files (.DS_Store files)
- Project structure cleanup and simplification
- Improved deployment workflow with Laravel cache optimization steps
- Enhanced permission handling for Bitnami server deployments 