<template>
  <div class="page-wrap nf">
    <p class="nf__code tabular">404</p>
    <h1 class="nf__title">No encontramos esta página</h1>
    <p class="nf__text">
      Puede que el enlace esté mal escrito o que la página se haya movido.
      Si llegaste aquí desde un test, es posible que su autor lo haya retirado.
    </p>
    <div class="nf__actions">
      <router-link to="/" class="btn btn--lg">Ir al catálogo</router-link>
      <button v-if="canGoBack" type="button" class="btn btn--secondary btn--lg" @click="router.back()">Volver atrás</button>
      <router-link to="/help" class="btn btn--ghost btn--lg">Centro de ayuda</router-link>
    </div>
    <p class="nf__path">Dirección: <code>{{ route.fullPath }}</code></p>
  </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
// Solo ofrecemos «Volver» si hay una página anterior dentro de la app
const canGoBack = Boolean(window.history.state?.back);
</script>

<style scoped>
.nf {
  padding-top: 96px;
  max-width: 720px;
  margin-left: auto;
  margin-right: auto;
}

.nf__code {
  font-size: 1rem;
  font-weight: 600;
  color: var(--accent);
  margin: 0 0 12px;
}

.nf__title {
  font-size: 2.25rem;
  letter-spacing: -0.025em;
  margin: 0 0 12px;
}

.nf__text {
  font-size: var(--fs-lead);
  color: var(--text-body);
  margin: 0 0 28px;
}

.nf__actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.nf__path {
  margin: 40px 0 0;
  font-size: var(--fs-meta);
  color: var(--muted);
  overflow-wrap: anywhere;
}

.nf__path code {
  font-size: inherit;
  background: var(--raised);
  padding: 2px 6px;
  border-radius: 4px;
}

@media (max-width: 640px) {
  .nf {
    padding-top: 48px;
  }

  .nf__title {
    font-size: 1.75rem;
  }
}
</style>
