<?php

namespace App\Http\Middleware;

use App\Services\Supabase\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SupabasePermissions
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get the route name and HTTP method
            $routeName = $request->route()->getName(); // e.g., 'categories.create'

            // Dynamically extract the resource name from the route name
            $resourceName = explode('.', $routeName)[0]; // e.g., 'categories' from 'categories.create'

            // Map HTTP methods and route names to permission codes
            $permissionMap = [
                'index' => 'r',
                'show' => 'r',
                'create' => 'i',
                'store' => 'i',
                'edit' => 'u',
                'update' => 'u',
                'destroy' => 'd',
            ];

            // Extract the action from the route name
            $action = explode('.', $routeName)[1] ?? null; // e.g., 'create' from 'categories.create'

            // Determine the permission code based on the action and HTTP method
            $permissionCode = $permissionMap[$action] ?? null;

            if ($permissionCode && !$this->supabase->check_user_permission($resourceName, $permissionCode)) {
                return redirect()->route('error.403');
            }

            return $next($request);
        } catch (\Exception $ex) {
            // Optionally, log the exception or handle it as needed
            return redirect('errors.403');
        }
    }

}
