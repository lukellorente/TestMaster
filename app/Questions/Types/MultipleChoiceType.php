<?php

namespace App\Questions\Types;

use App\Models\Question;

/** Una sola respuesta correcta. La respuesta del alumno es el id de la opción. */
class MultipleChoiceType extends OptionsType
{
    public function validate(array $data): ?string
    {
        if ($error = $this->validateOptions($data)) {
            return $error;
        }

        $correct = count(array_filter($this->filledOptions($data), fn ($o) => $o['is_correct']));

        return $correct === 1 ? null : 'debe tener exactamente una respuesta correcta.';
    }

    public function grade(Question $question, mixed $answer): float
    {
        $option = $question->options->first(fn ($o) => (string) $o->id === self::clean($answer));

        return $option && $option->is_correct ? 1.0 : 0.0;
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        $option = $question->options->first(fn ($o) => (string) $o->id === self::clean($answer));

        return $option?->content ?? 'Selección no disponible';
    }
}
