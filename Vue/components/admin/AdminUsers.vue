<template>
  <div>
    <form class="adm-filters" role="search" @submit.prevent="load(1)">
      <label class="sr-only" for="au-search">Buscar usuarios</label>
      <input id="au-search" v-model="filters.search" type="search" class="form-input" placeholder="Nombre o correo" />
      <label class="sr-only" for="au-role">Rol</label>
      <select id="au-role" v-model="filters.role" class="form-input" @change="load(1)">
        <option value="">Todos los roles</option>
        <option value="user">Usuarios</option>
        <option value="admin">Administradores</option>
      </select>
      <label class="sr-only" for="au-status">Estado</label>
      <select id="au-status" v-model="filters.status" class="form-input" @change="load(1)">
        <option value="">Activos y suspendidos</option>
        <option value="active">Activos</option>
        <option value="suspended">Suspendidos</option>
      </select>
      <button type="submit" class="btn btn--secondary">Buscar</button>
    </form>

    <BaseLoader v-if="loading && !rows.length" text="Cargando usuarios…" />
    <p v-else-if="error" class="adm-error" role="alert">{{ error }}</p>
    <p v-else-if="!rows.length" class="adm-empty">No hay usuarios con estos filtros.</p>

    <ul v-else class="adm-list" :class="{ 'is-busy': loading }">
      <li v-for="u in rows" :key="u.id" class="adm-row">
        <div class="adm-row__main">
          <span class="adm-row__title">
            <router-link :to="`/profile/${u.id}`">{{ u.name }}</router-link>
            <span v-if="u.role === 'admin'" class="pill pill--easy">Admin</span>
            <span v-if="u.suspended_at" class="pill pill--hard">Suspendida</span>
            <span v-if="u.id === authStore.userId" class="adm-muted">(tú)</span>
          </span>
          <span class="adm-muted">
            {{ u.email }} · alta {{ formatDate(u.created_at) }} ·
            {{ u.last_login ? `último acceso ${formatDate(u.last_login)}` : 'sin accesos' }} ·
            {{ plural(u.tests_count, 'test', 'tests') }} · {{ plural(u.completed_attempts_count, 'intento', 'intentos') }}
          </span>
        </div>
        <div v-if="u.id !== authStore.userId" class="adm-row__actions">
          <button
            type="button"
            class="btn btn--ghost"
            :disabled="busyId === u.id || Boolean(u.suspended_at)"
            @click="confirm = { user: u, kind: u.role === 'admin' ? 'demote' : 'promote' }"
          >
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            {{ u.role === 'admin' ? 'Quitar admin' : 'Hacer admin' }}
          </button>
          <button
            type="button"
            class="btn btn--ghost"
            :class="{ 'adm-danger': !u.suspended_at }"
            :disabled="busyId === u.id || (!u.suspended_at && u.role === 'admin')"
            :title="!u.suspended_at && u.role === 'admin' ? 'Quita primero el rol de administrador' : ''"
            @click="confirm = { user: u, kind: u.suspended_at ? 'reactivate' : 'suspend' }"
          >
            <i :class="u.suspended_at ? 'fa-solid fa-user-check' : 'fa-solid fa-user-slash'" aria-hidden="true"></i>
            {{ u.suspended_at ? 'Reactivar' : 'Suspender' }}
          </button>
        </div>
      </li>
    </ul>

    <AdminPager :page="meta.current_page" :last-page="meta.last_page" :total="meta.total" :busy="loading" @go="load" />

    <BaseModal :model-value="Boolean(confirm)" :title="confirmCopy.title" max-width="460px" @update:model-value="confirm = null">
      <p class="adm-modal-text">{{ confirmCopy.text }}</p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="confirm = null">Cancelar</button>
        <button type="button" class="btn" :class="{ 'btn--danger': confirmCopy.danger }" :disabled="busyId !== null" @click="apply">
          {{ confirmCopy.action }}
        </button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/axios';
import BaseLoader from '../base/BaseLoader.vue';
import BaseModal from '../base/BaseModal.vue';
import AdminPager from './AdminPager.vue';
import { plural } from '../../utils/tests';
import { useToast } from '../../composables/useToast';
import { useAuthStore } from '../../stores/auth';

const toast = useToast();
const authStore = useAuthStore();

const filters = reactive({ search: '', role: '', status: '' });
const rows = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const error = ref('');
const busyId = ref(null);
const confirm = ref(null);

const formatDate = (iso) => new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });

const confirmCopy = computed(() => {
  const name = confirm.value?.user.name ?? '';
  return {
    promote: {
      title: '¿Hacer administrador?', action: 'Hacer admin', danger: false,
      text: `${name} podrá gestionar tests, usuarios y categorías desde este panel.`,
    },
    demote: {
      title: '¿Quitar el rol de administrador?', action: 'Quitar admin', danger: true,
      text: `${name} dejará de tener acceso al panel de administración.`,
    },
    suspend: {
      title: '¿Suspender esta cuenta?', action: 'Suspender', danger: true,
      text: `${name} no podrá iniciar sesión y se cerrarán todas sus sesiones abiertas. Sus tests siguen visibles; puedes archivarlos desde la pestaña Tests.`,
    },
    reactivate: {
      title: '¿Reactivar esta cuenta?', action: 'Reactivar', danger: false,
      text: `${name} podrá volver a iniciar sesión.`,
    },
  }[confirm.value?.kind] ?? { title: '', text: '', action: '' };
});

async function load(page = 1) {
  loading.value = true;
  error.value = '';
  try {
    const params = { page };
    if (filters.search.trim()) params.search = filters.search.trim();
    if (filters.role) params.role = filters.role;
    if (filters.status) params.status = filters.status;
    const { data } = await api.get('/admin/users', { params });
    rows.value = data.data;
    meta.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
  } catch {
    error.value = 'No se han podido cargar los usuarios.';
  } finally {
    loading.value = false;
  }
}

async function apply() {
  const { user, kind } = confirm.value;
  const changes = {
    promote: { role: 'admin' },
    demote: { role: 'user' },
    suspend: { suspended: true },
    reactivate: { suspended: false },
  }[kind];

  busyId.value = user.id;
  try {
    const { data } = await api.patch(`/admin/users/${user.id}`, changes);
    Object.assign(user, { role: data.user.role, suspended_at: data.user.suspended_at });
    confirm.value = null;
    toast.success(data.message);
  } catch (err) {
    const errs = err.response?.data?.errors;
    toast.error(errs ? Object.values(errs).flat()[0] : 'No se ha podido actualizar el usuario.');
  } finally {
    busyId.value = null;
  }
}

onMounted(() => load(1));
</script>
