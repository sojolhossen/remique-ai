<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiInteractionLog;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\AI\NvidiaNimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function __construct(
        protected NvidiaNimService $aiService
    ) {}

    public function index(): View
    {
        $currentModel = SystemSetting::get('ai_nvidia_model', config('nvidia.model', 'openai/gpt-oss-20b'));
        $apiKey = SystemSetting::get('ai_nvidia_api_key', config('nvidia.api_key', ''));
        $maskedKey = ! empty($apiKey)
            ? substr($apiKey, 0, 6) . '••••••••' . substr($apiKey, -4)
            : 'Not Configured';

        $config = [
            'provider' => 'NVIDIA NIM (OpenAI-compatible)',
            'base_url' => config('nvidia.base_url', 'https://integrate.api.nvidia.com/v1'),
            'model' => $currentModel,
            'timeout' => config('nvidia.timeout', 30),
            'max_retries' => config('nvidia.max_retries', 2),
            'api_key_masked' => $maskedKey,
            'is_configured' => $this->aiService->isConfigured(),
            'available_models' => config('ai.nvidia.available_models', [
                'meta/llama-3.2-11b-vision-instruct' => 'Llama 3.2 11B Vision Instruct (Ultra-Fast 1.6s)',
                'openai/gpt-oss-20b' => 'GPT-OSS-20B (NVIDIA NIM)',
            ]),
        ];

        // Diagnostic analytics from logs
        $totalLogs = AiInteractionLog::count();
        $successfulLogs = AiInteractionLog::where('status', 'success')->count();
        $failedLogs = AiInteractionLog::where('status', 'failed')->count();
        $avgDuration = (int) round(AiInteractionLog::avg('duration_ms') ?? 0);
        $totalTokens = (int) AiInteractionLog::sum('total_tokens');

        $errorBreakdown = AiInteractionLog::whereNotNull('error_category')
            ->selectRaw('error_category, count(*) as count')
            ->groupBy('error_category')
            ->pluck('count', 'error_category')
            ->all();

        $recentLogs = AiInteractionLog::orderByDesc('created_at')->limit(15)->get();

        return view('admin.ai.index', compact(
            'config',
            'totalLogs',
            'successfulLogs',
            'failedLogs',
            'avgDuration',
            'totalTokens',
            'errorBreakdown',
            'recentLogs'
        ));
    }

    /**
     * Update NVIDIA model preferences.
     */
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nvidia_model' => 'required|string|max:100',
            'nvidia_api_key' => 'nullable|string|max:255',
        ]);

        SystemSetting::set('ai_active_provider', 'nvidia_nim');
        SystemSetting::set('ai_nvidia_model', $validated['nvidia_model']);
        SystemSetting::set('ai_active_model', $validated['nvidia_model']);

        if (! empty($validated['nvidia_api_key'])) {
            SystemSetting::set('ai_nvidia_api_key', trim($validated['nvidia_api_key']));
        }

        AuditLog::record(
            'ai.settings_updated',
            'NvidiaNimService',
            "NVIDIA NIM model updated to {$validated['nvidia_model']}",
            [
                'active_provider' => 'nvidia_nim',
                'active_model' => $validated['nvidia_model'],
            ],
            auth()->id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "AI Engine updated! Active model: {$validated['nvidia_model']}",
                'active_model' => $validated['nvidia_model'],
            ]);
        }

        return redirect()->route('admin.ai.index')->with('status', "AI Engine updated! Active model: {$validated['nvidia_model']}");
    }

    /**
     * Test live health / connectivity for NVIDIA NIM model.
     */
    public function testModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => 'nullable|string|max:100',
        ]);

        $result = $this->aiService->testConnection($validated['model'] ?? null);

        return response()->json($result);
    }

    /**
     * Run interactive intent extraction in playground.
     */
    public function testPlayground(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:1000',
            'timezone' => 'nullable|string|timezone',
        ]);

        $tz = $validated['timezone'] ?? auth()->user()->timezone ?? 'Asia/Dhaka';
        $result = $this->aiService->parseReminderPrompt($validated['prompt'], $tz, auth()->id());

        AuditLog::record(
            'ai.playground_tested',
            'NvidiaNimService',
            "Super Admin tested in AI playground using NVIDIA NIM ({$this->aiService->getModelName()})",
            [
                'provider' => 'nvidia_nim',
                'model' => $this->aiService->getModelName(),
                'intent' => $result->intent,
                'success' => $result->success,
                'duration_ms' => $result->durationMs,
            ],
            auth()->id()
        );

        $responseArray = $result->toArray();
        $responseArray['provider'] = 'nvidia_nim';
        $responseArray['model'] = $this->aiService->getModelName();

        return response()->json($responseArray);
    }
}
