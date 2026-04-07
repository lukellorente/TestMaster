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

        // Get all questions
        $questions = Question::where('test_id', $testId)->get();
        $answers = $request->answers;
        $timeSpent = max(0, time() - $request->started_at);

        // Calculate results
        $correctAnswers = 0;
        $incorrectAnswers = 0;
        $unanswered = 0;
        $pointsEarned = 0;
        $totalPoints = 0;
        $responses = [];

        foreach ($questions as $question) {
            $totalPoints += $question->points;
            $givenAnswer = $answers[$question->id] ?? null;
            $isCorrect = false;

            if (!$givenAnswer) {
                $unanswered++;
                $responses[] = [
                    'question_id' => $question->id,
                    'given_answer' => null,
                    'is_correct' => false,
                    'points_earned' => 0,
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
                'given_answer' => is_array($givenAnswer) ? json_encode($givenAnswer) : (string) $givenAnswer,
                'is_correct' => $isCorrect,
                'points_earned' => $isCorrect ? $question->points : 0,
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

        $responses = QuestionResponse::where('attempt_id', $attemptId)
            ->with(['question:id,content,type,explanation,points,order_index'])
            ->orderBy('id')
            ->get();

        // Get options for each response
        $responsesData = $responses->map(function ($response) use ($attempt) {
            $data = $response->toArray();
            if (in_array($response->question->type, ['multiple_choice', 'true_false', 'short_answer'])) {
                if ($attempt->test->show_correct_answers) {
                    $data['options'] = QuestionOption::where('question_id', $response->question_id)
                        ->orderBy('order_index')
                        ->get()
                        ->toArray();
                } else {
                    // Do not expose correct answer metadata when the test has it disabled.
                    $data['options'] = [];
                }
            }
            return $data;
        });

        return response()->json([
            'attempt' => $attempt,
            'responses' => $responsesData,
        ]);
    }
}
