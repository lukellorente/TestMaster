<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Get user profile
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Stats
        $stats = $user->stats ?? [
            'tests_created' => 0,
            'tests_completed' => 0,
            'tests_passed' => 0,
            'average_score' => 0,
            'perfect_scores' => 0,
            'current_streak' => 0,
        ];

        // Created tests
        $createdTests = Test::where('user_id', $user->id)
            ->where('is_public', true)
            ->where('status', 'published')
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => function ($q) {
                $q->completedExams();
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Completed tests
        $completedTests = TestAttempt::where('user_id', $user->id)
            ->completedExams()
            ->whereHas('test', function ($q) {
                $q->where('is_public', true);
            })
            ->with(['test:id,title', 'test.user:id,name'])
            ->orderBy('completed_at', 'desc')
            ->limit(20)
            ->get();

        // Recent activity
        $isOwnProfile = auth('sanctum')->check() && auth('sanctum')->id() === $user->id;

        // Minimización (RGPD art. 5.1.c): el historial de intentos solo lo ve su dueño.
        if (! $isOwnProfile) {
            $completedTests = collect();
        }

        $createdActivity = Test::where('user_id', $user->id)
            ->where('is_public', true)
            ->where('status', 'published')
            ->select('id', 'title', 'created_at')
            ->get()
            ->map(fn($t) => ['type' => 'test', 'title' => $t->title, 'date' => $t->created_at->toISOString(), 'id' => $t->id]);

        $attemptActivity = ! $isOwnProfile ? collect() : TestAttempt::where('user_id', $user->id)
            ->completedExams()
            ->whereHas('test', fn($q) => $q->where('is_public', true))
            ->with('test:id,title')
            ->select('id', 'test_id', 'completed_at')
            ->get()
            ->map(fn($a) => ['type' => 'attempt', 'title' => $a->test->title, 'date' => $a->completed_at->toISOString(), 'id' => $a->id]);

        $recentActivity = $createdActivity->concat($attemptActivity)
            ->sortByDesc('date')
            ->take(30)
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $isOwnProfile ? $user->email : null,
                'avatar' => $user->avatar,
                'bio' => $user->bio,
                'role' => $isOwnProfile ? $user->role : null,
                'created_at' => $user->created_at,
                'last_login' => $isOwnProfile ? $user->last_login : null,
            ],
            'stats' => $stats,
            'created_tests' => $createdTests,
            'completed_tests' => $completedTests,
            'recent_activity' => $recentActivity,
            'is_own_profile' => $isOwnProfile,
        ]);
    }

    /**
     * Update user avatar
     * 
     * Permite al usuario subir una imagen de perfil.
     * La imagen se almacena en storage/app/public/avatars y se devuelve la URL pública.
     * Si el usuario ya tenía un avatar, se elimina el archivo anterior.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:2048', // Máximo 2MB
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $disk = Storage::disk('public');

        // Asegurar directorio de avatares
        if (! $disk->exists('avatars')) {
            $disk->makeDirectory('avatars');
        }

        // Eliminar avatar anterior si existe y es un archivo local
        if ($user->avatar && str_starts_with($user->avatar, '/storage/avatars/')) {
            $oldPath = str_replace('/storage/', '', $user->avatar);
            if ($disk->exists($oldPath)) {
                $disk->delete($oldPath);
            }
        }

        try {
            // Guardar nueva imagen
            // Nunca usar la extensión enviada por el cliente: un PNG válido llamado "x.html"/"x.svg"
            // pasa la validación (que mira el contenido) y se serviría como HTML desde nuestro
            // dominio bajo /storage → XSS almacenado.
            $file = $request->file('avatar');
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => null,
            };
            if ($extension === null) {
                return response()->json(['message' => 'Formato de imagen no permitido.'], 422);
            }
            $filename = \Illuminate\Support\Str::random(40) . '.' . $extension;
            $path = $file->storeAs('avatars', $filename, 'public');
        } catch (\Throwable $e) {
            Log::error('Error uploading avatar', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'No se pudo guardar el avatar (revisa permisos de storage).',
            ], 500);
        }

        // Actualizar usuario con la nueva ruta del avatar
        $user->update([
            'avatar' => '/storage/' . $path,
        ]);

        return response()->json([
            'message' => 'Avatar actualizado correctamente',
            'avatar' => $user->avatar,
        ]);
    }

    /**
     * Delete user avatar
     * 
     * Elimina el avatar del usuario y restaura el avatar por defecto (inicial del nombre).
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        // Eliminar archivo si existe
        if ($user->avatar && str_starts_with($user->avatar, '/storage/avatars/')) {
            $path = str_replace('/storage/', '', $user->avatar);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        // Limpiar campo avatar
        $user->update(['avatar' => null]);

        return response()->json([
            'message' => 'Avatar eliminado correctamente',
        ]);
    }
}
