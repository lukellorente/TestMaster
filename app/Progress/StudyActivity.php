<?php

namespace App\Progress;

use App\Models\TestAttempt;
use Carbon\CarbonImmutable;
use DateTimeZone;

/**
 * Días de estudio y racha de un usuario.
 *
 * Cualquier intento terminado cuenta como actividad, también los repasos de fallos.
 * Los días se cuentan en la zona horaria del usuario (la envía el navegador), no en
 * la del servidor: si no, estudiar a las 00:30 contaría para el día anterior.
 *
 * Se calcula a partir de los intentos en lugar de guardarse, así nunca se desincroniza
 * y funciona también con el historial anterior a esta funcionalidad.
 */
final class StudyActivity
{
    public static function timezone(?string $tz): string
    {
        return $tz && in_array($tz, DateTimeZone::listIdentifiers(), true) ? $tz : config('app.timezone');
    }

    /**
     * @return array{
     *   days: array<string, int>, current_streak: int, longest_streak: int,
     *   studied_today: bool, active_days: int, today: string
     * }
     */
    public function summary(int $userId, string $timezone, int $calendarDays = 182, ?CarbonImmutable $now = null): array
    {
        $today = ($now ?? CarbonImmutable::now())->setTimezone($timezone)->startOfDay();

        /** @var array<string, int> $perDay recuento de intentos por día (Y-m-d), ordenado */
        $perDay = TestAttempt::where('user_id', $userId)
            ->where('is_completed', true)
            ->whereNotNull('completed_at')
            ->pluck('completed_at')
            ->countBy(fn ($at) => CarbonImmutable::parse($at)->setTimezone($timezone)->toDateString())
            ->sortKeys()
            ->all();

        $calendarStart = $today->subDays($calendarDays - 1)->toDateString();

        return [
            'today' => $today->toDateString(),
            'days' => array_filter($perDay, fn ($date) => $date >= $calendarStart, ARRAY_FILTER_USE_KEY),
            'current_streak' => $this->currentStreak($perDay, $today),
            'longest_streak' => $this->longestStreak(array_keys($perDay)),
            'studied_today' => isset($perDay[$today->toDateString()]),
            'active_days' => count($perDay),
        ];
    }

    /** La racha sigue viva durante hoy aunque aún no se haya estudiado: se cuenta desde ayer. */
    private function currentStreak(array $perDay, CarbonImmutable $today): int
    {
        $day = isset($perDay[$today->toDateString()]) ? $today : $today->subDay();
        $streak = 0;

        while (isset($perDay[$day->toDateString()])) {
            $streak++;
            $day = $day->subDay();
        }

        return $streak;
    }

    /** @param  array<int, string>  $dates  días con actividad, ordenados */
    private function longestStreak(array $dates): int
    {
        $longest = 0;
        $run = 0;
        $previous = null;

        foreach ($dates as $date) {
            $day = CarbonImmutable::parse($date);
            $run = $previous && $previous->addDay()->isSameDay($day) ? $run + 1 : 1;
            $longest = max($longest, $run);
            $previous = $day;
        }

        return $longest;
    }
}
