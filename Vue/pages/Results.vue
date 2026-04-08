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
        {{ attempt.correct_answers }} de {{ totalQuestions }} respuestas correctas
        <template v-if="showWeightedPoints">
          <br />
          {{ attempt.points_earned }} de {{ attempt.total_points }} puntos ponderados
        </template>
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
    <h2 style="margin-bottom: 1.5rem">Detalle de Respuestas</h2>

    <QuestionResult
      v-for="(r, idx) in responses"
      :key="r.id"
      :number="idx + 1"
      :content="r.question_text"
      :is-correct="r.is_correct"
      :answered="r.answered"
      :user-answer-text="getUserAnswerText(r)"
      :correct-answer-text="getCorrectAnswerText(r)"
      :explanation="attempt.test.show_explanations ? r.explanation : ''"
      :show-explanation="attempt.test.show_explanations"
    />

    <BaseAlert v-if="attempt.test && !attempt.test.show_correct_answers" variant="info">
      El creador del test ha desactivado la visualización de respuestas correctas.
    </BaseAlert>

    <!-- Actions -->
    <div class="results-actions">
      <BaseButton :to="`/test/${attempt.test_id}`" style="padding: 0.75rem 2rem">
        Ver Información del Test
      </BaseButton>
      <BaseButton :to="`/test/${attempt.test_id}/take`" variant="secondary" style="padding: 0.75rem 2rem">
        Volver a Intentar
      </BaseButton>
    </div>
  </main>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
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

const totalQuestions = computed(() => {
  if (!attempt.value) return 0;

  return Number(attempt.value.correct_answers || 0)
    + Number(attempt.value.incorrect_answers || 0)
    + Number(attempt.value.unanswered || 0);
});

const showWeightedPoints = computed(() => {
  if (!attempt.value) return false;

  return Number(attempt.value.total_points || 0) !== totalQuestions.value;
});

const getUserAnswerText = (r) => r.given_answer_label || '';

const getCorrectAnswerText = (r) => {
  if (!attempt.value?.test?.show_correct_answers || r.is_correct) {
    return '';
  }

  return r.correct_answer_label || '';
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
.results-actions {
  text-align: center;
  margin-top: 3rem;
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .results-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .results-actions :deep(.btn) {
    width: 100%;
  }
}
</style>
