<template>
  <div v-if="loading" style="text-align: center; padding: 3rem; color: #666">
    <i class="fa-solid fa-spinner fa-spin"></i> Cargando resultados...
  </div>

  <main v-else-if="attempt" style="max-width: 900px; margin: 0 auto">
    <!-- Score summary -->
    <div style="text-align: center; margin-bottom: 3rem">
      <h1 style="font-size: 2rem; margin-bottom: 1rem">
        <template v-if="attempt.passed">¡Aprobado! <i class="fa-solid fa-champagne-glasses"></i></template>
        <template v-else>No Aprobado <i class="fa-solid fa-face-sad-tear"></i></template>
      </h1>

      <div
        :style="{
          display: 'inline-block',
          background: attempt.passed
            ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)'
            : 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)',
          color: 'white',
          padding: '2rem 3rem',
          borderRadius: '16px',
          marginBottom: '2rem',
        }"
      >
        <div style="font-size: 4rem; font-weight: 700; margin-bottom: 0.5rem">
          {{ Number(attempt.score).toFixed(1) }}%
        </div>
        <div style="font-size: 1.2rem; opacity: 0.9">
          {{ attempt.points_earned }} de {{ attempt.total_points }} puntos
        </div>
      </div>
    </div>

    <!-- Stats grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem">
      <div class="stat-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-check"></i></div>
        <div style="font-size: 2rem; font-weight: 600; color: #10b981">{{ attempt.correct_answers }}</div>
        <div style="color: #718096; font-size: 0.9rem">Correctas</div>
      </div>
      <div class="stat-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-xmark"></i></div>
        <div style="font-size: 2rem; font-weight: 600; color: #ef4444">{{ attempt.incorrect_answers }}</div>
        <div style="color: #718096; font-size: 0.9rem">Incorrectas</div>
      </div>
      <div v-if="attempt.unanswered > 0" class="stat-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-regular fa-circle"></i></div>
        <div style="font-size: 2rem; font-weight: 600; color: #a0aec0">{{ attempt.unanswered }}</div>
        <div style="color: #718096; font-size: 0.9rem">Sin responder</div>
      </div>
      <div class="stat-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-clock"></i></div>
        <div style="font-size: 2rem; font-weight: 600; color: #667eea">{{ formatTime(attempt.time_spent) }}</div>
        <div style="color: #718096; font-size: 0.9rem">Tiempo</div>
      </div>
    </div>

    <!-- Answer detail -->
    <template v-if="attempt.test && attempt.test.show_correct_answers">
      <h2 style="margin-bottom: 1.5rem">Detalle de Respuestas</h2>

      <div
        v-for="(r, idx) in responses"
        :key="r.id"
        :class="['result-card', r.is_correct ? 'correct' : 'incorrect']"
      >
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem">
          <span
            :style="{
              background: r.is_correct ? '#10b981' : '#ef4444',
              color: 'white',
              padding: '0.25rem 0.75rem',
              borderRadius: '6px',
              fontSize: '0.85rem',
              fontWeight: 600,
            }"
          >
            Pregunta {{ idx + 1 }}
          </span>
          <span
            :style="{ color: r.is_correct ? '#10b981' : '#ef4444', fontWeight: 600, fontSize: '1.1rem' }"
          >
            {{ r.is_correct ? '✓ Correcta' : '✗ Incorrecta' }}
          </span>
        </div>

        <div style="font-size: 1.1rem; color: #1a202c; margin-bottom: 1.5rem" v-html="nl2br(r.question_text)"></div>

        <!-- multiple_choice -->
        <template v-if="r.type === 'multiple_choice'">
          <div
            v-for="opt in r.options"
            :key="opt.id"
            :style="optionStyle(r.given_answer, opt)"
          >
            <strong v-if="String(r.given_answer) === String(opt.id)" style="color: #667eea">Tu respuesta: </strong>
            {{ opt.content }}
            <span v-if="opt.is_correct && String(r.given_answer) !== String(opt.id)" style="color: #10b981; font-weight: 600">
              &larr; Respuesta correcta
            </span>
          </div>
        </template>

        <!-- true_false -->
        <template v-else-if="r.type === 'true_false'">
          <div
            :style="{
              padding: '0.75rem 1rem',
              border: '2px solid',
              borderRadius: '8px',
              marginBottom: '0.5rem',
              background: r.is_correct ? '#d1fae5' : '#fee2e2',
              borderColor: r.is_correct ? '#10b981' : '#ef4444',
            }"
          >
            Tu respuesta: <strong>{{ r.given_answer === 'true' ? 'Verdadero' : 'Falso' }}</strong>
          </div>
        </template>

        <!-- short_answer -->
        <template v-else-if="r.type === 'short_answer'">
          <div
            :style="{
              padding: '0.75rem 1rem',
              border: '2px solid',
              borderRadius: '8px',
              marginBottom: '0.5rem',
              background: r.is_correct ? '#d1fae5' : '#fee2e2',
              borderColor: r.is_correct ? '#10b981' : '#ef4444',
            }"
          >
            Tu respuesta: <strong>{{ r.given_answer }}</strong>
          </div>
          <div
            v-if="!r.is_correct && r.options && r.options.length"
            style="padding: 0.75rem 1rem; border: 2px solid #10b981; background: #d1fae5; border-radius: 8px"
          >
            Respuesta correcta: <strong>{{ r.options[0].content }}</strong>
          </div>
        </template>

        <!-- Explanation -->
        <div
          v-if="attempt.test && attempt.test.show_explanations && r.explanation"
          style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #667eea"
        >
          <strong style="color: #667eea"><i class="fa-solid fa-lightbulb"></i> Explicación:</strong><br />
          <span v-html="nl2br(r.explanation)"></span>
        </div>
      </div>
    </template>

    <div v-else class="alert alert-info">
      El creador del test ha desactivado la visualización de respuestas correctas.
    </div>

    <!-- Actions -->
    <div style="text-align: center; margin-top: 3rem">
      <router-link :to="`/test/${attempt.test_id}`" class="btn" style="padding: 0.75rem 2rem; margin-right: 1rem">
        Ver Información del Test
      </router-link>
      <router-link :to="`/test/${attempt.test_id}/take`" class="link" style="padding: 0.75rem 2rem">
        Volver a Intentar
      </router-link>
    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const loading = ref(true);
const attempt = ref(null);
const responses = ref([]);

const nl2br = (s) => (s || '').replace(/\n/g, '<br>');

const formatTime = (sec) => {
  if (!sec) return '00:00';
  const m = Math.floor(sec / 60);
  const s = sec % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

const optionStyle = (givenAnswer, opt) => {
  const isUser = String(givenAnswer) === String(opt.id);
  const isCorrect = !!opt.is_correct;
  let bg = 'transparent';
  let border = '#e2e8f0';

  if (isUser && isCorrect) { bg = '#d1fae5'; border = '#10b981'; }
  else if (isUser && !isCorrect) { bg = '#fee2e2'; border = '#ef4444'; }
  else if (isCorrect) { bg = '#d1fae5'; border = '#10b981'; }

  return {
    padding: '0.75rem 1rem',
    border: `2px solid ${border}`,
    borderRadius: '8px',
    marginBottom: '0.5rem',
    background: bg,
  };
};

onMounted(async () => {
  try {
    const { data } = await api.get(`/attempts/${route.params.attemptId}/results`);
    attempt.value = data.attempt;
    responses.value = data.responses;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
}
.result-card {
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 1.5rem;
  background: white;
}
.result-card.correct {
  border-left: 4px solid #10b981;
}
.result-card.incorrect {
  border-left: 4px solid #ef4444;
}
</style>
