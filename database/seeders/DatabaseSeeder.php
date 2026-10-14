<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'sojoldev31@gmail.com'],
            [
                'name' => 'Sojol',
                'password' => Hash::make('12345678'),
                'role' => 'super_admin',
                'status' => 'active',
                'timezone' => 'Asia/Dhaka',
                'preferred_language' => 'en',
                'theme' => 'light',
                'notification_preferences' => [
                    'email' => true,
                    'sound' => true,
                ],
            ]
        );

        // Seed default approved WhatsApp reminder template
        \App\Models\WhatsAppTemplate::updateOrCreate(
            ['name' => 'remi_reminder_alert'],
            [
                'category' => 'UTILITY',
                'language' => 'en_US',
                'status' => 'APPROVED',
                'body_text' => 'Hello {{1}}, this is your Remique reminder: {{2}} scheduled for {{3}}.',
                'parameters' => ['user_name', 'reminder_title', 'formatted_due_time'],
            ]
        );

        // Seed initial audit log for system seeding
        \App\Models\AuditLog::firstOrCreate(
            ['action' => 'system.initialized'],
            [
                'user_id' => $admin->id,
                'target_type' => 'System',
                'target_id' => 1,
                'reason' => 'Application seeded with Super Admin and default template',
                'ip_address' => '127.0.0.1',
                'metadata' => ['seeded' => true],
                'created_at' => now(),
            ]
        );
    }
}
