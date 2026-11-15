import { reactive } from 'vue';

const toasts = reactive([]);
let nextId = 1;

function dismiss(id) {
    const i = toasts.findIndex((t) => t.id === id);
    if (i !== -1) toasts.splice(i, 1);
}

function push(message, variant = 'info', timeout = 5000) {
    const id = nextId++;
    toasts.push({ id, message, variant });
    if (timeout) setTimeout(() => dismiss(id), timeout);
    return id;
}

/** Avisos no bloqueantes que sustituyen a window.alert(). */
export function useToast() {
    return {
        toasts,
        dismiss,
        info: (msg, timeout) => push(msg, 'info', timeout),
        success: (msg, timeout) => push(msg, 'success', timeout),
        error: (msg, timeout = 7000) => push(msg, 'error', timeout),
    };
}
