<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * En los tests la sesión se comparte entre peticiones. `AuthenticateSession`
     * (Sanctum SPA) guarda en ella el hash de la contraseña del usuario y, si en la
     * siguiente petición actúa otro usuario, cierra la sesión y responde 401.
     *
     * Al cambiar de usuario se olvidan los guards y las claves de autenticación de la
     * sesión (el resto, como un test desbloqueado, se conserva). En la app real cada
     * usuario tiene su propia sesión, así que esto solo afecta a los tests.
     */
    public function be(UserContract $user, $guard = null)
    {
        $this->app['auth']->forgetGuards();

        if ($this->app->bound('session.store')) {
            $session = $this->app['session.store'];
            foreach (array_keys($session->all()) as $key) {
                if (Str::startsWith($key, ['password_hash_', 'login_'])) {
                    $session->forget($key);
                }
            }
        }

        return parent::be($user, $guard);
    }
}
