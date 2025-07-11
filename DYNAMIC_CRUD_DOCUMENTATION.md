# Dynamic CRUD System Documentation

## Overview

This system provides a dynamic CRUD (Create, Read, Update, Delete) interface that allows developers to create new entities without writing repetitive controller code. The system uses JSON configuration files to define entity schemas and automatically generates forms, validation, and database operations.

## Architecture

### Core Components

1. **JSON Configuration Files** (`storage/app/json/*.json`) - Define entity schemas
2. **GeneralController** (`app/Http/Controllers/GeneralController.php`) - Handles all CRUD operations
3. **SupabaseService** (`app/Services/Supabase/SupabaseService.php`) - Manages database communication
4. **Dynamic Blade Components** (`resources/views/components/*.blade.php`) - Render form fields
5. **Views** (`resources/views/data/*.blade.php`) - Display data in consistent layouts

### Communication Flow

```
JSON Config → GeneralController → SupabaseService → Supabase Edge Functions → Database
```

## Entity Configuration Structure

### Basic Configuration (`storage/app/json/entity_name.json`)

```json
{
    "name": {
        "singular": "news",
        "plural": "news",
        "label_singular": "Article",
        "label_plural": "Articles"
    },
    "debug": ["GET", "POST", "UPDATE", "DELETE"],
    "schema": {
        // Field definitions go here
    },
    "GET": "edge",
    "INSERT": "edge", 
    "UPDATE": "edge",
    "DELETE": "edge",
    "order_by": ["created_at", "desc"]
}
```

### Configuration Properties

- **name**: Entity naming configuration
  - `singular`: Database table name (singular)
  - `plural`: Database table name (plural)
  - `label_singular`: Human-readable singular name
  - `label_plural`: Human-readable plural name

- **debug**: Array of operations to debug (`["GET", "POST", "UPDATE", "DELETE"]`)
- **schema**: Field definitions (see Field Types section)
- **GET/INSERT/UPDATE/DELETE**: Database operation methods (`"edge"` for Supabase Edge Functions)
- **order_by**: Default sorting `[field, direction]`

## Field Types and Components

### 1. Text Input (`text`)

```json
{
    "title": {
        "type": "text",
        "label": "Title",
        "placeholder": "Enter title"
    }
}
```

**Component**: `resources/views/components/input.blade.php`
**Usage**: Basic text input fields

### 2. Rich Text Editor (`trix`)

```json
{
    "body": {
        "type": "trix",
        "label": "Body Content",
        "placeholder": "Enter content"
    }
}
```

**Component**: `resources/views/components/trix-editor.blade.php`
**Usage**: Rich text editing with formatting options

### 3. Numeric Input (`numeric`)

```json
{
    "likes": {
        "type": "numeric",
        "label": "Likes",
        "placeholder": "0"
    }
}
```

**Component**: `resources/views/components/numeric-input.blade.php`
**Usage**: Number inputs with validation

### 4. Select Dropdown (`select`)

```json
{
    "category": {
        "type": "select",
        "label": "Category",
        "key": "category_id",
        "data": {
            "type": "class",
            "source": [
                "App\\Services\\Supabase\\SupabaseService",
                "read_edge",
                "categories"
            ],
            "value": "id",
            "name": "name"
        }
    }
}
```

**Component**: `resources/views/components/select.blade.php`
**Usage**: Dropdown selections from dynamic data sources

### 5. Checkbox (`checkbox`)

#### Single Checkbox
```json
{
    "is_published": {
        "type": "checkbox",
        "label": "Published",
        "key": "is_published",
        "value": true,
        "text": "Publish this article"
    }
}
```

#### Multiple Checkboxes
```json
{
    "tags": {
        "type": "checkbox",
        "label": "Tags",
        "key": "tag_ids",
        "value": ["breaking", "important"],
        "options": [
            {"value": "breaking", "name": "Breaking News"},
            {"value": "important", "name": "Important"},
            {"value": "trending", "name": "Trending"}
        ]
    }
}
```

**Component**: `resources/views/components/checkbox.blade.php`
**Usage**: Single or multiple selections

### 6. Image Upload (`image`)

```json
{
    "featured_image": {
        "key": "image_id",
        "type": "image",
        "label": "Featured Image",
        "upload_key": "image_base64"
    }
}
```

