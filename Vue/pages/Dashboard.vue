<template>
  <div class="page-wrap db">
    <header class="db__head">
      <h1 class="db__title">Mi progreso</h1>
      <p class="db__sub">Solo tú ves esta página.</p>
    </header>

    <BaseLoader v-if="loading" text="Cargando tu progreso…" size="large" />

    <div v-else-if="error" class="db__state panel" role="alert">
      <p class="db__state-title">{{ error }}</p>
      <button type="button" class="btn btn--secondary" @click="load">Reintentar</button>
    </div>

    <!-- Aún no ha hecho ningún test -->
    <div v-else-if="!hasActivity" class="db__state panel">
      <i class="fa-solid fa-seedling db__state-icon" aria-hidden="true"></i>
      <p class="db__state-title">Aún no has hecho ningún test</p>
      <p class="db__state-text">Cuando termines tu primer test verás aquí tu racha, tu nota media y en qué temas flojeas.</p>
      <router-link to="/" class="btn">Explorar tests</router-link>
    </div>

    <template v-else>
      <!-- ═══ Racha y resumen ═══ -->
      <section class="db__top" aria-label="Resumen">
        <div class="db__streak panel" :class="{ 'is-on': activity.current_streak > 0 }">
          <i class="fa-solid fa-fire db__streak-icon" aria-hidden="true"></i>
          <div>
            <p class="db__streak-num tabular">{{ plural(activity.current_streak, 'día', 'días') }}</p>
            <p class="db__streak-label">de racha</p>
          </div>
          <p class="db__streak-note">
            <template v-if="activity.studied_today">Hoy ya has estudiado.</template>
            <template v-else-if="activity.current_streak > 0">Estudia hoy para no perderla.</template>
            <template v-else>Haz un test hoy para empezar una racha.</template>
            <span v-if="activity.longest_streak > 1"> Récord: {{ plural(activity.longest_streak, 'día', 'días') }}.</span>
          </p>
        </div>

        <dl class="db__stats">
          <div class="db__stat panel">
            <dt>Nota media</dt>
            <dd class="tabular">{{ summary.average_score != null ? `${Math.round(summary.average_score)} %` : '—' }}</dd>
          </div>
          <div class="db__stat panel">
            <dt>Exámenes</dt>
            <dd class="tabular">{{ summary.exams_completed }}</dd>
            <span class="db__stat-sub">{{ summary.exams_passed }} aprobados</span>
          </div>
          <div class="db__stat panel">
            <dt>Repasos</dt>
            <dd class="tabular">{{ summary.reviews_completed }}</dd>
          </div>
          <div class="db__stat panel">
            <dt>Tiempo de estudio</dt>
            <dd class="tabular">{{ studyTime }}</dd>
          </div>
        </dl>
      </section>

      <div class="db__grid">
        <!-- ═══ Fallos pendientes ═══ -->
        <section v-if="pending.length" class="db__panel panel" aria-labelledby="db-pending">
          <h2 id="db-pending" class="db__h2">Fallos pendientes</h2>
          <p class="db__hint">Preguntas cuya última respuesta no fue correcta. Repasarlas no gasta intentos.</p>
          <ul class="db__pending">
            <li v-for="p in pending" :key="p.test_id">
              <router-link :to="`/test/${p.test_id}`" class="db__pending-title">{{ p.title }}</router-link>
              <span class="db__muted tabular">{{ plural(p.pending, 'pregunta', 'preguntas') }}</span>
              <router-link :to="`/test/${p.test_id}/review`" class="btn btn--secondary db__btn-sm">
                <i class="fa-solid fa-rotate" aria-hidden="true"></i>Repasar
              </router-link>
            </li>
          </ul>
        </section>

        <!-- ═══ Dominio por categoría ═══ -->
        <section v-if="mastery.length" class="db__panel panel" aria-labelledby="db-mastery">
          <h2 id="db-mastery" class="db__h2">Dominio por tema</h2>
          <p class="db__hint">Según tu última respuesta a cada pregunta. Los temas más flojos, arriba.</p>
          <ul class="db__mastery">
            <li v-for="m in mastery" :key="m.category_id ?? 'none'" class="db__meter">
              <div class="db__meter-head">
                <span class="db__meter-name">
                  <i v-if="m.icon" :class="m.icon" aria-hidden="true"></i>{{ m.name }}
                </span>
                <span class="tabular db__meter-value">{{ Math.round(m.mastery) }} %</span>
              </div>
              <div
                class="db__meter-track"
                role="meter"
                :aria-label="`Dominio de ${m.name}`"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="Math.round(m.mastery)"
                :title="`${m.mastered} de ${m.questions} preguntas dominadas`"
              >
                <div class="db__meter-fill" :style="{ width: `${m.mastery}%` }"></div>
              </div>
              <span class="db__muted db__meter-sub">{{ m.mastered }} de {{ plural(m.questions, 'pregunta dominada', 'preguntas dominadas') }}</span>
            </li>
          </ul>
        </section>
      </div>

      <!-- ═══ Actividad ═══ -->
      <section class="db__panel panel" aria-labelledby="db-activity">
        <div class="db__panel-head">
          <h2 id="db-activity" class="db__h2">Actividad</h2>
          <span class="db__muted">Últimos 6 meses · exámenes y repasos</span>
        </div>
        <ActivityHeatmap :days="activity.days" :today="activity.today" />
      </section>

      <!-- ═══ Historial ═══ -->
      <section class="db__panel panel" aria-labelledby="db-history">
        <div class="db__panel-head">
          <h2 id="db-history" class="db__h2">Historial de intentos</h2>
          <div class="db__tabs" role="group" aria-label="Filtrar historial">
            <button
              v-for="f in filters"
              :key="f.id"
              type="button"
              class="db__tab"
              :aria-pressed="String(historyMode === f.id)"
              @click="setMode(f.id)"
            >{{ f.label }}</button>
          </div>
        </div>

        <BaseLoader v-if="historyLoading && !history.length" text="Cargando historial…" />
        <p v-else-if="!history.length" class="db__muted db__empty">No hay intentos con este filtro.</p>

        <ul v-else class="db__history" :class="{ 'is-loading': historyLoading }">
          <li v-for="a in history" :key="a.id">
            <router-link :to="`/results/${a.id}`" class="db__row">
              <span class="db__row-main">
                <span class="db__row-title">{{ a.test_title ?? 'Test eliminado' }}</span>
                <span class="db__muted">
                  <span v-if="a.mode === 'review'" class="pill pill--medium db__pill">Repaso</span>
                  {{ formatDate(a.completed_at) }} · {{ a.correct_answers }}/{{ a.total_questions }} correctas · {{ formatDuration(a.time_spent) }}
                </span>
              </span>
              <span class="db__row-score tabular" :class="a.mode === 'exam' ? (a.passed ? 'is-pass' : 'is-fail') : ''">
                {{ Math.round(a.score) }} %
                <span v-if="a.mode === 'exam'" class="sr-only">{{ a.passed ? 'aprobado' : 'no aprobado' }}</span>
              </span>
            </router-link>
          </li>
        </ul>

        <nav v-if="historyMeta.last_page > 1" class="db__pager" aria-label="Páginas del historial">
          <button type="button" class="btn btn--ghost" :disabled="historyMeta.current_page <= 1 || historyLoading" @click="loadHistory(historyMeta.current_page - 1)">
            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Anteriores
          </button>
          <span class="db__muted tabular">Página {{ historyMeta.current_page }} de {{ historyMeta.last_page }}</span>
          <button type="button" class="btn btn--ghost" :disabled="historyMeta.current_page >= historyMeta.last_page || historyLoading" @click="loadHistory(historyMeta.current_page + 1)">
            Siguientes<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
          </button>
        </nav>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../api/axios';
