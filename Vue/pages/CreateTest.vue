<template>
  <div class="page-wrap ct">
    <nav class="ct__crumbs" aria-label="Ruta">
      <router-link to="/">Explorar</router-link>
      <span aria-hidden="true">/</span>
      <template v-if="isEditMode && form.title">
        <router-link :to="`/test/${editingTestId}`">{{ form.title }}</router-link>
        <span aria-hidden="true">/</span>
      </template>
      <span aria-current="page">{{ isEditMode ? 'Editar' : 'Crear test' }}</span>
    </nav>

    <h1 class="ct__title">{{ isEditMode ? 'Editar test' : 'Crear test' }}</h1>

    <BaseLoader v-if="loadingTest" text="Cargando el test…" />

    <div v-else-if="loadError" class="ct__load-error panel" role="alert">
      <p>{{ loadError }}</p>
      <router-link to="/" class="btn btn--secondary">Volver al catálogo</router-link>
    </div>

    <form v-else class="ct__layout" novalidate @submit.prevent>
      <!-- ═══ Columna principal ═══ -->
      <div class="ct__main">
        <section class="ct__panel" aria-labelledby="h-info">
          <h2 id="h-info" class="ct__h2">Información</h2>
          <BaseInput
            v-model="form.title"
            label="Título"
            maxlength="255"
            placeholder="Por ejemplo, Fundamentos de SQL"
            :error="fieldErrors.title"
            required
          />
          <BaseInput
            v-model="form.description"
            label="Descripción"
            type="textarea"
            rows="3"
            optional
            placeholder="Qué temas cubre y para quién es"
          />
          <div class="ct__grid2">
            <BaseSelect v-model="form.category_id" label="Categoría" :options="categories" placeholder="Sin categoría" />
            <BaseSelect
              v-model="form.difficulty"
              label="Dificultad"
              :options="[
                { value: 'easy', label: 'Fácil' },
                { value: 'medium', label: 'Media' },
                { value: 'hard', label: 'Difícil' },
              ]"
            />
          </div>
        </section>

        <section class="ct__questions" aria-labelledby="h-questions">
          <div class="ct__qs-head">
            <h2 id="h-questions" class="ct__h2">Preguntas</h2>
            <span class="ct__muted tabular">{{ questions.length }} · {{ plural(totalPoints, 'punto', 'puntos') }}</span>
          </div>

          <article
            v-for="(q, qIdx) in questions"
            :id="`q-${q._key}`"
            :key="q._key"
            class="ct__q"
            :class="{ 'has-error': questionErrors[q._key] }"
            :aria-labelledby="`qt-${q._key}`"
          >
            <header class="ct__q-head">
              <h3 :id="`qt-${q._key}`" class="ct__q-title">
                Pregunta {{ qIdx + 1 }}<span class="ct__muted"> · {{ typeLabel(q.type) }}</span>
              </h3>
              <div class="ct__q-tools">
                <button type="button" class="icon-btn" :disabled="qIdx === 0" :aria-label="`Subir pregunta ${qIdx + 1}`" title="Subir" @click="moveQuestion(qIdx, -1)">
                  <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
                </button>
                <button type="button" class="icon-btn" :disabled="qIdx === questions.length - 1" :aria-label="`Bajar pregunta ${qIdx + 1}`" title="Bajar" @click="moveQuestion(qIdx, 1)">
                  <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                </button>
                <button type="button" class="icon-btn" :aria-label="`Duplicar pregunta ${qIdx + 1}`" title="Duplicar" @click="duplicateQuestion(qIdx)">
                  <i class="fa-regular fa-copy" aria-hidden="true"></i>
                </button>
                <button type="button" class="icon-btn ct__danger" :aria-label="`Eliminar pregunta ${qIdx + 1}`" title="Eliminar" @click="requestRemove(qIdx)">
                  <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                </button>
              </div>
            </header>

            <BaseInput
              v-model="q.content"
              :id="`qc-${q._key}`"
              label="Enunciado"
              type="textarea"
              rows="2"
              placeholder="Escribe la pregunta"
              :error="questionErrors[q._key]?.content"
              required
            />

            <!-- Opción múltiple -->
            <fieldset v-if="q.type === 'multiple_choice'" class="ct__opts" :aria-describedby="`qo-help-${q._key}`">
              <legend class="field-legend">Respuestas</legend>
              <p :id="`qo-help-${q._key}`" class="help-text ct__opts-help">Marca el círculo de la respuesta correcta.</p>
              <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="ct__opt" :class="{ 'is-correct': q.correctOption === oIdx }">
                <input
                  v-model="q.correctOption"
                  type="radio"
                  class="ct__radio"
                  :name="`correct_${q._key}`"
                  :value="oIdx"
                  :aria-label="`Marcar la opción ${String.fromCharCode(65 + oIdx)} como correcta`"
                />
                <label :for="`qo-${q._key}-${oIdx}`" class="sr-only">Opción {{ String.fromCharCode(65 + oIdx) }}</label>
                <input
                  :id="`qo-${q._key}-${oIdx}`"
                  v-model="opt.content"
                  type="text"
                  class="form-input ct__opt-input"
                  :placeholder="`Opción ${String.fromCharCode(65 + oIdx)}`"
                />
                <span v-if="q.correctOption === oIdx" class="pill pill--easy ct__opt-tag">Correcta</span>
                <button
                  v-if="q.options.length > 2"
                  type="button"
                  class="icon-btn"
                  :aria-label="`Quitar la opción ${String.fromCharCode(65 + oIdx)}`"
                  @click="removeOption(q, oIdx)"
                >
                  <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
              </div>
              <p v-if="questionErrors[q._key]?.options" class="error-text">{{ questionErrors[q._key].options }}</p>
              <button v-if="q.options.length < 8" type="button" class="btn btn--ghost ct__add-opt" @click="q.options.push({ content: '' })">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>Añadir opción
              </button>
            </fieldset>

            <!-- Verdadero / falso -->
            <fieldset v-else-if="q.type === 'true_false'" class="ct__opts">
              <legend class="field-legend">Respuesta correcta</legend>
              <div class="ct__tf">
                <label class="ct__tf-opt" :class="{ 'is-correct': q.correct === 'true' }">
                  <input v-model="q.correct" type="radio" class="ct__radio" :name="`tf_${q._key}`" value="true" />Verdadero
                </label>
                <label class="ct__tf-opt" :class="{ 'is-correct': q.correct === 'false' }">
                  <input v-model="q.correct" type="radio" class="ct__radio" :name="`tf_${q._key}`" value="false" />Falso
                </label>
              </div>
            </fieldset>

            <!-- Respuesta corta -->
            <BaseInput
              v-else
              v-model="q.correct_answer"
              :id="`qa-${q._key}`"
              label="Respuesta correcta"
              help-text="No distingue mayúsculas de minúsculas."
              :error="questionErrors[q._key]?.answer"
              required
            />

            <div class="ct__q-extra">
              <BaseInput
                v-model="q.explanation"
                :id="`qe-${q._key}`"
                label="Explicación"
                type="textarea"
                rows="2"
                optional
                help-text="Se muestra al corregir, si activas las explicaciones."
              />
              <BaseInput v-model.number="q.points" :id="`qp-${q._key}`" label="Puntos" type="number" min="1" class="ct__points" />
            </div>
          </article>

          <div v-if="!questions.length" class="ct__empty panel">Añade la primera pregunta para empezar.</div>

          <div class="ct__add" role="group" aria-label="Añadir pregunta">
            <span class="ct__muted">Añadir pregunta</span>
            <button type="button" class="btn btn--secondary" @click="addQuestion('multiple_choice')">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>Opción múltiple
            </button>
            <button type="button" class="btn btn--secondary" @click="addQuestion('true_false')">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>Verdadero o falso
            </button>
            <button type="button" class="btn btn--secondary" @click="addQuestion('short_answer')">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>Respuesta corta
            </button>
          </div>
        </section>
      </div>

      <!-- ═══ Ajustes ═══ -->
      <aside class="ct__side">
        <section class="ct__panel" aria-labelledby="h-settings">
          <h2 id="h-settings" class="ct__h2">Ajustes</h2>

          <BaseSelect
            v-model="form.display_mode"
            label="Presentación de las preguntas"
            :options="[
              { value: 'paged', label: 'Una por pantalla' },
              { value: 'all', label: 'Todas en una página' },
            ]"
            help-text="«Una por pantalla» funciona mejor en móvil."
          />

          <div class="ct__limit">
            <BaseInput
              v-model.number="timeMinutes"
              label="Tiempo límite (min)"
              type="number"
              min="1"
              :disabled="noTimeLimit"
            />
            <label class="ct__check"><input v-model="noTimeLimit" type="checkbox" />Sin límite</label>
          </div>

          <div class="ct__limit">
            <BaseInput
              v-model.number="attemptsCount"
              label="Intentos por persona"
              type="number"
              min="1"
              :disabled="unlimitedAttempts"
            />
            <label class="ct__check"><input v-model="unlimitedAttempts" type="checkbox" />Ilimitados</label>
          </div>

          <BaseInput
            v-model.number="form.pass_score"
            label="Nota para aprobar (%)"
            type="number"
            min="0"
            max="100"
            :error="fieldErrors.pass_score"
          />

          <div class="ct__switches">
            <label class="ct__switch"><input v-model="form.is_public" type="checkbox" /><span>Visible en el catálogo</span></label>
            <label class="ct__switch"><input v-model="form.shuffle_questions" type="checkbox" /><span>Desordenar preguntas</span></label>
            <label class="ct__switch"><input v-model="form.shuffle_answers" type="checkbox" /><span>Desordenar respuestas</span></label>
            <label class="ct__switch"><input v-model="form.show_correct_answers" type="checkbox" /><span>Mostrar respuestas correctas al terminar</span></label>
            <label class="ct__switch"><input v-model="form.show_explanations" type="checkbox" /><span>Mostrar explicaciones al terminar</span></label>
          </div>
        </section>
      </aside>

      <!-- ═══ Barra de guardado ═══ -->
      <div class="ct__bar">
        <div class="ct__bar-inner">
          <p v-if="errorSummary" class="ct__bar-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>{{ errorSummary }}
          </p>
          <span v-else class="ct__muted ct__bar-status">{{ dirty ? 'Cambios sin guardar' : (isEditMode ? 'Sin cambios' : '') }}</span>
          <div class="ct__bar-actions">
            <button type="button" class="btn btn--secondary btn--lg" :disabled="submitting" @click="submitTest('draft')">
              Guardar borrador
            </button>
            <button type="button" class="btn btn--lg" :disabled="submitting" @click="submitTest('published')">
              <i v-if="submitting && submitStatus === 'published'" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
              {{ isEditMode ? 'Guardar y publicar' : 'Publicar' }}
            </button>
          </div>
        </div>
      </div>
    </form>

    <BaseModal v-model="showRemove" title="¿Eliminar esta pregunta?" max-width="420px">
      <p class="ct__modal-text">Se perderá lo que hayas escrito en ella.</p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="showRemove = false">Cancelar</button>
        <button type="button" class="btn btn--danger" @click="removeQuestion(removeIdx)">Eliminar</button>
      </template>
    </BaseModal>

    <BaseModal v-model="showLeave" title="¿Salir sin guardar?" max-width="420px" @close="cancelLeave">
      <p class="ct__modal-text">Tienes cambios sin guardar en este test.</p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="cancelLeave">Seguir editando</button>
        <button type="button" class="btn btn--danger" @click="confirmLeave">Salir sin guardar</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute, onBeforeRouteLeave } from 'vue-router';
