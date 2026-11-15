<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cómo se presentan las preguntas al hacer el test:
     *  - paged: una pregunta por pantalla (por defecto, también para los tests existentes)
     *  - all:   todas las preguntas en una sola página
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->enum('display_mode', ['paged', 'all'])->default('paged')->after('show_explanations');
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('display_mode');
        });
    }
};
