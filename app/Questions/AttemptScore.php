<?php

namespace App\Questions;

final class AttemptScore
{
    public int $correct = 0;

    /** Respondidas sin el 100 % de acierto (incluye las parciales). */
    public int $incorrect = 0;

    public int $unanswered = 0;

    /** Puede ser negativo por las penalizaciones; la nota (`score`) no. */
    public float $pointsEarned = 0.0;

    public int $totalPoints = 0;

    /** Porcentaje entre 0 y 100. */
    public float $score = 0.0;

    /** @var array<int, array{question_id: int, given_answer: mixed, is_correct: bool, points_earned: float, display_order: int}> */
    public array $responses = [];
}
