<template>
  <div class="page-wrap vt">
    <BaseLoader v-if="loading" text="Cargando test..." size="large" />

    <div v-else-if="error" class="vt__error panel" role="alert">
      <p class="vt__error-title">{{ error }}</p>
      <router-link to="/" class="btn btn--secondary">Volver al catálogo</router-link>
    </div>

    <template v-else-if="test">
      <nav class="vt__crumbs" aria-label="Ruta">
        <router-link to="/">Explorar</router-link>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ test.title }}</span>
      </nav>

      <div class="vt__layout">
        <!-- ═══ Columna principal ═══ -->
        <div class="vt__main">
          <section class="vt__hero">
            <div class="vt__tags">
              <span class="vt__cats">{{ categoryLabel }}</span>
              <span v-if="test.difficulty" :class="['pill', difficultyClass(test.difficulty)]">{{ difficultyLabel(test.difficulty) }}</span>
            </div>
            <h1 class="vt__title">{{ test.title }}</h1>
            <p v-if="test.description" class="vt__desc">{{ test.description }}</p>
            <div class="vt__author">
              <UserAvatar v-if="test.user" :user="test.user" size="small" />
              <span>
                <template v-if="test.user">
                  Creado por <router-link :to="`/profile/${test.user.id}`">{{ test.user.name }}</router-link> ·
                </template>
                <template v-else>Creado por Usuario eliminado · </template>
                {{ formatDate(test.created_at) }}
              </span>
            </div>
          </section>

          <dl class="vt__strip" aria-label="Datos del test">
            <div><dt>Preguntas</dt><dd class="tabular">{{ test.questions_count }}</dd></div>
            <div><dt>Tiempo límite</dt><dd class="tabular">{{ test.time_limit ? `${Math.round(test.time_limit / 60)} min` : 'Sin límite' }}</dd></div>
            <div><dt>Intentos</dt><dd class="tabular">{{ test.completed_attempts_count ?? 0 }}</dd></div>
            <div><dt>Nota media</dt><dd class="tabular">{{ test.avg_score ? `${Math.round(test.avg_score)} %` : '—' }}</dd></div>
          </dl>

          <!-- Tu último intento -->
          <router-link
            v-if="test.last_attempt"
            :to="`/results/${test.last_attempt.id}`"
            class="vt__prev"
            :class="lastPassed ? 'is-pass' : 'is-fail'"
          >
            <span class="vt__prev-icon" aria-hidden="true">
              <i :class="lastPassed ? 'fa-solid fa-check' : 'fa-solid fa-xmark'"></i>
            </span>
            <span class="vt__prev-text">
              <strong>{{ lastPassed ? 'Aprobaste tu último intento' : 'No aprobaste tu último intento' }}</strong>
              <span>
                {{ formatDate(test.last_attempt.completed_at) }}
                <template v-if="test.max_attempts !== -1"> · intento {{ test.user_attempts }} de {{ test.max_attempts }}</template>
              </span>
            </span>
            <span class="vt__prev-score tabular">{{ Math.round(test.last_attempt.score) }} %</span>
          </router-link>

          <section class="vt__config" aria-labelledby="h-config">
            <h2 id="h-config" class="vt__h2">Cómo es este test</h2>
            <dl class="vt__rows">
              <div><dt>Nota para aprobar</dt><dd class="tabular">{{ test.pass_score }} %</dd></div>
              <div><dt>Intentos permitidos</dt><dd>{{ test.max_attempts === -1 ? 'Ilimitados' : test.max_attempts }}</dd></div>
              <div><dt>Penalización por fallo</dt><dd>{{ penaltyLabel(test.wrong_penalty) ? `Resta ${penaltyLabel(test.wrong_penalty)} de la pregunta` : 'No' }}</dd></div>
              <div><dt>Presentación</dt><dd>{{ test.display_mode === 'all' ? 'Todas las preguntas en una página' : 'Una pregunta por pantalla' }}</dd></div>
              <div><dt>Orden de las preguntas</dt><dd>{{ test.shuffle_questions ? 'Aleatorio' : 'Fijo' }}</dd></div>
              <div><dt>Orden de las respuestas</dt><dd>{{ test.shuffle_answers ? 'Aleatorio' : 'Fijo' }}</dd></div>
              <div><dt>Respuestas correctas al terminar</dt><dd>{{ test.show_correct_answers ? 'Se muestran' : 'No se muestran' }}</dd></div>
              <div><dt>Explicaciones</dt><dd>{{ test.show_explanations ? 'Se muestran' : 'No se muestran' }}</dd></div>
            </dl>
          </section>
        </div>

        <!-- ═══ Lateral ═══ -->
        <aside class="vt__side">
          <div class="vt__cta panel">
            <h2 class="vt__cta-title">{{ ctaHeading }}</h2>
            <dl class="vt__cta-rows">
              <div><dt>Duración</dt><dd>{{ test.time_limit ? `${Math.round(test.time_limit / 60)} min` : 'Sin límite' }}</dd></div>
              <div><dt>Preguntas</dt><dd class="tabular">{{ test.questions_count }}</dd></div>
              <div v-if="authStore.isLoggedIn && test.max_attempts !== -1">
                <dt>Tus intentos</dt><dd class="tabular">{{ test.user_attempts ?? 0 }} de {{ test.max_attempts }}</dd>
              </div>
              <div><dt>Para aprobar</dt><dd class="tabular">{{ test.pass_score }} %</dd></div>
              <div v-if="penaltyLabel(test.wrong_penalty)"><dt>Cada fallo</dt><dd class="tabular">resta {{ penaltyLabel(test.wrong_penalty) }}</dd></div>
            </dl>

            <template v-if="!authStore.isLoggedIn">
              <p class="vt__note">Inicia sesión para hacer este test y guardar tu nota.</p>
              <router-link :to="{ path: '/login', query: { redirect: route.fullPath } }" class="btn btn--lg vt__full">Iniciar sesión</router-link>
              <router-link :to="{ path: '/register', query: { redirect: route.fullPath } }" class="btn btn--secondary btn--lg vt__full">Crear cuenta gratis</router-link>
            </template>

            <p v-else-if="attemptsExhausted" class="vt__note vt__note--box">
              Has usado los {{ test.max_attempts }} intentos que permite este test.
            </p>

            <template v-else>
              <router-link :to="`/test/${test.id}/take`" class="btn btn--lg vt__full">
                {{ test.user_attempts > 0 ? 'Volver a intentarlo' : 'Empezar test' }}
              </router-link>
              <p v-if="test.max_attempts !== -1 && test.user_attempts > 0" class="vt__note">
                Será tu intento {{ test.user_attempts + 1 }} de {{ test.max_attempts }}.
              </p>
            </template>
          </div>

          <div v-if="canManageTest" class="vt__owner panel">
            <h2 class="vt__h3">Gestionar test</h2>
            <BaseAlert v-if="ownerError" variant="error">{{ ownerError }}</BaseAlert>
            <router-link :to="`/test/${test.id}/edit`" class="btn btn--secondary vt__full">
              <i class="fa-solid fa-pen" aria-hidden="true"></i>Editar test
            </router-link>
            <button type="button" class="btn btn--ghost vt__full vt__danger" :disabled="deletingOwner" @click="confirmDelete = true">
              <i class="fa-regular fa-trash-can" aria-hidden="true"></i>{{ deletingOwner ? 'Eliminando…' : 'Eliminar test' }}
            </button>
          </div>
        </aside>
      </div>

      <!-- Botón fijo en móvil -->
      <div v-if="mobileCta" class="vt__sticky">
        <router-link :to="mobileCta.to" class="btn btn--lg vt__full">{{ mobileCta.label }}</router-link>
      </div>

      <BaseModal v-model="confirmDelete" title="¿Eliminar este test?" max-width="440px">
        <p class="vt__modal-text">Se borrarán el test, sus preguntas y todos los intentos. No se puede deshacer.</p>
        <template #footer>
          <button type="button" class="btn btn--secondary" @click="confirmDelete = false">Cancelar</button>
          <button type="button" class="btn btn--danger" :disabled="deletingOwner" @click="deleteTest">Eliminar</button>
        </template>
      </BaseModal>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseAlert from '../components/base/BaseAlert.vue';
