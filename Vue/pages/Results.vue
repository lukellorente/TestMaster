<template>
  <BaseLoader v-if="loading" text="Cargando resultados..." size="large" />

  <main v-else-if="attempt" style="max-width: 900px; margin: 0 auto">
    <!-- Score summary -->
    <div style="text-align: center; margin-bottom: 3rem">
      <h1 style="font-size: 2rem; margin-bottom: 1.5rem">
        <template v-if="attempt.passed">¡Aprobado! <i class="fa-solid fa-champagne-glasses"></i></template>
        <template v-else>No Aprobado <i class="fa-solid fa-face-sad-tear"></i></template>
      </h1>

      <ScoreCircle
        :score="Number(attempt.score)"
        :size="180"
        :stroke-width="12"
        :pass-threshold="attempt.test?.pass_score || 50"
      />
      
      <div style="margin-top: 1rem; color: #718096">
        {{ attempt.points_earned }} de {{ attempt.total_points }} puntos
      </div>
    </div>

    <!-- Stats grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 3rem">
      <StatsCard
        :value="attempt.correct_answers"
        label="Correctas"
        icon="fa-solid fa-check"
        icon-color="#10b981"
        format="none"
      />
      <StatsCard
        :value="attempt.incorrect_answers"
        label="Incorrectas"
        icon="fa-solid fa-xmark"
        icon-color="#ef4444"
        format="none"
      />
      <StatsCard
        v-if="attempt.unanswered > 0"
        :value="attempt.unanswered"
        label="Sin responder"
        icon="fa-regular fa-circle"
        icon-color="#a0aec0"
        format="none"
      />
      <StatsCard
        :value="attempt.time_spent"
        label="Tiempo"
        icon="fa-solid fa-clock"
        icon-color="#667eea"
        format="time"
      />
    </div>

    <!-- Answer detail -->
    <template v-if="attempt.test && attempt.test.show_correct_answers">
      <h2 style="margin-bottom: 1.5rem">Detalle de Respuestas</h2>

      <QuestionResult
        v-for="(r, idx) in responses"
        :key="r.id"
        :number="idx + 1"
        :content="r.question_text"
        :is-correct="r.is_correct"
        :answered="!!r.given_answer"
        :user-answer-text="formatUserAnswer(r)"
        :correct-answer-text="formatCorrectAnswer(r)"
        :explanation="attempt.test.show_explanations ? r.explanation : ''"
        :show-explanation="attempt.test.show_explanations"
      />
    </template>

    <BaseAlert v-else variant="info">
      El creador del test ha desactivado la visualización de respuestas correctas.
    </BaseAlert>

    <!-- Actions -->
    <div style="text-align: center; margin-top: 3rem">
      <BaseButton :to="`/test/${attempt.test_id}`" style="padding: 0.75rem 2rem; margin-right: 1rem">
        Ver Información del Test
      </BaseButton>
      <BaseButton :to="`/test/${attempt.test_id}/take`" variant="secondary" style="padding: 0.75rem 2rem">
        Volver a Intentar
      </BaseButton>
    </div>
  </main>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';
import BaseButton from '../components/base/BaseButton.vue';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseAlert from '../components/base/BaseAlert.vue';
import ScoreCircle from '../components/results/ScoreCircle.vue';
import QuestionResult from '../components/results/QuestionResult.vue';
import StatsCard from '../components/user/StatsCard.vue';

const route = useRoute();
const loading = ref(true);
const attempt = ref(null);
const responses = ref([]);

const formatUserAnswer = (r) => {
  if (!r.given_answer) return '';
  if (r.type === 'true_false') {
    return r.given_answer === 'true' ? 'Verdadero' : 'Falso';
  }
  if (r.type === 'multiple_choice' && r.options) {
    const opt = r.options.find(o => String(o.id) === String(r.given_answer));
    return opt ? opt.content : r.given_answer;
  }
  return r.given_answer;
};

const formatCorrectAnswer = (r) => {
  if (r.is_correct) return '';
  if (r.type === 'true_false') {
    // Find correct option
    const correct = r.options?.find(o => o.is_correct);
    return correct?.content === 'Verdadero' ? 'Verdadero' : 'Falso';
  }
  if (r.type === 'multiple_choice' && r.options) {
    const correct = r.options.find(o => o.is_correct);
    return correct ? correct.content : '';
  }
  if (r.type === 'short_answer' && r.options?.length) {
    return r.options[0].content;
  }
  return '';
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
