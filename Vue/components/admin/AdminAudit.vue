<template>
  <div>
    <div class="adm-filters">
      <label class="sr-only" for="aa-type">Tipo</label>
      <select id="aa-type" v-model="type" class="form-input" @change="load(1)">
        <option value="">Todas las acciones</option>
        <option value="test">Tests</option>
        <option value="user">Usuarios</option>
        <option value="category">Categorías</option>
      </select>
    </div>

    <BaseLoader v-if="loading && !rows.length" text="Cargando registro…" />
    <p v-else-if="error" class="adm-error" role="alert">{{ error }}</p>
    <p v-else-if="!rows.length" class="adm-empty">Todavía no hay acciones registradas.</p>

    <ol v-else class="adm-list" :class="{ 'is-busy': loading }">
      <li v-for="e in rows" :key="e.id" class="adm-row">
        <span class="aa__icon" aria-hidden="true"><i :class="iconFor(e)"></i></span>
        <div class="adm-row__main">
          <span>
            <strong>{{ e.admin?.name ?? 'Admin eliminado' }}</strong> {{ describe(e) }}
          </span>
          <span class="adm-muted tabular">{{ formatDateTime(e.created_at) }}</span>
        </div>
      </li>
    </ol>

    <AdminPager :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :busy="loading" @go="load" />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../api/axios';
import BaseLoader from '../base/BaseLoader.vue';
import AdminPager from './AdminPager.vue';

const type = ref('');
const rows = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');

const STATUS = { published: 'publicado', draft: 'borrador', archived: 'archivado' };
const ROLE = { user: 'usuario', admin: 'administrador', premium: 'premium', educator: 'educador' };

const formatDateTime = (iso) =>
  new Date(iso).toLocaleString('es-ES', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

function describe(e) {
  const d = e.details ?? {};
  const test = d.title ? `el test «${d.title}»` : `el test #${e.target_id}`;
  const user = `al usuario #${e.target_id}`;
  const category = d.name ? `la categoría «${d.name}»` : `la categoría #${e.target_id}`;
  switch (e.action) {
    case 'test.featured': return `destacó el test #${e.target_id}`;
    case 'test.unfeatured': return d.reason === 'status' ? `quitó de destacados el test #${e.target_id} al cambiar su estado` : `quitó de destacados el test #${e.target_id}`;
    case 'test.status': return `cambió el test #${e.target_id} de ${STATUS[d.from] ?? d.from} a ${STATUS[d.to] ?? d.to}`;
    case 'test.deleted': return `eliminó ${test}`;
    case 'user.role': return `cambió el rol ${user} de ${ROLE[d.from] ?? d.from} a ${ROLE[d.to] ?? d.to}`;
    case 'user.suspended': return `suspendió la cuenta del usuario #${e.target_id}`;
    case 'user.reactivated': return `reactivó la cuenta del usuario #${e.target_id}`;
    case 'category.created': return `creó ${category}`;
    case 'category.updated': return `editó la categoría #${e.target_id}${d.fields?.length ? ` (${d.fields.join(', ')})` : ''}`;
    case 'category.deleted': return `eliminó ${category}${d.tests ? `, que tenía ${d.tests} tests` : ''}`;
    default: return `${e.action} sobre ${e.target_type} #${e.target_id}`;
  }
}

const iconFor = (e) => ({
  test: 'fa-regular fa-file-lines',
  user: 'fa-regular fa-user',
  category: 'fa-solid fa-tag',
}[e.target_type] ?? 'fa-solid fa-circle-info');

async function load(page = 1) {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await api.get('/admin/audit', { params: { page, ...(type.value ? { target_type: type.value } : {}) } });
    rows.value = data.data;
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
  } catch {
    error.value = 'No se ha podido cargar el registro.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => load(1));
</script>

<style scoped>
.aa__icon {
  display: inline-grid;
  place-items: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--sunk);
  color: var(--muted);
  flex-shrink: 0;
}
</style>
