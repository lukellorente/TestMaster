<?php

namespace App\Questions;

use App\Questions\Types\FillBlankType;
use App\Questions\Types\MultipleChoiceType;
use App\Questions\Types\MultipleSelectType;
use App\Questions\Types\NumericType;
use App\Questions\Types\OrderItemsType;
use App\Questions\Types\ShortAnswerType;
use App\Questions\Types\TrueFalseType;
use InvalidArgumentException;

final class QuestionTypes
{
    private const MAP = [
        'multiple_choice' => MultipleChoiceType::class,
        'multiple_select' => MultipleSelectType::class,
        'true_false' => TrueFalseType::class,
        'short_answer' => ShortAnswerType::class,
        'fill_blank' => FillBlankType::class,
        'order_items' => OrderItemsType::class,
        'numeric' => NumericType::class,
    ];

    /** Relaciones que hay que cargar para corregir o presentar cualquier tipo sin consultas N+1. */
    public const RELATIONS = ['options', 'blanks', 'orderItems'];

    /** @var array<string, QuestionType> */
    private static array $instances = [];

    /** @return array<int, string> */
    public static function keys(): array
    {
        return array_keys(self::MAP);
    }

    public static function get(string $type): QuestionType
    {
        $class = self::MAP[$type] ?? throw new InvalidArgumentException("Tipo de pregunta desconocido: {$type}");

        return self::$instances[$type] ??= new $class;
    }

    /** ¿Ha respondido algo el alumno? Una lista cuenta si tiene al menos un valor no vacío. */
    public static function isAnswered(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::isAnswered($item)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
