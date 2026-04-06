<template>
  <main style="max-width: 1000px; margin: 0 auto">
    <h1 style="font-size: 2rem; margin-bottom: 2rem">{{ isEditMode ? 'Editar Test' : 'Crear Nuevo Test' }}</h1>

    <form @submit.prevent="submitTest">
      <!-- INFORMACIÓN BÁSICA -->
      <div class="section">
        <h2 class="section-title"><i class="fa-solid fa-file-lines"></i> Información Básica</h2>

        <div class="form-group">
          <label class="form-label">Título del Test *</label>
          <input v-model="form.title" type="text" class="form-input" placeholder="Ej: Test de Química Básica" required maxlength="255" />
        </div>

        <div class="form-group">
          <label class="form-label">Descripción</label>
          <textarea v-model="form.description" class="form-input" placeholder="Describe brevemente de qué trata el test..." rows="4"></textarea>
        </div>

        <div class="grid-2">
          <BaseSelect
            v-model="form.category_id"
            label="Categoría"
            :options="categories"
            placeholder="Sin categoría"
          />

          <BaseSelect
            v-model="form.difficulty"
            label="Dificultad"
            :options="[
              { value: 'easy', label: 'Fácil' },
              { value: 'medium', label: 'Medio' },
              { value: 'hard', label: 'Difícil' }
            ]"
          />
        </div>
      </div>

      <!-- CONFIGURACIÓN -->
      <div class="section">
        <h2 class="section-title"><i class="fa-solid fa-gear"></i> Configuración del Test</h2>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Tiempo Límite (minutos)</label>
            <input v-model.number="form.time_limit" type="number" class="form-input" min="0" placeholder="0 = sin límite" />
            <p class="help-text">0 = sin límite de tiempo</p>
          </div>

          <div class="form-group">
            <label class="form-label">Máximo de Intentos</label>
            <input v-model.number="form.max_attempts" type="number" class="form-input" placeholder="-1 = ilimitado" />
            <p class="help-text">-1 = intentos ilimitados</p>
          </div>

          <div class="form-group">
            <label class="form-label">Nota Mínima para Aprobar (%)</label>
            <input v-model.number="form.pass_score" type="number" class="form-input" min="0" max="100" />
          </div>

          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" v-model="form.is_public" /> <span>Hacer público</span></label>
          </div>
          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" v-model="form.shuffle_questions" /> <span>Orden aleatorio de preguntas</span></label>
          </div>
          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" v-model="form.shuffle_answers" /> <span>Orden aleatorio de respuestas</span></label>
          </div>
          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" v-model="form.show_correct_answers" /> <span>Mostrar respuestas correctas</span></label>
          </div>
          <div class="form-group">
            <label class="checkbox-label"><input type="checkbox" v-model="form.show_explanations" /> <span>Mostrar explicaciones</span></label>
          </div>
        </div>
      </div>

      <!-- PREGUNTAS -->
      <div class="section">
        <h2 class="section-title"><i class="fa-solid fa-question"></i> Preguntas</h2>

        <div v-for="(q, qIdx) in questions" :key="q._key" class="question-card">
          <div class="question-header">
            <span class="question-number">Pregunta {{ qIdx + 1 }}</span>
            <button type="button" class="btn-remove" @click="removeQuestion(qIdx)">✕ Eliminar</button>
          </div>

          <div class="form-group">
            <label class="form-label">Pregunta *</label>
            <textarea v-model="q.content" class="form-input" placeholder="Escribe aquí la pregunta..." rows="3" required></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Explicación (opcional)</label>
            <textarea v-model="q.explanation" class="form-input" placeholder="Explica por qué es correcta la respuesta..." rows="2"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Puntos</label>
            <input v-model.number="q.points" type="number" class="form-input" min="1" style="width: 100px" />
          </div>

          <!-- multiple_choice -->
          <div v-if="q.type === 'multiple_choice'" class="form-group">
            <label class="form-label">Opciones de Respuesta</label>
            <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="option-item">
              <input type="radio" :name="`correct_${q._key}`" :value="oIdx" v-model="q.correctOption" required />
              <input v-model="opt.content" type="text" class="form-input" :placeholder="`Opción ${oIdx + 1}`" required />
              <button v-if="q.options.length > 2" type="button" class="btn-remove" style="padding: 0.5rem" @click="q.options.splice(oIdx, 1)">✕</button>
            </div>
            <button type="button" class="btn-add-option" @click="q.options.push({ content: '' })">+ Añadir Opción</button>
            <p class="help-text">Marca la respuesta correcta</p>
          </div>

          <!-- true_false -->
          <div v-else-if="q.type === 'true_false'" class="form-group">
            <label class="form-label">Respuesta Correcta</label>
            <div style="display: flex; gap: 1rem">
              <label class="checkbox-label">
                <input type="radio" :name="`tf_${q._key}`" value="true" v-model="q.correct" />
                <span><i class="fa-solid fa-check"></i> Verdadero</span>
              </label>
              <label class="checkbox-label">
                <input type="radio" :name="`tf_${q._key}`" value="false" v-model="q.correct" />
                <span><i class="fa-solid fa-xmark"></i> Falso</span>
              </label>
            </div>
          </div>

          <!-- short_answer -->
          <div v-else-if="q.type === 'short_answer'" class="form-group">
            <label class="form-label">Respuesta Correcta *</label>
            <input v-model="q.correct_answer" type="text" class="form-input" placeholder="Escribe la respuesta correcta" required />
            <p class="help-text">La respuesta no distingue mayúsculas/minúsculas</p>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap">
          <BaseButton @click="addQuestion('multiple_choice')">+ Opción Múltiple</BaseButton>
          <BaseButton variant="success" @click="addQuestion('true_false')">+ Verdadero/Falso</BaseButton>
          <BaseButton variant="warning" @click="addQuestion('short_answer')">+ Respuesta Corta</BaseButton>
        </div>
        <p class="help-text" style="margin-top: 1rem">Añade al menos una pregunta para guardar el test</p>
      </div>

      <!-- BOTONES DE ACCIÓN -->
      <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem">
        <BaseButton type="submit" variant="secondary" :loading="submitting" icon="fa-solid fa-floppy-disk" style="padding: 1rem 2rem" @click="submitStatus = 'draft'">
          {{ isEditMode ? 'Guardar Cambios (Borrador)' : 'Guardar como Borrador' }}
        </BaseButton>
        <BaseButton type="submit" :loading="submitting" icon="fa-solid fa-rocket" style="padding: 1rem 2rem" @click="submitStatus = 'published'">
          {{ isEditMode ? 'Guardar y Publicar' : 'Publicar Test' }}
        </BaseButton>
      </div>

      <div v-if="errors.length" style="margin-top: 1.5rem">
      <BaseAlert v-for="(e, i) in errors" :key="i" variant="error">
        {{ e }}
      </BaseAlert>
      </div>
    </form>
  </main>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../api/axios';
