<?php

namespace App\Questions\Types;

use App\Models\Question;

/**
 * Respuesta numérica con margen. Editor: `numeric_answer` y `numeric_tolerance` (± opcional).
 * Acepta coma o punto decimal. Todo o nada.
 */
class NumericType extends BaseType
{
    /** Convierte '3,5' o ' 3.5 ' en 3.5; devuelve null si no es un número. */
    public static function parse(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace([',', ' '], ['.', ''], self::clean($value));

        return is_numeric($text) ? (float) $text : null;
    }

    public function validate(array $data): ?string
    {
        if (self::parse($data['numeric_answer'] ?? null) === null) {
            return 'necesita una respuesta numérica.';
        }

        $tolerance = $data['numeric_tolerance'] ?? null;
        if ($tolerance !== null && $tolerance !== '' && ((self::parse($tolerance) ?? -1) < 0)) {
            return 'tiene un margen no válido (debe ser 0 o positivo).';
        }

        return null;
    }

    public function attributes(array $data): array
    {
        return [
            'numeric_answer' => self::parse($data['numeric_answer'] ?? null),
            'numeric_tolerance' => self::parse($data['numeric_tolerance'] ?? null) ?? 0.0,
        ];
    }

    public function grade(Question $question, mixed $answer): float
    {
        $given = self::parse($answer);
        if ($given === null || $question->numeric_answer === null) {
            return 0.0;
        }

        // Pequeño épsilon para que 0.1 + 0.2 = 0.3 no falle por coma flotante.
        $tolerance = (float) ($question->numeric_tolerance ?? 0) + 1e-9;

        return abs($given - (float) $question->numeric_answer) <= $tolerance ? 1.0 : 0.0;
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        return self::clean($answer);
    }

    public function correctLabel(Question $question): string
    {
        if ($question->numeric_answer === null) {
            return '';
        }

        $label = self::format($question->numeric_answer);
        $tolerance = (float) ($question->numeric_tolerance ?? 0);

        return $tolerance > 0 ? $label.' (± '.self::format($tolerance).')' : $label;
    }

    /** 2.500000 → "2,5" */
    private static function format(float $value): string
    {
        $text = rtrim(rtrim(number_format($value, 6, ',', ''), '0'), ',');

        return $text === '-0' ? '0' : $text;
    }
}
