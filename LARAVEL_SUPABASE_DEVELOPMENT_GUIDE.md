# Laravel + Supabase Development Guide

## 📋 Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Getting Started](#getting-started)
3. [Adding New Features](#adding-new-features)
4. [Authentication System](#authentication-system)
5. [Permission System](#permission-system)
6. [Storage JSON Files](#storage-json-files)
7. [API Patterns](#api-patterns)
8. [Frontend Patterns](#frontend-patterns)
9. [Best Practices](#best-practices)
10. [Debugging](#debugging)

---

## Architecture Overview

### Core Principles

This Laravel application follows a **configuration-driven, model-less architecture** where:

1. **JSON Configuration Files** define entity schemas and CRUD operations
2. **SupabaseService** (Singleton) handles all Supabase API interactions
3. **GeneralController** processes all CRUD operations dynamically
4. **Middleware Stack** handles authentication, permissions, and JSON loading
5. **No Eloquent Models** - Direct API communication with Supabase

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                         Request Flow                         │
└─────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                     Route Middleware                         │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐   │
│  │  Supabase    │→ │  Supabase    │→ │   JsonProps     │   │
│  │  Middleware  │  │  Permissions │  │   Middleware    │   │
│  └──────────────┘  └──────────────┘  └─────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    GeneralController                         │
│  • Reads JSON props from middleware                          │
│  • Calls appropriate SupabaseService method                  │
│  • Returns view with data                                    │
└─────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                    SupabaseService                           │
│  • Singleton instance                                        │
│  • HTTP client to Supabase API                               │
│  • Methods: read, create, update, delete                     │
│  • Methods: read_edge, create_edge, update_edge, delete_edge│
│  • Storage operations: upload, delete, list                  │
└─────────────────────────────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                       Supabase API                           │
│  • REST API (rest/v1/)                                       │
│  • RPC Functions (rest/v1/rpc/)                              │
│  • Edge Functions (functions/v1/)                            │
│  • Storage (storage/v1/)                                     │
└─────────────────────────────────────────────────────────────┘
```

---

## Getting Started

### Prerequisites

- PHP 8.1+
- Composer
- Node.js & NPM
- Supabase Project

### Environment Setup

1. **Copy environment file:**
```bash
cp .env.example .env
```

2. **Configure Supabase credentials:**
```env
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_KEY=your-supabase-anon-key
SUPABASE_ANON_KEY=your-supabase-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

3. **Install dependencies:**
```bash
composer install
npm install
npm run build
```

4. **Generate application key:**
```bash
php artisan key:generate
```

5. **Start development server:**
```bash
php artisan serve
```

### File Structure

```
ro.cavaleriitemplului.app_laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── GeneralController.php          # Main CRUD controller
│   │   │   └── SupabaseLoginController.php    # Authentication
│   │   └── Middleware/
│   │       ├── SupabaseMiddleware.php          # Auth check & token refresh
│   │       ├── SupabasePermissions.php         # Permission checking
│   │       └── JsonPropsMiddleware.php         # JSON config loader
│   ├── Providers/
│   │   └── SupabaseServiceProvider.php         # Service registration
│   └── Services/
│       └── Supabase/
│           └── SupabaseService.php             # Core Supabase service
├── config/
│   └── supabase.php                            # Supabase configuration
├── storage/
│   └── app/
│       └── json/                               # Entity JSON configurations (54 files)
│           ├── categories.json
│           ├── products.json
│           ├── events.json
│           └── ...
├── resources/
│   └── views/
│       ├── data/                               # Generic CRUD views
│       │   ├── index.blade.php
│       │   ├── create.blade.php
│       │   └── edit.blade.php
│       └── components/                         # Reusable components
│           ├── input.blade.php
│           ├── select.blade.php
│           ├── gallery.blade.php
│           └── schedule.blade.php
└── routes/
    ├── web.php                                 # Dynamic route generation
    └── auth.php                                # Authentication routes
```

---

## Adding New Features

### Step-by-Step: Creating a New Entity

#### Step 1: Create Supabase Table

Create your table in Supabase with appropriate columns and RLS policies.

**Example: `blog_posts` table**

```sql
CREATE TABLE blog_posts (
  id BIGSERIAL PRIMARY KEY,
  title TEXT NOT NULL,
  content TEXT,
  author_id UUID REFERENCES auth.users(id),
  category_id BIGINT REFERENCES categories(id),
  image_id UUID,
  published_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Enable RLS
ALTER TABLE blog_posts ENABLE ROW LEVEL SECURITY;

-- Create policies
CREATE POLICY "Enable read for authenticated users" ON blog_posts
  FOR SELECT USING (auth.role() = 'authenticated');

CREATE POLICY "Enable insert for authenticated users" ON blog_posts
  FOR INSERT WITH CHECK (auth.role() = 'authenticated');
```

#### Step 2: Create JSON Configuration File

Create a new JSON file in `storage/app/json/` directory.

**File: `storage/app/json/blog_posts.json`**

```json
{
  "name": {
    "singular": "blog_post",
    "plural": "blog_posts"
  },
  "schema": {
    "id": {
      "label": "#",
      "type": "int",
      "readonly": true,
      "visible": false
    },
    "image_url": {
      "key": "image_id",
      "type": "image",
      "label": "Featured Image",
      "required": false
    },
    "title": {
      "type": "text",
      "label": "Title",
      "placeholder": "Enter blog post title",
      "required": true
    },
    "content": {
      "type": "textarea",
      "label": "Content",
      "placeholder": "Write your content here...",
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
    "published_at": {
      "type": "datetime-local",
      "label": "Publish Date",
      "required": false
    }
  },
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge",
  "order_by": {
    "key": "created_at",
    "direction": "desc"
  }
}
```

#### Step 3: Create Supabase Edge Function (Recommended)

**File: `supabase/functions/blog_posts/index.ts`** (in your Supabase project)

```typescript
import { serve } from "https://deno.land/std@0.168.0/http/server.ts"
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  const supabaseClient = createClient(
    Deno.env.get('SUPABASE_URL') ?? '',
    Deno.env.get('SUPABASE_ANON_KEY') ?? '',
    { global: { headers: { Authorization: req.headers.get('Authorization')! } } }
  )

  const { method } = req
  const url = new URL(req.url)
  const id = url.searchParams.get('id')

  try {
    switch (method) {
      case 'GET': {
        const { data, error } = await supabaseClient
          .from('blog_posts')
          .select('*, category:categories(name)')
          .order('created_at', { ascending: false })

        if (error) throw error
        return new Response(JSON.stringify({ success: true, data }), {
          headers: { 'Content-Type': 'application/json' }
        })
      }

      case 'POST': {
        const body = await req.json()
        const { data, error } = await supabaseClient
          .from('blog_posts')
          .insert([body])
          .select()

        if (error) throw error
        return new Response(JSON.stringify({ success: true, data }), {
          headers: { 'Content-Type': 'application/json' }
        })
      }

      case 'PUT': {
        if (!id) throw new Error('ID required for update')
        const body = await req.json()
        const { data, error } = await supabaseClient
          .from('blog_posts')
          .update(body)
          .eq('id', id)
          .select()

        if (error) throw error
        return new Response(JSON.stringify({ success: true, data }), {
          headers: { 'Content-Type': 'application/json' }
        })
      }

      case 'DELETE': {
        if (!id) throw new Error('ID required for delete')
        const { error } = await supabaseClient
          .from('blog_posts')
          .delete()
          .eq('id', id)

        if (error) throw error
        return new Response(JSON.stringify({ success: true }), {
          headers: { 'Content-Type': 'application/json' }
        })
      }

      default:
        throw new Error(`Method ${method} not allowed`)
    }
  } catch (error) {
    return new Response(JSON.stringify({ success: false, error: error.message }), {
      status: 400,
      headers: { 'Content-Type': 'application/json' }
    })
  }
})
```

#### Step 4: Configure Permissions in Supabase

Set up permissions in your `permissions` table:

```sql
-- Grant read permission for blog_posts
INSERT INTO permissions (role_id, resource_name, permission_code)
VALUES
  (1, 'blog_posts', 'r'),  -- Read
  (1, 'blog_posts', 'i'),  -- Insert
  (1, 'blog_posts', 'u'),  -- Update
  (1, 'blog_posts', 'd');  -- Delete
```

#### Step 5: Routes are Auto-Generated!

The routes are automatically generated from JSON files in `routes/web.php`:

```php
// Automatically creates these routes:
// GET    /blog_posts           -> index
// GET    /blog_posts/create    -> create
// POST   /blog_posts           -> store
// GET    /blog_posts/{id}      -> show
// GET    /blog_posts/{id}/edit -> edit
// PUT    /blog_posts/{id}      -> update
// DELETE /blog_posts/{id}      -> destroy
```

#### Step 6: Test Your New Feature

1. Navigate to `/blog_posts` in your browser
2. Click "Create New"
3. Fill in the form
4. Save and verify the data appears in Supabase

**That's it!** No controller code, no model, no migrations needed in Laravel.

---

## Authentication System

### How Authentication Works

#### Login Flow

1. **User submits credentials** via `/login` route
2. **SupabaseLoginController** calls `SupabaseService::signIn()`
3. **Supabase returns JWT tokens** and user metadata
4. **Tokens stored in Laravel session:**
   - `jwt_token` - Access token (expires in 1 hour)
   - `refresh_token` - Refresh token (expires in 30 days)
   - `user` - User metadata (email, name, roles)
5. **Redirect to dashboard**

#### Session Structure

```php
Session::get('jwt_token');      // "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
Session::get('refresh_token');  // "v1::refresh_token_here..."
Session::get('user');           // [
                                //   'email' => 'user@example.com',
                                //   'name' => 'John Doe',
                                //   'claims_admin' => 'admin',
                                //   'userrole' => 'administrator'
                                // ]
Session::get('permissions');    // Cached user permissions
```

#### Token Refresh (Automatic)

The `SupabaseMiddleware` automatically refreshes tokens:

```php
// app/Http/Middleware/SupabaseMiddleware.php
public function handle(Request $request, Closure $next): Response
{
    if (!Session::has('jwt_token')) {
        return redirect('/login');
    }

    // Refresh token automatically
    $token = Session::get('refresh_token');
    $response = $this->supabase->refresh_token($token);

    // Update session with new token
    Session::put('jwt_token', $response['access_token']);
    Session::put('refresh_token', $response['refresh_token']);

    return $next($request);
}
```

### Implementing Authentication in Custom Controllers

**Example: Custom controller with auth**

```php
namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CustomController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;

        // Apply auth middleware
        $this->middleware('supabase');
    }

    public function index()
    {
        // Get authenticated user
        $user = Session::get('user');

        // Make authenticated request
        $data = $this->supabase->read_edge('custom_entity');

        return view('custom.index', compact('data', 'user'));
    }
}
```

### Checking User Roles

```php
// In controller
public function adminOnly()
{
    $user = Session::get('user');

    if ($user['userrole'] !== 'administrator') {
        abort(403, 'Unauthorized');
    }

    // Admin-only logic here
}
```

```blade
{{-- In Blade view --}}
@if(session('user')['userrole'] === 'administrator')
    <button>Admin Action</button>
@endif
```

---

## Permission System

### How Permissions Work

This application implements a **fine-grained CRUD permission system**:

- **r** = Read
- **i** = Insert (Create)
- **u** = Update
- **d** = Delete

### Permission Checking Flow

1. **Route accessed** with permission middleware
2. **SupabasePermissions middleware** checks route action
3. **Maps action to permission code:**
   - `index`, `show` → `r`
   - `create`, `store` → `i`
   - `edit`, `update` → `u`
   - `destroy` → `d`
4. **Checks if user has permission** for the resource
5. **Allow or deny access**

### Permission Storage in Supabase

**Table: `permissions`**

```sql
CREATE TABLE permissions (
  id BIGSERIAL PRIMARY KEY,
  role_id BIGINT REFERENCES roles(id),
  resource_name TEXT NOT NULL,
  permission_code TEXT NOT NULL, -- 'r', 'i', 'u', 'd'
  created_at TIMESTAMP DEFAULT NOW()
);
```

**Example data:**

```sql
-- Administrators have full access to products
INSERT INTO permissions (role_id, resource_name, permission_code) VALUES
(1, 'products', 'r'),
(1, 'products', 'i'),
(1, 'products', 'u'),
(1, 'products', 'd');

-- Editors can only read and update products
INSERT INTO permissions (role_id, resource_name, permission_code) VALUES
(2, 'products', 'r'),
(2, 'products', 'u');
```

### Checking Permissions in Controllers

**Method 1: Using Middleware**

```php
Route::resource('products', GeneralController::class)
    ->middleware(['json.props', 'supabase.permissions']);
```

**Method 2: Manual Check**

```php
// In controller
public function store(Request $request)
{
    // Check if user has insert permission
    $hasPermission = $this->supabase->check_user_permission('products', 'i');

    if (!$hasPermission) {
        abort(403, 'You do not have permission to create products');
    }

    // Continue with store logic
}
```

**Method 3: Using Helper Method**

```php
// Check specific permission
if ($this->supabase->user_have_permission('products', 'u')) {
    // User can update products
}
```

### Hiding UI Elements Based on Permissions

```blade
{{-- In Blade view --}}
@php
    $permissions = session('permissions')['products'] ?? [];
@endphp

@if(in_array('i', $permissions))
    <a href="{{ route('products.create') }}" class="btn btn-primary">
        Create New Product
    </a>
@endif

@if(in_array('u', $permissions))
    <a href="{{ route('products.edit', $product->id) }}" class="btn btn-warning">
        Edit
    </a>
@endif

@if(in_array('d', $permissions))
    <form method="POST" action="{{ route('products.destroy', $product->id) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
@endif
```

---

## Storage JSON Files

See separate file: [STORAGE_JSON_SCHEMA.md](./STORAGE_JSON_SCHEMA.md)

---

## API Patterns

See separate file: [SUPABASE_API_PATTERNS.md](./SUPABASE_API_PATTERNS.md)

---

## Frontend Patterns

### View Structure

All CRUD operations use generic views with dynamic rendering:

```
resources/views/data/
├── index.blade.php     # List view with DataTables
├── create.blade.php    # Create form
└── edit.blade.php      # Edit form
```

### Dynamic Form Rendering

Forms are rendered dynamically based on JSON schema:

```blade
{{-- create.blade.php --}}
@foreach($props['schema'] as $key => $field)
    @if($field['type'] === 'text')
        <x-input
            :name="$key"
            :label="$field['label']"
            :placeholder="$field['placeholder'] ?? ''"
            :required="$field['required'] ?? false"
        />
    @elseif($field['type'] === 'select')
        <x-select
            :name="$key"
            :label="$field['label']"
            :options="$data[$key] ?? []"
            :required="$field['required'] ?? false"
        />
    @elseif($field['type'] === 'textarea')
        <x-textarea
            :name="$key"
            :label="$field['label']"
            :rows="$field['rows'] ?? 5"
        />
    @elseif($field['type'] === 'image')
        <x-image-upload
            :name="$key"
            :label="$field['label']"
        />
    @elseif($field['type'] === 'gallery')
        <x-gallery
            :name="$key"
            :label="$field['label']"
        />
    @elseif($field['type'] === 'datetime-local')
        <x-datetime
            :name="$key"
            :label="$field['label']"
        />
    @elseif($field['type'] === 'checkbox')
        <x-checkbox
            :name="$key"
            :label="$field['label']"
        />
    @endif
@endforeach
```

### Blade Components

#### Input Component

```blade
{{-- resources/views/components/input.blade.php --}}
<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <input
        type="{{ $type ?? 'text' }}"
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-control"
        placeholder="{{ $placeholder ?? '' }}"
        value="{{ old($name, $value ?? '') }}"
        {{ $required ? 'required' : '' }}
    >
</div>
```

#### Select Component

```blade
{{-- resources/views/components/select.blade.php --}}
<div class="form-group">
    <label for="{{ $name }}">{{ $label }}</label>
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        class="form-control"
        {{ $required ? 'required' : '' }}
    >
        <option value="">Select {{ $label }}</option>
        @foreach($options as $option)
            <option value="{{ $option['value'] }}"
                {{ old($name, $value ?? '') == $option['value'] ? 'selected' : '' }}>
                {{ $option['name'] }}
            </option>
        @endforeach
    </select>
</div>
```

### DataTables Integration

```blade
{{-- resources/views/data/index.blade.php --}}
<table class="table table-striped table_striped">
    <thead>
        <tr>
            @foreach($props['schema'] as $key => $field)
                @if(!isset($field['visible']) || $field['visible'] !== false)
                    <th>{{ $field['label'] }}</th>
                @endif
            @endforeach
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
            <tr>
                @foreach($props['schema'] as $key => $field)
                    @if(!isset($field['visible']) || $field['visible'] !== false)
                        <td>{{ $row[$key] ?? '-' }}</td>
                    @endif
                @endforeach
                <td>
                    <a href="{{ route($props['name']['plural'] . '.edit', $row['id']) }}">Edit</a>
                    <form method="POST" action="{{ route($props['name']['plural'] . '.destroy', $row['id']) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

<script>
$(document).ready(function() {
    $('.table_striped').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries"
        }
    });
});
</script>
```

### AJAX Patterns

#### Dynamic Subcategory Loading

```javascript
// resources/js/subcategory-loader.js
document.getElementById('parent_category').addEventListener('change', function() {
    const parentId = this.value;
    const table = this.dataset.table;

    fetch(`/api/subcategories/${table}?parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
            const subcategorySelect = document.getElementById('subcategory');
            subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';

            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                subcategorySelect.appendChild(option);
            });
        });
});
```

---

## Best Practices

### 1. Always Use Edge Functions

**✅ Preferred:**
```json
{
  "GET": "read_edge",
  "INSERT": "create_edge",
  "UPDATE": "update_edge",
  "DELETE": "delete_edge"
}
```

**❌ Avoid direct REST API:**
```json
{
  "GET": "read",
  "INSERT": "create"
}
```

**Why?** Edge functions provide:
- Better security (server-side logic)
- Custom business logic
- Data transformation
- Better error handling

### 2. Define Clear Field Types

Be specific with field types in JSON schema:

```json
{
  "email": {
    "type": "email",  // Not just "text"
    "label": "Email Address",
    "required": true
  },
  "phone": {
    "type": "tel",
    "label": "Phone Number"
  },
  "website": {
    "type": "url",
    "label": "Website"
  },
  "quantity": {
    "type": "number",
    "label": "Quantity",
    "min": 0
  }
}
```

### 3. Use Proper Data Source Configuration

**For static options:**
```json
{
  "status": {
    "type": "select",
    "label": "Status",
    "data": [
      {"value": "active", "name": "Active"},
      {"value": "inactive", "name": "Inactive"}
    ]
  }
}
```

**For dynamic options from Supabase:**
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
    }
  }
}
```

### 4. Handle Image Uploads Properly

**For single image:**
```json
{
  "image_url": {
    "key": "image_id",
    "type": "image",
    "label": "Featured Image",
    "bucket": "images"
  }
}
```

**For multiple images (gallery):**
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

### 5. Implement Proper Ordering

```json
{
  "order_by": {
    "key": "created_at",
    "direction": "desc"
  }
}
```

### 6. Use Readonly and Visible Flags

```json
{
  "id": {
    "type": "int",
    "readonly": true,
    "visible": false
  },
  "created_at": {
    "type": "datetime-local",
    "readonly": true,
    "visible": true,
    "label": "Created At"
  }
}
```

### 7. Enable Debug Mode During Development

```json
{
  "debug": ["GET", "POST", "UPDATE", "DELETE"]
}
```

Remove before production!

### 8. Implement Proper Validation

**In JSON schema:**
```json
{
  "title": {
    "type": "text",
    "label": "Title",
    "required": true,
    "minlength": 3,
    "maxlength": 255
  }
}
```

**In controller (if needed):**
```php
$validated = $request->validate([
    'title' => 'required|string|min:3|max:255',
    'email' => 'required|email',
    'price' => 'required|numeric|min:0'
]);
```

### 9. Use Meaningful Naming

**✅ Good:**
```json
{
  "name": {
    "singular": "blog_post",
    "plural": "blog_posts"
  }
}
```

**❌ Bad:**
```json
{
  "name": {
    "singular": "bp",
    "plural": "bps"
  }
}
```

### 10. Implement Proper Error Handling

**In Edge Functions:**
```typescript
try {
  const { data, error } = await supabaseClient
    .from('products')
    .select('*')

  if (error) throw error

  return new Response(JSON.stringify({ success: true, data }), {
    headers: { 'Content-Type': 'application/json' }
  })
} catch (error) {
  return new Response(JSON.stringify({
    success: false,
    error: { message: error.message, code: 'QUERY_ERROR' }
  }), {
    status: 400,
    headers: { 'Content-Type': 'application/json' }
  })
}
```

**In Laravel Controller:**
```php
try {
    $data = $this->supabase->read_edge('products');
    return view('data.index', compact('data'));
} catch (\Exception $e) {
    return redirect()->back()->with('error', 'Failed to load products: ' . $e->getMessage());
}
```

---

## Debugging

### Enable Debug Mode

Add to your JSON configuration:

```json
{
  "debug": ["GET", "POST", "UPDATE", "DELETE"]
}
```

### Debug Output

When debug mode is enabled, you'll see:

```php
// 1. Raw request data
dd('Request data:', $request->all());

// 2. Processed data before sending to Supabase
dd('Processed data:', $data);

// 3. Supabase response
dd('Supabase response:', $response);
```

### Common Issues and Solutions

#### Issue 1: "Unauthorized" Error

**Cause:** JWT token expired or invalid

**Solution:**
```php
// Clear session and re-login
Session::flush();
return redirect('/login');
```

#### Issue 2: "Permission Denied"

**Cause:** User doesn't have required permission

**Solution:**
1. Check permissions in Supabase `permissions` table
2. Verify user's role_id
3. Ensure RLS policies are correct

```sql
-- Check user's permissions
SELECT p.*
FROM permissions p
JOIN user_roles ur ON p.role_id = ur.role_id
WHERE ur.user_id = 'user-uuid';
```

#### Issue 3: Image Upload Fails

**Cause:** Bucket doesn't exist or incorrect permissions

**Solution:**
1. Create bucket in Supabase Storage
2. Set bucket to public (if needed)
3. Update JSON configuration:

```json
{
  "image_url": {
    "type": "image",
    "bucket": "correct-bucket-name"
  }
}
```

#### Issue 4: Select Field Shows No Options

**Cause:** Data source configuration incorrect

**Solution:**
```json
{
  "category_name": {
    "type": "select",
    "key": "category_id",
    "data": {
      "type": "class",
      "source": [
        "App\\Services\\Supabase\\SupabaseService",
        "read_rpc",  // or "read_edge"
        "categories"
      ],
      "value": "id",
      "name": "name"
    }
  }
}
```

#### Issue 5: Routes Not Working

**Cause:** JSON file not detected or route cache

**Solution:**
```bash
# Clear route cache
php artisan route:clear

# Verify JSON file exists
ls -la storage/app/json/your_entity.json

# Check file permissions
chmod 644 storage/app/json/*.json
```

### Logging

Add custom logging in controllers:

```php
use Illuminate\Support\Facades\Log;

Log::info('Creating new product', ['data' => $data]);
Log::error('Failed to create product', ['error' => $e->getMessage()]);
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Quick Reference

### SupabaseService Methods

| Method | Purpose | Example |
|--------|---------|---------|
| `signIn($email, $password)` | User login | `$supabase->signIn('user@example.com', 'password')` |
| `logout()` | User logout | `$supabase->logout()` |
| `refresh_token($token)` | Refresh JWT | `$supabase->refresh_token($refreshToken)` |
| `read($plural)` | Read via REST API | `$supabase->read('products')` |
| `read_rpc($plural)` | Read via RPC | `$supabase->read_rpc('products')` |
| `read_edge($plural)` | Read via Edge Function | `$supabase->read_edge('products')` |
| `create($data, $plural)` | Create via REST API | `$supabase->create($data, 'products')` |
| `create_edge($data, $plural)` | Create via Edge Function | `$supabase->create_edge($data, 'products')` |
| `update($id, $data, $plural)` | Update via REST API | `$supabase->update(1, $data, 'products')` |
| `update_edge($id, $data, $plural)` | Update via Edge Function | `$supabase->update_edge(1, $data, 'products')` |
| `delete($id, $plural)` | Delete via REST API | `$supabase->delete(1, 'products')` |
| `delete_edge($id, $plural)` | Delete via Edge Function | `$supabase->delete_edge(1, 'products')` |
| `uploadToStorage(...)` | Upload file | `$supabase->uploadToStorage('images', 'path/file.jpg', $content, 'image/jpeg')` |
| `check_user_permission(...)` | Check permission | `$supabase->check_user_permission('products', 'i')` |

### Field Types

| Type | Description | Example |
|------|-------------|---------|
| `text` | Single-line text | Name, title |
| `textarea` | Multi-line text | Description, content |
| `email` | Email input | user@example.com |
| `tel` | Phone number | +1234567890 |
| `url` | Website URL | https://example.com |
| `number` | Numeric input | Price, quantity |
| `datetime-local` | Date and time | Event date |
| `date` | Date only | Birth date |
| `time` | Time only | Opening time |
| `checkbox` | Boolean checkbox | Is active |
| `select` | Dropdown | Category selection |
| `image` | Single image upload | Featured image |
| `gallery` | Multiple images | Product gallery |
| `schedule` | Business hours | Opening hours |
| `location` | Map picker | Venue location |

### Permission Codes

| Code | Permission | Route Actions |
|------|------------|---------------|
| `r` | Read | `index`, `show` |
| `i` | Insert | `create`, `store` |
| `u` | Update | `edit`, `update` |
| `d` | Delete | `destroy` |

---

## Additional Resources

- [Storage JSON Schema Documentation](./STORAGE_JSON_SCHEMA.md)
- [Supabase API Patterns](./SUPABASE_API_PATTERNS.md)
- [Supabase Official Documentation](https://supabase.com/docs)
- [Laravel Documentation](https://laravel.com/docs)

---

**Last Updated:** 2025-11-10
**Version:** 1.0.0
