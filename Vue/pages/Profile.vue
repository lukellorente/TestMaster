<template>
  <div class="page-wrap pf">
    <BaseLoader v-if="loading" text="Cargando perfil…" size="large" />

    <div v-else-if="!profile" class="pf__missing panel" role="alert">
      <p class="pf__missing-title">No hemos encontrado este perfil</p>
      <router-link to="/" class="btn btn--secondary">Volver al catálogo</router-link>
    </div>

    <template v-else>
      <!-- ═══ Cabecera ═══ -->
      <section class="pf__head" aria-labelledby="pf-name">
        <div class="pf__identity">
          <div class="pf__avatar-wrap">
            <img :src="avatarUrl" :alt="`Foto de ${profile.user.name}`" class="pf__avatar" />
            <span v-if="uploadingAvatar" class="pf__avatar-busy" aria-live="polite">
              <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span class="sr-only">Subiendo foto</span>
            </span>
          </div>
          <div class="pf__who">
            <h1 id="pf-name" class="pf__name">{{ profile.user.name }}</h1>
            <p class="pf__meta">
              <span v-if="profile.user.role === 'admin'" class="pill pill--accent">Administrador</span>
              <span>Miembro desde {{ formatDate(profile.user.created_at) }}</span>
              <span v-if="profile.user.last_login">· Última actividad {{ timeAgo(profile.user.last_login) }}</span>
            </p>
            <p v-if="profile.user.bio" class="pf__bio">{{ profile.user.bio }}</p>
            <div v-if="profile.is_own_profile" class="pf__avatar-actions">
              <button type="button" class="btn btn--secondary" :disabled="uploadingAvatar" @click="triggerFileInput">
                <i class="fa-solid fa-camera" aria-hidden="true"></i>{{ hasCustomAvatar ? 'Cambiar foto' : 'Subir foto' }}
              </button>
              <button v-if="hasCustomAvatar" type="button" class="btn btn--ghost" :disabled="uploadingAvatar" @click="confirmDeleteAvatar = true">
                Quitar foto
              </button>
              <span class="pf__hint">JPG, PNG, GIF o WebP, hasta 2 MB.</span>
            </div>
            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              class="sr-only"
              tabindex="-1"
              aria-hidden="true"
              @change="handleFileSelect"
            />
          </div>
        </div>
        <router-link v-if="profile.is_own_profile" to="/create-test" class="btn btn--lg">
          <i class="fa-solid fa-plus" aria-hidden="true"></i>Crear test
        </router-link>
      </section>

      <!-- ═══ Estadísticas ═══ -->
      <dl class="pf__strip" aria-label="Estadísticas">
        <div><dt>Tests creados</dt><dd class="tabular">{{ stats.tests_created ?? 0 }}</dd></div>
        <div><dt>Completados</dt><dd class="tabular">{{ stats.tests_completed ?? 0 }}</dd></div>
        <div><dt>Aprobados</dt><dd class="tabular">{{ stats.tests_passed ?? 0 }}</dd></div>
        <div><dt>Nota media</dt><dd class="tabular">{{ stats.tests_completed ? `${Math.round(stats.average_score)} %` : '—' }}</dd></div>
        <div>
          <dt>Tasa de aprobado</dt>
          <dd class="tabular">{{ stats.tests_completed ? `${passRate} %` : '—' }}</dd>
        </div>
      </dl>
      <p v-if="stats.current_streak > 0" class="pf__streak">
        Racha de {{ plural(stats.current_streak, 'día', 'días') }} seguidos haciendo tests.
      </p>

      <!-- ═══ Pestañas ═══ -->
      <section class="pf__body" aria-label="Contenido del perfil">
        <div class="pf__tabs" role="tablist" aria-label="Secciones del perfil">
          <button
            v-for="tab in tabs"
            :id="`tab-${tab.key}`"
            :key="tab.key"
            type="button"
            role="tab"
            class="pf__tab"
            :aria-selected="String(activeTab === tab.key)"
            :aria-controls="`panel-${tab.key}`"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}<span v-if="tab.count !== null" class="pf__tab-count tabular">{{ tab.count }}</span>
          </button>
        </div>

        <!-- Tests creados -->
        <div v-show="activeTab === 'tests'" id="panel-tests" role="tabpanel" aria-labelledby="tab-tests">
          <div v-if="!profile.created_tests?.length" class="pf__empty panel">
            <p class="pf__empty-title">{{ profile.is_own_profile ? 'Aún no has creado ningún test' : `${firstName} aún no ha publicado tests` }}</p>
            <p v-if="profile.is_own_profile" class="pf__empty-text">Crea uno para tu clase, tu equipo o para practicar.</p>
            <router-link v-if="profile.is_own_profile" to="/create-test" class="btn">Crear mi primer test</router-link>
          </div>
          <ul v-else class="pf__list">
            <li v-for="t in profile.created_tests" :key="t.id">
              <router-link :to="`/test/${t.id}`" class="pf__row">
                <span class="pf__row-main">
                  <span class="pf__row-title">{{ t.title }}</span>
                  <span class="pf__row-meta tabular">
                    {{ plural(t.questions_count ?? 0, 'pregunta', 'preguntas') }} ·
                    {{ plural(t.completed_attempts_count ?? 0, 'intento', 'intentos') }} ·
                    {{ formatDate(t.created_at) }}
                  </span>
                </span>
                <span v-if="t.difficulty" :class="['pill', difficultyClass(t.difficulty)]">{{ difficultyLabel(t.difficulty) }}</span>
                <i class="fa-solid fa-chevron-right pf__chev" aria-hidden="true"></i>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Completados -->
        <div v-show="activeTab === 'completed'" id="panel-completed" role="tabpanel" aria-labelledby="tab-completed">
          <div v-if="!profile.completed_tests?.length" class="pf__empty panel">
            <p class="pf__empty-title">{{ profile.is_own_profile ? 'Aún no has hecho ningún test' : `${firstName} aún no ha hecho tests` }}</p>
            <router-link v-if="profile.is_own_profile" to="/" class="btn btn--secondary">Explorar tests</router-link>
          </div>
          <ul v-else class="pf__list">
            <li v-for="at in profile.completed_tests" :key="at.id">
              <router-link :to="profile.is_own_profile ? `/results/${at.id}` : `/test/${at.test_id}`" class="pf__row">
                <span class="pf__row-main">
                  <span class="pf__row-title">{{ at.test?.title ?? 'Test sin título' }}</span>
                  <span class="pf__row-meta">
                    <template v-if="at.test?.user?.name">de {{ at.test.user.name }} · </template>{{ formatDate(at.completed_at) }}
                  </span>
                </span>
                <span class="pf__score">
                  <span class="pf__score-num tabular">{{ Math.round(at.score) }} %</span>
                  <span :class="['pill', at.passed ? 'pill--easy' : 'pill--hard']">{{ at.passed ? 'Aprobado' : 'No aprobado' }}</span>
                </span>
                <i class="fa-solid fa-chevron-right pf__chev" aria-hidden="true"></i>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Actividad -->
        <div v-show="activeTab === 'activity'" id="panel-activity" role="tabpanel" aria-labelledby="tab-activity">
          <div v-if="!profile.recent_activity?.length" class="pf__empty panel">
            <p class="pf__empty-title">Sin actividad todavía</p>
          </div>
          <ol v-else class="pf__feed">
            <li v-for="(a, i) in profile.recent_activity" :key="i" class="pf__feed-item">
              <span class="pf__feed-icon" :class="a.type === 'test' ? 'is-create' : 'is-done'" aria-hidden="true">
                <i :class="a.type === 'test' ? 'fa-solid fa-plus' : 'fa-solid fa-check'"></i>
              </span>
              <span class="pf__feed-text">
                {{ a.type === 'test' ? 'Creó' : 'Completó' }}
                <router-link :to="activityLink(a)">{{ a.title }}</router-link>
              </span>
              <time class="pf__feed-time" :datetime="a.date">{{ timeAgo(a.date) }}</time>
            </li>
          </ol>
        </div>
      </section>

      <!-- ═══ Privacidad (solo perfil propio) ═══ -->
      <section v-if="profile.is_own_profile" class="pf__privacy panel" aria-labelledby="pf-privacy">
        <h2 id="pf-privacy" class="pf__privacy-title">Tus datos</h2>
        <p class="pf__privacy-text">
          Descarga una copia de todo lo que guardamos sobre ti o elimina tu cuenta.
          Más información en la <router-link to="/privacy">Política de Privacidad</router-link>.
        </p>
        <div class="pf__privacy-actions">
          <button type="button" class="btn btn--secondary" :disabled="exporting" @click="exportData">
            <i class="fa-solid fa-download" aria-hidden="true"></i>{{ exporting ? 'Preparando…' : 'Descargar mis datos' }}
          </button>
          <button type="button" class="btn btn--danger" @click="openDeleteAccount">
            <i class="fa-solid fa-trash" aria-hidden="true"></i>Eliminar mi cuenta
          </button>
        </div>
      </section>

      <BaseModal v-model="confirmDeleteAccount" title="¿Eliminar tu cuenta?" max-width="460px">
        <form class="pf__delete" @submit.prevent="deleteAccount">
          <p class="pf__modal-text">
            Se borrarán tus datos personales, tu foto, tus intentos y tus estadísticas. No se puede deshacer.
          </p>
          <label class="pf__delete-check">
            <input v-model="deleteForm.deleteTests" type="checkbox" />
            <span>
              Borrar también mis tests publicados.
              <small>Si no lo marcas, seguirán disponibles como «Usuario eliminado».</small>
            </span>
          </label>
          <label class="pf__delete-label" for="pf-delete-pass">Confirma con tu contraseña</label>
          <input
            id="pf-delete-pass"
            v-model="deleteForm.password"
            type="password"
            autocomplete="current-password"
            class="pf__delete-input"
          />
          <p v-if="deleteError" class="pf__delete-error" role="alert">{{ deleteError }}</p>
        </form>
        <template #footer>
          <button type="button" class="btn btn--secondary" @click="confirmDeleteAccount = false">Cancelar</button>
          <button type="button" class="btn btn--danger" :disabled="deletingAccount" @click="deleteAccount">
            {{ deletingAccount ? 'Eliminando…' : 'Eliminar cuenta' }}
          </button>
        </template>
      </BaseModal>

      <BaseModal v-model="confirmDeleteAvatar" title="¿Quitar tu foto de perfil?" max-width="420px">
        <p class="pf__modal-text">Volverás a ver la imagen por defecto. Puedes subir otra cuando quieras.</p>
        <template #footer>
          <button type="button" class="btn btn--secondary" @click="confirmDeleteAvatar = false">Cancelar</button>
          <button type="button" class="btn btn--danger" @click="deleteAvatar">Quitar foto</button>
        </template>
      </BaseModal>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseModal from '../components/base/BaseModal.vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { difficultyClass, difficultyLabel, plural } from '../utils/tests';

