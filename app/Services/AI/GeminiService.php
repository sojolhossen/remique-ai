<?php

namespace App\Services\AI;

use App\Models\AiInteractionLog;
use App\Models\SystemSetting;
use App\Services\AI\Contracts\AiServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null
    ) {
        $this->apiKey = (string) ($apiKey ?? SystemSetting::get('ai_gemini_api_key', config('ai.gemini.api_key', '')));
        $this->model = (string) ($model ?? SystemSetting::get('ai_gemini_model', config('ai.gemini.model', 'gemini-2.5-flash')));
        $this->timeout = (int) config('ai.gemini.timeout', 30);
        $this->maxRetries = (int) config('ai.gemini.max_retries', 2);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! str_contains($this->apiKey, 'YOUR_');
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    /**
     * Parse natural language reminder input in Bengali, Banglish, or English.
     */
    public function parseReminderPrompt(
        string $userInput,
        string $userTimezone = 'UTC',
        ?int $userId = null,
        array $conversationHistory = []
    ): ReminderInterpretationResult {
        $startTime = hrtime(true);

        if (! $this->isConfigured()) {
            return $this->buildFailureResult(
                'Gemini API key is not configured. Please set GEMINI_API_KEY in admin panel or environment.',
                'missing_api_key',
                $userId,
                $startTime
            );
        }

        $nowInUserTz = Carbon::now($userTimezone);
        $systemPrompt = $this->buildSystemPrompt($nowInUserTz, $userTimezone);

        try {
            $contents = [];

            // Add previous conversation turns
            foreach ($conversationHistory as $turn) {
                if (! empty($turn['role']) && isset($turn['content']) && $turn['content'] !== '') {
                    $contents[] = [
                        'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                        'parts' => [
                            ['text' => (string) $turn['content']],
                        ],
                    ];
                }
            }

            // Add user input
            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $userInput],
                ],
            ];

            $payload = [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.1,
                    'maxOutputTokens' => 450,
                ],
            ];

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->retry($this->maxRetries, 250, function (Throwable $exception) {
                if ($exception instanceof RequestException) {
                    $status = $exception->response?->status();
                    return in_array($status, [429, 502, 503, 504]);
                }
                return $exception instanceof ConnectionException;
            })
            ->post($url, $payload);

            if ($response->failed()) {
                $status = $response->status();
                $category = match (true) {
                    $status === 400 => 'invalid_argument',
                    $status === 401 || $status === 403 => 'auth_error',
                    $status === 404 => 'model_not_found',
                    $status === 429 => 'rate_limit',
                    $status >= 500 => 'provider_error',
                    default => 'client_error',
                };

                $errorBody = $response->json();
                $apiMessage = $errorBody['error']['message'] ?? 'Gemini request failed.';

                return $this->buildFailureResult(
                    "Gemini error ({$status}): {$apiMessage}",
                    $category,
                    $userId,
                    $startTime
                );
            }

            $data = $response->json();
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            $usage = $data['usageMetadata'] ?? [];

            $promptTokens = $usage['promptTokenCount'] ?? null;
            $completionTokens = $usage['candidatesTokenCount'] ?? null;
            $totalTokens = $usage['totalTokenCount'] ?? null;

            if (empty($content)) {
                return $this->buildFailureResult(
                    'Gemini returned an empty response. Please try again.',
                    'empty_response',
                    $userId,
                    $startTime,
                    ['total_tokens' => $totalTokens]
                );
            }

            $parsedData = $this->extractAndValidateJson($content);
            if (! $parsedData) {
                return $this->buildFailureResult(
                    'Unable to parse structured reminder from Gemini response.',
                    'malformed_json',
                    $userId,
                    $startTime,
                    ['total_tokens' => $totalTokens]
                );
            }

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $detectedLang = $parsedData['detected_language'] ?? 'unknown';

            $this->logInteraction(
                $userId,
                'success',
                $durationMs,
                $promptTokens,
                $completionTokens,
                $totalTokens,
                null,
                $detectedLang
            );

            return new ReminderInterpretationResult(
                success: true,
                intent: $parsedData['intent'] ?? 'create_reminder',
                title: $parsedData['title'] ?? null,
                description: $parsedData['description'] ?? null,
                date: $parsedData['date'] ?? null,
                time: $parsedData['time'] ?? null,
                priority: in_array($parsedData['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $parsedData['priority'] : 'medium',
                recurrence: in_array($parsedData['recurrence'] ?? '', ['none', 'daily', 'weekly', 'monthly', 'yearly']) ? $parsedData['recurrence'] : 'none',
                detectedLanguage: $detectedLang,
                hasMissingInfo: (bool) ($parsedData['has_missing_info'] ?? false),
                missingFields: (array) ($parsedData['missing_fields'] ?? []),
                clarificationQuestion: $parsedData['clarification_question'] ?? null,
                confidence: (float) ($parsedData['confidence'] ?? 0.95),
                errorMessage: null,
                errorCategory: null,
                durationMs: $durationMs,
                totalTokens: $totalTokens,
                searchQuery: $parsedData['search_query'] ?? null,
                fileType: $parsedData['file_type'] ?? null,
                targetReminderIdentifier: $parsedData['target_reminder_identifier'] ?? null,
                conversationalReply: $parsedData['conversational_reply'] ?? null
            );

        } catch (RequestException $e) {
            $status = $e->response?->status();
            $category = match (true) {
                $status === 401 || $status === 403 => 'auth_error',
                $status === 404 => 'model_not_found',
                $status === 429 => 'rate_limit',
                $status >= 500 => 'provider_error',
                default => 'client_error',
            };

            return $this->buildFailureResult(
                "Gemini connection error: " . $e->getMessage(),
                $category,
                $userId,
                $startTime
            );
        } catch (ConnectionException $e) {
            return $this->buildFailureResult(
                'Connection to Google Gemini timed out.',
                'timeout',
                $userId,
                $startTime
            );
        } catch (Throwable $e) {
            Log::warning('Gemini Service exception occurred', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            return $this->buildFailureResult(
                'An unexpected error occurred while communicating with Gemini.',
                'exception',
                $userId,
                $startTime
            );
        }
    }

    /**
     * Test connectivity to Gemini with a lightweight ping.
     */
    public function testConnection(?string $model = null): array
    {
        $targetModel = $model ?: $this->model;
        $startTime = hrtime(true);

        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'latency_ms' => 0,
                'message' => 'Gemini API key is not configured.',
                'model' => $targetModel,
                'provider' => 'gemini',
            ];
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$targetModel}:generateContent?key={$this->apiKey}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->timeout(12)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => 'Ping']]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 5,
                ],
            ]);

            $latencyMs = (int) round((hrtime(true) - $startTime) / 1e6);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'latency_ms' => $latencyMs,
                    'message' => "Model {$targetModel} responded successfully ({$latencyMs} ms).",
                    'model' => $targetModel,
                    'provider' => 'gemini',
                ];
            }

            $error = $response->json()['error']['message'] ?? 'HTTP ' . $response->status();

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Failed ({$response->status()}): {$error}",
                'model' => $targetModel,
                'provider' => 'gemini',
            ];
        } catch (Throwable $e) {
            $latencyMs = (int) round((hrtime(true) - $startTime) / 1e6);
            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'model' => $targetModel,
                'provider' => 'gemini',
            ];
        }
    }

    /**
     * Build the structured system prompt instructing Gemini to output strict JSON.
     */
    protected function buildSystemPrompt(Carbon $nowInUserTz, string $userTimezone): string
    {
        $currentDate = $nowInUserTz->toDateString();
        $currentTime = $nowInUserTz->toTimeString();
        $dayOfWeek = $nowInUserTz->format('l');

        return <<<PROMPT
You are Remi AI, the intelligent conversational assistant for Remique.
Output ONLY a strictly valid JSON object. No preamble, no markdown fences, no internal reasoning.

CONTEXT:
Date: {$currentDate} | Time: {$currentTime} | Day: {$dayOfWeek} | Timezone: {$userTimezone}

INTENTS:
"create_reminder" | "list_reminders" | "reschedule_reminder" | "cancel_reminder" | "complete_reminder" | "retrieve_file" | "general_chat" | "unknown"

DATE RULES:
"today"/"ajke" -> {$currentDate}
"tomorrow"/"kal" -> resolve +1 day relative to {$currentDate}
"day after tomorrow"/"porshu" -> resolve +2 days relative to {$currentDate}
"9 AM"/"সকাল ৯টা" -> "09:00:00" | "8 PM"/"রাত ৮টা" -> "20:00:00"

JSON SCHEMA:
{
  "intent": "create_reminder"|"list_reminders"|"reschedule_reminder"|"cancel_reminder"|"complete_reminder"|"retrieve_file"|"general_chat"|"unknown",
  "conversational_reply": "Warm, natural response matching user's language and script.",
  "title": "Concise reminder title or null",
  "description": null,
  "date": "YYYY-MM-DD"|null,
  "time": "HH:MM:SS"|null,
  "priority": "low"|"medium"|"high"|"urgent",
  "recurrence": "none"|"daily"|"weekly"|"monthly"|"yearly",
  "search_query": "file keyword to search or null",
  "file_type": "image"|"pdf"|"document"|null,
  "target_reminder_identifier": null,
  "detected_language": "bengali"|"banglish"|"english",
  "has_missing_info": false,
  "missing_fields": [],
  "clarification_question": null,
  "confidence": 0.95
}

MANDATORY LANGUAGE & SCRIPT MIRRORING:
1. BANGLA: User writes in Bengali script (e.g. কেমন আছো, রিমাইন্ডার দাও) -> reply strictly in Bengali script.
2. BANGLISH: User writes in Banglish / Latin script (e.g. kemon aso, ki khobor, kal reminder dio) -> reply strictly in Banglish (English letters). NEVER reply in Bengali script when user writes in Banglish!
3. ENGLISH: User writes in English -> reply in English.

MULTI-TURN MEMORY:
Use recent messages in history to remember names, context, and follow-ups.
PROMPT;
    }

    /**
     * Clean and parse JSON from LLM output.
     */
    protected function extractAndValidateJson(string $rawContent): ?array
    {
        $clean = trim($rawContent);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $clean, $matches)) {
            $clean = trim($matches[1]);
        }

        $decoded = json_decode($clean, true);

        if (! is_array($decoded)) {
            $firstBrace = strpos($clean, '{');
            $lastBrace = strrpos($clean, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $substring = substr($clean, $firstBrace, $lastBrace - $firstBrace + 1);
                $decoded = json_decode($substring, true);
            }
        }

        if (! is_array($decoded) || empty($decoded['intent'])) {
            return null;
        }

        if ($decoded['intent'] === 'create_reminder' && empty($decoded['title'])) {
            $lang = $decoded['detected_language'] ?? 'bengali';
            $decoded['title'] = ($lang === 'bengali' || $lang === 'banglish') ? 'রিমাইন্ডার' : 'Reminder';
        }

        if (! empty($decoded['date'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $decoded['date'])) {
                $decoded['date'] = null;
                $decoded['has_missing_info'] = true;
                $decoded['missing_fields'][] = 'date';
            }
        }

        if (! empty($decoded['time'])) {
            if (! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $decoded['time'])) {
                $decoded['time'] = null;
                $decoded['has_missing_info'] = true;
                $decoded['missing_fields'][] = 'time';
            } elseif (strlen($decoded['time']) === 5) {
                $decoded['time'] .= ':00';
            }
        }

        return $decoded;
    }

    /**
     * Build failure result and log interaction.
     */
    protected function buildFailureResult(string $message, string $category, ?int $userId, int $startTime, array $tokens = []): ReminderInterpretationResult
    {
        $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);

        $this->logInteraction(
            $userId,
            'failed',
            $durationMs,
            $tokens['prompt_tokens'] ?? null,
            $tokens['completion_tokens'] ?? null,
            $tokens['total_tokens'] ?? null,
            $category,
            null
        );

        return new ReminderInterpretationResult(
            success: false,
            intent: 'unknown',
            title: null,
            description: null,
            date: null,
            time: null,
            priority: 'medium',
            recurrence: 'none',
            detectedLanguage: 'unknown',
            hasMissingInfo: true,
            missingFields: ['all'],
            clarificationQuestion: null,
            confidence: 0.0,
            errorMessage: $message,
            errorCategory: $category,
            durationMs: $durationMs,
            totalTokens: $tokens['total_tokens'] ?? null
        );
    }

    /**
     * Record privacy-conscious diagnostic metadata into ai_interaction_logs.
     */
    protected function logInteraction(
        ?int $userId,
        string $status,
        int $durationMs,
        ?int $promptTokens,
        ?int $completionTokens,
        ?int $totalTokens,
        ?string $errorCategory,
        ?string $detectedLanguage
    ): void {
        try {
            AiInteractionLog::create([
                'user_id' => $userId,
                'provider' => 'gemini',
                'model' => $this->model,
                'status' => $status,
                'duration_ms' => $durationMs,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'error_category' => $errorCategory,
                'detected_language' => $detectedLanguage,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to log Gemini AI interaction: ' . $e->getMessage());
        }
    }
}
