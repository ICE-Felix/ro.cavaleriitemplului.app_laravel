<?php

namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use App\Services\TemplateParserService;
use App\Support\SchemaUtils;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;
use App\Support\DateTime as DT;

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
                if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                    dump('=== GET OPERATION ===');
                    dump([
                        'method' => $methodName,
                        'table'  => $this->props['name']['plural']
                    ]);
                }

                $debugEnabled = isset($this->props['debug']) && in_array('GET', $this->props['debug']);
                $data = $this->supabase->$methodName($this->props['name']['plural'], $debugEnabled);

                if (is_array($data)) {
                    $data = DT::normalizeTemporalCollection($data, $this->props);


                    $data = array_map(function($item) {
                        return $this->transformWooCommerceData($item);
                    }, $data);
                }

            } else {
                dd("Method $methodName does not exist on the object.");
            }

            // Sorting (runs after normalization)
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

            if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                dd('=== FINAL DATA TO VIEW ===', [
                    'data' => $data,
                    'props' => $props
                ]);
            }
            return view('data.index', compact('data', 'props'));
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            if ($e->getCode() == 403) {
                return back()->withErrors(['general' => 'You don\'t have permissions to access this resource']);
            }
        } catch (\Exception $e) {
            dd($e);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
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
        $data = [];

        try {
            // --- Configure per schema ---
            $plural = strtolower($this->props['name']['plural'] ?? '');
            $dtCfg  = $this->props['datetime'] ?? [];

            // Whether to combine raw date/time inputs into timestamptz
            $combineTemporal = (bool)($dtCfg['combine'] ?? false);

            // Where to put the combined values
            $startOutKey = (string)($dtCfg['output']['start'] ?? 'start');
            $endOutKey   = (string)($dtCfg['output']['end']   ?? 'end');

            // Sensible fallback if no datetime config was provided:
            if (!isset($this->props['datetime'])) {
                if (str_contains($plural, 'event')) {
                    $combineTemporal = true;
                    $startOutKey = 'start';
                    $endOutKey   = 'end';
                } elseif (str_contains($plural, 'venue_product')) {
                    $combineTemporal = true;
                    $startOutKey = 'start_date';
                    $endOutKey   = 'end_date';
                }
            }

            $temporalKeys = ['start_date', 'start_hour', 'end_date', 'end_hour'];

            foreach ($this->props['schema'] as $key => $prop) {
                $currentKey = $prop['key'] ?? $key;

                // Skip the raw temporal fields only when combining; we'll set output keys later
                if ($combineTemporal && in_array($currentKey, $temporalKeys, true)) {
                    continue;
                }

                // --- GALLERY ---
                if (($prop['type'] ?? null) === 'gallery') {
                    $field        = $prop['key'] ?? $key;                 // "gallery"
                    $max          = (int)($prop['max_images'] ?? 5);
                    $bucket       = $request->input($field . '_bucket', 'default-galleries');

                    // Existing (for storage mode / keep compatibility)
                    $existingImages = json_decode($request->input($field . '_existing', '[]'), true) ?? [];

                    // We will post base64s for gallery via "gallery_image_base64[]" to avoid clashing
                    // with featured image's "image_base64".
                    $uploadKeyForGallery = $prop['upload_key'] ?? 'gallery_image_base64';

                    // 1) Prefer client-provided base64 list from hidden inputs
                    $incoming = $request->input($uploadKeyForGallery, []);
                    if (!is_array($incoming)) {
                        $incoming = $incoming ? [$incoming] : [];
                    }

                    $base64List = [];
                    foreach ($incoming as $b64) {
                        if (!is_string($b64)) continue;
                        $b64 = trim($b64);
                        if ($b64 === '') continue;
                        // Accept either full data URLs or raw base64; pass-through to Edge
                        $base64List[] = $b64;
                    }

                    // 2) As a fallback only, turn uploaded files into base64
                    if (empty($base64List) && $request->hasFile($field)) {
                        $files = $request->file($field);
                        if ($files instanceof \Illuminate\Http\UploadedFile) {
                            $files = [$files];
                        }
                        if (count($files) > $max) {
                            $files = array_slice($files, 0, $max);
                        }
                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $raw  = file_get_contents($file->getPathname());
                                $mime = $file->getMimeType() ?: 'application/octet-stream';
                                $base64List[] = 'data:' . $mime . ';base64,' . base64_encode($raw);
                            }
                        }
                    }

                    // Store base64 array for the Edge function
                    $data[$uploadKeyForGallery] = !empty($base64List) ? array_slice($base64List, 0, $max) : null;

                    // Do NOT send the storage JSON when using base64 mode
                    $data[$field] = null;

                    continue;
                }

                // --- CHECKBOX ---
                if (($prop['type'] ?? null) === 'checkbox') {
                    $field = $prop['key'] ?? $key;
                    $raw = $request->input($field, []);
                    if (is_string($raw)) {
                        $decoded = json_decode($raw, true);
                        $raw = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                    }
                    if (!is_array($raw)) $raw = [];
                    $data[$field] = array_values(array_filter(array_map(fn($x) => (string)$x, $raw)));
                    continue;
                }

                // --- IMAGE ---
                if (($prop['type'] ?? null) === 'image') {
                    $field = $prop['key'] ?? $key;
                    if ($request->hasFile($field) && $request->file($field)->isValid()) {
                        $file = $request->file($field);
                        $fileContents = file_get_contents($file->getPathname());
                        $data[$prop['upload_key'] ?? $field] = base64_encode($fileContents);
                    }
                    continue;
                }

                // --- INFO FIELDS ---
                if (($prop['type'] ?? null) === 'info_fields') {
                    $field = $prop['key'] ?? $key;
                    $infoData = $request->get($field);

                    if ($infoData) {
                        if (is_string($infoData)) {
                            $decoded = json_decode($infoData, true);
                            $data[$field] = $decoded !== null ? json_encode($decoded) : null;
                        } else {
                            $data[$field] = json_encode($infoData);
                        }
                    } else {
                        $data[$field] = null;
                    }
                    continue;
                }

                // --- LOCATION ---
                if (($prop['type'] ?? null) === 'location') {
                    $fieldName  = $prop['key'] ?? $key;
                    $latField   = $fieldName . '_latitude';
                    $lngField   = $fieldName . '_longitude';

                    $lat = $request->get($latField);
                    $lng = $request->get($lngField);

                    $combinedRaw = $request->get($fieldName);
                    if ($combinedRaw) {
                        $decoded = is_string($combinedRaw) ? json_decode($combinedRaw, true)
                            : (is_array($combinedRaw) ? $combinedRaw : null);
                        if (is_array($decoded)) {
                            $lat = $lat ?? ($decoded['lat'] ?? $decoded['latitude'] ?? null);
                            $lng = $lng ?? ($decoded['lng'] ?? $decoded['longitude'] ?? null);
                            if (!empty($decoded['address'])) {
                                $data['address'] = html_entity_decode($decoded['address'], ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                            }
                        }
                    }

                    $addressInput = $request->get('address');
                    if (!empty($addressInput)) {
                        $data['address'] = html_entity_decode($addressInput, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                    }

                    if ($lat !== null) $data[$latField] = (string)$lat;
                    if ($lng !== null) $data[$lngField] = (string)$lng;

                    unset($data[$fieldName]);
                    continue;
                }

                // --- SCHEDULE ---
                if (($prop['type'] ?? null) === 'schedule') {
                    $field = $prop['key'] ?? $key;
                    $scheduleData = $request->get($field);
                    if ($scheduleData) {
                        if (is_string($scheduleData)) {
                            $decoded = json_decode($scheduleData, true);
                            $data[$field] = $decoded !== null ? json_encode($decoded) : null;
                        } else {
                            $data[$field] = json_encode($scheduleData);
                        }
                    } else {
                        $data[$field] = null;
                    }
                    continue;
                }

                // --- NUMERIC / NUMBER ---
                if (in_array(($prop['type'] ?? null), ['numeric', 'number'], true)) {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field);
                    $data[$field] = $v !== null && $v !== '' ? (float)$v : null;
                    continue;
                }

                // --- SWITCH (bool) ---
                if (($prop['type'] ?? null) === 'switch') {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field, false);
                    $data[$field] = (bool)($v === '1' || $v === 1 || $v === true || $v === 'true');
                    continue;
                }

                // --- LEVEL (int) ---
                if (($prop['key'] ?? $key) === 'level') {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field);
                    $data[$field] = $v !== null && $v !== '' ? (int)$v : null;
                    continue;
                }

                // --- DEFAULT (non-readonly) ---
                if (!isset($prop['readonly']) || !$prop['readonly']) {
                    $field = $prop['key'] ?? $key;
                    if ($request->has($field)) {
                        $value = $request->get($field);
                        if (is_array($value)) {
                            $data[$field] = array_map(function ($item) {
                                return is_string($item)
                                    ? html_entity_decode($item, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8')
                                    : $item;
                            }, $value);
                        } else {
                            $data[$field] = is_string($value)
                                ? html_entity_decode($value, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8')
                                : $value;
                        }
                    }
                }
            }

            // --- Combine to timestamptz only if enabled for this schema ---
            if ($combineTemporal) {
                $startDate = trim((string) $request->input('start_date', ''));
                $startHour = trim((string) $request->input('start_hour', ''));
                $endDate   = trim((string) $request->input('end_date', ''));
                $endHour   = trim((string) $request->input('end_hour', ''));

                $allProvided = ($startDate !== '' && $startHour !== '' && $endDate !== '' && $endHour !== '');

                if ($allProvided) {
                    $tz = config('app.timezone', 'Europe/Bucharest');

                    $startDT = DT::combine($startDate, $startHour, $tz);
                    $endDT   = DT::combine($endDate,   $endHour,   $tz);

                    if (!$startDT || !$endDT) {
                        return back()->withErrors(['msg' => 'Invalid start/end date or time format.']);
                    }
                    if ($endDT->lt($startDT)) {
                        return back()->withErrors(['msg' => 'End must be after Start.']);
                    }

                    $data[$startOutKey] = $startDT->toIso8601String();
                    $data[$endOutKey]   = $endDT->toIso8601String();
                }
            }

            // --- DEBUG ---
            if (isset($this->props['debug']) && in_array('POST', $this->props['debug'])) {
                dump('=== RAW REQUEST DATA ===', $request->all());
                dump('=== PROCESSED DATA FOR SUPABASE ===', $data);
            }

            // --- SAVE ---
            $methodName = $this->props['INSERT'];
            if ($methodName === 'edge') $methodName = "create_edge";

            if (method_exists($this->supabase, $methodName)) {
                try {
                    $debugEnabled = isset($this->props['debug']) && in_array('POST', $this->props['debug']);

                    // Transform for WooCommerce if needed
                    if ($this->props['name']['plural'] === 'woo_products') {
                        $data = $this->transformForWooCommerce($data);
                    }

                    $this->supabase->$methodName($data, $this->props['name']['plural'], $debugEnabled);
                } catch (\Exception $e) {
                    Log::error('Create error: ' . $e->getMessage());
                    return back()->withErrors(['msg' =>
                        "There was an error creating the " .
                        (isset($this->props['name']['label_singular'])
                            ? strtolower($this->props['name']['label_singular'])
                            : strtolower($this->props['name']['singular'])) . "!"
                    ]);
                }
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            if (isset($this->props['debug']) && in_array('POST', $this->props['debug'])) {
                dd('=== FINAL STATE BEFORE REDIRECT (POST) ===', [
                    'operation'       => 'CREATE',
                    'table'           => $this->props['name']['plural'],
                    'processed_data'  => $data,
                    'redirect_route'  => $this->props['name']['plural'],
                    'success_message' => (isset($this->props['name']['label_singular'])
                            ? ucfirst($this->props['name']['label_singular'])
                            : ucfirst($this->props['name']['singular'])) . ' has been created successfully!'
                ]);
            }

            return redirect($this->props['name']['plural'])->with('success',
                (isset($this->props['name']['label_singular'])
                    ? ucfirst($this->props['name']['label_singular'])
                    : ucfirst($this->props['name']['singular'])) . ' has been created successfully!'
            );

        } catch (\Exception $e) {
            Log::error('Error creating ' . ($this->props['name']['singular'] ?? 'entity') . ': ' . $e->getMessage());
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
            $data = [];
            $methodName = $this->props['GET'];

            switch ($methodName) {
                case 'edge':
                    $methodName = "read_edge";
                    break;
            }

            if (method_exists($this->supabase, $methodName)) {
                if (isset($this->props['debug']) && in_array('GET', $this->props['debug'])) {
                    dump('=== GET OPERATION FOR EDIT ===');
                    dump([
                        'method' => $methodName,
                        'table' => $this->props['name']['plural'],
                        'id' => $id
                    ]);
                }

                $debugEnabled = isset($this->props['debug']) && in_array('GET', $this->props['debug']);
                $data = $this->supabase->$methodName($this->props['name']['plural'], $debugEnabled);
            } else {
                dd("Method $methodName does not exist on the object.");
            }

            $data = $this->getData($data);

            if (is_array($data)) {
                $data = DT::normalizeTemporalCollection($data, $this->props);
            }

            $result = [];

            foreach ($data as $elem) {
                if(isset($elem['id']) && strval($elem['id']) === strval($id)) {
                    $result = $elem;
                }
            }
            
            $result = $this->transformWooCommerceData($result);

            $props = $this->props;

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
        \Log::info('=== UPDATE REQUEST DEBUG ===', [
            'method' => $request->method(),
            'url' => $request->url(),
            'id' => $id
        ]);

        $data = [];
        try {
            // --- Per-schema datetime behavior (same as store) ---
            $plural = strtolower($this->props['name']['plural'] ?? '');
            $dtCfg  = $this->props['datetime'] ?? [];

            $combineTemporal = (bool)($dtCfg['combine'] ?? false);
            $startOutKey     = (string)($dtCfg['output']['start'] ?? 'start');
            $endOutKey       = (string)($dtCfg['output']['end']   ?? 'end');

            if (!isset($this->props['datetime'])) {
                if (str_contains($plural, 'event')) {
                    $combineTemporal = true;
                    $startOutKey = 'start';
                    $endOutKey   = 'end';
                } elseif (str_contains($plural, 'venue_product')) {
                    $combineTemporal = true;
                    $startOutKey = 'start_date';
                    $endOutKey   = 'end_date';
                }
            }

            $temporalKeys = ['start_date','start_hour','end_date','end_hour'];

            foreach ($this->props['schema'] as $key => $prop) {
                $currentKey = $prop['key'] ?? $key;

                // Skip raw temporal fields only if we'll combine them later
                if ($combineTemporal && in_array($currentKey, $temporalKeys, true)) {
                    continue;
                }

                // --- GALLERY ---
                if (($prop['type'] ?? null) === 'gallery') {
                    $field        = $prop['key'] ?? $key;                 // "gallery"
                    $max          = (int)($prop['max_images'] ?? 5);
                    $bucket       = $request->input($field . '_bucket', 'default-galleries');

                    // Existing (for storage mode / keep compatibility)
                    $existingImages = json_decode($request->input($field . '_existing', '[]'), true) ?? [];

                    // We will post base64s for gallery via "gallery_image_base64[]" to avoid clashing
                    // with featured image's "image_base64".
                    $uploadKeyForGallery = $prop['upload_key'] ?? 'gallery_image_base64';

                    // 1) Prefer client-provided base64 list from hidden inputs
                    $incoming = $request->input($uploadKeyForGallery, []);
                    if (!is_array($incoming)) {
                        $incoming = $incoming ? [$incoming] : [];
                    }

                    $base64List = [];
                    foreach ($incoming as $b64) {
                        if (!is_string($b64)) continue;
                        $b64 = trim($b64);
                        if ($b64 === '') continue;
                        // Accept either full data URLs or raw base64; pass-through to Edge
                        $base64List[] = $b64;
                    }

                    // 2) As a fallback only, turn uploaded files into base64
                    if (empty($base64List) && $request->hasFile($field)) {
                        $files = $request->file($field);
                        if ($files instanceof \Illuminate\Http\UploadedFile) {
                            $files = [$files];
                        }
                        if (count($files) > $max) {
                            $files = array_slice($files, 0, $max);
                        }
                        foreach ($files as $file) {
                            if ($file && $file->isValid()) {
                                $raw  = file_get_contents($file->getPathname());
                                $mime = $file->getMimeType() ?: 'application/octet-stream';
                                $base64List[] = 'data:' . $mime . ';base64,' . base64_encode($raw);
                            }
                        }
                    }

                    // Store base64 array for the Edge function
                    $data[$uploadKeyForGallery] = !empty($base64List) ? array_slice($base64List, 0, $max) : null;

                    // Do NOT send the storage JSON when using base64 mode
                    $data[$field] = null;

                    continue;
                }
                // --- CHECKBOX ---
                if (($prop['type'] ?? null) === 'checkbox') {
                    $field = $prop['key'] ?? $key;
                    $raw = $request->input($field, []);
                    if (is_string($raw)) {
                        $decoded = json_decode($raw, true);
                        $raw = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                    }
                    if (!is_array($raw)) $raw = [];
                    $data[$field] = array_values(array_filter(array_map(fn($x) => (string)$x, $raw)));
                    continue;
                }

                // --- IMAGE ---
                if (($prop['type'] ?? null) === 'image') {
                    $field = $prop['key'] ?? $key;
                    if ($request->hasFile($field) && $request->file($field)->isValid()) {
                        $file = $request->file($field);
                        $fileContents = file_get_contents($file->getPathname());
                        $data[$prop['upload_key'] ?? $field] = base64_encode($fileContents);
                    }
                    continue;
                }

                // --- INFO FIELDS ---
                if (($prop['type'] ?? null) === 'info_fields') {
                    $field = $prop['key'] ?? $key;
                    $infoData = $request->get($field);

                    if ($infoData) {
                        if (is_string($infoData)) {
                            $decoded = json_decode($infoData, true);
                            if ($decoded !== null) {
                                $data[$field] = json_encode($decoded);
                            } else {
                                $data[$field] = null;
                            }
                        } else {
                            $data[$field] = json_encode($infoData);
                        }
                    } else {
                        $data[$field] = null;
                    }
                    continue;
                }

                // --- LOCATION ---
                if (($prop['type'] ?? null) === 'location') {
                    $fieldName  = $prop['key'] ?? $key;
                    $latField   = $fieldName . '_latitude';
                    $lngField   = $fieldName . '_longitude';

                    $lat = $request->get($latField);
                    $lng = $request->get($lngField);

                    $combinedRaw = $request->get($fieldName);
                    if ($combinedRaw) {
                        $decoded = is_string($combinedRaw) ? json_decode($combinedRaw, true)
                            : (is_array($combinedRaw) ? $combinedRaw : null);
                        if (is_array($decoded)) {
                            $lat = $lat ?? ($decoded['lat'] ?? $decoded['latitude'] ?? null);
                            $lng = $lng ?? ($decoded['lng'] ?? $decoded['longitude'] ?? null);
                            if (!empty($decoded['address'])) {
                                $data['address'] = html_entity_decode($decoded['address'], ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                            }
                        }
                    }

                    $addressInput = $request->get('address');
                    if (!empty($addressInput)) {
                        $data['address'] = html_entity_decode($addressInput, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8');
                    }

                    if ($lat !== null) $data[$latField] = (string)$lat;
                    if ($lng !== null) $data[$lngField] = (string)$lng;

                    unset($data[$fieldName]);
                    continue;
                }

                // --- SCHEDULE ---
                if (($prop['type'] ?? null) === 'schedule') {
                    $field = $prop['key'] ?? $key;
                    $scheduleData = $request->get($field);

                    if ($scheduleData) {
                        if (is_string($scheduleData)) {
                            $decoded = json_decode($scheduleData, true);
                            if ($decoded !== null) {
                                $data[$field] = json_encode($decoded);
                            } else {
                                $data[$field] = null;
                            }
                        } else {
                            $data[$field] = json_encode($scheduleData);
                        }
                    } else {
                        $data[$field] = null;
                    }
                    continue;
                }

                // --- NUMERIC / NUMBER ---
                if (in_array(($prop['type'] ?? null), ['numeric', 'number'], true)) {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field);
                    $data[$field] = $v !== null && $v !== '' ? (float)$v : null;
                    continue;
                }

                // --- SWITCH ---
                if (($prop['type'] ?? null) === 'switch') {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field, false);
                    $data[$field] = (bool)($v === '1' || $v === 1 || $v === true || $v === 'true');
                    continue;
                }

                // --- LEVEL ---
                if (($prop['key'] ?? $key) === 'level') {
                    $field = $prop['key'] ?? $key;
                    $v = $request->get($field);
                    $data[$field] = $v !== null ? (int)$v : null;
                    continue;
                }

                // --- DEFAULT (non-readonly) ---
                if (!isset($prop['readonly']) || !$prop['readonly']) {
                    $field = $prop['key'] ?? $key;
                    $value = $request->get($field);
                    if (is_array($value)) {
                        $data[$field] = array_map(function ($item) {
                            return is_string($item)
                                ? html_entity_decode($item, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8')
                                : $item;
                        }, $value);
                    } else {
                        $data[$field] = is_string($value)
                            ? html_entity_decode($value, ENT_QUOTES | ENT_HTML5 | ENT_XML1, 'UTF-8')
                            : $value;
                    }
                }
            }

            // --- Combine to timestamptz only if enabled for this schema ---
            if ($combineTemporal) {
                $startDate = trim((string) $request->input('start_date', ''));
                $startHour = trim((string) $request->input('start_hour', ''));
                $endDate   = trim((string) $request->input('end_date', ''));
                $endHour   = trim((string) $request->input('end_hour', ''));

                $allProvided = ($startDate !== '' && $startHour !== '' && $endDate !== '' && $endHour !== '');

                if ($allProvided) {
                    $tz = config('app.timezone', 'Europe/Bucharest');

                    $startDT = DT::combine($startDate, $startHour, $tz);
                    $endDT   = DT::combine($endDate,   $endHour,   $tz);

                    if (!$startDT || !$endDT) {
                        return back()->withErrors(['msg' => 'Invalid start/end date or time format.']);
                    }
                    if ($endDT->lt($startDT)) {
                        return back()->withErrors(['msg' => 'End must be after Start.']);
                    }

                    $data[$startOutKey] = $startDT->toIso8601String();
                    $data[$endOutKey]   = $endDT->toIso8601String();
                }
            }

            $methodName = $this->props['UPDATE'];
            if ($methodName === 'edge') {
                $methodName = 'update_edge';
            }

            if (method_exists($this->supabase, $methodName) && $data !== null) {
                try {
                    $debugEnabled = isset($this->props['debug']) && in_array('UPDATE', $this->props['debug']);

                    // Transform for WooCommerce if needed
                    if ($this->props['name']['plural'] === 'woo_products') {
                        $data = $this->transformForWooCommerce($data);
                    }

                    if ($debugEnabled) {
                        \Log::info('=== UPDATE OPERATION ===', [
                            'id' => $id,
                            'method' => $methodName,
                            'table' => $this->props['name']['plural'],
                            'data' => $data
                        ]);
                    }

                    $this->supabase->$methodName($id, $data, $this->props['name']['plural'], $debugEnabled);
                } catch (\Exception $e) {
                    \Log::error('Update error: ' . $e->getMessage());
                    return back()->withErrors(['msg' => "There was an error updating the " . $this->props['name']['singular'] . "!"]);
                }
            } else {
                return back()->withErrors(['msg' => "Method $methodName does not exist."]);
            }

            return redirect()->route($this->props['name']['plural'] . '.index')
                ->with('success', ucfirst($this->props['name']['label_singular'] ?? $this->props['name']['singular']) . " has been updated successfully!");

        } catch (\Exception $e) {
            \Log::error('Update exception: ' . $e->getMessage());
            return back()->withErrors(['msg' => $e->getMessage()]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            \Log::error('Update HTTP exception: ' . $e->getMessage());
            return back()->withErrors(['msg' => $e->message()]);
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
            if (isset($prop['type']) && ($prop['type'] === 'select' || $prop['type'] === 'checkbox') && isset($prop['data'])) {
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
            
            if ($level) {
                $filters = [
                    'parent_id' => 'eq.' . $parentId,
                    'level' => 'eq.' . $level
                ];
            } else {
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

            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $galleryId . '/' . $filename;

            $fileContent = file_get_contents($file->getPathname());
            $contentType = $file->getMimeType();

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

    private function transformWooCommerceData(array $data): array
    {
        if (!isset($this->props['name']['plural']) || $this->props['name']['plural'] !== 'woo_products') {
            return $data;
        }

        if (isset($data['shops']) && is_array($data['shops']) && !empty($data['shops'])) {
            $lastShop = end($data['shops']);

            if (is_array($lastShop)) {
                $data['shop_id'] = $lastShop['id'] ?? null;
                $data['shop'] = $lastShop['id'] ?? null;
                $data['shop_name'] = $lastShop['name'] ?? 'N/A';
            } else {
                $data['shop_id'] = $lastShop;
                $data['shop'] = $lastShop;
            }
        }

        if (isset($data['categories']) && is_array($data['categories'])) {
            $categoryIds = [];
            $categoryNames = [];

            foreach ($data['categories'] as $cat) {
                if (is_array($cat) && isset($cat['id'])) {
                    $categoryIds[] = (string)$cat['id'];
                    $categoryNames[] = $cat['name'] ?? "ID: " . $cat['id'];
                } else {
                    $categoryIds[] = (string)$cat;
                }
            }

            $data['categories'] = $categoryIds;
            $data['category_names'] = implode(', ', $categoryNames);
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagIds = [];
            $tagNames = [];

            foreach ($data['tags'] as $tag) {
                if (is_array($tag) && isset($tag['id'])) {
                    $tagIds[] = (string)$tag['id'];
                    $tagNames[] = $tag['name'] ?? "ID: " . $tag['id'];
                } else {
                    $tagIds[] = (string)$tag;
                }
            }

            $data['tags'] = $tagIds;
            $data['tag_names'] = implode(', ', $tagNames);
        }

        if (isset($data['shops']) && is_array($data['shops']) && !empty($data['shops'])) {
            if (is_array($data['shops'][0])) {
                $data['shop_id'] = $data['shops'][0]['id'] ?? null;
                $data['shop_name'] = $data['shops'][0]['name'] ?? 'N/A';
            } else {
                $data['shop_id'] = $data['shops'][0];
            }
        }

        if (isset($data['images']) && is_array($data['images']) && !empty($data['images'])) {
            if (isset($data['images'][0]['src'])) {
                $data['image_url'] = $data['images'][0]['src'];
            }
        } else {
            $data['image_url'] = null;
        }

        return $data;
    }

    /**
     * Transform data from Laravel format to WooCommerce API format
     */
    private function transformForWooCommerce(array $data): array
    {
        // Transform categories - check if already transformed
        if (isset($data['categories']) && is_array($data['categories'])) {
            $transformed = [];
            foreach ($data['categories'] as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $transformed[] = $item;
                } else {
                    $transformed[] = ['id' => (int)$item];
                }
            }
            $data['categories'] = $transformed;
        }

        if (isset($data['tags']) && is_array($data['tags'])) {
            $transformed = [];
            foreach ($data['tags'] as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $transformed[] = $item;
                } else {
                    $transformed[] = ['id' => (int)$item];
                }
            }
            $data['tags'] = $transformed;
        }

        if (isset($data['shop_id']) && $data['shop_id']) {
            $data['shop_id'] = (int)$data['shop_id'];
        }

        return $data;
    }
}

