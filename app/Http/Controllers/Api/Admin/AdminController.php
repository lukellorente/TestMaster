<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Panel de administración: métricas, moderación de tests y gestión de usuarios.
 * Todas las rutas pasan por el middleware `admin`; cada cambio queda en el registro.
 */
class AdminController extends Controller
{
    // ─── Métricas ────────────────────────────────────────────────────────────

    public function metrics(): JsonResponse
    {
        $since = CarbonImmutable::now()->startOfDay()->subDays(29);
        $completed = TestAttempt::where('is_completed', true);

        $perDay = fn ($dates) => collect($dates)
            ->countBy(fn ($at) => CarbonImmutable::parse($at)->toDateString())
            ->all();

        $attemptsByDay = $perDay((clone $completed)->where('completed_at', '>=', $since)->pluck('completed_at'));
        $signupsByDay = $perDay(User::where('created_at', '>=', $since)->pluck('created_at'));

        $series = [];
        for ($day = $since; $day->lte(CarbonImmutable::now()); $day = $day->addDay()) {
            $key = $day->toDateString();
            $series[] = ['date' => $key, 'attempts' => $attemptsByDay[$key] ?? 0, 'signups' => $signupsByDay[$key] ?? 0];
        }

        $testsByStatus = Test::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return response()->json([
            'users' => [
                'total' => User::count(),
                'new_30d' => User::where('created_at', '>=', $since)->count(),
                'active_7d' => (clone $completed)->where('completed_at', '>=', now()->subDays(7))->distinct()->count('user_id'),
                'suspended' => User::whereNotNull('suspended_at')->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
            'tests' => [
                'published' => (int) ($testsByStatus['published'] ?? 0),
                'draft' => (int) ($testsByStatus['draft'] ?? 0),
                'archived' => (int) ($testsByStatus['archived'] ?? 0),
                'featured' => Test::where('is_featured', true)->count(),
            ],
            'attempts' => [
                'total' => (clone $completed)->count(),
                'last_30d' => (clone $completed)->where('completed_at', '>=', $since)->count(),
                'reviews_30d' => (clone $completed)->where('completed_at', '>=', $since)->where('mode', TestAttempt::MODE_REVIEW)->count(),
            ],
            'series' => $series,
            'timezone' => config('app.timezone'),
        ]);
    }

    // ─── Tests ───────────────────────────────────────────────────────────────

    public function tests(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,published,archived',
            'featured' => 'nullable|boolean',
        ]);

        $page = Test::with('user:id,name')
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => fn ($q) => $q->completedExams()])
            ->addSelect(DB::raw('(tests.password IS NOT NULL) as has_password'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(fn ($q) => $q->where('title', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('featured'), fn ($q) => $q->where('is_featured', $request->boolean('featured')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($page);
    }

    public function updateTest(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,published,archived',
        ]);

        $test = Test::findOrFail($id);
        $admin = $request->user();

        DB::transaction(function () use ($test, $data, $admin) {
            if (isset($data['status']) && $data['status'] !== $test->status) {
                AdminAuditLog::record($admin, 'test.status', 'test', $test->id, ['from' => $test->status, 'to' => $data['status']]);
                $test->status = $data['status'];
                if ($data['status'] === 'published') {
                    $test->published_at ??= now();
                }
                // Un test que deja de estar publicado no puede seguir destacado.
                if ($data['status'] !== 'published' && $test->is_featured) {
                    $test->is_featured = false;
                    $test->featured_at = null;
                    AdminAuditLog::record($admin, 'test.unfeatured', 'test', $test->id, ['reason' => 'status']);
                }
            }

            if (array_key_exists('is_featured', $data) && (bool) $data['is_featured'] !== $test->is_featured) {
                if ($data['is_featured'] && ! ($test->is_public && $test->status === 'published')) {
                    throw ValidationException::withMessages([
                        'is_featured' => 'Solo se pueden destacar tests públicos y publicados.',
                    ]);
                }
                $test->is_featured = (bool) $data['is_featured'];
                $test->featured_at = $data['is_featured'] ? now() : null;
                AdminAuditLog::record($admin, $data['is_featured'] ? 'test.featured' : 'test.unfeatured', 'test', $test->id);
            }

            $test->save();
        });

        return response()->json(['message' => 'Test actualizado.', 'test' => $test->fresh()]);
    }

    public function destroyTest(Request $request, int $id): JsonResponse
    {
        $test = Test::findOrFail($id);

        DB::transaction(function () use ($request, $test) {
            AdminAuditLog::record($request->user(), 'test.deleted', 'test', $test->id, ['title' => $test->title]);
            $test->deleteWithCounters();
        });

        return response()->json(['message' => 'Test eliminado.']);
    }

    // ─── Usuarios ────────────────────────────────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'role' => 'nullable|in:user,premium,educator,admin',
            'status' => 'nullable|in:active,suspended',
        ]);

        $page = User::query()
            ->select('id', 'name', 'email', 'role', 'avatar', 'suspended_at', 'created_at', 'last_login')
            ->withCount('tests')
            ->withCount(['attempts as completed_attempts_count' => fn ($q) => $q->completedExams()])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->search.'%';
                $q->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->role))
            ->when($request->status === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($request->status === 'active', fn ($q) => $q->whereNull('suspended_at'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($page);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'role' => 'sometimes|in:user,admin',
            'suspended' => 'sometimes|boolean',
        ]);

        $admin = $request->user();
        $user = User::findOrFail($id);

        // Un admin no puede quitarse el rol ni suspenderse: siempre queda al menos uno con acceso.
        if ((int) $user->id === (int) $admin->id) {
            throw ValidationException::withMessages([
                'user' => 'No puedes cambiar tu propio rol ni suspender tu cuenta.',
            ]);
        }

        $willBeAdmin = ($data['role'] ?? $user->role) === 'admin';
        if (! empty($data['suspended']) && $willBeAdmin) {
            throw ValidationException::withMessages([
                'suspended' => 'Quita primero el rol de administrador para suspender esta cuenta.',
            ]);
        }

        DB::transaction(function () use ($user, $data, $admin) {
            if (isset($data['role']) && $data['role'] !== $user->role) {
                AdminAuditLog::record($admin, 'user.role', 'user', $user->id, ['from' => $user->role, 'to' => $data['role']]);
                $user->role = $data['role'];
            }

            if (array_key_exists('suspended', $data) && (bool) $data['suspended'] !== $user->isSuspended()) {
                if ($data['suspended']) {
                    $user->suspended_at = now();
                    // Cierra todas sus sesiones abiertas.
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                    $user->tokens()->delete();
                    AdminAuditLog::record($admin, 'user.suspended', 'user', $user->id);
                } else {
                    $user->suspended_at = null;
                    AdminAuditLog::record($admin, 'user.reactivated', 'user', $user->id);
                }
            }

            $user->save();
        });

        return response()->json([
            'message' => 'Usuario actualizado.',
            'user' => $user->only('id', 'name', 'email', 'role', 'suspended_at'),
        ]);
    }

    // ─── Registro ────────────────────────────────────────────────────────────

    public function audit(Request $request): JsonResponse
    {
        $request->validate(['target_type' => 'nullable|in:test,user,category']);

        $page = AdminAuditLog::with('admin:id,name')
            ->when($request->filled('target_type'), fn ($q) => $q->where('target_type', $request->target_type))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30);

        return response()->json($page);
    }
}
