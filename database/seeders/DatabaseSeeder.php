<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStat;
use App\Models\UserPoint;
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
        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password_hash' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        UserStat::firstOrCreate(['user_id' => $testUser->id]);
        UserPoint::firstOrCreate(['user_id' => $testUser->id]);

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password_hash' => Hash::make('adminpassword'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        UserStat::firstOrCreate(['user_id' => $adminUser->id]);
        UserPoint::firstOrCreate(['user_id' => $adminUser->id]);

        // Ejecutar el seeder de preguntas y exámenes
        $this->call(QuestionSeeder::class);
    }
}
