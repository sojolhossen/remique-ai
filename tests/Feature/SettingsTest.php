<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_settings(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Profile & Preferences', false);
    }

    public function test_user_can_update_profile_and_preferences(): void
    {
        $user = User::factory()->create([
            'timezone' => 'UTC',
            'preferred_language' => 'en',
            'theme' => 'light',
        ]);

        $response = $this->actingAs($user)->put('/settings', [
            'name' => 'Jane SuperUser',
            'timezone' => 'Asia/Dhaka',
            'preferred_language' => 'bn',
            'theme' => 'dark',
            'notification_email' => 1,
            'notification_sound' => 0,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertEquals('Jane SuperUser', $user->name);
        $this->assertEquals('Asia/Dhaka', $user->timezone);
        $this->assertEquals('bn', $user->preferred_language);
        $this->assertEquals('dark', $user->theme);
        $this->assertTrue($user->notification_preferences['email']);
        $this->assertFalse($user->notification_preferences['sound']);
    }

    public function test_user_cannot_update_with_invalid_timezone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/settings', [
            'name' => 'Jane Doe',
            'timezone' => 'Invalid/Fake_Zone',
            'preferred_language' => 'en',
            'theme' => 'light',
        ]);

        $response->assertSessionHasErrors('timezone');
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'NewSecurePassword456!',
            'password_confirmation' => 'NewSecurePassword456!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword456!', $user->password));
    }

    public function test_user_cannot_update_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/settings/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewSecurePassword456!',
            'password_confirmation' => 'NewSecurePassword456!',
        ]);

        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $user->password));
    }
}
