<template>
  <div :class="['alert', `alert--${variant}`]" role="alert">
    <i v-if="showIcon" :class="iconClass"></i>
    <div class="alert-content">
      <strong v-if="title" class="alert-title">{{ title }}</strong>
      <slot />
    </div>
    <button v-if="dismissible" type="button" class="alert-close" @click="$emit('dismiss')" aria-label="Cerrar">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['info', 'success', 'warning', 'error'].includes(v),
  },
  title: {
    type: String,
    default: '',
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  dismissible: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['dismiss']);

const iconClass = computed(() => {
  const icons = {
    info: 'fa-solid fa-circle-info',
    success: 'fa-solid fa-circle-check',
    warning: 'fa-solid fa-triangle-exclamation',
    error: 'fa-solid fa-circle-xmark',
  };
  return icons[props.variant];
});
</script>

<style scoped>
.alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.alert > i {
  font-size: 1.1rem;
  margin-top: 0.1rem;
}

.alert-content {
  flex: 1;
}

.alert-title {
  display: block;
  margin-bottom: 0.25rem;
}

.alert-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: inherit;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.alert-close:hover {
  opacity: 1;
}

.alert--info {
  background: var(--info-bg);
  color: var(--info-ink);
}

.alert--success {
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.alert--warning {
  background: var(--warning-bg);
  color: var(--warning-ink);
}

.alert--error {
  background: var(--negative-bg);
  color: var(--negative-ink);
}
</style>
