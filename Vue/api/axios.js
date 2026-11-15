import axios from 'axios';

/*
 * Autenticación SPA de Sanctum: la sesión viaja en una cookie httpOnly que
 * JavaScript no puede leer, y axios reenvía la cookie XSRF-TOKEN como cabecera
 * X-XSRF-TOKEN en cada petición mutante.
 */
const api = axios.create({
    baseURL: '/api',
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export function getCsrfCookie() {
    return axios.get('/sanctum/csrf-cookie', { withCredentials: true });
}

let onUnauthenticated = null;
export function setUnauthenticatedHandler(fn) {
    onUnauthenticated = fn;
}

api.interceptors.response.use(
    (response) => response,
    async (error) => {
        const status = error.response?.status;
        const config = error.config || {};

        // 419 = token CSRF caducado: se renueva y se reintenta una vez.
        if (status === 419 && !config._csrfRetried) {
            config._csrfRetried = true;
            await getCsrfCookie();
            return api(config);
        }

        if (status === 401 && !config.skipAuthRedirect && onUnauthenticated) {
            onUnauthenticated();
        }
        return Promise.reject(error);
    }
);

export default api;
