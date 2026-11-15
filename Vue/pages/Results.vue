<template>
  <div class="page-wrap rs">
    <BaseLoader v-if="loading" text="Cargando resultados…" size="large" />

    <div v-else-if="!attempt" class="rs__error panel" role="alert">
      <p class="rs__error-title">No se han podido cargar los resultados</p>
      <router-link to="/" class="btn btn--secondary">Volver al catálogo</router-link>
    </div>

    <template v-else>
      <nav class="rs__crumbs" aria-label="Ruta">
        <router-link to="/">Explorar</router-link>
        <span aria-hidden="true">/</span>
        <router-link :to="`/test/${attempt.test_id}`">{{ attempt.test?.title }}</router-link>
        <span aria-hidden="true">/</span>
        <span aria-current="page">{{ comparison?.attempt_number ? `Intento ${comparison.attempt_number}` : 'Resultado' }}</span>
      </nav>

      <div class="rs__top">
        <!-- Nota y acciones -->
        <section class="rs__score panel" aria-labelledby="rs-title">
          <div class="rs__ring" role="img" :aria-label="`Nota ${score} por ciento`" :style="{ '--pct': score }">
            <div class="rs__ring-inner">
              <span class="rs__ring-num tabular">{{ score }} %</span>
              <span class="rs__ring-sub">se aprueba con {{ passScore }} %</span>
            </div>
          </div>
          <div class="rs__summary">
            <h1 id="rs-title" class="rs__title">{{ attempt.passed ? 'Aprobado' : 'No aprobado' }}</h1>
            <p class="rs__line">
              {{ attempt.correct_answers }} de {{ totalQuestions }} correctas en {{ formatDuration(attempt.time_spent) }}.
              <template v-if="progressLine">{{ progressLine }}</template>
            </p>
            <p v-if="showWeightedPoints" class="rs__line">{{ attempt.points_earned }} de {{ attempt.total_points }} puntos ponderados.</p>
            <div class="rs__actions">
              <router-link v-if="canRetry" :to="`/test/${attempt.test_id}/take`" class="btn btn--lg">Reintentar</router-link>
              <router-link :to="`/test/${attempt.test_id}`" class="btn btn--secondary btn--lg">Volver al test</router-link>
              <router-link to="/" class="btn btn--ghost btn--lg">Explorar más tests</router-link>
            </div>
            <p v-if="attemptsNote" class="rs__note">{{ attemptsNote }}</p>
          </div>
        </section>

        <!-- Comparativa -->
        <section v-if="bars.length" class="rs__cmp panel" aria-labelledby="rs-cmp">
          <h2 id="rs-cmp" class="rs__h3">Comparativa</h2>
          <div v-for="b in bars" :key="b.label" class="rs__bar">
            <div class="rs__bar-head">
              <span :class="{ 'is-strong': b.mine }">{{ b.label }}</span>
              <span class="tabular" :class="{ 'is-strong': b.mine }">{{ b.value }} %</span>
            </div>
            <div class="rs__bar-track">
              <div class="rs__bar-fill" :class="{ 'is-mine': b.mine }" :style="{ width: `${b.value}%` }"></div>
              <div class="rs__bar-pass" :style="{ left: `${passScore}%` }" aria-hidden="true"></div>
            </div>
          </div>
          <p class="rs__cmp-note">
            La línea marca la nota para aprobar, {{ passScore }} %.
            <template v-if="comparison?.test_attempts">Media calculada sobre {{ plural(comparison.test_attempts, 'intento', 'intentos') }}.</template>
          </p>
        </section>
      </div>

      <dl class="rs__strip" aria-label="Resumen">
        <div><dt>Correctas</dt><dd class="tabular">{{ attempt.correct_answers }}</dd></div>
        <div><dt>Incorrectas</dt><dd class="tabular" :class="{ 'is-neg': attempt.incorrect_answers > 0 }">{{ attempt.incorrect_answers }}</dd></div>
        <div><dt>Sin responder</dt><dd class="tabular">{{ attempt.unanswered }}</dd></div>
        <div>
          <dt>Tiempo</dt>
          <dd class="tabular">
            {{ formatClock(attempt.time_spent) }}
            <span v-if="attempt.test?.time_limit" class="rs__of">de {{ formatClock(attempt.test.time_limit) }}</span>
          </dd>
        </div>
      </dl>

      <!-- Detalle de respuestas -->
      <section class="rs__detail" aria-labelledby="rs-detail">
        <div class="rs__detail-head">
          <h2 id="rs-detail" class="rs__h2">Detalle de respuestas</h2>
          <div class="rs__tabs" role="tablist" aria-label="Filtrar respuestas">
            <button
              v-for="t in tabs"
              :key="t.id"
              type="button"
              role="tab"
              class="rs__tab"
              :aria-selected="String(tab === t.id)"
              :disabled="t.count === 0 && t.id !== 'all'"
              @click="tab = t.id"
            >
              {{ t.label }}<span class="rs__tab-count tabular">{{ t.count }}</span>
            </button>
          </div>
        </div>

        <p v-if="!attempt.test?.show_correct_answers" class="rs__info">
          El autor de este test ha decidido no mostrar las respuestas correctas.
        </p>

        <div role="tabpanel">
          <article v-for="r in visibleRows" :key="r.id" class="rs__row">
            <span class="rs__icon" :class="`is-${r.status}`" aria-hidden="true">
              <i :class="statusIcon[r.status]"></i>
            </span>
            <div class="rs__row-body">
              <div>
                <span class="rs__meta">Pregunta {{ r.n }} · {{ statusLabel[r.status] }}</span>
                <h3 class="rs__q">{{ r.question_text }}</h3>
              </div>

              <span v-if="r.status === 'ok'" class="rs__yours">Tu respuesta: {{ r.given_answer_label }}</span>

              <template v-else>
                <div class="rs__answers">
                  <div class="rs__ans" :class="r.status === 'bad' ? 'is-bad' : 'is-none'">
                    <span>Tu respuesta</span>
                    <strong>{{ r.status === 'none' ? 'Sin responder' : r.given_answer_label }}</strong>
                  </div>
                  <div v-if="attempt.test?.show_correct_answers && r.correct_answer_label" class="rs__ans is-ok">
                    <span>Respuesta correcta</span>
                    <strong>{{ r.correct_answer_label }}</strong>
                  </div>
                </div>
              </template>

              <p v-if="attempt.test?.show_explanations && r.explanation" class="rs__expl">{{ r.explanation }}</p>
            </div>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import { formatClock, formatDuration, plural } from '../utils/tests';

