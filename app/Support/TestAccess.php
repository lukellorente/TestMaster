<?php

namespace App\Support;

use App\Models\Test;
use App\Models\User;
use Illuminate\Contracts\Session\Session;

/**
 * Quién puede ver, gestionar y hacer un test.
 *
 * Un test con contraseña se puede ver en el catálogo (título y descripción), pero
 * para hacerlo hay que desbloquearlo. El desbloqueo dura lo que la sesión y se pierde
 * si el autor cambia la contraseña. El autor y los admins nunca la necesitan.
 */
final class TestAccess
{
    private const SESSION_KEY = 'unlocked_tests';

    public static function canView(Test $test, ?User $user): bool
    {
        if ($test->is_public && $test->status === 'published') {
            return true;
        }

        return self::canManage($test, $user);
    }

    public static function canManage(Test $test, ?User $user): bool
    {
        return $user !== null && ((int) $user->id === (int) $test->user_id || $user->isAdmin());
    }

    /** ¿Tiene que introducir la contraseña para hacer el test? */
    public static function isLocked(Test $test, ?User $user, ?Session $session): bool
    {
        if (! $test->hasPassword() || self::canManage($test, $user)) {
            return false;
        }

        return ! $session || $session->get(self::SESSION_KEY.'.'.$test->id) !== self::fingerprint($test);
    }

    public static function unlock(Test $test, Session $session): void
    {
        $session->put(self::SESSION_KEY.'.'.$test->id, self::fingerprint($test));
    }

    /** Cambia con la contraseña: así un cambio vuelve a bloquear las sesiones ya desbloqueadas. */
    private static function fingerprint(Test $test): string
    {
        return substr(hash('sha256', (string) ($test->getAttributes()['password'] ?? '')), 0, 16);
    }
}