import BaseLoader from '../components/base/BaseLoader.vue';
import ActivityHeatmap from '../components/charts/ActivityHeatmap.vue';
import { formatDuration, plural } from '../utils/tests';

const loading = ref(true);
const error = ref('');
const summary = ref({});
const activity = ref({ days: {}, today: '', current_streak: 0, longest_streak: 0, studied_today: false, active_days: 0 });
const mastery = ref([]);
const pending = ref([]);

const hasActivity = computed(() => activity.value.active_days > 0);

const studyTime = computed(() => {
  const s = Number(summary.value.study_time) || 0;
  const h = Math.floor(s / 3600);
  const m = Math.round((s % 3600) / 60);
  if (h) return `${h} h ${m} min`;
  return `${m} min`;
});

const formatDate = (iso) =>
  new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });

async function load() {
  loading.value = true;
  error.value = '';
  try {
    // La racha y el calendario se cuentan en la zona horaria del navegador
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const { data } = await api.get('/dashboard', { params: { tz } });
    summary.value = data.summary;
    activity.value = data.activity;
    mastery.value = data.mastery;
    pending.value = data.pending_reviews;
    if (hasActivity.value) loadHistory(1);
  } catch (err) {
    console.error(err);
    error.value = 'No se ha podido cargar tu progreso.';
  } finally {
    loading.value = false;
  }
}

// ─── Historial ───────────────────────────────────────────────────────────────
const filters = [
  { id: '', label: 'Todos' },
  { id: 'exam', label: 'Exámenes' },
  { id: 'review', label: 'Repasos' },
];
const historyMode = ref('');
const history = ref([]);
const historyMeta = ref({ current_page: 1, last_page: 1, total: 0 });
const historyLoading = ref(false);

async function loadHistory(page) {
  historyLoading.value = true;
  try {
    const { data } = await api.get('/dashboard/attempts', {
      params: { page, ...(historyMode.value ? { mode: historyMode.value } : {}) },
    });
    history.value = data.data;
    historyMeta.value = data.meta;
  } catch (err) {
    console.error(err);
  } finally {
    historyLoading.value = false;
  }
}

