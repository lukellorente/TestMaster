// Utilidades compartidas para mostrar tests

const DIFFICULTY = {
    easy: { label: 'Fácil', cls: 'pill--easy' },
    medium: { label: 'Media', cls: 'pill--medium' },
    hard: { label: 'Difícil', cls: 'pill--hard' },
};

export const difficultyLabel = (d) => DIFFICULTY[d]?.label ?? d ?? '';
export const difficultyClass = (d) => DIFFICULTY[d]?.cls ?? 'pill--neutral';

export const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;

/** "12 preg. · 15 min · 38 intentos" */
export function formatTestMeta(test) {
    const q = test.questions_count ?? test.num_questions ?? 0;
    const attempts = test.completed_attempts_count ?? test.attempts_count ?? 0;
    const time = test.time_limit ? `${Math.round(test.time_limit / 60)} min` : 'sin límite';
    return `${q} preg. · ${time} · ${plural(attempts, 'intento', 'intentos')}`;
}

/** 684 → "11 min 24 s" */
export function formatDuration(seconds) {
    const s = Math.max(0, Math.round(Number(seconds) || 0));
    const m = Math.floor(s / 60);
    const r = s % 60;
    if (!m) return `${r} s`;
    return r ? `${m} min ${r} s` : `${m} min`;
}

/** 684 → "11:24" */
export function formatClock(seconds) {
    const s = Math.max(0, Math.round(Number(seconds) || 0));
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
}

/** Fecha relativa corta: "hoy", "ayer", "hace 3 días", o la fecha. */
export function relativeDay(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const days = Math.floor((Date.now() - d.getTime()) / 86400000);
    if (days <= 0) return 'hoy';
    if (days === 1) return 'ayer';
    if (days < 7) return `hace ${days} días`;
    return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
}
