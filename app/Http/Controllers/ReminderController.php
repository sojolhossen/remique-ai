<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reminder\AiInterpretRequest;
use App\Http\Requests\Reminder\StoreReminderRequest;
use App\Http\Requests\Reminder\UpdateReminderRequest;
use App\Models\Reminder;
use App\Services\AI\Contracts\AiServiceInterface;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function __construct(
        protected ReminderService $reminderService,
        protected AiServiceInterface $aiService
    ) {}

    /**
     * Display a paginated list of user reminders with filters.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'priority', 'date_filter', 'sort_by']);

        $reminders = $this->reminderService->getPaginatedReminders($user, $filters, 10);

        return view('reminders.index', [
            'user' => $user,
            'reminders' => $reminders,
            'filters' => $filters,
            'activeTab' => $filters['status'] ?? 'all',
        ]);
    }

    /**
     * Show the form for creating a new reminder.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $nowInUserTz = Carbon::now($user->timezone ?: 'UTC');

        return view('reminders.create', [
            'user' => $user,
            'defaultDate' => $nowInUserTz->copy()->addDay()->toDateString(),
            'defaultTime' => '09:00',
            'aiConfigured' => $this->aiService->isConfigured(),
        ]);
    }

    /**
     * Store a newly created reminder in persistent storage.
     */
    public function store(StoreReminderRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->reminderService->createReminder($user, $request->validated());

        return redirect()->route('reminders.index')
            ->with('status', 'Reminder created successfully!');
    }

    /**
     * Show the form for editing an existing reminder.
     */
    public function edit(Reminder $reminder): View
    {
        Gate::authorize('update', $reminder);

        $user = auth()->user();
        $userTz = $user->timezone ?: 'UTC';
        $dueInUserTz = $reminder->due_at->copy()->setTimezone($userTz);

        return view('reminders.edit', [
            'reminder' => $reminder,
            'user' => $user,
            'date' => $dueInUserTz->toDateString(),
            'time' => $dueInUserTz->format('H:i'),
        ]);
    }

    /**
     * Update the specified reminder.
     */
    public function update(UpdateReminderRequest $request, Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        $this->reminderService->updateReminder($reminder, $request->validated(), $request->user()->timezone);

        return redirect()->route('reminders.index')
            ->with('status', 'Reminder updated successfully.');
    }

    /**
     * Mark the reminder as completed.
     */
    public function complete(Request $request, Reminder $reminder): RedirectResponse|JsonResponse
    {
        Gate::authorize('complete', $reminder);

        $success = $reminder->markAsCompleted();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Reminder marked as completed!' : 'Reminder is already completed.',
            ]);
        }

        return back()->with('status', $success ? 'Reminder marked as completed!' : 'Reminder is already completed.');
    }

    /**
     * Cancel the reminder.
     */
    public function cancel(Request $request, Reminder $reminder): RedirectResponse|JsonResponse
    {
        Gate::authorize('cancel', $reminder);

        $success = $reminder->markAsCancelled();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Reminder cancelled.' : 'Reminder is already cancelled.',
            ]);
        }

        return back()->with('status', $success ? 'Reminder cancelled.' : 'Reminder is already cancelled.');
    }

    /**
     * Reopen a completed or cancelled reminder.
     */
    public function reopen(Request $request, Reminder $reminder): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $reminder);

        $success = $reminder->reopen();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => 'Reminder rescheduled to active.',
            ]);
        }

        return back()->with('status', 'Reminder rescheduled to active.');
    }

    /**
     * Remove the specified reminder.
     */
    public function destroy(Request $request, Reminder $reminder): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $reminder);

        $reminder->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder deleted successfully.',
            ]);
        }

        return redirect()->route('reminders.index')
            ->with('status', 'Reminder deleted successfully.');
    }

    /**
     * Interpret natural language prompt via NVIDIA NIM.
     * Returns structured preview for confirmation.
     */
    public function interpretAi(AiInterpretRequest $request): JsonResponse
    {
        $user = $request->user();
        $userTz = $user->timezone ?: 'UTC';

        $result = $this->aiService->parseReminderPrompt(
            $request->input('prompt'),
            $userTz,
            $user->id
        );

        return response()->json($result->toArray(), $result->success ? 200 : 422);
    }
}
