import { defineStore } from 'pinia';
import api, { getCsrfCookie } from '../api/axios';

// La sesión vive en una cookie httpOnly; aquí solo se guarda el usuario en memoria.
// Limpia restos del antiguo sistema de tokens Bearer en localStorage.
try {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
} catch { /* almacenamiento no disponible */ }

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        initialized: false,
    }),

    getters: {
        isLoggedIn: (state) => !!state.user,
        userId: (state) => state.user?.id,
        userName: (state) => state.user?.name,
        userRole: (state) => state.user?.role,
    },

    actions: {
        async init() {
            if (this.initialized) return;
            await this.fetchUser();
            this.initialized = true;
        },

        async login(email, password) {
            await getCsrfCookie();
            const { data } = await api.post('/login', { email, password });
            this.user = data.user;
            return data;
        },

        async register(name, email, password, password_confirmation, accept_terms) {
            await getCsrfCookie();
            const { data } = await api.post('/register', {
                name, email, password, password_confirmation, accept_terms,
            });
            this.user = data.user;
            return data;
        },

        async logout() {
            try {
                await api.post('/logout');
            } catch {
                // ignore
            }
            this.clearAuth();
        },

        async fetchUser() {
            try {
                const { data } = await api.get('/me', { skipAuthRedirect: true });
                this.user = data.user;
            } catch {
                this.clearAuth();
            }
        },

        async exportData() {
            const { data } = await api.get('/account/export');
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'testmaster-mis-datos.json';
            a.click();
            URL.revokeObjectURL(url);
        },

        async deleteAccount(password, deleteTests) {
            const { data } = await api.delete('/account', {
                data: { password, delete_tests: deleteTests },
            });
            this.clearAuth();
            return data;
        },

        clearAuth() {
            this.user = null;
        },
    },
});
