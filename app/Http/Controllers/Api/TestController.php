<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Category;
use App\Models\UserStat;
use App\Models\User;
use App\Questions\QuestionTypes;
use App\Questions\ReviewQueue;
use App\Support\TestAccess;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    private function canViewTest(Test $test): bool
    {
        return TestAccess::canView($test, auth('sanctum')->user());
    }

    private function canManageTest(?User $user, Test $test): bool
    {
        return TestAccess::canManage($test, $user);
    }

    /** Respuesta para un test con contraseña que aún no se ha desbloqueado en esta sesión. */
    private function lockedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Este test está protegido con contraseña.',
            'locked' => true,
        ], 423);
    }

    /**
     * Contraseña desde el editor: ausente = no cambia; null o '' = se quita; texto = se pone.
     */
    private function passwordFromRequest(Request $request, ?Test $test = null): ?string
    {
        if (! $request->has('password')) {
            return $test?->getAttributes()['password'] ?? null;
        }

        $plain = (string) $request->input('password', '');

        return $plain === '' ? null : Hash::make($plain);
    }

    /**
     * List public published tests (homepage)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Test::with(['user:id,name,avatar', 'categories:id,name,icon'])
            ->where('is_public', true)
            ->where('status', 'published')
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => function ($q) {
                $q->completedExams();
            }])
            ->addSelect(DB::raw('(tests.password IS NOT NULL) as has_password'));

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        // Filter by difficulty
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->get('sort', 'recent');
        match ($sort) {
            'views' => $query->orderBy('views_count', 'desc'),
            // Order by the computed completed attempts count (withCount alias)
            'attempts' => $query->orderBy('completed_attempts_count', 'desc'),
            'longest' => $query->orderBy('questions_count', 'desc')->orderBy('created_at', 'desc'),
            'shortest' => $query->orderBy('questions_count', 'asc')->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = min(max((int) $request->get('per_page', 20), 1), 50);
        $tests = $query->paginate($perPage);

        return response()->json($tests);
    }

    /**
     * Tests destacados por la administración, el más reciente primero.
     */
    public function featured(): JsonResponse
    {
        $tests = Test::with(['user:id,name,avatar', 'categories:id,name,icon'])
            ->where('is_featured', true)
            ->where('is_public', true)
            ->where('status', 'published')
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => fn ($q) => $q->completedExams()])
            ->addSelect(DB::raw('(tests.password IS NOT NULL) as has_password'))
            ->orderByDesc('featured_at')
            ->limit(6)
            ->get();

        return response()->json($tests);
    }

    /**
     * Get general stats for homepage
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_tests' => Test::where('is_public', true)->where('status', 'published')->count(),
            'total_users' => \App\Models\User::count(),
            'total_attempts' => \App\Models\TestAttempt::completedExams()->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Show a single test
     */
    public function show(int $id): JsonResponse
    {
        $test = Test::with(['user:id,name,avatar', 'categories:id,name,icon'])
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => function ($q) {
                $q->completedExams();
            }])
            ->findOrFail($id);

        if (! $this->canViewTest($test)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        // Average score
        $avgScore = $test->attempts()
            ->completedExams()
            ->avg('score');

        $data = $test->toArray();
        $data['avg_score'] = $avgScore;

        // If user is authenticated, get their attempt info
        $authUser = auth('sanctum')->user();
        $data['can_manage'] = $this->canManageTest($authUser, $test);
        $data['has_password'] = $test->hasPassword();
        $data['locked'] = TestAccess::isLocked($test, $authUser, request()->hasSession() ? request()->session() : null);
        if ($authUser) {
            $userId = $authUser->id;
            $data['user_attempts'] = $test->attempts()
                ->where('user_id', $userId)
                ->completedExams()
                ->count();

            $data['last_attempt'] = $test->attempts()
                ->where('user_id', $userId)
                ->completedExams()
                ->orderBy('completed_at', 'desc')
                ->first(['id', 'score', 'completed_at']);

            $data['pending_review_count'] = app(ReviewQueue::class)->pendingCount($userId, $test->id);

            // Evolución de tus notas en este test (solo exámenes, los 30 últimos en orden cronológico).
            $data['score_history'] = $test->attempts()
                ->where('user_id', $userId)
                ->completedExams()
                ->orderByDesc('completed_at')
                ->limit(30)
                ->get(['id', 'score', 'passed', 'completed_at'])
                ->reverse()
                ->values()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'score' => round((float) $a->score, 1),
                    'passed' => (bool) $a->passed,
                    'completed_at' => $a->completed_at?->toIso8601String(),
                ]);
        }

        // Increment views
        $test->increment('views_count');

        return response()->json($data);
    }

    /**
     * Create a new test
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'difficulty' => 'in:easy,medium,hard',
            'time_limit' => 'nullable|integer|min:0',
            'max_attempts' => 'integer',
            'pass_score' => 'integer|min:0|max:100',
            'is_public' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_correct_answers' => 'boolean',
            'show_explanations' => 'boolean',
            'display_mode' => 'in:paged,all',
            'status' => 'in:draft,published',
            'questions' => 'required|array|min:1',
            'wrong_penalty' => 'numeric|min:0|max:1',
            'password' => 'nullable|string|min:4|max:100',
            'questions.*.type' => ['required', Rule::in(QuestionTypes::keys())],
            'questions.*.content' => 'required|string',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'integer|min:1',
        ]);

        $this->validateQuestions($request->questions);

        return DB::transaction(function () use ($request, $user) {
            $timeLimitSeconds = ($request->time_limit ?? 0) > 0
                ? $request->time_limit * 60
                : null;

            $test = Test::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public', true),
                'time_limit' => $timeLimitSeconds,
                'max_attempts' => $request->get('max_attempts', -1),
                'pass_score' => $request->get('pass_score', 70),
                'wrong_penalty' => (float) $request->get('wrong_penalty', 0),
                'password' => $this->passwordFromRequest($request),
                'shuffle_questions' => $request->boolean('shuffle_questions'),
                'shuffle_answers' => $request->boolean('shuffle_answers'),
                'show_correct_answers' => $request->boolean('show_correct_answers', true),
                'show_explanations' => $request->boolean('show_explanations', true),
                'display_mode' => $request->get('display_mode', 'paged'),
                'difficulty' => $request->get('difficulty', 'medium'),
                'status' => $request->get('status', 'draft'),
                'published_at' => $request->status === 'published' ? now() : null,
            ]);

            // Attach category
            if ($request->category_id) {
                $test->categories()->attach($request->category_id);
                Category::where('id', $request->category_id)
                    ->increment('tests_count');
            }

            $this->syncQuestions($test, $request->questions);

            // Update user stats
            \App\Models\UserStat::firstOrCreate(['user_id' => $user->id])
                ->increment('tests_created');

            $test->load(['questions.options', 'categories']);

            return response()->json([
                'message' => $test->status === 'published'
                    ? '¡Test publicado exitosamente!'
                    : 'Test guardado como borrador',
                'test' => $test,
            ], 201);
        });
    }

    /**
     * Get editable test data (owner only)
     */
    public function edit(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $test = Test::with([
            'categories:id',
            'questions' => function ($q) {
                $q->orderBy('order_index');
            },
            'questions.options' => function ($q) {
                $q->orderBy('order_index');
            },
            'questions.blanks',
            'questions.orderItems',
        ])->findOrFail($id);

        if (! $this->canManageTest($user, $test)) {
            return response()->json([
                'message' => 'No tienes permisos para editar este test.',
            ], 403);
        }

        $data = $test->toArray();
        $data['category_id'] = $test->categories->first()?->id;
        $data['has_password'] = $test->hasPassword();

        return response()->json([
            'test' => $data,
        ]);
    }

    /**
     * Update a test owned by the authenticated user
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $test = Test::with('categories:id')->findOrFail($id);

        if (! $this->canManageTest($user, $test)) {
            return response()->json([
                'message' => 'No tienes permisos para editar este test.',
            ], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'time_limit' => 'nullable|integer|min:0',
            'max_attempts' => 'sometimes|integer',
            'pass_score' => 'sometimes|integer|min:0|max:100',
            'is_public' => 'sometimes|boolean',
            'shuffle_questions' => 'sometimes|boolean',
            'shuffle_answers' => 'sometimes|boolean',
            'show_correct_answers' => 'sometimes|boolean',
            'show_explanations' => 'sometimes|boolean',
            'display_mode' => 'sometimes|in:paged,all',
            'status' => 'sometimes|in:draft,published,archived',
            'questions' => 'sometimes|array|min:1',
            'wrong_penalty' => 'sometimes|numeric|min:0|max:1',
            'password' => 'sometimes|nullable|string|min:4|max:100',
            'questions.*.type' => ['required_with:questions', Rule::in(QuestionTypes::keys())],
            'questions.*.content' => 'required_with:questions|string',
            'questions.*.id' => 'sometimes|nullable|integer',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'sometimes|integer|min:1',
        ]);

        if ($request->has('questions')) {
            $this->validateQuestions($request->questions);
        }

        return DB::transaction(function () use ($request, $test) {
            $oldStatus = $test->status;
            $newStatus = $request->has('status') ? $request->status : $test->status;

            $test->fill([
                'title' => $request->has('title') ? $request->title : $test->title,
                'description' => $request->has('description') ? $request->description : $test->description,
                'is_public' => $request->has('is_public') ? $request->boolean('is_public') : $test->is_public,
                'time_limit' => $request->has('time_limit')
                    ? (($request->time_limit ?? 0) > 0 ? $request->time_limit * 60 : null)
                    : $test->time_limit,
                'max_attempts' => $request->has('max_attempts') ? $request->max_attempts : $test->max_attempts,
                'pass_score' => $request->has('pass_score') ? $request->pass_score : $test->pass_score,
                'wrong_penalty' => $request->has('wrong_penalty') ? (float) $request->wrong_penalty : $test->wrong_penalty,
                'password' => $this->passwordFromRequest($request, $test),
                'shuffle_questions' => $request->has('shuffle_questions')
                    ? $request->boolean('shuffle_questions')
                    : $test->shuffle_questions,
                'shuffle_answers' => $request->has('shuffle_answers')
                    ? $request->boolean('shuffle_answers')
                    : $test->shuffle_answers,
                'show_correct_answers' => $request->has('show_correct_answers')
                    ? $request->boolean('show_correct_answers')
                    : $test->show_correct_answers,
                'show_explanations' => $request->has('show_explanations')
                    ? $request->boolean('show_explanations')
                    : $test->show_explanations,
                'display_mode' => $request->has('display_mode') ? $request->display_mode : $test->display_mode,
                'difficulty' => $request->has('difficulty') ? $request->difficulty : $test->difficulty,
                'status' => $newStatus,
            ]);

            if ($newStatus === 'published' && $oldStatus !== 'published') {
                $test->published_at = now();
            } elseif ($newStatus !== 'published') {
                $test->published_at = null;
            }

            $test->save();

            if ($request->has('category_id')) {
                $oldCategoryIds = $test->categories()->pluck('categories.id')->all();
                $newCategoryIds = $request->category_id ? [(int) $request->category_id] : [];

                $toDetach = array_values(array_diff($oldCategoryIds, $newCategoryIds));
                $toAttach = array_values(array_diff($newCategoryIds, $oldCategoryIds));

                if (! empty($toDetach)) {
                    Category::whereIn('id', $toDetach)->decrement('tests_count');
                }

                if (! empty($toAttach)) {
                    Category::whereIn('id', $toAttach)->increment('tests_count');
                }

                $test->categories()->sync($newCategoryIds);
            }

            if ($request->has('questions')) {
                $this->syncQuestions($test, $request->questions);
            }

            $test->load(['questions.options', 'categories']);

            return response()->json([
                'message' => 'Test actualizado correctamente',
                'test' => $test,
            ]);
        });
    }

    /**
     * Delete a test owned by the authenticated user
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $test = Test::with('categories:id')->findOrFail($id);

        if (! $this->canManageTest($user, $test)) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este test.',
            ], 403);
        }

        $test->deleteWithCounters();

        return response()->json([
            'message' => 'Test eliminado correctamente',
        ]);
    }

    /**
     * Get test questions for taking the test.
     * Con `?mode=review` devuelve solo las preguntas que el usuario tiene pendientes de repasar.
     */
    public function questions(Request $request, int $id, ReviewQueue $reviewQueue): JsonResponse
    {
        $test = Test::findOrFail($id);

        if (! $this->canViewTest($test)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        if (TestAccess::isLocked($test, $request->user(), $request->session())) {
            return $this->lockedResponse();
        }

        $isReview = $request->query('mode') === 'review';

        $questionsQuery = Question::where('test_id', $id)->with(QuestionTypes::RELATIONS);

        if ($isReview) {
            $questionsQuery->whereIn('id', $reviewQueue->pendingQuestionIds((int) $request->user()->id, $test->id));
        }

        if ($test->shuffle_questions) {
            $questionsQuery->inRandomOrder();
        } else {
            $questionsQuery->orderBy('order_index');
        }

        // Se construye a mano para no filtrar la solución (opciones correctas, respuesta
        // numérica, explicación...). Cada tipo decide qué datos extra ve el alumno.
        $questions = $questionsQuery->get()->map(fn (Question $question) => array_merge([
            'id' => $question->id,
            'type' => $question->type,
            'content' => $question->content,
            'points' => $question->points,
            'order_index' => $question->order_index,
            'image_url' => $question->image_url,
        ], QuestionTypes::get($question->type)->forTaking($question, (bool) $test->shuffle_answers)));

        return response()->json([
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'mode' => $isReview ? 'review' : 'exam',
                // El repaso es para estudiar: sin límite de tiempo.
                'time_limit' => $isReview ? null : $test->time_limit,
                'display_mode' => $test->display_mode ?? 'paged',
                'shuffle_questions' => $test->shuffle_questions,
                'wrong_penalty' => $test->wrong_penalty,
            ],
            'questions' => $questions,
        ]);
    }

    /**
     * Desbloquea un test con contraseña para la sesión actual.
     */
    public function unlock(Request $request, int $id): JsonResponse
    {
        $request->validate(['password' => 'required|string|max:100']);

        $test = Test::findOrFail($id);

        if (! $this->canViewTest($test)) {
            return response()->json(['message' => 'Test no encontrado.'], 404);
        }

        if ($test->hasPassword() && ! Hash::check($request->password, $test->getAttributes()['password'])) {
            throw ValidationException::withMessages(['password' => 'La contraseña no es correcta.']);
        }

        TestAccess::unlock($test, $request->session());

        return response()->json(['message' => 'Test desbloqueado.', 'locked' => false]);
    }

    /**
     * Valida lo específico de cada tipo de pregunta (opciones, huecos, elementos...).
     *
     * @throws ValidationException
     */
    private function validateQuestions(array $questions): void
    {
        $errors = [];

        foreach (array_values($questions) as $index => $data) {
            if (! is_array($data) || trim((string) ($data['content'] ?? '')) === '') {
                $errors["questions.{$index}.content"] = 'La pregunta '.($index + 1).' necesita un enunciado.';

                continue;
            }

            $error = QuestionTypes::get($data['type'])->validate($data);
            if ($error !== null) {
                $errors["questions.{$index}"] = 'La pregunta '.($index + 1).' '.$error;
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Guarda las preguntas del editor. Las que traen `id` de este test se actualizan
     * en su sitio en lugar de borrarse y recrearse: así no se pierden las respuestas
     * de intentos anteriores (historial y repaso de fallos). Las que ya no vienen se borran.
     */
    private function syncQuestions(Test $test, array $questions): void
    {
        $existing = Question::where('test_id', $test->id)->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($questions) as $index => $data) {
            $type = QuestionTypes::get($data['type']);
            $attributes = array_merge([
                'test_id' => $test->id,
                'type' => $data['type'],
                'content' => trim($data['content']),
                'explanation' => $data['explanation'] ?? null,
                'points' => $data['points'] ?? 1,
                'order_index' => $index + 1,
            ], $type->attributes($data));

            $question = $existing->get((int) ($data['id'] ?? 0));

            // Un id ajeno, repetido o con otro tipo se trata como pregunta nueva.
            if ($question && $question->type === $data['type'] && ! in_array($question->id, $keptIds, true)) {
                $question->update($attributes);
            } else {
                $question = Question::create($attributes);
            }

            $type->store($question, $data);
            $keptIds[] = $question->id;
        }

        Question::where('test_id', $test->id)->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Get categories
     */
    public function categories(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();
        return response()->json($categories);
    }
}
