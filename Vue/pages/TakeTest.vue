<template>
  <div class="tt">
    <!-- ═══ Barra superior: título, progreso y tiempo ═══ -->
    <header class="tt__bar">
      <div class="tt__bar-inner">
        <button type="button" class="tt__exit" aria-label="Salir del test" @click="requestExit">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i><span class="tt__hide-sm">Salir</span>
        </button>
        <span class="tt__name">
          <span v-if="isReview" class="pill pill--medium tt__mode">Repaso de fallos</span>{{ testTitle || 'Cargando…' }}
        </span>
        <span class="tt__spacer"></span>
        <span v-if="questions.length" class="tt__count tt__hide-sm tabular">{{ answeredCount }} de {{ questions.length }} respondidas</span>
        <span
          v-if="timeLimit"
          class="tt__timer tabular"
          :class="{ 'is-warn': timeLeft <= 120 }"
          role="timer"
          :aria-label="`Tiempo restante ${formatClock(timeLeft)}`"
        >
          <i class="fa-regular fa-clock" aria-hidden="true"></i>{{ formatClock(timeLeft) }}
        </span>
        <button v-if="questions.length" type="button" class="btn btn--secondary tt__send" @click="openReview">Enviar</button>
      </div>
      <div
        class="tt__progress"
        role="progressbar"
        aria-label="Preguntas respondidas"
        aria-valuemin="0"
        :aria-valuemax="questions.length"
        :aria-valuenow="answeredCount"
      >
        <div class="tt__progress-bar" :style="{ width: progressPct }"></div>
      </div>
    </header>

    <BaseLoader v-if="loading" text="Cargando preguntas…" size="large" />

    <div v-else-if="loadError" class="tt__col tt__state">
      <p class="tt__state-title">{{ loadError }}</p>
      <router-link :to="`/test/${route.params.id}`" class="btn btn--secondary">Volver al test</router-link>
    </div>

    <!-- Repaso sin preguntas pendientes -->
    <div v-else-if="!questions.length" class="tt__col tt__state">
      <i class="fa-solid fa-circle-check tt__state-icon" aria-hidden="true"></i>
      <p class="tt__state-title">{{ isReview ? 'No tienes fallos pendientes en este test' : 'Este test no tiene preguntas' }}</p>
      <p v-if="isReview" class="tt__state-text">Has acertado todas las preguntas que habías fallado.</p>
      <router-link :to="`/test/${route.params.id}`" class="btn btn--secondary">Volver al test</router-link>
    </div>

    <!-- ═══ Modo: una pregunta por pantalla ═══ -->
    <template v-else-if="isPaged && current">
      <main class="tt__col tt__main">
        <div class="tt__qhead">
          <span class="tt__meta">Pregunta {{ index + 1 }} de {{ questions.length }} · {{ questionTypeLabel(current.type) }}</span>
          <h1 ref="questionHeading" class="tt__q" tabindex="-1">{{ promptOf(current) }}</h1>
        </div>
        <img v-if="current.image_url" :src="`/uploads/${current.image_url}`" alt="" class="tt__img" />

        <AnswerInput :key="current.id" v-model="answers[current.id]" :question="current" :number="index + 1" />

        <div class="tt__nav">
          <span class="tt__meta">Ir a la pregunta</span>
          <nav class="tt__nav-list" aria-label="Preguntas del test">
            <button
              v-for="(q, i) in questions"
              :key="q.id"
              type="button"
              class="tt__nav-n tabular"
              :class="{ 'is-done': isAnswered(q), 'is-cur': i === index }"
              :aria-current="i === index ? 'step' : undefined"
              :aria-label="`Pregunta ${i + 1}, ${isAnswered(q) ? 'respondida' : 'sin responder'}`"
              @click="goTo(i)"
            >{{ i + 1 }}</button>
          </nav>
        </div>
      </main>

      <footer class="tt__foot">
        <div class="tt__col tt__foot-inner">
          <button type="button" class="btn btn--secondary btn--lg" :disabled="index === 0" @click="goTo(index - 1)">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Anterior
          </button>
          <button v-if="index < questions.length - 1" type="button" class="btn btn--lg" @click="goTo(index + 1)">
            Siguiente<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          </button>
          <button v-else type="button" class="btn btn--lg" @click="openReview">Revisar y enviar</button>
        </div>
      </footer>
    </template>

    <!-- ═══ Modo: todas en una página ═══ -->
    <main v-else-if="!isPaged" class="tt__col tt__main tt__main--all">
      <section
        v-for="(q, i) in questions"
        :id="`pregunta-${i + 1}`"
        :key="q.id"
        class="tt__block"
        :aria-labelledby="`qh-${q.id}`"
      >
        <div class="tt__qhead">
          <span class="tt__meta">Pregunta {{ i + 1 }} de {{ questions.length }} · {{ questionTypeLabel(q.type) }}</span>
          <h2 :id="`qh-${q.id}`" class="tt__q tt__q--sm">{{ promptOf(q) }}</h2>
        </div>
        <img v-if="q.image_url" :src="`/uploads/${q.image_url}`" alt="" class="tt__img" />
        <AnswerInput v-model="answers[q.id]" :question="q" :number="i + 1" />
      </section>
      <div class="tt__all-end">
        <span class="tt__meta tabular">{{ answeredCount }} de {{ questions.length }} respondidas</span>
        <button type="button" class="btn btn--lg" @click="openReview">Revisar y enviar</button>
      </div>
    </main>

    <!-- ═══ Resumen antes de enviar ═══ -->
    <BaseModal v-model="showReview" title="¿Enviar tus respuestas?" max-width="460px">
      <p class="tt__modal-text">
        Has respondido {{ answeredCount }} de {{ questions.length }} preguntas. Cuando envíes ya no podrás cambiarlas.
      </p>
      <p v-if="penalty" class="tt__modal-text tt__penalty">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        Cada respuesta incorrecta resta {{ penalty }} de sus puntos. Las preguntas en blanco no restan.
      </p>
      <div v-if="pending.length" class="tt__pending">
        <span class="tt__pending-title">{{ pending.length === 1 ? '1 pregunta sin responder' : `${pending.length} preguntas sin responder` }}</span>
        <div class="tt__pending-list">
          <button
            v-for="p in pending"
            :key="p.i"
            type="button"
            class="tt__nav-n tabular"
            :aria-label="`Ir a la pregunta ${p.i + 1}`"
            @click="goToFromReview(p.i)"
          >{{ p.i + 1 }}</button>
        </div>
      </div>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="showReview = false">Seguir respondiendo</button>
        <button type="button" class="btn" :disabled="submitting" @click="submitTest">
          <i v-if="submitting" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
          {{ submitting ? 'Enviando…' : 'Enviar respuestas' }}
        </button>
      </template>
    </BaseModal>

    <!-- ═══ Confirmar salida ═══ -->
    <BaseModal v-model="showExit" title="¿Salir del test?" max-width="440px" @close="cancelExit">
      <p class="tt__modal-text">
        Si sales ahora se perderán tus respuestas<template v-if="!isReview"> y no contará como intento</template>.
      </p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="cancelExit">Seguir con el test</button>
        <button type="button" class="btn btn--danger" @click="confirmExit">Salir</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseModal from '../components/base/BaseModal.vue';