import api from '../api/axios';
import BaseInput from '../components/base/BaseInput.vue';
import BaseSelect from '../components/base/BaseSelect.vue';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseModal from '../components/base/BaseModal.vue';
import { plural } from '../utils/tests';
import { useToast } from '../composables/useToast';

const router = useRouter();
const route = useRoute();
const toast = useToast();

const categories = ref([]);
const submitting = ref(false);
const submitStatus = ref('published');
const loadingTest = ref(false);
const loadError = ref('');
const serverErrors = ref([]);
const fieldErrors = reactive({ title: '', pass_score: '' });
const questionErrors = ref({});
let keyCounter = 0;

const editingTestId = ref(route.params.id ? Number(route.params.id) : null);
const isEditMode = computed(() => Boolean(editingTestId.value));

const form = reactive({
  title: '',
  description: '',
  category_id: '',
  difficulty: 'medium',
  time_limit: 0,
  max_attempts: -1,
  pass_score: 70,
  is_public: true,
  shuffle_questions: false,
  shuffle_answers: false,
  show_correct_answers: true,
  show_explanations: true,
  display_mode: 'paged',
});

// Tiempo e intentos: casilla «sin límite» en lugar de valores mágicos (0 / -1)
const timeMinutes = ref(15);
const noTimeLimit = ref(true);
const attemptsCount = ref(3);
const unlimitedAttempts = ref(true);

