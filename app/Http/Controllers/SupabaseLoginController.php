<?php

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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            $response = $this->supabase->signIn($credentials['email'], $credentials['password']);
            if (isset($response['error'])) {
                return back()->withErrors(['email' => $response['message']]);
            }

            if (isset($response['access_token'])) {
                Session::put('jwt_token', $response['access_token']);
                Session::put('refresh_token', $response['refresh_token']);
                Session::put('user', [
                    'email' => $response['user']['email'],
                    'name' => $this->getUserDisplayName($response['user']),
                    'claims_admin' => $response['user']['app_metadata']['claims_admin'] ?? null,
                    'userrole' => $response['user']['app_metadata']['userrole'] ?? null
                ]);

                return redirect()->route('home');
            } else {
                return back()->withErrors(['error' => "Invalid credentials"], 401);
            }
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            // Extract error message from the response
            $response = $ex->getResponse();
            $responseBody = json_decode((string) $response->getBody(), true);

            // Check if the response contains specific error details
            if (isset($responseBody['msg'])) {
                $errorMessage = $responseBody['msg'];
            } else {
                $errorMessage = 'An error occurred while trying to log in.';
            }

            // Redirect back with the error message
            return back()->withErrors(['error' => $errorMessage]);
        } catch (\Exception $ex) {
            // Generic exception handling
            return back()->withErrors(['error' => 'An unexpected error occurred. Please try again later.']);
        }
    }


    public function logout(Request $request)
    {
        Session::forget('jwt_token');
        Session::forget('refresh_token');

        Session::forget('user');

        // Assuming supabase->logout() clears the session or token on Supabase side
        try {
            $this->supabase->logout();
        } catch (\Exception $ex) {
            // Optionally handle exception, such as logging
        }

        return redirect()->route('login');
    }

    private function getUserDisplayName($user)
    {
        $firstName = $user['user_metadata']['first_name'] ?? $user['app_metadata']['first_name'] ?? null;
        $lastName = $user['user_metadata']['last_name'] ?? $user['app_metadata']['last_name'] ?? null;
        
        if ($firstName && $lastName) {
            return trim($firstName . ' ' . $lastName);
        } elseif ($firstName) {
            return $firstName;
        } elseif ($lastName) {
            return $lastName;
        }
        
        return $user['email'];
    }
}
