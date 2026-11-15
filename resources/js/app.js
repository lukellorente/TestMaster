import './bootstrap';
// Fuentes e iconos servidos desde nuestro dominio (sin enviar la IP del visitante a Google/CDN).
import '@fontsource/wix-madefor-text/400.css';
import '@fontsource/wix-madefor-text/500.css';
import '@fontsource/wix-madefor-text/600.css';
import '@fontsource/wix-madefor-text/700.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from '../../Vue/router';
import App from '../../Vue/App.vue';

import { setUnauthenticatedHandler } from '../../Vue/api/axios';
import { useAuthStore } from '../../Vue/stores/auth';

const app = createApp(App);
app.use(createPinia());
app.use(router);

// Si la sesión caduca, limpia el usuario y manda a login solo desde rutas protegidas.
setUnauthenticatedHandler(() => {
    useAuthStore().clearAuth();
    const current = router.currentRoute.value;
    if (current.meta?.requiresAuth) {
        router.push({ name: 'login', query: { redirect: current.fullPath } });
    }
});

app.mount('#app');