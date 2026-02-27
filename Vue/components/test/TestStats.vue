<template>
  <div class="test-stats">
    <h4 v-if="title" class="stats-title">{{ title }}</h4>
    <div class="stats-grid">
      <div class="stat-box">
        <div class="stat-value">{{ attemptsCount }}</div>
        <div class="stat-label">Intentos</div>
      </div>
      <div v-if="avgScore !== null" class="stat-box">
        <div class="stat-value" :style="{ color: scoreColor }">{{ avgScore.toFixed(1) }}%</div>
        <div class="stat-label">Puntuación media</div>
      </div>
      <div v-if="passRate !== null" class="stat-box">
        <div class="stat-value" :style="{ color: passRateColor }">{{ passRate.toFixed(0) }}%</div>
        <div class="stat-label">Tasa de aprobados</div>
      </div>
      <div v-if="avgTime" class="stat-box">
        <div class="stat-value">{{ formattedAvgTime }}</div>
        <div class="stat-label">Tiempo medio</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Estadísticas',
  },
  attemptsCount: {
    type: Number,
    default: 0,
  },
  avgScore: {
    type: Number,
    default: null,
  },
  passRate: {
    type: Number,
    default: null,
  },
  avgTime: {
    type: Number,
    default: 0,
  },
});

const scoreColor = computed(() => {
  if (props.avgScore >= 70) return '#10b981';
  if (props.avgScore >= 50) return '#f59e0b';
  return '#ef4444';
});

const passRateColor = computed(() => {
  if (props.passRate >= 70) return '#10b981';
  if (props.passRate >= 50) return '#f59e0b';
  return '#ef4444';
});

const formattedAvgTime = computed(() => {
  if (!props.avgTime) return '0:00';
  const m = Math.floor(props.avgTime / 60);
  const s = Math.floor(props.avgTime % 60);
  return `${m}:${String(s).padStart(2, '0')}`;
});
</script>

<style scoped>
.test-stats {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 12px;
}

.stats-title {
  color: #4a5568;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  text-align: center;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 1rem;
}

.stat-box {
  text-align: center;
  padding: 0.75rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #667eea;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.8rem;
  color: #a0aec0;
}
</style>
