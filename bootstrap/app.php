<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La app solo es accesible a través de Caddy (red interna de Docker).
        $middleware->trustProxies('*');

        // Autenticación SPA de Sanctum: sesión en cookie httpOnly + protección CSRF.
        $middleware->statefulApi();

        $middleware->throttleApi('api');

        // Una cuenta suspendida pierde el acceso aunque tenga la sesión abierta.
        $middleware->appendToGroup('api', \App\Http\Middleware\EnsureAccountActive::class);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
