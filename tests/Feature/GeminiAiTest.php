<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AI\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('ai.gemini.api_key', 'test_dummy_gemini_key_12345');
        Config::set('ai.gemini.model', 'gemini-2.5-flash');
    }

    public function test_gemini_service_parses_natural_language_prompt(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'intent' => 'create_reminder',
                                        'conversational_reply' => 'Thik ache! Ami kal shokal 9 tay remind korbo.',
                                        'title' => 'Doctor meeting',
                                        'description' => null,
                                        'date' => '2026-10-15',
                                        'time' => '09:00:00',
                                        'priority' => 'high',
                                        'recurrence' => 'none',
                                        'detected_language' => 'banglish',
                                        'has_missing_info' => false,
                                        'missing_fields' => [],
                                        'clarification_question' => null,
                                        'confidence' => 0.98,
                                    ]),
                                ],
                            ],
                            'role' => 'model',
                        ],
                    ],
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 30,
                    'candidatesTokenCount' => 25,
                    'totalTokenCount' => 55,
                ],
            ], 200),
        ]);

        $service = new GeminiService('test_dummy_gemini_key_12345', 'gemini-2.5-flash');
        $result = $service->parseReminderPrompt('Kal shokal 9 tay doctor meeting', 'Asia/Dhaka');

        $this->assertTrue($result->success);
        $this->assertEquals('create_reminder', $result->intent);
        $this->assertEquals('Doctor meeting', $result->title);
        $this->assertEquals('2026-10-15', $result->date);
        $this->assertEquals('09:00:00', $result->time);
        $this->assertEquals('banglish', $result->detectedLanguage);
        $this->assertEquals('Thik ache! Ami kal shokal 9 tay remind korbo.', $result->conversationalReply);
    }

    public function test_gemini_service_test_connection(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Pong']]]],
                ],
            ], 200),
        ]);

        $service = new GeminiService('test_dummy_gemini_key_12345', 'gemini-2.5-flash');
        $testResult = $service->testConnection();

        $this->assertTrue($testResult['success']);
        $this->assertEquals('gemini', $testResult['provider']);
        $this->assertEquals('gemini-2.5-flash', $testResult['model']);
    }

    public function test_super_admin_can_update_ai_settings_and_switch_provider(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($admin)->postJson(route('admin.ai.update-settings'), [
            'active_provider' => 'gemini',
            'gemini_model' => 'gemini-2.5-flash',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'active_provider' => 'gemini',
            'active_model' => 'gemini-2.5-flash',
        ]);

        $this->assertEquals('gemini', SystemSetting::get('ai_active_provider'));
        $this->assertEquals('gemini-2.5-flash', SystemSetting::get('ai_gemini_model'));
    }

    public function test_super_admin_can_test_model_connectivity_from_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Pong']]]],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.ai.test-model'), [
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
        ]);
    }
}
