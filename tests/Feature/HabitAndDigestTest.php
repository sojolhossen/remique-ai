<?php

namespace Tests\Feature;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Reminder;
use App\Models\TelegramIdentity;
use App\Models\User;
use App\Services\Digest\MorningDigestService;
use App\Services\Habits\HabitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitAndDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_habits_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('habits.index'));

        $response->assertStatus(200);
        $response->assertSee('Daily Habits & Streaks');
    }

    public function test_user_can_create_a_habit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('habits.store'), [
            'name' => 'Drink 2L Water',
            'frequency' => 'daily',
            'reminder_time' => '08:30',
            'description' => 'Stay hydrated everyday',
        ]);

        $response->assertRedirect(route('habits.index'));
        $this->assertDatabaseHas('habits', [
            'user_id' => $user->id,
            'name' => 'Drink 2L Water',
            'frequency' => 'daily',
        ]);
    }

    public function test_user_can_toggle_habit_and_streak_updates(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'name' => 'Morning Jogging',
            'frequency' => 'daily',
            'current_streak' => 0,
        ]);

        // 1. Toggle ON
        $response = $this->actingAs($user)->postJson(route('habits.toggle', $habit));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
            'streak' => 1,
        ]);

        $this->assertDatabaseHas('habit_logs', [
            'habit_id' => $habit->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $this->assertEquals(1, $habit->fresh()->current_streak);

        // 2. Toggle OFF (uncomplete)
        $responseUncomplete = $this->actingAs($user)->postJson(route('habits.toggle', $habit));
        $responseUncomplete->assertStatus(200);
        $responseUncomplete->assertJson([
            'success' => true,
            'status' => 'uncompleted',
            'streak' => 0,
        ]);

        $this->assertDatabaseMissing('habit_logs', [
            'habit_id' => $habit->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_toggle_another_users_habit_idor_protection(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $habit = Habit::create([
            'user_id' => $user1->id,
            'name' => 'Secret Routine',
            'frequency' => 'daily',
        ]);

        $response = $this->actingAs($user2)->postJson(route('habits.toggle', $habit));
        $response->assertStatus(403);
    }

    public function test_user_can_delete_habit(): void
    {
        $user = User::factory()->create();
        $habit = Habit::create([
            'user_id' => $user->id,
            'name' => 'Temporary Habit',
            'frequency' => 'daily',
        ]);

        $response = $this->actingAs($user)->delete(route('habits.destroy', $habit));
        $response->assertRedirect(route('habits.index'));

        $this->assertDatabaseMissing('habits', ['id' => $habit->id]);
    }

    public function test_habit_service_can_match_and_complete_habit_by_natural_language(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);
        $habit = Habit::create([
            'user_id' => $user->id,
            'name' => 'Evening Workout',
            'frequency' => 'daily',
        ]);

        $habitService = app(HabitService::class);
        $result = $habitService->findAndCompleteByName($user, 'workout');

        $this->assertNotNull($result);
        $this->assertEquals($habit->id, $result['habit']->id);
        $this->assertEquals(1, $result['streak']);
        $this->assertEquals('completed', $result['status']);
    }

    public function test_morning_digest_generates_accurate_briefing(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        // Create today's reminder
        Reminder::create([
            'user_id' => $user->id,
            'title' => 'Client Zoom Meeting',
            'due_at' => Carbon::now('Asia/Dhaka')->setTime(11, 0),
            'priority' => 'high',
            'status' => 'pending',
        ]);

        // Create habit
        Habit::create([
            'user_id' => $user->id,
            'name' => 'Read 15 Pages',
            'frequency' => 'daily',
            'current_streak' => 3,
        ]);

        $digestService = app(MorningDigestService::class);
        $digest = $digestService->generateDigest($user);

        $this->assertEquals(1, $digest['stats']['total_reminders']);
        $this->assertEquals(1, $digest['stats']['total_habits']);
        $this->assertEquals(0, $digest['stats']['completed_habits']);

        $text = $digestService->formatTextMessage($digest);
        $this->assertStringContainsString('Daily Morning Briefing', $text);
        $this->assertStringContainsString('Client Zoom Meeting', $text);
        $this->assertStringContainsString('Read 15 Pages', $text);
        $this->assertStringContainsString('3d streak', $text);
    }

    public function test_send_morning_digest_artisan_command_runs_successfully(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'timezone' => 'Asia/Dhaka',
        ]);

        $this->artisan('remi:send-morning-digest', ['--user' => $user->id, '--force' => true])
            ->assertExitCode(0);
    }
}
