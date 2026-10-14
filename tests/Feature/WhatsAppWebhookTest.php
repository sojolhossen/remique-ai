<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppIdentity;
use App\Models\WhatsAppMessage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('whatsapp.verify_token', 'test_verify_token_123');
        Config::set('whatsapp.app_secret', 'test_secret_abc_456');
        Config::set('whatsapp.phone_number_id', '1234567890');
        Config::set('whatsapp.access_token', 'test_access_token');
    }

    public function test_get_webhook_verification_succeeds_with_valid_token(): void
    {
        $response = $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=test_verify_token_123&hub_challenge=challenge_token_xyz');

        $response->assertStatus(200);
        $this->assertEquals('challenge_token_xyz', $response->getContent());
    }

    public function test_get_webhook_verification_fails_with_invalid_token(): void
    {
        $response = $this->get('/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=challenge_token_xyz');

        $response->assertStatus(403);
    }

    public function test_post_webhook_rejects_invalid_signature(): void
    {
        $payload = json_encode(['entry' => []]);

        $response = $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid_signature_hash',
            ],
            $payload
        );

        $response->assertStatus(401);
    }

    public function test_post_webhook_accepts_valid_signature(): void
    {
        $payload = json_encode([
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test_secret_abc_456');

        $response = $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(200);
        $this->assertEquals('EVENT_RECEIVED', $response->getContent());
    }

    public function test_unknown_sender_receives_onboarding_and_cannot_access_data(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.outbound123']]], 200),
        ]);

        $payload = json_encode([
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.inbound_unknown_1',
                                        'from' => '8801999999999',
                                        'type' => 'text',
                                        'text' => ['body' => 'Show my reminders'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test_secret_abc_456');

        $response = $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $response->assertStatus(200);

        // Assert message was logged as unknown sender
        $this->assertDatabaseHas('whatsapp_messages', [
            'wamid' => 'wamid.inbound_unknown_1',
            'from_number' => '+8801999999999',
            'whatsapp_identity_id' => null,
        ]);

        // Assert outbound reply was sent informing user they are unauthorized
        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'not yet authorized');
        });
    }

    public function test_pending_customer_verifies_with_valid_otp(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.outbound123']]], 200),
        ]);

        $user = User::factory()->create();
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '+8801700000000',
            'status' => 'pending_verification',
            'verification_token' => 'OTP123',
            'verification_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $payload = json_encode([
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.inbound_otp_1',
                                        'from' => '8801700000000',
                                        'type' => 'text',
                                        'text' => ['body' => 'VERIFY OTP123'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test_secret_abc_456');

        $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        $identity->refresh();
        $this->assertEquals('active', $identity->status);
        $this->assertNotNull($identity->verified_at);
        $this->assertNull($identity->verification_token);

        Http::assertSent(function ($request) {
            return str_contains($request->body(), 'now verified');
        });
    }

    public function test_duplicate_webhook_events_are_deduplicated(): void
    {
        WhatsAppMessage::create([
            'wamid' => 'wamid.already_exists_123',
            'direction' => 'inbound',
            'from_number' => '+8801711111111',
            'to_number' => '1234567890',
            'message_type' => 'text',
            'body' => 'Test',
            'status' => 'received',
        ]);

        Http::fake();

        $payload = json_encode([
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'id' => 'wamid.already_exists_123',
                                        'from' => '8801711111111',
                                        'type' => 'text',
                                        'text' => ['body' => 'Test'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test_secret_abc_456');

        $this->call(
            'POST',
            '/whatsapp/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $payload
        );

        // No new outbound requests sent because duplicate was ignored
        Http::assertNothingSent();
    }
}
