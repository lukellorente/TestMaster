<?php

namespace App\Questions\Types;

use App\Models\Question;
use App\Models\QuestionOption;

/**
 * Base para tipos de opciones (una o varias correctas).
 * El editor envía `options: [{content, is_correct}]`.
 */
abstract class OptionsType extends BaseType
{
    protected const MAX_OPTIONS = 8;

    /** @return array<int, array{content: string, is_correct: bool}> */
    protected function filledOptions(array $data): array
    {
        $options = [];

        foreach (($data['options'] ?? []) as $option) {
            $content = self::clean(is_array($option) ? ($option['content'] ?? '') : $option);
            if ($content === '') {
                continue;
            }

            $options[] = [
                'content' => $content,
                'is_correct' => is_array($option) && filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        return $options;
    }

    protected function validateOptions(array $data): ?string
    {
        $options = $this->filledOptions($data);

        if (count($options) < 2) {
            return 'necesita al menos dos respuestas.';
        }

        if (count($options) > self::MAX_OPTIONS) {
            return 'admite como máximo '.self::MAX_OPTIONS.' respuestas.';
        }

        return null;
    }

    public function store(Question $question, array $data): void
    {
        $rows = [];
        foreach ($this->filledOptions($data) as $index => $option) {
            $rows[] = [
                'question_id' => $question->id,
                'content' => $option['content'],
                'is_correct' => $option['is_correct'],
                'order_index' => $index,
            ];
        }

        self::syncRows($question->options()->get(), $rows, QuestionOption::class);
    }

    public function forTaking(Question $question, bool $shuffle): array
    {
        $options = $question->options
            ->map(fn (QuestionOption $option) => [
                'id' => $option->id,
                'content' => $option->content,
                'image_url' => $option->image_url,
            ])
            ->values();

        return ['options' => $shuffle ? $options->shuffle()->values()->all() : $options->all()];
    }

    public function correctLabel(Question $question): string
    {
        return $question->options->where('is_correct', true)->pluck('content')->implode(', ');
    }
}
