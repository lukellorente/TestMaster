<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('points')->default(0);
            $table->integer('level')->default(1);
            $table->integer('experience')->default(0);
            $table->integer('streak_days')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->timestamps();
        });

        Schema::create('user_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tests_created')->default(0);
            $table->unsignedInteger('tests_completed')->default(0);
            $table->unsignedInteger('tests_passed')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->unsignedInteger('total_study_time')->default(0);
            $table->unsignedInteger('perfect_scores')->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->integer('total_points')->default(0);
            $table->unsignedInteger('badges_earned')->default(0);
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stats');
        Schema::dropIfExists('user_points');
    }
};
