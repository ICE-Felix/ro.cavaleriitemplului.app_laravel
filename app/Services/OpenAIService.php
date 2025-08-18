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
        
        // Add validation for API key
        if (empty($this->apiKey)) {
            throw new \Exception('OpenAI API key is not configured. Please set the OPENAI_KEY environment variable.');
        }
        
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
     * @param string $size Image size (512x512, 1024x1024, 1792x1024, 1024x1792)
     * @param int $n Number of images to generate
     * @return array
     * @throws GuzzleException
     */
    public function generateImage(string $prompt, string $size = '1024x1024', int $n = 1): array
    {
        try {
            // Map legacy sizes to DALL-E 3 supported sizes
            $sizeMapping = [
                '256x256' => '1024x1024',
                '512x512' => '1024x1024',
                '1024x1024' => '1024x1024',
                '1792x1024' => '1792x1024',
                '1024x1792' => '1024x1792'
            ];
            
            $mappedSize = $sizeMapping[$size] ?? '1024x1024';
            
            $response = $this->client->post('images/generations', [
                'json' => [
                    'model' => 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1, // DALL-E 3 only supports n=1
                    'size' => $mappedSize,
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
                'url' => url('storage/' . $path)
            ];
            
        } catch (GuzzleException $e) {
            Log::error('Image download error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to download image: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate text using OpenAI GPT
     *
     * @param string $prompt The text generation prompt
     * @param string $model The model to use (gpt-4, gpt-3.5-turbo, etc.)
     * @param int $maxTokens Maximum tokens to generate
     * @param float $temperature Controls randomness (0.0 to 2.0)
     * @return array
     * @throws GuzzleException
     */
    public function generateText(string $prompt, string $model = 'gpt-3.5-turbo', int $maxTokens = 500, float $temperature = 0.7): array
    {
        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that generates high-quality, engaging content. Write clear, informative, and well-structured text that is appropriate for the given context.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => $temperature,
                    'top_p' => 1,
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            
            if (isset($result['choices']) && !empty($result['choices'])) {
                $generatedText = $result['choices'][0]['message']['content'] ?? '';
                
                return [
                    'success' => true,
                    'text' => trim($generatedText),
                    'usage' => $result['usage'] ?? null
                ];
            }

            return [
                'success' => false,
                'error' => 'No text generated'
            ];

        } catch (GuzzleException $e) {
            Log::error('OpenAI Text Generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate text: ' . $e->getMessage()
            ];
        }
    }
} 