**Component**: `resources/views/components/file-browser.blade.php`
**Features**: 
- File upload with preview
- AI image generation
- Base64 encoding
- Image validation

### 7. Date Input (`date`)

```json
{
    "published_at": {
        "type": "date",
        "label": "Publication Date",
        "format": "Y-m-d H:i:s"
    }
}
```

**Component**: `resources/views/components/date-input.blade.php`
**Usage**: Date and time selection

### 8. Hidden Fields

```json
{
    "id": {
        "type": "text",
        "readonly": true,
        "visible": false
    }
}
```

**Properties**:
- `readonly`: Field is not editable
- `visible`: Field is hidden from forms and tables

## Data Sources

### Static Options
```json
{
    "options": [
        {"value": "option1", "name": "Option 1"},
        {"value": "option2", "name": "Option 2"}
    ]
}
```

### Dynamic Data Sources
```json
{
    "data": {
        "type": "class",
        "source": [
            "App\\Services\\Supabase\\SupabaseService",
            "read_edge",
            "table_name"
        ],
        "value": "id",
        "name": "display_field"
    }
}
```

## CRUD Operations

### Create Operation (POST)

1. **Route**: `POST /entity_name`
2. **Controller**: `GeneralController@store`
3. **Process**:
   - Validate and process form data
   - Handle file uploads (convert to base64)
   - Cast numeric fields
   - Call `SupabaseService::create_edge()`
   - Redirect with success message

### Read Operation (GET)

1. **Route**: `GET /entity_name`
2. **Controller**: `GeneralController@index`
3. **Process**:
   - Call `SupabaseService::read_edge()`
   - Apply sorting
   - Process data for display
   - Return index view

### Update Operation (PUT)

1. **Route**: `PUT /entity_name/{id}`
2. **Controller**: `GeneralController@update`
3. **Process**:
   - Load existing data
   - Validate and process form data
   - Handle file uploads
   - Call `SupabaseService::update_edge()`
   - Redirect with success message

### Delete Operation (DELETE)

1. **Route**: `DELETE /entity_name/{id}`
2. **Controller**: `GeneralController@destroy`
3. **Process**:
   - Call `SupabaseService::delete_edge()`
   - Redirect with success message

## Supabase Communication

### Edge Functions

The system uses Supabase Edge Functions for database operations:

- **Create**: `POST /functions/v1/table_name`
- **Read**: `GET /functions/v1/table_name`
- **Update**: `PUT /functions/v1/table_name/{id}`
- **Delete**: `DELETE /functions/v1/table_name/{id}`

### Authentication

All requests include JWT token:
```php
'Authorization' => 'Bearer ' . Session::get('jwt_token')
```

### Response Format

Success Response:
```json
{
    "success": true,
    "data": { ... }
}
```

Error Response:
```json
{
    "success": false,
    "error": {
        "message": "Error description",
        "code": "error_code"
    }
}
```

## Debugging System

### Debug Configuration

Enable debugging in entity JSON:
```json
{
    "debug": ["GET", "POST", "UPDATE", "DELETE"]
}
```

### Debug Output Flow

#### GET Operations
1. `=== GET OPERATION ===` - Shows method and table
2. `=== SUPABASE READ REQUEST ===` - Shows request details
3. `=== SUPABASE READ RESPONSE ===` - Shows response data
4. `=== FINAL DATA TO VIEW ===` - Shows data being sent to view

#### POST Operations
1. `=== RAW REQUEST DATA ===` - Shows form data
2. `=== PROCESSED DATA FOR SUPABASE ===` - Shows cleaned data
3. `=== SUPABASE CALL INFO ===` - Shows method details
4. `=== SUPABASE REQUEST ===` - Shows request to Supabase
5. `=== SUPABASE RESPONSE ===` - Shows response from Supabase
6. `=== FINAL STATE BEFORE REDIRECT (POST) ===` - Shows final state

#### UPDATE Operations
1. `=== UPDATE OPERATION ===` - Shows update details
2. `=== SUPABASE UPDATE REQUEST ===` - Shows request details
3. `=== SUPABASE UPDATE RESPONSE ===` - Shows response data
4. `=== FINAL STATE BEFORE REDIRECT (UPDATE) ===` - Shows final state

#### DELETE Operations
1. `=== DELETE OPERATION ===` - Shows delete details
2. `=== SUPABASE DELETE REQUEST ===` - Shows request details
3. `=== SUPABASE DELETE RESPONSE ===` - Shows response data
4. `=== FINAL STATE BEFORE REDIRECT (DELETE) ===` - Shows final state

