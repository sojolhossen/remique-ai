<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\ReminderDeliveryAttempt;
use App\Models\User;
use App\Models\WhatsAppIdentity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScheduledDeliveryEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('whatsapp.phone_number_id', '1234567890');
        Config::set('whatsapp.access_token', 'test_token');
        Config::set('whatsapp.default_reminder_template', 'remi_reminder_alert');
    }

    public function test_scheduler_delivers_due_reminder_within_24h_window(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.outbound_due_1']],
            ], 200),
        ]);

        $user = User::factory()->create(['status' => 'active', 'timezone' => 'Asia/Dhaka']);
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '+8801700000001',
            'status' => 'active',
            'verified_at' => Carbon::now()->subDays(1),
            'last_inbound_at' => Carbon::now()->subHours(2), // within 24h window
        ]);

        $dueReminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Client Zoom Meeting',
            'due_at' => Carbon::now('UTC')->subMinute(),
            'status' => Reminder::STATUS_SCHEDULED,
            'recurrence' => Reminder::RECURRENCE_NONE,
        ]);

        Artisan::call('remi:send-due-reminders');

        // Assert attempt recorded as sent
        $this->assertDatabaseHas('reminder_delivery_attempts', [
            'reminder_id' => $dueReminder->id,
            'status' => 'sent',
            'is_template' => false,
        ]);

        // Assert reminder marked as completed
        $dueReminder->refresh();
        $this->assertEquals(Reminder::STATUS_COMPLETED, $dueReminder->status);

        // Verify sent text contains reminder title
        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'Client Zoom Meeting') &&
                   $request['type'] === 'text';
        });
    }

    public function test_scheduler_uses_template_outside_24h_window(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.template_outbound_1']],
            ], 200),
        ]);

        $user = User::factory()->create(['status' => 'active', 'name' => 'Sojol']);
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '+8801700000002',
            'status' => 'active',
            'verified_at' => Carbon::now()->subDays(5),
            'last_inbound_at' => Carbon::now()->subHours(30), // EXPIRED: outside 24h window
        ]);

        $dueReminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Pay Internet Bill',
            'due_at' => Carbon::now('UTC')->subMinutes(5),
            'status' => Reminder::STATUS_SCHEDULED,
            'recurrence' => Reminder::RECURRENCE_NONE,
        ]);

        Artisan::call('remi:send-due-reminders');

        $this->assertDatabaseHas('reminder_delivery_attempts', [
            'reminder_id' => $dueReminder->id,
            'status' => 'sent',
            'is_template' => true,
            'template_name' => 'remi_reminder_alert',
        ]);

        Http::assertSent(function ($request) {
            return $request['type'] === 'template' &&
                   $request['template']['name'] === 'remi_reminder_alert';
        });
    }

    public function test_recurring_reminder_rolls_to_next_occurrence(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.recurring_1']],
            ], 200),
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '+8801700000003',
            'status' => 'active',
            'verified_at' => Carbon::now()->subDays(1),
            'last_inbound_at' => Carbon::now()->subMinutes(10),
        ]);

        $dueAt = Carbon::now('UTC')->subMinute();
        $recurringReminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Daily Team Standup',
            'due_at' => $dueAt,
            'status' => Reminder::STATUS_SCHEDULED,
            'recurrence' => Reminder::RECURRENCE_DAILY,
        ]);

        Artisan::call('remi:send-due-reminders');

        $recurringReminder->refresh();
        // Should remain scheduled, but due_at advanced by exactly 1 day
        $this->assertEquals(Reminder::STATUS_SCHEDULED, $recurringReminder->status);
        $this->assertEquals($dueAt->copy()->addDay()->timestamp, $recurringReminder->due_at->timestamp);
    }

    public function test_idempotency_prevents_duplicate_sends(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.dup_1']],
            ], 200),
        ]);

        $user = User::factory()->create(['status' => 'active']);
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '+8801700000004',
            'status' => 'active',
            'verified_at' => Carbon::now(),
            'last_inbound_at' => Carbon::now(),
        ]);

        $reminder = Reminder::create([
            'user_id' => $user->id,
            'title' => 'Single Execution Test',
            'due_at' => Carbon::now('UTC')->subMinute(),
            'status' => Reminder::STATUS_SCHEDULED,
            'recurrence' => Reminder::RECURRENCE_NONE,
        ]);

        // First run sends message
        Artisan::call('remi:send-due-reminders');
        $this->assertEquals(1, ReminderDeliveryAttempt::count());

        // Second run immediately after
        Artisan::call('remi:send-due-reminders');
        // No second attempt created or sent
        $this->assertEquals(1, ReminderDeliveryAttempt::count());
        Http::assertSentCount(1);
    }
}
