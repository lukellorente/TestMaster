<template>
  <div :class="['loader', `loader--${size}`]" :style="containerStyle">
    <i class="fa-solid fa-spinner fa-spin" :style="{ color: color }"></i>
    <span v-if="text" class="loader-text">{{ text }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'medium',
    validator: (v) => ['small', 'medium', 'large'].includes(v),
  },
  text: {
    type: String,
    default: '',
  },
  color: {
    type: String,
    default: '#667eea',
  },
  center: {
    type: Boolean,
    default: true,
  },
});

const containerStyle = computed(() => ({
  textAlign: props.center ? 'center' : 'left',
  padding: props.size === 'small' ? '0.5rem' : props.size === 'large' ? '3rem' : '1.5rem',
}));
</script>

<style scoped>
.loader {
  color: #718096;
}

.loader--small {
  font-size: 0.875rem;
}

.loader--small i {
  font-size: 1rem;
}

.loader--medium {
  font-size: 1rem;
}

.loader--medium i {
  font-size: 1.5rem;
}

.loader--large {
  font-size: 1.25rem;
}

.loader--large i {
  font-size: 2.5rem;
}

.loader-text {
  margin-left: 0.5rem;
}
</style>
