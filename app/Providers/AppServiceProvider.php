<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límite general de la API.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Login/registro: frena fuerza bruta y relleno de credenciales.
        RateLimiter::for('auth', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('auth-email:' . $email . '|' . $request->ip()),
                Limit::perMinute(20)->by('auth-ip:' . $request->ip()),
            ];
        });

        // Contraseña de un test: frena la fuerza bruta por usuario y test.
        RateLimiter::for('unlock', function (Request $request) {
            return Limit::perMinute(5)->by('unlock:' . ($request->user()?->id ?: $request->ip()) . ':' . $request->route('id'));
        });

        // Operaciones sensibles de cuenta (cambio de contraseña, borrado, exportación).
        RateLimiter::for('sensitive', function (Request $request) {
            return Limit::perMinute(5)->by('sensitive:' . ($request->user()?->id ?: $request->ip()));
        });
    }
}
