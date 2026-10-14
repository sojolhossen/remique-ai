<?php

namespace Tests\Feature;

use App\Models\AiInteractionLog;
use App\Models\User;
use App\Services\AI\NvidiaNimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NvidiaNimAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('nvidia.api_key', 'test_dummy_nvidia_key_12345');
        Config::set('nvidia.base_url', 'https://integrate.api.nvidia.com/v1');
        Config::set('nvidia.model', 'openai/gpt-oss-20b');
    }

    public function test_ai_endpoint_requires_authentication(): void
    {
        $response = $this->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9am to buy milk',
        ]);

        $response->assertStatus(401);
    }

    public function test_can_interpret_bengali_prompt(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'create_reminder',
                                'title' => 'ডাক্তার দেখানো',
                                'description' => null,
                                'date' => '2026-10-15',
                                'time' => '09:00:00',
                                'priority' => 'high',
                                'recurrence' => 'none',
                                'detected_language' => 'bengali',
                                'has_missing_info' => false,
                                'missing_fields' => [],
                                'clarification_question' => null,
                                'confidence' => 0.98,
                            ]),
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 120,
                    'completion_tokens' => 50,
                    'total_tokens' => 170,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'আগামীকাল সকাল ৯টায় আমাকে ডাক্তার দেখানোর কথা মনে করিয়ে দিও।',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'title' => 'ডাক্তার দেখানো',
            'date' => '2026-10-15',
            'time' => '09:00:00',
            'detected_language' => 'bengali',
            'has_missing_info' => false,
        ]);

        // Verify privacy-conscious log entry (no user prompt retained)
        $this->assertDatabaseHas('ai_interaction_logs', [
            'user_id' => $user->id,
            'provider' => 'nvidia_nim',
            'model' => 'openai/gpt-oss-20b',
            'status' => 'success',
            'detected_language' => 'bengali',
            'total_tokens' => 170,
        ]);
    }

    public function test_can_interpret_banglish_prompt(): void
    {
        $user = User::factory()->create(['timezone' => 'Asia/Dhaka']);

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'create_reminder',
                                'title' => 'Doctor Appointment',
                                'description' => null,
                                'date' => '2026-10-15',
                                'time' => '09:00:00',
                                'priority' => 'medium',
                                'recurrence' => 'none',
                                'detected_language' => 'banglish',
                                'has_missing_info' => false,
                                'missing_fields' => [],
                                'clarification_question' => null,
                                'confidence' => 0.95,
                            ]),
                        ],
                    ],
                ],
                'usage' => ['total_tokens' => 110],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'kal shokal 9 tay amake doctor er kotha mone koriye dio.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'title' => 'Doctor Appointment',
            'detected_language' => 'banglish',
        ]);
    }

    public function test_can_interpret_english_prompt(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'create_reminder',
                                'title' => 'Call the client',
                                'description' => null,
                                'date' => '2026-10-15',
                                'time' => '09:00:00',
                                'priority' => 'high',
                                'recurrence' => 'none',
                                'detected_language' => 'english',
                                'has_missing_info' => false,
                                'missing_fields' => [],
                                'clarification_question' => null,
                                'confidence' => 0.99,
                            ]),
                        ],
                    ],
                ],
                'usage' => ['total_tokens' => 95],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9 AM to call the client.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'title' => 'Call the client',
            'detected_language' => 'english',
            'date' => '2026-10-15',
            'time' => '09:00:00',
        ]);
    }

    public function test_handles_ambiguous_prompt_with_clarification_question(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'intent' => 'create_reminder',
                                'title' => 'Call John',
                                'description' => null,
                                'date' => null,
                                'time' => null,
                                'priority' => 'medium',
                                'recurrence' => 'none',
                                'detected_language' => 'english',
                                'has_missing_info' => true,
                                'missing_fields' => ['date', 'time'],
                                'clarification_question' => 'When would you like to be reminded to call John?',
                                'confidence' => 0.70,
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me to call John',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'has_missing_info' => true,
            'missing_fields' => ['date', 'time'],
            'clarification_question' => 'When would you like to be reminded to call John?',
        ]);
    }

    public function test_handles_rate_limit_429_gracefully(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'error' => 'Rate limit exceeded',
            ], 429),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9 AM',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_category' => 'rate_limit',
        ]);

        $this->assertDatabaseHas('ai_interaction_logs', [
            'status' => 'failed',
            'error_category' => 'rate_limit',
        ]);
    }

    public function test_handles_provider_error_500_gracefully(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response('Internal Server Error', 500),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9 AM',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_category' => 'provider_error',
        ]);
    }

    public function test_handles_malformed_llm_output_gracefully(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://integrate.api.nvidia.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Sorry, as an AI I am unable to process this request.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9 AM',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_category' => 'malformed_json',
        ]);
    }

    public function test_handles_missing_api_key_gracefully(): void
    {
        Config::set('nvidia.api_key', '');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('reminders.ai-interpret'), [
            'prompt' => 'Remind me tomorrow at 9 AM',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_category' => 'missing_api_key',
        ]);
    }
}
