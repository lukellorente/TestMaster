<template>
  <div class="stats-card">
    <div class="stats-icon" :style="{ background: iconBackground }">
      <i :class="icon"></i>
    </div>
    <div class="stats-content">
      <div class="stats-value">{{ formattedValue }}</div>
      <div class="stats-label">{{ label }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  value: {
    type: [Number, String],
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    default: 'fa-solid fa-chart-bar',
  },
  iconColor: {
    type: String,
    default: '#667eea',
  },
  format: {
    type: String,
    default: 'number',
    validator: (v) => ['number', 'percent', 'time', 'none'].includes(v),
  },
});

const iconBackground = computed(() => {
  // Generar un fondo más claro basado en el color del icono
  return `${props.iconColor}15`;
});

const formattedValue = computed(() => {
  const val = props.value;
  
  switch (props.format) {
    case 'percent':
      return `${Number(val).toFixed(1)}%`;
    case 'time':
      const m = Math.floor(val / 60);
      const s = Math.floor(val % 60);
      return `${m}:${String(s).padStart(2, '0')}`;
    case 'number':
      return Number(val).toLocaleString();
    default:
      return val;
  }
});
</script>

<style scoped>
.stats-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.stats-icon {
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 1.25rem;
  color: v-bind(iconColor);
  flex-shrink: 0;
}

.stats-content {
  flex: 1;
  min-width: 0;
}

.stats-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
  line-height: 1.2;
}

.stats-label {
  color: #a0aec0;
  font-size: 0.85rem;
  margin-top: 0.125rem;
}
</style>