const DEFAULT_AVATAR_URL = '/images/defaultpfp.svg';
const DEFAULT_STATS = { tests_created: 0, tests_completed: 0, tests_passed: 0, average_score: 0, current_streak: 0 };

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const loading = ref(true);
const profile = ref(null);
const activeTab = ref('tests');
const fileInput = ref(null);
const uploadingAvatar = ref(false);
const confirmDeleteAvatar = ref(false);

// ─── Derivados ───────────────────────────────────────────────────────────────
const stats = computed(() => ({ ...DEFAULT_STATS, ...(profile.value?.stats ?? {}) }));
const hasCustomAvatar = computed(() => Boolean(profile.value?.user?.avatar));
const avatarUrl = computed(() => profile.value?.user?.avatar || DEFAULT_AVATAR_URL);
const firstName = computed(() => (profile.value?.user?.name || '').split(' ')[0]);

const passRate = computed(() => {
  const s = stats.value;
  return s.tests_completed ? Math.round((s.tests_passed / s.tests_completed) * 100) : 0;
});

// El historial de intentos es privado: solo aparece en el perfil propio.
const tabs = computed(() => [
  { key: 'tests', label: 'Tests creados', count: profile.value?.created_tests?.length ?? 0 },
  ...(profile.value?.is_own_profile
    ? [{ key: 'completed', label: 'Completados', count: profile.value?.completed_tests?.length ?? 0 }]
    : []),
  { key: 'activity', label: 'Actividad', count: null },
]);

