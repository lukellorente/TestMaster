<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Models\QuestionOrderItem;

/**
 * Ordenar elementos. Editor: `items: ['primero', 'segundo', ...]` en el orden correcto.
 * Respuesta del alumno: lista de ids de elemento en el orden elegido.
 * Puntuación parcial: elementos en su posición correcta / total.
 */
class OrderItemsType extends BaseType
{
    private const MIN_ITEMS = 2;

    private const MAX_ITEMS = 10;

    /** @return array<int, string> */
    private function itemsFromPayload(array $data): array
    {
        return array_values(array_filter(
            array_map(fn ($item) => self::clean(is_array($item) ? ($item['content'] ?? '') : $item), (array) ($data['items'] ?? [])),
            fn ($item) => $item !== '',
        ));
    }

    public function validate(array $data): ?string
    {
        $count = count($this->itemsFromPayload($data));

        if ($count < self::MIN_ITEMS) {
            return 'necesita al menos '.self::MIN_ITEMS.' elementos.';
        }

        return $count > self::MAX_ITEMS ? 'admite como máximo '.self::MAX_ITEMS.' elementos.' : null;
    }

    public function store(Question $question, array $data): void
    {
        $rows = [];
        foreach ($this->itemsFromPayload($data) as $index => $content) {
            $rows[] = [
                'question_id' => $question->id,
                'content' => $content,
                'correct_order' => $index + 1,
            ];
        }

        self::syncRows($question->orderItems()->get(), $rows, QuestionOrderItem::class);
    }

    /** Siempre se desordenan, y nunca se entregan ya en el orden correcto. */
    public function forTaking(Question $question, bool $shuffle): array
    {
        $items = $question->orderItems->map(fn ($item) => ['id' => $item->id, 'content' => $item->content]);
        $correctIds = $items->pluck('id')->all();

        $shuffled = $items->shuffle();
        for ($tries = 0; $items->count() > 1 && $shuffled->pluck('id')->all() === $correctIds && $tries < 10; $tries++) {
            $shuffled = $items->shuffle();
        }
        if ($items->count() > 1 && $shuffled->pluck('id')->all() === $correctIds) {
            $shuffled = $items->reverse();
        }

        return ['items' => $shuffled->values()->all()];
    }

    /** @return array<int, int> ids enviados por el alumno, solo si son una permutación válida */
    private function givenIds(Question $question, mixed $answer): array
    {
        $given = array_map('intval', array_values(is_array($answer) ? $answer : []));
        $valid = $question->orderItems->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        $sorted = $given;
        sort($sorted);

        return $sorted === $valid ? $given : [];
    }

    public function grade(Question $question, mixed $answer): float
    {
        $correctIds = $question->orderItems->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $given = $this->givenIds($question, $answer);

        if (! $correctIds || ! $given) {
            return 0.0;
        }

        $hits = count(array_filter($correctIds, fn ($id, $pos) => $given[$pos] === $id, ARRAY_FILTER_USE_BOTH));

        return $hits / count($correctIds);
    }

    public function answerLabel(Question $question, mixed $answer): string
    {
        $byId = $question->orderItems->keyBy('id');
        $given = array_map('intval', array_values(is_array($answer) ? $answer : []));

        return collect($given)
            ->map(fn ($id) => $byId->get($id)?->content)
            ->filter()
            ->implode(' → ');
    }

    public function correctLabel(Question $question): string
    {
        return $question->orderItems->pluck('content')->implode(' → ');
    }
}