import BaseButton from '../components/base/BaseButton.vue';
import BaseAlert from '../components/base/BaseAlert.vue';
import BaseSelect from '../components/base/BaseSelect.vue';

const router = useRouter();
const route = useRoute();
const categories = ref([]);
const submitting = ref(false);
const submitStatus = ref('published');
const errors = ref([]);
let keyCounter = 0;
const editingTestId = ref(route.params.id ? Number(route.params.id) : null);
const isEditMode = ref(Boolean(editingTestId.value));

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
});

const questions = ref([]);

function addQuestion(type) {
  const q = {
    _key: ++keyCounter,
    type,
    content: '',
    explanation: '',
    points: 1,
  };
  if (type === 'multiple_choice') {
    q.options = [{ content: '' }, { content: '' }, { content: '' }, { content: '' }];
    q.correctOption = 0;
  } else if (type === 'true_false') {
    q.correct = 'true';
  } else if (type === 'short_answer') {
    q.correct_answer = '';
  }
  questions.value.push(q);
}

function removeQuestion(idx) {
  if (confirm('¿Eliminar esta pregunta?')) {
    questions.value.splice(idx, 1);
  }
}

onMounted(async () => {
  try {
    const { data } = await api.get('/categories');
    categories.value = data;
  } catch (err) {
    console.error(err);
  }

  if (isEditMode.value) {
    await loadTestForEdit();
  } else {
    addQuestion('multiple_choice');
  }
});

