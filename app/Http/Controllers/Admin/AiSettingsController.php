<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiInteractionLog;
use App\Models\AuditLog;
use App\Services\AI\NvidiaNimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function __construct(
        protected NvidiaNimService $aiService
    ) {}

    public function index(): View
    {
        $apiKey = config('nvidia.api_key', '');
        $maskedKey = ! empty($apiKey)
            ? substr($apiKey, 0, 6) . '••••••••' . substr($apiKey, -4)
            : 'Not Configured';

        $config = [
            'provider' => 'NVIDIA NIM (OpenAI-compatible)',
            'base_url' => config('nvidia.base_url', 'https://integrate.api.nvidia.com/v1'),
            'model' => config('nvidia.model', 'openai/gpt-oss-20b'),
            'timeout' => config('nvidia.timeout', 30),
            'max_retries' => config('nvidia.max_retries', 2),
            'api_key_masked' => $maskedKey,
            'is_configured' => $this->aiService->isConfigured(),
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

        return view('admin.ai.index', compact('config', 'totalLogs', 'successfulLogs', 'failedLogs', 'avgDuration', 'totalTokens', 'errorBreakdown', 'recentLogs'));
    }

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
            'Super Admin ran test in AI playground',
            ['intent' => $result->intent, 'success' => $result->success],
            auth()->id()
        );

        return response()->json($result->toArray());
    }
}
