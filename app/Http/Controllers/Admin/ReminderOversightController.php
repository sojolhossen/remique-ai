<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\ReminderDeliveryAttempt;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReminderOversightController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', '');
        $search = trim($request->get('search', ''));

        $query = Reminder::with(['user', 'deliveryAttempts' => function ($q) {
            $q->orderByDesc('created_at')->limit(3);
        }]);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $reminders = $query->orderByDesc('due_at')->paginate(20)->withQueryString();

        // Failed delivery queue
        $failedAttempts = ReminderDeliveryAttempt::with(['reminder.user', 'identity'])
            ->whereIn('status', ['failed', 'exhausted'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('admin.reminders.index', compact('reminders', 'failedAttempts', 'status', 'search'));
    }

    public function reschedule(Request $request, Reminder $reminder): RedirectResponse
    {
        $validated = $request->validate([
            'due_at' => 'required|date',
            'reason' => 'required|string|min:5|max:500',
        ]);

        $oldDueAt = $reminder->due_at->toIso8601String();
        $newDueAt = Carbon::parse($validated['due_at'], 'UTC');

        $reminder->reopen($newDueAt);

        AuditLog::record(
            'reminder.admin_rescheduled',
            $reminder,
            $validated['reason'],
            [
                'old_due_at' => $oldDueAt,
                'new_due_at' => $newDueAt->toIso8601String(),
            ],
            auth()->id()
        );

        return back()->with('success', "Reminder #{$reminder->id} rescheduled to {$newDueAt->toDateTimeString()} UTC.");
    }

    public function cancel(Request $request, Reminder $reminder): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:500',
        ]);

        $reminder->markAsCancelled();

        AuditLog::record(
            'reminder.admin_cancelled',
            $reminder,
            $validated['reason'],
            ['title' => $reminder->title],
            auth()->id()
        );

        return back()->with('success', "Reminder #{$reminder->id} cancelled by Super Admin.");
    }
}
