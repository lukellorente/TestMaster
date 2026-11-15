<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1: nuevos tipos de pregunta, puntuación parcial y penalización por fallo.
 */
return new class extends Migration
{
    private const TYPES = [
        'multiple_choice', 'multiple_select', 'true_false', 'short_answer',
        'fill_blank', 'order_items', 'numeric',
    ];

    private const LEGACY_TYPES = [
        'multiple_choice', 'true_false', 'short_answer',
        'fill_blank', 'drag_drop', 'order_items', 'numeric', 'image_hotspot',
    ];

    public function up(): void
    {
        // drag_drop e image_hotspot nunca tuvieron editor ni corrección: no puede haber filas válidas.
        DB::table('questions')->whereIn('type', ['drag_drop', 'image_hotspot'])->delete();

        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
            // Pregunta numérica: respuesta y margen aceptado (±).
            $table->decimal('numeric_answer', 20, 6)->nullable()->after('points');
            $table->decimal('numeric_tolerance', 20, 6)->nullable()->after('numeric_answer');
        });

        // Fracción de los puntos de la pregunta que resta un fallo (0, 0.25, 0.3333, 0.5).
        Schema::table('tests', function (Blueprint $table) {
            $table->decimal('wrong_penalty', 5, 4)->default(0)->after('pass_score');
        });

        // Puntuación parcial y penalizaciones necesitan decimales.
        Schema::table('test_attempts', function (Blueprint $table) {
            $table->decimal('points_earned', 8, 2)->default(0)->change();
        });

        Schema::table('question_responses', function (Blueprint $table) {
            $table->decimal('points_earned', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('question_responses', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->change();
        });

        Schema::table('test_attempts', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->change();
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('wrong_penalty');
        });

        // multiple_select no existe en el enum anterior.
        DB::table('questions')->where('type', 'multiple_select')->delete();

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['numeric_answer', 'numeric_tolerance']);
            $table->enum('type', self::LEGACY_TYPES)->change();
        });
    }
};