function setMode(mode) {
  if (historyMode.value === mode) return;
  historyMode.value = mode;
  loadHistory(1);
}

onMounted(load);
</script>

<style scoped>
.db {
  padding-top: 32px;
  padding-bottom: 64px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.db__head {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.db__title {
  margin: 0;
}

.db__sub,
.db__muted,
.db__hint {
  color: var(--muted);
  font-size: var(--fs-small);
  margin: 0;
}

.db__hint {
  margin: -8px 0 16px;
}

.db__state {
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 12px;
}

.db__state-icon {
  font-size: 2rem;
  color: var(--positive);
}

.db__state-title {
  font-weight: 600;
  margin: 0;
}

.db__state-text {
  color: var(--text-body);
  margin: 0 0 8px;
  max-width: 420px;
}

/* Racha + cifras */
.db__top {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  gap: 16px;
}

.db__streak {
  padding: 20px 24px;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  align-items: center;
  gap: 4px 16px;
}

.db__streak-icon {
  font-size: 2rem;
  color: var(--muted);
}

.db__streak.is-on .db__streak-icon {
  color: var(--warning);
}

.db__streak-num {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1.1;
  margin: 0;
}

.db__streak-label {
  margin: 0;
  color: var(--muted);
  font-size: var(--fs-small);
}

.db__streak-note {
  grid-column: 1 / -1;
  margin: 8px 0 0;
  font-size: var(--fs-small);
  color: var(--text-body);
}

.db__stats {
  margin: 0;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.db__stat {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.db__stat dt {
  font-size: var(--fs-small);
  color: var(--muted);
}

.db__stat dd {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.db__stat-sub {
  font-size: var(--fs-meta);
  color: var(--muted);
}

/* Paneles */
.db__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 380px), 1fr));
  gap: 24px;
}

.db__panel {
  padding: 24px;
  min-width: 0;
}

.db__panel-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.db__panel-head .db__h2 {
  margin: 0;
}

.db__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0 0 16px;
}

/* Fallos pendientes */
.db__pending {
  list-style: none;
  margin: 0;
  padding: 0;
}

.db__pending li {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
  border-top: 1px solid var(--border);
}

.db__pending-title {
  color: var(--text);
  font-weight: 500;
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.db__pending-title:hover {
  text-decoration: underline;
}

.db__btn-sm {
  height: 36px;
  padding: 0 12px;
  font-size: var(--fs-small);
}

/* Dominio: medidor con la pista en un paso más claro del mismo tono */
.db__mastery {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.db__meter-head {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
  font-size: var(--fs-small);
}

.db__meter-name {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  font-weight: 500;
}

.db__meter-name i {
  color: var(--muted);
}

.db__meter-value {
  font-weight: 600;
}

.db__meter-track {
  height: 10px;
  border-radius: 4px;
  background: var(--accent-soft);
  overflow: hidden;
}

.db__meter-fill {
  height: 100%;
  border-radius: 0 4px 4px 0;
  background: var(--chart-1);
  transition: width 0.4s ease;
}

.db__meter-sub {
  display: block;
  margin-top: 4px;
  font-size: var(--fs-meta);
}

/* Historial */
.db__tabs {
  display: flex;
  gap: 4px;
  padding: 3px;
  border-radius: var(--radius-control);
  background: var(--sunk);
}

.db__tab {
  border: 0;
  background: transparent;
  color: var(--text-body);
  font: inherit;
  font-size: var(--fs-small);
  padding: 6px 12px;
  border-radius: var(--radius-control);
  cursor: pointer;
}

.db__tab[aria-pressed='true'] {
  background: var(--bg);
  color: var(--text);
  font-weight: 600;
}

.db__history {
  list-style: none;
  margin: 0;
  padding: 0;
  transition: opacity 0.15s;
}

.db__history.is-loading {
  opacity: 0.5;
}

.db__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 12px 8px;
  margin: 0 -8px;
  border-top: 1px solid var(--border);
  border-radius: var(--radius-control);
  color: inherit;
  text-decoration: none;
}

.db__history li:first-child .db__row {
  border-top: 0;
}

.db__row:hover {
  background: var(--sunk);
}

.db__row-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.db__row-title {
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.db__pill {
  margin-right: 6px;
}

.db__row-score {
  font-weight: 600;
  flex-shrink: 0;
}

.db__row-score.is-pass {
  color: var(--positive-ink);
}

.db__row-score.is-fail {
  color: var(--negative-ink);
}

.db__empty {
  padding: 16px 0;
}

.db__pager {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
}

@media (max-width: 900px) {
  .db__top {
    grid-template-columns: minmax(0, 1fr);
  }

  .db__stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 480px) {
  .db__pending li {
    grid-template-columns: minmax(0, 1fr) auto;
  }

  .db__pending li .db__muted {
    grid-row: 2;
  }

  .db__pager .btn {
    padding-inline: 8px;
  }
}
</style>
