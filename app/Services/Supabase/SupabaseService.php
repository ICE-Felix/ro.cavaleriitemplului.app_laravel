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

    public function read_edge($plural)
    {
        $url = 'functions/v1/get_' . str_replace('-', '_', $plural);

        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'), // Add token to headers here
            ],
        ]);
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function create_edge($data, $singular)
    {
        $url = 'functions/v1/create_' . $singular;

        // Prepare the payload
        $payload = json_encode($data);

        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            ],
            'body' => $payload,
        ], 'POST');

        if ($response === null) {
            throw new \Exception("There is no valid response");
        }
        try {
            return [
                'status' => $response->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            throw new \Exception("There was a problem creating $singular");
        }
    }

    public function update_edge($id, array $data, $singular)
    {
        $url = 'functions/v1/update_' . $singular;

        // Prepare the payload
        $data['id'] = $id;
        $payload = json_encode($data);


        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            ],
            'body' => $payload,
        ], 'POST');

        if ($response === null) {
            throw new \Exception("There is no valid response");
        }
        try {
            return [
                'status' => $response->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            throw new \Exception("There was a problem updating $singular");
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
    public function delete($id, $plural)
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

    public function delete_edge($id, $singular)
    {
        $url = 'functions/v1/delete_' . $singular;

        // Prepare the payload
        $payload = json_encode(
            [
                "id" => $id
            ]
        );
        // Make the POST request
        $response = $this->processRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . Session::get('jwt_token'),
            ],
            'body' => $payload,
        ], 'POST');

        if ($response === null) {
            throw new \Exception("There is no valid response");
        }
        try {
            return [
                'status' => $response->getStatusCode(),
            ];
        } catch (GuzzleException $e) {
            throw new \Exception("There was a problem deleting $plural");
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

}
