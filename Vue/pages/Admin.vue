<template>
  <div class="page-wrap adm">
    <header class="adm__head">
      <h1 class="adm__title">Administración</h1>
      <p class="adm-muted">Cada cambio que hagas aquí queda en el registro.</p>
    </header>

    <div class="adm__tabs" role="tablist" aria-label="Secciones de administración" @keydown="onTabKey">
      <button
        v-for="t in TABS"
        :id="`tab-${t.id}`"
        :key="t.id"
        type="button"
        role="tab"
        class="adm__tab"
        :aria-selected="String(tab === t.id)"
        :aria-controls="`panel-${t.id}`"
        :tabindex="tab === t.id ? 0 : -1"
        @click="select(t.id)"
      >
        <i :class="t.icon" aria-hidden="true"></i>{{ t.label }}
      </button>
    </div>

    <section :id="`panel-${tab}`" role="tabpanel" :aria-labelledby="`tab-${tab}`" class="adm__panel">
      <KeepAlive>
        <component :is="current.component" :key="tab" />
      </KeepAlive>
    </section>
  </div>
</template>

<script setup>
import { computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminOverview from '../components/admin/AdminOverview.vue';
import AdminTests from '../components/admin/AdminTests.vue';
import AdminUsers from '../components/admin/AdminUsers.vue';
import AdminCategories from '../components/admin/AdminCategories.vue';
import AdminAudit from '../components/admin/AdminAudit.vue';

const TABS = [
  { id: 'resumen', label: 'Resumen', icon: 'fa-solid fa-chart-column', component: AdminOverview },
  { id: 'tests', label: 'Tests', icon: 'fa-regular fa-file-lines', component: AdminTests },
  { id: 'usuarios', label: 'Usuarios', icon: 'fa-regular fa-user', component: AdminUsers },
  { id: 'categorias', label: 'Categorías', icon: 'fa-solid fa-tags', component: AdminCategories },
  { id: 'registro', label: 'Registro', icon: 'fa-solid fa-clock-rotate-left', component: AdminAudit },
];

const route = useRoute();
const router = useRouter();

// La pestaña vive en la URL (?tab=usuarios) para poder enlazarla y volver con «Atrás»
const tab = computed(() => (TABS.some((t) => t.id === route.query.tab) ? route.query.tab : 'resumen'));
const current = computed(() => TABS.find((t) => t.id === tab.value));

function select(id) {
  if (id !== tab.value) router.replace({ query: { ...route.query, tab: id } });
}

// Flechas izquierda/derecha entre pestañas (patrón ARIA de tabs)
async function onTabKey(e) {
  if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
  e.preventDefault();
  const i = TABS.findIndex((t) => t.id === tab.value);
  const next = {
    ArrowLeft: (i - 1 + TABS.length) % TABS.length,
    ArrowRight: (i + 1) % TABS.length,
    Home: 0,
    End: TABS.length - 1,
  }[e.key];
  select(TABS[next].id);
  await nextTick();
  document.getElementById(`tab-${TABS[next].id}`)?.focus();
}
</script>

<style scoped>
.adm {
  padding-top: 32px;
  padding-bottom: 64px;
}

.adm__head {
  margin-bottom: 24px;
}

.adm__title {
  margin: 0 0 4px;
}

.adm__tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 24px;
  overflow-x: auto;
  scrollbar-width: none;
}

.adm__tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border: 0;
  border-bottom: 2px solid transparent;
  margin-bottom: -1px;
  background: none;
  color: var(--text-body);
  font: inherit;
  font-size: var(--fs-small);
  white-space: nowrap;
  cursor: pointer;
}

.adm__tab:hover {
  color: var(--text);
}

.adm__tab[aria-selected='true'] {
  color: var(--text);
  font-weight: 600;
  border-bottom-color: var(--accent);
}

.adm__tab:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: -2px;
}
</style>

<!-- Estilos compartidos por las secciones del panel (componentes hijos) -->
<style>
.adm-muted {
  color: var(--muted);
  font-size: var(--fs-small);
  margin: 0;
}

.adm-error {
  color: var(--negative);
  font-weight: 500;
}

.adm-empty {
  color: var(--muted);
  padding: 24px 0;
}

.adm-modal-text {
  margin: 0;
  color: var(--text-body);
}

.adm-filters {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
}

.adm-filters .form-input {
  width: auto;
  min-width: 0;
  flex: 0 1 220px;
}

.adm-filters input[type='search'] {
  flex: 1 1 240px;
}

.adm-check {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--fs-small);
  cursor: pointer;
}

.adm-check input {
  width: 18px;
  height: 18px;
  accent-color: var(--accent);
}

.adm-list {
  list-style: none;
  margin: 0;
  padding: 0;
  transition: opacity 0.15s;
}

.adm-list.is-busy {
  opacity: 0.5;
}

.adm-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 0;
  border-top: 1px solid var(--border);
}

.adm-row:first-child {
  border-top: 0;
}

.adm-row__main {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  flex: 1;
}

.adm-row__title {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-weight: 600;
  color: var(--text);
  text-decoration: none;
  overflow-wrap: anywhere;
}

a.adm-row__title:hover,
.adm-row__title a:hover {
  text-decoration: underline;
}

.adm-row__title a {
  color: inherit;
  text-decoration: none;
}

.adm-row__actions {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  justify-content: flex-end;
  flex-shrink: 0;
}

.adm-row__actions .btn {
  height: 36px;
  padding: 0 10px;
  font-size: var(--fs-small);
}

.adm-danger:not(:disabled):hover {
  color: var(--negative);
  background: var(--negative-bg);
}

@media (max-width: 720px) {
  .adm-row {
    flex-direction: column;
    align-items: stretch;
  }

  .adm-row__actions {
    justify-content: flex-start;
  }
}
</style>
