<?php

namespace App\Questions;

use App\Models\QuestionResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Preguntas que un usuario tiene pendientes de repasar.
 *
 * Cuenta la última respuesta del usuario a cada pregunta, venga de un examen o
 * de un repaso: si no es 100 % correcta (incorrecta, parcial o en blanco), la
 * pregunta está pendiente. Al acertarla una vez, sale de la lista.
 */
final class ReviewQueue
{
    /**
     * Subconsulta con el id de la última respuesta del usuario a cada pregunta,
     * de un test concreto o de todos.
     */
    public function latestResponseIds(int $userId, ?int $testId = null): Builder
    {
        return QuestionResponse::query()
            ->join('test_attempts', 'test_attempts.id', '=', 'question_responses.attempt_id')
            ->where('test_attempts.user_id', $userId)
            ->when($testId !== null, fn ($q) => $q->where('test_attempts.test_id', $testId))
            ->where('test_attempts.is_completed', true)
            ->groupBy('question_responses.question_id')
            ->selectRaw('MAX(question_responses.id)');
    }

    /** @return Collection<int, int> ids de pregunta pendientes */
    public function pendingQuestionIds(int $userId, int $testId): Collection
    {
        return QuestionResponse::whereIn('id', $this->latestResponseIds($userId, $testId))
            ->where('is_correct', false)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function pendingCount(int $userId, int $testId): int
    {
        return $this->pendingQuestionIds($userId, $testId)->count();
    }

    /**
     * Tests con preguntas pendientes, de más a menos fallos. Solo los que el
     * usuario aún puede abrir (públicos y publicados, o suyos).
     *
     * @return Collection<int, array{test_id: int, title: string, pending: int}>
     */
    public function pendingByTest(int $userId, int $limit = 10): Collection
    {
        return DB::table('question_responses')
            ->join('questions', 'questions.id', '=', 'question_responses.question_id')
            ->join('tests', 'tests.id', '=', 'questions.test_id')
            ->whereIn('question_responses.id', $this->latestResponseIds($userId)->toBase())
            ->where('question_responses.is_correct', false)
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('tests.is_public', true)->where('tests.status', 'published'))
                ->orWhere('tests.user_id', $userId))
            ->groupBy('tests.id', 'tests.title')
            ->orderByDesc('pending')
            ->orderBy('tests.title')
            ->limit($limit)
            ->get(['tests.id as test_id', 'tests.title', DB::raw('COUNT(*) as pending')])
            ->map(fn ($row) => [
                'test_id' => (int) $row->test_id,
                'title' => $row->title,
                'pending' => (int) $row->pending,
            ]);
    }
}
