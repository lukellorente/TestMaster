<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('completions_count')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->unsignedInteger('average_time')->default(0);
            $table->decimal('pass_rate', 5, 2)->default(0);
            $table->decimal('difficulty_rating', 3, 2)->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index('test_id');
            $table->index('user_id');
            $table->index('parent_id');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->timestamps();

            $table->unique(['user_id', 'test_id']);
        });

        Schema::create('follows', function (Blueprint $table) {
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['follower_id', 'following_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'test_comment', 'new_follower', 'badge_earned',
                'group_invite', 'test_shared', 'system'
            ]);
            $table->string('title', 255);
            $table->text('content')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('is_read');
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reportable_type', ['test', 'comment', 'user']);
            $table->unsignedBigInteger('reportable_id');
            $table->enum('reason', ['spam', 'inappropriate', 'copyright', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'resolved', 'dismissed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('follows');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('test_stats');
    }
};
