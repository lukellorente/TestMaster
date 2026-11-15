import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import Home from '../pages/Home.vue';
import Login from '../pages/Login.vue';
import Register from '../pages/Register.vue';
import ViewTest from '../pages/ViewTest.vue';
import TakeTest from '../pages/TakeTest.vue';
import Results from '../pages/Results.vue';
import CreateTest from '../pages/CreateTest.vue';
import Profile from '../pages/Profile.vue';
import Dashboard from '../pages/Dashboard.vue';
import Admin from '../pages/Admin.vue';
import Help from '../pages/Help.vue';
import Privacy from '../pages/Privacy.vue';
import Terms from '../pages/Terms.vue';
import Contact from '../pages/Contact.vue';
import NotFound from '../pages/NotFound.vue';

const routes = [
    { path: '/', name: 'home', component: Home },
    { path: '/login', name: 'login', component: Login },
    { path: '/register', name: 'register', component: Register },
    { path: '/test/:id', name: 'view-test', component: ViewTest },
    {
        path: '/test/:id/take',
        name: 'take-test',
        component: TakeTest,
        meta: { requiresAuth: true, hideChrome: true },
    },
    {
        // Repasar mis fallos: misma pantalla, solo con las preguntas pendientes
        path: '/test/:id/review',
        name: 'review-test',
        component: TakeTest,
        meta: { requiresAuth: true, hideChrome: true },
    },
    {
        path: '/results/:attemptId',
        name: 'results',
        component: Results,
        meta: { requiresAuth: true },
    },
    {
        path: '/create-test',
        name: 'create-test',
        component: CreateTest,
        meta: { requiresAuth: true },
    },
    {
        path: '/test/:id/edit',
        name: 'edit-test',
        component: CreateTest,
        meta: { requiresAuth: true },
    },
    { path: '/profile/:id', name: 'profile', component: Profile },
    { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/admin', name: 'admin', component: Admin, meta: { requiresAuth: true, requiresAdmin: true } },
    { path: '/help', name: 'help', component: Help },
    { path: '/privacy', name: 'privacy', component: Privacy }, 
    { path: '/terms', name: 'terms', component: Terms },
    { path: '/contact', name: 'contact', component: Contact },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFound },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    await authStore.init();
    if (to.meta.requiresAuth && !authStore.isLoggedIn) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
        // El servidor también lo comprueba: esto solo evita mostrar una página vacía.
        next({ name: 'not-found', params: { pathMatch: to.path.slice(1).split('/') } });
    } else {
        next();
    }
});

export default router;
