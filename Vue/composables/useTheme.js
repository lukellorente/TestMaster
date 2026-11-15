import { ref } from 'vue';

const STORAGE_KEY = 'theme';
const media = typeof window !== 'undefined' && window.matchMedia
    ? window.matchMedia('(prefers-color-scheme: dark)')
    : null;

function readStored() {
    try {
        const v = localStorage.getItem(STORAGE_KEY);
        return v === 'dark' || v === 'light' ? v : null;
    } catch {
        return null;
    }
}

function resolve() {
    return readStored() ?? (media?.matches ? 'dark' : 'light');
}

// Estado compartido por toda la app
const theme = ref(resolve());
const isDark = ref(theme.value === 'dark');

function apply(value) {
    theme.value = value;
    isDark.value = value === 'dark';
    document.documentElement.dataset.theme = value;
}

let listening = false;

export function useTheme() {
    if (!listening && media) {
        listening = true;
        apply(theme.value);
        // Si el usuario no ha elegido, seguimos al sistema en vivo
        media.addEventListener('change', (e) => {
            if (!readStored()) apply(e.matches ? 'dark' : 'light');
        });
    }

    const toggleTheme = () => {
        const next = isDark.value ? 'light' : 'dark';
        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            /* modo privado: el cambio dura hasta recargar */
        }
        apply(next);
    };

    return { theme, isDark, toggleTheme };
}
