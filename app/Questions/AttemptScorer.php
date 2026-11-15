<?php

namespace App\Questions;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Corrige un intento completo.
 *
 * Reglas:
 *  - Cada pregunta aporta `fracción de acierto × puntos` (puntuación parcial).
 *  - Con penalización, una pregunta respondida con 0 aciertos resta `penalización × puntos`.
 *    Las parciales no restan y las que están en blanco tampoco.
 *  - La nota final es puntos obtenidos / puntos totales, y nunca baja de 0 %.
 *  - Solo cuenta como correcta una pregunta con el 100 % de acierto.
 */
final class AttemptScorer
{
    private const FULL = 0.9999;

    /**
     * @param  Collection<int, Question>  $questions  en el orden en que se mostraron, con QuestionTypes::RELATIONS cargadas
     * @param  array<int|string, mixed>  $answers  respuestas indexadas por id de pregunta
     */
    public function score(Collection $questions, array $answers, float $wrongPenalty = 0.0): AttemptScore
    {
        $wrongPenalty = max(0.0, min(1.0, $wrongPenalty));
        $result = new AttemptScore;

        foreach ($questions->values() as $index => $question) {
            $points = (int) $question->points;
            $result->totalPoints += $points;
            $given = $answers[$question->id] ?? ($answers[(string) $question->id] ?? null);

            if (! QuestionTypes::isAnswered($given)) {
                $result->unanswered++;
                $result->responses[] = $this->response($question, $index, null, false, 0.0);

                continue;
            }

            $ratio = max(0.0, min(1.0, QuestionTypes::get($question->type)->grade($question, $given)));
            $isCorrect = $ratio >= self::FULL;

            if ($ratio <= 0.0) {
                $earned = -$wrongPenalty * $points;
            } else {
                $earned = $isCorrect ? (float) $points : $ratio * $points;
            }
            $earned = round($earned, 2);

            $isCorrect ? $result->correct++ : $result->incorrect++;
            $result->pointsEarned += $earned;
            $result->responses[] = $this->response($question, $index, $given, $isCorrect, $earned);
        }

        $result->pointsEarned = round($result->pointsEarned, 2);
        $result->score = $result->totalPoints > 0
            ? round(max(0.0, $result->pointsEarned) / $result->totalPoints * 100, 2)
            : 0.0;

        return $result;
    }

    private function response(Question $question, int $index, mixed $given, bool $isCorrect, float $earned): array
    {
        return [
            'question_id' => $question->id,
            'given_answer' => $given,
            'is_correct' => $isCorrect,
            'points_earned' => $earned,
            'display_order' => $index + 1,
        ];
    }
}
