<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación SPA con Sanctum: la sesión vive en una cookie httpOnly
 * (no accesible desde JavaScript) y las peticiones mutantes llevan token CSRF.
 */
class AuthController extends Controller
{
    /** Versión vigente de la Política de Privacidad / Términos. Súbela al cambiar los textos legales. */
    public const PRIVACY_POLICY_VERSION = '2026-11-15';

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            // Casilla obligatoria: ≥14 años (LOPDGDD art. 7) + aceptación de Política y Términos.
            'accept_terms' => 'accepted',
        ], [
            'accept_terms.accepted' => 'Debes confirmar que tienes al menos 14 años y aceptar la Política de Privacidad y los Términos.',
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'email_verified_at' => now(),
                'privacy_accepted_at' => now(),
                'privacy_policy_version' => self::PRIVACY_POLICY_VERSION,
            ]);

            UserStat::create(['user_id' => $user->id]);
            UserPoint::create(['user_id' => $user->id]);

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'user' => $this->formatUser($user),
            'message' => 'Cuenta creada exitosamente.',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Email o contraseña incorrectos.'],
            ]);
        }

        Auth::guard('web')->login($user);
        // Evita fijación de sesión.
        $request->session()->regenerate();

        $user->update(['last_login' => now()]);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['stats', 'points']);

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        if (! Hash::check($request->current_password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        // Cierra el resto de sesiones y revoca cualquier token restante.
        $request->session()->regenerate();
        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'last_login' => $user->last_login,
        ];
    }
}
