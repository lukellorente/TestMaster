import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isLoggedIn: (state) => !!state.token,
        userId: (state) => state.user?.id,
        userName: (state) => state.user?.name,
        userRole: (state) => state.user?.role,
    },

    actions: {
        async login(email, password) {
            const { data } = await api.post('/login', { email, password });
            this.setAuth(data.user, data.token);
            return data;
        },

        async register(name, email, password, password_confirmation) {
            const { data } = await api.post('/register', {
                name, email, password, password_confirmation,
            });
            this.setAuth(data.user, data.token);
            return data;
        },

        async logout() {
            try {
                await api.post('/logout');
            } catch (e) {
                // ignore
            }
            this.clearAuth();
        },

        async fetchUser() {
            try {
                const { data } = await api.get('/me');
                this.user = data.user;
                localStorage.setItem('user', JSON.stringify(data.user));
            } catch {
                this.clearAuth();
            }
        },

        setAuth(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('token', token);
        },

        clearAuth() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
        },
    },
});
