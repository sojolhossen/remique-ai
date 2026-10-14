<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossUserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_view_another_users_reminder_edit_page(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderB = Reminder::create([
            'user_id' => $userB->id,
            'title' => 'Secret Reminder of User B',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->get(route('reminders.edit', $reminderB));

        $response->assertStatus(403);
    }

    public function test_user_cannot_update_another_users_reminder(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderB = Reminder::create([
            'user_id' => $userB->id,
            'title' => 'User B Title',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->put(route('reminders.update', $reminderB), [
            'title' => 'Hacked by User A',
            'date' => Carbon::now('UTC')->addDays(2)->toDateString(),
            'time' => '10:00',
            'priority' => 'low',
        ]);

        $response->assertStatus(403);

        $reminderB->refresh();
        $this->assertEquals('User B Title', $reminderB->title);
    }

    public function test_user_cannot_complete_another_users_reminder(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderB = Reminder::create([
            'user_id' => $userB->id,
            'title' => 'User B Title',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->post(route('reminders.complete', $reminderB));

        $response->assertStatus(403);

        $reminderB->refresh();
        $this->assertEquals(Reminder::STATUS_SCHEDULED, $reminderB->status);
    }

    public function test_user_cannot_cancel_another_users_reminder(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderB = Reminder::create([
            'user_id' => $userB->id,
            'title' => 'User B Title',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->post(route('reminders.cancel', $reminderB));

        $response->assertStatus(403);

        $reminderB->refresh();
        $this->assertEquals(Reminder::STATUS_SCHEDULED, $reminderB->status);
    }

    public function test_user_cannot_delete_another_users_reminder(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reminderB = Reminder::create([
            'user_id' => $userB->id,
            'title' => 'User B Title',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->delete(route('reminders.destroy', $reminderB));

        $response->assertStatus(403);

        $this->assertDatabaseHas('reminders', ['id' => $reminderB->id]);
    }

    public function test_user_reminder_list_does_not_leak_other_users_reminders(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Reminder::create([
            'user_id' => $userA->id,
            'title' => 'User A Reminder Only',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        Reminder::create([
            'user_id' => $userB->id,
            'title' => 'User B Confidential Reminder',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($userA)->get(route('reminders.index'));

        $response->assertStatus(200);
        $response->assertSee('User A Reminder Only');
        $response->assertDontSee('User B Confidential Reminder');
    }
}
