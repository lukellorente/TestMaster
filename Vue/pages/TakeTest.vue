<template>
  <BaseLoader v-if="loading" text="Cargando preguntas..." size="large" />

  <main v-else style="max-width: 900px; margin: 0 auto">
    <!-- Timer -->
    <CountdownTimer
      v-if="timeLimit"
      ref="timerRef"
      :initial-time="timeLimit"
      :warning-threshold="120"
      :critical-threshold="30"
      class="test-timer"
      @expired="handleTimeExpired"
    />

    <!-- Test info -->
    <TestInfo
      :title="testTitle"
      :questions-count="questions.length"
      :time-limit="timeLimit"
    />

    <!-- Questions -->
    <form @submit.prevent="submitTest">
      <QuestionCard
        v-for="(q, idx) in questions"
        :key="q.id"
        :number="idx + 1"
        :content="q.content"
        :image-url="q.image_url ? `/uploads/${q.image_url}` : ''"
      >
        <!-- multiple_choice -->
        <QuestionMultipleChoice
          v-if="q.type === 'multiple_choice'"
          :question-id="q.id"
          :options="q.options"
          v-model="answers[q.id]"
        />

        <!-- true_false -->
        <QuestionTrueFalse
          v-else-if="q.type === 'true_false'"
          :question-id="q.id"
          v-model="answers[q.id]"
        />

        <!-- short_answer -->
        <QuestionShortAnswer
          v-else-if="q.type === 'short_answer'"
          v-model="answers[q.id]"
        />
      </QuestionCard>

      <div style="text-align: center; margin-top: 2rem">
        <BaseButton type="submit" :loading="submitting" icon="fa-solid fa-paper-plane" style="padding: 1rem 3rem; font-size: 1.1rem">
          {{ submitting ? 'Enviando...' : 'Enviar Respuestas' }}
        </BaseButton>
      </div>
    </form>
  </main>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import api from '../api/axios';
import BaseButton from '../components/base/BaseButton.vue';
import BaseLoader from '../components/base/BaseLoader.vue';
import TestInfo from '../components/test/TestInfo.vue';
import CountdownTimer from '../components/results/CountdownTimer.vue';
import QuestionCard from '../components/question/QuestionCard.vue';
import QuestionMultipleChoice from '../components/question/QuestionMultipleChoice.vue';
import QuestionTrueFalse from '../components/question/QuestionTrueFalse.vue';
import QuestionShortAnswer from '../components/question/QuestionShortAnswer.vue';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const submitting = ref(false);
const testTitle = ref('');
const timeLimit = ref(0);
const questions = ref([]);
const answers = reactive({});
const startedAt = ref(null);
const timerRef = ref(null);

const handleTimeExpired = () => {
  alert('¡Tiempo agotado! El test se enviará automáticamente.');
  submitTest();
};

onMounted(async () => {
  try {
    const { data } = await api.get(`/tests/${route.params.id}/questions`);
    console.log('Preguntas recibidas:', data.questions);
    testTitle.value = data.test.title;
    timeLimit.value = data.test.time_limit || 0;
    questions.value = data.questions;
    startedAt.value = Math.floor(Date.now() / 1000);
  } catch (err) {
    console.error(err);
    alert('No se pudieron cargar las preguntas.');
    router.push('/');
  } finally {
    loading.value = false;
  }
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
      question_order: questions.value.map((q) => q.id),
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
.test-timer {
  position: fixed;
  top: 100px;
  right: 2rem;
  z-index: 1000;
}

@media (max-width: 768px) {
  .test-timer {
    position: sticky;
    top: 0.75rem;
    right: auto;
    margin: 0 auto 1rem;
    width: 100%;
    z-index: 1000;
  }
}
</style>
