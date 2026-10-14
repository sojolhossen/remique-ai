<?php

namespace App\Console\Commands;

use App\Services\AI\NvidiaNimService;
use Illuminate\Console\Command;

class TestAiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:test-ai {--prompt=Remind me tomorrow at 9 AM to call the client}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NVIDIA NIM API connectivity and reminder interpretation safely';

    /**
     * Execute the console command.
     */
    public function handle(NvidiaNimService $aiService): int
    {
        $this->info('========================================');
        $this->info('  Remi AI - NVIDIA NIM Connectivity Test');
        $this->info('========================================');

        $this->line('Base URL: ' . config('nvidia.base_url'));
        $this->line('Model:    ' . config('nvidia.model'));

        if (! $aiService->isConfigured()) {
            $this->warn('NVIDIA_NIM_API_KEY is not configured in .env.');
            $this->line('To enable live AI parsing, set your rotated NVIDIA_NIM_API_KEY in .env');
            return Command::INVALID;
        }

        $this->info('API Key:  [CONFIGURED - REDACTED]');
        $prompt = (string) $this->option('prompt');
        $this->line("Test Prompt: \"{$prompt}\"");
        $this->line('Contacting NVIDIA NIM endpoint...');

        $result = $aiService->parseReminderPrompt($prompt, 'UTC');

        if ($result->success) {
            $this->info('SUCCESS! Received valid structured response from NVIDIA NIM.');
            $this->table(
                ['Field', 'Value'],
                [
                    ['Title', $result->title],
                    ['Date', $result->date],
                    ['Time', $result->time],
                    ['Priority', $result->priority],
                    ['Detected Language', $result->detectedLanguage],
                    ['Missing Info', $result->hasMissingInfo ? 'Yes' : 'No'],
                    ['Clarification Question', $result->clarificationQuestion ?? 'N/A'],
                    ['Latency (ms)', $result->durationMs],
                    ['Total Tokens', $result->totalTokens ?? 'N/A'],
                ]
            );
            return Command::SUCCESS;
        }

        $this->error('AI Request Failed: ' . $result->errorMessage);
        $this->line('Error Category: ' . $result->errorCategory);
        $this->line('Latency: ' . $result->durationMs . ' ms');

        return Command::FAILURE;
    }
}
