<?php

namespace App\Http\Middleware;

use App\Services\Supabase\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class LoadMenuData
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $supabase = SupabaseService::getInstance();

            $menu = $supabase->get_app_menu();
            View::share('menus', $menu);
        }
        catch (\Exception $e) {

        }

        return $next($request);

    }
}
