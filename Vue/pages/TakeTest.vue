<template>
  <div v-if="loading" style="text-align: center; padding: 3rem; color: #666">
    <i class="fa-solid fa-spinner fa-spin"></i> Cargando preguntas...
  </div>

  <main v-else style="max-width: 900px; margin: 0 auto">
    <!-- Timer -->
    <div
      v-if="timeLimit"
      :class="['timer', { warning: timeLeft <= 120 }]"
    >
      <div style="font-size: 0.85rem; color: #718096; margin-bottom: 0.25rem">Tiempo restante</div>
      <div style="font-size: 1.5rem; font-weight: 600; color: #667eea">{{ formattedTime }}</div>
    </div>

    <!-- Test info -->
    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem">
      <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem">{{ testTitle }}</h1>
      <p style="color: #718096">
        {{ questions.length }} pregunta{{ questions.length !== 1 ? 's' : '' }}
        <template v-if="timeLimit"> &bull; {{ Math.round(timeLimit / 60) }} minutos</template>
      </p>
    </div>

    <!-- Questions -->
    <form @submit.prevent="submitTest">
      <div v-for="(q, idx) in questions" :key="q.id" class="question-card">
        <span class="question-number">Pregunta {{ idx + 1 }}</span>
        <div class="question-text" v-html="nl2br(q.content)"></div>

        <img
          v-if="q.image_url"
          :src="`/uploads/${q.image_url}`"
          alt="Imagen de la pregunta"
          style="max-width: 100%; border-radius: 8px; margin-bottom: 1rem"
        />

        <!-- multiple_choice -->
        <template v-if="q.type === 'multiple_choice'">
          <label v-for="opt in q.options" :key="opt.id" class="option-label">
            <input type="radio" :name="`q_${q.id}`" :value="String(opt.id)" v-model="answers[q.id]" />
            <span class="option-text">{{ opt.content }}</span>
          </label>
        </template>

        <!-- true_false -->
        <template v-else-if="q.type === 'true_false'">
          <label class="option-label">
            <input type="radio" :name="`q_${q.id}`" value="true" v-model="answers[q.id]" />
            <span class="option-text"><i class="fa-solid fa-check"></i> Verdadero</span>
          </label>
          <label class="option-label">
            <input type="radio" :name="`q_${q.id}`" value="false" v-model="answers[q.id]" />
            <span class="option-text"><i class="fa-solid fa-xmark"></i> Falso</span>
          </label>
        </template>

        <!-- short_answer -->
        <template v-else-if="q.type === 'short_answer'">
          <input
            type="text"
            v-model="answers[q.id]"
            class="short-answer"
            placeholder="Escribe tu respuesta aquí..."
          />
        </template>
      </div>

      <div style="text-align: center; margin-top: 2rem">
        <button type="submit" class="btn" :disabled="submitting" style="padding: 1rem 3rem; font-size: 1.1rem">
          <i class="fa-solid fa-paper-plane"></i>
          {{ submitting ? 'Enviando...' : 'Enviar Respuestas' }}
        </button>
      </div>
    </form>
  </main>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const submitting = ref(false);
const testTitle = ref('');
const timeLimit = ref(0);
const timeLeft = ref(0);
const questions = ref([]);
const answers = reactive({});
const startedAt = ref(null);
let timerInterval = null;

const formattedTime = computed(() => {
  const m = Math.floor(timeLeft.value / 60);
  const s = timeLeft.value % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

const nl2br = (str) => (str || '').replace(/\n/g, '<br>');

onMounted(async () => {
  try {
    const { data } = await api.get(`/tests/${route.params.id}/questions`);
    console.log('Preguntas recibidas:', data.questions);
    testTitle.value = data.test.title;
    timeLimit.value = data.test.time_limit || 0;
    questions.value = data.questions;
    startedAt.value = Math.floor(Date.now() / 1000);

    if (timeLimit.value) {
      timeLeft.value = timeLimit.value;
      timerInterval = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
          clearInterval(timerInterval);
          alert('¡Tiempo agotado! El test se enviará automáticamente.');
          submitTest();
        }
      }, 1000);
    }
  } catch (err) {
    console.error(err);
    alert('No se pudieron cargar las preguntas.');
    router.push('/');
  } finally {
    loading.value = false;
  }
});

onBeforeUnmount(() => {
  if (timerInterval) clearInterval(timerInterval);
});

onBeforeRouteLeave((to, from, next) => {
  if (!submitting.value && Object.keys(answers).length > 0) {
    const leave = window.confirm('¿Seguro que quieres salir? Se perderán tus respuestas.');
    next(leave);
  } else {
    next();
  }
});

async function submitTest() {
  if (submitting.value) return;
  submitting.value = true;

  try {
    const { data } = await api.post('/attempts/submit', {
      test_id: route.params.id,
      answers,
      started_at: startedAt.value,
    });
    router.push(`/results/${data.attempt_id}`);
  } catch (err) {
    console.error(err);
    alert(err.response?.data?.message || 'Error al enviar el test.');
    submitting.value = false;
  }
}
</script>

<style scoped>
.timer {
  position: fixed;
  top: 100px;
  right: 2rem;
  background: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}
.timer.warning {
  border-color: #f5576c;
  animation: pulse 1s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}
.question-card {
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 1.5rem;
  background: white;
}
.question-number {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
  margin-bottom: 1rem;
}
.question-text {
  font-size: 1.1rem;
  color: #1a202c;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}
.option-label {
  display: block;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}
.option-label:hover {
  border-color: #667eea;
  background: #f8f9fa;
}
.option-label input[type='radio'] {
  margin-right: 0.75rem;
}
.short-answer {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-family: inherit;
  font-size: 1rem;
  box-sizing: border-box;
}
.short-answer:focus {
  outline: none;
  border-color: #667eea;
}
</style>
