<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
    protected string $baseUrl;
    protected string $apiVersion;
    protected string $phoneNumberId;
    protected string $accessToken;
    protected int $timeout;
    protected int $retryLimit;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiVersion = null,
        ?string $phoneNumberId = null,
        ?string $accessToken = null,
        ?int $timeout = null,
        ?int $retryLimit = null
    ) {
        $this->baseUrl = rtrim($baseUrl ?? config('whatsapp.base_url', 'https://graph.facebook.com'), '/');
        $this->apiVersion = trim($apiVersion ?? config('whatsapp.api_version', 'v21.0'), '/');
        $this->phoneNumberId = $phoneNumberId ?? (string) config('whatsapp.phone_number_id', '');
        $this->accessToken = $accessToken ?? (string) config('whatsapp.access_token', '');
        $this->timeout = $timeout ?? (int) config('whatsapp.timeout', 15);
        $this->retryLimit = $retryLimit ?? (int) config('whatsapp.retry_limit', 3);
    }

    /**
     * Build base URL for phone number operations.
     */
    protected function messagesEndpoint(): string
    {
        return "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";
    }

    /**
     * Send a free-form text message (valid within 24-hour customer service window).
     */
    public function sendText(string $to, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->cleanRecipient($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send an approved WhatsApp template message (used outside 24-hour service window).
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'en_US', array $parameters = []): array
    {
        $components = [];

        if (!empty($parameters)) {
            $formattedParams = array_map(function ($param) {
                return [
                    'type' => 'text',
                    'text' => (string) $param,
                ];
            }, $parameters);

            $components[] = [
                'type' => 'body',
                'parameters' => array_values($formattedParams),
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->cleanRecipient($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
                'components' => $components,
            ],
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send an image message using uploaded media ID or accessible URL.
     */
    public function sendImage(string $to, string $mediaIdOrUrl, ?string $caption = null, bool $isMediaId = true): array
    {
        $mediaData = [];
        if ($isMediaId) {
            $mediaData['id'] = $mediaIdOrUrl;
        } else {
            $mediaData['link'] = $mediaIdOrUrl;
        }

        if (!empty($caption)) {
            $mediaData['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->cleanRecipient($to),
            'type' => 'image',
            'image' => $mediaData,
        ];

        return $this->postMessage($payload);
    }

    /**
     * Send a document message (PDF, etc.) using media ID or URL.
     */
    public function sendDocument(string $to, string $mediaIdOrUrl, ?string $caption = null, ?string $filename = null, bool $isMediaId = true): array
    {
        $mediaData = [];
        if ($isMediaId) {
            $mediaData['id'] = $mediaIdOrUrl;
        } else {
            $mediaData['link'] = $mediaIdOrUrl;
        }

        if (!empty($caption)) {
            $mediaData['caption'] = $caption;
        }

        if (!empty($filename)) {
            $mediaData['filename'] = $filename;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->cleanRecipient($to),
            'type' => 'document',
            'document' => $mediaData,
        ];

        return $this->postMessage($payload);
    }

    /**
     * Upload local file to WhatsApp Cloud API to obtain media ID.
     */
    public function uploadMedia(string $filePath, string $mimeType): ?string
    {
        if (!file_exists($filePath)) {
            Log::error("Media upload failed: file not found at {$filePath}");
            return null;
        }

        $endpoint = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}/media";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout($this->timeout * 2)
                ->attach('file', file_get_contents($filePath), basename($filePath), ['Content-Type' => $mimeType])
                ->post($endpoint, [
                    'messaging_product' => 'whatsapp',
                    'type' => $mimeType,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['id'] ?? null;
            }

            Log::error('WhatsApp media upload failed', [
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('Exception during WhatsApp media upload: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieve WhatsApp media metadata and download its binary contents.
     */
    public function downloadMedia(string $mediaId): ?array
    {
        $metaEndpoint = "{$this->baseUrl}/{$this->apiVersion}/{$mediaId}";

        try {
            // Step 1: Query media metadata
            $metaResponse = Http::withToken($this->accessToken)
                ->timeout($this->timeout)
                ->get($metaEndpoint);

            if (!$metaResponse->successful()) {
                Log::error('WhatsApp media metadata fetch failed', [
                    'media_id' => $mediaId,
                    'status' => $metaResponse->status(),
                ]);
                return null;
            }

            $metaData = $metaResponse->json();
            $downloadUrl = $metaData['url'] ?? null;
            $mimeType = $metaData['mime_type'] ?? 'application/octet-stream';
            $fileSize = $metaData['file_size'] ?? 0;

            if (empty($downloadUrl)) {
                Log::error('WhatsApp media URL missing in metadata response', ['media_id' => $mediaId]);
                return null;
            }

            // Step 2: Download media binary using authenticated request
            $binaryResponse = Http::withToken($this->accessToken)
                ->timeout($this->timeout * 2)
                ->get($downloadUrl);

            if (!$binaryResponse->successful()) {
                Log::error('WhatsApp media binary download failed', [
                    'media_id' => $mediaId,
                    'status' => $binaryResponse->status(),
                ]);
                return null;
            }

            return [
                'contents' => $binaryResponse->body(),
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
            ];
        } catch (\Throwable $e) {
            Log::error('Exception during WhatsApp media download: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Perform actual authorized connectivity test to Meta WhatsApp Cloud API.
     */
    public function testConnectivity(): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            return [
                'success' => false,
                'message' => 'WhatsApp Cloud API Phone Number ID or Access Token is missing in configuration.',
                'status_code' => 0,
                'details' => null,
            ];
        }

        $endpoint = "{$this->baseUrl}/{$this->apiVersion}/{$this->phoneNumberId}";

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout($this->timeout)
                ->get($endpoint, [
                    'fields' => 'id,verified_name,display_phone_number,quality_rating,code_verification_status',
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Successfully connected to Meta WhatsApp Cloud API.',
                    'status_code' => $response->status(),
                    'details' => $response->json(),
                ];
            }

            $errorData = $response->json('error') ?? [];
            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Failed to connect to Meta WhatsApp Cloud API.',
                'status_code' => $response->status(),
                'details' => [
                    'error_code' => $errorData['code'] ?? null,
                    'error_subcode' => $errorData['error_subcode'] ?? null,
                    'type' => $errorData['type'] ?? null,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Network error connecting to WhatsApp Cloud API: ' . $e->getMessage(),
                'status_code' => 500,
                'details' => null,
            ];
        }
    }

    /**
     * Dispatch HTTP POST message payload with bounded retries.
     */
    protected function postMessage(array $payload): array
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            return [
                'success' => false,
                'wamid' => null,
                'status' => 'failed',
                'error_code' => 'CONFIG_MISSING',
                'error_message' => 'WhatsApp Phone Number ID or Access Token is not configured.',
                'raw' => null,
            ];
        }

        $endpoint = $this->messagesEndpoint();
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryLimit) {
            $attempts++;
            try {
                $response = Http::withToken($this->accessToken)
                    ->timeout($this->timeout)
                    ->post($endpoint, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    $wamid = $json['messages'][0]['id'] ?? null;

                    return [
                        'success' => true,
                        'wamid' => $wamid,
                        'status' => 'accepted',
                        'error_code' => null,
                        'error_message' => null,
                        'raw' => $json,
                    ];
                }

                $errorData = $response->json('error') ?? [];
                $errorCode = (string) ($errorData['code'] ?? $response->status());
                $errorMessage = $errorData['message'] ?? 'Unknown WhatsApp API error';

                // If non-retryable 4xx client error (e.g. invalid recipient or template)
                if ($response->status() >= 400 && $response->status() < 500 && $response->status() !== 429) {
                    Log::warning('WhatsApp API client rejection', [
                        'code' => $errorCode,
                        'message' => $errorMessage,
                    ]);

                    return [
                        'success' => false,
                        'wamid' => null,
                        'status' => 'failed',
                        'error_code' => $errorCode,
                        'error_message' => $errorMessage,
                        'raw' => $response->json(),
                    ];
                }

                // Wait before retrying server errors or rate limits
                usleep(200000 * $attempts);
            } catch (\Throwable $e) {
                $lastException = $e;
                usleep(200000 * $attempts);
            }
        }

        $msg = $lastException ? $lastException->getMessage() : 'Retry limit exhausted';
        Log::error('WhatsApp API send failed after retries: ' . $msg);

        return [
            'success' => false,
            'wamid' => null,
            'status' => 'failed',
            'error_code' => 'RETRY_EXHAUSTED',
            'error_message' => $msg,
            'raw' => null,
        ];
    }

    /**
     * Clean phone number for recipient string (digits only, no +).
     */
    protected function cleanRecipient(string $number): string
    {
        return ltrim(preg_replace('/[^\d]/', '', $number), '+');
    }
}
