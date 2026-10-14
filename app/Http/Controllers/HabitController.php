<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Services\Digest\MorningDigestService;
use App\Services\Habits\HabitService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HabitController extends Controller
{
    public function __construct(
        protected HabitService $habitService,
        protected MorningDigestService $digestService
    ) {}

    /**
     * Display habits dashboard with streaks and 7-day tracker.
     */
    public function index(): View
    {
        $user = auth()->user();
        $habits = $this->habitService->getUserHabitsWithStatus($user);

        $totalHabits = $habits->count();
        $completedToday = $habits->where('is_completed_today', true)->count();
        $completionRate = $totalHabits > 0 ? (int) round(($completedToday / $totalHabits) * 100) : 0;
        $totalStreak = $habits->sum('current_streak');
        $longestStreak = $habits->max('longest_streak') ?? 0;

        return view('habits.index', compact(
            'habits',
            'totalHabits',
            'completedToday',
            'completionRate',
            'totalStreak',
            'longestStreak'
        ));
    }

    /**
     * Store a new habit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'frequency' => 'required|string|in:daily,weekdays,weekends',
            'reminder_time' => 'nullable|date_format:H:i',
        ]);

        $this->habitService->createHabit(auth()->user(), $validated);

        return redirect()->route('habits.index')->with('status', "Habit '{$validated['name']}' created successfully!");
    }

    /**
     * AJAX Toggle habit completion for today.
     */
    public function toggle(Habit $habit, Request $request): JsonResponse|RedirectResponse
    {
        if ($habit->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to habit.');
        }

        $result = $this->habitService->toggleHabit(auth()->user(), $habit);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'streak' => $result['streak'],
                'message' => $result['message'],
            ]);
        }

        return redirect()->route('habits.index')->with('status', $result['message']);
    }

    /**
     * Delete a habit.
     */
    public function destroy(Habit $habit): RedirectResponse
    {
        if ($habit->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to habit.');
        }

        $name = $habit->name;
        $habit->delete();

        return redirect()->route('habits.index')->with('status', "Habit '{$name}' deleted.");
    }

    /**
     * Preview or live test the Morning Daily Digest.
     */
    public function sendTestDigest(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $sent = $this->digestService->sendToUser($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $sent,
                'message' => $sent
                    ? '☀️ Morning Daily Digest sent to your Telegram!'
                    : '⚠️ Please link your Telegram account in Settings to receive live briefings.',
            ]);
        }

        return back()->with('status', $sent
            ? '☀️ Morning Daily Digest sent to your Telegram!'
            : '⚠️ Please link your Telegram account in Settings to receive live briefings.'
        );
    }
}
