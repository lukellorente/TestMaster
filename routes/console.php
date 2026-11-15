<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Retención de datos (RGPD art. 5.1.e). Requiere que el scheduler esté en marcha
| (programa "scheduler" en supervisord: php artisan schedule:work).
*/

// Sesiones caducadas (contienen IP y user-agent).
Artisan::command('gdpr:prune-sessions', function () {
    $cutoff = now()->subMinutes((int) config('session.lifetime'))->getTimestamp();
    $deleted = DB::table('sessions')->where('last_activity', '<', $cutoff)->delete();
    $this->info("Sesiones caducadas eliminadas: {$deleted}");
})->purpose('Elimina sesiones caducadas');

// Tokens de restablecimiento de contraseña con más de 1 día.
Artisan::command('gdpr:prune-reset-tokens', function () {
    $deleted = DB::table('password_reset_tokens')->where('created_at', '<', now()->subDay())->delete()
        + DB::table('password_resets')->where('expires_at', '<', now())->delete();
    $this->info("Tokens de reseteo eliminados: {$deleted}");
})->purpose('Elimina tokens de reseteo caducados');

Schedule::command('gdpr:prune-sessions')->hourly();
Schedule::command('gdpr:prune-reset-tokens')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
// Los logs rotan a diario y se conservan LOG_DAILY_DAYS (30) días: LOG_STACK=daily.
