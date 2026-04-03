<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\TestStat;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    /**
     * List public published tests (homepage)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Test::with(['user:id,name', 'categories:id,name,icon'])
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
            'attempts' => $query->orderBy('attempts_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $tests = $query->paginate($request->get('per_page', 20));

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
        $test = Test::with(['user:id,name', 'categories:id,name,icon'])
            ->withCount('questions')
            ->withCount(['attempts as completed_attempts_count' => function ($q) {
                $q->where('is_completed', true);
            }])
            ->findOrFail($id);

        // Average score
        $avgScore = $test->attempts()
            ->where('is_completed', true)
            ->avg('score');

        $data = $test->toArray();
        $data['avg_score'] = $avgScore;

        // If user is authenticated, get their attempt info
        if (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
            $data['user_attempts'] = $test->attempts()
                ->where('user_id', $userId)
                ->where('is_completed', true)
                ->count();

            $data['last_attempt'] = $test->attempts()
                ->where('user_id', $userId)
                ->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->first(['score', 'completed_at']);
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
            'status' => 'in:draft,published',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:multiple_choice,true_false,short_answer',
            'questions.*.content' => 'required|string',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.points' => 'integer|min:1',
        ]);

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
                'shuffle_questions' => $request->boolean('shuffle_questions'),
                'shuffle_answers' => $request->boolean('shuffle_answers'),
                'show_correct_answers' => $request->boolean('show_correct_answers', true),
                'show_explanations' => $request->boolean('show_explanations', true),
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

            // Create questions
            $orderIndex = 1;
            foreach ($request->questions as $qData) {
                $content = $qData['content'] ?? '';
                if (is_array($content)) $content = '';
                if (empty(trim($content))) continue;

                // Log para depuración
                if ($qData['type'] === 'multiple_choice') {
                    \Log::info('Opciones recibidas para pregunta multiple_choice:', [
                        'options' => $qData['options'] ?? null,
                        'content' => $qData['content'] ?? null,
                    ]);
                }

                $question = Question::create([
                    'test_id' => $test->id,
                    'type' => $qData['type'],
                    'content' => $qData['content'],
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => $qData['points'] ?? 1,
                    'order_index' => $orderIndex++, 
                ]);

                if ($qData['type'] === 'multiple_choice') {
                    foreach (($qData['options'] ?? []) as $idx => $opt) {
                        $optionStr = is_array($opt) ? ($opt['content'] ?? '') : $opt;
                        if (empty(trim($optionStr))) continue;
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'content' => $optionStr,
                            'is_correct' => is_array($opt) ? ($opt['is_correct'] ?? false) : false,
                            'order_index' => is_array($opt) ? ($opt['order_index'] ?? $idx) : $idx,
                        ]);
                    }
                } elseif ($qData['type'] === 'true_false') {
                    $correct = $qData['correct'] ?? 'true';
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'content' => 'Verdadero',
                        'is_correct' => $correct === 'true',
                        'order_index' => 1,
                    ]);
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'content' => 'Falso',
                        'is_correct' => $correct === 'false',
                        'order_index' => 2,
                    ]);
                } elseif ($qData['type'] === 'short_answer') {
                    $correctAnswer = $qData['correct_answer'] ?? '';
                    if (is_array($correctAnswer)) $correctAnswer = '';
                    $correctAnswer = trim($correctAnswer);
                    if (!empty($correctAnswer)) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'content' => $correctAnswer,
                            'is_correct' => true,
                            'order_index' => 1,
                        ]);
                    }
                }
            }

            // Create test stats
            TestStat::create(['test_id' => $test->id]);

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
     * Get test questions for taking the test
     */
    public function questions(int $id): JsonResponse
    {
        $test = Test::findOrFail($id);

        $questionsQuery = Question::where('test_id', $id);

        if ($test->shuffle_questions) {
            $questionsQuery->inRandomOrder();
        } else {
            $questionsQuery->orderBy('order_index');
        }

        $questions = $questionsQuery->get();

        foreach ($questions as $question) {
            if (in_array($question->type, ['multiple_choice', 'true_false'])) {
                $optionsQuery = QuestionOption::where('question_id', $question->id);
                if ($test->shuffle_answers) {
                    $optionsQuery->inRandomOrder();
                } else {
                    $optionsQuery->orderBy('order_index');
                }
                // Don't expose is_correct to client
                $question->options = $optionsQuery->get(['id', 'question_id', 'content', 'order_index', 'image_url']);
            } else {
                $question->options = null;
            }
        }

        return response()->json([
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'time_limit' => $test->time_limit,
                'shuffle_questions' => $test->shuffle_questions,
            ],
            'questions' => $questions,
        ]);
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
