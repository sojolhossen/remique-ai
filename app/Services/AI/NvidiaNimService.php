<?php

namespace App\Services\AI;

use App\Models\AiInteractionLog;
use App\Services\AI\Exceptions\NvidiaNimException;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

use App\Models\SystemSetting;
use App\Services\AI\Contracts\AiServiceInterface;

class NvidiaNimService implements AiServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct(
        ?string $apiKey = null,
        ?string $model = null
    ) {
        $this->apiKey = (string) ($apiKey ?? SystemSetting::get('ai_nvidia_api_key', config('nvidia.api_key', '')));
        $this->baseUrl = rtrim((string) config('nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->model = (string) ($model ?? SystemSetting::get('ai_nvidia_model', config('nvidia.model', 'openai/gpt-oss-20b')));
        $this->timeout = (int) config('nvidia.timeout', 30);
        $this->maxRetries = (int) config('nvidia.max_retries', 2);
    }

    /**
     * Check if the API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && $this->apiKey !== 'YOUR_ROTATED_API_KEY';
    }

    public function getProviderName(): string
    {
        return 'nvidia_nim';
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    /**
     * Test connectivity to NVIDIA NIM with a lightweight ping.
     */
    public function testConnection(?string $model = null): array
    {
        $targetModel = $model ?: $this->model;
        $startTime = hrtime(true);

        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'latency_ms' => 0,
                'message' => 'NVIDIA NIM API key is not configured.',
                'model' => $targetModel,
                'provider' => 'nvidia_nim',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout(12)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $targetModel,
                'messages' => [
                    ['role' => 'user', 'content' => 'Ping'],
                ],
                'max_tokens' => 5,
            ]);

            $latencyMs = (int) round((hrtime(true) - $startTime) / 1e6);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'latency_ms' => $latencyMs,
                    'message' => "Model {$targetModel} responded successfully ({$latencyMs} ms).",
                    'model' => $targetModel,
                    'provider' => 'nvidia_nim',
                ];
            }

            $error = $response->json()['error']['message'] ?? 'HTTP ' . $response->status();

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Failed ({$response->status()}): {$error}",
                'model' => $targetModel,
                'provider' => 'nvidia_nim',
            ];
        } catch (Throwable $e) {
            $latencyMs = (int) round((hrtime(true) - $startTime) / 1e6);
            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'model' => $targetModel,
                'provider' => 'nvidia_nim',
            ];
        }
    }

    /**
     * Parse natural language reminder input in Bengali, Banglish, or English.
     *
     * @param string $userInput Natural language text from user
     * @param string $userTimezone User IANA timezone (e.g. 'Asia/Dhaka', 'UTC')
     * @param int|null $userId User ID for logging metadata
     * @return ReminderInterpretationResult
     */
    public function parseReminderPrompt(string $userInput, string $userTimezone = 'UTC', ?int $userId = null, array $conversationHistory = []): ReminderInterpretationResult
    {
        $startTime = hrtime(true);

        if (! $this->isConfigured()) {
            return $this->buildFailureResult(
                'NVIDIA NIM API key is not configured. Please set NVIDIA_NIM_API_KEY in your environment or use manual reminder creation.',
                'missing_api_key',
                $userId,
                $startTime
            );
        }

        $nowInUserTz = Carbon::now($userTimezone);
        $systemPrompt = $this->buildSystemPrompt($nowInUserTz, $userTimezone);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
            ];

            foreach ($conversationHistory as $turn) {
                if (! empty($turn['role']) && isset($turn['content']) && $turn['content'] !== '') {
                    $messages[] = [
                        'role' => $turn['role'] === 'assistant' ? 'assistant' : 'user',
                        'content' => (string) $turn['content'],
                    ];
                }
            }

            $messages[] = [
                'role' => 'user',
                'content' => $userInput,
            ];

            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => 0.1,
                'top_p' => 0.9,
                'max_tokens' => 350,
                'stream' => false,
            ];

            $sleepMs = app()->environment('testing') ? 0 : 250;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->retry($this->maxRetries, $sleepMs, function (Throwable $exception, $request) {
                // Only retry transient failures: rate limits (429), server errors (503, 504), connection timeouts
                if ($exception instanceof RequestException) {
                    $status = $exception->response?->status();
                    return in_array($status, [429, 502, 503, 504]);
                }
                return $exception instanceof ConnectionException;
            })
            ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->failed()) {
                $status = $response->status();
                $category = match (true) {
                    $status === 401 || $status === 403 => 'auth_error',
                    $status === 429 => 'rate_limit',
                    $status >= 500 => 'provider_error',
                    default => 'client_error',
                };

                $safeMessage = match ($category) {
                    'auth_error' => 'NVIDIA NIM authentication failed. Check your API credentials.',
                    'rate_limit' => 'NVIDIA NIM rate limit reached. Please try again shortly or use manual reminder creation.',
                    'provider_error' => 'NVIDIA NIM service is temporarily unavailable. Please use manual reminder creation.',
                    default => 'NVIDIA NIM returned an unexpected response. Please try manual entry.',
                };

                return $this->buildFailureResult($safeMessage, $category, $userId, $startTime);
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $tokens = $data['usage'] ?? [];

            if (empty($content)) {
                return $this->buildFailureResult(
                    'AI provider returned an empty response. Please use manual reminder creation.',
                    'empty_response',
                    $userId,
                    $startTime,
                    $tokens
                );
            }

            $parsedData = $this->extractAndValidateJson($content);
            if (! $parsedData) {
                return $this->buildFailureResult(
                    'Unable to parse structured reminder from AI output. Please use manual reminder creation.',
                    'malformed_json',
                    $userId,
                    $startTime,
                    $tokens
                );
            }

            $durationMs = (int) round((hrtime(true) - $startTime) / 1e6);
            $detectedLang = $parsedData['detected_language'] ?? 'unknown';

            // Log privacy-conscious diagnostic metadata (no raw prompt/text)
            $this->logInteraction(
                $userId,
                'success',
                $durationMs,
                $tokens['prompt_tokens'] ?? null,
                $tokens['completion_tokens'] ?? null,
                $tokens['total_tokens'] ?? null,
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
                confidence: (float) ($parsedData['confidence'] ?? 1.0),
                errorMessage: null,
                errorCategory: null,
                durationMs: $durationMs,
                totalTokens: $tokens['total_tokens'] ?? null,
                searchQuery: $parsedData['search_query'] ?? null,
                fileType: $parsedData['file_type'] ?? null,
                targetReminderIdentifier: $parsedData['target_reminder_identifier'] ?? null,
                conversationalReply: $parsedData['conversational_reply'] ?? null
            );

        } catch (RequestException $e) {
            $status = $e->response?->status();
            $category = match (true) {
                $status === 401 || $status === 403 => 'auth_error',
                $status === 429 => 'rate_limit',
                $status >= 500 => 'provider_error',
                default => 'client_error',
            };

            $safeMessage = match ($category) {
                'auth_error' => 'NVIDIA NIM authentication failed. Check your API credentials.',
                'rate_limit' => 'NVIDIA NIM rate limit reached. Please try again shortly or use manual reminder creation.',
                'provider_error' => 'NVIDIA NIM service is temporarily unavailable. Please use manual reminder creation.',
                default => 'NVIDIA NIM returned an unexpected response. Please try manual entry.',
            };

            return $this->buildFailureResult($safeMessage, $category, $userId, $startTime);
        } catch (ConnectionException $e) {
            return $this->buildFailureResult(
                'Connection to NVIDIA NIM timed out. Please check your internet connection or use manual creation.',
                'timeout',
                $userId,
                $startTime
            );
        } catch (Throwable $e) {
            Log::warning('NVIDIA NIM Service exception occurred', [
                'error' => $e->getMessage(),
                'model' => $this->model,
            ]);

            return $this->buildFailureResult(
                'An error occurred while contacting AI service. Please use manual creation.',
                'exception',
                $userId,
                $startTime
            );
        }
    }

    /**
     * Build the structured system prompt instructing the model to output strict JSON.
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

        // Remove markdown code fences if LLM wrapped it
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $clean, $matches)) {
            $clean = trim($matches[1]);
        }

        // Try direct decode
        $decoded = json_decode($clean, true);

        // If direct decode failed, search for first '{' to last '}'
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

        // For create_reminder, if title is missing, assign a sensible default title
        if ($decoded['intent'] === 'create_reminder' && empty($decoded['title'])) {
            $lang = $decoded['detected_language'] ?? 'bengali';
            $decoded['title'] = ($lang === 'bengali' || $lang === 'banglish') ? 'রিমাইন্ডার' : 'Reminder';
        }

        // Validate date format if provided
        if (! empty($decoded['date'])) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $decoded['date'])) {
                $decoded['date'] = null;
                $decoded['has_missing_info'] = true;
                $decoded['missing_fields'][] = 'date';
            }
        }

        // Validate time format if provided
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
                'provider' => 'nvidia_nim',
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
            // Fail silently on logging to not block user interaction
            Log::error('Failed to log AI interaction: ' . $e->getMessage());
        }
    }
}
