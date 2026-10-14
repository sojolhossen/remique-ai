<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_reminders_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reminders');

        $response->assertStatus(200);
        $response->assertSee('All Reminders');
    }

    public function test_user_can_create_a_reminder_with_timezone_conversion(): void
    {
        // User is in Asia/Dhaka (GMT+6)
        $user = User::factory()->create([
            'timezone' => 'Asia/Dhaka',
        ]);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(2)->toDateString();

        $response = $this->actingAs($user)->post('/reminders', [
            'title' => 'Doctor Appointment',
            'description' => 'Visit Dr. Rahman for regular checkup',
            'date' => $futureDate,
            'time' => '14:30', // 2:30 PM in Asia/Dhaka -> 08:30:00 UTC
            'priority' => 'high',
            'recurrence' => 'none',
        ]);

        $response->assertRedirect(route('reminders.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'title' => 'Doctor Appointment',
            'priority' => 'high',
            'status' => 'scheduled',
        ]);

        $reminder = Reminder::where('title', 'Doctor Appointment')->first();

        // Check UTC conversion: 14:30 in Asia/Dhaka (GMT+6) is 08:30 in UTC
        $expectedUtc = Carbon::createFromFormat('Y-m-d H:i:s', "{$futureDate} 14:30:00", 'Asia/Dhaka')->setTimezone('UTC');
        $this->assertEquals(
            $expectedUtc->format('Y-m-d H:i:s'),
            $reminder->due_at->format('Y-m-d H:i:s')
        );
    }

    public function test_reminder_creation_fails_if_required_fields_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/reminders', [
            'title' => '',
            'date' => '',
            'time' => '',
        ]);

        $response->assertSessionHasErrors(['title', 'date', 'time']);
    }

    public function test_reminder_creation_fails_if_date_is_in_the_past(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
        ]);

        $pastDate = Carbon::now('UTC')->subDays(2)->toDateString();

        $response = $this->actingAs($user)->post('/reminders', [
            'title' => 'Past Reminder',
            'date' => $pastDate,
            'time' => '09:00',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('date');
    }

    public function test_user_can_edit_and_update_reminder(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Original Title',
            'due_at' => Carbon::now('UTC')->addDays(1),
            'status' => 'scheduled',
            'priority' => 'low',
        ]);

        $response = $this->actingAs($user)->get(route('reminders.edit', $reminder));
        $response->assertStatus(200);
        $response->assertSee('Original Title');

        $newDate = Carbon::now('UTC')->addDays(3)->toDateString();

        $updateResponse = $this->actingAs($user)->put(route('reminders.update', $reminder), [
            'title' => 'Updated Title',
            'description' => 'Updated description notes',
            'date' => $newDate,
            'time' => '11:00',
            'priority' => 'urgent',
            'recurrence' => 'weekly',
        ]);

        $updateResponse->assertRedirect(route('reminders.index'));

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'title' => 'Updated Title',
            'priority' => 'urgent',
            'recurrence' => 'weekly',
        ]);
    }

    public function test_user_can_mark_reminder_as_completed(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Task to Complete',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->post(route('reminders.complete', $reminder));

        $response->assertSessionHas('status');

        $reminder->refresh();
        $this->assertEquals(Reminder::STATUS_COMPLETED, $reminder->status);
        $this->assertNotNull($reminder->completed_at);
    }

    public function test_user_can_cancel_and_reopen_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Task to Cancel',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        // Cancel
        $this->actingAs($user)->post(route('reminders.cancel', $reminder));
        $reminder->refresh();
        $this->assertEquals(Reminder::STATUS_CANCELLED, $reminder->status);
        $this->assertNotNull($reminder->cancelled_at);

        // Reopen
        $this->actingAs($user)->post(route('reminders.reopen', $reminder));
        $reminder->refresh();
        $this->assertEquals(Reminder::STATUS_SCHEDULED, $reminder->status);
        $this->assertNull($reminder->cancelled_at);
    }

    public function test_user_can_delete_reminder(): void
    {
        $user = User::factory()->create();
        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Task to Delete',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($user)->delete(route('reminders.destroy', $reminder));

        $response->assertRedirect(route('reminders.index'));
        $this->assertDatabaseMissing('reminders', ['id' => $reminder->id]);
    }
}