const questions = ref([]);

const typeLabel = (t) =>
  ({ multiple_choice: 'Opción múltiple', true_false: 'Verdadero o falso', short_answer: 'Respuesta corta' }[t] ?? t);

const totalPoints = computed(() => questions.value.reduce((s, q) => s + (Number(q.points) || 0), 0));

// ─── Preguntas ───────────────────────────────────────────────────────────────
function newQuestion(type) {
  const q = { _key: ++keyCounter, type, content: '', explanation: '', points: 1 };
  if (type === 'multiple_choice') {
    q.options = [{ content: '' }, { content: '' }, { content: '' }, { content: '' }];
    q.correctOption = 0;
  } else if (type === 'true_false') {
    q.correct = 'true';
  } else {
    q.correct_answer = '';
  }
  return q;
}

async function focusQuestion(q) {
  await nextTick();
  document.getElementById(`q-${q._key}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  document.getElementById(`qc-${q._key}`)?.focus({ preventScroll: true });
}

function addQuestion(type) {
  const q = newQuestion(type);
  questions.value.push(q);
  focusQuestion(q);
}

function duplicateQuestion(idx) {
  const copy = JSON.parse(JSON.stringify(questions.value[idx]));
  copy._key = ++keyCounter;
  questions.value.splice(idx + 1, 0, copy);
  focusQuestion(copy);
}

function moveQuestion(idx, dir) {
  const to = idx + dir;
  if (to < 0 || to >= questions.value.length) return;
  const list = questions.value;
  [list[idx], list[to]] = [list[to], list[idx]];
}

function removeOption(q, oIdx) {
  q.options.splice(oIdx, 1);
  if (q.correctOption === oIdx) q.correctOption = 0;
  else if (q.correctOption > oIdx) q.correctOption -= 1;
}

const showRemove = ref(false);
const removeIdx = ref(-1);

function isBlank(q) {
  return !q.content.trim() && !q.explanation?.trim()
    && !(q.options || []).some((o) => o.content.trim()) && !q.correct_answer?.trim();
}

function requestRemove(idx) {
  // Una pregunta vacía se borra sin preguntar
  if (isBlank(questions.value[idx])) return removeQuestion(idx);
  removeIdx.value = idx;
  showRemove.value = true;
}

function removeQuestion(idx) {
  showRemove.value = false;
  if (idx >= 0) questions.value.splice(idx, 1);
}

// ─── Cambios sin guardar ─────────────────────────────────────────────────────
const dirty = ref(false);
let trackChanges = false;
let saved = false;

watch([form, questions, timeMinutes, noTimeLimit, attemptsCount, unlimitedAttempts], () => {
  if (trackChanges) dirty.value = true;
}, { deep: true });

const showLeave = ref(false);
let pendingLeave = null;

onBeforeRouteLeave((to, from, next) => {
  if (!dirty.value || saved) return next();
  pendingLeave = next;
  showLeave.value = true;
});

const cancelLeave = () => {
  showLeave.value = false;
  pendingLeave?.(false);
  pendingLeave = null;
};

const confirmLeave = () => {
  showLeave.value = false;
  const next = pendingLeave;
  pendingLeave = null;
  next?.();
};

function onBeforeUnload(e) {
  if (dirty.value && !saved) {
    e.preventDefault();
    e.returnValue = '';
  }
}

// ─── Carga ───────────────────────────────────────────────────────────────────
onMounted(async () => {
  window.addEventListener('beforeunload', onBeforeUnload);
  try {
    const { data } = await api.get('/categories');
    categories.value = data;
  } catch (err) {
    console.error(err);
  }

  if (isEditMode.value) {
    await loadTestForEdit();
  } else {
    questions.value.push(newQuestion('multiple_choice'));
  }
  await nextTick();
  trackChanges = true;
});

onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload));

async function loadTestForEdit() {
  loadingTest.value = true;
  try {
    const { data } = await api.get(`/tests/${editingTestId.value}/edit`);
    const t = data.test;

    form.title = t.title ?? '';
    form.description = t.description ?? '';
    form.category_id = t.category_id ?? t.categories?.[0]?.id ?? '';
    form.difficulty = t.difficulty ?? 'medium';
    form.pass_score = t.pass_score ?? 70;
    form.is_public = Boolean(t.is_public);
    form.shuffle_questions = Boolean(t.shuffle_questions);
    form.shuffle_answers = Boolean(t.shuffle_answers);
    form.show_correct_answers = Boolean(t.show_correct_answers);
    form.show_explanations = Boolean(t.show_explanations);
    form.display_mode = t.display_mode === 'all' ? 'all' : 'paged';

    noTimeLimit.value = !t.time_limit;
    if (t.time_limit) timeMinutes.value = Math.round(t.time_limit / 60);
    unlimitedAttempts.value = (t.max_attempts ?? -1) === -1;
    if (!unlimitedAttempts.value) attemptsCount.value = t.max_attempts;

    questions.value = (t.questions ?? []).map((q) => {
      const mapped = {
        _key: ++keyCounter,
        type: q.type,
        content: q.content ?? '',
        explanation: q.explanation ?? '',
        points: q.points ?? 1,
      };

      if (q.type === 'multiple_choice') {
        const options = (q.options ?? []).map((opt) => ({ content: opt.content ?? '' }));
        while (options.length < 2) options.push({ content: '' });
        mapped.options = options;
        const correctIdx = (q.options ?? []).findIndex((opt) => Boolean(opt.is_correct));
        mapped.correctOption = correctIdx >= 0 ? correctIdx : 0;
      } else if (q.type === 'true_false') {
        const correct = (q.options ?? []).find((opt) => Boolean(opt.is_correct));
        mapped.correct = correct?.content === 'Falso' ? 'false' : 'true';
      } else if (q.type === 'short_answer') {
        const correct = (q.options ?? []).find((opt) => Boolean(opt.is_correct));
        mapped.correct_answer = correct?.content ?? '';
      }
      return mapped;
    });

    if (!questions.value.length) questions.value.push(newQuestion('multiple_choice'));
  } catch (err) {
    if (err.response?.status === 403) loadError.value = 'No tienes permiso para editar este test.';
    else if (err.response?.status === 404) loadError.value = 'Este test no existe o se ha eliminado.';
    else loadError.value = 'No se ha podido cargar el test.';
  } finally {
    loadingTest.value = false;
  }
}

// ─── Validación ──────────────────────────────────────────────────────────────
function validate() {
  fieldErrors.title = form.title.trim() ? '' : 'Ponle un título al test.';
  const ps = Number(form.pass_score);
  fieldErrors.pass_score = Number.isFinite(ps) && ps >= 0 && ps <= 100 ? '' : 'Usa un valor entre 0 y 100.';

  const qErrors = {};
  for (const q of questions.value) {
    const e = {};
    if (!q.content.trim()) e.content = 'Escribe el enunciado.';
    if (q.type === 'multiple_choice') {
      const filled = q.options.filter((o) => o.content.trim());
      if (filled.length < 2) e.options = 'Escribe al menos dos respuestas.';
      else if (!q.options[q.correctOption]?.content.trim()) e.options = 'La respuesta marcada como correcta está vacía.';
    }
    if (q.type === 'short_answer' && !q.correct_answer?.trim()) e.answer = 'Escribe la respuesta correcta.';
    if (Object.keys(e).length) qErrors[q._key] = e;
  }
  questionErrors.value = qErrors;

  return !fieldErrors.title && !fieldErrors.pass_score && !Object.keys(qErrors).length && questions.value.length > 0;
}

const errorSummary = computed(() => {
  if (serverErrors.value.length) return serverErrors.value[0];
  const nQ = Object.keys(questionErrors.value).length;
  const parts = [];
  if (fieldErrors.title || fieldErrors.pass_score) parts.push('revisa los datos del test');
  if (nQ) parts.push(`${plural(nQ, 'pregunta necesita', 'preguntas necesitan')} cambios`);
  if (!parts.length) return '';
  const s = parts.join(' y ');
  return s.charAt(0).toUpperCase() + s.slice(1) + '.';
});

function scrollToFirstError() {
  if (fieldErrors.title) {
    document.querySelector('.ct__main .field input')?.focus();
    return;
  }
  const first = questions.value.find((q) => questionErrors.value[q._key]);
  if (first) document.getElementById(`q-${first._key}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Limpia los avisos de una pregunta en cuanto se corrige
watch(questions, () => {
  if (Object.keys(questionErrors.value).length) validate();
}, { deep: true });

// ─── Guardar ─────────────────────────────────────────────────────────────────
async function submitTest(status) {
  submitStatus.value = status;
  serverErrors.value = [];
  if (!questions.value.length) {
    serverErrors.value = ['Añade al menos una pregunta.'];
    return;
  }
  if (!validate()) {
    scrollToFirstError();
    return;
  }

  const payload = {
    ...form,
    time_limit: noTimeLimit.value ? 0 : Math.max(1, Number(timeMinutes.value) || 1),
    max_attempts: unlimitedAttempts.value ? -1 : Math.max(1, Number(attemptsCount.value) || 1),
    status,
    questions: questions.value.map((q, idx) => {
      const base = {
        type: q.type,
        content: q.content.trim(),
        explanation: q.explanation?.trim() || null,
        points: Math.max(1, Number(q.points) || 1),
        order_index: idx,
      };

      if (q.type === 'multiple_choice') {
        // Las opciones vacías se descartan, conservando cuál es la correcta
        const kept = q.options
          .map((o, i) => ({ content: o.content.trim(), is_correct: i === q.correctOption }))
          .filter((o) => o.content);
        base.options = kept.map((o, i) => ({ ...o, order_index: i }));
      } else if (q.type === 'true_false') {
        base.options = [
          { content: 'Verdadero', is_correct: q.correct === 'true', order_index: 0 },
          { content: 'Falso', is_correct: q.correct === 'false', order_index: 1 },
        ];
      } else {
        base.correct_answer = q.correct_answer.trim();
      }
      return base;
    }),
  };

  submitting.value = true;
  try {
    const { data } = isEditMode.value
      ? await api.put(`/tests/${editingTestId.value}`, payload)
      : await api.post('/tests', payload);
    saved = true;
    dirty.value = false;
    toast.success(status === 'published' ? 'Test publicado.' : 'Borrador guardado.');
    router.push(`/test/${data.test.id}`);
  } catch (err) {
    const errs = err.response?.data?.errors;
    serverErrors.value = errs ? Object.values(errs).flat() : [err.response?.data?.message || 'No se ha podido guardar el test.'];
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.ct {
  padding-top: 32px;
}

.ct__crumbs {
  display: flex;
  gap: 8px;
  font-size: var(--fs-small);
  color: var(--muted);
  flex-wrap: wrap;
}

.ct__crumbs a {
  color: var(--muted);
  text-decoration: none;
}

.ct__crumbs a:hover {
  color: var(--text);
}

.ct__crumbs [aria-current] {
  color: var(--text);
}

.ct__title {
  margin: 16px 0 32px;
}

.ct__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  gap: 32px;
  align-items: start;
}

.ct__main {
  display: flex;
  flex-direction: column;
  gap: 32px;
  min-width: 0;
}

.ct__panel {
  background: var(--raised);
  border-radius: var(--radius-panel);
  padding: 24px;
}

.ct__panel :deep(.form-input) {
  background-color: var(--bg);
}

.ct__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0 0 16px;
}

.ct__muted {
  color: var(--muted);
  font-size: var(--fs-small);
  font-weight: 400;
}

.ct__grid2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.ct__grid2 :deep(.form-group) {
  margin-bottom: 0;
}

/* Preguntas */
.ct__questions {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.ct__qs-head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
}

.ct__qs-head .ct__h2 {
  margin: 0;
}

.ct__q {
  border: 1px solid var(--border);
  border-radius: var(--radius-panel);
  padding: 20px 24px 8px;
  background: var(--bg);
  scroll-margin-top: 96px;
}

.ct__q.has-error {
  border-color: var(--negative);
}

.ct__q-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.ct__q-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.ct__q-tools {
  display: flex;
  gap: 2px;
}

.ct__q-tools .icon-btn {
  width: 36px;
  height: 36px;
  font-size: 0.9rem;
}

.ct__q-tools .icon-btn:disabled {
  opacity: 0.35;
  cursor: default;
  background: transparent;
}

.ct__danger:hover {
  color: var(--negative);
  background: var(--negative-bg);
}

.field-legend {
  font-size: var(--fs-small);
  font-weight: 500;
  padding: 0;
}

.ct__opts {
  border: 0;
  padding: 0;
  margin: 0 0 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.ct__opts-help {
  margin: -4px 0 4px;
}

.ct__opt {
  display: flex;
  align-items: center;
  gap: 10px;
}

.ct__radio {
  width: 20px;
  height: 20px;
  accent-color: var(--positive);
  flex-shrink: 0;
  cursor: pointer;
}

.ct__opt-input {
  flex: 1;
  min-width: 0;
}

.ct__opt.is-correct .ct__opt-input {
  border-color: var(--positive);
}

.ct__opt-tag {
  flex-shrink: 0;
}

.ct__add-opt {
  align-self: flex-start;
}

.ct__tf {
  display: flex;
  gap: 8px;
}

.ct__tf-opt {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  height: 44px;
  padding: 0 16px;
  border: 1px solid var(--border);
  border-radius: var(--radius-card);
  cursor: pointer;
}

.ct__tf-opt.is-correct {
  border-color: var(--positive);
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.ct__q-extra {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 96px;
  gap: 16px;
}

.ct__empty {
  padding: 32px;
  text-align: center;
  color: var(--text-body);
}

.ct__add {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.ct__add .ct__muted {
  margin-right: 4px;
}

/* Ajustes */
.ct__side {
  position: sticky;
  top: calc(var(--header-h) + 24px);
}

.ct__side :deep(.form-group) {
  margin-bottom: 16px;
}

.ct__limit {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 12px;
  align-items: end;
}

.ct__limit .ct__check {
  margin-bottom: 26px;
}

.ct__check {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--fs-small);
  cursor: pointer;
  white-space: nowrap;
}

.ct__check input {
  width: 18px;
  height: 18px;
  accent-color: var(--accent);
}

.ct__switches {
  display: flex;
  flex-direction: column;
  border-top: 1px solid var(--border);
  margin-top: 4px;
}

.ct__switch {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid var(--border);
  font-size: var(--fs-small);
  cursor: pointer;
}

.ct__switch:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

/* Interruptor accesible a partir de un checkbox real */
.ct__switch input {
  order: 2;
  appearance: none;
  width: 36px;
  height: 20px;
  border-radius: var(--radius-pill);
  background: var(--control);
  position: relative;
  flex-shrink: 0;
  cursor: pointer;
  transition: background 0.15s;
}

.ct__switch input::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  transition: transform 0.15s;
}

.ct__switch input:checked {
  background: var(--accent);
}

.ct__switch input:checked::after {
  transform: translateX(16px);
}

/* Barra de guardado fija */
.ct__bar {
  grid-column: 1 / -1;
  position: sticky;
  bottom: 0;
  z-index: 20;
  margin: 0 calc(var(--gutter) * -1);
  background: var(--bg);
  border-top: 1px solid var(--rule);
}

.ct__bar-inner {
  min-height: 72px;
  padding: 12px var(--gutter);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.ct__bar-actions {
  display: flex;
  gap: 8px;
}

.ct__bar-error {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--negative);
  font-size: var(--fs-small);
  font-weight: 500;
  margin: 0;
}

.ct__load-error {
  padding: 40px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.ct__load-error p {
  margin: 0;
  font-weight: 600;
}

.ct__modal-text {
  color: var(--text-body);
  margin: 0;
}

@media (max-width: 960px) {
  .ct__layout {
    grid-template-columns: minmax(0, 1fr);
  }

  .ct__side {
    position: static;
  }
}

@media (max-width: 640px) {
  .ct__grid2,
  .ct__q-extra {
    grid-template-columns: minmax(0, 1fr);
  }

  .ct__q {
    padding: 16px 16px 4px;
  }

  .ct__q-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .ct__opt-tag {
    display: none;
  }

  .ct__bar-actions {
    width: 100%;
  }

  .ct__bar-actions .btn {
    flex: 1;
  }
}
</style>
