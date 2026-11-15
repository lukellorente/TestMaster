<?php

namespace App\Progress;

use App\Questions\ReviewQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dominio por categoría: qué parte de los puntos de las preguntas que has
 * respondido tienes ganados según tu última respuesta a cada una.
 *
 * Usa la última respuesta (no la media histórica) porque refleja lo que sabes ahora:
 * si fallaste una pregunta y la acertaste en un repaso, cuenta como dominada.
 * Las parciales cuentan su parte; las penalizaciones no restan aquí.
 */
final class CategoryMastery
{
    public function __construct(private readonly ReviewQueue $reviewQueue)
    {
    }

    /**
     * @return Collection<int, array{category_id: ?int, name: string, color: ?string, icon: ?string,
     *   questions: int, mastered: int, mastery: float}> de menor a mayor dominio
     */
    public function forUser(int $userId): Collection
    {
        return DB::table('question_responses')
            ->join('questions', 'questions.id', '=', 'question_responses.question_id')
            ->leftJoin('test_categories', 'test_categories.test_id', '=', 'questions.test_id')
            ->leftJoin('categories', 'categories.id', '=', 'test_categories.category_id')
            ->whereIn('question_responses.id', $this->reviewQueue->latestResponseIds($userId)->toBase())
            ->groupBy('categories.id', 'categories.name', 'categories.color', 'categories.icon')
            ->get([
                'categories.id as category_id',
                'categories.name',
                'categories.color',
                'categories.icon',
                DB::raw('COUNT(*) as questions'),
                DB::raw('SUM(CASE WHEN question_responses.is_correct THEN 1 ELSE 0 END) as mastered'),
                DB::raw('SUM(CASE WHEN question_responses.points_earned > 0 THEN question_responses.points_earned ELSE 0 END) as earned'),
                DB::raw('SUM(questions.points) as total'),
            ])
            ->map(fn ($row) => [
                'category_id' => $row->category_id !== null ? (int) $row->category_id : null,
                'name' => $row->name ?? 'Sin categoría',
                'color' => $row->color,
                'icon' => $row->icon,
                'questions' => (int) $row->questions,
                'mastered' => (int) $row->mastered,
                'mastery' => $row->total > 0 ? round(min(1, $row->earned / $row->total) * 100, 1) : 0.0,
            ])
            ->sortBy([['mastery', 'asc'], ['name', 'asc']])
            ->values();
    }
}
