<template>
  <AppHeader v-if="!route.meta.hideChrome" />
  <router-view v-if="isRedesigned" />
  <!-- Las páginas antiguas esperan el <main> contenedor de siempre -->
  <main v-else class="legacy">
    <router-view />
  </main>
  <AppFooter />
  <ToastStack />
</template>

<script setup>
import { computed, provide } from 'vue';
import { useRoute } from 'vue-router';
import AppHeader from './components/AppHeader.vue';
import AppFooter from './components/AppFooter.vue';
import ToastStack from './components/base/ToastStack.vue';
import { useTheme } from './composables/useTheme';
import 'theme-toggles/css/within.min.css';
import './assets/tokens.css';
import './assets/styles.css';

const route = useRoute();
const { isDark, toggleTheme } = useTheme();

// Las páginas rediseñadas marcan `meta.redesigned` en el router;
// el resto sigue usando las reglas antiguas de modo oscuro (.legacy).
const isRedesigned = computed(() => Boolean(route.meta.redesigned));

provide('isDark', isDark);
provide('toggleTheme', toggleTheme);
window.toggleTheme = toggleTheme;
</script>