import AnswerInput from '../components/question/AnswerInput.vue';
import { formatClock, questionTypeLabel, isAnswerFilled, numberBlanks, penaltyLabel } from '../utils/tests';
import { useToast } from '../composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const loading = ref(true);
const loadError = ref('');
const submitting = ref(false);
const submitted = ref(false);
const testTitle = ref('');
const timeLimit = ref(0);
const displayMode = ref('paged');
const penalty = ref('');
const isReview = computed(() => route.name === 'review-test');
const questions = ref([]);
const answers = reactive({});
const startedAt = ref(null);
const index = ref(0);
const questionHeading = ref(null);

const showReview = ref(false);
const showExit = ref(false);
let pendingLeave = null;

// ─── Derivados ───────────────────────────────────────────────────────────────
const isPaged = computed(() => displayMode.value !== 'all');
const current = computed(() => questions.value[index.value]);

const isAnswered = (q) => isAnswerFilled(answers[q.id]);

// En «Rellenar huecos» los ___ se numeran para relacionarlos con sus campos
const promptOf = (q) => (q.type === 'fill_blank' ? numberBlanks(q.content) : q.content);

const answeredCount = computed(() => questions.value.filter(isAnswered).length);
const progressPct = computed(() =>
  questions.value.length ? `${(answeredCount.value / questions.value.length) * 100}%` : '0%'
);
const pending = computed(() =>
  questions.value.map((q, i) => ({ q, i })).filter(({ q }) => !isAnswered(q))
);

