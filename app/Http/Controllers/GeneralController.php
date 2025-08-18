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
                
                //if field is level, cast to integer (for hierarchy levels 1, 2, 3)
                if (($prop['key'] ?? $key) === 'level') {
                    $value = $data[$prop['key'] ?? $key] ?? null;
                    $data[$prop['key'] ?? $key] = $value !== null ? (int)$value : null;
                }
                
                //if type switch, cast to boolean
                if (isset($prop['type']) && $prop['type'] === 'switch') {
                    $value = $data[$prop['key'] ?? $key] ?? false;
                    $data[$prop['key'] ?? $key] = (bool)($value === '1' || $value === 1 || $value === true || $value === 'true');
                }
                
                //if type schedule, ensure proper JSON structure
                if (isset($prop['type']) && $prop['type'] === 'schedule') {
                    $scheduleData = $data[$prop['key'] ?? $key] ?? null;
                    if ($scheduleData) {
                        // If it's a JSON string, decode it first to validate and then re-encode
                        if (is_string($scheduleData)) {
                            $decoded = json_decode($scheduleData, true);
                            if ($decoded !== null) {
                                // Re-encode to ensure proper JSON format for Supabase
                                $data[$prop['key'] ?? $key] = json_encode($decoded);
                                \Log::info('Schedule data processed (string input)', ['key' => $prop['key'] ?? $key, 'final_value' => $data[$prop['key'] ?? $key]]);
                            } else {
                                // Invalid JSON, set to null
                                $data[$prop['key'] ?? $key] = null;
                                \Log::warning('Invalid schedule JSON data', ['key' => $prop['key'] ?? $key, 'raw_data' => $scheduleData]);
                            }
                        } else {
                            // If it's already an array, encode it to JSON string
                            $data[$prop['key'] ?? $key] = json_encode($scheduleData);
                            \Log::info('Schedule data processed (array input)', ['key' => $prop['key'] ?? $key, 'final_value' => $data[$prop['key'] ?? $key]]);
                        }
                    } else {
                        // Set default empty schedule if no data provided
                        $data[$prop['key'] ?? $key] = null;
                        \Log::info('Schedule data set to null (no data provided)', ['key' => $prop['key'] ?? $key]);
                    }
                }
                
                //if type gallery, ensure proper JSON structure
                if (isset($prop['type']) && $prop['type'] === 'gallery') {
                    $galleryData = $data[$prop['key'] ?? $key] ?? null;
                    if ($galleryData) {
                        // If it's a JSON string, decode it
                        if (is_string($galleryData)) {
                            $galleryData = json_decode($galleryData, true);
                        }
                        // Ensure it's properly formatted for Supabase
                        $data[$prop['key'] ?? $key] = $galleryData;
                    } else {
                        // Set default empty gallery if no data provided
                        $data[$prop['key'] ?? $key] = null;
                    }
                }
                
                //if type location, handle latitude and longitude
                if (isset($prop['type']) && $prop['type'] === 'location') {
                    $fieldName = $prop['key'] ?? $key;
                    $latitudeField = $fieldName . '_latitude';
                    $longitudeField = $fieldName . '_longitude';
                    
                    // Get latitude and longitude from request
                    $latitude = $request->get($latitudeField);
                    $longitude = $request->get($longitudeField);
                    
                    // Store latitude and longitude as separate fields
                    if ($latitude !== null) {
                        $data[$latitudeField] = (string)$latitude;
                    }
                    if ($longitude !== null) {
                        $data[$longitudeField] = (string)$longitude;
                    }
                    
                    // Remove the original location field from data if it exists
                    unset($data[$fieldName]);
                }
                
                //if type checkbox or three_level_hierarchical_checkbox, ensure UUID strings
                if (isset($prop['type']) && in_array($prop['type'], ['checkbox', 'three_level_hierarchical_checkbox'])) {
                    $fieldName = $prop['key'] ?? $key;
                    $value = $data[$fieldName] ?? null;
                    
                    if ($value) {
                        // If it's a JSON string, decode it first
                        if (is_string($value)) {
                            $decoded = json_decode($value, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $value = $decoded;
                            }
                        }
                        
                        // Ensure all values are UUID strings (not integers)
                        if (is_array($value)) {
                            $data[$fieldName] = array_map(function($item) {
                                return (string)$item; // Cast to string to preserve UUID format
                            }, array_filter($value)); // Remove empty values
                        }
                    }
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
        // Debug: Log all incoming request data
        \Log::info('=== UPDATE REQUEST DEBUG ===');
        \Log::info('Request method: ' . $request->method());
        \Log::info('Request URL: ' . $request->url());
        \Log::info('All request data:', $request->all());
        \Log::info('business_hours from request->get(): ' . ($request->get('business_hours') ?? 'NULL'));
        \Log::info('business_hours from request->input(): ' . ($request->input('business_hours') ?? 'NULL'));
        \Log::info('=== END UPDATE REQUEST DEBUG ===');
        
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
                
                //if field is level, cast to integer (for hierarchy levels 1, 2, 3)
                if (($prop['key'] ?? $key) === 'level') {
                    $value = $data[$prop['key'] ?? $key] ?? null;
                    $data[$prop['key'] ?? $key] = $value !== null ? (int)$value : null;
                }
                
                //if type switch, cast to boolean
                if (isset($prop['type']) && $prop['type'] === 'switch') {
                    $value = $data[$prop['key'] ?? $key] ?? false;
                    $data[$prop['key'] ?? $key] = (bool)($value === '1' || $value === 1 || $value === true || $value === 'true');
                }
                
                //if type schedule, ensure proper JSON structure
                if (isset($prop['type']) && $prop['type'] === 'schedule') {
                    $scheduleData = $data[$prop['key'] ?? $key] ?? null;
                    dump('Business Hours data received (UPDATE) --->');
                    dump('Key: ' . ($prop['key'] ?? $key));
                    dump('Raw data from request: ' . ($scheduleData ?? 'NULL'));
                    dump('All request data for debugging:', $request->all());
                    if ($scheduleData) {
                        // If it's a JSON string, decode it first to validate and then re-encode
                        if (is_string($scheduleData)) {
                            $decoded = json_decode($scheduleData, true);
                            if ($decoded !== null) {
                                // Re-encode to ensure proper JSON format for Supabase
                                $data[$prop['key'] ?? $key] = json_encode($decoded);
                                \Log::info('Schedule data processed (string input - update)', ['key' => $prop['key'] ?? $key, 'final_value' => $data[$prop['key'] ?? $key]]);
                            } else {
                                // Invalid JSON, set to null
                                $data[$prop['key'] ?? $key] = null;
                                \Log::warning('Invalid schedule JSON data (update)', ['key' => $prop['key'] ?? $key, 'raw_data' => $scheduleData]);
                            }
                        } else {
                            // If it's already an array, encode it to JSON string
                            $data[$prop['key'] ?? $key] = json_encode($scheduleData);
                            \Log::info('Schedule data processed (array input - update)', ['key' => $prop['key'] ?? $key, 'final_value' => $data[$prop['key'] ?? $key]]);
                        }
                    } else {
                        // Set default empty schedule if no data provided
                        $data[$prop['key'] ?? $key] = null;
                        \Log::info('Schedule data set to null (no data provided - update)', ['key' => $prop['key'] ?? $key]);
                    }
                }
                
                //if type gallery, ensure proper JSON structure
                if (isset($prop['type']) && $prop['type'] === 'gallery') {
                    $galleryData = $data[$prop['key'] ?? $key] ?? null;
                    if ($galleryData) {
                        // If it's a JSON string, decode it
                        if (is_string($galleryData)) {
                            $galleryData = json_decode($galleryData, true);
                        }
                        // Ensure it's properly formatted for Supabase
                        $data[$prop['key'] ?? $key] = $galleryData;
                    } else {
                        // Set default empty gallery if no data provided
                        $data[$prop['key'] ?? $key] = null;
                    }
                }
                
                //if type location, handle latitude and longitude
                if (isset($prop['type']) && $prop['type'] === 'location') {
                    $fieldName = $prop['key'] ?? $key;
                    $latitudeField = $fieldName . '_latitude';
                    $longitudeField = $fieldName . '_longitude';
                    
                    // Get latitude and longitude from request
                    $latitude = $request->get($latitudeField);
                    $longitude = $request->get($longitudeField);
                    
                    // Store latitude and longitude as separate fields
                    if ($latitude !== null) {
                        $data[$latitudeField] = (string)$latitude;
                    }
                    if ($longitude !== null) {
                        $data[$longitudeField] = (string)$longitude;
                    }
                    
                    // Remove the original location field from data if it exists
                    unset($data[$fieldName]);
                }
                
                //if type checkbox or three_level_hierarchical_checkbox, ensure UUID strings
                if (isset($prop['type']) && in_array($prop['type'], ['checkbox', 'three_level_hierarchical_checkbox'])) {
                    $fieldName = $prop['key'] ?? $key;
                    $value = $data[$fieldName] ?? null;
                    
                    if ($value) {
                        // If it's a JSON string, decode it first
                        if (is_string($value)) {
                            $decoded = json_decode($value, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $value = $decoded;
                            }
                        }
                        
                        // Ensure all values are UUID strings (not integers)
                        if (is_array($value)) {
                            $data[$fieldName] = array_map(function($item) {
                                return (string)$item; // Cast to string to preserve UUID format
                            }, array_filter($value)); // Remove empty values
                        }
                    }
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
                    if ($source[1] === 'read_edge') {
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
            if (isset($prop['type']) && ($prop['type'] === 'select' || $prop['type'] === 'checkbox' || $prop['type'] === 'hierarchical_checkbox' || $prop['type'] === 'three_level_hierarchical_checkbox') && isset($prop['data'])) {
                // Check for static options first
                if (isset($prop['data']['type']) && $prop['data']['type'] === 'static' && isset($prop['data']['options'])) {
                    // Handle static options
                    $data[$key] = array_map(function ($option) {
                        return [
                            'value' => $option['value'],
                            'name' => $option['name']
                        ];
                    }, $prop['data']['options']);
                }
                // Check if data property has source (for dynamic data)
                elseif (isset($prop['data']['source'])) {
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
     * Generate AI description using OpenAI GPT
     */
    public function generateAiDescription(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|max:1000',
            'field_name' => 'required|string',
            'max_length' => 'nullable|integer|min:50|max:2000',
            'model' => 'nullable|in:gpt-3.5-turbo,gpt-4',
            'temperature' => 'nullable|numeric|min:0|max:2'
        ]);

        try {
            $openAIService = new \App\Services\OpenAIService();
            
            $maxTokens = $request->max_length ? min($request->max_length / 4, 500) : 300; // Rough token estimation
            $model = $request->model ?? 'gpt-3.5-turbo';
            $temperature = $request->temperature ?? 0.7;
            
            // Enhance the prompt with context
            $enhancedPrompt = $this->buildDescriptionPrompt($request->prompt, $request->field_name);
            
            // Generate text
            $result = $openAIService->generateText($enhancedPrompt, $model, $maxTokens, $temperature);
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ]);
            }
            
            return response()->json([
                'success' => true,
                'text' => $result['text'],
                'usage' => $result['usage'] ?? null,
                'word_count' => str_word_count($result['text']),
                'character_count' => strlen($result['text'])
            ]);
            
        } catch (\Exception $e) {
            \Log::error('AI Description Generation Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate description: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Build enhanced prompt for description generation
     */
    private function buildDescriptionPrompt($userPrompt, $fieldName)
    {
        $contextualInstructions = [
            'description' => 'Write a detailed, engaging description that captures the essence and key details.',
            'agenda' => 'Create a well-structured agenda with clear sections and actionable items.',
            'body' => 'Generate comprehensive content that is informative and well-organized.',
            'content' => 'Create engaging, high-quality content that is clear and informative.',
            'summary' => 'Write a concise yet comprehensive summary that covers the main points.',
            'overview' => 'Provide a clear overview that gives readers a complete understanding.',
        ];

        $instruction = $contextualInstructions[$fieldName] ?? 'Generate clear, professional text content.';
        
        return "Context: You are generating content for a '{$fieldName}' field.\n\n" .
               "Instructions: {$instruction}\n\n" .
               "User Request: {$userPrompt}\n\n" .
               "Please provide well-formatted, engaging content that directly addresses the request:";
    }

    /**
     * Get subcategories for a given parent category
     */
    public function getSubcategories(Request $request, $table)
    {
        try {
            $parentId = $request->query('parent_id');
            $level = $request->query('level'); // New parameter for level filtering
            
            // Add debug logging
            \Log::info('getSubcategories called', [
                'table' => $table,
                'parent_id' => $parentId,
                'level' => $level,
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

            // Build filters based on parent_id and optionally level
            $filters = [];
            
            if ($level) {
                // If level is specified, filter by both parent_id and level
                $filters = [
                    'parent_id' => 'eq.' . $parentId,
                    'level' => 'eq.' . $level
                ];
            } else {
                // Original behavior: filter by parent_id only
                $filters = ['parent_id', 'eq', $parentId];
            }
            
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

            return response()->json($subcategories);

        } catch (\Exception $e) {
            \Log::error('Error loading subcategories', [
                'table' => $table,
                'parent_id' => $request->query('parent_id'),
                'level' => $request->query('level'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to load subcategories: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Upload gallery image to Supabase Storage
     */
    public function uploadGalleryImage(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
                'gallery_id' => 'required|string',
                'bucket' => 'required|string'
            ]);
            
            $file = $request->file('file');
            $galleryId = $request->input('gallery_id');
            $bucket = $request->input('bucket', 'venue-galleries');
            
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $galleryId . '/' . $filename;
            
            // Read file content
            $fileContent = file_get_contents($file->getPathname());
            $contentType = $file->getMimeType();
            
            // Upload to Supabase Storage
            $result = $this->supabase->uploadToStorage($bucket, $path, $fileContent, $contentType);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'image' => [
                        'id' => uniqid(),
                        'url' => $result['public_url'],
                        'path' => $path,
                        'filename' => $filename,
                        'size' => $file->getSize(),
                        'mime_type' => $contentType
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']['message'] ?? 'Upload failed'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Gallery upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete gallery image from Supabase Storage
     */
    public function deleteGalleryImage(Request $request)
    {
        try {
            $request->validate([
                'gallery_id' => 'required|string',
                'image_path' => 'required|string',
                'bucket' => 'required|string'
            ]);
            
            $galleryId = $request->input('gallery_id');
            $imagePath = $request->input('image_path');
            $bucket = $request->input('bucket', 'venue-galleries');
            
            // Delete from Supabase Storage
            $result = $this->supabase->deleteFromStorage($bucket, $imagePath);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']['message'] ?? 'Delete failed'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Gallery delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * List gallery images from Supabase Storage
     */
    public function listGalleryImages(Request $request, $galleryId)
    {
        try {
            $bucket = $request->input('bucket', 'venue-galleries');
            
            // List files in gallery folder
            $result = $this->supabase->listStorageFiles($bucket, $galleryId);
            
            if ($result['success']) {
                $images = array_map(function($file) use ($bucket) {
                    return [
                        'id' => $file['id'] ?? uniqid(),
                        'name' => $file['name'],
                        'url' => $this->supabase->getStoragePublicUrl($bucket, $file['name']),
                        'path' => $file['name'],
                        'size' => $file['metadata']['size'] ?? 0,
                        'created_at' => $file['created_at'] ?? null,
                        'updated_at' => $file['updated_at'] ?? null
                    ];
                }, $result['data']);
                
                return response()->json([
                    'success' => true,
                    'images' => $images
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']['message'] ?? 'Failed to list images'
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error('Gallery list error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the events calendar
     */
    public function calendar()
    {
        return view('calendar', [
            'title' => 'Events Calendar',
            'events_create_url' => route('events.create')
        ]);
    }

    /**
     * API endpoint to fetch events data for FullCalendar
     */
    public function getCalendarEvents(Request $request)
    {
        try {
            // Get events from Supabase
            $result = $this->supabase->read_edge('events');
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch events'
                ], 500);
            }

            $events = [];
            foreach ($result['data'] as $event) {
                // Combine date and time for start and end
                $startDateTime = null;
                $endDateTime = null;
                
                if (!empty($event['start_date'])) {
                    $startDate = $event['start_date'];
                    $startTime = $event['start_hour'] ?? '00:00';
                    $startDateTime = $startDate . 'T' . $startTime;
                }
                
                if (!empty($event['end_date'])) {
                    $endDate = $event['end_date'];
                    $endTime = $event['end_hour'] ?? '23:59';
                    $endDateTime = $endDate . 'T' . $endTime;
                }

                $calendarEvent = [
                    'id' => $event['id'],
                    'title' => $event['title'] ?? 'Untitled Event',
                    'start' => $startDateTime,
                    'end' => $endDateTime,
                    'description' => strip_tags($event['description'] ?? ''),
                    'venue' => $event['venue_name'] ?? '',
                    'event_type' => $event['event_type_name'] ?? '',
                    'price' => $event['price'] ?? '',
                    'capacity' => $event['capacity'] ?? '',
                    'contact_person' => $event['contact_person'] ?? '',
                    'phone_no' => $event['phone_no'] ?? '',
                    'email' => $event['email'] ?? '',
                    'url' => route('events.edit', $event['id']), // Link to edit event
                    'backgroundColor' => $this->getEventColor($event['event_type_name'] ?? ''),
                    'borderColor' => $this->getEventColor($event['event_type_name'] ?? ''),
                    'textColor' => '#ffffff'
                ];

                $events[] = $calendarEvent;
            }

            return response()->json($events);

        } catch (\Exception $e) {
            \Log::error('Calendar events fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get color for event based on event type
     */
    private function getEventColor($eventType)
    {
        $colors = [
            'Conference' => '#3788d8',
            'Workshop' => '#28a745',
            'Meeting' => '#ffc107',
            'Training' => '#dc3545',
            'Social' => '#6f42c1',
            'Concert' => '#fd7e14',
            'Exhibition' => '#20c997',
            'default' => '#6c757d'
        ];

        return $colors[$eventType] ?? $colors['default'];
    }

}

