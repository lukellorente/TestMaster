<template>
  <BaseLoader v-if="loading" text="Cargando perfil..." size="large" />

  <div v-else-if="profile" class="profile-page">
    <div class="page-layout">

      <!-- ═══════════════════════════════════════
           SIDEBAR IZQUIERDO
      ═══════════════════════════════════════ -->
      <aside class="profile-sidebar">

        <!-- Identidad -->
        <div class="identity-card">

          <!-- Avatar -->
          <div
            class="avatar-wrap"
            :class="{ editable: profile.is_own_profile }"
            @click="profile.is_own_profile && triggerFileInput()"
          >
            <div class="avatar">
              <img
                :src="avatarUrl"
                :alt="profile.user.name"
                class="avatar-img"
              />
            </div>
            <div v-if="profile.is_own_profile" class="avatar-edit-btn" title="Cambiar foto">
              <i class="fa-solid fa-camera"></i>
            </div>
            <button
              v-if="profile.is_own_profile && hasCustomAvatar"
              class="avatar-delete-btn"
              @click.stop="deleteAvatar"
              title="Eliminar foto"
            >
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            style="display:none"
            @change="handleFileSelect"
          />

          <div v-if="uploadingAvatar" class="upload-spinner">
            <i class="fa-solid fa-spinner fa-spin"></i> Subiendo...
          </div>

          <!-- Nombre y rol -->
          <h1 class="profile-name">{{ profile.user.name }}</h1>
          <span class="role-badge">{{ profile.user.role }}</span>

          <!-- Bio -->
          <p v-if="profile.user.bio" class="profile-bio">{{ profile.user.bio }}</p>

          <!-- Meta -->
          <div class="profile-meta">
            <div v-if="profile.user.email" class="meta-row">
              <i class="fa-regular fa-envelope"></i>
              <span>{{ profile.user.email }}</span>
            </div>
            <div class="meta-row">
              <i class="fa-regular fa-calendar"></i>
              <span>Miembro desde {{ formatDate(profile.user.created_at) }}</span>
            </div>
            <div v-if="profile.user.last_login" class="meta-row">
              <i class="fa-regular fa-clock"></i>
              <span>Última actividad {{ timeAgo(profile.user.last_login) }}</span>
            </div>
          </div>

        </div>

        <!-- Estadísticas -->
        <div class="stats-card">
          <p class="card-eyebrow">Estadísticas</p>
          <div class="stats-grid">
            <div class="stat-chip">
              <span class="stat-n">{{ stats.tests_created }}</span>
              <span class="stat-l">creados</span>
            </div>
            <div class="stat-chip">
              <span class="stat-n">{{ stats.tests_completed }}</span>
              <span class="stat-l">completados</span>
            </div>
            <div class="stat-chip">
              <span class="stat-n">{{ stats.tests_passed }}</span>
              <span class="stat-l">aprobados</span>
            </div>
            <div class="stat-chip">
              <span class="stat-n">{{ stats.average_score ? Number(stats.average_score).toFixed(1) + '%' : '—' }}</span>
              <span class="stat-l">promedio</span>
            </div>
          </div>

          <!-- Barra tasa de aprobación -->
          <div v-if="stats.tests_completed > 0" class="pass-bar-wrap">
            <div class="pass-bar-head">
              <span>Tasa de aprobación</span>
              <span class="pass-bar-pct">{{ passRate }}%</span>
            </div>
            <div class="pass-bar-track">
              <div class="pass-bar-fill" :style="{ width: passRate + '%' }"></div>
            </div>
          </div>

          <!-- Racha -->
          <div v-if="stats.current_streak > 0" class="streak-row">
            <i class="fa-solid fa-fire streak-icon"></i>
            <span>Racha de <strong>{{ stats.current_streak }}</strong> días</span>
          </div>
        </div>

      </aside>

      <!-- ═══════════════════════════════════════
           COLUMNA PRINCIPAL
      ═══════════════════════════════════════ -->
      <div class="main-col">

        <!-- Tabs -->
        <div class="tabs-bar">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="['tab', { active: activeTab === tab.key }]"
            @click="activeTab = tab.key"
          >
            <i :class="tab.icon"></i>
            {{ tab.label }}
            <span v-if="tab.count !== null" class="tab-count">{{ tab.count }}</span>
          </button>
        </div>

        <!-- ── Tests creados ── -->
        <div v-if="activeTab === 'tests'">
          <div v-if="!profile.created_tests?.length" class="empty-state">
            <i class="fa-solid fa-file-lines empty-icon"></i>
            <p>{{ profile.is_own_profile ? 'Aún no has creado ningún test.' : `${profile.user.name} aún no ha creado tests públicos.` }}</p>
            <router-link v-if="profile.is_own_profile" to="/create-test" class="btn-empty">
              Crear mi primer test
            </router-link>
          </div>
          <div v-else class="item-list">
            <router-link
              v-for="t in profile.created_tests"
              :key="t.id"
              :to="`/test/${t.id}`"
              class="list-item"
            >
              <div class="list-item-body">
                <p class="list-item-title">{{ t.title }}</p>
                <div class="list-item-meta">
                  <span>{{ t.questions_count ?? t.num_questions ?? 0 }} preg.</span>
                  <span>{{ t.attempts_count ?? t.completed_attempts_count ?? 0 }} intentos</span>
                  <span>{{ formatDate(t.created_at) }}</span>
                </div>
              </div>
              <div class="list-item-right">
                <span :class="['status-badge', t.status === 'published' ? 'status--pub' : 'status--draft']">
                  {{ t.status === 'published' ? 'publicado' : 'borrador' }}
                </span>
              </div>
            </router-link>
          </div>
        </div>

        <!-- ── Tests completados ── -->
        <div v-if="activeTab === 'completed'">
          <div v-if="!profile.completed_tests?.length" class="empty-state">
            <i class="fa-solid fa-circle-check empty-icon"></i>
            <p>{{ profile.is_own_profile ? 'Aún no has completado ningún test.' : `${profile.user.name} aún no ha completado tests.` }}</p>
          </div>
          <div v-else class="item-list">
            <div
              v-for="at in profile.completed_tests"
              :key="at.id"
              class="list-item"
            >
              <div class="list-item-body">
                <p class="list-item-title">{{ completedTestTitle(at) }}</p>
                <div class="list-item-meta">
                  <span v-if="completedTestAuthor(at)">por {{ completedTestAuthor(at) }}</span>
                  <span>{{ formatDate(at.completed_at) }}</span>
                </div>
              </div>
              <div class="list-item-right">
                <p :class="['score-val', at.passed ? 'score--pass' : 'score--fail']">
                  {{ Number(at.score).toFixed(1) }}%
                </p>
                <p :class="['score-sub', at.passed ? 'score--pass' : 'score--fail']">
                  {{ at.passed ? 'Aprobado' : 'No aprobado' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Actividad ── -->
        <div v-if="activeTab === 'activity'">
          <div v-if="!profile.recent_activity?.length" class="empty-state">
            <i class="fa-solid fa-bolt empty-icon"></i>
            <p>Sin actividad reciente.</p>
          </div>
          <div v-else class="activity-feed">
            <div
              v-for="(a, i) in profile.recent_activity"
              :key="i"
              class="act-item"
            >
              <div :class="['act-dot', a.type === 'test' ? 'act-dot--create' : 'act-dot--complete']">
                <i :class="a.type === 'test' ? 'fa-solid fa-plus' : 'fa-solid fa-check'"></i>
              </div>
              <div class="act-body">
                <p class="act-text">
                  <template v-if="a.type === 'test'">
                    Creó el test <strong>{{ a.title }}</strong>
                  </template>
                  <template v-else>
                    Completó <strong>{{ a.title }}</strong>
                  </template>
                </p>
                <p class="act-time">{{ timeAgo(a.date) }}</p>
              </div>
              <span v-if="a.score !== undefined" :class="['act-score', a.passed ? 'score--pass' : 'score--fail']">
                {{ Number(a.score).toFixed(1) }}%
              </span>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Error -->
  <div v-else class="empty-state" style="margin-top:3rem">
    <i class="fa-solid fa-ghost empty-icon"></i>
    <p>No se encontró el perfil.</p>
    <router-link to="/" class="btn-empty">Volver al inicio</router-link>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';

const DEFAULT_AVATAR_URL = '/images/defaultpfp.svg';

const route     = useRoute();
const DEFAULT_STATS = {
  tests_created: 0,
  tests_completed: 0,
  tests_passed: 0,
  average_score: 0,
  current_streak: 0,
};

// ─── Estado ──────────────────────────────────────────────────────────────────
const loading        = ref(true);
const profile        = ref(null);
const activeTab      = ref('tests');
const fileInput      = ref(null);
const uploadingAvatar = ref(false);

// ─── Computed ─────────────────────────────────────────────────────────────────
const stats = computed(() => profile.value?.stats ?? DEFAULT_STATS);

const hasCustomAvatar = computed(() => Boolean(profile.value?.user?.avatar));
const avatarUrl = computed(() => profile.value?.user?.avatar || DEFAULT_AVATAR_URL);

const passRate = computed(() => {
  const s = stats.value;
  if (!s.tests_completed) return 0;
  return Math.round((s.tests_passed / s.tests_completed) * 100);
});

const tabs = computed(() => [
  {
    key: 'tests',
    label: 'Tests creados',
    icon: 'fa-solid fa-file-lines',
    count: profile.value?.created_tests?.length ?? 0,
  },
  {
    key: 'completed',
    label: 'Completados',
    icon: 'fa-solid fa-circle-check',
    count: profile.value?.completed_tests?.length ?? 0,
  },
  {
    key: 'activity',
    label: 'Actividad',
    icon: 'fa-solid fa-bolt',
    count: null,
  },
]);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric',
  });
};

