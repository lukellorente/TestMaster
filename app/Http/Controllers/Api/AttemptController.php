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
use App\Questions\ReviewQueue;
use App\Support\TestAccess;
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

    /**
     * Submit a test attempt
     */
    public function submit(Request $request, AttemptScorer $scorer, ReviewQueue $reviewQueue): JsonResponse
    {
        $request->validate([
            'test_id' => 'required|exists:tests,id',
            'answers' => 'required|array',
            'started_at' => 'required|integer',
            'question_order' => 'sometimes|array',
            'question_order.*' => 'integer',
            'mode' => 'sometimes|in:exam,review',
        ]);

        $user = $request->user();
        $testId = $request->test_id;
        $test = Test::findOrFail($testId);

        if (! TestAccess::canView($test, $user)) {
            return response()->json([
                'message' => 'Test no encontrado.',
            ], 404);
        }

        if (TestAccess::isLocked($test, $user, $request->session())) {
            return response()->json([
                'message' => 'Este test está protegido con contraseña.',
                'locked' => true,
            ], 423);
        }

        if ($request->started_at > time()) {
            return response()->json([
                'message' => 'Marca de inicio inválida.',
            ], 422);
        }

        $isReview = $request->input('mode') === TestAttempt::MODE_REVIEW;

        // Check max attempts (los repasos no gastan intentos)
        $userAttempts = TestAttempt::where('user_id', $user->id)
            ->where('test_id', $testId)
            ->completedExams()
            ->count();

        if (! $isReview && $test->max_attempts != -1 && $userAttempts >= $test->max_attempts) {
            return response()->json([
                'error' => 'Has alcanzado el número máximo de intentos',
            ], 422);
        }

        // Build attempt question order from the client payload.
        // En un repaso solo se corrigen las preguntas que siguen pendientes.
        $questionsById = Question::where('test_id', $testId)
            ->when($isReview, fn ($q) => $q->whereIn('id', $reviewQueue->pendingQuestionIds((int) $user->id, (int) $testId)))
            ->with(QuestionTypes::RELATIONS)
            ->get()
            ->keyBy('id');

        if ($isReview && $questionsById->isEmpty()) {
            return response()->json([
                'message' => 'No tienes preguntas pendientes de repasar en este test.',
            ], 422);
        }
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

        return DB::transaction(function () use ($user, $testId, $result, $timeSpent, $passed, $request, $isReview) {
            $attempt = TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $testId,
                'mode' => $isReview ? TestAttempt::MODE_REVIEW : TestAttempt::MODE_EXAM,
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

            // Los repasos no cuentan para las estadísticas del usuario.
            if (! $isReview) {
                $userStats = UserStat::firstOrCreate(['user_id' => $user->id]);
                $userStats->tests_completed += 1;
                $userStats->tests_passed += $passed ? 1 : 0;
                $userStats->perfect_scores += ($result->score >= 100) ? 1 : 0;
                $userStats->total_study_time += $timeSpent;
                $userStats->average_score = TestAttempt::where('user_id', $user->id)
                    ->completedExams()
                    ->avg('score');
                $userStats->save();
            }

            return response()->json([
                'attempt_id' => $attempt->id,
                'mode' => $attempt->mode,
                'score' => round($result->score, 1),
                'passed' => $passed,
                'message' => $passed ? '¡Aprobado!' : 'No Aprobado',
            ]);
        });
    }

    /**
     * Get attempt results
     */
    public function results(int $attemptId, ReviewQueue $reviewQueue): JsonResponse
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

        // Comparativa (solo exámenes): media del test, tu mejor intento anterior e intentos restantes
        $comparison = $attempt->mode === TestAttempt::MODE_REVIEW ? null : $this->comparison($attempt, (int) $user->id);

        return response()->json([
            'attempt' => $attempt,
            'responses' => $responsesData,
            'comparison' => $comparison,
            'pending_review_count' => $reviewQueue->pendingCount((int) $user->id, (int) $attempt->test_id),
        ]);
    }

    private function comparison(TestAttempt $attempt, int $userId): array
    {
        $completed = TestAttempt::where('test_id', $attempt->test_id)->completedExams();
        $userCompleted = (clone $completed)->where('user_id', $userId);
        $attemptsUsed = (clone $userCompleted)->count();
        $maxAttempts = (int) $attempt->test->max_attempts;

        $previousBest = (clone $userCompleted)
            ->where('id', '!=', $attempt->id)
            ->where('completed_at', '<', $attempt->completed_at)
            ->max('score');

        return [
            'test_average' => ($avg = (clone $completed)->avg('score')) !== null ? round((float) $avg, 1) : null,
            'test_attempts' => (clone $completed)->count(),
            'previous_best' => $previousBest !== null ? round((float) $previousBest, 1) : null,
            'attempt_number' => (clone $userCompleted)->where('completed_at', '<=', $attempt->completed_at)->count(),
            'attempts_used' => $attemptsUsed,
            'attempts_remaining' => $maxAttempts === -1 ? null : max(0, $maxAttempts - $attemptsUsed),
        ];
    }
}
