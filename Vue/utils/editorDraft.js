// Copia local del editor de tests, para no perder el trabajo si se cierra la pestaña.
//
// Vive solo en este navegador (localStorage). La clave incluye el id del usuario
// para que en un ordenador compartido nadie vea el borrador de otro, y al cerrar
// sesión se borran todos. Cualquier acceso puede fallar (modo privado, almacenamiento
// bloqueado o lleno): en ese caso el editor funciona igual, solo que sin copia.

const PREFIX = 'testmaster:draft:';
const VERSION = 1;
const MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000;

export const draftKey = (userId, testId) => `${PREFIX}${userId}:${testId ?? 'new'}`;

export function readDraft(key) {
    try {
        const raw = localStorage.getItem(key);
        if (!raw) return null;
        const draft = JSON.parse(raw);
        if (draft?.v !== VERSION || !draft.savedAt || Date.now() - draft.savedAt > MAX_AGE_MS) {
            localStorage.removeItem(key);
            return null;
        }
        return draft;
    } catch {
        return null;
    }
}

/** @returns {number|null} marca de tiempo del guardado, o null si no se pudo guardar */
export function writeDraft(key, data) {
    try {
        const savedAt = Date.now();
        localStorage.setItem(key, JSON.stringify({ ...data, v: VERSION, savedAt }));
        return savedAt;
    } catch {
        return null;
    }
}

export function removeDraft(key) {
    try {
        localStorage.removeItem(key);
    } catch {
        // sin almacenamiento no hay nada que borrar
    }
}

export function clearAllDrafts() {
    try {
        const keys = [];
        for (let i = 0; i < localStorage.length; i++) keys.push(localStorage.key(i));
        keys.filter((k) => k?.startsWith(PREFIX)).forEach((k) => localStorage.removeItem(k));
    } catch {
        // idem
    }
}
