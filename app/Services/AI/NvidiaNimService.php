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

class NvidiaNimService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct()
    {
        $this->apiKey = (string) config('nvidia.api_key', '');
        $this->baseUrl = rtrim((string) config('nvidia.base_url', 'https://integrate.api.nvidia.com/v1'), '/');
        $this->model = (string) config('nvidia.model', 'openai/gpt-oss-20b');
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

    /**
     * Parse natural language reminder input in Bengali, Banglish, or English.
     *
     * @param string $userInput Natural language text from user
     * @param string $userTimezone User IANA timezone (e.g. 'Asia/Dhaka', 'UTC')
     * @param int|null $userId User ID for logging metadata
     * @return ReminderInterpretationResult
     */
    public function parseReminderPrompt(string $userInput, string $userTimezone = 'UTC', ?int $userId = null): ReminderInterpretationResult
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
            $payload = [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userInput,
                    ],
                ],
                'temperature' => 0.2,
                'top_p' => 0.9,
                'max_tokens' => 1024,
                'stream' => false,
            ];

            $sleepMs = app()->environment('testing') ? 0 : 500;

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
You manage natural-language reminders, personal memory files, and schedules in Bengali, Banglish, and English.
You must parse the user's intent and output ONLY a strictly valid JSON object.

CONTEXT INFORMATION:
- Current Date: {$currentDate}
- Current Time: {$currentTime}
- Day of Week: {$dayOfWeek}
- User Timezone: {$userTimezone}

SUPPORTED INTENTS:
1. "create_reminder":
   - "আগামীকাল সকাল ৯টায় ক্লায়েন্টকে কল করার কথা মনে করিয়ে দিও।"
   - "Kal shokal 8 tay meeting ache, remind korio."
   - "Remind me to pay the internet bill on the 5th of every month."
2. "list_reminders":
   - "আজকের সব reminder দেখাও।"
   - "amar shob reminder dekhao"
   - "Show all my reminders for today."
3. "reschedule_reminder":
   - "আগামীকালের reminder-টা রাত ৮টায় করে দাও।"
   - "Kal er reminder ta rat 8 tay shift koro."
   - "Reschedule tomorrow's reminder to 8 PM."
4. "cancel_reminder":
   - "ওই reminder-টা বাতিল করো।"
   - "oi reminder ta cancel koro"
   - "Cancel that reminder."
5. "complete_reminder":
   - "meeting reminder-টা complete করে দাও।"
   - "Done with that reminder."
6. "retrieve_file":
   - "গত সপ্তাহে পাঠানো X89 earbuds-এর ছবিটা আমাকে দাও।"
   - "amar PDF invoice-ta khuje dao"
   - "Find my passport PDF / photo."
7. "general_chat":
   - Any conversation, greeting, question, chitchat, banter, asking how you are, asking for help, or talking naturally in Bengali, Banglish, or English.
8. "unknown":
   - Unparseable noise or gibberish.

DATE & TIME RESOLUTION RULES:
- "today", "আজ", "ajke" -> {$currentDate}
- "tomorrow", "আগামীকাল", "kal" -> mathematically resolve relative to {$currentDate}
- "day after tomorrow", "পরশু", "porshu" -> resolve relative to {$currentDate}
- "5th of every month" -> recurrence: "monthly", date: next 5th day of month
- "9 AM", "সকাল ৯টা" -> "09:00:00"
- "8 PM", "রাত ৮টা", "rat 8 ta" -> "20:00:00"
- Date format: "YYYY-MM-DD" or null.
- Time format: "HH:MM:SS" (24-hour) or null.

CRITICAL JSON SCHEMA:
You MUST respond with ONLY a single JSON object with these exact keys:
{
  "intent": "create_reminder" | "list_reminders" | "reschedule_reminder" | "cancel_reminder" | "complete_reminder" | "retrieve_file" | "general_chat" | "unknown",
  "conversational_reply": "Your intelligent, natural, human-like response in Bengali, Banglish, or English answering whatever the user said, greeting them, chatting, or confirming an action naturally without robotic templates.",
  "title": "Clean concise reminder title or null",
  "description": null,
  "date": "YYYY-MM-DD" | null,
  "time": "HH:MM:SS" | null,
  "priority": "low" | "medium" | "high" | "urgent",
  "recurrence": "none" | "daily" | "weekly" | "monthly" | "yearly",
  "search_query": "file topic / product name to search in vault or null",
  "file_type": "image" | "pdf" | "document" | null,
  "target_reminder_identifier": "which reminder was referenced (e.g. 'tomorrow', 'meeting', 'previous') or null",
  "detected_language": "bengali" | "banglish" | "english" | "other",
  "has_missing_info": false,
  "missing_fields": [],
  "clarification_question": null,
  "confidence": 0.95
}

MISSING INFORMATION & CLARIFICATION:
- If creating a reminder without explicit date or time, set "has_missing_info": true, add to "missing_fields", and generate a polite "clarification_question" in the user's detected language.
- For general_chat, provide a warm, empathetic, intelligent conversational reply in "conversational_reply" matching the user's language and tone.
- DO NOT invent dates or times. If ambiguous, ask for clarification.
- DO NOT wrap in markdown fences. Output raw JSON only.
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

        // For create_reminder, title is required unless missing info is flagged
        if ($decoded['intent'] === 'create_reminder' && empty($decoded['title']) && empty($decoded['has_missing_info'])) {
            return null;
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