const syncStoredUserAvatar = (avatar) => {
  const stored = localStorage.getItem('user');
  if (!stored) return;

  const localUser = JSON.parse(stored);
  if (localUser.id === profile.value?.user?.id) {
    localUser.avatar = avatar;
    localStorage.setItem('user', JSON.stringify(localUser));
  }
};

const timeAgo = (d) => {
  if (!d) return '';
  const diff = Math.floor((Date.now() - new Date(d)) / 1000);
  if (diff < 60)     return 'hace un momento';
  if (diff < 3600)   return `hace ${Math.floor(diff / 60)} min`;
  if (diff < 86400)  return `hace ${Math.floor(diff / 3600)} h`;
  if (diff < 604800) return `hace ${Math.floor(diff / 86400)} días`;
  return formatDate(d);
};

const completedTestTitle = (attempt) =>
  attempt?.test?.title ?? attempt?.title ?? 'Test sin título';

const completedTestAuthor = (attempt) =>
  attempt?.test?.user?.name ?? attempt?.author_name ?? '';

// ─── Avatar ───────────────────────────────────────────────────────────────────
const triggerFileInput = () => fileInput.value?.click();

const handleFileSelect = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    alert('Selecciona una imagen válida (JPEG, PNG, GIF o WebP).');
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    alert('La imagen no puede superar los 2 MB.');
    return;
  }

  uploadingAvatar.value = true;
  try {
    const form = new FormData();
    form.append('avatar', file);
    const { data } = await api.post('/profile/avatar', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    if (profile.value?.user) profile.value.user.avatar = data.avatar;
    syncStoredUserAvatar(data.avatar);
  } catch (err) {
    alert(err.response?.data?.message ?? 'Error al subir la imagen.');
  } finally {
    uploadingAvatar.value = false;
    if (fileInput.value) fileInput.value.value = '';
  }
};

