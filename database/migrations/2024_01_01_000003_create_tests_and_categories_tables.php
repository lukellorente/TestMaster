<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 7)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->unsignedInteger('tests_count')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('password', 255)->nullable();
            $table->unsignedInteger('time_limit')->nullable();
            $table->integer('max_attempts')->default(-1);
            $table->integer('pass_score')->default(70);
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_answers')->default(false);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('show_explanations')->default(true);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('attempts_count')->default(0);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->timestamps();
            $table->timestamp('published_at')->nullable();

            $table->index('user_id');
            $table->index('is_public');
            $table->index('status');
            $table->index('difficulty');
        });

        Schema::create('test_categories', function (Blueprint $table) {
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['test_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_categories');
        Schema::dropIfExists('tests');
        Schema::dropIfExists('categories');
    }
};
