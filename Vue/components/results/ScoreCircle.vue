<template>
  <div class="score-circle" :style="circleStyle">
    <svg :width="size" :height="size" viewBox="0 0 100 100">
      <!-- Background circle -->
      <circle
        cx="50"
        cy="50"
        :r="radius"
        fill="none"
        :stroke="trackColor"
        :stroke-width="strokeWidth"
      />
      <!-- Progress circle -->
      <circle
        cx="50"
        cy="50"
        :r="radius"
        fill="none"
        :stroke="progressColor"
        :stroke-width="strokeWidth"
        :stroke-dasharray="circumference"
        :stroke-dashoffset="dashOffset"
        stroke-linecap="round"
        class="progress-ring"
      />
    </svg>
    <div class="score-content">
      <div class="score-value" :style="{ color: progressColor }">{{ displayScore }}</div>
      <div v-if="label" class="score-label">{{ label }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  score: {
    type: Number,
    required: true,
  },
  maxScore: {
    type: Number,
    default: 100,
  },
  size: {
    type: Number,
    default: 150,
  },
  strokeWidth: {
    type: Number,
    default: 8,
  },
  label: {
    type: String,
    default: '',
  },
  showPercent: {
    type: Boolean,
    default: true,
  },
  trackColor: {
    type: String,
    default: '#e2e8f0',
  },
  passThreshold: {
    type: Number,
    default: 50,
  },
});

const radius = computed(() => 50 - props.strokeWidth / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const percentage = computed(() => Math.min((props.score / props.maxScore) * 100, 100));

const dashOffset = computed(() => {
  return circumference.value - (percentage.value / 100) * circumference.value;
});

const progressColor = computed(() => {
  if (percentage.value >= 70) return '#10b981';
  if (percentage.value >= props.passThreshold) return '#f59e0b';
  return '#ef4444';
});

const displayScore = computed(() => {
  if (props.showPercent) {
    return `${percentage.value.toFixed(0)}%`;
  }
  return `${props.score}/${props.maxScore}`;
});

const circleStyle = computed(() => ({
  width: `${props.size}px`,
  height: `${props.size}px`,
}));
</script>

<style scoped>
.score-circle {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.score-circle svg {
  transform: rotate(-90deg);
}

.progress-ring {
  transition: stroke-dashoffset 0.5s ease;
}

.score-content {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.score-value {
  font-size: 1.75rem;
  font-weight: 700;
  line-height: 1;
}

.score-label {
  font-size: 0.75rem;
  color: #a0aec0;
  margin-top: 0.25rem;
}
</style>
