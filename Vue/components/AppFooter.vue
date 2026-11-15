<template>
  <footer v-if="!hidden" class="page-wrap">
    <div class="app-footer">
      <div>
        <img src="/images/logo.svg" alt="TestMaster" class="app-footer__logo" />
        <p class="app-footer__copy">© {{ year }} TestMaster</p>
      </div>
      <nav class="app-footer__links" aria-label="Pie de página">
        <router-link to="/help" class="link">Ayuda</router-link>
        <router-link to="/contact" class="link">Contacto</router-link>
        <router-link to="/privacy" class="link">Privacidad</router-link>
        <router-link to="/terms" class="link">Términos</router-link>
      </nav>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const year = new Date().getFullYear();
// Mientras se hace un test no hay pie: la pantalla es solo para responder
const hidden = computed(() => Boolean(route.meta.hideChrome));
</script>
