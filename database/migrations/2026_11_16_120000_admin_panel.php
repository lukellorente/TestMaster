<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 5: suspensión de cuentas, tests destacados y registro de acciones de administración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('last_login');
        });

        // Orden de los destacados en la Home: el más reciente primero.
        Schema::table('tests', function (Blueprint $table) {
            $table->timestamp('featured_at')->nullable()->after('is_featured');
        });

        // Quién hizo qué y cuándo. Sin datos personales en `details`: solo ids y cambios.
        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('featured_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('suspended_at');
        });
    }
};
