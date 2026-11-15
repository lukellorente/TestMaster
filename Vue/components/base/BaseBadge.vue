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
  padding: 2px 10px;
  border-radius: var(--radius-pill);
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1.5;
  white-space: nowrap;
}

.badge--primary,
.badge--info { background: var(--accent-soft); color: var(--accent-ink); }
.badge--secondary,
.badge--popular { background: var(--sunk); color: var(--muted); }
.badge--success { background: var(--positive-bg); color: var(--positive-ink); }
.badge--warning { background: var(--warning-bg); color: var(--warning-ink); }
.badge--danger { background: var(--negative-bg); color: var(--negative-ink); }
</style>
