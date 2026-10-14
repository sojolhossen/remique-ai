<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected string $botToken;
    protected string $apiBaseUrl;
    protected int $timeout;

    public function __construct(
        ?string $botToken = null,
        ?string $apiBaseUrl = null,
        ?int $timeout = null
    ) {
        $this->botToken = (string) ($botToken ?? config('telegram.bot_token', ''));
        $this->apiBaseUrl = rtrim($apiBaseUrl ?? config('telegram.api_base_url', 'https://api.telegram.org'), '/');
        $this->timeout = $timeout ?? (int) config('telegram.timeout', 15);
    }

    protected function endpoint(string $method): string
    {
        return "{$this->apiBaseUrl}/bot{$this->botToken}/{$method}";
    }

    public function isConfigured(): bool
    {
        return ! empty($this->botToken);
    }

    /**
     * Send text message to a chat.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): array
    {
        if (! $this->isConfigured()) {
            Log::warning("TelegramBotService: BOT_TOKEN not configured. Skipping sendMessage.");
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);

        try {
            $response = Http::timeout($this->timeout)->post($this->endpoint('sendMessage'), $payload);
            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Send photo to a chat.
     */
    public function sendPhoto(string $chatId, string $photoPath, string $caption = ''): array
    {
        if (! $this->isConfigured() || ! file_exists($photoPath)) {
            return ['ok' => false, 'description' => 'Unconfigured or file missing'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->attach('photo', file_get_contents($photoPath), basename($photoPath))
                ->post($this->endpoint('sendPhoto'), [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ]);

            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            Log::error('Telegram sendPhoto failed: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Send document to a chat.
     */
    public function sendDocument(string $chatId, string $documentPath, string $caption = ''): array
    {
        if (! $this->isConfigured() || ! file_exists($documentPath)) {
            return ['ok' => false, 'description' => 'Unconfigured or file missing'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->attach('document', file_get_contents($documentPath), basename($documentPath))
                ->post($this->endpoint('sendDocument'), [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ]);

            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            Log::error('Telegram sendDocument failed: ' . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get file path info from file_id.
     */
    public function getFile(string $fileId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)->get($this->endpoint('getFile'), [
                'file_id' => $fileId,
            ]);

            $json = $response->json();
            return ($json['ok'] ?? false) ? $json['result'] : null;
        } catch (\Throwable $e) {
            Log::error('Telegram getFile failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Download binary contents of a Telegram file.
     */
    public function downloadFile(string $filePath): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $url = "{$this->apiBaseUrl}/file/bot{$this->botToken}/{$filePath}";
            $response = Http::timeout($this->timeout * 2)->get($url);
            return $response->successful() ? $response->body() : null;
        } catch (\Throwable $e) {
            Log::error('Telegram downloadFile failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Set incoming webhook URL on Telegram.
     */
    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = ['url' => $url];
        if ($secretToken) {
            $payload['secret_token'] = $secretToken;
        }

        try {
            $response = Http::timeout($this->timeout)->post($this->endpoint('setWebhook'), $payload);
            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Delete active webhook (required before polling).
     */
    public function deleteWebhook(): array
    {
        try {
            $response = Http::timeout($this->timeout)->post($this->endpoint('deleteWebhook'));
            return $response->json() ?? ['ok' => false];
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Fetch pending updates via long-polling (useful for local development).
     */
    public function getUpdates(int $offset = 0, int $limit = 20, int $timeout = 10): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::timeout($timeout + 5)->get($this->endpoint('getUpdates'), [
                'offset' => $offset,
                'limit' => $limit,
                'timeout' => $timeout,
            ]);

            $json = $response->json();
            return ($json['ok'] ?? false) ? ($json['result'] ?? []) : [];
        } catch (\Throwable $e) {
            Log::error('Telegram getUpdates failed: ' . $e->getMessage());
            return [];
        }
    }
}
