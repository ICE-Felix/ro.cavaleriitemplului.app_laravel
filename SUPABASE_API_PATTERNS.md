# Supabase API Patterns & Examples

## Overview

This document provides comprehensive examples and patterns for interacting with Supabase API through the `SupabaseService` class in this Laravel application.

---

## Table of Contents

1. [SupabaseService Overview](#supabaseservice-overview)
2. [Authentication Operations](#authentication-operations)
3. [CRUD Operations - REST API](#crud-operations---rest-api)
4. [CRUD Operations - RPC Functions](#crud-operations---rpc-functions)
5. [CRUD Operations - Edge Functions](#crud-operations---edge-functions)
6. [Storage Operations](#storage-operations)
7. [Permission Operations](#permission-operations)
8. [Edge Function Examples](#edge-function-examples)
9. [Error Handling](#error-handling)
10. [Best Practices](#best-practices)

---

## SupabaseService Overview

### Service Location

**File:** `app/Services/Supabase/SupabaseService.php`

### Architecture

- **Singleton Pattern:** One instance throughout application lifecycle
- **HTTP Client:** GuzzleHttp for API communication
- **Session Integration:** JWT tokens stored in Laravel session
- **Configuration:** Loaded from `config/supabase.php`

### Initialization

```php
use App\Services\Supabase\SupabaseService;

// Dependency injection (recommended)
public function __construct(SupabaseService $supabase)
{
    $this->supabase = $supabase;
}

// Or get instance directly
$supabase = SupabaseService::getInstance();
```

### Configuration

**File:** `config/supabase.php`

```php
return [
    'url' => env('SUPABASE_URL'),
    'key' => env('SUPABASE_KEY', env('SUPABASE_ANON_KEY')),
    'anon_key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
];
```

**Environment variables:**
```env
SUPABASE_URL=https://xxxxx.supabase.co
SUPABASE_KEY=your-anon-key
SUPABASE_ANON_KEY=your-anon-key
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key
```

---

## Authentication Operations

### Sign In

**Method:** `signIn($email, $password)`

**Example:**
```php
$response = $this->supabase->signIn('user@example.com', 'password123');

// Response structure:
[
    'access_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
    'refresh_token' => 'v1::refresh_token_here...',
    'expires_in' => 3600,
    'token_type' => 'bearer',
    'user' => [
        'id' => 'uuid-here',
        'email' => 'user@example.com',
        'app_metadata' => [
            'claims_admin' => 'admin',
            'userrole' => 'administrator'
        ],
        'user_metadata' => [
            'name' => 'John Doe'
        ]
    ]
]
```

**Full Controller Example:**
```php
namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupabaseLoginController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        try {
            $response = $this->supabase->signIn(
                $request->email,
                $request->password
            );

            // Store tokens in session
            Session::put('jwt_token', $response['access_token']);
            Session::put('refresh_token', $response['refresh_token']);
            Session::put('user', [
                'email' => $response['user']['email'],
                'name' => $response['user']['user_metadata']['name'] ?? $response['user']['email'],
                'claims_admin' => $response['user']['app_metadata']['claims_admin'] ?? null,
                'userrole' => $response['user']['app_metadata']['userrole'] ?? null
            ]);

            return redirect('/dashboard');

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Invalid credentials']);
        }
    }
}
```

### Refresh Token

**Method:** `refresh_token($refreshToken)`

**Example:**
```php
$refreshToken = Session::get('refresh_token');
$response = $this->supabase->refresh_token($refreshToken);

// Update session with new tokens
Session::put('jwt_token', $response['access_token']);
Session::put('refresh_token', $response['refresh_token']);
```

**Automatic refresh in middleware:**
```php
// app/Http/Middleware/SupabaseMiddleware.php
public function handle(Request $request, Closure $next): Response
{
    if (!Session::has('jwt_token')) {
        return redirect('/login');
    }

    // Auto-refresh token
    $token = Session::get('refresh_token');
    $response = $this->supabase->refresh_token($token);

    Session::put('jwt_token', $response['access_token']);
    Session::put('refresh_token', $response['refresh_token']);

    return $next($request);
}
```

### Logout

**Method:** `logout()`

**Example:**
```php
public function logout()
{
    try {
        $this->supabase->logout();
    } catch (\Exception $e) {
        // Handle error
    }

    Session::flush();
    return redirect('/login');
}
```

### Get User Roles and Permissions

**Method:** `get_roles_and_permissions()`

**Example:**
```php
$permissions = $this->supabase->get_roles_and_permissions();

// Response structure:
[
    'products' => ['r', 'i', 'u', 'd'],  // Full CRUD
    'categories' => ['r'],                // Read-only
    'venues' => ['r', 'u']                // Read and update
]

// Store in session for quick access
Session::put('permissions', $permissions);
```

---

## CRUD Operations - REST API

### Read (Direct REST API)

**Method:** `read($plural, $debug = false, $queryParams = [])`

**URL Pattern:** `rest/v1/{table}`

**Basic Example:**
```php
$products = $this->supabase->read('products');

// Returns array of objects:
[
    [
        'id' => 1,
        'name' => 'Product 1',
        'price' => 99.99,
        'category_id' => 5
    ],
    // ...
]
```

**With Query Parameters:**
```php
$products = $this->supabase->read('products', false, [
    'select' => 'id,name,price',
    'order' => 'name.asc',
    'limit' => 10
]);
```

**Advanced Filtering:**
```php
// Get products where price > 50
$products = $this->supabase->read('products', false, [
    'select' => 'id,name,price',
    'price' => 'gt.50',
    'order' => 'price.desc'
]);

// Get products by category
$products = $this->supabase->read('products', false, [
    'category_id' => 'eq.5',
    'is_active' => 'eq.true'
]);
```

**PostgREST Operators:**
- `eq.value` - Equal to
- `neq.value` - Not equal to
- `gt.value` - Greater than
- `gte.value` - Greater than or equal
- `lt.value` - Less than
- `lte.value` - Less than or equal
- `like.pattern` - Like (pattern matching)
- `ilike.pattern` - Case-insensitive like
- `in.(val1,val2)` - In list
- `is.null` - Is null

### Create (Direct REST API)

**Method:** `create(array $data, $plural, $debug = false)`

**URL Pattern:** `rest/v1/{table}`

**Example:**
```php
$newProduct = [
    'name' => 'New Product',
    'description' => 'Product description',
    'price' => 149.99,
    'category_id' => 3,
    'is_active' => true
];

$result = $this->supabase->create($newProduct, 'products');

// Returns created record:
[
    'id' => 123,
    'name' => 'New Product',
    'price' => 149.99,
    // ...
]
```

**Controller Example:**
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0'
    ]);

    $result = $this->supabase->create($validated, 'products');

    return redirect()->route('products.index')
        ->with('success', 'Product created successfully');
}
```

### Update (Direct REST API)

**Method:** `update($id, array $data, $plural, $debug = false)`

**URL Pattern:** `rest/v1/{table}?id=eq.{id}`

**Example:**
```php
$updateData = [
    'name' => 'Updated Product Name',
    'price' => 199.99
];

$result = $this->supabase->update(123, $updateData, 'products');
```

**Controller Example:**
```php
public function update(Request $request, $id)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0'
    ]);

    $this->supabase->update($id, $validated, 'products');

    return redirect()->route('products.index')
        ->with('success', 'Product updated successfully');
}
```

### Delete (Direct REST API)

**Method:** `delete($id, $plural, $debug = false)`

**URL Pattern:** `rest/v1/{table}?id=eq.{id}`

**Example:**
```php
$this->supabase->delete(123, 'products');
```

**Controller Example:**
```php
public function destroy($id)
{
    try {
        $this->supabase->delete($id, 'products');

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully');
    } catch (\Exception $e) {
        return back()->with('error', 'Failed to delete product');
    }
}
```

---

## CRUD Operations - RPC Functions

RPC (Remote Procedure Call) functions allow you to execute PostgreSQL functions via the API.

### Read via RPC

**Method:** `read_rpc($plural, $debug = false, $params = [])`

**URL Pattern:** `rest/v1/rpc/get_{table}`

**Example:**
```php
$categories = $this->supabase->read_rpc('categories');
```

**With Parameters:**
```php
$results = $this->supabase->read_rpc('products', false, [
    'category_id' => 5,
    'min_price' => 50
]);
```

**Supabase SQL Function Example:**
```sql
-- Create RPC function in Supabase SQL Editor
CREATE OR REPLACE FUNCTION get_products(category_id INT DEFAULT NULL, min_price DECIMAL DEFAULT 0)
RETURNS TABLE (
    id BIGINT,
    name TEXT,
    price DECIMAL,
    category_name TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        p.id,
        p.name,
        p.price,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE
        (category_id IS NULL OR p.category_id = category_id)
        AND p.price >= min_price
    ORDER BY p.name;
END;
$$ LANGUAGE plpgsql;
```

**Complex RPC Example:**
```php
// Get sales report via RPC
$salesReport = $this->supabase->read_rpc('sales_report', false, [
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31'
]);
```

---

## CRUD Operations - Edge Functions

**Recommended approach** - Provides full control, custom logic, and better security.

### Read via Edge Function

**Method:** `read_edge($plural, $debug = false, $queryParams = [])`

**URL Pattern:** `functions/v1/{table}`

**Example:**
```php
$products = $this->supabase->read_edge('products');

// With query parameters
$products = $this->supabase->read_edge('products', false, [
    'category_id' => 5,
    'sort' => 'price_desc'
]);
```

**Expected Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product 1",
      "price": 99.99
    }
  ]
}
```

### Create via Edge Function

**Method:** `create_edge($data, $plural, $debug = false)`

**URL Pattern:** `functions/v1/{table}` (POST)

**Example:**
```php
$newProduct = [
    'name' => 'New Product',
    'price' => 149.99,
    'category_id' => 3
];

$result = $this->supabase->create_edge($newProduct, 'products');
```

### Update via Edge Function

**Method:** `update_edge($id, array $data, $plural, $debug = false)`

**URL Pattern:** `functions/v1/{table}?id={id}` (PUT)

**Example:**
```php
$updateData = ['name' => 'Updated Name', 'price' => 199.99];
$result = $this->supabase->update_edge(123, $updateData, 'products');
```

### Delete via Edge Function

**Method:** `delete_edge($id, $plural, $debug = false)`

**URL Pattern:** `functions/v1/{table}?id={id}` (DELETE)

**Example:**
```php
$this->supabase->delete_edge(123, 'products');
```

---

## Storage Operations

### Upload File

**Method:** `uploadToStorage($bucket, $path, $fileContent, $contentType)`

**Example:**
```php
// From request file
$file = $request->file('image');
$fileContent = file_get_contents($file->getRealPath());
$contentType = $file->getMimeType();
$fileName = uniqid() . '.' . $file->getClientOriginalExtension();

$result = $this->supabase->uploadToStorage(
    'images',                    // bucket
    $fileName,                   // path
    $fileContent,                // file content
    $contentType                 // content type
);

// Returns storage path
return $result; // "images/abc123.jpg"
```

**Upload with Custom Path:**
```php
$path = 'products/2025/' . $fileName;
$result = $this->supabase->uploadToStorage('images', $path, $fileContent, $contentType);
```

**Base64 Image Upload:**
```php
$base64Image = $request->input('image');

// Remove data:image/jpeg;base64, prefix if present
if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
    $type = strtolower($type[1]);

    $imageContent = base64_decode($base64Image);
    $fileName = uniqid() . '.' . $type;

    $this->supabase->uploadToStorage('images', $fileName, $imageContent, 'image/' . $type);
}
```

### Delete File

**Method:** `deleteFromStorage($bucket, $path)`

**Example:**
```php
$this->supabase->deleteFromStorage('images', 'abc123.jpg');
```

**Delete old image when updating:**
```php
// Get old image ID
$oldImageId = $product['image_id'];

// Upload new image
$newImageId = $this->supabase->uploadToStorage(...);

// Delete old image
if ($oldImageId) {
    $this->supabase->deleteFromStorage('images', $oldImageId);
}

// Update product
$this->supabase->update($id, ['image_id' => $newImageId], 'products');
```

### Get Public URL

**Method:** `getStoragePublicUrl($bucket, $path)`

**Example:**
```php
$url = $this->supabase->getStoragePublicUrl('images', 'abc123.jpg');

// Returns: https://xxx.supabase.co/storage/v1/object/public/images/abc123.jpg
```

**In Blade:**
```blade
@if($product['image_id'])
    <img src="{{ app(\App\Services\Supabase\SupabaseService::class)->getStoragePublicUrl('images', $product['image_id']) }}"
         alt="{{ $product['name'] }}">
@endif
```

### List Files

**Method:** `listStorageFiles($bucket, $path = '', $options = [])`

**Example:**
```php
// List all files in bucket
$files = $this->supabase->listStorageFiles('images');

// List files in specific folder
$files = $this->supabase->listStorageFiles('images', 'products/2025/');

// Response:
[
    [
        'name' => 'abc123.jpg',
        'id' => 'uuid-here',
        'updated_at' => '2025-11-10T12:00:00Z',
        'created_at' => '2025-11-10T12:00:00Z',
        'last_accessed_at' => '2025-11-10T12:00:00Z',
        'metadata' => [
            'size' => 1024000,
            'mimetype' => 'image/jpeg'
        ]
    ],
    // ...
]
```

---

## Permission Operations

### Check User Permission

**Method:** `check_user_permission($table_name, $action_required = 'r')`

**Actions:**
- `r` - Read
- `i` - Insert
- `u` - Update
- `d` - Delete

**Example:**
```php
// Check if user can insert products
if ($this->supabase->check_user_permission('products', 'i')) {
    // User has permission to create products
}

// Check if user can delete categories
if (!$this->supabase->check_user_permission('categories', 'd')) {
    abort(403, 'Unauthorized');
}
```

**Controller Example:**
```php
public function store(Request $request)
{
    if (!$this->supabase->check_user_permission('products', 'i')) {
        return back()->with('error', 'You do not have permission to create products');
    }

    // Continue with store logic
}
```

### User Have Permission

**Method:** `user_have_permission($resourceName, $permissionCode)`

**Example:**
```php
if ($this->supabase->user_have_permission('products', 'u')) {
    // User can update products
}
```

**Blade Example:**
```blade
@if($supabase->user_have_permission('products', 'd'))
    <form method="POST" action="{{ route('products.destroy', $product['id']) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endif
```

---

## Edge Function Examples

### Basic Edge Function Structure

**File:** `supabase/functions/{table}/index.ts`

```typescript
import { serve } from "https://deno.land/std@0.168.0/http/server.ts"
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  // CORS headers
  const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  }

  // Handle CORS preflight
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders })
  }

  try {
    // Create Supabase client with user's JWT
    const supabaseClient = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_ANON_KEY') ?? '',
      { global: { headers: { Authorization: req.headers.get('Authorization')! } } }
    )

    const { method } = req
    const url = new URL(req.url)
    const id = url.searchParams.get('id')

    // Route based on HTTP method
    switch (method) {
      case 'GET':
        return await handleGet(supabaseClient, url)
      case 'POST':
        return await handlePost(supabaseClient, req)
      case 'PUT':
        return await handlePut(supabaseClient, req, id)
      case 'DELETE':
        return await handleDelete(supabaseClient, id)
      default:
        throw new Error(`Method ${method} not allowed`)
    }
  } catch (error) {
    return new Response(
      JSON.stringify({ success: false, error: error.message }),
      { status: 400, headers: { ...corsHeaders, 'Content-Type': 'application/json' } }
    )
  }
})

async function handleGet(supabaseClient, url) {
  const { data, error } = await supabaseClient
    .from('products')
    .select('*, category:categories(name)')
    .order('created_at', { ascending: false })

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}

async function handlePost(supabaseClient, req) {
  const body = await req.json()

  const { data, error } = await supabaseClient
    .from('products')
    .insert([body])
    .select()

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data: data[0] }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}

async function handlePut(supabaseClient, req, id) {
  if (!id) throw new Error('ID required for update')

  const body = await req.json()

  const { data, error } = await supabaseClient
    .from('products')
    .update(body)
    .eq('id', id)
    .select()

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data: data[0] }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}

async function handleDelete(supabaseClient, id) {
  if (!id) throw new Error('ID required for delete')

  const { error } = await supabaseClient
    .from('products')
    .delete()
    .eq('id', id)

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}
```

### Edge Function with Joins

```typescript
async function handleGet(supabaseClient, url) {
  const { data, error } = await supabaseClient
    .from('events')
    .select(`
      *,
      venue:venues(
        id,
        name,
        address,
        latitude,
        longitude
      ),
      category:categories(
        id,
        name
      )
    `)
    .order('start_datetime', { ascending: true })

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}
```

### Edge Function with Query Parameters

```typescript
async function handleGet(supabaseClient, url) {
  const categoryId = url.searchParams.get('category_id')
  const minPrice = url.searchParams.get('min_price')
  const search = url.searchParams.get('search')

  let query = supabaseClient
    .from('products')
    .select('*, category:categories(name)')

  // Apply filters
  if (categoryId) {
    query = query.eq('category_id', parseInt(categoryId))
  }

  if (minPrice) {
    query = query.gte('price', parseFloat(minPrice))
  }

  if (search) {
    query = query.ilike('name', `%${search}%`)
  }

  const { data, error } = await query.order('name', { ascending: true })

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}
```

### Edge Function with Validation

```typescript
async function handlePost(supabaseClient, req) {
  const body = await req.json()

  // Validate required fields
  if (!body.name || body.name.trim() === '') {
    throw new Error('Name is required')
  }

  if (!body.price || body.price < 0) {
    throw new Error('Price must be greater than or equal to 0')
  }

  if (!body.category_id) {
    throw new Error('Category is required')
  }

  // Validate category exists
  const { data: category, error: categoryError } = await supabaseClient
    .from('categories')
    .select('id')
    .eq('id', body.category_id)
    .single()

  if (categoryError || !category) {
    throw new Error('Invalid category')
  }

  // Insert product
  const { data, error } = await supabaseClient
    .from('products')
    .insert([{
      name: body.name.trim(),
      description: body.description?.trim() || null,
      price: parseFloat(body.price),
      category_id: parseInt(body.category_id),
      is_active: body.is_active ?? true,
      created_at: new Date().toISOString()
    }])
    .select()

  if (error) throw error

  return new Response(
    JSON.stringify({ success: true, data: data[0] }),
    { headers: { 'Content-Type': 'application/json' } }
  )
}
```

---

## Error Handling

### Try-Catch Pattern

```php
try {
    $products = $this->supabase->read_edge('products');
    return view('products.index', compact('products'));
} catch (\Exception $e) {
    Log::error('Failed to fetch products: ' . $e->getMessage());
    return back()->with('error', 'Failed to load products');
}
```

### Validation Before Operations

```php
public function store(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0'
    ]);

    // Check permission
    if (!$this->supabase->check_user_permission('products', 'i')) {
        abort(403, 'Unauthorized');
    }

    try {
        // Create product
        $result = $this->supabase->create_edge($validated, 'products');

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully');
    } catch (\Exception $e) {
        return back()
            ->withInput()
            ->with('error', 'Failed to create product: ' . $e->getMessage());
    }
}
```

### Handle Different Error Types

```php
try {
    $product = $this->supabase->read_edge('products', false, ['id' => $id]);
} catch (\GuzzleHttp\Exception\ClientException $e) {
    // 4xx errors (client errors)
    if ($e->getCode() === 401) {
        return redirect('/login')->with('error', 'Session expired');
    } elseif ($e->getCode() === 403) {
        abort(403, 'Permission denied');
    } elseif ($e->getCode() === 404) {
        abort(404, 'Product not found');
    }
    return back()->with('error', 'Client error occurred');
} catch (\GuzzleHttp\Exception\ServerException $e) {
    // 5xx errors (server errors)
    Log::error('Supabase server error: ' . $e->getMessage());
    return back()->with('error', 'Server error occurred');
} catch (\Exception $e) {
    // General errors
    Log::error('Unexpected error: ' . $e->getMessage());
    return back()->with('error', 'An unexpected error occurred');
}
```

---

## Best Practices

### 1. Always Use Edge Functions

✅ **Preferred:**
```php
$products = $this->supabase->read_edge('products');
$this->supabase->create_edge($data, 'products');
```

❌ **Avoid:**
```php
$products = $this->supabase->read('products');
$this->supabase->create($data, 'products');
```

### 2. Validate Input

```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email',
    'price' => 'required|numeric|min:0'
]);

$this->supabase->create_edge($validated, 'products');
```

### 3. Check Permissions

```php
if (!$this->supabase->check_user_permission('products', 'i')) {
    abort(403);
}
```

### 4. Handle Errors Gracefully

```php
try {
    // Operation
} catch (\Exception $e) {
    Log::error('Error: ' . $e->getMessage());
    return back()->with('error', 'Operation failed');
}
```

### 5. Use Dependency Injection

✅ **Good:**
```php
public function __construct(SupabaseService $supabase)
{
    $this->supabase = $supabase;
}
```

❌ **Avoid:**
```php
$supabase = SupabaseService::getInstance();
```

### 6. Log Important Operations

```php
use Illuminate\Support\Facades\Log;

Log::info('Creating product', ['data' => $data]);
$result = $this->supabase->create_edge($data, 'products');
Log::info('Product created', ['id' => $result['id']]);
```

### 7. Cache Permissions

```php
// In middleware
if (!Session::has('permissions')) {
    $permissions = $this->supabase->get_roles_and_permissions();
    Session::put('permissions', $permissions);
}
```

### 8. Use Transactions for Multiple Operations

```php
DB::transaction(function () use ($data) {
    // Create main record
    $product = $this->supabase->create_edge($data, 'products');

    // Create related records
    foreach ($data['variants'] as $variant) {
        $variant['product_id'] = $product['id'];
        $this->supabase->create_edge($variant, 'product_variants');
    }
});
```

### 9. Implement Rate Limiting

```php
// In routes
Route::middleware('throttle:60,1')->group(function () {
    Route::resource('products', GeneralController::class);
});
```

### 10. Use Query Parameters Efficiently

```php
// Instead of fetching all and filtering in PHP
❌ $products = $this->supabase->read_edge('products');
   $filtered = array_filter($products, fn($p) => $p['category_id'] === 5);

// Filter in Edge Function
✅ $products = $this->supabase->read_edge('products', false, ['category_id' => 5]);
```

---

**Last Updated:** 2025-11-10
**Version:** 1.0.0
