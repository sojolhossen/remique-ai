<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ReminderDeliveryAttempt;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppManagementController extends Controller
{
    public function __construct(
        protected WhatsAppCloudApiService $apiService
    ) {}

    public function index(Request $request): View
    {
        // 1. Config status masked
        $phoneId = config('whatsapp.phone_number_id');
        $wabaId = config('whatsapp.business_account_id');
        $accessToken = config('whatsapp.access_token');
        $appSecret = config('whatsapp.app_secret');
        $verifyToken = config('whatsapp.verify_token');

        $configStatus = [
            'is_configured' => ! empty($phoneId) && ! empty($accessToken),
            'phone_number_id' => ! empty($phoneId) ? substr($phoneId, 0, 4) . '••••' . substr($phoneId, -4) : 'Not Configured',
            'business_account_id' => ! empty($wabaId) ? substr($wabaId, 0, 4) . '••••' . substr($wabaId, -4) : 'Not Configured',
            'token_set' => ! empty($accessToken),
            'app_secret_set' => ! empty($appSecret),
            'verify_token' => $verifyToken,
            'webhook_url' => url('/whatsapp/webhook'),
            'api_version' => config('whatsapp.api_version'),
        ];

        // 2. Messages query with filter
        $direction = $request->get('direction', '');
        $status = $request->get('status', '');
        $search = trim($request->get('search', ''));

        $query = WhatsAppMessage::with('identity.user');

        if (! empty($direction)) {
            $query->where('direction', $direction);
        }
        if (! empty($status)) {
            $query->where('status', $status);
        }
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('from_number', 'like', "%{$search}%")
                  ->orWhere('to_number', 'like', "%{$search}%")
                  ->orWhere('wamid', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $messages = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // 3. Approved templates
        $templates = WhatsAppTemplate::orderBy('name')->get();

        return view('admin.whatsapp.index', compact('configStatus', 'messages', 'templates', 'direction', 'status', 'search'));
    }

    public function testConnectivity(): JsonResponse
    {
        $result = $this->apiService->testConnectivity();

        AuditLog::record(
            'whatsapp.connectivity_tested',
            'WhatsAppCloudApi',
            $result['message'],
            ['success' => $result['success'], 'status_code' => $result['status_code']],
            auth()->id()
        );

        return response()->json($result);
    }

    public function retryDelivery(ReminderDeliveryAttempt $attempt): RedirectResponse
    {
        abort_if(! in_array($attempt->status, ['failed', 'exhausted']), 400, 'Attempt is not in a failed state.');

        $attempt->update([
            'status' => 'pending',
            'attempt_number' => max(1, $attempt->attempt_number - 1),
            'error_code' => null,
            'error_message' => null,
        ]);

        AuditLog::record(
            'reminder.delivery_requeued',
            $attempt,
            'Super Admin manually requeued failed reminder delivery attempt',
            ['attempt_id' => $attempt->id],
            auth()->id()
        );

        return back()->with('success', "Delivery attempt #{$attempt->id} requeued for next scheduler cycle.");
    }
}
