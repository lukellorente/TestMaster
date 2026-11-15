<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestAttempt;
use App\Progress\CategoryMastery;
use App\Progress\StudyActivity;
use App\Questions\ReviewQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Panel privado de progreso del usuario autenticado ("Mi progreso").
 * Nada de esto se expone en el perfil público.
 */
class DashboardController extends Controller
{
    public function show(
        Request $request,
        StudyActivity $activity,
        CategoryMastery $mastery,
        ReviewQueue $reviewQueue,
    ): JsonResponse {
        $request->validate(['tz' => 'sometimes|nullable|string|max:64']);

        $userId = (int) $request->user()->id;
        $timezone = StudyActivity::timezone($request->query('tz'));

        $exams = TestAttempt::where('user_id', $userId)->completedExams();

        return response()->json([
            'summary' => [
                'exams_completed' => (clone $exams)->count(),
                'exams_passed' => (clone $exams)->where('passed', true)->count(),
                'average_score' => ($avg = (clone $exams)->avg('score')) !== null ? round((float) $avg, 1) : null,
                'reviews_completed' => TestAttempt::where('user_id', $userId)
                    ->where('is_completed', true)
                    ->where('mode', TestAttempt::MODE_REVIEW)
                    ->count(),
                // Tiempo de estudio real: exámenes y repasos.
                'study_time' => (int) TestAttempt::where('user_id', $userId)->where('is_completed', true)->sum('time_spent'),
            ],
            'activity' => $activity->summary($userId, $timezone),
            'mastery' => $mastery->forUser($userId),
            'pending_reviews' => $reviewQueue->pendingByTest($userId),
            'timezone' => $timezone,
        ]);
    }

    /**
     * Historial de intentos paginado. `mode` filtra por exam/review.
     */
    public function attempts(Request $request): JsonResponse
    {
        $request->validate(['mode' => 'sometimes|nullable|in:exam,review']);

        $page = TestAttempt::where('user_id', $request->user()->id)
            ->where('is_completed', true)
            ->when($request->query('mode'), fn ($q, $mode) => $q->where('mode', $mode))
            ->with('test:id,title')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(15, [
                'id', 'test_id', 'mode', 'score', 'passed', 'correct_answers',
                'incorrect_answers', 'unanswered', 'time_spent', 'completed_at',
            ]);

        return response()->json([
            'data' => collect($page->items())->map(fn (TestAttempt $a) => [
                'id' => $a->id,
                'test_id' => $a->test_id,
                'test_title' => $a->test?->title,
                'mode' => $a->mode,
                'score' => round((float) $a->score, 1),
                'passed' => (bool) $a->passed,
                'correct_answers' => $a->correct_answers,
                'total_questions' => $a->correct_answers + $a->incorrect_answers + $a->unanswered,
                'time_spent' => $a->time_spent,
                'completed_at' => $a->completed_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
