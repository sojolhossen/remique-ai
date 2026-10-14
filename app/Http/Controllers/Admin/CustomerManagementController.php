<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WhatsAppIdentity;
use App\Services\Vault\VaultService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CustomerManagementController extends Controller
{
    public function __construct(
        protected VaultService $vaultService
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $status = $request->get('status', '');

        $query = User::where('role', 'customer')
            ->with(['whatsappIdentities', 'reminders', 'vaultFiles'])
            ->withCount(['reminders', 'vaultFiles']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('whatsappIdentities', function ($sq) use ($search) {
                      $sq->where('phone_number', 'like', "%{$search}%");
                  });
            });
        }

        if (! empty($status)) {
            $query->where('status', $status);
        }

        $customers = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers', 'search', 'status'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'timezone' => 'required|string|timezone',
            'preferred_language' => 'required|in:en,bn',
            'whatsapp_number' => 'nullable|string|max:32',
        ]);

        $customer = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'customer',
            'status' => 'active',
            'timezone' => $validated['timezone'],
            'preferred_language' => $validated['preferred_language'],
        ]);

        if (! empty($validated['whatsapp_number'])) {
            $normalized = WhatsAppIdentity::normalizePhoneNumber($validated['whatsapp_number']);
            $otp = strtoupper(Str::random(6));

            WhatsAppIdentity::create([
                'user_id' => $customer->id,
                'phone_number' => $normalized,
                'status' => 'pending_verification',
                'verification_token' => $otp,
                'verification_expires_at' => Carbon::now()->addMinutes(30),
            ]);
        }

        AuditLog::record(
            'customer.created',
            $customer,
            'New customer registered by Super Admin',
            ['email' => $customer->email],
            auth()->id()
        );

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Customer '{$customer->name}' created successfully.");
    }

    public function show(User $customer): View
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->load(['whatsappIdentities', 'reminders' => function ($q) {
            $q->orderByDesc('due_at')->limit(10);
        }, 'vaultFiles' => function ($q) {
            $q->orderByDesc('created_at')->limit(10);
        }]);

        $storageUsed = $this->vaultService->getUserStorageUsage($customer);
        $remindersCount = $customer->reminders()->count();
        $completedRemindersCount = $customer->reminders()->completed()->count();
        $vaultFilesCount = $customer->vaultFiles()->count();

        // Customer audit history
        $customerAuditLogs = AuditLog::where('target_type', 'User')
            ->where('target_id', $customer->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.customers.show', compact(
            'customer',
            'storageUsed',
            'remindersCount',
            'completedRemindersCount',
            'vaultFilesCount',
            'customerAuditLogs'
        ));
    }

    public function linkWhatsApp(Request $request, User $customer): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|max:32',
        ]);

        $normalized = WhatsAppIdentity::normalizePhoneNumber($validated['phone_number']);

        // Check if phone number is already linked to another account
        $existing = WhatsAppIdentity::where('phone_number', $normalized)
            ->where('user_id', '!=', $customer->id)
            ->first();

        if ($existing) {
            return back()->withErrors(['phone_number' => "This phone number is already linked to customer #{$existing->user_id}."]);
        }

        $otp = strtoupper(Str::random(6));

        $identity = WhatsAppIdentity::updateOrCreate(
            ['user_id' => $customer->id, 'phone_number' => $normalized],
            [
                'status' => 'pending_verification',
                'verification_token' => $otp,
                'verification_expires_at' => Carbon::now()->addMinutes(30),
                'verified_at' => null,
            ]
        );

        AuditLog::record(
            'whatsapp.linking_initiated',
            $identity,
            "Verification token generated for {$normalized}",
            ['phone' => $normalized, 'expires_at' => $identity->verification_expires_at->toIso8601String()],
            auth()->id()
        );

        return back()->with('success', "Verification OTP generated: {$otp}. Customer must text this code to Remique within 30 minutes.");
    }

    public function manualVerify(Request $request, WhatsAppIdentity $identity): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:6|max:500',
        ]);

        $identity->update([
            'status' => 'active',
            'verified_at' => Carbon::now(),
            'verified_by_user_id' => auth()->id(),
            'manual_verification_reason' => $validated['reason'],
            'consent_recorded_at' => Carbon::now(),
            'verification_token' => null,
            'verification_expires_at' => null,
        ]);

        AuditLog::record(
            'whatsapp.manual_verification_override',
            $identity,
            $validated['reason'],
            [
                'phone_number' => $identity->phone_number,
                'authorized_by' => auth()->user()->email,
            ],
            auth()->id()
        );

        return back()->with('success', "WhatsApp number {$identity->phone_number} manually verified by Super Admin.");
    }

    public function updateStatus(Request $request, User $customer): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,blocked',
            'reason' => 'nullable|string|max:500',
        ]);

        $oldStatus = $customer->status;
        $newStatus = $validated['status'];

        $customer->update(['status' => $newStatus]);

        // Sync linked WhatsApp numbers
        $identityStatus = match ($newStatus) {
            'suspended' => 'suspended',
            'blocked' => 'blocked',
            default => 'active',
        };

        $customer->whatsappIdentities()->update(['status' => $identityStatus]);

        AuditLog::record(
            "customer.status_{$newStatus}",
            $customer,
            $validated['reason'] ?? "Customer status transitioned from {$oldStatus} to {$newStatus}",
            ['old_status' => $oldStatus, 'new_status' => $newStatus],
            auth()->id()
        );

        return back()->with('success', "Customer status updated to {$newStatus}.");
    }

    public function revokeIdentity(Request $request, WhatsAppIdentity $identity): RedirectResponse
    {
        $identity->update(['status' => 'revoked']);

        AuditLog::record(
            'whatsapp.identity_revoked',
            $identity,
            'Super Admin revoked WhatsApp authorization',
            ['phone_number' => $identity->phone_number],
            auth()->id()
        );

        return back()->with('success', "Authorization revoked for {$identity->phone_number}.");
    }
}