const route = useRoute();
const loading = ref(true);
const attempt = ref(null);
const responses = ref([]);
const comparison = ref(null);
const tab = ref('all');

const score = computed(() => Math.round(Number(attempt.value?.score ?? 0)));
const passScore = computed(() => Number(attempt.value?.test?.pass_score ?? 50));

const totalQuestions = computed(() => {
  if (!attempt.value) return 0;
  return Number(attempt.value.correct_answers || 0)
    + Number(attempt.value.incorrect_answers || 0)
    + Number(attempt.value.unanswered || 0);
});

const showWeightedPoints = computed(
  () => attempt.value && Number(attempt.value.total_points || 0) !== totalQuestions.value
);

// ─── Comparativa ─────────────────────────────────────────────────────────────
const progressLine = computed(() => {
  const prev = comparison.value?.previous_best;
  if (prev == null) return '';
  const diff = score.value - Math.round(prev);
  if (diff > 0) return `Has mejorado ${plural(diff, 'punto', 'puntos')} respecto a tu mejor intento anterior.`;
  if (diff < 0) return `${plural(-diff, 'punto', 'puntos')} por debajo de tu mejor intento anterior.`;
  return 'Igualas tu mejor intento anterior.';
});

const bars = computed(() => {
  const c = comparison.value;
  if (!c) return [];
  const list = [{ label: 'Tu nota', value: score.value, mine: true }];
  if (c.test_average != null) list.push({ label: 'Media del test', value: Math.round(c.test_average) });
  if (c.previous_best != null) list.push({ label: 'Tu mejor intento anterior', value: Math.round(c.previous_best) });
  return list.length > 1 ? list : [];
});

