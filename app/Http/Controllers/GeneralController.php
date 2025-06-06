<?php

namespace App\Http\Controllers;

use App\Services\Supabase\SupabaseService;
use App\Services\TemplateParserService;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        $methodName = $this->props['GET'];

        // Check if the method exists
        if (method_exists($this->supabase, $methodName)) {
            // Fetch data using the specified method
            $data = $this->supabase->$methodName($this->props['name']['plural']);
        } else {
            abort(404, "Method $methodName does not exist on the object.");
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

        return view('data.index', compact('data', 'props'));
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
                        $data[$prop['key'] ?? $key] = $request->get($prop['key'] ?? $key);
                    }
                }
                if (isset($prop['type']) && $prop['type'] === 'image') {
                    $objectId = false;
                    // Check if an image file was uploaded
                    if ($request->hasFile($prop['key'] ?? $key) && $request->file($prop['key'] ?? $key)->isValid()) {
                        // Define bucket, filename, and filePath
                        $bucket = $prop['bucket'] ?? 'images'; // Replace with your actual bucket name
                        $file = $request->file($prop['key'] ?? $key);
                        $filename = $file->getClientOriginalName(); // Or generate a unique name
                        $filePath = $file->getPathname();

                        // Upload the image to Supabase Storage
                        $uploadedImageName = $this->supabase->uploadImage($filename, $filePath, $bucket);
                        $imageData = $this->supabase->listObjects($uploadedImageName);
                        // Assuming the upload response contains a UUID or some form of identifier
                        $objectId = $imageData[0]['id'] ?? null;
                        $data[$prop['key'] ?? $key] = $objectId;

                        // Check if the image was successfully uploaded
                        if (!$objectId) {
                            // Handle error (image not uploaded)
                            return back()->withErrors(['msg' => 'Failed to upload ' . $prop['type'] . '.']);
                        }
                    }
                }
            }
            $methodName = $this->props['INSERT'];

            if (method_exists($this->supabase, $methodName) && $data !== null) {
                try {

                    $this->supabase->$methodName($data, $methodName === 'create_edge' ? $this->props['name']['singular'] : $this->props['name']['plural']);
                } catch (Exception $e) {
                    Log::error('There was an error creating the ' . ($this->props['name']['label_singular'] ?? $this->props['name']['singular']) . ': ' . $e->getMessage());
                    return back()->withErrors(['msg' => "There was an error creating the " . isset($this->props['name']['label_singular']) ?  strtolower($this->props['name']['label_singular']) : strtolower($this->props['name']['singular']) . "!"]);
                }
            } else {
                dd("Method $methodName does not exist on the object.");
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
            if (method_exists($this->supabase, $methodName)) {
                $data = $this->supabase->$methodName($this->props['name']['plural']);
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
    public function update(Request $request, $id)
    {
        $data = null;
        try {
            foreach ($this->props['schema'] as $key => $prop) {

                if (!isset($prop['readonly']) || !$prop['readonly']) {
                    if($prop['type'] !== 'image') {
                        $data[$prop['key'] ?? $key] = $request->get($prop['key'] ?? $key);
                    }
                }

                if (isset($prop['type']) && $prop['type'] === 'image') {
                    $objectId = false;
                    // Check if an image file was uploaded
                    if ($request->hasFile($prop['key'] ?? $key) && $request->file($prop['key'] ?? $key)->isValid()) {
                        // Define bucket, filename, and filePath
                        $bucket = $prop['bucket'] ?? 'images'; // Replace with your actual bucket name
                        $file = $request->file($prop['key'] ?? $key);
                        $filename = $file->getClientOriginalName(); // Or generate a unique name
                        $filePath = $file->getPathname();

                        // Upload the image to Supabase Storage
                        $uploadedImageName = $this->supabase->uploadImage($filename, $filePath, $bucket);
                        $imageData = $this->supabase->listObjects($uploadedImageName);
                        // Assuming the upload response contains a UUID or some form of identifier
                        $objectId = $imageData[0]['id'] ?? null;
                        $data[$prop['key'] ?? $key] = $objectId;

                        // Check if the image was successfully uploaded
                        if (!$objectId) {
                            // Handle error (image not uploaded)
                            return back()->withErrors(['msg' => 'Failed to upload ' . $prop['type'] . '.']);
                        }
                    }
                }
            }

            $methodName = $this->props['UPDATE'];
            if (method_exists($this->supabase, $methodName) && $data !== null) {
                try {
                    $this->supabase->$methodName($id, $data, $methodName === 'update_edge' ? $this->props['name']['singular'] : $this->props['name']['plural']);
                } catch (Exception $e) {
                    Log::error('There was an error creating the ' . $this->props['name']['singular'] . ': ' . $e->getMessage());
                    return back()->withErrors(['msg' => "There was an error creating the ' . $this->props['name']['singular'] . '!"]);
                }
            } else {
                dd("Method $methodName does not exist on the object.");
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

        if (method_exists($this->supabase, $methodName)) {
            $this->supabase->$methodName($id, $methodName === 'delete_edge' ? $this->props['name']['singular'] : $this->props['name']['plural']);
        } else {
            dd("Method $methodName does not exist on the object.");
        }
        return redirect($this->props['name']['plural'])->with('success', (isset($this->props['name']['label_singular']) ? ucfirst($this->props['name']['label_singular']) : ucfirst($this->props['name']['singular'])) . ' has been deleted successfully!');
    }

    /**
     * @throws Exception
     */
    function getSourceData($source, $valueKey = 'value', $nameKey = 'name', $type = "array", $template = null): array
    {
        if (is_array($source) && isset($source[0])) {
            if ($type === 'class') {
                $className = $source[0]; // Should be 'App\Http\Controllers\SupabaseService'
                $methodName = 'getInstance';
                $serviceInstance = call_user_func([$className, $methodName]);

                if (method_exists($serviceInstance, $source[1])) {
                    $data = $serviceInstance->{$source[1]}($source[2]);
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
            if (isset($prop['type']) && $prop['type'] === 'select') {
                $data[$key] =
                    $this->getSourceData($prop['data']['source'],
                        $prop['data']['value'] ?? 'value',
                        $prop['data']['name'] ?? 'name',
                        $prop['data']['type'] ?? null,
                        $prop['data']['name'] ?? ($prop['data']['value'] ?? null)
                    );
            }
        }
        return $data;
    }

}