// ─── Navegación ──────────────────────────────────────────────────────────────
const goTo = async (i) => {
  if (i < 0 || i >= questions.value.length) return;
  if (!isPaged.value) {
    document.getElementById(`pregunta-${i + 1}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return;
  }
  index.value = i;
  window.scrollTo({ top: 0 });
  await nextTick();
  // Lleva el foco a la pregunta para lectores de pantalla y teclado
  questionHeading.value?.focus({ preventScroll: true });
};

const goToFromReview = (i) => {
  showReview.value = false;
  goTo(i);
};

const openReview = () => {
  showReview.value = true;
};

// Flechas izquierda/derecha para moverse (salvo escribiendo una respuesta)
const onKeydown = (e) => {
  if (!isPaged.value || showReview.value || showExit.value) return;
  const tag = e.target?.tagName;
  if ((tag === 'INPUT' && !['radio', 'checkbox'].includes(e.target.type)) || tag === 'TEXTAREA') return;
  if (e.key === 'ArrowRight' && !e.altKey) goTo(index.value + 1);
  if (e.key === 'ArrowLeft' && !e.altKey) goTo(index.value - 1);
};

// ─── Temporizador (por fecha límite: no se desfasa si la pestaña se duerme) ──
const timeLeft = ref(0);
let deadline = 0;
let timer = null;
let warned = false;

const tick = () => {
  timeLeft.value = Math.max(0, Math.round((deadline - Date.now()) / 1000));
  if (!warned && timeLeft.value <= 120 && timeLeft.value > 0) {
    warned = true;
    toast.info('Quedan 2 minutos.');
  }
  if (timeLeft.value === 0) {
    clearInterval(timer);
    timer = null;
    handleTimeExpired();
  }
};

const startTimer = () => {
  if (!timeLimit.value) return;
  deadline = Date.now() + timeLimit.value * 1000;
  timeLeft.value = timeLimit.value;
  timer = setInterval(tick, 1000);
};

const handleTimeExpired = () => {
  toast.info('Se acabó el tiempo. Enviamos tus respuestas.');
  showReview.value = false;
  submitTest();
};

// ─── Carga ───────────────────────────────────────────────────────────────────
onMounted(async () => {
  window.addEventListener('keydown', onKeydown);
  window.addEventListener('beforeunload', onBeforeUnload);
  try {
    const { data } = await api.get(`/tests/${route.params.id}/questions`, {
      params: isReview.value ? { mode: 'review' } : {},
    });
    testTitle.value = data.test.title;
    timeLimit.value = data.test.time_limit || 0;
    displayMode.value = data.test.display_mode === 'all' ? 'all' : 'paged';
    penalty.value = penaltyLabel(data.test.wrong_penalty);
    questions.value = data.questions;
    startedAt.value = Math.floor(Date.now() / 1000);
    startTimer();
  } catch (err) {
    console.error(err);
    loadError.value = err.response?.data?.message || 'No se pudieron cargar las preguntas.';
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown);
  window.removeEventListener('beforeunload', onBeforeUnload);
  if (timer) clearInterval(timer);
});

// ─── Salir sin perder respuestas por accidente ───────────────────────────────
const hasProgress = () => !submitted.value && answeredCount.value > 0;

function onBeforeUnload(e) {
  if (hasProgress()) {
    e.preventDefault();
    e.returnValue = '';
  }
}

onBeforeRouteLeave((to, from, next) => {
  if (!hasProgress()) return next();
  pendingLeave = next;
  showExit.value = true;
});

const requestExit = () => {
  router.push(`/test/${route.params.id}`);
};

const cancelExit = () => {
  showExit.value = false;
  if (pendingLeave) {
    pendingLeave(false);
    pendingLeave = null;
  }
};

const confirmExit = () => {
  showExit.value = false;
  const next = pendingLeave;
  pendingLeave = null;
  next?.();
};

// ─── Envío ───────────────────────────────────────────────────────────────────
async function submitTest() {
  if (submitting.value || submitted.value) return;
  submitting.value = true;

  try {
    const { data } = await api.post('/attempts/submit', {
      test_id: route.params.id,
      answers,
      started_at: startedAt.value,
      question_order: questions.value.map((q) => q.id),
      mode: isReview.value ? 'review' : 'exam',
    });
    submitted.value = true;
    if (timer) clearInterval(timer);
    showReview.value = false;
    router.push(`/results/${data.attempt_id}`);
  } catch (err) {
    console.error(err);
    toast.error(err.response?.data?.message || 'No se pudieron enviar las respuestas. Inténtalo de nuevo.');
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.tt__mode {
  margin-right: 10px;
  vertical-align: middle;
}

.tt__state-icon {
  font-size: 2rem;
  color: var(--positive);
}

.tt__state-text {
  margin: 0;
  color: var(--text-body);
}
.tt__penalty {
  display: flex;
  gap: 8px;
  align-items: baseline;
  margin-top: 12px;
  font-size: var(--fs-small);
}

.tt {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg);
}

.tt__col {
  width: 100%;
  max-width: 760px;
  margin: 0 auto;
  padding: 0 32px;
}

/* Barra superior */
.tt__bar {
  position: sticky;
  top: 0;
  z-index: 30;
  background: var(--bg);
}

.tt__bar-inner {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 32px;
  height: 60px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.tt__exit {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 10px;
  border: 0;
  border-radius: var(--radius-control);
  background: transparent;
  color: var(--muted);
  font: inherit;
  font-size: var(--fs-small);
  font-weight: 500;
  cursor: pointer;
}

.tt__exit:hover {
  background: var(--raised);
  color: var(--text);
}

.tt__name {
  font-weight: 600;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tt__spacer {
  flex: 1;
}

.tt__count {
  font-size: var(--fs-small);
  color: var(--muted);
  white-space: nowrap;
}

.tt__timer {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 32px;
  padding: 0 12px;
  border-radius: var(--radius-pill);
  background: var(--raised);
  font-size: var(--fs-small);
  font-weight: 500;
  white-space: nowrap;
}

.tt__timer.is-warn {
  background: var(--warning-bg);
  color: var(--warning-ink);
}

.tt__send {
  height: 36px;
}

.tt__progress {
  height: 3px;
  background: var(--rule);
}

.tt__progress-bar {
  height: 100%;
  background: var(--accent);
  transition: width 0.25s ease;
}

/* Contenido */
.tt__main {
  flex: 1;
  padding-top: 48px;
  padding-bottom: 40px;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.tt__qhead {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.tt__meta {
  font-size: var(--fs-small);
  color: var(--muted);
}

.tt__q {
  font-size: 1.625rem;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: -0.01em;
  margin: 0;
  overflow-wrap: anywhere;
}

.tt__q:focus {
  outline: none;
}

.tt__q--sm {
  font-size: 1.25rem;
}

.tt__img {
  max-width: 100%;
  max-height: 360px;
  object-fit: contain;
  align-self: flex-start;
  border-radius: var(--radius-card);
}

.tt__nav {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}

.tt__nav-list {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tt__nav-n {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-control);
  border: 1px solid transparent;
  background: var(--raised);
  color: var(--muted);
  font: inherit;
  font-size: var(--fs-meta);
  font-weight: 500;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.12s, color 0.12s;
}

.tt__nav-n:hover {
  background: var(--sunk);
  color: var(--text);
}

.tt__nav-n.is-done {
  background: var(--accent-soft);
  color: var(--accent-ink);
}

.tt__nav-n.is-cur {
  border-color: var(--text);
  color: var(--text);
  font-weight: 600;
}

/* Pie fijo con la navegación */
.tt__foot {
  position: sticky;
  bottom: 0;
  background: var(--bg);
  border-top: 1px solid var(--rule);
}

.tt__foot-inner {
  height: 76px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

/* Modo todas juntas */
.tt__main--all {
  gap: 0;
}

.tt__block {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 36px 0;
  border-top: 1px solid var(--rule);
  scroll-margin-top: 80px;
}

.tt__block:first-child {
  border-top: 0;
  padding-top: 0;
}

.tt__all-end {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 24px;
  border-top: 1px solid var(--rule);
}

/* Estados y modales */
.tt__state {
  padding-top: 96px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 16px;
}

.tt__state-title {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0;
}

.tt__modal-text {
  color: var(--text-body);
  margin: 0;
}

.tt__pending {
  margin-top: 16px;
  background: var(--warning-bg);
  color: var(--warning-ink);
  border-radius: var(--radius-card);
  padding: 12px 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.tt__pending-title {
  font-size: var(--fs-small);
  font-weight: 600;
}

.tt__pending-list {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tt__pending .tt__nav-n {
  background: var(--bg);
  color: var(--text);
}

@media (max-width: 640px) {
  .tt__col,
  .tt__bar-inner {
    padding: 0 16px;
  }

  .tt__hide-sm {
    display: none;
  }

  .tt__main {
    padding-top: 28px;
  }

  .tt__q {
    font-size: 1.25rem;
  }

  .tt__nav-list {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 4px;
  }

  .tt__foot-inner .btn {
    flex: 1;
  }

  .tt__all-end {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
