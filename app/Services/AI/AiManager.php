<?php

namespace App\Services\AI;

use App\Models\SystemSetting;
use App\Services\AI\Contracts\AiServiceInterface;
use InvalidArgumentException;

class AiManager implements AiServiceInterface
{
    protected array $drivers = [];

    public function __construct(
        protected GeminiService $geminiService,
        protected NvidiaNimService $nvidiaService
    ) {
        $this->drivers = [
            'gemini' => $this->geminiService,
            'nvidia_nim' => $this->nvidiaService,
        ];
    }

    /**
     * Get the active AI provider key ('gemini' or 'nvidia_nim').
     */
    public function getActiveProvider(): string
    {
        $provider = SystemSetting::get('ai_active_provider', config('ai.default_provider', 'gemini'));
        return array_key_exists($provider, $this->drivers) ? $provider : 'gemini';
    }

    /**
     * Set the active AI provider and optionally active model.
     */
    public function setActiveProvider(string $provider, ?string $model = null): void
    {
        if (! array_key_exists($provider, $this->drivers)) {
            throw new InvalidArgumentException("Unsupported AI provider: {$provider}");
        }

        SystemSetting::set('ai_active_provider', $provider);

        if ($model) {
            SystemSetting::set('ai_active_model', $model);
            if ($provider === 'gemini') {
                SystemSetting::set('ai_gemini_model', $model);
            } elseif ($provider === 'nvidia_nim') {
                SystemSetting::set('ai_nvidia_model', $model);
            }
        }
    }

    /**
     * Get the driver for the specified provider or the active provider.
     */
    public function getProviderService(?string $provider = null): AiServiceInterface
    {
        $target = $provider ?: $this->getActiveProvider();
        return $this->drivers[$target] ?? $this->geminiService;
    }

    public function isConfigured(): bool
    {
        return $this->getProviderService()->isConfigured();
    }

    public function getProviderName(): string
    {
        return $this->getProviderService()->getProviderName();
    }

    public function getModelName(): string
    {
        return $this->getProviderService()->getModelName();
    }

    /**
     * Parse prompt using active AI engine.
     */
    public function parseReminderPrompt(
        string $userInput,
        string $userTimezone = 'UTC',
        ?int $userId = null,
        array $conversationHistory = []
    ): ReminderInterpretationResult {
        return $this->getProviderService()->parseReminderPrompt(
            $userInput,
            $userTimezone,
            $userId,
            $conversationHistory
        );
    }

    /**
     * Test connection for a given or active provider and model.
     */
    public function testConnection(?string $model = null, ?string $provider = null): array
    {
        $service = $provider ? ($this->drivers[$provider] ?? $this->getProviderService()) : $this->getProviderService();
        return $service->testConnection($model);
    }

    /**
     * Return all available providers and their metadata.
     */
    public function getAvailableProviders(): array
    {
        return [
            'gemini' => [
                'name' => 'Google Gemini',
                'description' => 'Next-gen multimodal AI with native JSON schema support and ultra-low latency (<1.5s).',
                'models' => config('ai.gemini.available_models', [
                    'gemini-2.5-flash' => 'Gemini 2.5 Flash (Ultra Fast & Recommended)',
                    'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Lightweight)',
                    'gemini-flash-latest' => 'Gemini Flash Latest',
                ]),
                'current_model' => SystemSetting::get('ai_gemini_model', config('ai.gemini.model', 'gemini-2.5-flash')),
                'is_configured' => $this->geminiService->isConfigured(),
                'api_key_masked' => $this->maskKey(SystemSetting::get('ai_gemini_api_key', config('ai.gemini.api_key', ''))),
            ],
            'nvidia_nim' => [
                'name' => 'NVIDIA NIM (OpenAI-compatible)',
                'description' => 'Enterprise OpenAI-compatible inference with open-weights models.',
                'models' => config('ai.nvidia.available_models', [
                    'openai/gpt-oss-20b' => 'GPT-OSS-20B (NVIDIA NIM)',
                    'meta/llama-3.1-70b-instruct' => 'Llama 3.1 70B Instruct',
                    'meta/llama-3.1-8b-instruct' => 'Llama 3.1 8B Instruct',
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
