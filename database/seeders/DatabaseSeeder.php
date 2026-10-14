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
        User::updateOrCreate(
            ['email' => 'sojoldev31@gmail.com'],
            [
                'name' => 'Sojol',
                'password' => Hash::make('12345678'),
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
    }
}
