<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Models\QuestionBlank;

/**
 * Rellenar huecos. En el enunciado, cada hueco se escribe como tres o más guiones bajos (___).
 *
 * Editor: `blanks: [{answers: ['París', 'Paris']}, ...]` en el orden de los huecos y
 * `case_sensitive: bool`. Respuesta del alumno: lista de textos, uno por hueco.
 * Puntuación parcial: huecos acertados / total de huecos.
 */
class FillBlankType extends BaseType
{
    public const MARKER = '/_{3,}/';

    private const MAX_BLANKS = 20;

    public static function countBlanks(string $content): int
    {
        return preg_match_all(self::MARKER, $content);
    }

    /** @return array<int, array<int, string>> respuestas aceptadas por hueco */
    private function acceptedFromPayload(array $data): array
    {
        return array_map(
            fn ($blank) => array_values(array_filter(
                array_map(fn ($a) => self::clean($a), (array) (is_array($blank) ? ($blank['answers'] ?? []) : $blank)),
                fn ($a) => $a !== '',
            )),
            array_values((array) ($data['blanks'] ?? [])),
        );
    }

    public function validate(array $data): ?string
    {
        $count = self::countBlanks(self::clean($data['content'] ?? ''));

        if ($count === 0) {
            return 'necesita al menos un hueco (escribe ___ en el enunciado).';
        }

        if ($count > self::MAX_BLANKS) {
            return 'admite como máximo '.self::MAX_BLANKS.' huecos.';
        }

        $accepted = $this->acceptedFromPayload($data);

        if (count($accepted) !== $count) {
            return 'tiene '.$count.' huecos pero '.count($accepted).' respuestas.';
        }

        foreach ($accepted as $index => $answers) {
            if (! $answers) {
                return 'necesita la respuesta del hueco '.($index + 1).'.';
            }
        }

        return null;
    }

    public function store(Question $question, array $data): void
    {
        $caseSensitive = filter_var($data['case_sensitive'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $rows = [];
        foreach ($this->acceptedFromPayload($data) as $index => $answers) {
            $rows[] = [
                'question_id' => $question->id,
                'blank_index' => $index,
                'correct_answers' => $answers,
                'case_sensitive' => $caseSensitive,
            ];
        }

        self::syncRows($question->blanks()->get(), $rows, QuestionBlank::class);
    }

    public function forTaking(Question $question, bool $shuffle): array
    {
        return ['blank_count' => $question->blanks->count()];
    }

    private static function givenList(mixed $answer): array
    {
        return array_values(is_array($answer) ? $answer : [$answer]);
    }

    public function grade(Question $question, mixed $answer): float
    {
        $blanks = $question->blanks->values();
        if ($blanks->isEmpty()) {
            return 0.0;
        }

        $given = self::givenList($answer);
        $hits = 0;

        foreach ($blanks as $index => $blank) {
            $value = self::normalize($given[$index] ?? '', $blank->case_sensitive);
            if ($value === '') {
                continue;
            }

            foreach ((array) $blank->correct_answers as $accepted) {
                if (self::normalize($accepted, $blank->case_sensitive) === $value) {
                    $hits++;
                    break;
                }
            }
        }

        return $hits / $blanks->count();
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        $given = self::givenList($answer);

        return $question->blanks->values()
            ->map(fn ($blank, $i) => self::clean($given[$i] ?? '') ?: '—')
            ->implode(' · ');
    }

    public function correctLabel(Question $question): string
    {
        return $question->blanks
            ->map(fn ($blank) => implode(' / ', (array) $blank->correct_answers))
            ->implode(' · ');
    }
}
