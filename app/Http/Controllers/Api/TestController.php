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
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    private function canViewTest(Test $test): bool
    {
        if ($test->is_public && $test->status === 'published') {
            return true;
        }

        $authUser = auth('sanctum')->user();
        if (! $authUser) {
            return false;
        }

        if ($this->isAdminUser($authUser)) {
            return true;
        }

        return (int) $authUser->id === (int) $test->user_id;
    }

    private function isAdminUser(?User $user): bool
    {
        return $user
            && $user->role === 'admin'
            && $user->email === 'admin@example.com';
    }

    private function canManageTest(?User $user, Test $test): bool
    {
        return $user
            && ((int) $user->id === (int) $test->user_id || $this->isAdminUser($user));
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
                $q->where('is_completed', true);
            }]);

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
     * Get general stats for homepage
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_tests' => Test::where('is_public', true)->where('status', 'published')->count(),
            'total_users' => \App\Models\User::count(),
            'total_attempts' => \App\Models\TestAttempt::where('is_completed', true)->count(),
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
                $q->where('is_completed', true);
            }])
            ->findOrFail($id);

        if (! $this->canViewTest($test)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        // Average score
        $avgScore = $test->attempts()
            ->where('is_completed', true)
            ->avg('score');

        $data = $test->toArray();
        $data['avg_score'] = $avgScore;

        // If user is authenticated, get their attempt info
        $authUser = auth('sanctum')->user();
        $data['can_manage'] = $this->canManageTest($authUser, $test);
        if ($authUser) {
            $userId = $authUser->id;
            $data['user_attempts'] = $test->attempts()
                ->where('user_id', $userId)
                ->where('is_completed', true)
                ->count();

            $data['last_attempt'] = $test->attempts()
                ->where('user_id', $userId)
                ->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->first(['id', 'score', 'completed_at']);
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

            $this->createQuestions($test, $request->questions);

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
            'questions.*.type' => ['required_with:questions', Rule::in(QuestionTypes::keys())],
            'questions.*.content' => 'required_with:questions|string',
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
                Question::where('test_id', $test->id)->delete();
                $this->createQuestions($test, $request->questions);
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

        return DB::transaction(function () use ($test, $user) {
            $categoryIds = $test->categories()->pluck('categories.id')->all();

            if (! empty($categoryIds)) {
                Category::whereIn('id', $categoryIds)->decrement('tests_count');
            }

            $test->delete();

            $userStat = UserStat::firstOrCreate(['user_id' => $user->id]);
            if ($userStat->tests_created > 0) {
                $userStat->decrement('tests_created');
            }

            return response()->json([
                'message' => 'Test eliminado correctamente',
            ]);
        });
    }

    /**
     * Get test questions for taking the test
     */
    public function questions(int $id): JsonResponse
    {
        $test = Test::findOrFail($id);

        if (! $this->canViewTest($test)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        $questionsQuery = Question::where('test_id', $id)->with(QuestionTypes::RELATIONS);

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
                'time_limit' => $test->time_limit,
                'display_mode' => $test->display_mode ?? 'paged',
                'shuffle_questions' => $test->shuffle_questions,
                'wrong_penalty' => $test->wrong_penalty,
            ],
            'questions' => $questions,
        ]);
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

    private function createQuestions(Test $test, array $questions): void
    {
        foreach (array_values($questions) as $index => $data) {
            $type = QuestionTypes::get($data['type']);

            $question = Question::create(array_merge([
                'test_id' => $test->id,
                'type' => $data['type'],
                'content' => trim($data['content']),
                'explanation' => $data['explanation'] ?? null,
                'points' => $data['points'] ?? 1,
                'order_index' => $index + 1,
            ], $type->attributes($data)));

            $type->store($question, $data);
        }
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
