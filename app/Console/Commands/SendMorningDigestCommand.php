<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Digest\MorningDigestService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendMorningDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remi:send-morning-digest {--user= : User ID to send digest to directly} {--force : Force send regardless of time or previous delivery}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches automated Daily Morning Briefing to users via Telegram/WhatsApp based on their local time';

    /**
     * Execute the console command.
     */
    public function handle(MorningDigestService $digestService): int
    {
        $userId = $this->option('user');
        $force = (bool) $this->option('force');

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User ID {$userId} not found.");
                return self::FAILURE;
            }

            $this->info("Sending Morning Digest to {$user->name} ({$user->email})...");
            $sent = $digestService->sendToUser($user);
            if ($sent) {
                $this->info("✓ Morning Briefing delivered successfully to {$user->name}!");
            } else {
                $this->warn("⚠️ Could not deliver (no active Telegram identity connected or delivery error).");
            }
            return self::SUCCESS;
        }

        $this->info("Scanning users for Daily Morning Briefing dispatch...");
        $users = User::where('status', 'active')->get();
        $dispatchedCount = 0;

        foreach ($users as $user) {
            if (! $user->isMorningDigestEnabled() && ! $force) {
                continue;
            }

            $userTz = $user->timezone ?: 'Asia/Dhaka';
            $nowInUserTz = Carbon::now($userTz);
            $targetTime = $user->getMorningDigestTime(); // e.g. "08:00"
            $targetHour = (int) explode(':', $targetTime)[0];

            // If not forcing, check if current local hour matches preferred hour
            if (! $force && $nowInUserTz->hour !== $targetHour) {
                continue;
            }

            // Prevent duplicate delivery on the same calendar day for this user
            $cacheKey = "morning_digest_sent_{$user->id}_{$nowInUserTz->toDateString()}";
            if (! $force && Cache::has($cacheKey)) {
                continue;
            }

            $sent = $digestService->sendToUser($user);
            if ($sent) {
                Cache::put($cacheKey, true, now()->addHours(20));
                $dispatchedCount++;
                $this->line("• Sent morning digest to: {$user->name} ({$user->email})");
            }
        }

        $this->info("✓ Morning Digest dispatch complete. Total sent: {$dispatchedCount}");

        return self::SUCCESS;
    }
}
