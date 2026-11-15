<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Derechos del interesado (RGPD): acceso/portabilidad (arts. 15 y 20) y supresión (art. 17).
 */
class AccountController extends Controller
{
    /**
     * Exporta todos los datos personales del usuario en JSON.
     */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['stats', 'points']);

        $tests = Test::where('user_id', $user->id)
            ->with(['categories:id,name', 'questions.options'])
            ->get();

        $attempts = TestAttempt::where('user_id', $user->id)
            ->with(['test:id,title', 'responses'])
            ->orderBy('completed_at')
            ->get()
            ->makeHidden(['ip_address', 'user_agent']);

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'last_login' => $user->last_login,
                'privacy_accepted_at' => $user->privacy_accepted_at,
                'privacy_policy_version' => $user->privacy_policy_version,
            ],
            'stats' => $user->stats,
            'points' => $user->points,
            'tests_created' => $tests,
            'attempts' => $attempts,
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="testmaster-mis-datos.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Elimina la cuenta. Por defecto los tests públicos publicados se conservan
     * anonimizados; con delete_tests=true se borran también.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'delete_tests' => 'sometimes|boolean',
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'password' => ['La contraseña es incorrecta.'],
            ]);
        }

        $deleteAllTests = $request->boolean('delete_tests');

        DB::transaction(function () use ($user, $deleteAllTests) {
            // Los borradores y tests privados no tienen sentido sin dueño: se borran siempre.
            $testsToDelete = Test::where('user_id', $user->id)
                ->when(! $deleteAllTests, fn ($q) => $q->where(function ($q) {
                    $q->where('is_public', false)->orWhere('status', '!=', 'published');
                }))
                ->with('categories:id')
                ->get();

            foreach ($testsToDelete as $test) {
                $categoryIds = $test->categories->pluck('id')->all();
                if (! empty($categoryIds)) {
                    Category::whereIn('id', $categoryIds)->decrement('tests_count');
                }
                $test->delete();
            }

            if ($user->avatar && str_starts_with($user->avatar, '/storage/avatars/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }

            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            // Cascada en BD: estadísticas, puntos, intentos y respuestas.
            // Los tests conservados quedan con user_id = NULL ("Usuario eliminado").
            $user->delete();
        });

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Tu cuenta y tus datos personales se han eliminado.']);
    }
}
