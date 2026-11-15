<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Models\QuestionOption;

/** La respuesta del alumno es 'true' o 'false'. */
class TrueFalseType extends BaseType
{
    public function validate(array $data): ?string
    {
        return in_array($this->correctFromPayload($data), ['true', 'false'], true)
            ? null
            : 'debe indicar si la afirmación es verdadera o falsa.';
    }

    /** Acepta `correct: 'true'|'false'` o el formato de opciones que envía el editor. */
    private function correctFromPayload(array $data): ?string
    {
        if (isset($data['correct'])) {
            return self::normalize($data['correct']);
        }

        foreach (($data['options'] ?? []) as $option) {
            if (is_array($option) && filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                return self::normalize($option['content'] ?? '') === 'falso' ? 'false' : 'true';
            }
        }

        return null;
    }

    public function store(Question $question, array $data): void
    {
        $correct = $this->correctFromPayload($data);

        $rows = [];
        foreach (['Verdadero' => 'true', 'Falso' => 'false'] as $content => $value) {
            $rows[] = [
                'question_id' => $question->id,
                'content' => $content,
                'is_correct' => $correct === $value,
                'order_index' => $value === 'true' ? 1 : 2,
            ];
        }

        self::syncRows($question->options()->get(), $rows, QuestionOption::class);
    }

    private static function toBool(mixed $answer): ?bool
    {
        if (is_bool($answer)) {
            return $answer;
        }

        return match (self::normalize($answer)) {
            'true', 'verdadero', '1' => true,
            'false', 'falso', '0' => false,
            default => null,
        };
    }

    private function correctValue(Question $question): ?bool
    {
        $option = $question->options->firstWhere('is_correct', true);

        return $option ? $option->content === 'Verdadero' : null;
    }

    public function grade(Question $question, mixed $answer): float
    {
        $given = self::toBool($answer);
        $correct = $this->correctValue($question);

        return $given !== null && $given === $correct ? 1.0 : 0.0;
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        return match (self::toBool($answer)) {
            true => 'Verdadero',
            false => 'Falso',
            null => self::clean($answer),
        };
    }

    public function correctLabel(Question $question): string
    {
        return match ($this->correctValue($question)) {
            true => 'Verdadero',
            false => 'Falso',
            null => '',
        };
    }
}
