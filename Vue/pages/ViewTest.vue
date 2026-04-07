<template>
  <BaseLoader v-if="loading" text="Cargando test..." size="large" />
  <BaseAlert v-else-if="error" variant="error">{{ error }}</BaseAlert>

  <div v-else-if="test" class="view-test">

      <!-- Layout principal -->
    <div class="page-layout">

      <!-- ═══════════════════════════════════════
           COLUMNA PRINCIPAL
      ═══════════════════════════════════════ -->
      <div class="main-col">

        <!-- Hero -->
        <section class="test-hero">
          <div class="hero-badges">
            <span
              v-for="cat in test.categories"
              :key="cat.id"
              class="cat-badge"
            >
              <i v-if="cat.icon" :class="`fa-solid fa-${cat.icon}`"></i>
              {{ cat.name }}
            </span>
            <span :class="['diff-badge', `diff-${test.difficulty}`]">
              {{ diffLabel(test.difficulty) }}
            </span>
            <span v-if="test.completed_attempts_count > 10" class="pop-badge">
              popular
            </span>
          </div>

          <h1 class="test-title">{{ test.title }}</h1>

          <p v-if="test.description" class="test-desc">{{ test.description }}</p>

          <div class="author-row">
            <UserAvatar v-if="test.user" :user="test.user" size="small" />
            <UserAvatar v-else name="?" size="small" />
            <span class="author-text">
              Creado por
              <router-link
                v-if="test.user"
                :to="`/profile/${test.user.id}`"
                class="author-link"
              >{{ test.user.name }}</router-link>
              · {{ formatDate(test.created_at) }}
            </span>
          </div>
        </section>

        <!-- Métricas rápidas -->
        <div class="stat-strip">
          <div class="stat-chip">
            <i class="fa-solid fa-circle-question stat-icon"></i>
            <span class="stat-val">{{ test.questions_count }}</span>
            <span class="stat-lbl">preguntas</span>
          </div>
          <div class="stat-chip">
            <i class="fa-regular fa-clock stat-icon"></i>
            <span class="stat-val">
              {{ test.time_limit ? Math.round(test.time_limit / 60) + ' min' : '∞' }}
            </span>
            <span class="stat-lbl">tiempo límite</span>
          </div>
          <div class="stat-chip">
            <i class="fa-solid fa-users stat-icon"></i>
            <span class="stat-val">{{ test.completed_attempts_count ?? 0 }}</span>
            <span class="stat-lbl">intentos</span>
          </div>
          <div class="stat-chip">
            <i class="fa-solid fa-chart-simple stat-icon"></i>
            <span class="stat-val">
              {{ test.avg_score ? Number(test.avg_score).toFixed(1) + '%' : '—' }}
            </span>
            <span class="stat-lbl">nota media</span>
          </div>
        </div>

        <!-- Banner resultado previo -->
        <div v-if="test.last_attempt" class="prev-banner">
          <div class="prev-left">
            <div :class="['prev-icon', test.last_attempt.score >= test.pass_score ? 'prev-icon--pass' : 'prev-icon--fail']">
              <i :class="test.last_attempt.score >= test.pass_score ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
            </div>
            <div class="prev-info">
              <p class="prev-title">
                {{ test.last_attempt.score >= test.pass_score ? 'Aprobado en tu último intento' : 'No aprobado en tu último intento' }}
              </p>
              <p class="prev-sub">
                Completado el {{ formatDate(test.last_attempt.completed_at) }}
                <span v-if="test.max_attempts !== -1">
                  · Intento {{ test.user_attempts }} de {{ test.max_attempts }}
                </span>
              </p>
            </div>
          </div>
          <div :class="['prev-score', test.last_attempt.score >= test.pass_score ? 'prev-score--pass' : 'prev-score--fail']">
            {{ Number(test.last_attempt.score).toFixed(1) }}%
          </div>
        </div>

        <!-- Configuración -->
        <section class="config-card">
          <h2 class="section-title">Configuración del test</h2>
          <div class="config-list">
            <div class="config-row">
              <span class="config-label">Nota mínima para aprobar</span>
              <span class="config-val">{{ test.pass_score }}%</span>
            </div>
            <div class="config-row">
              <span class="config-label">Intentos permitidos</span>
              <span class="config-val">
                {{ test.max_attempts === -1 ? 'Ilimitados' : test.max_attempts }}
              </span>
            </div>
            <div class="config-row">
              <span class="config-label">Orden de preguntas</span>
              <span class="config-val">
                {{ test.shuffle_questions ? 'Aleatorio' : 'Fijo' }}
              </span>
            </div>
            <div class="config-row">
              <span class="config-label">Orden de respuestas</span>
              <span class="config-val">
                {{ test.shuffle_answers ? 'Aleatorio' : 'Fijo' }}
              </span>
            </div>
            <div class="config-row">
              <span class="config-label">Mostrar respuestas correctas</span>
              <span :class="['config-val', test.show_correct_answers ? 'val--yes' : 'val--no']">
                <i :class="test.show_correct_answers ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                {{ test.show_correct_answers ? 'Sí' : 'No' }}
              </span>
            </div>
            <div class="config-row config-row--last">
              <span class="config-label">Mostrar explicaciones</span>
              <span :class="['config-val', test.show_explanations ? 'val--yes' : 'val--no']">
                <i :class="test.show_explanations ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
                {{ test.show_explanations ? 'Sí' : 'No' }}
              </span>
            </div>
          </div>
        </section>

      </div>

      <!-- ═══════════════════════════════════════
           SIDEBAR
      ═══════════════════════════════════════ -->
      <aside class="side-col">

        <!-- Panel de acción principal -->
        <div class="cta-card">
          <p class="cta-heading">
            {{ ctaHeading }}
          </p>

          <div class="cta-meta">
            <div class="cta-row">
              <span class="cta-label">Duración aprox.</span>
              <span class="cta-value">
                {{ test.time_limit ? Math.round(test.time_limit / 60) + ' min' : 'Sin límite' }}
              </span>
            </div>
            <div class="cta-row">
              <span class="cta-label">Preguntas</span>
              <span class="cta-value">{{ test.questions_count }}</span>
            </div>
            <div v-if="authStore.isLoggedIn && test.max_attempts !== -1" class="cta-row">
              <span class="cta-label">Tus intentos</span>
              <span class="cta-value">
                {{ test.user_attempts ?? 0 }} de {{ test.max_attempts }}
              </span>
            </div>
            <div class="cta-row">
              <span class="cta-label">Para aprobar</span>
              <span class="cta-value">{{ test.pass_score }}%</span>
            </div>
          </div>

          <div class="cta-sep"></div>

          <!-- Sin sesión -->
          <template v-if="!authStore.isLoggedIn">
            <p class="cta-note">
              Debes iniciar sesión para realizar este test.
            </p>
            <router-link to="/login" class="btn-action btn-action--primary">
              Iniciar sesión
            </router-link>
            <router-link to="/register" class="btn-action btn-action--ghost">
              Crear cuenta gratis
            </router-link>
          </template>

          <!-- Intentos agotados -->
          <template v-else-if="attemptsExhausted">
            <BaseAlert variant="info" :show-icon="false">
              Has alcanzado el número máximo de intentos para este test.
            </BaseAlert>
          </template>

          <!-- Puede intentar -->
          <template v-else>
            <router-link
              :to="`/test/${test.id}/take`"
              class="btn-action btn-action--primary"
            >
              <i class="fa-solid fa-play"></i>
              {{ test.user_attempts > 0 ? 'Volver a intentar' : 'Empezar test' }}
            </router-link>
            <p v-if="test.user_attempts > 0 && test.max_attempts !== -1" class="cta-note">
              Intento {{ test.user_attempts + 1 }} de {{ test.max_attempts }}
            </p>
          </template>
        </div>

        <div v-if="isOwner" class="owner-card">
          <h2 class="section-title">Gestionar test</h2>

          <BaseAlert v-if="ownerError" variant="error">{{ ownerError }}</BaseAlert>

          <div class="owner-actions">
            <router-link :to="`/test/${test.id}/edit`" class="btn-action btn-action--primary">
              <i class="fa-solid fa-pen"></i>
              Editar test completo
            </router-link>
            <button class="btn-action btn-action--danger" :disabled="deletingOwner" @click="deleteTest">
              <i class="fa-solid fa-trash"></i>
              {{ deletingOwner ? 'Eliminando...' : 'Eliminar test' }}
            </button>
          </div>
        </div>

        <!-- Estadísticas globales -->
        <div v-if="test.avg_score || test.completed_attempts_count" class="gstats-card">
          <h2 class="section-title">Estadísticas generales</h2>
          <div class="gstats-list">
            <div class="gstat-row">
              <span class="gstat-label">Total intentos</span>
              <span class="gstat-val">{{ test.completed_attempts_count ?? 0 }}</span>
            </div>
            <div v-if="test.avg_score" class="gstat-row">
              <span class="gstat-label">Nota media</span>
              <span class="gstat-val">{{ Number(test.avg_score).toFixed(1) }}%</span>
            </div>
            <div class="gstat-row gstat-row--last">
              <span class="gstat-label">Visitas</span>
              <span class="gstat-val">{{ test.views_count ?? 0 }}</span>
            </div>
          </div>
        </div>

      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseAlert from '../components/base/BaseAlert.vue';
