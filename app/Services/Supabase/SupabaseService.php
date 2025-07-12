<?php

namespace App\Services\Supabase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\Uuid;

class SupabaseService
{
    private static ?SupabaseService $instance = null; // Static variable to hold the instance
    protected Client $client;
    protected mixed $apiKey;
    protected mixed $baseUrl;

    // Make the constructor private to prevent external instantiation
    private function __construct()
    {
        $this->baseUrl = config('supabase.url');
        $this->apiKey = config('supabase.key');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            // Move 'Authorization' header to each method
            'headers' => [
                'apikey' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    // Public static method to get the instance of the class
    public static function getInstance(): ?SupabaseService
    {
        if (self::$instance === null) {
            self::$instance = new self(); // Create the instance if it doesn't exist
        }

        return self::$instance;
    }

    /**
     * @throws GuzzleException
     */
    public function signIn($email, $password)
    {
        $response = $this->client->post('auth/v1/token?grant_type=password', [
            'json' => [
                'email' => $email,
                'password' => $password,
            ]
        ]);


        return json_decode((string)$response->getBody(), true);
    }

    public function refresh_token($token)
    {
        $response = $this->client->post('auth/v1/token?grant_type=refresh_token', [
            'json' => [
                'refresh_token' => $token
            ]
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function logout()
    {
        $response = $this->client->post('auth/v1/logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function get_roles_and_permissions()
    {
        $response = $this->client->post('rest/v1/rpc/get_roles_and_permissions', [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    public static function user_have_permission($resourceName, $permissionCode): bool
    {
        $forbidden = true;

        if ($permissionCode) {
            $roles_permission = Session::get('roles_permissions');
            if (is_array($permissionCode)) {
                foreach ($permissionCode as $code) {
                    if ($roles_permission !== null) {
                        foreach ($roles_permission as $role_permission) {
                            if ($resourceName === $role_permission['name']) {
                                if ($role_permission['actions'] === "*" || $role_permission['actions'] === $code) {
                                    $forbidden = false;
                                }
                            }
                        }
                    }
                }
            } else {
                if ($roles_permission !== null) {
                    foreach ($roles_permission as $role_permission) {
                        if ($resourceName === $role_permission['name']) {
                            if ($role_permission['actions'] === "*" || $role_permission['actions'] === $permissionCode) {
                                $forbidden = false;
                            }
                        }
                    }
                }
            }
        }

        return !$forbidden;
    }

    /**
     * @throws GuzzleException
     */
    public function processRequest($url, $params, $method = "GET")
    {
       
        try {
            switch ($method) {
                case 'GET':
                    return $this->client->get($url, $params);
                case 'POST':
                    return $this->client->post($url, $params);
                case 'PATCH':
                    return $this->client->patch($url, $params);
                case 'PUT':
                    return $this->client->put($url, $params);
                case 'DELETE':
                    return $this->client->delete($url, $params);
                default:
                    dd("There is no valid method to access $url");
            }
        } catch (GuzzleException $e) {
            switch ($e->getCode()) {
                case 403:
                    return view('errors.403')->withErrors(['general' => 'You don\'t have permissions to use this resource']);
                case 401:
                    if (str_contains($e->getMessage(), 'PGRST301')) {
                        return redirect('login');
                    }
                    break;
                case 400:
                    if (str_contains($e->getMessage(), '23502')) {
                        throw $e;
                    }
                    break;
                default:
                    throw $e;
            }
        }
    }

    public function get_app_menu()
    {
        $url = 'rest/v1/rpc/get_app_menu';

        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Add token to headers here
            ],
        ], 'POST');

        if ($response === null) {
            throw new \Exception("There is no valid response");
        } else {
            return json_decode((string)$response->getBody(), true);
        }
    }

    /**
     * @throws GuzzleException
     */
    public function read_rpc($plural)
    {
        $url = 'rest/v1/rpc/get_' . str_replace('-', '_', $plural);

        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Add token to headers here
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function read_edge($plural, $debug = false)
    {
        $url = 'functions/v1/' . str_replace('-', '_', $plural);

        // DEBUG: Show the URL and payload being sent to Supabase only if debugging is enabled
        if ($debug) {
            dump('=== SUPABASE READ REQUEST ===');
            dump([
                'url' => $this->baseUrl . '/' . $url,
                'method' => 'GET',
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
            ]);
        }

        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
            ]);

            if ($response === null) {
                throw new \Exception("No response received while reading $plural.");
            }

            $body = json_decode((string) $response->getBody(), true);
            
            // DEBUG: Show the response from Supabase only if debugging is enabled
            if ($debug) {
                dump('=== SUPABASE READ RESPONSE ===');
                dump([
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'response_raw' => (string) $response->getBody()
                ]);
            }
            
            if (isset($body['success']) && $body['success'] === true) {
                // DEBUG: Success case only if debugging is enabled
                if ($debug) {
                    dump('=== SUPABASE READ SUCCESS ===', [
                        'status' => $response->getStatusCode(),
                        'data'   => $body['data'] ?? null,
                    ]);
                }
                
                return $body['data'];
            }

            // Optionally log the error or throw an exception
            $error = $body['error']['message'] ?? 'Unknown error';
            $code = $body['error']['code'] ?? 'unknown_code';
            
            // DEBUG: Error case only if debugging is enabled
            if ($debug) {
                dd('=== SUPABASE READ ERROR ===', [
                    'error_message' => $error,
                    'error_code' => $code,
                    'full_error' => $body['error'] ?? null
                ]);
            }
            
            throw new \Exception("Request failed: $error (Code: $code)");

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // DEBUG: HTTP error only if debugging is enabled
            if ($debug) {
                dd('=== HTTP READ ERROR ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("HTTP error while reading $plural: " . $e->getMessage());
        } catch (\Exception $e) {
            // DEBUG: General exception only if debugging is enabled
            if ($debug) {
                dd('=== GENERAL READ EXCEPTION ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("Failed to read $plural: " . $e->getMessage());
        }
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function create_edge($data, $plural, $debug = false): array
    {
        $url = 'functions/v1/' . $plural;

        // Prepare the payload
        $payload = json_encode($data);
        
        // DEBUG: Show the URL and payload being sent to Supabase only if debugging is enabled
        if ($debug) {
            dump('=== SUPABASE REQUEST ===');
            dump([
                'url' => $this->baseUrl . '/' . $url,
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
                'payload_raw' => $data,
                'payload_json' => $payload
            ]);
        }

        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
                'body' => $payload,
            ], 'POST');

            if ($response === null) {
                throw new \Exception("No response received from the server.");
            }

            $body = json_decode((string) $response->getBody(), true);
            
            // DEBUG: Show the response from Supabase only if debugging is enabled
            if ($debug) {
                dump('=== SUPABASE RESPONSE ===');
                dump([
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'response_raw' => (string) $response->getBody()
                ]);
            }
            
            if (isset($body['success']) && $body['success'] === true) {
                // DEBUG: Success case only if debugging is enabled
                if ($debug) {
                    dump('=== SUPABASE SUCCESS ===', [
                        'status' => $response->getStatusCode(),
                        'data'   => $body['data'] ?? null,
                    ]);
                }
                
                return [
                    'status' => $response->getStatusCode(),
                    'data'   => $body['data'] ?? null,
                ];
            }

            // Handle error case
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            $errorCode    = $body['error']['code'] ?? 'unknown_code';
            
            // DEBUG: Error case only if debugging is enabled
            if ($debug) {
                dd('=== SUPABASE ERROR ===', [
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                    'full_error' => $body['error'] ?? null
                ]);
            }
            
            throw new \Exception("Error creating $plural: $errorMessage (Code: $errorCode)");

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // DEBUG: HTTP error only if debugging is enabled
            if ($debug) {
                dd('=== HTTP ERROR ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("HTTP request failed while creating $plural: " . $e->getMessage());
        } catch (\Exception $e) {
            // DEBUG: General exception only if debugging is enabled
            if ($debug) {
                dd('=== GENERAL EXCEPTION ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("Failed to create $plural: " . $e->getMessage());
        }

    }

    /**
     * @throws GuzzleException|\Exception
     */
    public function update_edge($id, array $data, $plural, $debug = false): array
    {
        $url = 'functions/v1/' . $plural . '/' . $id;
        $payload = json_encode($data);

        // DEBUG: Show the URL and payload being sent to Supabase only if debugging is enabled
        if ($debug) {
            dump('=== SUPABASE UPDATE REQUEST ===');
            dump([
                'url' => $this->baseUrl . '/' . $url,
                'method' => 'PUT',
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
                'id' => $id,
                'payload_raw' => $data,
                'payload_json' => $payload
            ]);
        }

        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
                'body' => $payload,
            ], 'PUT');

            if ($response === null) {
                throw new \Exception("No response received while updating $plural with ID $id.");
            }

            $body = json_decode((string) $response->getBody(), true);
            
            // DEBUG: Show the response from Supabase only if debugging is enabled
            if ($debug) {
                dump('=== SUPABASE UPDATE RESPONSE ===');
                dump([
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'response_raw' => (string) $response->getBody()
                ]);
            }

            if (isset($body['success']) && $body['success'] === true) {
                // DEBUG: Success case only if debugging is enabled
                if ($debug) {
                    dump('=== SUPABASE UPDATE SUCCESS ===', [
                        'status' => $response->getStatusCode(),
                        'data'   => $body['data'] ?? null,
                    ]);
                }
                
                return [
                    'status' => $response->getStatusCode(),
                    'data'   => $body['data'] ?? null,
                ];
            }

            // Handle error response
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            $errorCode    = $body['error']['code'] ?? 'unknown_code';
            
            // DEBUG: Error case only if debugging is enabled
            if ($debug) {
                dd('=== SUPABASE UPDATE ERROR ===', [
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                    'full_error' => $body['error'] ?? null
                ]);
            }
            
            throw new \Exception("Error updating $plural: $errorMessage (Code: $errorCode)");

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // DEBUG: HTTP error only if debugging is enabled
            if ($debug) {
                dd('=== HTTP UPDATE ERROR ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("HTTP error while updating $plural: " . $e->getMessage());
        } catch (\Exception $e) {
            // DEBUG: General exception only if debugging is enabled
            if ($debug) {
                dd('=== GENERAL UPDATE EXCEPTION ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("Failed to update $plural with ID $id: " . $e->getMessage());
        }
    }


    public function read($plural)
    {
        $url = 'rest/v1/' . $plural;
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Add token to headers here
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    public function delete($id, $plural): bool
    {
        $url = 'rest/v1/' . $plural . '?id=eq.' . $id;

        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                'Content-Type' => 'application/json',
            ],
        ], 'DELETE');

        return true;
    }

    /**
     * @throws \Exception
     */
    public function delete_edge($id, $plural, $debug = false): array
    {
        $url = 'functions/v1/' . $plural . '/' . $id;

        // DEBUG: Show the URL and payload being sent to Supabase only if debugging is enabled
        if ($debug) {
            dump('=== SUPABASE DELETE REQUEST ===');
            dump([
                'url' => $this->baseUrl . '/' . $url,
                'method' => 'DELETE',
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
                'id' => $id
            ]);
        }

        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                    'Content-Type'  => 'application/json',
                ],
            ], 'DELETE');

            if ($response === null) {
                throw new \Exception("No response received while deleting $plural with ID $id.");
            }

            $body = json_decode((string) $response->getBody(), true);
            
            // DEBUG: Show the response from Supabase only if debugging is enabled
            if ($debug) {
                dump('=== SUPABASE DELETE RESPONSE ===');
                dump([
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'response_raw' => (string) $response->getBody()
                ]);
            }

            if (isset($body['success']) && $body['success'] === true) {
                // DEBUG: Success case only if debugging is enabled
                if ($debug) {
                    dump('=== SUPABASE DELETE SUCCESS ===', [
                        'status' => $response->getStatusCode(),
                        'data'   => $body['data'] ?? null,
                    ]);
                }
                
                return [
                    'status' => $response->getStatusCode(),
                    'data'   => $body['data'] ?? null,
                ];
            }

            // Error response
            $errorMessage = $body['error']['message'] ?? 'Unknown error';
            $errorCode    = $body['error']['code'] ?? 'unknown_code';
            
            // DEBUG: Error case only if debugging is enabled
            if ($debug) {
                dd('=== SUPABASE DELETE ERROR ===', [
                    'error_message' => $errorMessage,
                    'error_code' => $errorCode,
                    'full_error' => $body['error'] ?? null
                ]);
            }
            
            throw new \Exception("Error deleting $plural: $errorMessage (Code: $errorCode)");

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // DEBUG: HTTP error only if debugging is enabled
            if ($debug) {
                dd('=== HTTP DELETE ERROR ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("HTTP error during deletion of $plural: " . $e->getMessage());
        } catch (\Exception $e) {
            // DEBUG: General exception only if debugging is enabled
            if ($debug) {
                dd('=== GENERAL DELETE EXCEPTION ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("Failed to delete $plural with ID $id: " . $e->getMessage());
        }
    }


    public function check_user_permission($table_name, $action_required = 'r')
    {
        $url = 'rest/v1/rpc/check_user_permission_with_claims';

        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Add token to headers here
                'Content-Type' => 'application/json',
            ],
            'json' => [
                "action_required" => $action_required,
                "table_name" => $table_name
            ]
        ], 'POST');

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Create a new category in the database.
     *
     * @param array $data Data to be sent in the request body.
     * @return array The response decoded into an associative array.
     * @throws GuzzleException If there's a problem with the HTTP request.
     * @throws \Exception
     */
    public function create(array $data, $plural)
    {
        $url = 'rest/v1/' . $plural;

        // Prepare the payload
        $payload = json_encode($data);
        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Use a session or another method to store/retrieve your JWT
                'Prefer' => 'return=minimal',
            ],
            'body' => $payload,
        ], 'POST');
        if ($response === null) {
            throw new \Exception("There is no valid response");
        }

        return [
            'status' => $response->getStatusCode(),
        ];
    }

    public function update($id, array $data, $plural)
    {
        $url = "rest/v1/$plural?id=eq.$id";

        // Prepare the payload
        $payload = json_encode($data);
        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Use a session or another method to store/retrieve your JWT
                'Prefer' => 'return=minimal',
            ],
            'body' => $payload,
        ], 'PATCH');

        return [
            'status' => $response->getStatusCode(),
        ];
    }

    /**
     * Upload an image to Supabase Storage.
     *
     * @param string $bucket The storage bucket name.
     * @param string $filename The destination filename including path in the bucket.
     * @param string $filePath The local file path of the image to be uploaded.
     * @return string Response status and additional information.
     * @throws GuzzleException If there's a problem with the HTTP request.
     */
    public function uploadImage(string $filename, string $filePath, string $bucket = 'images')
    {
        // Generate a time-based UUID for the filename
        $uuid = Uuid::uuid4()->toString(); // Generate a UUID
        $timestamp = time(); // Get current timestamp
        $extension = pathinfo($filename, PATHINFO_EXTENSION); // Extract the file extension from the original filename
        $newFilename = "{$uuid}-{$timestamp}.{$extension}"; // Construct the new filename using UUID and timestamp

        // Construct the URL for the Supabase Storage API with the newFilename
        $url = "storage/v1/object/{$bucket}/{$newFilename}";

        // Open the file in binary mode
        $fileData = fopen($filePath, 'rb');

        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Use a session or another method to store/retrieve your JWT
                'Content-Type' => 'application/octet-stream', // Ensure correct content type is set if required
            ],
            'body' => $fileData, // Attach the file data directly
        ], 'POST');
        // Close the file resource
        if (is_resource($fileData)) {
            fclose($fileData);
        }
        // Assuming you want to return the status code or some form of confirmation
        return $newFilename;
    }


    public function listObjects($search = null, string $bucket = 'images', $limit = 1)
    {
        // Construct the URL for the Supabase Storage API
        $url = "storage/v1/object/list/$bucket";


        $request = [
            "prefix" => "",
            "limit" => $limit,
            "offset" => 0,
            "sortBy" => [
                "column" => "name",
                "order" => "asc"
            ],
        ];

        if ($search !== null) {
            $request['search'] = $search;
        }

        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Use a session or another method to store/retrieve your JWT
                'Content-Type' => 'application/json',
                'accept' => '*/*'
            ],
            'json' => $request

        ], 'POST');

        // Assuming you want to return the status code or some form of confirmation
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Check if array is associative
     */
    private function is_assoc(array $arr): bool
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @throws GuzzleException
     */
    public function read_edge_filtered($plural, $filters = [], $debug = false)
    {
        $url = 'functions/v1/' . str_replace('-', '_', $plural);
        
                // Add query parameters for filtering
        if (!empty($filters)) {
            // Handle array-based filters like ["parent_id", "is", null]
            if (is_array($filters) && count($filters) === 3 ) {
            // if (is_array($filters) && count($filters) === 3 && !is_assoc($filters)) {
                $field = $filters[0];
                $operator = $filters[1];
                $value = $filters[2];
                
                if ($debug) {
                    dump('Processing filter array:', [
                        'field' => $field,
                        'operator' => $operator, 
                        'value' => $value,
                        'value_type' => gettype($value),
                        'is_null' => is_null($value)
                    ]);
                }
                
                // Convert to query parameter format
                $queryParams = [];
                if ($operator === 'is' && ($value === null || $value === 'null')) {
                    $queryParams[$field] = 'is.null';
                } elseif ($operator === 'eq') {
                    $queryParams[$field] = 'eq.' . $value;
                } elseif ($operator === 'neq') {
                    $queryParams[$field] = 'neq.' . $value;
                } else {
                    $queryParams[$field] = $operator . '.' . $value;
                }
                
                if ($debug) {
                    dump('Generated query params:', $queryParams);
                }
                
                $url .= '?' . http_build_query($queryParams);
            } else {
                // Handle associative array filters
                $queryParams = http_build_query($filters);
                $url .= '?' . $queryParams;
            }
        }
        
        if ($debug) {
            dump('Filters received:', $filters);
            dump('Final URL:', $url);
        }

        // DEBUG: Show the URL and payload being sent to Supabase only if debugging is enabled
        if ($debug) {
            dump('=== SUPABASE READ FILTERED REQUEST ===');
            dump([
                'url' => $this->baseUrl . '/' . $url,
                'method' => 'GET',
                'filters' => $filters,
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
            ]);
        }

        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
            ]);

            if ($response === null) {
                throw new \Exception("No response received while reading $plural with filters.");
            }

            $body = json_decode((string) $response->getBody(), true);
            
            // DEBUG: Show the response from Supabase only if debugging is enabled
            if ($debug) {
                dump('=== SUPABASE READ FILTERED RESPONSE ===');
                dump([
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $body,
                    'response_raw' => (string) $response->getBody()
                ]);
            }
            
            if (isset($body['success']) && $body['success'] === true) {
                // DEBUG: Success case only if debugging is enabled
                if ($debug) {
                    dump('=== SUPABASE READ FILTERED SUCCESS ===', [
                        'status' => $response->getStatusCode(),
                        'data'   => $body['data'] ?? null,
                    ]);
                }
                
                return $body['data'];
            }

            // Optionally log the error or throw an exception
            $error = $body['error']['message'] ?? 'Unknown error';
            $code = $body['error']['code'] ?? 'unknown_code';
            
            // DEBUG: Error case only if debugging is enabled
            if ($debug) {
                dd('=== SUPABASE READ FILTERED ERROR ===', [
                    'error_message' => $error,
                    'error_code' => $code,
                    'full_error' => $body['error'] ?? null
                ]);
            }
            
            throw new \Exception("Request failed: $error (Code: $code)");

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // DEBUG: HTTP error only if debugging is enabled
            if ($debug) {
                dd('=== HTTP READ FILTERED ERROR ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("HTTP error while reading $plural: " . $e->getMessage());
        } catch (\Exception $e) {
            // DEBUG: General exception only if debugging is enabled
            if ($debug) {
                dd('=== GENERAL READ FILTERED EXCEPTION ===', [
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode(),
                    'exception_class' => get_class($e)
                ]);
            }
            
            throw new \Exception("Failed to read $plural: " . $e->getMessage());
        }
    }

    /**
     * Upload file to Supabase Storage
     */
    public function uploadToStorage($bucket, $path, $fileContent, $contentType = 'application/octet-stream', $options = [])
    {
        $url = "storage/v1/object/{$bucket}/{$path}";
        
        $headers = [
            'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            'Content-Type' => $contentType,
        ];
        
        // Add upsert option if specified
        if (isset($options['upsert']) && $options['upsert']) {
            $headers['x-upsert'] = 'true';
        }
        
        try {
            $response = $this->processRequest($url, [
                'headers' => $headers,
                'body' => $fileContent,
            ], 'POST');
            
            if ($response === null) {
                throw new \Exception("No response received while uploading to storage.");
            }
            
            $body = json_decode((string) $response->getBody(), true);
            
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 201) {
                return [
                    'success' => true,
                    'data' => $body,
                    'public_url' => $this->getStoragePublicUrl($bucket, $path)
                ];
            }
            
            return [
                'success' => false,
                'error' => $body
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }
    
    /**
     * Delete file from Supabase Storage
     */
    public function deleteFromStorage($bucket, $path)
    {
        $url = "storage/v1/object/{$bucket}/{$path}";
        
        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
            ], 'DELETE');
            
            if ($response === null) {
                throw new \Exception("No response received while deleting from storage.");
            }
            
            $body = json_decode((string) $response->getBody(), true);
            
            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $body
                ];
            }
            
            return [
                'success' => false,
                'error' => $body
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }
    
    /**
     * Get public URL for file in Supabase Storage
     */
    public function getStoragePublicUrl($bucket, $path)
    {
        return $this->baseUrl . "/storage/v1/object/public/{$bucket}/{$path}";
    }
    
    /**
     * List files in Supabase Storage bucket
     */
    public function listStorageFiles($bucket, $path = '', $options = [])
    {
        $url = "storage/v1/object/list/{$bucket}";
        
        $params = [];
        if ($path) {
            $params['prefix'] = $path;
        }
        if (isset($options['limit'])) {
            $params['limit'] = $options['limit'];
        }
        if (isset($options['offset'])) {
            $params['offset'] = $options['offset'];
        }
        
        try {
            $response = $this->processRequest($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . Session::get('jwt_token'),
                ],
                'json' => $params
            ], 'POST');
            
            if ($response === null) {
                throw new \Exception("No response received while listing storage files.");
            }
            
            $body = json_decode((string) $response->getBody(), true);
            
            if ($response->getStatusCode() === 200) {
                return [
                    'success' => true,
                    'data' => $body
                ];
            }
            
            return [
                'success' => false,
                'error' => $body
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }

}
