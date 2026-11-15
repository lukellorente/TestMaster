<template>
  <div class="at">
    <form class="adm-filters" role="search" @submit.prevent="load(1)">
      <label class="sr-only" for="at-search">Buscar tests</label>
      <input id="at-search" v-model="filters.search" type="search" class="form-input" placeholder="Título o autor" />
      <label class="sr-only" for="at-status">Estado</label>
      <select id="at-status" v-model="filters.status" class="form-input" @change="load(1)">
        <option value="">Todos los estados</option>
        <option value="published">Publicados</option>
        <option value="draft">Borradores</option>
        <option value="archived">Archivados</option>
      </select>
      <label class="adm-check"><input v-model="filters.featured" type="checkbox" @change="load(1)" />Solo destacados</label>
      <button type="submit" class="btn btn--secondary">Buscar</button>
    </form>

    <BaseLoader v-if="loading && !rows.length" text="Cargando tests…" />
    <p v-else-if="error" class="adm-error" role="alert">{{ error }}</p>
    <p v-else-if="!rows.length" class="adm-empty">No hay tests con estos filtros.</p>

    <ul v-else class="adm-list" :class="{ 'is-busy': loading }">
      <li v-for="t in rows" :key="t.id" class="adm-row">
        <div class="adm-row__main">
          <router-link :to="`/test/${t.id}`" class="adm-row__title">
            <i v-if="t.is_featured" class="fa-solid fa-star at__star" aria-hidden="true"></i>
            <span v-if="t.is_featured" class="sr-only">Destacado. </span>
            <i v-if="t.has_password" class="fa-solid fa-lock adm-muted" aria-hidden="true"></i>
            {{ t.title }}
          </router-link>
          <span class="adm-muted">
            {{ t.user?.name ?? 'Usuario eliminado' }} · {{ statusLabel[t.status] }}{{ t.is_public ? '' : ' · privado' }} ·
            {{ plural(t.questions_count, 'pregunta', 'preguntas') }} · {{ plural(t.completed_attempts_count, 'intento', 'intentos') }}
          </span>
        </div>
        <div class="adm-row__actions">
          <button
            type="button"
            class="btn btn--ghost"
            :disabled="busyId === t.id || (!t.is_featured && !(t.is_public && t.status === 'published'))"
            :title="!t.is_featured && !(t.is_public && t.status === 'published') ? 'Solo se destacan tests públicos y publicados' : ''"
            @click="update(t, { is_featured: !t.is_featured })"
          >
            <i :class="t.is_featured ? 'fa-solid fa-star' : 'fa-regular fa-star'" aria-hidden="true"></i>
            {{ t.is_featured ? 'Quitar destacado' : 'Destacar' }}
          </button>
          <button
            type="button"
            class="btn btn--ghost"
            :disabled="busyId === t.id"
            @click="update(t, { status: t.status === 'archived' ? 'published' : 'archived' })"
          >
            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
            {{ t.status === 'archived' ? 'Publicar' : 'Archivar' }}
          </button>
          <button type="button" class="btn btn--ghost adm-danger" :disabled="busyId === t.id" @click="toDelete = t">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>Eliminar
          </button>
        </div>
      </li>
    </ul>

    <AdminPager :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :busy="loading" @go="load" />

    <BaseModal :model-value="Boolean(toDelete)" title="¿Eliminar este test?" max-width="440px" @update:model-value="toDelete = null">
      <p class="adm-modal-text">
        Se borrará «{{ toDelete?.title }}» con sus preguntas y todos los intentos de los alumnos. No se puede deshacer.
      </p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="toDelete = null">Cancelar</button>
        <button type="button" class="btn btn--danger" :disabled="busyId !== null" @click="remove">Eliminar</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../../api/axios';
import BaseLoader from '../base/BaseLoader.vue';
import BaseModal from '../base/BaseModal.vue';
import AdminPager from './AdminPager.vue';
import { plural } from '../../utils/tests';
import { useToast } from '../../composables/useToast';

const toast = useToast();
const statusLabel = { published: 'publicado', draft: 'borrador', archived: 'archivado' };

const filters = reactive({ search: '', status: '', featured: false });
const rows = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');
const busyId = ref(null);
const toDelete = ref(null);

async function load(page = 1) {
  loading.value = true;
  error.value = '';
  try {
    const params = { page };
    if (filters.search.trim()) params.search = filters.search.trim();
    if (filters.status) params.status = filters.status;
    if (filters.featured) params.featured = 1;
    const { data } = await api.get('/admin/tests', { params });
    rows.value = data.data;
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
  } catch {
    error.value = 'No se han podido cargar los tests.';
  } finally {
    loading.value = false;
  }
}

async function update(test, changes) {
  busyId.value = test.id;
  try {
    const { data } = await api.patch(`/admin/tests/${test.id}`, changes);
    Object.assign(test, { status: data.test.status, is_featured: data.test.is_featured });
    toast.success(data.message);
  } catch (err) {
    const errs = err.response?.data?.errors;
    toast.error(errs ? Object.values(errs).flat()[0] : 'No se ha podido actualizar el test.');
  } finally {
    busyId.value = null;
  }
}

async function remove() {
  const test = toDelete.value;
  busyId.value = test.id;
  try {
    await api.delete(`/admin/tests/${test.id}`);
    toDelete.value = null;
    toast.success('Test eliminado.');
    await load(rows.value.length === 1 && meta.value.current_page > 1 ? meta.value.current_page - 1 : meta.value.current_page);
  } catch {
    toast.error('No se ha podido eliminar el test.');
  } finally {
    busyId.value = null;
  }
}

onMounted(() => load(1));
</script>

<style scoped>
.at__star {
  color: var(--warning);
}
</style>
