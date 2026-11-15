<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Prueba del consentimiento (RGPD art. 7.1): cuándo y qué versión de la política se aceptó.
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_accepted_at')->nullable()->after('email_verified_at');
            $table->string('privacy_policy_version', 20)->nullable()->after('privacy_accepted_at');
        });

        // Al borrar una cuenta, sus tests pueden conservarse anonimizados (autor = NULL).
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Minimización: IP y user-agent por intento no son necesarios para la finalidad.
        DB::table('test_attempts')->update(['ip_address' => null, 'user_agent' => null]);

        // Se pasa de tokens Bearer a sesión en cookie: los tokens antiguos se revocan.
        DB::table('personal_access_tokens')->delete();
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::table('tests')->whereNull('user_id')->delete();
        Schema::table('tests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacy_accepted_at', 'privacy_policy_version']);
        });
    }
};
