<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->nullable()->after('question_id');
            $table->index(['attempt_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->dropIndex(['attempt_id', 'display_order']);
            $table->dropColumn('display_order');
        });
    }
};
