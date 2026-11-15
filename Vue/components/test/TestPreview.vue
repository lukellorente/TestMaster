<template>
  <BaseModal :model-value="modelValue" title="Vista previa" max-width="760px" @update:model-value="$emit('update:modelValue', $event)">
    <div class="pv">
      <header class="pv__head">
        <h2 class="pv__title">{{ form.title?.trim() || 'Test sin título' }}</h2>
        <p v-if="form.description?.trim()" class="pv__desc">{{ form.description }}</p>
        <p class="pv__meta tabular">{{ metaLine }}</p>
      </header>

      <div class="pv__notice">
        <i class="fa-regular fa-eye" aria-hidden="true"></i>
        <p>
          Así verá el test el alumno. Lo que respondas aquí no se guarda.
          <template v-if="form.shuffle_questions || form.shuffle_answers">
            El orden de {{ shuffleWhat }} cambiará en cada intento.
          </template>
        </p>
      </div>

      <label class="pv__toggle">
        <input v-model="showSolutions" type="checkbox" />
        Mostrar las respuestas correctas
      </label>

      <p v-if="!previewQuestions.length" class="pv__empty">Aún no hay preguntas con enunciado.</p>

      <ol class="pv__list">
        <li v-for="(q, i) in previewQuestions" :key="q.id" class="pv__q">
          <span class="pv__q-meta">
            Pregunta {{ i + 1 }} de {{ previewQuestions.length }} · {{ questionTypeLabel(q.type) }} · {{ plural(q.points, 'punto', 'puntos') }}
          </span>
          <p class="pv__q-text">{{ q.type === 'fill_blank' ? numberBlanks(q.content) : q.content }}</p>
          <AnswerInput v-model="answers[q.id]" :question="q" :number="i + 1" />
          <p v-if="showSolutions" class="pv__solution">
            <i class="fa-solid fa-check" aria-hidden="true"></i>
            <span><strong>Correcta:</strong> {{ q.solution || '—' }}</span>
          </p>
        </li>
      </ol>
    </div>

    <template #footer>
      <button type="button" class="btn btn--secondary" @click="resetAnswers">Borrar respuestas</button>
      <button type="button" class="btn" @click="$emit('update:modelValue', false)">Volver al editor</button>
    </template>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import BaseModal from '../base/BaseModal.vue';
import AnswerInput from '../question/AnswerInput.vue';
import { numberBlanks, penaltyLabel, plural, questionTypeLabel } from '../../utils/tests';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  // Datos del editor tal cual (CreateTest): form + preguntas en formato de edición
  form: { type: Object, required: true },
  questions: { type: Array, required: true },
  timeLimitMinutes: { type: Number, default: 0 },
});

defineEmits(['update:modelValue']);

const answers = reactive({});
const showSolutions = ref(false);
const order = ref([]); // orden barajado, fijo mientras la vista previa está abierta

const shuffle = (list) => {
  const a = [...list];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
};

const filled = (list) => (list || []).map((x) => (typeof x === 'string' ? x : x.content ?? '').trim()).filter(Boolean);

/** Respuesta correcta en texto, a partir de los datos del editor */
function solutionOf(q) {
  switch (q.type) {
    case 'multiple_choice':
      return q.options[q.correctOption]?.content?.trim() ?? '';
    case 'multiple_select':
      return q.options.filter((o) => o.correct && o.content.trim()).map((o) => o.content.trim()).join(', ');
    case 'true_false':
      return q.correct === 'false' ? 'Falso' : 'Verdadero';
    case 'fill_blank':
      return q.blanks.map((b, i) => `(${i + 1}) ${String(b).split('|').map((a) => a.trim()).filter(Boolean).join(' / ') || '—'}`).join(' · ');
    case 'order_items':
      return filled(q.items).join(' → ');
    case 'numeric': {
      const tol = String(q.numeric_tolerance ?? '').trim();
      return `${String(q.numeric_answer ?? '').trim()}${tol && tol !== '0' ? ` (± ${tol})` : ''}`;
    }
    default:
      return q.correct_answer?.trim() ?? '';
  }
}

/** Pregunta en el mismo formato que envía el servidor al alumno */
function toStudent(q) {
  const base = {
    id: `pv${q._key}`,
    type: q.type,
    content: q.content.trim(),
    points: Math.max(1, Number(q.points) || 1),
    solution: solutionOf(q),
  };

  if (q.type === 'multiple_choice' || q.type === 'multiple_select') {
    const opts = filled(q.options).map((content, i) => ({ id: i, content }));
    base.options = props.form.shuffle_answers ? shuffle(opts) : opts;
  } else if (q.type === 'fill_blank') {
    base.blank_count = q.blanks.length;
  } else if (q.type === 'order_items') {
    // Igual que en el servidor: siempre desordenados
    base.items = shuffle(filled(q.items).map((content, i) => ({ id: i, content })));
  }
  return base;
}

const previewQuestions = computed(() => {
  const list = props.questions.filter((q) => q.content.trim()).map(toStudent);
  if (!order.value.length) return list;
  const byId = new Map(list.map((q) => [q.id, q]));
  return order.value.map((id) => byId.get(id)).filter(Boolean);
});

function resetAnswers() {
  Object.keys(answers).forEach((k) => delete answers[k]);
}

// Al abrir: respuestas en blanco y, si toca, preguntas barajadas
watch(() => props.modelValue, (open) => {
  if (!open) return;
  resetAnswers();
  const ids = props.questions.filter((q) => q.content.trim()).map((q) => `pv${q._key}`);
  order.value = props.form.shuffle_questions ? shuffle(ids) : ids;
});

const shuffleWhat = computed(() => {
  if (props.form.shuffle_questions && props.form.shuffle_answers) return 'las preguntas y las respuestas';
  return props.form.shuffle_questions ? 'las preguntas' : 'las respuestas';
});

const metaLine = computed(() => {
  const parts = [plural(previewQuestions.value.length, 'pregunta', 'preguntas')];
  parts.push(props.timeLimitMinutes ? `${props.timeLimitMinutes} min` : 'sin límite de tiempo');
  parts.push(`se aprueba con ${props.form.pass_score} %`);
  const penalty = penaltyLabel(props.form.wrong_penalty);
  if (penalty) parts.push(`cada fallo resta ${penalty}`);
  return parts.join(' · ');
});
</script>

<style scoped>
.pv {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.pv__title {
  margin: 0;
  font-size: var(--fs-section);
}

.pv__desc {
  margin: 6px 0 0;
  color: var(--text-body);
}

.pv__meta {
  margin: 6px 0 0;
  font-size: var(--fs-small);
  color: var(--muted);
}

.pv__notice {
  display: flex;
  gap: 10px;
  align-items: baseline;
  padding: 12px 14px;
  border-radius: var(--radius-card);
  background: var(--accent-soft);
  color: var(--accent-ink);
  font-size: var(--fs-small);
}

.pv__notice p {
  margin: 0;
}

.pv__toggle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: var(--fs-small);
  cursor: pointer;
  align-self: flex-start;
}

.pv__toggle input {
  width: 18px;
  height: 18px;
  accent-color: var(--accent);
}

.pv__empty {
  color: var(--muted);
  margin: 0;
}

.pv__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.pv__q {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.pv__q-meta {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.pv__q-text {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.pv__solution {
  display: flex;
  gap: 8px;
  align-items: baseline;
  margin: 0;
  padding: 10px 14px;
  border-radius: var(--radius-card);
  background: var(--positive-bg);
  color: var(--positive-ink);
  font-size: var(--fs-small);
  overflow-wrap: anywhere;
}
</style>
