<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Questions\QuestionType;

abstract class BaseType implements QuestionType
{
    public function validate(array $data): ?string
    {
        return null;
    }

    public function attributes(array $data): array
    {
        return [];
    }

    public function store(Question $question, array $data): void
    {
    }

    public function forTaking(Question $question, bool $shuffle): array
    {
        return [];
    }

    /** Compara textos sin tener en cuenta espacios sobrantes y, opcionalmente, mayúsculas. */
    protected static function normalize(mixed $value, bool $caseSensitive = false): string
    {
        $text = is_scalar($value) ? (string) $value : '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $caseSensitive ? $text : mb_strtolower($text);
    }

    protected static function clean(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
