<?php

namespace App\Questions\Types;

use App\Models\Question;

/**
 * Varias respuestas correctas. La respuesta del alumno es una lista de ids.
 *
 * Puntuación parcial: (aciertos marcados − errores marcados) / total de correctas,
 * nunca por debajo de 0. Así marcar todas las opciones no da puntos gratis.
 */
class MultipleSelectType extends OptionsType
{
    public function validate(array $data): ?string
    {
        if ($error = $this->validateOptions($data)) {
            return $error;
        }

        $correct = count(array_filter($this->filledOptions($data), fn ($o) => $o['is_correct']));

        return $correct >= 1 ? null : 'debe tener al menos una respuesta correcta.';
    }

    /** @return array<int, string> ids de opción válidos y sin repetir */
    private function selectedIds(Question $question, mixed $answer): array
    {
        $valid = $question->options->pluck('id')->map(fn ($id) => (string) $id)->all();
        $given = array_map(fn ($id) => self::clean($id), is_array($answer) ? $answer : [$answer]);

        return array_values(array_unique(array_intersect($given, $valid)));
    }

    public function grade(Question $question, mixed $answer): float
    {
        $correct = $question->options->where('is_correct', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
        if (! $correct) {
            return 0.0;
        }

        $selected = $this->selectedIds($question, $answer);
        $hits = count(array_intersect($selected, $correct));
        $misses = count($selected) - $hits;

        return max(0.0, ($hits - $misses) / count($correct));
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        $selected = $this->selectedIds($question, $answer);

        return $question->options
            ->filter(fn ($o) => in_array((string) $o->id, $selected, true))
            ->pluck('content')
            ->implode(', ');
    }
}
