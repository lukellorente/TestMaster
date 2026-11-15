<template>
  <section class="featured" aria-label="Test destacado">
    <div class="featured__body">
      <span class="featured__label">Destacado · {{ categoryLabel }}</span>
      <h2 class="featured__title">{{ test.title }}</h2>
      <p v-if="test.description" class="featured__desc">{{ test.description }}</p>
      <span class="featured__meta tabular">
        {{ meta }}<template v-if="test.user"> · {{ test.user.name }}</template>
      </span>
    </div>
    <router-link :to="`/test/${test.id}`" class="btn btn--lg">Ver test</router-link>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { formatTestMeta } from '../../utils/tests';

const props = defineProps({
  test: { type: Object, required: true },
});

const categoryLabel = computed(() => (props.test.categories || []).map((c) => c.name).join(' · ') || 'General');
const meta = computed(() => formatTestMeta(props.test));
</script>

<style scoped>
.featured {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 32px;
  align-items: center;
  background: var(--accent-soft);
  border-radius: 12px;
  padding: 28px 32px;
}

.featured__body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.featured__label {
  font-size: var(--fs-meta);
  font-weight: 500;
  color: var(--accent-ink);
}

.featured__title {
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  margin: 0;
}

.featured__desc {
  color: var(--text-body);
  max-width: 640px;
  margin: 0;
}

.featured__meta {
  font-size: var(--fs-meta);
  color: var(--text-body);
}

@media (max-width: 720px) {
  .featured {
    grid-template-columns: minmax(0, 1fr);
    padding: 24px;
  }
}
</style>