// ─── Privacidad: exportar / eliminar cuenta (RGPD arts. 15, 17 y 20) ──────────
const exporting = ref(false);
const confirmDeleteAccount = ref(false);
const deleteForm = ref({ password: '', deleteTests: false });
const deleteError = ref('');
const deletingAccount = ref(false);

const exportData = async () => {
  exporting.value = true;
  try {
    await authStore.exportData();
    toast.success('Descarga iniciada: testmaster-mis-datos.json');
  } catch {
    toast.error('No se han podido exportar tus datos. Inténtalo de nuevo.');
  } finally {
    exporting.value = false;
  }
};

const openDeleteAccount = () => {
  deleteForm.value = { password: '', deleteTests: false };
  deleteError.value = '';
  confirmDeleteAccount.value = true;
};

const deleteAccount = async () => {
  deleteError.value = '';
  if (!deleteForm.value.password) {
    deleteError.value = 'Escribe tu contraseña para confirmar.';
    return;
  }
  deletingAccount.value = true;
  try {
    await authStore.deleteAccount(deleteForm.value.password, deleteForm.value.deleteTests);
    confirmDeleteAccount.value = false;
    toast.success('Tu cuenta se ha eliminado.');
    router.push('/');
  } catch (err) {
    deleteError.value = err.response?.data?.errors?.password?.[0]
      || err.response?.data?.message
      || 'No se ha podido eliminar la cuenta.';
  } finally {
    deletingAccount.value = false;
  }
};

