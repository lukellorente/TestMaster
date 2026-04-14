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
import Help from '../pages/Help.vue';
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
        meta: { requiresAuth: true },
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
    { path: '/help', name: 'help', component: Help },
    { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFound },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    if (to.meta.requiresAuth && !authStore.isLoggedIn) {
        next({ name: 'login' });
    } else {
        next();
    }
});

export default router;
