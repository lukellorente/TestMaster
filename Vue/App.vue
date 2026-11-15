<template>
  <AppHeader v-if="!route.meta.hideChrome" />
  <router-view />
  <AppFooter />
  <ToastStack />
</template>

<script setup>
import { provide } from 'vue';
import { useRoute } from 'vue-router';
import AppHeader from './components/AppHeader.vue';
import AppFooter from './components/AppFooter.vue';
import ToastStack from './components/base/ToastStack.vue';
import { useTheme } from './composables/useTheme';
import './assets/tokens.css';
import './assets/styles.css';

const route = useRoute();
const { isDark, toggleTheme } = useTheme();

provide('isDark', isDark);
provide('toggleTheme', toggleTheme);
</script>
