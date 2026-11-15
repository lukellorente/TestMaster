<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Questions\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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

    /**
     * Sincroniza las filas auxiliares por posición: actualiza las que existen, crea las
     * que faltan y borra las sobrantes. Conserva los ids, que las respuestas guardadas
     * de intentos anteriores referencian (por ejemplo, la opción elegida).
     *
     * @param  Collection<int, Model>  $existing  filas actuales, en orden
     * @param  array<int, array<string, mixed>>  $rows  atributos deseados, en orden
     * @param  class-string<Model>  $model
     */
    protected static function syncRows(Collection $existing, array $rows, string $model): void
    {
        $existing = $existing->values();

        foreach (array_values($rows) as $index => $attributes) {
            $current = $existing->get($index);
            $current ? $current->update($attributes) : $model::create($attributes);
        }

        $existing->slice(count($rows))->each->delete();
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
