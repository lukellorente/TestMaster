<template>
  <header>
    <div class="header-content">
      <router-link to="/" class="header-brand" @click="closeMobileMenu">
        <img src="/images/logo.svg" alt="TestMaster Logo" class="logo" />
      </router-link>

      <div class="header-actions">
        <div class="header-primary">
          <input
            v-model="searchQuery"
            type="text"
            class="search"
            placeholder="buscar tests..."
            @keyup.enter="doSearch"
          />

          <button
            class="theme-toggle"
            :class="{ 'theme-toggle--toggled': isDark }"
            type="button"
            @click="toggleTheme"
            title="Cambiar tema"
            aria-label="Cambiar tema"
          >
            <span class="theme-toggle-sr">Cambiar tema</span>
            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="theme-toggle__within" height="1em" width="1em" viewBox="0 0 32 32" fill="currentColor">
              <clipPath id="theme-toggle__within__clip">
                <path d="M0 0h32v32h-32ZM6 16A1 1 0 0026 16 1 1 0 006 16" />
              </clipPath>
              <g clip-path="url(#theme-toggle__within__clip)">
                <path d="M30.7 21.3 27.1 16l3.7-5.3c.4-.5.1-1.3-.6-1.4l-6.3-1.1-1.1-6.3c-.1-.6-.8-.9-1.4-.6L16 5l-5.4-3.7c-.5-.4-1.3-.1-1.4.6l-1 6.3-6.4 1.1c-.6.1-.9.9-.6 1.3L4.9 16l-3.7 5.3c-.4.5-.1 1.3.6 1.4l6.3 1.1 1.1 6.3c.1.6.8.9 1.4.6l5.3-3.7 5.3 3.7c.5.4 1.3.1 1.4-.6l1.1-6.3 6.3-1.1c.8-.1 1.1-.8.7-1.4zM16 25.1c-5.1 0-9.1-4.1-9.1-9.1 0-5.1 4.1-9.1 9.1-9.1s9.1 4.1 9.1 9.1c0 5.1-4 9.1-9.1 9.1z" />
              </g>
              <path class="theme-toggle__within__circle" d="M16 7.7c-4.6 0-8.2 3.7-8.2 8.2s3.6 8.4 8.2 8.4 8.2-3.7 8.2-8.2-3.6-8.4-8.2-8.4zm0 14.4c-3.4 0-6.1-2.9-6.1-6.2s2.7-6.1 6.1-6.1c3.4 0 6.1 2.9 6.1 6.2s-2.7 6.1-6.1 6.1z" />
              <path class="theme-toggle__within__inner" d="M16 9.5c-3.6 0-6.4 2.9-6.4 6.4s2.8 6.5 6.4 6.5 6.4-2.9 6.4-6.4-2.8-6.5-6.4-6.5z" />
            </svg>
          </button>

          <button
            class="menu-toggle"
            type="button"
            :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
            aria-controls="header-nav"
            :aria-label="mobileMenuOpen ? 'Cerrar menú' : 'Abrir menú'"
            @click="mobileMenuOpen = !mobileMenuOpen"
          >
            <i class="fa-solid" :class="mobileMenuOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
          </button>
        </div>

        <div id="header-nav" class="header-nav" :class="{ 'is-open': mobileMenuOpen }">
          <template v-if="authStore.isLoggedIn">
            <router-link :to="`/profile/${authStore.userId}`" class="link" @click="closeMobileMenu">mi panel</router-link>
            <BaseButton to="/create-test" @click="closeMobileMenu">crear test</BaseButton>
            <a href="#" class="link" @click.prevent="handleLogout">salir</a>
          </template>
          <template v-else>
            <router-link to="/login" class="link" @click="closeMobileMenu">iniciar sesión</router-link>
            <BaseButton to="/register" @click="closeMobileMenu">registrarse</BaseButton>
          </template>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BaseButton from './base/BaseButton.vue';

const authStore = useAuthStore();
const router = useRouter();
const searchQuery = ref('');
const mobileMenuOpen = ref(false);

const isDark = inject('isDark');
const toggleTheme = inject('toggleTheme');

const handleLogout = async () => {
  await authStore.logout();
  closeMobileMenu();
  router.push('/');
};

const doSearch = () => {
  router.push({ path: '/', query: { search: searchQuery.value } });
  closeMobileMenu();
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
};
</script>