const canRetry = computed(() => {
  const remaining = comparison.value?.attempts_remaining;
  return remaining === null || remaining === undefined || remaining > 0;
});

const attemptsNote = computed(() => {
  const c = comparison.value;
  if (!c || c.attempts_remaining == null) return '';
  const max = Number(attempt.value?.test?.max_attempts);
  if (c.attempts_remaining === 0) return `Has usado los ${max} intentos de este test.`;
  return `Te ${c.attempts_remaining === 1 ? 'queda' : 'quedan'} ${c.attempts_remaining} de ${max} intentos.`;
});

// ─── Respuestas ──────────────────────────────────────────────────────────────
const rows = computed(() =>
  responses.value.map((r, i) => ({
    ...r,
    n: i + 1,
    status: r.is_correct ? 'ok' : r.answered ? 'bad' : 'none',
  }))
);

const tabs = computed(() => [
  { id: 'all', label: 'Todas', count: rows.value.length },
  { id: 'bad', label: 'Incorrectas', count: rows.value.filter((r) => r.status === 'bad').length },
  { id: 'none', label: 'Sin responder', count: rows.value.filter((r) => r.status === 'none').length },
]);

const visibleRows = computed(() => (tab.value === 'all' ? rows.value : rows.value.filter((r) => r.status === tab.value)));

const statusLabel = { ok: 'Correcta', bad: 'Incorrecta', none: 'Sin responder' };
const statusIcon = { ok: 'fa-solid fa-check', bad: 'fa-solid fa-xmark', none: 'fa-solid fa-minus' };