// ─── Utilidades ──────────────────────────────────────────────────────────────
const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' }) : '';

const timeAgo = (d) => {
  if (!d) return '';
  const diff = Math.floor((Date.now() - new Date(d)) / 1000);
  if (diff < 60) return 'ahora mismo';
  if (diff < 3600) return `hace ${Math.floor(diff / 60)} min`;
  if (diff < 86400) return `hace ${Math.max(1, Math.round(diff / 3600))} h`;
  if (diff < 604800) return `hace ${plural(Math.floor(diff / 86400), 'día', 'días')}`;
  return formatDate(d);
};

const activityLink = (a) => {
  if (a.type === 'test') return `/test/${a.id}`;
  return profile.value?.is_own_profile ? `/results/${a.id}` : `/profile/${profile.value?.user?.id}`;
};

// Mantiene la foto de la cabecera al día
const syncStoredUserAvatar = (avatar) => {
  if (authStore.user && authStore.user.id === profile.value?.user?.id) {
    authStore.user = { ...authStore.user, avatar };
  }
};

// ─── Avatar ──────────────────────────────────────────────────────────────────
const triggerFileInput = () => fileInput.value?.click();

const handleFileSelect = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    toast.error('Elige una imagen JPG, PNG, GIF o WebP.');
    e.target.value = '';
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.error('La imagen no puede pasar de 2 MB.');
    e.target.value = '';
    return;
  }

  uploadingAvatar.value = true;
  try {
    const form = new FormData();
    form.append('avatar', file);
    const { data } = await api.post('/profile/avatar', form, { headers: { 'Content-Type': 'multipart/form-data' } });
    if (profile.value?.user) profile.value.user.avatar = data.avatar;
    syncStoredUserAvatar(data.avatar);
    toast.success('Foto actualizada.');
  } catch (err) {
    toast.error(err.response?.data?.message ?? 'No se pudo subir la imagen.');
  } finally {
    uploadingAvatar.value = false;
    if (fileInput.value) fileInput.value.value = '';
  }
};

const deleteAvatar = async () => {
  confirmDeleteAvatar.value = false;
  uploadingAvatar.value = true;
  try {
    await api.delete('/profile/avatar');
    if (profile.value?.user) profile.value.user.avatar = null;
    syncStoredUserAvatar(null);
    toast.success('Foto quitada.');
  } catch {
    toast.error('No se pudo quitar la foto.');
  } finally {
    uploadingAvatar.value = false;
  }
};

// ─── Carga ───────────────────────────────────────────────────────────────────
const fetchProfile = async () => {
  loading.value = true;
  profile.value = null;
  try {
    const { data } = await api.get(`/profile/${route.params.id}`);
    profile.value = data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchProfile);

// Pasar de un perfil a otro reutiliza el componente: hay que recargar
watch(
  () => route.params.id,
  (id, old) => {
    if (id && id !== old) {
      activeTab.value = 'tests';
      fetchProfile();
    }
  }
);
</script>

<style scoped>
.pf {
  padding-top: 48px;
  max-width: 1100px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Cabecera */
.pf__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 24px;
  flex-wrap: wrap;
}

.pf__identity {
  display: flex;
  gap: 24px;
  align-items: flex-start;
  min-width: 0;
  flex: 1;
}

.pf__avatar-wrap {
  position: relative;
  flex-shrink: 0;
}

.pf__avatar {
  width: 88px;
  height: 88px;
  border-radius: 50%;
  object-fit: cover;
  background: var(--raised);
  display: block;
}

.pf__avatar-busy {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--scrim);
  color: #fff;
  display: grid;
  place-items: center;
}

.pf__who {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.pf__name {
  font-size: 1.875rem;
  margin: 0;
  overflow-wrap: anywhere;
}

.pf__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-size: var(--fs-small);
  color: var(--muted);
  margin: 0;
}

.pf__bio {
  color: var(--text-body);
  max-width: 620px;
  margin: 4px 0 0;
}

.pf__avatar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.pf__hint {
  font-size: var(--fs-meta);
  color: var(--muted);
}

/* Estadísticas */
.pf__strip {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  background: var(--raised);
  border-radius: var(--radius-panel);
  margin: 0;
}

.pf__strip > div {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  border-left: 1px solid var(--border);
}

