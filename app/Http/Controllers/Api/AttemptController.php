<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Question;
use App\Models\QuestionResponse;
use App\Models\UserStat;
use App\Questions\AttemptScorer;
use App\Questions\QuestionTypes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttemptController extends Controller
{
    private function encodeGivenAnswer(mixed $givenAnswer): ?string
    {
        if (is_null($givenAnswer)) {
            return null;
        }

        return json_encode($givenAnswer, JSON_UNESCAPED_UNICODE);
    }

    private function decodeGivenAnswer(mixed $storedValue): mixed
    {
        if (is_null($storedValue)) {
            return null;
        }

        if (is_array($storedValue) || is_bool($storedValue) || is_int($storedValue) || is_float($storedValue)) {
            return $storedValue;
        }

        if (! is_string($storedValue)) {
            return $storedValue;
        }

        $decoded = json_decode($storedValue, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $storedValue;
    }

    private function canAttemptTest(Test $test, int $userId): bool
    {
        if ($test->is_public && $test->status === 'published') {
            return true;
        }

        return (int) $test->user_id === (int) $userId;
    }

    /**
     * Submit a test attempt
     */
    public function submit(Request $request, AttemptScorer $scorer): JsonResponse
    {
        $request->validate([
            'test_id' => 'required|exists:tests,id',
            'answers' => 'required|array',
            'started_at' => 'required|integer',
            'question_order' => 'sometimes|array',
            'question_order.*' => 'integer',
        ]);

        $user = $request->user();
        $testId = $request->test_id;
        $test = Test::findOrFail($testId);

        if (! $this->canAttemptTest($test, (int) $user->id)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        if ($request->started_at > time()) {
            return response()->json([
                'message' => 'Marca de inicio inválida.',
            ], 422);
        }

        // Check max attempts
        $userAttempts = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $testId)
            ->where('is_completed', true)
            ->count();

        if ($test->max_attempts != -1 && $userAttempts >= $test->max_attempts) {
            return response()->json([
                'error' => 'Has alcanzado el número máximo de intentos',
            ], 422);
        }

        // Build attempt question order from the client payload.
        $questionsById = Question::where('test_id', $testId)
            ->with(QuestionTypes::RELATIONS)
            ->get()
            ->keyBy('id');
        $questionOrder = collect($request->input('question_order', []))
            ->map(fn ($questionId) => (int) $questionId)
            ->filter(fn ($questionId) => $questionId > 0)
            ->values();

        $orderedQuestions = collect();
        $seenQuestionIds = [];

        foreach ($questionOrder as $questionId) {
            if (isset($seenQuestionIds[$questionId]) || ! $questionsById->has($questionId)) {
                continue;
            }

            $orderedQuestions->push($questionsById->get($questionId));
            $seenQuestionIds[$questionId] = true;
        }

        // Fallback for legacy clients or malformed order payloads.
        foreach ($questionsById as $questionId => $question) {
            if (! isset($seenQuestionIds[$questionId])) {
                $orderedQuestions->push($question);
            }
        }

        $timeSpent = max(0, time() - $request->started_at);
        $result = $scorer->score($orderedQuestions, $request->answers, (float) $test->wrong_penalty);
        $passed = $result->score >= $test->pass_score;

        return DB::transaction(function () use ($user, $testId, $result, $timeSpent, $passed, $request) {
            $attempt = TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $testId,
                'score' => $result->score,
                'points_earned' => $result->pointsEarned,
                'total_points' => $result->totalPoints,
                'correct_answers' => $result->correct,
                'incorrect_answers' => $result->incorrect,
                'unanswered' => $result->unanswered,
                'time_spent' => $timeSpent,
                'is_completed' => true,
                'passed' => $passed,
                'started_at' => date('Y-m-d H:i:s', $request->started_at),
                'completed_at' => now(),
            ]);

            foreach ($result->responses as $response) {
                QuestionResponse::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $response['question_id'],
                    'given_answer' => $this->encodeGivenAnswer($response['given_answer']),
                    'is_correct' => $response['is_correct'],
                    'points_earned' => $response['points_earned'],
                    'display_order' => $response['display_order'],
                ]);
            }

            $userStats = UserStat::firstOrCreate(['user_id' => $user->id]);
            $userStats->tests_completed += 1;
            $userStats->tests_passed += $passed ? 1 : 0;
            $userStats->perfect_scores += ($result->score >= 100) ? 1 : 0;
            $userStats->total_study_time += $timeSpent;
            $userStats->average_score = TestAttempt::where('user_id', $user->id)
                ->where('is_completed', true)
                ->avg('score');
            $userStats->save();

            return response()->json([
                'attempt_id' => $attempt->id,
                'score' => round($result->score, 1),
                'passed' => $passed,
                'message' => $passed ? '¡Aprobado!' : 'No Aprobado',
            ]);
        });
    }

    /**
     * Get attempt results
     */
    public function results(int $attemptId): JsonResponse
    {
        $user = auth('sanctum')->user();

        $attempt = TestAttempt::with('test:id,title,pass_score,wrong_penalty,max_attempts,time_limit,show_correct_answers,show_explanations')
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $showCorrectAnswers = (bool) $attempt->test->show_correct_answers;

        $responses = QuestionResponse::where('attempt_id', $attemptId)
            ->with([
                'question:id,content,type,explanation,points,order_index,numeric_answer,numeric_tolerance',
                'question.options:id,question_id,content,is_correct,order_index',
                'question.blanks',
                'question.orderItems',
            ])
            ->get()
            ->sortBy(function ($response) {
                if (! is_null($response->display_order)) {
                    return (int) $response->display_order;
                }

                return (int) ($response->question->order_index ?? PHP_INT_MAX);
            })
            ->values();

        $responsesData = $responses->map(function ($response) use ($showCorrectAnswers) {
            $question = $response->question;
            $type = $question ? QuestionTypes::get($question->type) : null;
            $givenAnswerValue = $this->decodeGivenAnswer($response->given_answer);
            $answered = QuestionTypes::isAnswered($givenAnswerValue);
            $earned = (float) $response->points_earned;

            $status = match (true) {
                ! $answered => 'none',
                (bool) $response->is_correct => 'ok',
                $earned > 0 => 'partial',
                default => 'bad',
            };

            return [
                'id' => $response->id,
                'question_id' => $response->question_id,
                'display_order' => $response->display_order,
                'question_text' => $question?->content ?? '',
                'type' => $question?->type,
                'explanation' => $question?->explanation ?? '',
                'is_correct' => (bool) $response->is_correct,
                'status' => $status,
                'answered' => $answered,
                'points' => (int) ($question?->points ?? 0),
                'points_earned' => $earned,
                'given_answer' => $givenAnswerValue,
                'given_answer_label' => $answered && $type ? $type->answerLabel($question, $givenAnswerValue) : '',
                'correct_answer_label' => $showCorrectAnswers && ! $response->is_correct && $type
                    ? $type->correctLabel($question)
                    : '',
            ];
        });

        // Comparativa: media del test, tu mejor intento anterior e intentos restantes
        $completed = TestAttempt::where('test_id', $attempt->test_id)->where('is_completed', true);
        $userCompleted = (clone $completed)->where('user_id', $user->id);
        $attemptsUsed = (clone $userCompleted)->count();
        $maxAttempts = (int) $attempt->test->max_attempts;

        $previousBest = (clone $userCompleted)
            ->where('id', '!=', $attempt->id)
            ->where('completed_at', '<', $attempt->completed_at)
            ->max('score');

        $comparison = [
            'test_average' => ($avg = (clone $completed)->avg('score')) !== null ? round((float) $avg, 1) : null,
            'test_attempts' => (clone $completed)->count(),
            'previous_best' => $previousBest !== null ? round((float) $previousBest, 1) : null,
            'attempt_number' => (clone $userCompleted)->where('completed_at', '<=', $attempt->completed_at)->count(),
            'attempts_used' => $attemptsUsed,
            'attempts_remaining' => $maxAttempts === -1 ? null : max(0, $maxAttempts - $attemptsUsed),
        ];

        return response()->json([
            'attempt' => $attempt,
            'responses' => $responsesData,
            'comparison' => $comparison,
        ]);
    }
}
