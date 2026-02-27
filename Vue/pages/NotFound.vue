<template>
  <main style="max-width: 600px; margin: 0 auto; text-align: center; padding: 4rem 1.5rem">
    <div style="font-size: 6rem; margin-bottom: 1rem">
      <i class="fa-solid fa-ghost" style="color: #667eea"></i>
    </div>
    <h1 style="font-size: 3rem; font-weight: 700; margin-bottom: 1rem; color: #1a202c">404</h1>
    <p style="font-size: 1.2rem; color: #718096; margin-bottom: 2rem">
      La página que buscas no existe o ha sido movida.
    </p>
    <BaseButton to="/" icon="fa-solid fa-home" style="padding: 0.75rem 2rem; font-size: 1rem">
      Volver al Inicio
    </BaseButton>
  </main>
</template>

<script setup>
import BaseButton from '../components/base/BaseButton.vue';
</script>
