<template>
  <span :class="['badge', `badge--${variant}`]">
    <i v-if="icon" :class="icon"></i>
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'success', 'warning', 'danger', 'info', 'popular'].includes(v),
  },
  icon: {
    type: String,
    default: '',
  },
});
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.badge--primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.badge--secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.badge--success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.badge--warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.badge--danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.badge--info {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.badge--popular {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
}
</style>
