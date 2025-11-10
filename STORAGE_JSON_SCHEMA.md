# Storage JSON Schema Documentation

## Overview

This document provides comprehensive documentation for the JSON configuration files located in `storage/app/json/`. These files define the structure, behavior, and CRUD operations for entities in the Laravel-Supabase application.

---

## Table of Contents

1. [JSON File Structure](#json-file-structure)
2. [Field Types Reference](#field-types-reference)
3. [Data Source Configuration](#data-source-configuration)
4. [CRUD Methods](#crud-methods)
5. [Advanced Features](#advanced-features)
6. [Real-World Examples](#real-world-examples)
7. [Validation Rules](#validation-rules)

---

## JSON File Structure

### Basic Template

```json
{
  "name": {
    "singular": "entity_name",
    "plural": "entity_names"
  },
  "schema": {
    "field_name": {
      "type": "field_type",
      "label": "Display Label",
      "required": true,
      "placeholder": "Placeholder text"
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "created_at",
    "direction": "desc"
  },
  "debug": []
}
```

### Root Properties

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `name` | Object | Yes | Singular and plural names for the entity |
| `name.singular` | String | Yes | Singular form (e.g., "product") |
| `name.plural` | String | Yes | Plural form (e.g., "products") - must match Supabase table name |
| `schema` | Object | Yes | Field definitions |
| `GET` | String | Yes | Method for reading data: "read", "read_rpc", "read_edge" |
| `INSERT` | String | Yes | Method for creating data: "create", "create_edge" |
| `UPDATE` | String | Yes | Method for updating data: "update", "update_edge" |
| `DELETE` | String | Yes | Method for deleting data: "delete", "delete_edge" |
| `order_by` | Object | No | Default sorting configuration |
| `order_by.key` | String | No | Field to sort by |
| `order_by.direction` | String | No | "asc" or "desc" |
| `debug` | Array | No | Enable debug mode for specific operations: ["GET", "POST", "UPDATE", "DELETE"] |

---

## Field Types Reference

### 1. Text Input (`text`)

Single-line text input field.

```json
{
  "name": {
    "type": "text",
    "label": "Product Name",
    "placeholder": "Enter product name",
    "required": true,
    "minlength": 3,
    "maxlength": 255
  }
}
```

**Properties:**
- `type`: "text"
- `label`: Display label
- `placeholder`: Placeholder text
- `required`: Boolean (default: false)
- `minlength`: Minimum character length
- `maxlength`: Maximum character length
- `readonly`: Boolean (default: false)
- `visible`: Boolean (default: true)

**Rendered as:**
```html
<input type="text" name="name" placeholder="Enter product name" required>
```

---

### 2. Textarea (`textarea`)

Multi-line text input field.

```json
{
  "description": {
    "type": "textarea",
    "label": "Description",
    "placeholder": "Enter detailed description",
    "rows": 5,
    "required": false
  }
}
```

**Properties:**
- `type`: "textarea"
- `label`: Display label
- `placeholder`: Placeholder text
- `rows`: Number of visible rows (default: 5)
- `required`: Boolean

**Rendered as:**
```html
<textarea name="description" rows="5">...</textarea>
```

---

### 3. Email (`email`)

Email address input with validation.

```json
{
  "email": {
    "type": "email",
    "label": "Email Address",
    "placeholder": "user@example.com",
    "required": true
  }
}
```

**Rendered as:**
```html
<input type="email" name="email" required>
```

---

### 4. Phone (`tel`)

Telephone number input.

```json
{
  "phone": {
    "type": "tel",
    "label": "Phone Number",
    "placeholder": "+1 (555) 123-4567"
  }
}
```

**Rendered as:**
```html
<input type="tel" name="phone">
```

---

### 5. URL (`url`)

Website URL input with validation.

```json
{
  "website": {
    "type": "url",
    "label": "Website",
    "placeholder": "https://example.com"
  }
}
```

**Rendered as:**
```html
<input type="url" name="website">
```

---

### 6. Number (`number`)

Numeric input field.

```json
{
  "price": {
    "type": "number",
    "label": "Price",
    "placeholder": "0.00",
    "min": 0,
    "max": 999999,
    "step": 0.01,
    "required": true
  }
}
```

**Properties:**
- `min`: Minimum value
- `max`: Maximum value
- `step`: Increment step (e.g., 0.01 for currency)

**Rendered as:**
```html
<input type="number" name="price" min="0" max="999999" step="0.01" required>
```

---

### 7. Datetime (`datetime-local`)

Date and time picker.

```json
{
  "event_date": {
    "type": "datetime-local",
    "label": "Event Date & Time",
    "required": true
  }
}
```

**Rendered as:**
```html
<input type="datetime-local" name="event_date" required>
```

**Output format:** `2025-11-10T14:30:00`

---

### 8. Date (`date`)

Date picker only.

```json
{
  "birth_date": {
    "type": "date",
    "label": "Date of Birth"
  }
}
```

**Rendered as:**
```html
<input type="date" name="birth_date">
```

**Output format:** `2025-11-10`

---

### 9. Time (`time`)

Time picker only.

```json
{
  "start_time": {
    "type": "time",
    "label": "Start Time"
  }
}
```

**Rendered as:**
```html
<input type="time" name="start_time">
```

**Output format:** `14:30:00`

---

### 10. Checkbox (`checkbox`)

Boolean checkbox field.

```json
{
  "is_active": {
    "type": "checkbox",
    "label": "Active",
    "default": true
  }
}
```

**Properties:**
- `default`: Boolean default value

**Rendered as:**
```html
<input type="checkbox" name="is_active" checked>
```

**Value handling:**
- Checked: `true` or `1`
- Unchecked: `false` or `0`

---

### 11. Select Dropdown (`select`)

Dropdown selection field with static or dynamic options.

#### Static Options

```json
{
  "status": {
    "type": "select",
    "label": "Status",
    "data": [
      {"value": "active", "name": "Active"},
      {"value": "inactive", "name": "Inactive"},
      {"value": "pending", "name": "Pending"}
    ],
    "required": true
  }
}
```

#### Dynamic Options (from Supabase)

```json
{
  "category_name": {
    "type": "select",
    "label": "Category",
    "key": "category_id",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_rpc",
        "categories"
      ],
      "value": "id",
      "name": "name"
    },
    "required": true
  }
}
```

**Properties:**
- `key`: The actual database column name (e.g., "category_id")
- `data`: Array of options OR data source configuration
- `data.type`: "class" for dynamic data
- `data.source`: [Class, Method, Table]
- `data.value`: Field to use as option value
- `data.name`: Field to use as option display name

**Rendered as:**
```html
<select name="category_id" required>
    <option value="">Select Category</option>
    <option value="1">Electronics</option>
    <option value="2">Clothing</option>
</select>
```

---

### 12. Image Upload (`image`)

Single image upload field with Supabase Storage integration.

```json
{
  "image_url": {
    "key": "image_id",
    "type": "image",
    "label": "Featured Image",
    "bucket": "images",
    "required": false
  }
}
```

**Properties:**
- `key`: Database column name for storing image ID
- `bucket`: Supabase Storage bucket name (default: "images")

**How it works:**
1. User selects image file
2. File is uploaded to Supabase Storage bucket
3. UUID is generated and stored in database
4. Public URL is generated for display

**Database value:** UUID (e.g., `"a1b2c3d4-e5f6-7890-abcd-ef1234567890"`)

**Display URL:** `https://project.supabase.co/storage/v1/object/public/images/a1b2c3d4-e5f6-7890-abcd-ef1234567890`

---

### 13. Gallery (`gallery`)

Multiple image upload field with drag-and-drop reordering.

```json
{
  "gallery_urls": {
    "key": "gallery_ids",
    "type": "gallery",
    "label": "Image Gallery",
    "bucket": "galleries"
  }
}
```

**Properties:**
- `key`: Database column name (stores JSON array)
- `bucket`: Supabase Storage bucket name

**Database value:** JSON array of UUIDs
```json
["uuid1", "uuid2", "uuid3"]
```

**Features:**
- Multiple file upload
- Drag-and-drop reordering
- Remove individual images
- Preview thumbnails

---

### 14. Schedule (`schedule`)

Business hours / weekly schedule component.

```json
{
  "business_hours": {
    "type": "schedule",
    "label": "Business Hours",
    "key": "schedule"
  }
}
```

**Database value:** JSON object
```json
{
  "monday": {"enabled": true, "open": "09:00", "close": "17:00"},
  "tuesday": {"enabled": true, "open": "09:00", "close": "17:00"},
  "wednesday": {"enabled": true, "open": "09:00", "close": "17:00"},
  "thursday": {"enabled": true, "open": "09:00", "close": "17:00"},
  "friday": {"enabled": true, "open": "09:00", "close": "17:00"},
  "saturday": {"enabled": false, "open": "10:00", "close": "14:00"},
  "sunday": {"enabled": false, "open": "10:00", "close": "14:00"}
}
```

**Rendered as:**
- Checkbox for each day (enabled/disabled)
- Time pickers for open/close times
- Repeat pattern options

---

### 15. Location Picker (`location`)

Google Maps location picker with address search.

```json
{
  "location": {
    "type": "location",
    "label": "Venue Location",
    "lat_key": "latitude",
    "lng_key": "longitude",
    "address_key": "address"
  }
}
```

**Properties:**
- `lat_key`: Database column for latitude
- `lng_key`: Database column for longitude
- `address_key`: Database column for formatted address

**Database values:**
```
latitude: 40.7128
longitude: -74.0060
address: "New York, NY, USA"
```

**Features:**
- Interactive map
- Address autocomplete
- Marker placement
- Reverse geocoding

---

### 16. Integer (`int`)

Integer field (typically for IDs).

```json
{
  "id": {
    "type": "int",
    "label": "#",
    "readonly": true,
    "visible": false
  }
}
```

**Properties:**
- `readonly`: Cannot be edited
- `visible`: Hide from forms/tables

---

### 17. Hidden (`hidden`)

Hidden input field.

```json
{
  "user_id": {
    "type": "hidden",
    "default": "current_user_id"
  }
}
```

---

## Data Source Configuration

### Static Data

```json
{
  "type": "select",
  "data": [
    {"value": "option1", "name": "Option 1"},
    {"value": "option2", "name": "Option 2"}
  ]
}
```

### Dynamic Data from Supabase

#### Using read_rpc

```json
{
  "category_name": {
    "type": "select",
    "key": "category_id",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_rpc",
        "categories"
      ],
      "value": "id",
      "name": "name"
    }
  }
}
```

#### Using read_edge

```json
{
  "venue_name": {
    "type": "select",
    "key": "venue_id",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_edge",
        "venues"
      ],
      "value": "id",
      "name": "name"
    }
  }
}
```

#### Hierarchical Data (Parent-Child)

```json
{
  "parent_category": {
    "type": "select",
    "key": "parent_id",
    "label": "Parent Category",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_rpc",
        "categories"
      ],
      "value": "id",
      "name": "name",
      "where": {
        "parent_id": null
      }
    }
  },
  "subcategory": {
    "type": "select",
    "key": "subcategory_id",
    "label": "Subcategory",
    "depends_on": "parent_id",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_rpc",
        "categories"
      ],
      "value": "id",
      "name": "name"
    }
  }
}
```

**Behavior:**
- When parent_id changes, subcategory options are fetched via AJAX
- Endpoint: `/api/subcategories/categories?parent_id={parent_id}`

---

## CRUD Methods

### GET Methods

| Method | Description | Use Case |
|--------|-------------|----------|
| `read` | Direct REST API call to table | Simple queries without joins |
| `read_rpc` | Call RPC function | Complex queries with joins/logic |
| `read_edge` | Call Edge Function | **Recommended** - Full control, custom logic |

**Example:**
```json
{
  "GET": "read_edge"
}
```

### INSERT Methods

| Method | Description | Use Case |
|--------|-------------|----------|
| `create` | Direct REST API insert | Simple inserts |
| `create_edge` | Call Edge Function | **Recommended** - Validation, transformations |

**Example:**
```json
{
  "INSERT": "create_edge"
}
```

### UPDATE Methods

| Method | Description | Use Case |
|--------|-------------|----------|
| `update` | Direct REST API update | Simple updates |
| `update_edge` | Call Edge Function | **Recommended** - Business logic |

**Example:**
```json
{
  "UPDATE": "update_edge"
}
```

### DELETE Methods

| Method | Description | Use Case |
|--------|-------------|----------|
| `delete` | Direct REST API delete | Simple deletes |
| `delete_edge` | Call Edge Function | **Recommended** - Cascade deletes, cleanup |

**Example:**
```json
{
  "DELETE": "delete_edge"
}
```

---

## Advanced Features

### Ordering Results

Sort results by a specific field:

```json
{
  "order_by": {
    "key": "created_at",
    "direction": "desc"
  }
}
```

**Multiple sort keys:**
```json
{
  "order_by": [
    {"key": "priority", "direction": "asc"},
    {"key": "created_at", "direction": "desc"}
  ]
}
```

### Read-Only Fields

```json
{
  "created_at": {
    "type": "datetime-local",
    "label": "Created At",
    "readonly": true
  }
}
```

### Hidden Fields

```json
{
  "id": {
    "type": "int",
    "visible": false
  }
}
```

### Default Values

```json
{
  "is_active": {
    "type": "checkbox",
    "default": true
  },
  "status": {
    "type": "select",
    "default": "pending"
  }
}
```

### Conditional Fields

Fields that depend on other field values:

```json
{
  "has_discount": {
    "type": "checkbox",
    "label": "Has Discount"
  },
  "discount_percentage": {
    "type": "number",
    "label": "Discount %",
    "min": 0,
    "max": 100,
    "show_if": {
      "has_discount": true
    }
  }
}
```

### Field Grouping

Group related fields together:

```json
{
  "schema": {
    "basic_info": {
      "type": "group",
      "label": "Basic Information",
      "fields": {
        "name": {
          "type": "text",
          "label": "Name"
        },
        "email": {
          "type": "email",
          "label": "Email"
        }
      }
    }
  }
}
```

### Debug Mode

Enable detailed debugging for specific operations:

```json
{
  "debug": ["GET", "POST", "UPDATE", "DELETE"]
}
```

**Debug output includes:**
- Request data
- Processed data
- Supabase request details
- Supabase response
- Errors and stack traces

---

## Real-World Examples

### Example 1: Simple Product

```json
{
  "name": {
    "singular": "product",
    "plural": "products"
  },
  "schema": {
    "id": {
      "type": "int",
      "readonly": true,
      "visible": false
    },
    "image_url": {
      "key": "image_id",
      "type": "image",
      "label": "Product Image"
    },
    "name": {
      "type": "text",
      "label": "Product Name",
      "required": true,
      "maxlength": 255
    },
    "description": {
      "type": "textarea",
      "label": "Description",
      "rows": 5
    },
    "price": {
      "type": "number",
      "label": "Price ($)",
      "min": 0,
      "step": 0.01,
      "required": true
    },
    "category_name": {
      "type": "select",
      "label": "Category",
      "key": "category_id",
      "data": {
        "type": "class",
        "source": [
          "App\\Services\\Supabase\\SupabaseService",
          "read_rpc",
          "categories"
        ],
        "value": "id",
        "name": "name"
      },
      "required": true
    },
    "is_active": {
      "type": "checkbox",
      "label": "Active",
      "default": true
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "name",
    "direction": "asc"
  }
}
```

### Example 2: Event with Date/Time

```json
{
  "name": {
    "singular": "event",
    "plural": "events"
  },
  "schema": {
    "id": {
      "type": "int",
      "readonly": true,
      "visible": false
    },
    "image_url": {
      "key": "image_id",
      "type": "image",
      "label": "Event Poster"
    },
    "title": {
      "type": "text",
      "label": "Event Title",
      "required": true
    },
    "description": {
      "type": "textarea",
      "label": "Description",
      "rows": 8
    },
    "venue_name": {
      "type": "select",
      "label": "Venue",
      "key": "venue_id",
      "data": {
        "type": "class",
        "source": [
          "App\\Services\\Supabase\\SupabaseService",
          "read_edge",
          "venues"
        ],
        "value": "id",
        "name": "name"
      },
      "required": true
    },
    "start_datetime": {
      "type": "datetime-local",
      "label": "Start Date & Time",
      "required": true
    },
    "end_datetime": {
      "type": "datetime-local",
      "label": "End Date & Time",
      "required": true
    },
    "ticket_price": {
      "type": "number",
      "label": "Ticket Price",
      "min": 0,
      "step": 0.01
    },
    "max_attendees": {
      "type": "number",
      "label": "Maximum Attendees",
      "min": 1
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "start_datetime",
    "direction": "desc"
  }
}
```

### Example 3: Venue with Gallery and Schedule

```json
{
  "name": {
    "singular": "venue",
    "plural": "venues"
  },
  "schema": {
    "id": {
      "type": "int",
      "readonly": true,
      "visible": false
    },
    "image_url": {
      "key": "image_id",
      "type": "image",
      "label": "Featured Image"
    },
    "gallery_urls": {
      "key": "gallery_ids",
      "type": "gallery",
      "label": "Gallery Images"
    },
    "name": {
      "type": "text",
      "label": "Venue Name",
      "required": true
    },
    "description": {
      "type": "textarea",
      "label": "Description",
      "rows": 6
    },
    "website": {
      "type": "url",
      "label": "Website",
      "placeholder": "https://example.com"
    },
    "email": {
      "type": "email",
      "label": "Contact Email"
    },
    "phone": {
      "type": "tel",
      "label": "Phone Number"
    },
    "location": {
      "type": "location",
      "label": "Location",
      "lat_key": "latitude",
      "lng_key": "longitude",
      "address_key": "address"
    },
    "business_hours": {
      "type": "schedule",
      "label": "Business Hours",
      "key": "schedule"
    },
    "capacity": {
      "type": "number",
      "label": "Capacity",
      "min": 1
    },
    "category_name": {
      "type": "select",
      "label": "Category",
      "key": "category_id",
      "data": {
        "type": "class",
        "source": [
          "App\\Services\\Supabase\\SupabaseService",
          "read_rpc",
          "categories"
        ],
        "value": "id",
        "name": "name"
      }
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "name",
    "direction": "asc"
  }
}
```

### Example 4: User Profile

```json
{
  "name": {
    "singular": "user_profile",
    "plural": "user_profiles"
  },
  "schema": {
    "id": {
      "type": "int",
      "readonly": true,
      "visible": false
    },
    "avatar_url": {
      "key": "avatar_id",
      "type": "image",
      "label": "Profile Photo"
    },
    "first_name": {
      "type": "text",
      "label": "First Name",
      "required": true
    },
    "last_name": {
      "type": "text",
      "label": "Last Name",
      "required": true
    },
    "email": {
      "type": "email",
      "label": "Email",
      "required": true,
      "readonly": true
    },
    "phone": {
      "type": "tel",
      "label": "Phone Number"
    },
    "birth_date": {
      "type": "date",
      "label": "Date of Birth"
    },
    "bio": {
      "type": "textarea",
      "label": "Biography",
      "rows": 4,
      "maxlength": 500
    },
    "website": {
      "type": "url",
      "label": "Website"
    },
    "role_name": {
      "type": "select",
      "label": "Role",
      "key": "role_id",
      "data": {
        "type": "class",
        "source": [
          "App\\Services\\Supabase\\SupabaseService",
          "read",
          "roles"
        ],
        "value": "id",
        "name": "name"
      },
      "required": true
    },
    "is_active": {
      "type": "checkbox",
      "label": "Active",
      "default": true
    },
    "created_at": {
      "type": "datetime-local",
      "label": "Created At",
      "readonly": true
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "last_name",
    "direction": "asc"
  }
}
```

---

## Validation Rules

### Client-Side Validation (HTML5)

Applied automatically based on field configuration:

```json
{
  "email": {
    "type": "email",           // Email format validation
    "required": true           // Required field
  },
  "age": {
    "type": "number",
    "min": 18,                 // Minimum value
    "max": 100                 // Maximum value
  },
  "name": {
    "type": "text",
    "minlength": 3,            // Minimum length
    "maxlength": 255           // Maximum length
  }
}
```

### Server-Side Validation (Laravel)

Add custom validation in controller if needed:

```php
// app/Http/Controllers/GeneralController.php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|min:3|max:255',
        'email' => 'required|email|unique:users,email',
        'price' => 'required|numeric|min:0',
        'category_id' => 'required|exists:categories,id'
    ]);

    // Continue with store logic...
}
```

### Validation Rules Reference

| Rule | JSON Config | Laravel Validation |
|------|-------------|-------------------|
| Required | `"required": true` | `'required'` |
| Email | `"type": "email"` | `'email'` |
| URL | `"type": "url"` | `'url'` |
| Number | `"type": "number"` | `'numeric'` |
| Min value | `"min": 0` | `'min:0'` |
| Max value | `"max": 100` | `'max:100'` |
| Min length | `"minlength": 3` | `'min:3'` |
| Max length | `"maxlength": 255` | `'max:255'` |

---

## Best Practices

### 1. Use Descriptive Names

✅ **Good:**
```json
{
  "name": {
    "singular": "blog_post",
    "plural": "blog_posts"
  }
}
```

❌ **Bad:**
```json
{
  "name": {
    "singular": "bp",
    "plural": "bps"
  }
}
```

### 2. Always Set Field Labels

✅ **Good:**
```json
{
  "first_name": {
    "type": "text",
    "label": "First Name"
  }
}
```

❌ **Bad:**
```json
{
  "first_name": {
    "type": "text"
  }
}
```

### 3. Use Appropriate Field Types

✅ **Good:**
```json
{
  "email": {"type": "email"},
  "website": {"type": "url"},
  "price": {"type": "number", "step": 0.01}
}
```

❌ **Bad:**
```json
{
  "email": {"type": "text"},
  "website": {"type": "text"},
  "price": {"type": "text"}
}
```

### 4. Prefer Edge Functions

✅ **Good:**
```json
{
  "GET": "read_edge",
  "INSERT": "create_edge"
}
```

❌ **Avoid:**
```json
{
  "GET": "read",
  "INSERT": "create"
}
```

### 5. Set Validation Rules

✅ **Good:**
```json
{
  "name": {
    "type": "text",
    "required": true,
    "minlength": 3,
    "maxlength": 255
  }
}
```

### 6. Use Key Mapping for Foreign Keys

✅ **Good:**
```json
{
  "category_name": {
    "type": "select",
    "key": "category_id",
    "label": "Category"
  }
}
```

This maps the display field (`category_name`) to the actual database column (`category_id`).

### 7. Hide System Fields

```json
{
  "id": {
    "type": "int",
    "visible": false
  },
  "created_at": {
    "type": "datetime-local",
    "readonly": true
  }
}
```

### 8. Remove Debug Before Production

❌ **Never in production:**
```json
{
  "debug": ["GET", "POST", "UPDATE", "DELETE"]
}
```

---

## Field Type Quick Reference

| Type | Input | Use Case |
|------|-------|----------|
| `text` | Single-line text | Name, title, short text |
| `textarea` | Multi-line text | Description, content, long text |
| `email` | Email | Email addresses |
| `tel` | Phone | Phone numbers |
| `url` | URL | Websites, links |
| `number` | Number | Prices, quantities, ages |
| `date` | Date picker | Birth dates, deadlines |
| `time` | Time picker | Opening times, durations |
| `datetime-local` | Date & time | Event dates, appointments |
| `checkbox` | Checkbox | Boolean values, flags |
| `select` | Dropdown | Categories, statuses, relationships |
| `image` | File upload (single) | Avatars, featured images |
| `gallery` | File upload (multiple) | Product galleries, portfolios |
| `schedule` | Weekly schedule | Business hours, availability |
| `location` | Map picker | Addresses, venues, locations |
| `int` | Integer | IDs, counts |
| `hidden` | Hidden | System fields, metadata |

---

## Troubleshooting

### Issue: Field Not Showing

**Check:**
1. `"visible": false` is not set
2. Field is in `schema` object
3. JSON syntax is valid

### Issue: Select Shows No Options

**Check:**
1. `data` configuration is correct
2. Supabase table has data
3. `source` array has correct class/method/table

### Issue: Image Upload Fails

**Check:**
1. Supabase Storage bucket exists
2. Bucket name in `"bucket"` matches
3. Bucket permissions are set correctly

### Issue: Routes Not Working

**Check:**
1. File name matches `"plural"` name
2. File is in `storage/app/json/`
3. JSON is valid
4. Clear route cache: `php artisan route:clear`

---

**Last Updated:** 2025-11-10
**Version:** 1.0.0
