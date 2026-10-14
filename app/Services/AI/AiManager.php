<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use App\Services\AI\Contracts\AiServiceInterface;

class AiManager implements AiServiceInterface
{
    public function __construct(
        protected NvidiaNimService $nvidiaService
    ) {}

    public function getActiveProvider(): string
    {
        return 'nvidia_nim';
    }

    public function setActiveProvider(string $provider, ?string $model = null): void
    {
        SystemSetting::set('ai_active_provider', 'nvidia_nim');

        if ($model) {
            SystemSetting::set('ai_active_model', $model);
            SystemSetting::set('ai_nvidia_model', $model);
        }
    }

    public function getProviderService(?string $provider = null): AiServiceInterface
    {
        return $this->nvidiaService;
    }

    public function isConfigured(): bool
    {
        return $this->nvidiaService->isConfigured();
    }

    public function getProviderName(): string
    {
        return $this->nvidiaService->getProviderName();
    }

    public function getModelName(): string
    {
        return $this->nvidiaService->getModelName();
    }

    public function parseReminderPrompt(
        string $userInput,
        string $userTimezone = 'UTC',
        ?int $userId = null,
        array $conversationHistory = []
    ): ReminderInterpretationResult {
        return $this->nvidiaService->parseReminderPrompt(
            $userInput,
            $userTimezone,
            $userId,
            $conversationHistory
        );
    }

    public function testConnection(?string $model = null, ?string $provider = null): array
    {
        return $this->nvidiaService->testConnection($model);
    }

    public function getAvailableProviders(): array
    {
        return [
            'nvidia_nim' => [
                'name' => 'NVIDIA NIM (OpenAI-compatible)',
                'description' => 'Enterprise OpenAI-compatible inference with ultra-reliable open-weights models.',
                'models' => config('ai.nvidia.available_models', [
                    'meta/llama-3.2-11b-vision-instruct' => 'Llama 3.2 11B Vision Instruct (Ultra-Fast 1.6s)',
                    'openai/gpt-oss-20b' => 'GPT-OSS-20B (NVIDIA NIM)',
                ]),
                'current_model' => SystemSetting::get('ai_nvidia_model', config('nvidia.model', 'openai/gpt-oss-20b')),
                'is_configured' => $this->nvidiaService->isConfigured(),
                'api_key_masked' => $this->maskKey(SystemSetting::get('ai_nvidia_api_key', config('nvidia.api_key', ''))),
            ],
        ];
    }

    protected function maskKey(string $key): string
    {
        if (empty($key)) {
            return 'Not Configured';
        }
        if (strlen($key) <= 8) {
            return '••••••••';
        }
        return substr($key, 0, 6) . '••••••••' . substr($key, -4);
    }
}
