<?php

namespace App\Http\Controllers;

use App\Services\ReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReminderService $reminderService
    ) {}

    /**
     * Display the enterprise user dashboard overview.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $metrics = $this->reminderService->getDashboardMetrics($user);

        return view('dashboard.overview', [
            'user' => $user,
            'metrics' => $metrics,
        ]);
    }
}