onMounted(async () => {
  try {
    const { data } = await api.get(`/attempts/${route.params.attemptId}/results`);
    attempt.value = data.attempt;
    responses.value = data.responses;
    comparison.value = data.comparison ?? null;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.rs {
  padding-top: 32px;
  max-width: 1100px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.rs__crumbs {
  display: flex;
  gap: 8px;
  font-size: var(--fs-small);
  color: var(--muted);
  min-width: 0;
  flex-wrap: wrap;
}

.rs__crumbs a {
  color: var(--muted);
  text-decoration: none;
}

.rs__crumbs a:hover {
  color: var(--text);
}

.rs__crumbs [aria-current] {
  color: var(--text);
}

.rs__top {
  display: grid;
  grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
  gap: 24px;
}

/* Nota */
.rs__score {
  padding: 28px;
  display: flex;
  gap: 28px;
  align-items: center;
  flex-wrap: wrap;
}

.rs__ring {
  width: 148px;
  height: 148px;
  border-radius: 50%;
  background: conic-gradient(var(--accent) calc(var(--pct) * 1%), var(--border) 0);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.rs__ring-inner {
  width: 122px;
  height: 122px;
  border-radius: 50%;
  background: var(--raised);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.rs__ring-num {
  font-size: 2.125rem;
  font-weight: 600;
  line-height: 1;
}

.rs__ring-sub {
  font-size: 0.75rem;
  color: var(--muted);
  margin-top: 4px;
  max-width: 96px;
}

.rs__summary {
  flex: 1;
  min-width: 240px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.rs__title {
  font-size: var(--fs-title);
  margin: 0;
}

.rs__line {
  color: var(--text-body);
  margin: 0;
}

.rs__actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 14px;
}

.rs__note {
  font-size: var(--fs-meta);
  color: var(--muted);
  margin: 0;
}

/* Comparativa */
.rs__cmp {
  padding: 24px 28px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.rs__h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.rs__bar {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.rs__bar-head {
  display: flex;
  justify-content: space-between;
  font-size: var(--fs-small);
  color: var(--text-body);
}

.rs__bar-head .is-strong {
  color: var(--text);
  font-weight: 600;
}

.rs__bar-track {
  position: relative;
  height: 8px;
  border-radius: var(--radius-pill);
  background: var(--border);
}

.rs__bar-fill {
  height: 100%;
  border-radius: inherit;
  background: var(--chart-muted);
}

.rs__bar-fill.is-mine {
  background: var(--accent);
}

.rs__bar-pass {
  position: absolute;
  top: -4px;
  bottom: -4px;
  width: 2px;
  margin-left: -1px;
  background: var(--text);
}

.rs__cmp-note {
  font-size: var(--fs-meta);
  color: var(--muted);
  margin: 0;
}

/* Resumen */
.rs__strip {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  background: var(--raised);
  border-radius: var(--radius-panel);
  margin: 0;
}

.rs__strip > div {
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  border-left: 1px solid var(--border);
}

.rs__strip > div:first-child {
  border-left: 0;
}

.rs__strip dt {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.rs__strip dd {
  margin: 0;
  font-size: 1.5rem;
}

.rs__strip dd.is-neg {
  color: var(--negative);
}

.rs__of {
  font-size: var(--fs-small);
  color: var(--muted);
}

/* Detalle */
.rs__detail {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.rs__detail-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.rs__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0;
}

.rs__tabs {
  display: flex;
  gap: 4px;
  background: var(--raised);
  padding: 4px;
  border-radius: var(--radius-card);
}

.rs__tab {
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
}

.rs__tab:hover:not(:disabled) {
  color: var(--text);
}

.rs__tab:disabled {
  opacity: 0.5;
  cursor: default;
}

.rs__tab[aria-selected='true'] {
  background: var(--bg);
  color: var(--text);
  font-weight: 600;
}

.rs__tab-count {
  font-size: 0.75rem;
  color: var(--muted);
}

.rs__info {
  font-size: var(--fs-small);
  background: var(--info-bg);
  color: var(--info-ink);
  padding: 12px 14px;
  border-radius: var(--radius-card);
  margin: 0;
}

.rs__row {
  display: grid;
  grid-template-columns: 28px minmax(0, 1fr);
  gap: 14px;
  padding: 20px 0;
  border-top: 1px solid var(--rule);
}

.rs__icon {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 0.75rem;
}

.rs__icon.is-ok {
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.rs__icon.is-bad {
  background: var(--negative-bg);
  color: var(--negative-ink);
}

.rs__icon.is-none {
  background: var(--sunk);
  color: var(--muted);
}

.rs__row-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.rs__meta {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.rs__q {
  font-size: 1rem;
  font-weight: 500;
  line-height: 1.4;
  margin: 2px 0 0;
  overflow-wrap: anywhere;
}

.rs__yours {
  font-size: var(--fs-small);
  color: var(--text-body);
}

.rs__answers {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.rs__ans {
  border-radius: var(--radius-card);
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.rs__ans span {
  font-size: 0.75rem;
}

.rs__ans strong {
  font-weight: 500;
  overflow-wrap: anywhere;
}

.rs__ans.is-bad {
  background: var(--negative-bg);
  color: var(--negative-ink);
}

.rs__ans.is-none {
  background: var(--sunk);
  color: var(--muted);
}

.rs__ans.is-ok {
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.rs__expl {
  font-size: var(--fs-small);
  color: var(--text-body);
  max-width: 720px;
  margin: 0;
}

.rs__error {
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  margin-top: 32px;
}

.rs__error-title {
  font-weight: 600;
  margin: 0;
}

@media (max-width: 860px) {
  .rs__top {
    grid-template-columns: minmax(0, 1fr);
  }

  .rs__strip {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .rs__strip > div:nth-child(3) {
    border-left: 0;
  }

  .rs__strip > div:nth-child(n + 3) {
    border-top: 1px solid var(--border);
  }
}

@media (max-width: 640px) {
  .rs__answers {
    grid-template-columns: minmax(0, 1fr);
  }

  .rs__actions .btn {
    flex: 1;
  }

  .rs__tabs {
    width: 100%;
    overflow-x: auto;
  }
}
</style>
