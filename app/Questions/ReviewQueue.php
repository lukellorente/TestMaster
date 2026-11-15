<?php

namespace App\Questions;

use App\Models\QuestionResponse;
use Illuminate\Support\Collection;

/**
 * Preguntas que un usuario tiene pendientes de repasar en un test.
 *
 * Cuenta la última respuesta del usuario a cada pregunta, venga de un examen o
 * de un repaso: si no es 100 % correcta (incorrecta, parcial o en blanco), la
 * pregunta está pendiente. Al acertarla una vez, sale de la lista.
 */
final class ReviewQueue
{
    /** @return Collection<int, int> ids de pregunta pendientes */
    public function pendingQuestionIds(int $userId, int $testId): Collection
    {
        $latestResponseIds = QuestionResponse::query()
            ->join('test_attempts', 'test_attempts.id', '=', 'question_responses.attempt_id')
            ->where('test_attempts.user_id', $userId)
            ->where('test_attempts.test_id', $testId)
            ->where('test_attempts.is_completed', true)
            ->groupBy('question_responses.question_id')
            ->selectRaw('MAX(question_responses.id)');

        return QuestionResponse::whereIn('id', $latestResponseIds)
            ->where('is_correct', false)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function pendingCount(int $userId, int $testId): int
    {
        return $this->pendingQuestionIds($userId, $testId)->count();
    }
}
