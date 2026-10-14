<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\ReminderInterpretationResult;

interface AiServiceInterface
{
    /**
     * Check if the provider is properly configured with an API key.
     */
    public function isConfigured(): bool;

    /**
     * Parse natural language reminder/intent prompt into structured result.
     *
     * @param string $userInput
     * @param string $userTimezone
     * @param int|null $userId
     * @param array $conversationHistory
     * @return ReminderInterpretationResult
     */
    public function parseReminderPrompt(
        string $userInput,
        string $userTimezone = 'UTC',
        ?int $userId = null,
        array $conversationHistory = []
    ): ReminderInterpretationResult;

    /**
     * Test live connectivity and return latency and status.
     *
     * @param string|null $model Optional specific model to test
     * @return array{success: bool, latency_ms: int, message: string, model: string, provider: string}
     */
    public function testConnection(?string $model = null): array;

    /**
     * Get provider identifier (e.g. 'gemini', 'nvidia_nim').
     */
    public function getProviderName(): string;

    /**
     * Get currently active or default model name.
     */
    public function getModelName(): string;
}
