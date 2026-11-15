<template>
  <div class="toast-stack" aria-live="polite" aria-atomic="false">
    <TransitionGroup name="toast">
      <div
        v-for="t in toasts"
        :key="t.id"
        :class="['toast', `toast--${t.variant}`]"
        :role="t.variant === 'error' ? 'alert' : 'status'"
      >
        <i :class="icons[t.variant]" aria-hidden="true"></i>
        <span>{{ t.message }}</span>
        <button type="button" class="toast__close" aria-label="Cerrar aviso" @click="dismiss(t.id)">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '../../composables/useToast';

const { toasts, dismiss } = useToast();

const icons = {
  info: 'fa-solid fa-circle-info',
  success: 'fa-solid fa-circle-check',
  error: 'fa-solid fa-circle-exclamation',
};
</script>
