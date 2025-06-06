<?php

namespace App\Http\Middleware;

use App\Services\Supabase\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SupabaseMiddleware
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        try {
            // Check if the JWT token exists in the session
            $jwtToken = Session::get('jwt_token');
            $refreshToken = Session::get('refresh_token');
            if (!$jwtToken) {
                // If token not found, return unauthorized error
                return response()->redirectToRoute('login');
            }
            $refreshTokenResponse = $this->supabase->refresh_token($refreshToken);
            Session::put('jwt_token', $refreshTokenResponse['access_token']);

            $user_permissions = $this->supabase->get_roles_and_permissions();
            Session::put('roles_permissions', $user_permissions);

            return $next($request);
        }
        catch (\Exception $e) {
            Session::remove('jwt_token');
            switch ($e->getCode()) {
                case 403:
                    return view('errors.403')->withErrors(['general' => 'You don\'t have permissions to use this resource']);
                case 401:
                    if (str_contains($e->getMessage(), 'PGRST301')) {
                        return redirect('login');
                    }
                case 400:
                    if (str_contains($e->getMessage(), '23502')) {
                        return view('errors.403');
                    }
                default:
                    return redirect('login');
            }
        }
    }
}