import BaseModal from '../components/base/BaseModal.vue';
import UserAvatar from '../components/user/UserAvatar.vue';
import { difficultyClass, difficultyLabel, penaltyLabel } from '../utils/tests';
import { useToast } from '../composables/useToast';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const test = ref(null);
const loading = ref(true);
const error = ref('');
const deletingOwner = ref(false);
const ownerError = ref('');
const confirmDelete = ref(false);

// ─── Computed ─────────────────────────────────────────────────────────────────
const attemptsExhausted = computed(() => {
  if (!test.value || test.value.max_attempts === -1) return false;
  return (test.value.user_attempts ?? 0) >= test.value.max_attempts;
});

const ctaHeading = computed(() => {
  if (!authStore.isLoggedIn) return 'Haz este test';
  if (attemptsExhausted.value) return 'Intentos agotados';
  if (test.value?.user_attempts > 0) return 'Ya lo has hecho';
  return 'Haz este test';
});

const lastPassed = computed(
  () => test.value?.last_attempt && Number(test.value.last_attempt.score) >= Number(test.value.pass_score)
);

const categoryLabel = computed(() => (test.value?.categories || []).map((c) => c.name).join(' · ') || 'General');

const mobileCta = computed(() => {
  if (!test.value) return null;
  if (!authStore.isLoggedIn) return { to: { path: '/login', query: { redirect: route.fullPath } }, label: 'Inicia sesión para empezar' };
  if (attemptsExhausted.value) return null;
  return {
    to: `/test/${test.value.id}/take`,
    label: test.value.user_attempts > 0 ? 'Volver a intentarlo' : 'Empezar test',
  };
});

