<template>
  <BaseLoader v-if="loading" text="Cargando test..." size="large" />
  <BaseAlert v-else-if="error" variant="error">{{ error }}</BaseAlert>
  <div v-else-if="test" style="max-width: 800px; margin: 0 auto">
    <!-- Categorías -->
    <div style="margin-bottom: 1rem">
      <BaseBadge
        v-for="cat in test.categories"
        :key="cat.id"
        variant="secondary"
        :icon="cat.icon"
        style="margin-right: 0.5rem"
      >
        {{ cat.name }}
      </BaseBadge>
    </div>

    <h1 style="font-size: 2rem; margin-bottom: 1rem">{{ test.title }}</h1>

    <p v-if="test.description" style="color: #718096; font-size: 1.1rem; line-height: 1.6">
      {{ test.description }}
    </p>

    <div style="margin-top: 1.5rem; color: #a0aec0; font-size: 0.9rem">
      Creado por 
      <router-link v-if="test.user" :to="`/profile/${test.user.id}`" style="color: #4a5568; font-weight: 600; text-decoration: none">
        {{ test.user?.name }}
      </router-link>
      el {{ formatDateShort(test.created_at) }}
    </div>

    <!-- Info cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 2rem 0">
      <StatsCard
        :value="test.questions_count"
        label="Preguntas"
        icon="fa-solid fa-file-lines"
        format="none"
      />
      <StatsCard
        :value="test.time_limit ? Math.round(test.time_limit / 60) : '∞'"
        label="Minutos"
        icon="fa-solid fa-clock"
        format="none"
      />
      <StatsCard
        :value="difficultyText"
        label="Dificultad"
        :icon="difficultyIcon"
        format="none"
      />
      <StatsCard
        :value="test.completed_attempts_count || 0"
        label="Intentos"
        icon="fa-solid fa-users"
        format="none"
      />
    </div>

    <!-- Last attempt -->
    <div
      v-if="test.last_attempt"
      style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem"
    >
      <h3 style="margin-bottom: 1rem">Tu mejor resultado</h3>
      <div style="display: flex; justify-content: space-between; align-items: center">
        <div>
          <div style="font-size: 2rem; font-weight: 600">{{ Number(test.last_attempt.score).toFixed(1) }}%</div>
          <div style="opacity: 0.9; font-size: 0.9rem">
            Realizado el {{ formatDateShort(test.last_attempt.completed_at) }}
          </div>
        </div>
        <div style="text-align: right">
          <div style="font-size: 1.2rem">{{ test.user_attempts }} intento{{ test.user_attempts > 1 ? 's' : '' }}</div>
        </div>
      </div>
    </div>

    <!-- Config -->
    <div style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 2rem">
      <h3 style="margin-bottom: 1.5rem; color: #1a202c">Configuración del Test</h3>
      <div style="display: grid; gap: 1rem">
        <div class="config-row">
          <span style="color: #718096">Nota mínima para aprobar</span>
          <strong style="color: #667eea">{{ test.pass_score }}%</strong>
        </div>
        <div class="config-row">
          <span style="color: #718096">Intentos permitidos</span>
          <strong style="color: #667eea">{{ test.max_attempts == -1 ? 'Ilimitados' : test.max_attempts }}</strong>
        </div>
        <div class="config-row">
          <span style="color: #718096">Orden de preguntas</span>
          <strong style="color: #667eea">{{ test.shuffle_questions ? 'Aleatorio' : 'Fijo' }}</strong>
        </div>
        <div class="config-row" style="border-bottom: none">
          <span style="color: #718096">Mostrar respuestas correctas</span>
          <strong style="color: #667eea">{{ test.show_correct_answers ? 'Sí' : 'No' }}</strong>
        </div>
      </div>
    </div>

    <!-- Action button -->
    <div style="text-align: center; margin-bottom: 2rem">
      <template v-if="!authStore.isLoggedIn">
        <p style="color: #718096; margin-bottom: 1rem">Debes iniciar sesión para realizar este test</p>
        <BaseButton to="/login" style="padding: 1rem 3rem; font-size: 1.1rem">
          Iniciar Sesión
        </BaseButton>
      </template>
      <template v-else-if="test.max_attempts != -1 && (test.user_attempts || 0) >= test.max_attempts">
        <BaseAlert variant="info">Has alcanzado el número máximo de intentos para este test.</BaseAlert>
      </template>
      <template v-else>
        <BaseButton :to="`/test/${test.id}/take`" style="padding: 1rem 3rem; font-size: 1.1rem">
          Realizar Test
        </BaseButton>
        <p v-if="test.user_attempts > 0" style="color: #718096; margin-top: 1rem; font-size: 0.9rem">
          Intento {{ test.user_attempts + 1 }} de {{ test.max_attempts == -1 ? '∞' : test.max_attempts }}
        </p>
      </template>
    </div>

    <!-- Average stats -->
    <TestStats
      v-if="test.avg_score"
      title="Estadísticas Generales"
      :attempts-count="test.completed_attempts_count"
      :avg-score="Number(test.avg_score)"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import BaseButton from '../components/base/BaseButton.vue';
import BaseLoader from '../components/base/BaseLoader.vue';
import BaseAlert from '../components/base/BaseAlert.vue';
import BaseBadge from '../components/base/BaseBadge.vue';
import StatsCard from '../components/user/StatsCard.vue';
import TestStats from '../components/test/TestStats.vue';

const route = useRoute();
const authStore = useAuthStore();
const test = ref(null);
const loading = ref(true);
const error = ref('');

const difficultyMap = { easy: 'Fácil', medium: 'Medio', hard: 'Difícil' };
const difficultyIconMap = {
  easy: 'fa-solid fa-face-smile',
  medium: 'fa-solid fa-face-meh',
  hard: 'fa-solid fa-face-frown',
};

const difficultyText = computed(() => difficultyMap[test.value?.difficulty] || 'N/A');
const difficultyIcon = computed(() => difficultyIconMap[test.value?.difficulty] || 'fa-solid fa-question');

const formatDateShort = (d) => {
  if (!d) return '';
  return new Date(d).toLocaleDateString('es-ES');
};

onMounted(async () => {
  if (!route.params.id) {
    error.value = 'Test no encontrado. ID inválido.';
    loading.value = false;
    return;
  }
  try {
    const { data } = await api.get(`/tests/${route.params.id}`);
    test.value = data;
  } catch (err) {
    error.value = 'Error al cargar el test.';
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.config-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f7fafc;
}
</style>
