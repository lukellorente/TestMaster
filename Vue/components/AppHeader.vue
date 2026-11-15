<template>
  <header class="app-header">
    <div class="page-wrap app-header__inner">
      <router-link to="/" class="app-header__brand" @click="closeMenu">
        <img src="/images/logo.svg" alt="TestMaster, inicio" class="logo" />
      </router-link>

      <nav class="app-header__nav" aria-label="Principal">
        <router-link to="/" class="nav-link">Explorar</router-link>
        <router-link v-if="authStore.isLoggedIn" to="/dashboard" class="nav-link">Mi progreso</router-link>
        <router-link v-if="authStore.isAdmin" to="/admin" class="nav-link">Admin</router-link>
        <router-link to="/help" class="nav-link">Ayuda</router-link>
      </nav>

      <span class="app-header__spacer"></span>

      <div class="app-header__actions">
        <template v-if="authStore.isLoggedIn">
          <router-link to="/create-test" class="btn hide-mobile">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>Crear test
          </router-link>
        </template>
        <template v-else>
          <router-link to="/login" class="btn btn--ghost hide-mobile">Iniciar sesión</router-link>
          <router-link to="/register" class="btn">Crear cuenta</router-link>
        </template>

        <button
          class="icon-btn"
          type="button"
          :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
          :title="isDark ? 'Modo claro' : 'Modo oscuro'"
          @click="toggleTheme"
        >
          <i :class="isDark ? 'fa-regular fa-sun' : 'fa-regular fa-moon'" aria-hidden="true"></i>
        </button>

        <template v-if="authStore.isLoggedIn">
          <router-link
            :to="`/profile/${authStore.userId}`"
            class="icon-btn hide-mobile"
            :aria-label="`Perfil de ${authStore.userName}`"
          >
            <UserAvatar :user="authStore.user" size="small" />
          </router-link>
          <button type="button" class="icon-btn hide-mobile" aria-label="Cerrar sesión" title="Cerrar sesión" @click="handleLogout">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
          </button>
        </template>

        <button
          class="icon-btn menu-toggle"
          type="button"
          :aria-expanded="menuOpen ? 'true' : 'false'"
          aria-controls="mobile-menu"
          :aria-label="menuOpen ? 'Cerrar menú' : 'Abrir menú'"
          @click="menuOpen = !menuOpen"
        >
          <i class="fa-solid" :class="menuOpen ? 'fa-xmark' : 'fa-bars'" aria-hidden="true"></i>
        </button>
      </div>
    </div>

    <nav id="mobile-menu" class="mobile-menu" :class="{ 'is-open': menuOpen }" aria-label="Menú móvil">
      <router-link to="/" class="nav-link" @click="closeMenu">Explorar</router-link>
      <template v-if="authStore.isLoggedIn">
        <router-link to="/dashboard" class="nav-link" @click="closeMenu">Mi progreso</router-link>
        <router-link :to="`/profile/${authStore.userId}`" class="nav-link" @click="closeMenu">Mi perfil</router-link>
        <router-link v-if="authStore.isAdmin" to="/admin" class="nav-link" @click="closeMenu">Admin</router-link>
        <router-link to="/create-test" class="nav-link" @click="closeMenu">Crear test</router-link>
        <router-link to="/help" class="nav-link" @click="closeMenu">Ayuda</router-link>
        <a href="#" class="nav-link" @click.prevent="handleLogout">Cerrar sesión</a>
      </template>
      <template v-else>
        <router-link to="/help" class="nav-link" @click="closeMenu">Ayuda</router-link>
        <router-link to="/login" class="nav-link" @click="closeMenu">Iniciar sesión</router-link>
      </template>
    </nav>
  </header>
</template>

<script setup>
import { ref, inject, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import UserAvatar from './user/UserAvatar.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const menuOpen = ref(false);

const isDark = inject('isDark');
const toggleTheme = inject('toggleTheme');

const closeMenu = () => {
  menuOpen.value = false;
};

watch(() => route.fullPath, closeMenu);

const handleLogout = async () => {
  await authStore.logout();
  closeMenu();
  router.push('/');
};
</script>
