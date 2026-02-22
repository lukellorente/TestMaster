<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'multiple_choice', 'true_false', 'short_answer',
                'fill_blank', 'drag_drop', 'order_items', 'numeric', 'image_hotspot'
            ]);
            $table->text('content');
            $table->text('explanation')->nullable();
            $table->integer('points')->default(1);
            $table->integer('order_index')->default(0);
            $table->string('image_url', 255)->nullable();
            $table->string('audio_url', 255)->nullable();
            $table->string('video_url', 255)->nullable();
            $table->unsignedInteger('time_limit')->nullable();
            $table->timestamps();

            $table->index('test_id');
            $table->index('type');
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_correct')->default(false);
            $table->integer('order_index')->default(0);
            $table->string('image_url', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('question_blanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->integer('blank_index');
            $table->json('correct_answers');
            $table->boolean('case_sensitive')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('question_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->integer('correct_order');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('question_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('times_answered')->default(0);
            $table->unsignedInteger('times_correct')->default(0);
            $table->unsignedInteger('times_incorrect')->default(0);
            $table->decimal('difficulty_score', 5, 2)->default(0);
            $table->unsignedInteger('average_time')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_stats');
        Schema::dropIfExists('question_order_items');
        Schema::dropIfExists('question_blanks');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
