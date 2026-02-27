<template>
  <div :class="['countdown-timer', { warning: isWarning, critical: isCritical }]">
    <div v-if="showLabel" class="timer-label">{{ label }}</div>
    <div class="timer-value">
      <i v-if="showIcon" class="fa-solid fa-clock"></i>
      {{ formattedTime }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  // Tiempo inicial en segundos
  initialTime: {
    type: Number,
    required: true,
  },
  // Auto iniciar el timer
  autoStart: {
    type: Boolean,
    default: true,
  },
  // Umbral de warning (segundos)
  warningThreshold: {
    type: Number,
    default: 120,
  },
  // Umbral crítico (segundos)
  criticalThreshold: {
    type: Number,
    default: 30,
  },
  label: {
    type: String,
    default: 'Tiempo restante',
  },
  showLabel: {
    type: Boolean,
    default: true,
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['tick', 'warning', 'critical', 'expired']);

const timeLeft = ref(props.initialTime);
const isRunning = ref(false);
let timerInterval = null;

const isWarning = computed(() => timeLeft.value <= props.warningThreshold && timeLeft.value > props.criticalThreshold);
const isCritical = computed(() => timeLeft.value <= props.criticalThreshold);

const formattedTime = computed(() => {
  const hours = Math.floor(timeLeft.value / 3600);
  const minutes = Math.floor((timeLeft.value % 3600) / 60);
  const seconds = timeLeft.value % 60;
  
  if (hours > 0) {
    return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
  }
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const start = () => {
  if (isRunning.value) return;
  isRunning.value = true;
  timerInterval = setInterval(() => {
    if (timeLeft.value > 0) {
      timeLeft.value--;
      emit('tick', timeLeft.value);
      
      if (timeLeft.value === props.warningThreshold) {
        emit('warning', timeLeft.value);
      }
      if (timeLeft.value === props.criticalThreshold) {
        emit('critical', timeLeft.value);
      }
    } else {
      stop();
      emit('expired');
    }
  }, 1000);
};

const stop = () => {
  isRunning.value = false;
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};

const reset = (newTime = props.initialTime) => {
  stop();
  timeLeft.value = newTime;
};

watch(() => props.initialTime, (newVal) => {
  timeLeft.value = newVal;
});

onMounted(() => {
  if (props.autoStart && props.initialTime > 0) {
    start();
  }
});

onBeforeUnmount(() => {
  stop();
});

defineExpose({ start, stop, reset, timeLeft });
</script>

<style scoped>
.countdown-timer {
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  padding: 0.75rem 1.25rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.timer-label {
  font-size: 0.8rem;
  color: #718096;
  margin-bottom: 0.25rem;
}

.timer-value {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #667eea;
}

.timer-value i {
  font-size: 1.1rem;
}

.warning .timer-value {
  color: #f59e0b;
}

.critical .timer-value {
  color: #ef4444;
  animation: pulse 1s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
</style>
