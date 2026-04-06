<template>
  <section v-if="test" class="featured-wrap">
    <p class="section-eyebrow">
      <i class="fa-solid fa-star"></i> Destacado
    </p>
    <router-link :to="`/test/${test.id}`" class="featured-card">
      <div class="featured-meta">
        <div class="cat-row">
          <span v-for="cat in test.categories" :key="cat.id" class="cat-badge">{{ cat.name }}</span>
          <span :class="['diff-badge', `diff-${test.difficulty}`]">
            {{ diffLabel(test.difficulty) }}
          </span>
        </div>
        <h2 class="featured-title">{{ test.title }}</h2>
        <p v-if="test.description" class="featured-desc">
          {{ truncate(test.description, 120) }}
        </p>
      </div>
      <div class="featured-numbers">
        <div class="feat-stat">
          <span class="feat-n">{{ test.questions_count }}</span>
          <span class="feat-l">preguntas</span>
        </div>
        <div class="feat-stat">
          <span class="feat-n">{{ test.completed_attempts_count }}</span>
          <span class="feat-l">intentos</span>
        </div>
        <div v-if="test.time_limit" class="feat-stat">
          <span class="feat-n">{{ Math.round(test.time_limit / 60) }}</span>
          <span class="feat-l">minutos</span>
        </div>
      </div>
      <span class="featured-arrow">
        <i class="fa-solid fa-arrow-right"></i>
      </span>
    </router-link>
  </section>
</template>

<script setup>
defineProps({
  test: {
    type: Object,
    required: true,
  },
});

const diffLabel = (d) => ({ easy: 'Fácil', medium: 'Medio', hard: 'Difícil' }[d] || d);
const truncate = (str, n) => (str && str.length > n ? str.slice(0, n).trimEnd() + '…' : str);
</script>

<style scoped>
/* ── Sección destacado ────────────────────────────────────────────────────── */
.section-eyebrow {
  font-size: 0.78rem;
  font-weight: 600;
  color: #667eea;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 0.6rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.featured-card {
  display: flex;
  align-items: center;
  gap: 2rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 1.5rem 2rem;
  text-decoration: none;
  transition: all 0.2s;
  flex-wrap: wrap;
}

.featured-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.12);
}

.featured-meta {
  flex: 1;
  min-width: 200px;
}

.cat-row {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 0.6rem;
}

.featured-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.4rem;
}

.featured-desc {
  font-size: 0.9rem;
  color: #718096;
  line-height: 1.55;
}

.featured-numbers {
  display: flex;
  gap: 2rem;
  flex-shrink: 0;
}

.feat-stat {
  text-align: center;
}

.feat-n {
  display: block;
  font-size: 1.4rem;
  font-weight: 600;
  color: #1a202c;
}

.feat-l {
  display: block;
  font-size: 0.75rem;
  color: #a0aec0;
  margin-top: 2px;
}

.featured-arrow {
  color: #667eea;
  font-size: 1.1rem;
  flex-shrink: 0;
}

/* ── Badges ───────────────────────────────────────────────────────────────── */
.cat-badge {
  font-size: 0.72rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
  background: #ebf0ff;
  color: #4c5fd5;
  white-space: nowrap;
}

.diff-badge {
  font-size: 0.72rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}

.diff-easy {
  background: #e8f5e9;
  color: #2e7d32;
}

.diff-medium {
  background: #fff8e1;
  color: #e65100;
}

.diff-hard {
  background: #fce4ec;
  color: #c62828;
}

/* ── Dark mode ────────────────────────────────────────────────────────────── */
body.dark-mode .featured-card {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .featured-title,
body.dark-mode .feat-n {
  color: var(--tm-text) !important;
}

body.dark-mode .featured-desc,
body.dark-mode .feat-l {
  color: var(--tm-muted) !important;
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .featured-card {
    flex-direction: column;
    padding: 1.25rem;
    gap: 1rem;
  }

  .featured-numbers {
    justify-content: space-around;
    width: 100%;
  }

  .featured-arrow {
    display: none;
  }
}
</style>