## Creating New Entities

### Step 1: Create JSON Configuration

Create `storage/app/json/your_entity.json`:

```json
{
    "name": {
        "singular": "product",
        "plural": "products",
        "label_singular": "Product",
        "label_plural": "Products"
    },
    "debug": [],
    "schema": {
        "id": {
            "type": "text",
            "readonly": true,
            "visible": false
        },
        "name": {
            "type": "text",
            "label": "Product Name",
            "placeholder": "Enter product name"
        },
        "description": {
            "type": "trix",
            "label": "Description",
            "placeholder": "Enter product description"
        },
        "price": {
            "type": "numeric",
            "label": "Price",
            "placeholder": "0.00"
        },
        "category_name": {
            "type": "select",
            "label": "Category",
            "key": "category_id",
            "data": {
                "type": "class",
                "source": [
                    "App\\Services\\Supabase\\SupabaseService",
                    "read_edge",
                    "categories"
                ],
                "value": "id",
                "name": "name"
            }
        },
        "image": {
            "key": "image_id",
            "type": "image",
            "label": "Product Image",
            "upload_key": "image_base64"
        },
        "is_active": {
            "type": "checkbox",
            "label": "Active",
            "key": "is_active",
            "value": true,
            "text": "Product is active"
        }
    },
    "GET": "edge",
    "INSERT": "edge",
    "UPDATE": "edge",
    "DELETE": "edge",
    "order_by": ["created_at", "desc"]
}
```

### Step 2: Create Routes

Add to `routes/web.php`:
```php
Route::resource('products', GeneralController::class);
```

### Step 3: Create Database Table

Ensure your Supabase database has the corresponding table with proper fields.

### Step 4: Create Edge Functions

Create Supabase Edge Functions for your entity if using custom logic.

### Step 5: Test and Debug

1. Enable debugging: `"debug": ["GET", "POST", "UPDATE", "DELETE"]`
2. Access your entity routes
3. Check debug output for issues
4. Disable debugging when ready: `"debug": []`

## Best Practices

### 1. Naming Conventions
- Use snake_case for database fields
- Use descriptive labels for UI
- Keep JSON file names lowercase

### 2. Field Configuration
- Always include `id` field with `readonly: true, visible: false`
- Use appropriate field types for data validation
- Add helpful placeholder text

### 3. Data Sources
- Use class-based data sources for dynamic options
- Cache frequently accessed data
- Handle empty data gracefully

### 4. Debugging
- Enable debugging during development
- Use specific operations for targeted debugging
- Disable debugging in production

### 5. Security
- Validate all input data
- Use proper authentication
- Implement permission checks

## Common Issues and Solutions

### Issue: "Undefined array key 'id'"
**Solution**: Ensure your data includes an `id` field or handle missing IDs in views:
```php
@if(isset($elem['id']))
    // Show edit/delete buttons
@endif
```

### Issue: Select options not loading
**Solution**: Check your data source configuration and ensure the method exists:
```json
{
    "data": {
        "type": "class",
        "source": [
            "App\\Services\\Supabase\\SupabaseService",
            "read_edge",
            "table_name"
        ],
        "value": "id",
        "name": "name"
    }
}
```

### Issue: Image upload not working
**Solution**: Ensure `upload_key` is specified and your server handles base64 data:
```json
{
    "image": {
        "key": "image_id",
        "type": "image",
        "upload_key": "image_base64"
    }
}
```

### Issue: Validation errors
**Solution**: Check field types and ensure data format matches expectations.

## Advanced Features

### AI Image Generation
The file browser component includes AI image generation powered by OpenAI:
- Users can generate images from text prompts
- Multiple size options available
- Automatic file conversion and preview

### Template Parsing
Use template syntax for dynamic field display:
```json
{
    "format": "Published on {created_at} by {author_name}"
}
```

### Permissions
Integrate with your permission system:
```php
SupabaseService::user_have_permission($table_name, $permission_code)
```

## Conclusion

This dynamic CRUD system provides a flexible foundation for rapid application development. By following the patterns and conventions outlined in this documentation, developers can quickly create new entities with minimal code while maintaining consistency and functionality across the application.

For additional help or feature requests, refer to the codebase or contact the development team. 