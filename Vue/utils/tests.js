// Utilidades compartidas para mostrar tests

const DIFFICULTY = {
    easy: { label: 'Fácil', cls: 'pill--easy' },
    medium: { label: 'Media', cls: 'pill--medium' },
    hard: { label: 'Difícil', cls: 'pill--hard' },
};

export const difficultyLabel = (d) => DIFFICULTY[d]?.label ?? d ?? '';
export const difficultyClass = (d) => DIFFICULTY[d]?.cls ?? 'pill--neutral';

export const plural = (n, one, many) => `${n} ${n === 1 ? one : many}`;

// ─── Tipos de pregunta ───────────────────────────────────────────────────────
export const QUESTION_TYPES = {
    multiple_choice: { label: 'Opción múltiple', hint: 'Una respuesta correcta' },
    multiple_select: { label: 'Selección múltiple', hint: 'Varias respuestas correctas' },
    true_false: { label: 'Verdadero o falso', hint: 'Afirmación a valorar' },
    short_answer: { label: 'Respuesta corta', hint: 'Texto libre' },
    fill_blank: { label: 'Rellenar huecos', hint: 'Completar una frase' },
    order_items: { label: 'Ordenar', hint: 'Pasos, fechas, procesos' },
    numeric: { label: 'Numérica', hint: 'Número con margen' },
};

export const questionTypeLabel = (t) => QUESTION_TYPES[t]?.label ?? t ?? '';

/** Marca de hueco en el enunciado de «Rellenar huecos»: tres o más guiones bajos. */
export const BLANK_MARKER = /_{3,}/g;

export const countBlanks = (text) => (String(text ?? '').match(BLANK_MARKER) || []).length;

/** "La capital es ___." → "La capital es (1) ____." */
export const numberBlanks = (text) => {
    let n = 0;
    return String(text ?? '').replace(BLANK_MARKER, () => `(${++n}) ____`);
};

/** ¿Ha respondido algo? En listas basta con un valor no vacío (igual que el backend). */
export function isAnswerFilled(value) {
    if (value === undefined || value === null) return false;
    if (Array.isArray(value)) return value.some(isAnswerFilled);
    return String(value).trim() !== '';
}

// ─── Penalización por fallo ──────────────────────────────────────────────────
export const PENALTY_OPTIONS = [
    { value: 0, label: 'Sin penalización' },
    { value: 0.25, label: 'Resta 1/4 por fallo' },
    { value: 0.3333, label: 'Resta 1/3 por fallo' },
    { value: 0.5, label: 'Resta 1/2 por fallo' },
];

/** 0.3333 → "1/3"; 0.4 → "40 %"; 0 → '' */
export function penaltyLabel(value) {
    const v = Number(value) || 0;
    if (v <= 0) return '';
    const known = { 0.25: '1/4', 0.3333: '1/3', 0.5: '1/2' };
    const key = Object.keys(known).find((k) => Math.abs(Number(k) - v) < 0.001);
    return key ? known[key] : `${Math.round(v * 100)} %`;
}

/** 1.5 → "1,5"; 2 → "2" */
export const formatPoints = (n) =>
    Number(n || 0).toLocaleString('es-ES', { maximumFractionDigits: 2 });

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
