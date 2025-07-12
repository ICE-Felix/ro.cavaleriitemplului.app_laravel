<?php

namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use App\Services\TemplateParserService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;

class GeneralController extends Controller
{
    protected $supabase;
    protected TemplateParserService $templateParser;
    private $props;

    public function __construct(SupabaseService $supabase, TemplateParserService $templateParser)
    {
        $this->supabase = $supabase;
        $this->templateParser = $templateParser;
        $this->middleware(function ($request, $next) {
            // Access the props added by the middleware
            $this->props = $request->attributes->get('props');
            // Continue to the next middleware or the controller action
            return $next($request);
        });

    }

    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Foundation\Application
    {
        $data = [];
        
        try {
            $methodName = $this->props['GET'];

            switch ($methodName) {
                case 'edge':
                    $methodName = "read_edge";
                    break;
            }

            if (method_exists($this->supabase, $methodName)) {
                // DEBUG: Check if GET debugging is enabled
                if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                    dump('=== GET OPERATION ===');
                    dump([
                        'method' => $methodName,
                        'table' => $this->props['name']['plural']
                    ]);
                }
                
                // Pass debug flag to SupabaseService
                $debugEnabled = isset($this->props['debug']) && in_array('GET', $this->props['debug']);
                $data = $this->supabase->$methodName($this->props['name']['plural'], $debugEnabled);
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            // Sorting logic
            if (isset($this->props['order_by']) && is_array($this->props['order_by'])) {
                [$field, $direction] = $this->props['order_by'];
                $direction = strtolower($direction) === 'desc' ? SORT_DESC : SORT_ASC;

                usort($data, function ($a, $b) use ($field, $direction) {
                    return $direction === SORT_DESC
                        ? $b[$field] <=> $a[$field]
                        : $a[$field] <=> $b[$field];
                });
            }

            $props = $this->props;

            // DEBUG: Final data being sent to view
            if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                dd('=== FINAL DATA TO VIEW ===', [
                    'data' => $data,
                    'props' => $props
                ]);
            }

            return view('data.index', compact('data', 'props'));
        } catch (GuzzleException $e) {
            if ($e->getCode() == 403) {
                return back()->withErrors(['general' => 'You don\'t have permissions to access this resource']);
            }
        } catch (Exception $e) {
            dd($e);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            $props = $this->props;

            $data = [];

            $data = $this->getData($data);
            $props = $this->props;

            // DEBUG: Final data being sent to view
            if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                dd('=== FINAL DATA TO CREATE VIEW ===', [
                    'data' => $data,
                    'props' => $props
                ]);
            }

