<template>
  <div v-if="loading" style="text-align: center; padding: 3rem; color: #666">
    <i class="fa-solid fa-spinner fa-spin"></i> Cargando test...
  </div>
  <div v-else-if="error" style="text-align: center; padding: 3rem; color: #ef4444">
    <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
  </div>
  <div v-else-if="test" style="max-width: 800px; margin: 0 auto">
    <!-- Categorías -->
    <div style="margin-bottom: 1rem">
      <span
        v-for="cat in test.categories"
        :key="cat.id"
        style="background: #f8f9fa; color: #667eea; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.85rem; margin-right: 0.5rem"
      >
        <i :class="cat.icon"></i> {{ cat.name }}
      </span>
    </div>

    <h1 style="font-size: 2rem; margin-bottom: 1rem">{{ test.title }}</h1>

    <p v-if="test.description" style="color: #718096; font-size: 1.1rem; line-height: 1.6">
      {{ test.description }}
    </p>

    <div style="margin-top: 1.5rem; color: #a0aec0; font-size: 0.9rem">
      Creado por <strong style="color: #4a5568">{{ test.user?.name }}</strong>
      el {{ formatDateShort(test.created_at) }}
    </div>

    <!-- Info cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0">
      <div class="info-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-file-lines"></i></div>
        <div class="info-value">{{ test.questions_count }}</div>
        <div class="info-label">Preguntas</div>
      </div>

      <div class="info-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-clock"></i></div>
        <div class="info-value">{{ test.time_limit ? Math.round(test.time_limit / 60) + ' min' : 'Sin límite' }}</div>
        <div class="info-label">Tiempo</div>
      </div>

      <div class="info-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem">
          <i :class="difficultyIcon"></i>
        </div>
        <div class="info-value">{{ difficultyText }}</div>
        <div class="info-label">Dificultad</div>
      </div>

      <div class="info-card">
        <div style="font-size: 2rem; margin-bottom: 0.5rem"><i class="fa-solid fa-users"></i></div>
        <div class="info-value">{{ test.completed_attempts_count || 0 }}</div>
        <div class="info-label">Personas</div>
      </div>
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
        <router-link to="/login" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem">
          Iniciar Sesión
        </router-link>
      </template>
      <template v-else-if="test.max_attempts != -1 && (test.user_attempts || 0) >= test.max_attempts">
        <div class="alert alert-info">Has alcanzado el número máximo de intentos para este test.</div>
      </template>
      <template v-else>
        <router-link :to="`/test/${test.id}/take`" class="btn" style="padding: 1rem 3rem; font-size: 1.1rem">
          Realizar Test
        </router-link>
        <p v-if="test.user_attempts > 0" style="color: #718096; margin-top: 1rem; font-size: 0.9rem">
          Intento {{ test.user_attempts + 1 }} de {{ test.max_attempts == -1 ? '∞' : test.max_attempts }}
        </p>
      </template>
    </div>

    <!-- Average stats -->
    <div
      v-if="test.avg_score"
      style="background: #f8f9fa; padding: 1.5rem; border-radius: 12px; text-align: center"
    >
      <h4 style="color: #4a5568; margin-bottom: 1rem">Estadísticas Generales</h4>
      <div style="font-size: 2rem; font-weight: 600; color: #667eea; margin-bottom: 0.5rem">
        {{ Number(test.avg_score).toFixed(1) }}%
      </div>
      <div style="color: #718096; font-size: 0.9rem">
        Puntuación media de {{ test.completed_attempts_count }} persona{{ test.completed_attempts_count !== 1 ? 's' : '' }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

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
.info-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
}
.info-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #667eea;
}
.info-label {
  color: #718096;
  font-size: 0.9rem;
}
.config-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f7fafc;
}
</style>
