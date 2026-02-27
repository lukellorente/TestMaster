<template>
  <div class="test-info">
    <h1 class="test-info-title">{{ title }}</h1>
    <p v-if="description" class="test-info-description">{{ description }}</p>
    
    <div class="test-info-stats">
      <div class="stat-item">
        <i class="fa-solid fa-file-lines"></i>
        <span>{{ questionsCount }} pregunta{{ questionsCount !== 1 ? 's' : '' }}</span>
      </div>
      <div v-if="timeLimit" class="stat-item">
        <i class="fa-solid fa-clock"></i>
        <span>{{ formattedTime }}</span>
      </div>
      <div v-if="difficulty" class="stat-item">
        <i :class="difficultyIcon"></i>
        <span>{{ difficultyText }}</span>
      </div>
      <div v-if="passScore" class="stat-item">
        <i class="fa-solid fa-trophy"></i>
        <span>Aprobar: {{ passScore }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  questionsCount: {
    type: Number,
    default: 0,
  },
  timeLimit: {
    type: Number,
    default: 0,
  },
  difficulty: {
    type: String,
    default: '',
  },
  passScore: {
    type: Number,
    default: 0,
  },
});

const difficultyMap = { easy: 'Fácil', medium: 'Medio', hard: 'Difícil' };
const difficultyIconMap = {
  easy: 'fa-solid fa-face-smile',
  medium: 'fa-solid fa-face-meh',
  hard: 'fa-solid fa-face-frown',
};

const difficultyText = computed(() => difficultyMap[props.difficulty] || '');
const difficultyIcon = computed(() => difficultyIconMap[props.difficulty] || 'fa-solid fa-question');

const formattedTime = computed(() => {
  const minutes = Math.round(props.timeLimit / 60);
  return `${minutes} minuto${minutes !== 1 ? 's' : ''}`;
});
</script>

<style scoped>
.test-info {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.test-info-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.test-info-description {
  color: #718096;
  margin-bottom: 1rem;
  line-height: 1.6;
}

.test-info-stats {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.stat-item {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #4a5568;
  font-size: 0.95rem;
}

.stat-item i {
  color: #667eea;
}
</style>
