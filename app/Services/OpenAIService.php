<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAIService
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key');
        $this->baseUrl = 'https://api.openai.com/v1/';
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Generate an image using OpenAI DALL-E
     *
     * @param string $prompt The image generation prompt
     * @param string $size Image size (256x256, 512x512, 1024x1024)
     * @param int $n Number of images to generate
     * @return array
     * @throws GuzzleException
     */
    public function generateImage(string $prompt, string $size = '512x512', int $n = 1): array
    {
        try {
            $response = $this->client->post('images/generations', [
                'json' => [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => $n,
                    'size' => $size,
                    'response_format' => 'url'
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            
            if (isset($result['data']) && !empty($result['data'])) {
                return [
                    'success' => true,
                    'images' => $result['data']
                ];
            }

            return [
                'success' => false,
                'error' => 'No images generated'
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download and save generated image locally
     *
     * @param string $imageUrl The URL of the generated image
     * @param string $filename Desired filename
     * @return array
     */
    public function downloadAndSaveImage(string $imageUrl, string $filename): array
    {
        try {
            $imageClient = new Client();
            $response = $imageClient->get($imageUrl);
            
            $imageContent = $response->getBody()->getContents();
            $path = 'ai-generated/' . $filename;
            
            Storage::disk('public')->put($path, $imageContent);
            
            return [
                'success' => true,
                'path' => $path,
                'url' => Storage::disk('public')->url($path)
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Image download error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to download image: ' . $e->getMessage()
            ];
        }
    }
} 