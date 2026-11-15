<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2: los intentos de "Repasar mis fallos" se guardan aparte de los exámenes.
 * No gastan intentos ni cuentan para la nota media ni las estadísticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->enum('mode', ['exam', 'review'])->default('exam')->after('test_id');
            $table->index(['user_id', 'test_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'test_id', 'mode']);
            $table->dropColumn('mode');
        });
    }
};
