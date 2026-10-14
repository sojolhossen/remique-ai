<?php

namespace App\Console\Commands;

use App\Services\AI\NvidiaNimService;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VerifySystemHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:health-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive system health, security, and integration diagnostics';

    /**
     * Execute the console command.
     */
    public function handle(NvidiaNimService $aiService, WhatsAppCloudApiService $whatsAppService): int
    {
        $this->info("=========================================================");
        $this->info("   REMIQUE PRODUCTION READINESS & HEALTH DIAGNOSTICS    ");
        $this->info("=========================================================\n");

        $results = [];
        $hasFailures = false;

        // 1. Database Connectivity & Latency
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            $results[] = [
                'Component' => 'Database Connectivity',
                'Status' => '<info>PASS</info>',
                'Details' => "Connected to " . DB::connection()->getDatabaseName() . " ({$latency}ms)",
            ];
        } catch (\Throwable $e) {
            $hasFailures = true;
            $results[] = [
                'Component' => 'Database Connectivity',
                'Status' => '<error>FAIL</error>',
                'Details' => $e->getMessage(),
            ];
        }

        // 2. Storage Disk Writeability
        try {
            $testFilename = storage_path('app/health_test_' . Str::random(8) . '.tmp');
            File::put($testFilename, 'health_check');
            $readContent = File::get($testFilename);
            File::delete($testFilename);

            if ($readContent === 'health_check') {
                $results[] = [
                    'Component' => 'Storage Writeability',
                    'Status' => '<info>PASS</info>',
                    'Details' => 'storage/app is readable and writable',
                ];
            } else {
                throw new \Exception('Read content mismatch');
            }
        } catch (\Throwable $e) {
            $hasFailures = true;
            $results[] = [
                'Component' => 'Storage Writeability',
                'Status' => '<error>FAIL</error>',
                'Details' => 'Storage write test failed: ' . $e->getMessage(),
            ];
        }

        // 3. Cache Subsystem
        try {
            $cacheKey = 'remi_health_' . Str::random(6);
            Cache::put($cacheKey, 'ok', 60);
            $cachedVal = Cache::get($cacheKey);
            Cache::forget($cacheKey);

            if ($cachedVal === 'ok') {
                $results[] = [
                    'Component' => 'Cache System',
                    'Status' => '<info>PASS</info>',
                    'Details' => 'Cache store (' . config('cache.default') . ') operational',
                ];
            } else {
                throw new \Exception('Cache get failed');
            }
        } catch (\Throwable $e) {
            $hasFailures = true;
            $results[] = [
                'Component' => 'Cache System',
                'Status' => '<error>FAIL</error>',
                'Details' => $e->getMessage(),
            ];
        }

        // 4. Security Flags
        $appKey = config('app.key');
        $debug = config('app.debug');
        $env = config('app.env');

        if (! empty($appKey)) {
            $results[] = [
                'Component' => 'Application Key',
                'Status' => '<info>PASS</info>',
                'Details' => 'APP_KEY is set and valid',
            ];
        } else {
            $hasFailures = true;
            $results[] = [
                'Component' => 'Application Key',
                'Status' => '<error>FAIL</error>',
                'Details' => 'APP_KEY is missing! Run php artisan key:generate',
            ];
        }

        $results[] = [
            'Component' => 'Debug Mode',
            'Status' => $debug ? '<comment>WARN</comment>' : '<info>PASS</info>',
            'Details' => $debug ? 'APP_DEBUG=true (Disable in production!)' : 'APP_DEBUG=false (Production safe)',
        ];

        // 5. NVIDIA NIM AI Configuration
        $aiConfigured = $aiService->isConfigured();
        $aiModel = config('services.nvidia_nim.model', 'openai/gpt-oss-20b');
        $results[] = [
            'Component' => 'NVIDIA NIM AI Service',
            'Status' => $aiConfigured ? '<info>PASS</info>' : '<comment>WARN</comment>',
            'Details' => $aiConfigured ? "Configured with model: {$aiModel}" : 'NVIDIA_NIM_API_KEY missing in .env (rule-based fallback active)',
        ];

        // 6. WhatsApp Cloud API Configuration
        $waConfigured = $whatsAppService->isConfigured();
        $phoneId = config('whatsapp.phone_number_id');
        $results[] = [
            'Component' => 'WhatsApp Cloud API',
            'Status' => $waConfigured ? '<info>PASS</info>' : '<comment>WARN</comment>',
            'Details' => $waConfigured ? "Configured (Phone ID: {$phoneId})" : 'WHATSAPP_ACCESS_TOKEN or PHONE_NUMBER_ID not set',
        ];

        // 7. Webhook Signature Secret
        $appSecret = config('whatsapp.app_secret');
        $results[] = [
            'Component' => 'Meta App Secret (HMAC)',
            'Status' => ! empty($appSecret) ? '<info>PASS</info>' : '<comment>WARN</comment>',
            'Details' => ! empty($appSecret) ? 'App secret configured for HMAC-SHA256 signature verification' : 'META_APP_SECRET missing; webhooks cannot be cryptographically signed',
        ];

        $this->table(['System Component', 'Status', 'Diagnostic Details'], $results);

        if ($hasFailures) {
            $this->error("\n❌ System health check detected critical failures.");
            return Command::FAILURE;
        }

        $this->info("\n✓ Remique system health check completed without critical failures.");
        return Command::SUCCESS;
    }
}