async function loadTestForEdit() {
  errors.value = [];

  try {
    const { data } = await api.get(`/tests/${editingTestId.value}/edit`);
    const t = data.test;

    form.title = t.title ?? '';
    form.description = t.description ?? '';
    form.category_id = t.category_id ?? '';
    form.difficulty = t.difficulty ?? 'medium';
    form.time_limit = t.time_limit ? Math.round(t.time_limit / 60) : 0;
    form.max_attempts = t.max_attempts ?? -1;
    form.pass_score = t.pass_score ?? 70;
    form.is_public = Boolean(t.is_public);
    form.shuffle_questions = Boolean(t.shuffle_questions);
    form.shuffle_answers = Boolean(t.shuffle_answers);
    form.show_correct_answers = Boolean(t.show_correct_answers);
    form.show_explanations = Boolean(t.show_explanations);

    const mappedQuestions = (t.questions ?? []).map((q) => {
      const mapped = {
        _key: ++keyCounter,
        type: q.type,
        content: q.content ?? '',
        explanation: q.explanation ?? '',
        points: q.points ?? 1,
      };

      if (q.type === 'multiple_choice') {
        const options = (q.options ?? []).map((opt) => ({ content: opt.content ?? '' }));
        while (options.length < 2) {
          options.push({ content: '' });
        }

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

    questions.value = mappedQuestions;
    if (questions.value.length === 0) {
      addQuestion('multiple_choice');
    }
  } catch (err) {
    if (err.response?.status === 403) {
      errors.value.push('No tienes permisos para editar este test.');
    } else if (err.response?.status === 404) {
      errors.value.push('El test no existe o fue eliminado.');
    } else {
      errors.value.push('No se pudo cargar el test para edición.');
    }
  }
}

async function submitTest() {
  errors.value = [];

  if (!form.title.trim()) {
    errors.value.push('El título es obligatorio.');
    return;
  }
  if (questions.value.length === 0) {
    errors.value.push('Debes añadir al menos una pregunta.');
    return;
  }

  // Build payload
  const payload = {
    ...form,
    time_limit: form.time_limit ? form.time_limit : 0,
    status: submitStatus.value,
    questions: questions.value.map((q, idx) => {
      const base = {
        type: q.type,
        content: q.content,
        explanation: q.explanation || null,
        points: q.points,
        order_index: idx,
      };

      if (q.type === 'multiple_choice') {
        base.options = q.options.map((o, oIdx) => ({
          content: o.content,
          is_correct: oIdx === q.correctOption,
          order_index: oIdx,
        }));
      } else if (q.type === 'true_false') {
        base.options = [
          { content: 'Verdadero', is_correct: q.correct === 'true', order_index: 0 },
          { content: 'Falso', is_correct: q.correct === 'false', order_index: 1 },
        ];
      } else if (q.type === 'short_answer') {
        base.correct_answer = q.correct_answer;
      }

      return base;
    }),
  };

  submitting.value = true;
  try {
    const { data } = isEditMode.value
      ? await api.put(`/tests/${editingTestId.value}`, payload)
      : await api.post('/tests', payload);
    router.push(`/test/${data.test.id}`);
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = Object.values(err.response.data.errors).flat();
    } else {
      errors.value.push(err.response?.data?.message || 'Error al crear el test.');
    }
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.section {
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  background: white;
}
.section-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 1.5rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e2e8f0;
}
.question-card {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  margin-bottom: 1.5rem;
}
.question-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}
.question-number {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
}
.btn-remove {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
}
.btn-remove:hover {
  background: #dc2626;
}
.option-item {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  margin-bottom: 0.75rem;
}
.option-item input[type='text'] {
  flex: 1;
}
.btn-add-option {
  background: #10b981;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}
.btn-add-option:hover {
  background: #059669;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}
.help-text {
  font-size: 0.85rem;
  color: #718096;
  margin-top: 0.5rem;
}
.grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}
@media (max-width: 768px) {
  .grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
