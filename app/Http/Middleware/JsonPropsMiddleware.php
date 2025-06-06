<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class JsonPropsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Specify the storage folder
        $directory = 'json'; // Example: 'app/json' if your files are stored in storage/app/json

        // Read all json files from the directory
        $files = Storage::files($directory);
        $props = [];
        foreach ($files as $file) {
            $entity = str_replace('json', '',
                    str_replace('/', '',
                        str_replace('.', '', $file)
                    )
            );

            if ($entity === explode('/', $request->getRequestUri())[1]) {
                $content = Storage::get($file);
                $decodedContent = json_decode($content, true);
                // Assuming each JSON file's data is an array and can be merged
                $props = array_merge($props, $decodedContent);
            }
        }
        // Attach the props to the request
        $request->attributes->add(['props' => $props]);

        return $next($request);
    }
}
