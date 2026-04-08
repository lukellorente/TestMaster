<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionResponse;
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

    private function isAnsweredValue(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        return true;
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
    public function submit(Request $request): JsonResponse
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
        $questionsById = Question::where('test_id', $testId)->get()->keyBy('id');
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

        $answers = $request->answers;
        $timeSpent = max(0, time() - $request->started_at);

        // Calculate results
        $correctAnswers = 0;
        $incorrectAnswers = 0;
        $unanswered = 0;
        $pointsEarned = 0;
        $totalPoints = 0;
        $responses = [];

        foreach ($orderedQuestions as $displayIndex => $question) {
            $totalPoints += $question->points;
            $givenAnswer = $answers[$question->id] ?? ($answers[(string) $question->id] ?? null);
            $isCorrect = false;
            $displayOrder = $displayIndex + 1;

            $isUnanswered = ! $this->isAnsweredValue($givenAnswer);

            if ($isUnanswered) {
                $unanswered++;
                $responses[] = [
                    'question_id' => $question->id,
                    'given_answer' => null,
                    'is_correct' => false,
                    'points_earned' => 0,
                    'display_order' => $displayOrder,
                ];
                continue;
            }

            // Check answer by question type
            if ($question->type === 'multiple_choice') {
                $option = QuestionOption::where('id', $givenAnswer)
                    ->where('question_id', $question->id)
                    ->first();
                $isCorrect = $option && $option->is_correct;
            } elseif ($question->type === 'true_false') {
                $correctOption = QuestionOption::where('question_id', $question->id)
                    ->where('is_correct', true)
                    ->first();
                if ($correctOption) {
                    $isCorrect = (strtolower($givenAnswer) === 'true' && $correctOption->content === 'Verdadero')
                              || (strtolower($givenAnswer) === 'false' && $correctOption->content === 'Falso');
                }
            } elseif ($question->type === 'short_answer') {
                $correctOptions = QuestionOption::where('question_id', $question->id)
                    ->where('is_correct', true)
                    ->get();
                foreach ($correctOptions as $option) {
                    if (strcasecmp(trim($givenAnswer), trim($option->content)) === 0) {
                        $isCorrect = true;
                        break;
                    }
                }
            }

            if ($isCorrect) {
                $correctAnswers++;
                $pointsEarned += $question->points;
            } else {
                $incorrectAnswers++;
            }

            $responses[] = [
                'question_id' => $question->id,
                'given_answer' => $this->encodeGivenAnswer($givenAnswer),
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? $question->points : 0,
                'display_order' => $displayOrder,
            ];
        }

        $score = $totalPoints > 0 ? ($pointsEarned / $totalPoints) * 100 : 0;
        $passed = $score >= $test->pass_score;

        return DB::transaction(function () use (
            $user, $testId, $score, $pointsEarned, $totalPoints,
            $correctAnswers, $incorrectAnswers, $unanswered,
            $timeSpent, $passed, $request, $responses, $test
        ) {
            // Create attempt
            $attempt = TestAttempt::create([
                'user_id' => $user->id,
                'test_id' => $testId,
                'score' => $score,
                'points_earned' => $pointsEarned,
                'total_points' => $totalPoints,
                'correct_answers' => $correctAnswers,
                'incorrect_answers' => $incorrectAnswers,
                'unanswered' => $unanswered,
                'time_spent' => $timeSpent,
                'is_completed' => true,
                'passed' => $passed,
                'started_at' => date('Y-m-d H:i:s', $request->started_at),
                'completed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Save responses
            foreach ($responses as $response) {
                QuestionResponse::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $response['question_id'],
                    'given_answer' => $response['given_answer'],
                    'is_correct' => $response['is_correct'],
                    'points_earned' => $response['points_earned'],
                    'display_order' => $response['display_order'],
                ]);
            }

            // Update user stats
            $userStats = \App\Models\UserStat::firstOrCreate(['user_id' => $user->id]);
            $userStats->tests_completed += 1;
            $userStats->tests_passed += $passed ? 1 : 0;
            $userStats->perfect_scores += ($score >= 100) ? 1 : 0;
            $userStats->total_study_time += $timeSpent;
            $userStats->average_score = TestAttempt::where('user_id', $user->id)
                ->where('is_completed', true)
                ->avg('score');
            $userStats->save();

            return response()->json([
                'attempt_id' => $attempt->id,
                'score' => round($score, 1),
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

        $attempt = TestAttempt::with('test:id,title,show_correct_answers,show_explanations')
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $showCorrectAnswers = (bool) $attempt->test->show_correct_answers;

        $responses = QuestionResponse::where('attempt_id', $attemptId)
            ->with([
                'question:id,content,type,explanation,points,order_index',
                'question.options:id,question_id,content,is_correct,order_index',
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
            $questionType = $question?->type ?? null;
            $options = $question?->options ?? collect();
            $givenAnswerValue = $this->decodeGivenAnswer($response->given_answer);
            $givenAnswerLabel = '';

            if ($this->isAnsweredValue($givenAnswerValue)) {
                if ($questionType === 'multiple_choice') {
                    $selectedOption = $options->first(fn ($option) => (string) $option->id === (string) $givenAnswerValue);
                    $givenAnswerLabel = $selectedOption?->content ?? 'Seleccion no disponible';
                } elseif ($questionType === 'true_false') {
                    if (is_bool($givenAnswerValue)) {
                        $normalized = $givenAnswerValue ? 'true' : 'false';
                    } else {
                        $normalized = strtolower(trim((string) $givenAnswerValue));
                    }

                    if (in_array($normalized, ['true', 'verdadero', '1'], true)) {
                        $givenAnswerLabel = 'Verdadero';
                    } elseif (in_array($normalized, ['false', 'falso', '0'], true)) {
                        $givenAnswerLabel = 'Falso';
                    } else {
                        $givenAnswerLabel = (string) $givenAnswerValue;
                    }
                } else {
                    $givenAnswerLabel = is_scalar($givenAnswerValue)
                        ? (string) $givenAnswerValue
                        : json_encode($givenAnswerValue, JSON_UNESCAPED_UNICODE);
                }
            }

            $correctAnswerLabel = '';
            if ($showCorrectAnswers && ! $response->is_correct) {
                $correctOption = $options->first(fn ($option) => (bool) $option->is_correct);
                $correctAnswerLabel = $correctOption?->content ?? '';
            }

            return [
                'id' => $response->id,
                'question_id' => $response->question_id,
                'display_order' => $response->display_order,
                'question_text' => $question?->content ?? '',
                'type' => $questionType,
                'explanation' => $question?->explanation ?? '',
                'is_correct' => (bool) $response->is_correct,
                'answered' => $this->isAnsweredValue($givenAnswerValue),
                'given_answer' => $givenAnswerValue,
                'given_answer_label' => $givenAnswerLabel,
                'correct_answer_label' => $correctAnswerLabel,
            ];
        });

        return response()->json([
            'attempt' => $attempt,
            'responses' => $responsesData,
        ]);
    }
}
