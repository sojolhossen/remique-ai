<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiInteractionLog;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\AI\AiManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function __construct(
        protected AiManager $aiManager
    ) {}

    public function index(): View
    {
        $activeProvider = $this->aiManager->getActiveProvider();
        $providers = $this->aiManager->getAvailableProviders();

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
            'activeProvider',
            'providers',
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
     * Update active provider and model preferences.
     */
    public function updateSettings(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'active_provider' => 'required|in:gemini,nvidia_nim',
            'gemini_model' => 'nullable|string|max:100',
            'nvidia_model' => 'nullable|string|max:100',
            'gemini_api_key' => 'nullable|string|max:255',
            'nvidia_api_key' => 'nullable|string|max:255',
        ]);

        $provider = $validated['active_provider'];
        SystemSetting::set('ai_active_provider', $provider);

        if (! empty($validated['gemini_model'])) {
            SystemSetting::set('ai_gemini_model', $validated['gemini_model']);
        }

        if (! empty($validated['nvidia_model'])) {
            SystemSetting::set('ai_nvidia_model', $validated['nvidia_model']);
        }

        if (! empty($validated['gemini_api_key'])) {
            SystemSetting::set('ai_gemini_api_key', trim($validated['gemini_api_key']));
        }

        if (! empty($validated['nvidia_api_key'])) {
            SystemSetting::set('ai_nvidia_api_key', trim($validated['nvidia_api_key']));
        }

        $activeModel = $provider === 'gemini'
            ? SystemSetting::get('ai_gemini_model', config('ai.gemini.model', 'gemini-2.5-flash'))
            : SystemSetting::get('ai_nvidia_model', config('nvidia.model', 'openai/gpt-oss-20b'));

        SystemSetting::set('ai_active_model', $activeModel);

        AuditLog::record(
            'ai.settings_updated',
            'AiManager',
            "Active AI provider switched to {$provider} ({$activeModel})",
            [
                'active_provider' => $provider,
                'active_model' => $activeModel,
            ],
            auth()->id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "AI Engine updated! Active provider: {$provider} ({$activeModel})",
                'active_provider' => $provider,
                'active_model' => $activeModel,
            ]);
        }

        return redirect()->route('admin.ai.index')->with('status', "AI Engine updated! Active provider: {$provider} ({$activeModel})");
    }

    /**
     * Test live health / connectivity for any model or provider.
     */
    public function testModel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|in:gemini,nvidia_nim',
            'model' => 'nullable|string|max:100',
        ]);

        $result = $this->aiManager->testConnection(
            $validated['model'] ?? null,
            $validated['provider']
        );

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
            'provider' => 'nullable|in:gemini,nvidia_nim',
        ]);

        $tz = $validated['timezone'] ?? auth()->user()->timezone ?? 'Asia/Dhaka';
        $provider = $validated['provider'] ?? $this->aiManager->getActiveProvider();
        $service = $this->aiManager->getProviderService($provider);

        $result = $service->parseReminderPrompt($validated['prompt'], $tz, auth()->id());

        AuditLog::record(
            'ai.playground_tested',
            $service->getProviderName(),
            "Super Admin tested in AI playground using {$service->getProviderName()} ({$service->getModelName()})",
            [
                'provider' => $service->getProviderName(),
                'model' => $service->getModelName(),
                'intent' => $result->intent,
                'success' => $result->success,
                'duration_ms' => $result->durationMs,
            ],
            auth()->id()
        );

        $responseArray = $result->toArray();
        $responseArray['provider'] = $service->getProviderName();
        $responseArray['model'] = $service->getModelName();

        return response()->json($responseArray);
    }
}