const deleteAvatar = async () => {
  if (!confirm('¿Eliminar tu foto de perfil?')) return;
  uploadingAvatar.value = true;
  try {
    await api.delete('/profile/avatar');
    if (profile.value?.user) profile.value.user.avatar = null;
    syncStoredUserAvatar(null);
  } catch {
    alert('Error al eliminar la imagen.');
  } finally {
    uploadingAvatar.value = false;
  }
};

const fetchProfile = async () => {
  loading.value = true;
  try {
    const { data } = await api.get(`/profile/${route.params.id}`);
    profile.value = data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  await fetchProfile();
});
</script>

<style scoped>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.profile-page {
  --pf-surface: #ffffff;
  --pf-border: #e2e8f0;
  --pf-text: #1a202c;
  --pf-muted: #718096;
  --pf-subtle: #a0aec0;
  --pf-accent: #667eea;
  --pf-accent-2: #764ba2;
  --pf-accent-soft: #ebf0ff;
  --pf-hover: #f8f9fa;
  --pf-danger: #dc2626;
  --pf-danger-hover: #b91c1c;
  --pf-success-soft: #eaf8ee;
  --pf-success: #207a3a;
  --pf-warning-soft: #fff6df;
  --pf-warning: #a35a00;

  display: flex;
  flex-direction: column;
}

.page-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 1.5rem;
  align-items: start;
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: 72px;
}

/* ── Identity card ───────────────────────────────────────────────────────── */
.identity-card {
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 16px;
  padding: 1.75rem 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.6rem;
}

.avatar-wrap {
  position: relative;
  flex-shrink: 0;
  margin-bottom: 0.25rem;
}

.avatar-wrap.editable {
  cursor: pointer;
}

.avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 600;
  color: white;
  overflow: hidden;
  transition: filter 0.2s;
}

.avatar-wrap.editable:hover .avatar {
  filter: brightness(0.8);
}

.avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-edit-btn {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: var(--pf-muted);
  pointer-events: none;
}

.avatar-delete-btn {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--pf-danger);
  border: 2px solid var(--pf-surface);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 9px;
  transition: background 0.15s;
}

.avatar-delete-btn:hover {
  background: var(--pf-danger-hover);
}

.upload-spinner {
  font-size: 12px;
  color: var(--pf-accent);
}

.profile-name {
  font-size: 1.15rem;
  font-weight: 600;
  color: var(--pf-text);
  line-height: 1.3;
}

.role-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
  background: var(--pf-accent-soft);
  color: var(--pf-accent);
  text-transform: capitalize;
}

.profile-bio {
  font-size: 13px;
  color: var(--pf-muted);
  line-height: 1.65;
  text-align: left;
  width: 100%;
  white-space: pre-line;
}

.profile-meta {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 7px;
  padding-top: 0.75rem;
  border-top: 1px solid #edf3f1;
  text-align: left;
}

.meta-row {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 12px;
  color: var(--pf-muted);
}

.meta-row i {
  font-size: 11px;
  color: #cbd5e0;
  width: 14px;
  text-align: center;
  flex-shrink: 0;
}

/* ── Stats card ──────────────────────────────────────────────────────────── */
.stats-card {
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 16px;
  padding: 1.25rem;
}

.card-eyebrow {
  font-size: 11px;
  font-weight: 600;
  color: var(--pf-subtle);
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 0.85rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 1rem;
}

.stat-chip {
  background: #f8f9fa;
  border-radius: 10px;
  padding: 0.75rem;
  text-align: center;
}

.stat-n {
  display: block;
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--pf-text);
  line-height: 1;
}

.stat-l {
  display: block;
  font-size: 11px;
  color: var(--pf-subtle);
  margin-top: 3px;
}

.pass-bar-wrap {
  margin-bottom: 0.75rem;
}

.pass-bar-head {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: var(--pf-muted);
  margin-bottom: 6px;
}

.pass-bar-pct {
  font-weight: 600;
  color: var(--pf-accent);
}

.pass-bar-track {
  height: 6px;
  background: #f0f0ff;
  border-radius: 3px;
  overflow: hidden;
}

.pass-bar-fill {
  height: 100%;
  border-radius: 3px;
  background: linear-gradient(90deg, #667eea, #764ba2);
  transition: width 0.5s ease;
}

.streak-row {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  color: var(--pf-muted);
  padding-top: 0.75rem;
  border-top: 1px solid #edf3f1;
}

.streak-icon {
  color: #ea8a0d;
  font-size: 14px;
}

/* ── Columna principal ───────────────────────────────────────────────────── */
.main-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-width: 0;
}

/* ── Tabs ────────────────────────────────────────────────────────────────── */
.tabs-bar {
  display: flex;
  gap: 3px;
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 12px;
  padding: 4px;
}

.tab {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 10px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  font-family: inherit;
  color: var(--pf-muted);
  border: none;
  background: transparent;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}

.tab i {
  font-size: 11px;
}

.tab:hover {
  background: var(--pf-hover);
  color: var(--pf-text);
}

.tab.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.tab-count {
  font-size: 11px;
  opacity: 0.75;
  background: rgba(0,0,0,0.08);
  padding: 1px 6px;
  border-radius: 10px;
}

.tab.active .tab-count {
  background: rgba(255,255,255,0.2);
}

/* ── Lista de items (tests creados y completados) ────────────────────────── */
.item-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.list-item {
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 12px;
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  transition: border-color 0.15s;
  cursor: pointer;
}

.list-item:hover {
  border-color: #b6ccc4;
}

.list-item-body {
  flex: 1;
  min-width: 0;
}

.list-item-title {
  font-size: 14px;
  font-weight: 500;
  color: var(--pf-text);
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.list-item-meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  font-size: 12px;
  color: var(--pf-subtle);
}

.list-item-right {
  flex-shrink: 0;
  text-align: right;
}

/* Badges de estado */
.status-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
}

.status--pub   { background: var(--pf-success-soft); color: var(--pf-success); }
.status--draft { background: var(--pf-warning-soft); color: var(--pf-warning); }

/* Scores */
.score-val {
  font-size: 15px;
  font-weight: 600;
  line-height: 1;
}

