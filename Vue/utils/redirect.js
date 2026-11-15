/**
 * Devuelve una ruta interna segura a la que volver tras iniciar sesión.
 * Rechaza URLs absolutas o "//host" para evitar redirecciones abiertas.
 */
export function safeRedirect(value, fallback = '/') {
    const path = Array.isArray(value) ? value[0] : value;
    if (typeof path !== 'string' || !path.startsWith('/') || path.startsWith('//') || path.startsWith('/\\')) {
        return fallback;
    }
    if (path.startsWith('/login') || path.startsWith('/register')) return fallback;
    return path;
}
