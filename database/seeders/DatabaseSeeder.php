<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserStat;
use App\Models\UserPoint;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password'),
        ]);
        UserStat::create(['user_id' => $testUser->id]);
        UserPoint::create(['user_id' => $testUser->id]);

        $adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password_hash' => bcrypt('adminpassword'),
            'role' => 'admin',
        ]);
        UserStat::create(['user_id' => $adminUser->id]);
        UserPoint::create(['user_id' => $adminUser->id]);

        // Ejecutar el seeder de preguntas y exámenes
        $this->call(QuestionSeeder::class);
    }
}