.pf__strip > div:first-child {
  border-left: 0;
}

.pf__strip dt {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.pf__strip dd {
  margin: 0;
  font-size: 1.5rem;
}

.pf__streak {
  font-size: var(--fs-small);
  color: var(--text-body);
  margin: -16px 0 0;
}

/* Pestañas */
.pf__body {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pf__tabs {
  display: flex;
  gap: 4px;
  background: var(--raised);
  padding: 4px;
  border-radius: var(--radius-card);
  align-self: flex-start;
  max-width: 100%;
  overflow-x: auto;
}

.pf__tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 34px;
  padding: 0 14px;
  border: 0;
  border-radius: var(--radius-control);
  background: transparent;
  color: var(--muted);
  font: inherit;
  font-size: var(--fs-small);
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
}

.pf__tab:hover {
  color: var(--text);
}

.pf__tab[aria-selected='true'] {
  background: var(--bg);
  color: var(--text);
  font-weight: 600;
}

.pf__tab-count {
  font-size: 0.75rem;
  color: var(--muted);
}

/* Listas */
.pf__list,
.pf__feed {
  list-style: none;
  margin: 0;
  padding: 0;
}

.pf__row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 8px;
  margin: 0 -8px;
  border-top: 1px solid var(--rule);
  border-radius: var(--radius-card);
  color: var(--text);
  text-decoration: none;
  transition: background 0.12s;
}

.pf__list li:first-child .pf__row {
  border-top-color: transparent;
}

.pf__row:hover {
  background: var(--raised);
  color: var(--text);
}

.pf__row-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.pf__row-title {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pf__row-meta {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.pf__score {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pf__score-num {
  font-size: 1.125rem;
  font-weight: 600;
}

.pf__chev {
  color: var(--muted);
  font-size: 0.75rem;
}

.pf__feed-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 0;
  border-top: 1px solid var(--rule);
}

.pf__feed-item:first-child {
  border-top: 0;
}

.pf__feed-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.pf__feed-icon.is-create {
  background: var(--accent-soft);
  color: var(--accent-ink);
}

.pf__feed-icon.is-done {
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.pf__feed-text {
  flex: 1;
  min-width: 0;
  color: var(--text-body);
}

.pf__feed-text a {
  color: var(--text);
  font-weight: 500;
  text-decoration: none;
}

.pf__feed-text a:hover {
  color: var(--accent);
}

.pf__feed-time {
  font-size: var(--fs-meta);
  color: var(--muted);
  white-space: nowrap;
}

/* Vacíos */
.pf__empty,
.pf__missing {
  padding: 40px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  text-align: center;
}

.pf__empty-title,
.pf__missing-title {
  font-weight: 600;
  margin: 0;
}

.pf__empty-text {
  color: var(--text-body);
  margin: 0 0 8px;
}

.pf__missing {
  margin-top: 32px;
  gap: 16px;
}

.pf__modal-text {
  color: var(--text-body);
  margin: 0;
}

.pf__privacy {
  margin-top: 32px;
  padding: 20px 24px;
}

.pf__privacy-title {
  font-size: 1.05rem;
  color: var(--text);
  margin: 0 0 6px;
}

.pf__privacy-text {
  color: var(--muted);
  font-size: var(--fs-small);
  margin: 0 0 14px;
}

.pf__privacy-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.pf__delete {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pf__delete-check {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  color: var(--text-body);
  font-size: var(--fs-small);
  cursor: pointer;
}

.pf__delete-check input {
  margin-top: 3px;
  flex-shrink: 0;
}

.pf__delete-check small {
  display: block;
  color: var(--muted);
  font-size: var(--fs-meta);
}

.pf__delete-label {
  font-size: var(--fs-small);
  color: var(--text);
  font-weight: 600;
}

.pf__delete-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-control);
  background: var(--raised);
  color: var(--text);
  font: inherit;
}

.pf__delete-error {
  color: var(--negative-ink);
  font-size: var(--fs-small);
  margin: 0;
}

@media (max-width: 860px) {
  .pf__strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .pf__strip > div:nth-child(odd) {
    border-left: 0;
  }

  .pf__strip > div:nth-child(n + 3) {
    border-top: 1px solid var(--border);
  }
}

@media (max-width: 640px) {
  .pf {
    padding-top: 32px;
  }

  .pf__name {
    font-size: 1.5rem;
  }

  .pf__identity {
    flex-direction: column;
    gap: 16px;
  }

  .pf__head > .btn {
    width: 100%;
  }

  .pf__score .pill {
    display: none;
  }
}
</style>
