# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

### Fixed
- **Gallery Component Bug Fix**
  - Fixed undefined property error `$supabaseUrl` in SupabaseService
- **File Browser Component Support**
  - Added missing `@case('file-browser')` support in `create.blade.php` for file upload functionality
  - Added missing `@case('file-browser')` support in `edit.blade.php` for file editing functionality  
  - Added missing `@case('file-browser')` support in `index.blade.php` for file display in tables
  - File browser fields now properly render in forms and display download links in index tables
  - Supports both image and non-image file types with configurable `is_image` parameter
  - Files display as clickable download buttons with proper styling in data tables
      - Corrected `getStoragePublicUrl()` method to use `$this->baseUrl` instead of non-existent `$this->supabaseUrl`
    - Gallery image upload now works correctly with proper public URL generation

### Added
- **Contract Management System**
  - Added complete contract management functionality with `contracts.json` schema configuration
  - Contract fields include: number, partner selection, contract type, file upload, comments, and active status
  - Integrated with partner selection via dynamic dropdown (linked to partners table)
  - Contract type selection via dynamic dropdown (linked to contract_types table)
  - File upload capability using file-browser component for contract document storage
  - Active/inactive status management using switch component
  - Full CRUD operations (Create, Read, Update, Delete) via Supabase Edge functions
  - Debug logging enabled for POST, INSERT, UPDATE, PUT operations
- **Contract Types Management**
  - Added contract types management system with `contract_types.json` schema configuration
  - Simple structure with ID and name fields for categorizing different contract types
  - Full CRUD operations support for managing contract type categories
  - Integrated with contracts system for dynamic contract type selection
  - Supports creating, editing, and deleting contract type categories
  - Used as reference data for contract creation and management
- **Location Component Data Processing**
  - Fixed location data not being saved to database due to missing field type handling
  - Added proper processing for `location` field type in GeneralController
  - Location picker now correctly saves `location_latitude` and `location_longitude` fields to Supabase
  - Enhanced both `store()` and `update()` methods to handle location coordinate data
- **Schedule Component Data Persistence**
  - Fixed schedule data not being saved properly to Supabase JSON fields
  - Enhanced schedule handling to ensure proper JSON string encoding for database storage
  - Schedule changes are now persistent and correctly saved/loaded from database
  - Improved JSON validation and error handling for schedule data processing
  - Added debug logging for schedule data processing to help troubleshoot issues
  - Fixed JavaScript hidden input field targeting issue (ID/name mismatch)
  - Schedule component now properly updates the form field that gets submitted
  - Added comprehensive JavaScript debugging to track schedule data updates and hidden input changes
  - Added form submission debugging to verify data being sent to server
  - Added GeneralController request debugging to verify data being received
  - Added debug button to manually check schedule data state
  - Enhanced updateHiddenInput function with multiple element finding methods and detailed logging
  - Added comprehensive debugging to identify why hidden input field is not being updated
- **Venue Category Display Enhancement**
  - Added `venue_category_titles` field to venues table display
  - Hidden the `categories` hierarchical checkbox field from table view (visible only in forms)
  - Enabled display of prettier category names instead of raw category IDs in venues list
  - Enhanced venues.json configuration to support both form input and table display of categories
- **Table Display Array Support**
  - Enhanced default field display in `index.blade.php` to handle both strings and arrays
  - Arrays of strings are now automatically converted to comma-separated values for display
  - Improved compatibility with fields that may return multiple values (like `venue_category_titles` and `attribute_names`)
  - Added dedicated `switch` case with array support for boolean toggle fields
  - Switch arrays display proper on/off labels (e.g., "Active, Inactive, Active" for multiple switch values)
  - Added dedicated `schedule` case for readable business hours display
  - Schedule data now shows formatted hours (e.g., "Mon: 9:00 AM-5:00 PM, Tue: 9:00 AM-5:00 PM")
  - Enhanced schedule display with proper time formatting and closed days handling

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

- **Gallery Management System for Venues**
  - New `gallery` component type for multi-image upload and management (`resources/views/components/gallery.blade.php`)
  - Drag & drop image upload with visual feedback and progress indicators
  - Multiple image selection support (configurable 1-6 images per gallery)
  - Real-time image preview with responsive grid layout
  - Individual image editing modal with alt text and caption fields
  - Image deletion functionality with confirmation dialogs
  - File validation (JPG, PNG, GIF formats, max 5MB per image)
  - Automatic unique filename generation to prevent conflicts
  - Empty state with helpful upload instructions and visual cues
  - Mobile-responsive design with touch-friendly controls
  - Supabase Storage integration for secure cloud image storage
  - JSON metadata storage with organized folder structure per gallery

### Enhanced
- **Supabase Service Layer Improvements**
  - Extended `SupabaseService` with `read_edge_filtered()` method for dynamic query filtering
  - Added array-based filter processing (`["field", "operator", "value"]` format)
  - Enhanced query parameter handling for PostgREST-style filtering
  - Improved debug logging for filtered queries with detailed parameter inspection
  - Added `is_assoc()` helper method for proper array type detection
  - **Gallery Storage Integration**: Extended SupabaseService with comprehensive storage methods
    - Added `uploadToStorage()` method for file uploads to Supabase Storage buckets
    - Added `deleteFromStorage()` method for file deletion from storage
    - Added `getStoragePublicUrl()` method for generating public URLs for stored files
    - Added `listStorageFiles()` method for listing files in storage buckets
    - Implemented proper error handling and response processing for all storage operations
    - Added support for custom content types and upload options (upsert functionality)

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
  - **Gallery Component Support**: Added comprehensive gallery management API methods
    - Added `uploadGalleryImage()` method for handling image uploads with validation
    - Added `deleteGalleryImage()` method for secure image deletion from storage
    - Added `listGalleryImages()` method for retrieving gallery image listings
    - Integrated gallery data processing in both `store()` and `update()` methods
    - Added proper file validation (type, size, format) and error handling
    - Implemented unique filename generation and path organization
    - Added support for gallery metadata management (alt text, captions, ordering)

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
  - **Gallery Component Integration**: Added gallery field type support in form views
    - Integrated gallery component rendering in both create and edit forms
    - Added proper value handling for JSON gallery data in edit mode
    - Enhanced form validation error display for gallery fields
    - Support for configurable gallery properties (min/max images, bucket name)
    - Added gallery data preservation during form validation errors

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
- Added Gallery Management API Routes (`routes/web.php`)
  - Added `POST /api/gallery/upload` endpoint for image uploads
  - Added `DELETE /api/gallery/delete` endpoint for image deletion
  - Added `GET /api/gallery/{galleryId}/images` endpoint for listing gallery images
  - All routes protected with authentication and permission middleware
  - Comprehensive error handling and response formatting for all endpoints
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
  - Updated `storage/app/json/venues.json`: Added gallery component for venue images
    - Added new `gallery` field with `"type": "gallery"`
    - Configured for 1-6 images per venue with `venue-galleries` storage bucket
    - Set as required field with comprehensive validation and metadata support
    - Added configurable min/max image limits and bucket specification

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