<template>
  <div :class="{ 'dark-mode': isDark }">
    <AppHeader />
    <main>
      <router-view />
    </main>
    <AppFooter />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppHeader from './components/AppHeader.vue';
import AppFooter from './components/AppFooter.vue';
import './assets/styles.css';

const isDark = ref(false);

onMounted(() => {
  const theme = localStorage.getItem('theme');
  if (theme === 'dark') {
    isDark.value = true;
    document.body.classList.add('dark-mode');
  }
});

// Provide toggle globally
window.toggleTheme = () => {
  isDark.value = !isDark.value;
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
};
</script>