import UserAvatar from '../components/user/UserAvatar.vue';

const route     = useRoute();
const router    = useRouter();
const authStore = useAuthStore();

const test    = ref(null);
const loading = ref(true);
const error   = ref('');
const deletingOwner = ref(false);
const ownerError = ref('');

// ─── Computed ─────────────────────────────────────────────────────────────────
const attemptsExhausted = computed(() => {
  if (!test.value || test.value.max_attempts === -1) return false;
  return (test.value.user_attempts ?? 0) >= test.value.max_attempts;
});

const ctaHeading = computed(() => {
  if (!authStore.isLoggedIn)   return 'Realiza este test';
  if (attemptsExhausted.value) return 'Intentos agotados';
  if (test.value?.user_attempts > 0) return 'Ya has realizado este test';
  return 'Realiza este test';
});

const isOwner = computed(() => {
  if (!authStore.isLoggedIn || !test.value) return false;
  return Number(authStore.userId) === Number(test.value.user_id);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const diffLabel = (d) =>
  ({ easy: 'Fácil', medium: 'Medio', hard: 'Difícil' }[d] ?? d);

const formatDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('es-ES', {
    day: '2-digit', month: 'short', year: 'numeric',
  });
};

const deleteTest = async () => {
  if (!test.value) return;
  const ok = window.confirm('Esta accion eliminara el test y sus datos asociados. ¿Continuar?');
  if (!ok) return;

  ownerError.value = '';
  deletingOwner.value = true;

  try {
    await api.delete(`/tests/${test.value.id}`);
    await router.push('/');
  } catch (err) {
    ownerError.value = err.response?.data?.message || 'No se pudo eliminar el test.';
    console.error(err);
  } finally {
    deletingOwner.value = false;
  }
};

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (!route.params.id) {
    error.value = 'ID de test inválido.';
    loading.value = false;
    return;
  }
  try {
    const { data } = await api.get(`/tests/${route.params.id}`);
    test.value = data;
  } catch (err) {
    error.value = 'No se pudo cargar el test.';
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* ── Página ──────────────────────────────────────────────────────────────── */
.view-test {
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* ── Layout dos columnas ─────────────────────────────────────────────────── */
.page-layout {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 1.5rem;
  align-items: start;
}

.main-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-width: 0;
}

.side-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.test-hero {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 2rem;
}

.hero-badges {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.cat-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 500;
  padding: 4px 11px;
  border-radius: 20px;
  background: #ebf0ff;
  color: #4c5fd5;
}

.cat-badge i {
  font-size: 10px;
}

.diff-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 4px 11px;
  border-radius: 20px;
}

.diff-easy   { background: #e8f5e9; color: #2e7d32; }
.diff-medium { background: #fff8e1; color: #e65100; }
.diff-hard   { background: #fce4ec; color: #c62828; }

.pop-badge {
  font-size: 11px;
  font-weight: 500;
  padding: 4px 11px;
  border-radius: 20px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}

.test-title {
  font-size: 1.65rem;
  font-weight: 600;
  color: #1a202c;
  line-height: 1.3;
  margin-bottom: 0.75rem;
}

.test-desc {
  font-size: 0.95rem;
  color: #718096;
  line-height: 1.7;
  margin-bottom: 1.25rem;
}

.author-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.author-text {
  font-size: 13px;
  color: #a0aec0;
}

.author-link {
  color: #4a5568;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.15s;
}

.author-link:hover {
  color: #667eea;
}

/* ── Stats strip ─────────────────────────────────────────────────────────── */
.stat-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}

.stat-chip {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-align: center;
}

.stat-icon {
  font-size: 14px;
  color: #667eea;
  margin-bottom: 2px;
}

.stat-val {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a202c;
  line-height: 1;
}

.stat-lbl {
  font-size: 11px;
  color: #a0aec0;
}

/* ── Banner resultado previo ─────────────────────────────────────────────── */
.prev-banner {
  background: #f0fff4;
  border: 1px solid #9ae6b4;
  border-radius: 14px;
  padding: 1.25rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.prev-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.prev-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: white;
  flex-shrink: 0;
}

.prev-icon--pass { background: #38a169; }
.prev-icon--fail { background: #e53e3e; }

.prev-title {
  font-size: 14px;
  font-weight: 600;
  color: #22543d;
  margin-bottom: 3px;
}

.prev-sub {
  font-size: 12px;
  color: #48bb78;
}

.prev-score {
  font-size: 2.25rem;
  font-weight: 700;
  line-height: 1;
  flex-shrink: 0;
}

.prev-score--pass { color: #276749; }
.prev-score--fail { color: #c53030; }

/* prev-banner cuando es fallo */
.prev-banner:has(.prev-icon--fail) {
  background: #fff5f5;
  border-color: #feb2b2;
}

.prev-banner:has(.prev-icon--fail) .prev-title { color: #742a2a; }
.prev-banner:has(.prev-icon--fail) .prev-sub   { color: #fc8181; }

/* ── Config card ─────────────────────────────────────────────────────────── */
.config-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
}

.section-title {
  font-size: 11px;
  font-weight: 600;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 1rem;
}

.config-list {
  display: flex;
  flex-direction: column;
}

.config-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f7fafc;
  gap: 1rem;
}

.config-row--last {
  border-bottom: none;
  padding-bottom: 0;
}

.config-label {
  font-size: 13px;
  color: #718096;
}

.config-val {
  font-size: 13px;
  font-weight: 500;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
}

.val--yes { color: #38a169; }
.val--no  { color: #a0aec0; }

/* ── CTA card (sidebar) ──────────────────────────────────────────────────── */
.cta-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.cta-heading {
  font-size: 15px;
  font-weight: 600;
  color: #1a202c;
}

.cta-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.cta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
}

.cta-label { color: #718096; }

.cta-value {
  font-weight: 500;
  color: #2d3748;
}

.cta-sep {
  height: 1px;
  background: #f7fafc;
  margin: 0 -0.25rem;
}

.cta-note {
  font-size: 12px;
  color: #a0aec0;
  text-align: center;
  line-height: 1.55;
}

/* ── Botones de acción ───────────────────────────────────────────────────── */
.btn-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  font-family: inherit;
  text-decoration: none;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-action--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-action--primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.4);
}

.btn-action--ghost {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #4a5568;
}

.btn-action--ghost:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
}

/* ── Global stats card (sidebar) ─────────────────────────────────────────── */
.gstats-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
}

.owner-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.owner-actions {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.btn-action--danger {
  background: #fff5f5;
  border: 1px solid #fed7d7;
  color: #c53030;
}

.btn-action--danger:hover {
  background: #ffe6e6;
  border-color: #fcb4b4;
}

.btn-action:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.gstats-list {
  display: flex;
  flex-direction: column;
}

.gstat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f7fafc;
  font-size: 13px;
}

.gstat-row--last {
  border-bottom: none;
  padding-bottom: 0;
}

.gstat-label { color: #718096; }

.gstat-val {
  font-weight: 600;
  color: #1a202c;
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .test-hero,
body.dark-mode .stat-chip,
body.dark-mode .config-card,
body.dark-mode .cta-card,
body.dark-mode .owner-card,
body.dark-mode .gstats-card {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .test-title,
body.dark-mode .stat-val,
body.dark-mode .cta-heading,
body.dark-mode .cta-value,
body.dark-mode .gstat-val,
body.dark-mode .config-val {
  color: var(--tm-text) !important;
}

body.dark-mode .test-desc,
body.dark-mode .config-label,
body.dark-mode .cta-label,
body.dark-mode .gstat-label,
body.dark-mode .author-text,
body.dark-mode .stat-lbl {
  color: var(--tm-muted) !important;
}

body.dark-mode .config-row,
body.dark-mode .gstat-row {
  border-bottom-color: var(--tm-border) !important;
}

body.dark-mode .cta-sep {
  background: var(--tm-border);
}

body.dark-mode .btn-action--ghost {
  border-color: var(--tm-border);
  color: var(--tm-muted);
}

body.dark-mode .btn-action--ghost:hover {
  background: var(--tm-hover);
}

body.dark-mode .btn-action--danger {
  background: rgba(197, 48, 48, 0.1) !important;
  border-color: rgba(197, 48, 48, 0.35) !important;
  color: #fc8181 !important;
}

body.dark-mode .btn-action--primary {
  color: #fff !important;
}

body.dark-mode .prev-banner {
  background: rgba(56, 161, 105, 0.1) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .prev-title { color: var(--tm-text) !important; }
body.dark-mode .prev-sub   { color: var(--tm-muted) !important; }
body.dark-mode .prev-score--pass { color: var(--tm-text) !important; }
body.dark-mode .prev-score--fail { color: var(--tm-text) !important; }

body.dark-mode .cat-badge {
  background: rgba(102, 126, 234, 0.15) !important;
  color: #a78bfa !important;
}

body.dark-mode .author-link {
  color: #a0aec0 !important;
}

body.dark-mode .author-link:hover {
  color: #a78bfa !important;
}

body.dark-mode .val--yes { color: #68d391 !important; }
body.dark-mode .val--no  { color: #4a5568 !important; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .page-layout {
    grid-template-columns: 1fr;
  }

  .side-col {
    position: static;
    order: -1;
  }

  .stat-strip {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .test-hero {
    padding: 1.25rem;
  }

  .test-title {
    font-size: 1.35rem;
  }

  .stat-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .prev-banner {
    flex-direction: column;
    align-items: flex-start;
  }

  .prev-score {
    font-size: 1.75rem;
  }

}
</style>