<?php

return [
    'url' => env('SUPABASE_URL'),
    /*
    |--------------------------------------------------------------------------
    | Supabase API Keys
    |--------------------------------------------------------------------------
    |
    | `SUPABASE_KEY` is kept for backwards compatibility. If it is not set we
    | gracefully fall back to the anonymous key so password logins can work out
    | of the box in local environments where only the anon key is provided.
    |
    */
    'key' => env('SUPABASE_KEY', env('SUPABASE_ANON_KEY')),
    'anon_key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),
];