const canManageTest = computed(() => {
  if (!authStore.isLoggedIn || !test.value) return false;

  if (test.value.can_manage !== undefined && test.value.can_manage !== null) {
    return !!test.value.can_manage;
  }

  const isOwner = Number(authStore.userId) === Number(test.value.user_id);
  const isAdmin = authStore.userRole === 'admin' && authStore.user?.email === 'admin@example.com';
  return isOwner || isAdmin;
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatDate = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
};

const deleteTest = async () => {
  if (!test.value) return;
  ownerError.value = '';
  deletingOwner.value = true;

  try {
    await api.delete(`/tests/${test.value.id}`);
    confirmDelete.value = false;
    toast.success('Test eliminado.');
    await router.push('/');
  } catch (err) {
    confirmDelete.value = false;
    ownerError.value = err.response?.data?.message || 'No se pudo eliminar el test.';
    console.error(err);
  } finally {
    deletingOwner.value = false;
  }
};

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(async () => {
  if (!route.params.id) {
    error.value = 'El enlace del test no es válido.';
    loading.value = false;
    return;
  }
  try {
    const { data } = await api.get(`/tests/${route.params.id}`);
    test.value = data;
  } catch (err) {
    error.value = err.response?.status === 404 ? 'Este test no existe o ya no está publicado.' : 'No se pudo cargar el test.';
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.vt {
  padding-top: 32px;
}

.vt__crumbs {
  display: flex;
  gap: 8px;
  font-size: var(--fs-small);
  color: var(--muted);
  margin-bottom: 32px;
  min-width: 0;
}

.vt__crumbs a {
  color: var(--muted);
  text-decoration: none;
}

.vt__crumbs a:hover {
  color: var(--text);
}

.vt__crumbs [aria-current] {
  color: var(--text);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.vt__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 48px;
  align-items: start;
}

.vt__main {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

/* Hero */
.vt__hero {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vt__tags {
  display: flex;
  align-items: center;
  gap: 12px;
}

.vt__cats {
  font-size: var(--fs-small);
  color: var(--muted);
}

.vt__title {
  font-size: 2.25rem;
  letter-spacing: -0.025em;
  line-height: 1.15;
  margin: 0;
}

.vt__desc {
  font-size: var(--fs-lead);
  color: var(--text-body);
  max-width: 680px;
  margin: 0;
}

.vt__author {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: var(--fs-small);
  color: var(--muted);
  margin-top: 4px;
}

.vt__author a {
  color: var(--text);
  font-weight: 500;
  text-decoration: none;
}

.vt__author a:hover {
  color: var(--accent);
}

/* Strip de datos */
.vt__strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  background: var(--raised);
  border-radius: var(--radius-panel);
  margin: 0;
}

.vt__strip > div {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  border-left: 1px solid var(--border);
}

.vt__strip > div:first-child {
  border-left: 0;
}

.vt__strip dt {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.vt__strip dd {
  margin: 0;
  font-size: 1.375rem;
}

/* Último intento */
.vt__prev {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-radius: var(--radius-panel);
  text-decoration: none;
  color: var(--text);
  transition: filter 0.15s;
}

.vt__prev.is-pass {
  background: var(--positive-bg);
}

.vt__prev.is-fail {
  background: var(--negative-bg);
}

.vt__prev:hover {
  color: var(--text);
  filter: brightness(0.98);
}

.vt__prev-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  color: #fff;
}

.is-pass .vt__prev-icon {
  background: var(--positive);
}

.is-fail .vt__prev-icon {
  background: var(--negative);
}

.vt__prev-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  font-size: var(--fs-small);
}

.vt__prev-text strong {
  font-size: var(--fs-body);
  font-weight: 600;
}

.vt__prev-text span {
  color: var(--text-body);
}

.vt__prev-score {
  font-size: 1.5rem;
  font-weight: 600;
}

/* Configuración */
.vt__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0 0 8px;
}

.vt__rows {
  margin: 0;
}

.vt__rows > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 0;
  border-top: 1px solid var(--rule);
  font-size: var(--fs-small);
}

.vt__rows dt {
  color: var(--text-body);
}

.vt__rows dd {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

/* Lateral */
.vt__side {
  position: sticky;
  top: calc(var(--header-h) + 24px);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.vt__cta,
.vt__owner {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.vt__cta-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}

.vt__cta-rows {
  margin: 0 0 8px;
}

.vt__cta-rows > div {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid var(--border);
  font-size: var(--fs-small);
}

.vt__cta-rows dt {
  color: var(--muted);
}

.vt__cta-rows dd {
  margin: 0;
  font-weight: 500;
}

.vt__full {
  width: 100%;
}

.vt__note {
  font-size: var(--fs-meta);
  color: var(--muted);
  margin: 0;
  text-align: center;
}

.vt__note--box {
  text-align: left;
  font-size: var(--fs-small);
  background: var(--bg);
  color: var(--text-body);
  padding: 12px 14px;
  border-radius: var(--radius-card);
}

.vt__h3 {
  font-size: var(--fs-body);
  font-weight: 600;
  margin: 0;
}

.vt__danger {
  color: var(--negative);
}

.vt__danger:hover {
  background: var(--negative-bg);
  color: var(--negative-ink);
}

.vt__modal-text {
  color: var(--text-body);
  margin: 0;
}

.vt__error {
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  text-align: center;
  margin-top: 32px;
}

.vt__error-title {
  font-weight: 600;
  margin: 0;
}

.vt__sticky {
  display: none;
}

@media (max-width: 960px) {
  .vt__layout {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .vt__side {
    position: static;
  }
}

@media (max-width: 720px) {
  .vt__title {
    font-size: 1.75rem;
  }

  .vt__strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .vt__strip > div:nth-child(3) {
    border-left: 0;
  }

  .vt__strip > div:nth-child(n + 3) {
    border-top: 1px solid var(--border);
  }

  /* En móvil la acción principal queda siempre a mano */
  .vt__sticky {
    display: block;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
    padding: 12px var(--gutter) calc(12px + env(safe-area-inset-bottom));
    background: var(--bg);
    border-top: 1px solid var(--rule);
  }

  .vt {
    padding-bottom: 88px;
  }
}
</style>