            return view('data.create', compact('data', 'props'));

        } catch (GuzzleException $e) {
            if ($e->getCode() == 403) {
                return back()->withErrors(['general' => 'You don\'t have permissions to access this resource']);
            }
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function store(Request $request)
    {
        $data = null;
        try {
            foreach ($this->props['schema'] as $key => $prop) {
                if (!isset($prop['readonly']) || !$prop['readonly']) {
                    if ($request->get($prop['key'] ?? $key) !== null) {
                        // Get the value from request
                        $value = $request->get($prop['key'] ?? $key);
                        
                        // Handle arrays (from checkboxes with multiple options) vs strings
                        if (is_array($value)) {
                            // For arrays, decode each element if it's a string
                            $data[$prop['key'] ?? $key] = array_map(function($item) {
                                return is_string($item) ? html_entity_decode($item, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8') : $item;
                            }, $value);
                        } else {
                            // For strings, decode as usual
                            $data[$prop['key'] ?? $key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                        }
                    }
                }
                if (isset($prop['type']) && $prop['type'] === 'image') {
                    // Check if an image file was uploaded
                    if ($request->hasFile($prop['key'] ?? $key) && $request->file($prop['key'] ?? $key)->isValid()) {
                        $file = $request->file($prop['key'] ?? $key);
                        
                        // Read file contents and convert to base64
                        $fileContents = file_get_contents($file->getPathname());
                        $base64 = base64_encode($fileContents);
                        // Store the base64 string instead of object ID
                        $data[$prop['upload_key'] ?? ($prop['key'] ?? $key)] = $base64;
                    }
                }

                //if type numeric, cast to int or double
                if (isset($prop['type']) && $prop['type'] === 'numeric') {
                    $data[$prop['key'] ?? $key] = (float)$data[$prop['key'] ?? $key];
                }
            }
            
            // DEBUG: Check if POST debugging is enabled
            if (isset($this->props['debug']) && in_array('POST', $this->props['debug'])) {
                dump('=== RAW REQUEST DATA ===');
                dump($request->all());
                
                dump('=== PROCESSED DATA FOR SUPABASE ===');
                dump($data);
            }
            
            $methodName = $this->props['INSERT'];

            switch ($methodName) {
                case 'edge':
                    $methodName = "create_edge";
                    break;
            }

            if (method_exists($this->supabase, $methodName) && $data !== null) {
                try {
                    // DEBUG: Check if POST debugging is enabled
                    if (isset($this->props['debug']) && in_array('POST', $this->props['debug'])) {
                        dump('=== SUPABASE CALL INFO ===');
                        dump([
                            'method' => $methodName,
                            'table' => $this->props['name']['plural'],
                            'data' => $data
                        ]);
                    }

                    // Pass debug flag to SupabaseService
                    $debugEnabled = isset($this->props['debug']) && in_array('POST', $this->props['debug']);
                    $this->supabase->$methodName($data, $this->props['name']['plural'], $debugEnabled);
                } catch (Exception $e) {
                    Log::error('There was an error creating the ' . ($this->props['name']['label_singular'] ?? $this->props['name']['singular']) . ': ' . $e->getMessage());
                    return back()->withErrors(['msg' => "There was an error creating the " . isset($this->props['name']['label_singular']) ?  strtolower($this->props['name']['label_singular']) : strtolower($this->props['name']['singular']) . "!"]);
                }
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            // DEBUG: Final state before redirect
            if (isset($this->props['debug']) && in_array('POST', $this->props['debug'])) {
                dd('=== FINAL STATE BEFORE REDIRECT (POST) ===', [
                    'operation' => 'CREATE',
                    'table' => $this->props['name']['plural'],
                    'processed_data' => $data,
                    'redirect_route' => $this->props['name']['plural'],
                    'success_message' => (isset($this->props['name']['label_singular']) ? ucfirst($this->props['name']['label_singular']) : ucfirst($this->props['name']['singular'])) . ' has been created successfully!'
                ]);
            }

            return redirect($this->props['name']['plural'])->with('success', (isset($this->props['name']['label_singular']) ? ucfirst($this->props['name']['label_singular']) : ucfirst($this->props['name']['singular'])) . ' has been created successfully!');

        } catch (Exception $e) {
            Log::error('Error creating ' . $this->props['name']['singular'] . ' with error: ' . $e->getMessage());// Assuming $e is your exception object

            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $props = $this->props;
            $data = [];

            $methodName = $this->props['GET'];

            switch ($methodName) {
                case 'edge':
                    $methodName = "read_edge";
                    break;
            }
            if (method_exists($this->supabase, $methodName)) {
                // DEBUG: Check if GET debugging is enabled
                if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                    dump('=== GET OPERATION FOR EDIT ===');
                    dump([
                        'method' => $methodName,
                        'table' => $this->props['name']['plural'],
                        'id' => $id
                    ]);
                }
                
                // Pass debug flag to SupabaseService
                $debugEnabled = isset($this->props['debug']) && in_array('GET', $this->props['debug']);
                $data = $this->supabase->$methodName($this->props['name']['plural'], $debugEnabled);
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            $data = $this->getData($data);
            $result = [];

            foreach ($data as $elem) {
                if(isset($elem['id']) && strval($elem['id']) === strval($id)) {
                    $result = $elem;
                }
            }

            $props = $this->props;

            // DEBUG: Final data being sent to view
            if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                dd('=== FINAL DATA TO EDIT VIEW ===', [
                    'data' => $data,
                    'props' => $props,
                    'result' => $result
                ]);
            }

            return view('data.edit', compact('data', 'props', 'result'));

        } catch (GuzzleException $e) {
            if ($e->getCode() == 403) {
                return back()->withErrors(['general' => 'You don\'t have permissions to access this resource']);
            }
        } catch (Exception $e) {
            dd($e);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $data = null;
        try {
            foreach ($this->props['schema'] as $key => $prop) {
                if (!isset($prop['readonly']) || !$prop['readonly']) {
                    if($prop['type'] !== 'image') {
                        // Get the value from request
                        $value = $request->get($prop['key'] ?? $key);
                        
                        // Handle arrays (from checkboxes with multiple options) vs strings
                        if (is_array($value)) {
                            // For arrays, decode each element if it's a string
                            $data[$prop['key'] ?? $key] = array_map(function($item) {
                                return is_string($item) ? html_entity_decode($item, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8') : $item;
                            }, $value);
                        } else {
                            // For strings, decode as usual
                            $data[$prop['key'] ?? $key] = html_entity_decode($value, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                        }
                    }
                }

                if (isset($prop['type']) && $prop['type'] === 'image') {
                    // Check if an image file was uploaded
                    if ($request->hasFile($prop['key'] ?? $key) && $request->file($prop['key'] ?? $key)->isValid()) {
                        $file = $request->file($prop['key'] ?? $key);
                        
                        // Read file contents and convert to base64
                        $fileContents = file_get_contents($file->getPathname());
                        $base64 = base64_encode($fileContents);
                        // Store the base64 string instead of object ID
                        $data[$prop['upload_key'] ?? ($prop['key'] ?? $key)] = $base64;
                    }
                }

                //if type numeric, cast to int or double
                if (isset($prop['type']) && $prop['type'] === 'numeric') {
                    $data[$prop['key'] ?? $key] = ((float)$data[$prop['key'] ?? $key]) ?? 0;
                }
            }

            $methodName = $this->props['UPDATE'];

            switch ($methodName) {
                case 'edge':
                    $methodName = "update_edge";
                    break;
            }

            if (method_exists($this->supabase, $methodName) && $data !== null) {
                try {
                    // DEBUG: Check if UPDATE debugging is enabled
                    if (isset($this->props['debug']) && in_array('UPDATE', $this->props['debug'])) {
                        dump('=== UPDATE OPERATION ===');
                        dump([
                            'id' => $id,
                            'method' => $methodName,
                            'table' => $this->props['name']['plural'],
                            'data' => $data
                        ]);
                    }
                    
                    // Pass debug flag to SupabaseService
                    $debugEnabled = isset($this->props['debug']) && in_array('UPDATE', $this->props['debug']);
                    $this->supabase->$methodName($id, $data, $this->props['name']['plural'], $debugEnabled);
                } catch (Exception $e) {
                    Log::error('There was an error creating the ' . $this->props['name']['singular'] . ': ' . $e->getMessage());
                    return back()->withErrors(['msg' => "There was an error creating the ' . $this->props['name']['singular'] . '!"]);
                }
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            // DEBUG: Final state before redirect
            if (isset($this->props['debug']) && in_array('UPDATE', $this->props['debug'])) {
                dd('=== FINAL STATE BEFORE REDIRECT (UPDATE) ===', [
                    'operation' => 'UPDATE',
                    'id' => $id,
                    'table' => $this->props['name']['plural'],
                    'processed_data' => $data,
                    'redirect_route' => $this->props['name']['plural'] . '.index',
                    'success_message' => ucfirst($this->props['name']['label_singular'] ?? $this->props['name']['singular']) . " has been updated successfully!"
                ]);
            }

            return redirect()->route($this->props['name']['plural'] . '.index')
                ->with('success', ucfirst($this->props['name']['label_singular'] ?? $this->props['name']['singular']) . " has been updated successfully!");
        } catch (Exception $e) {
            Log::error('Error creating ' . $this->props['name']['singular']  . $e->getMessage());
            return back()->withErrors(['msg' => $e->getMessage()]);
        } catch (GuzzleException $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @throws GuzzleException
     */
    public function destroy(string $id): \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\Foundation\Application
    {
        $methodName = $this->props['DELETE'];

        switch ($methodName) {
            case 'edge':
                $methodName = "delete_edge";
                break;
        }

        if (method_exists($this->supabase, $methodName)) {
            // DEBUG: Check if DELETE debugging is enabled
            if (isset($this->props['debug']) && in_array('DELETE', $this->props['debug'])) {
                dump('=== DELETE OPERATION ===');
                dump([
                    'id' => $id,
                    'method' => $methodName,
                    'table' => $this->props['name']['plural']
                ]);
            }
            
            // Pass debug flag to SupabaseService
            $debugEnabled = isset($this->props['debug']) && in_array('DELETE', $this->props['debug']);
            $this->supabase->$methodName($id, $this->props['name']['plural'], $debugEnabled);
        } else {
            dd("Method $methodName does not exist on the object.");
        }
        
        // DEBUG: Final state before redirect
        if (isset($this->props['debug']) && in_array('DELETE', $this->props['debug'])) {
            dd('=== FINAL STATE BEFORE REDIRECT (DELETE) ===', [
                'operation' => 'DELETE',
                'id' => $id,
                'table' => $this->props['name']['plural'],
                'redirect_route' => $this->props['name']['plural'],
                'success_message' => (isset($this->props['name']['label_singular']) ? ucfirst($this->props['name']['label_singular']) : ucfirst($this->props['name']['singular'])) . ' has been deleted successfully!'
            ]);
        }
        
        return redirect($this->props['name']['plural'])->with('success', (isset($this->props['name']['label_singular']) ? ucfirst($this->props['name']['label_singular']) : ucfirst($this->props['name']['singular'])) . ' has been deleted successfully!');
    }

    /**
     * @throws Exception
     */
    function getSourceData($source, $valueKey = 'value', $nameKey = 'name', $type = "array", $template = null, $filters = []): array
    {
        if (is_array($source) && isset($source[0])) {
            if ($type === 'class') {
                $className = $source[0]; // Should be 'App\Http\Controllers\SupabaseService'
                $methodName = 'getInstance';
                $serviceInstance = call_user_func([$className, $methodName]);

                if($source[1] === "edge") {
                    $source[1] = "read_edge";
                }

                if (method_exists($serviceInstance, $source[1])) {
                    // Pass debug flag to SupabaseService for GET operations
                    $debugEnabled = isset($this->props['debug']) && in_array('GET', $this->props['debug']);
                    
                    // Handle filtered methods
                    if ($source[1] === 'read_edge_filtered') {
                        // Extract filters from source array (4th element) if provided
                        $sourceFilters = isset($source[3]) && is_array($source[3]) ? $source[3] : [];
                        // Merge with any additional filters passed as parameter
                        $allFilters = array_merge($sourceFilters, $filters);
                        
                        if ($debugEnabled) {
                            dump('=== CONTROLLER FILTER DEBUG ===');
                            dump('Source array:', $source);
                            dump('Source filters (4th element):', $sourceFilters);
                            dump('Additional filters:', $filters);
                            dump('All filters to pass:', $allFilters);
                        }
                        
                        $data = $serviceInstance->{$source[1]}($source[2], $allFilters, $debugEnabled);
                    } else {
                        $data = $serviceInstance->{$source[1]}($source[2], $debugEnabled);
                    }
                    
                    return array_map(function ($item) use ($valueKey, $nameKey, $template) {
                        return [
                            'value' => $item[$valueKey],
                            'name' => ($template !== null) ? (!empty($this->templateParser->parseTemplate($template, $item)) ? $this->templateParser->parseTemplate($template, $item) : $item[$valueKey]) : $item[$valueKey]
                        ];
                    }, $data);
                } else {
                    throw new Exception("Method does not exist.");
                }
            }
        } else {
            return array_map(function ($key, $item) use ($valueKey, $nameKey) {
                return [
                    'value' => $item[$valueKey] ?? $key,
                    'name' => $item[$nameKey] ?? $item ,
                ];
            }, array_keys($source), $source);
        }
        return [];
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function getData(array $data): array
    {
        foreach ($this->props['schema'] as $key => $prop) {
            if (isset($prop['type']) && ($prop['type'] === 'select' || $prop['type'] === 'checkbox' || $prop['type'] === 'hierarchical_checkbox') && isset($prop['data'])) {
                $data[$key] =
                    $this->getSourceData($prop['data']['source'],
                        $prop['data']['value'] ?? 'value',
                        $prop['data']['name'] ?? 'name',
                        $prop['data']['type'] ?? null,
                        $prop['data']['name'] ?? ($prop['data']['value'] ?? null),
                        $prop['filters'] ?? []
                    );
            }
        }
        return $data;
    }

    public function generateAiImage(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'size' => 'required|in:512x512,1024x1024,1792x1024,1024x1792',
            'component_name' => 'required|string'
        ]);

        try {
            $openAIService = new OpenAIService();
            
            // Generate image
            $result = $openAIService->generateImage($request->prompt, $request->size);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            
            // Download and save the first image
            $imageUrl = $result['images'][0]['url'];
            $filename = 'ai_' . time() . '_' . uniqid() . '.png';
            
            $downloadResult = $openAIService->downloadAndSaveImage($imageUrl, $filename);
            
            if (!$downloadResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $downloadResult['error']
                ]);
            }
            
            // Convert image to base64 for file field (if needed)
            $imagePath = storage_path('app/public/' . $downloadResult['path']);
            
            // Add safety check for file existence
            if (!file_exists($imagePath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Generated image file not found'
                ]);
            }
            
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            $base64Image = 'data:' . $mimeType . ';base64,' . $imageData;
            
            return response()->json([
                'success' => true,
                'path' => $downloadResult['path'],
                'preview_url' => url('storage/' . $downloadResult['path']),
                'filename' => $filename,
                'file_data' => $base64Image, // For file field
                'file_size' => filesize($imagePath),
                'mime_type' => $mimeType
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AI Image Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate image: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get subcategories for a given parent category
     */
    public function getSubcategories(Request $request, $table)
    {
        try {
            $parentId = $request->query('parent_id');
            
            // Add debug logging
            \Log::info('getSubcategories called', [
                'table' => $table,
                'parent_id' => $parentId,
                'request_headers' => $request->headers->all(),
                'user_authenticated' => session()->has('jwt_token')
            ]);
            
            if (!$parentId) {
                \Log::warning('getSubcategories: Parent ID is required');
                return response()->json([
                    'success' => false,
                    'error' => 'Parent ID is required'
                ], 400);
            }

            // Use the filtered method to get subcategories
            $filters = ['parent_id', 'eq', $parentId];
            
            \Log::info('getSubcategories: Calling read_edge_filtered', [
                'table' => $table,
                'filters' => $filters
            ]);
            
            $subcategories = $this->supabase->read_edge_filtered($table, $filters);
            
            \Log::info('getSubcategories: Success', [
                'table' => $table,
                'subcategories_count' => count($subcategories),
                'subcategories' => $subcategories
            ]);

            return response()->json([
                'success' => true,
                'subcategories' => $subcategories
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading subcategories', [
                'table' => $table,
                'parent_id' => $request->query('parent_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load subcategories: ' . $e->getMessage()
            ], 500);
        }
    }

}