.score-sub {
  font-size: 11px;
  margin-top: 3px;
}

.score--pass { color: #2d8f4b; }
.score--fail { color: #d13a3a; }

/* ── Feed de actividad ───────────────────────────────────────────────────── */
.activity-feed {
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.act-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #edf3f1;
  transition: background 0.1s;
}

.act-item:last-child {
  border-bottom: none;
}

.act-item:hover {
  background: var(--pf-hover);
}

.act-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  flex-shrink: 0;
}

.act-dot--create   { background: #ebf0ff; color: #4c5fd5; }
.act-dot--complete { background: #eaf8ee; color: #207a3a; }

.act-body {
  flex: 1;
  min-width: 0;
}

.act-text {
  font-size: 13px;
  color: var(--pf-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.act-text strong {
  color: var(--pf-text);
  font-weight: 500;
}

.act-time {
  font-size: 11px;
  color: var(--pf-subtle);
  margin-top: 2px;
}

.act-score {
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

/* ── Estado vacío ────────────────────────────────────────────────────────── */
.empty-state {
  background: var(--pf-surface);
  border: 1px solid var(--pf-border);
  border-radius: 14px;
  padding: 3rem 1.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.empty-icon {
  font-size: 2rem;
  color: #c9d7d3;
}

.empty-state p {
  font-size: 14px;
  color: var(--pf-subtle);
}

.btn-empty {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  margin-top: 0.5rem;
  transition: box-shadow 0.2s;
}

.btn-empty:hover {
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35);
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .identity-card,
body.dark-mode .stats-card,
body.dark-mode .tabs-bar,
body.dark-mode .list-item,
body.dark-mode .activity-feed,
body.dark-mode .empty-state {
  background: #0f1b24 !important;
  border-color: #233645 !important;
}

body.dark-mode .profile-name,
body.dark-mode .stat-n,
body.dark-mode .list-item-title,
body.dark-mode .act-text {
  color: #dceaf0 !important;
}

body.dark-mode .profile-bio,
body.dark-mode .meta-row,
body.dark-mode .list-item-meta,
body.dark-mode .act-time,
body.dark-mode .pass-bar-head,
body.dark-mode .streak-row {
  color: #8aa0ac !important;
}

body.dark-mode .meta-row i {
  color: #567182 !important;
}

body.dark-mode .stat-chip {
  background: #122532 !important;
}

body.dark-mode .stat-l,
body.dark-mode .card-eyebrow,
body.dark-mode .tab-count,
body.dark-mode .empty-state p {
  color: #6f8897 !important;
}

body.dark-mode .profile-meta,
body.dark-mode .act-item,
body.dark-mode .streak-row {
  border-color: #233645 !important;
}

body.dark-mode .pass-bar-track {
  background: #163141 !important;
}

body.dark-mode .tab {
  color: #8aa0ac;
}

body.dark-mode .tab:hover {
  background: #163141;
  color: #dceaf0;
}

body.dark-mode .tab-count {
  background: #122532;
  color: #8aa0ac;
  opacity: 1;
}

body.dark-mode .tab.active {
  color: white;
}

body.dark-mode .tab.active .tab-count {
  background: #dceaf0;
  color: #0f1b24;
}

body.dark-mode .act-item:hover {
  background: #163141 !important;
}

body.dark-mode .act-text strong {
  color: #dceaf0 !important;
}

body.dark-mode .empty-icon {
  color: #314a58 !important;
}

body.dark-mode .status--pub   { background: rgba(45,143,75,.2) !important; color: #86d5a1 !important; }
body.dark-mode .status--draft { background: rgba(233,138,13,.2) !important; color: #f3c77a !important; }

body.dark-mode .role-badge {
  background: rgba(102,126,234,.15) !important;
  color: #a78bfa !important;
}

body.dark-mode .avatar-edit-btn {
  background: #0f1b24 !important;
  border-color: #233645 !important;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 860px) {
  .page-layout {
    grid-template-columns: 1fr;
  }

  .profile-sidebar {
    position: static;
  }

  .identity-card {
    flex-direction: row;
    text-align: left;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: flex-start;
  }

  .avatar-wrap {
    flex-shrink: 0;
  }

  .identity-card > *:not(.avatar-wrap):not(.upload-spinner) {
    text-align: left;
  }

  .profile-meta {
    width: 100%;
  }

  .stats-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 540px) {
  .tabs-bar {
    flex-wrap: wrap;
  }

  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }

  .identity-card {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
}
</style>