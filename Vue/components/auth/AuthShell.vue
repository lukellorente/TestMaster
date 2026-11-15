<template>
  <div class="auth page-wrap">
    <section class="auth__card" :aria-labelledby="titleId">
      <h1 :id="titleId" class="auth__title">{{ title }}</h1>
      <p v-if="subtitle" class="auth__sub">{{ subtitle }}</p>
      <slot />
    </section>
    <p v-if="$slots.footer" class="auth__footer"><slot name="footer" /></p>
  </div>
</template>

<script setup>
import { useId } from 'vue';

defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
});

const titleId = `auth-${useId()}`;
</script>

<style scoped>
.auth {
  padding-top: 72px;
  padding-bottom: 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

.auth__card {
  width: 100%;
  max-width: 420px;
  background: var(--raised);
  border-radius: 12px;
  padding: 32px;
  display: flex;
  flex-direction: column;
}

.auth__title {
  font-size: 1.625rem;
  margin: 0 0 6px;
}

.auth__sub {
  color: var(--text-body);
  margin: 0 0 24px;
}

.auth__footer {
  font-size: var(--fs-small);
  color: var(--muted);
  margin: 0;
}

.auth__footer :deep(a) {
  font-weight: 600;
  text-decoration: none;
}

.auth__card :deep(.form-input) {
  background-color: var(--bg);
}

@media (max-width: 480px) {
  .auth {
    padding-top: 32px;
  }

  .auth__card {
    padding: 24px 20px;
  }
}
</style>
