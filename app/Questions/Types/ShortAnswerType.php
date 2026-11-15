<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Models\QuestionOption;

/** Texto libre. Se compara sin mayúsculas ni espacios sobrantes con las respuestas aceptadas. */
class ShortAnswerType extends BaseType
{
    public function validate(array $data): ?string
    {
        return self::clean($data['correct_answer'] ?? '') === '' ? 'necesita la respuesta correcta.' : null;
    }

    public function store(Question $question, array $data): void
    {
        self::syncRows($question->options()->get(), [[
            'question_id' => $question->id,
            'content' => self::clean($data['correct_answer'] ?? ''),
            'is_correct' => true,
            'order_index' => 1,
        ]], QuestionOption::class);
    }

    public function grade(Question $question, mixed $answer): float
    {
        $given = self::normalize($answer);

        return $question->options
            ->where('is_correct', true)
            ->contains(fn ($o) => self::normalize($o->content) === $given) ? 1.0 : 0.0;
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        return self::clean($answer);
    }

    public function correctLabel(Question $question): string
    {
        return (string) ($question->options->firstWhere('is_correct', true)?->content ?? '');
    }
}
