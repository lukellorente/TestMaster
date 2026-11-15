<template>
  <section class="hero">
    <div class="hero__copy">
      <h1 class="hero__title">Pon a prueba lo que sabes</h1>
      <p class="hero__sub">
        Tests creados por la comunidad sobre programación, ciencias, historia y mucho más.
        Respondes, ves tu nota al momento y repasas cada fallo con su explicación.
      </p>
      <div class="hero__actions">
        <router-link to="/register" class="btn btn--lg">Crear cuenta gratis</router-link>
        <a href="#tests" class="btn btn--secondary btn--lg">Ver tests</a>
      </div>
      <p v-if="hasStats" class="hero__stats tabular">
        {{ fmt(stats.total_tests) }} tests · {{ fmt(stats.total_attempts) }} intentos · {{ fmt(stats.total_users) }} usuarios
      </p>
    </div>

    <!-- Ilustración: una pregunta tal y como se ve al hacer un test -->
    <div class="hero__demo panel" aria-hidden="true">
      <div class="demo__top">
        <span class="demo__test">Fundamentos de SQL</span>
        <span class="demo__timer tabular"><i class="fa-regular fa-clock"></i>08:42</span>
      </div>
      <div class="demo__progress">
        <span>Pregunta 5 de 12</span>
        <div class="progress"><div class="progress__bar" style="width: 41.6%"></div></div>
      </div>
      <p class="demo__q">¿Qué cláusula filtra los grupos después de un GROUP BY?</p>
      <div class="demo__opts">
        <div class="demo__opt"><span class="demo__key">A</span>WHERE</div>
        <div class="demo__opt is-sel"><span class="demo__key">B</span>HAVING</div>
        <div class="demo__opt"><span class="demo__key">C</span>ORDER BY</div>
        <div class="demo__opt"><span class="demo__key">D</span>LIMIT</div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: { type: Object, default: () => ({}) },
});

const hasStats = computed(() => props.stats && props.stats.total_tests != null);
const fmt = (n) => Number(n ?? 0).toLocaleString('es-ES');
</script>

<style scoped>
.hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 64px;
  align-items: center;
}

.hero__copy {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.hero__title {
  font-size: var(--fs-display);
  line-height: 1.04;
  font-weight: 600;
  letter-spacing: -0.035em;
  margin: 0;
}

.hero__sub {
  font-size: 1.125rem;
  color: var(--text-body);
  max-width: 480px;
  margin: 0;
}

.hero__actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 8px;
}

.hero__stats {
  font-size: var(--fs-small);
  color: var(--muted);
  margin: 0;
}

.hero__demo {
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.demo__top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: var(--fs-meta);
}

.demo__test {
  font-weight: 500;
}

.demo__timer {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg);
  color: var(--muted);
  padding: 4px 10px;
  border-radius: var(--radius-pill);
}

.demo__progress {
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: var(--fs-meta);
  color: var(--muted);
}

.demo__q {
  font-size: 1.3rem;
  font-weight: 500;
  line-height: 1.35;
  margin: 0;
}

.demo__opts {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.demo__opt {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 50px;
  padding: 10px 14px;
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  background: var(--bg);
}

.demo__key {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-control);
  background: var(--raised);
  font-size: var(--fs-meta);
  font-weight: 600;
  color: var(--muted);
}

.demo__opt.is-sel {
  border-color: var(--accent);
  background: var(--accent-soft);
  box-shadow: inset 0 0 0 1px var(--accent);
}

.demo__opt.is-sel .demo__key {
  background: var(--accent);
  color: var(--on-accent);
}

@media (max-width: 960px) {
  .hero {
    grid-template-columns: minmax(0, 1fr);
    gap: 40px;
  }
}
</style>
