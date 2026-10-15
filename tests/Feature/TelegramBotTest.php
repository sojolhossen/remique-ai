<?php

namespace Tests\Feature;

use App\Models\TelegramIdentity;
use App\Models\User;
use App\Models\VaultFile;
use App\Services\AI\NvidiaNimService;
use App\Services\AI\ReminderInterpretationResult;
use App\Services\Telegram\TelegramBotService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class TelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('telegram.bot_token', 'test_bot_token_123');
        Config::set('telegram.webhook_secret', 'test_secret_token_abc');
    }

    public function test_telegram_webhook_rejects_invalid_secret_token(): void
    {
        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 100,
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong_secret',
        ]);

        $response->assertStatus(403);
    }

    public function test_telegram_webhook_accepts_valid_secret_token(): void
    {
        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 100,
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    public function test_user_can_link_telegram_via_start_deep_link(): void
    {
        $user = User::factory()->create();
        $token = 'LINK99';

        $identity = TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => 'temp_chat_placeholder',
            'status' => 'pending_verification',
            'verification_token' => $token,
            'verification_expires_at' => now()->addHour(),
        ]);

        // Mock TelegramBotService sendMessage
        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '123456789' && str_contains($text, 'Welcome to Remique');
            })
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // Inbound /start LINK99
        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 101,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123456789,
                    'username' => 'sojol_dev',
                    'first_name' => 'Sojol',
                ],
                'chat' => [
                    'id' => 123456789,
                    'type' => 'private',
                ],
                'text' => '/start LINK99',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc',
        ]);

        $response->assertStatus(200);

        // Assert identity is now active with actual chat_id
        $this->assertEquals('active', $identity->fresh()->status);
        $this->assertEquals('123456789', $identity->fresh()->chat_id);
        $this->assertEquals('sojol_dev', $identity->fresh()->username);
    }

    public function test_verified_user_can_create_reminder_via_telegram(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '987654321',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Mock AI interpretation
        $mockAi = Mockery::mock(NvidiaNimService::class);
        $mockAi->shouldReceive('parseReminderPrompt')
            ->once()
            ->andReturn(new ReminderInterpretationResult(
                success: true,
                intent: 'create_reminder',
                title: 'Doctor appointment',
                description: null,
                date: '2026-10-15',
                time: '09:00:00',
                priority: 'high',
                recurrence: 'none',
                detectedLanguage: 'Banglish',
                hasMissingInfo: false
            ));
        $this->app->instance(NvidiaNimService::class, $mockAi);

        // Mock Telegram reply
        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '987654321' && (str_contains($text, 'Reminder Scheduled') || str_contains($text, 'Reminder Set Kora Hoyeche') || str_contains($text, 'Doctor appointment'));
            })
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // Post text update
        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 102,
            'message' => [
                'message_id' => 2,
                'from' => ['id' => 987654321],
                'chat' => ['id' => 987654321],
                'text' => 'Kal shokal 9 tay doctor appointment',
            ],
        ], [
            'X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc',
        ]);

        $response->assertStatus(200);

        // Assert reminder created in DB
        $this->assertDatabaseHas('reminders', [
            'user_id' => $user->id,
            'title' => 'Doctor appointment',
            'status' => 'scheduled',
        ]);
    }

    public function test_settings_page_renders_telegram_integration_card(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.edit'));
        $response->assertStatus(200);
        $response->assertSee('Telegram Personal Assistant');
        $response->assertSee('NOT CONNECTED');
    }

    public function test_telegram_maintains_multi_turn_conversation_memory(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        $identity = TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '11223344',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')->twice()->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        $mockAi = Mockery::mock(NvidiaNimService::class);

        // Turn 1: user introduces themselves
        $mockAi->shouldReceive('parseReminderPrompt')
            ->once()
            ->withArgs(function ($text, $tz, $userId, $history) {
                return $text === 'Amar nam Sojol' && empty($history);
            })
            ->andReturn(new ReminderInterpretationResult(
                success: true,
                intent: 'general_chat',
                conversationalReply: 'Shunlam Sojol bhai! Kemon achen?'
            ));

        // Turn 2: user asks a question referring back
        $mockAi->shouldReceive('parseReminderPrompt')
            ->once()
            ->withArgs(function ($text, $tz, $userId, $history) {
                return $text === 'Amar nam ki mone ache?' &&
                    count($history) === 2 &&
                    $history[0]['content'] === 'Amar nam Sojol' &&
                    $history[1]['content'] === 'Shunlam Sojol bhai! Kemon achen?';
            })
            ->andReturn(new ReminderInterpretationResult(
                success: true,
                intent: 'general_chat',
                conversationalReply: 'Haa, apnar nam Sojol!'
            ));

        $this->app->instance(NvidiaNimService::class, $mockAi);

        // Inbound Turn 1
        $this->postJson(route('telegram.webhook'), [
            'update_id' => 201,
            'message' => [
                'message_id' => 10,
                'from' => ['id' => 11223344],
                'chat' => ['id' => 11223344],
                'text' => 'Amar nam Sojol',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc'])->assertStatus(200);

        // Inbound Turn 2
        $this->postJson(route('telegram.webhook'), [
            'update_id' => 202,
            'message' => [
                'message_id' => 11,
                'from' => ['id' => 11223344],
                'chat' => ['id' => 11223344],
                'text' => 'Amar nam ki mone ache?',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc'])->assertStatus(200);

        // Check messages table has both inbound and outbound turns recorded
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_identity_id' => $identity->id,
            'role' => 'user',
            'body' => 'Amar nam Sojol',
        ]);
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_identity_id' => $identity->id,
            'role' => 'assistant',
            'body' => 'Shunlam Sojol bhai! Kemon achen?',
        ]);
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_identity_id' => $identity->id,
            'role' => 'user',
            'body' => 'Amar nam ki mone ache?',
        ]);
        $this->assertDatabaseHas('telegram_messages', [
            'telegram_identity_id' => $identity->id,
            'role' => 'assistant',
            'body' => 'Haa, apnar nam Sojol!',
        ]);
    }

    public function test_telegram_shows_time_remaining_when_listing_reminders(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '778899',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Create a scheduled reminder due in 2 hours
        \App\Models\Reminder::create([
            'user_id' => $user->id,
            'title' => 'Upload video to YouTube',
            'due_at' => Carbon::now('UTC')->addHours(2),
            'status' => \App\Models\Reminder::STATUS_SCHEDULED,
        ]);

        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '778899' &&
                    str_contains($text, 'Upload video to YouTube') &&
                    (str_contains($text, 'Baki ache') || str_contains($text, 'Remaining') || str_contains($text, 'বাকি আছে'));
            })
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // Send "Reminder list"
        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 301,
            'message' => [
                'message_id' => 30,
                'from' => ['id' => 778899],
                'chat' => ['id' => 778899],
                'text' => 'Reminder list',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc']);

        $response->assertStatus(200);
    }

    public function test_telegram_shows_time_remaining_when_creating_reminder(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '556677',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $futureDate = Carbon::now('Asia/Dhaka')->addDays(1)->toDateString();

        $mockAi = Mockery::mock(NvidiaNimService::class);
        $mockAi->shouldReceive('parseReminderPrompt')
            ->once()
            ->andReturn(new ReminderInterpretationResult(
                success: true,
                intent: 'create_reminder',
                title: 'Team Standup',
                description: null,
                date: $futureDate,
                time: '10:00:00',
                priority: 'medium',
                recurrence: 'none',
                detectedLanguage: 'bengali',
                hasMissingInfo: false
            ));
        $this->app->instance(NvidiaNimService::class, $mockAi);

        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '556677' &&
                    str_contains($text, 'Team Standup') &&
                    str_contains($text, 'বাকি আছে');
            })
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 302,
            'message' => [
                'message_id' => 31,
                'from' => ['id' => 556677],
                'chat' => ['id' => 556677],
                'text' => 'কাল সকাল ১০টায় টিম মিটিং',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc']);

        $response->assertStatus(200);
    }

    public function test_web_cron_endpoint_executes_due_reminders(): void
    {
        $response = $this->getJson(route('cron.reminders'));
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }

    public function test_telegram_fast_paths_common_acknowledgments_without_ai(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '112299',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        // Mock AI to ensure it is NEVER called for "Okay"
        $mockAi = Mockery::mock(NvidiaNimService::class);
        $mockAi->shouldReceive('parseReminderPrompt')->never();
        $this->app->instance(NvidiaNimService::class, $mockAi);

        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        $mockBot->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function ($chatId, $text) {
                return $chatId === '112299' && (str_contains($text, 'Thik ache') || str_contains($text, 'Sure thing') || str_contains($text, 'ঠিক আছে'));
            })
            ->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        $response = $this->postJson(route('telegram.webhook'), [
            'update_id' => 401,
            'message' => [
                'message_id' => 41,
                'from' => ['id' => 112299],
                'chat' => ['id' => 112299],
                'text' => 'Okay',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc']);

        $response->assertStatus(200);
    }

    public function test_telegram_webhook_deduplicates_retried_updates(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        TelegramIdentity::create([
            'user_id' => $user->id,
            'chat_id' => '112288',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $mockBot = Mockery::mock(TelegramBotService::class);
        $mockBot->shouldReceive('sendChatAction')->zeroOrMoreTimes()->andReturn(['ok' => true]);
        // Only ONE message should ever be sent, despite 2 incoming duplicate webhook calls
        $mockBot->shouldReceive('sendMessage')->once()->andReturn(['ok' => true]);
        $this->app->instance(TelegramBotService::class, $mockBot);

        // Attempt 1
        $res1 = $this->postJson(route('telegram.webhook'), [
            'update_id' => 500,
            'message' => [
                'message_id' => 51,
                'from' => ['id' => 112288],
                'chat' => ['id' => 112288],
                'text' => 'Good night',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc']);
        $res1->assertStatus(200);

        // Attempt 2 (Duplicate retry from Telegram)
        $res2 = $this->postJson(route('telegram.webhook'), [
            'update_id' => 500,
            'message' => [
                'message_id' => 51,
                'from' => ['id' => 112288],
                'chat' => ['id' => 112288],
                'text' => 'Good night',
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'test_secret_token_abc']);
        $res2->assertStatus(200);
    }
}


