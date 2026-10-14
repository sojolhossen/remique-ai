<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiInteractionLog;
use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\ReminderDeliveryAttempt;
use App\Models\User;
use App\Models\VaultFile;
use App\Models\WhatsAppMessage;
use App\Services\Vault\VaultService;
use Carbon\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected VaultService $vaultService
    ) {}

    public function index(): View
    {
        $nowUtc = Carbon::now('UTC');
        $todayStartUtc = Carbon::now('UTC')->startOfDay();
        $todayEndUtc = Carbon::now('UTC')->endOfDay();

        // 1. Customer metrics
        $totalCustomers = User::where('role', 'customer')->count();
        $activeCustomers = User::where('role', 'customer')->where('status', 'active')->count();
        $pendingCustomers = User::where('role', 'customer')->whereHas('whatsappIdentities', function ($q) {
            $q->where('status', 'pending_verification');
        })->count();
        $suspendedCustomers = User::where('role', 'customer')->whereIn('status', ['suspended', 'blocked'])->count();

        // 2. Reminder metrics
        $scheduledCount = Reminder::scheduled()->count();
        $dueTodayCount = Reminder::scheduled()
            ->whereBetween('due_at', [$todayStartUtc, $todayEndUtc])
            ->count();
        $completedCount = Reminder::completed()->count();
        $cancelledCount = Reminder::cancelled()->count();

        // 3. WhatsApp delivery metrics (last 30 days)
        $since30Days = Carbon::now('UTC')->subDays(30);
        $totalAttempts = ReminderDeliveryAttempt::where('created_at', '>=', $since30Days)->count();
        $deliveredAttempts = ReminderDeliveryAttempt::where('created_at', '>=', $since30Days)
            ->whereIn('status', ['sent', 'delivered', 'read'])
            ->count();
        $failedAttempts = ReminderDeliveryAttempt::where('created_at', '>=', $since30Days)
            ->whereIn('status', ['failed', 'exhausted'])
            ->count();

        $deliverySuccessRate = $totalAttempts > 0
            ? round(($deliveredAttempts / $totalAttempts) * 100, 1)
            : 100.0;

        // 4. Inbound and Outbound WhatsApp messages
        $inboundMessagesCount = WhatsAppMessage::where('direction', 'inbound')->count();
        $outboundMessagesCount = WhatsAppMessage::where('direction', 'outbound')->count();

        // 5. Memory Vault storage metrics
        $vaultMetrics = $this->vaultService->getGlobalStorageMetrics();

        // 6. AI Interaction & Error metrics
        $totalAiRequests = AiInteractionLog::count();
        $failedAiRequests = AiInteractionLog::where('status', 'failed')->count();
        $avgAiDuration = (int) round(AiInteractionLog::avg('duration_ms') ?? 0);

        // 7. Recent failed delivery queue
        $failedQueue = ReminderDeliveryAttempt::with(['reminder.user', 'identity'])
            ->whereIn('status', ['failed', 'exhausted'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // 8. Recent Security & Audit Logs
        $recentAuditLogs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard.index', compact(
            'totalCustomers',
            'activeCustomers',
            'pendingCustomers',
            'suspendedCustomers',
            'scheduledCount',
            'dueTodayCount',
            'completedCount',
            'cancelledCount',
            'totalAttempts',
            'deliveredAttempts',
            'failedAttempts',
            'deliverySuccessRate',
            'inboundMessagesCount',
            'outboundMessagesCount',
            'vaultMetrics',
            'totalAiRequests',
            'failedAiRequests',
            'avgAiDuration',
            'failedQueue',
            'recentAuditLogs'
        ));
    }
}
