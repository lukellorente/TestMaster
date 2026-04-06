<template>
  <section class="hero">
    <div class="hero-body">
      <h1 class="hero-title">Pon a prueba lo que sabes</h1>
      <p class="hero-sub">
        Tests creados por la comunidad en programación, ciencias, historia y mucho más.
        Realiza un test, comprueba tus resultados y mejora.
      </p>
      <div class="hero-actions">
        <router-link v-if="isLoggedIn" to="/create-test" class="btn btn-primary">
          <i class="fa-solid fa-plus"></i> Crear test
        </router-link>
        <router-link v-else to="/register" class="btn btn-primary">
          <i class="fa-solid fa-rocket"></i> Empezar gratis
        </router-link>
        <router-link to="/login" v-if="!isLoggedIn" class="btn btn-ghost">
          Iniciar sesión
        </router-link>
      </div>
    </div>

    <div class="hero-stats">
      <div class="stat-chip">
        <span class="stat-num">{{ stats?.total_tests?.toLocaleString() ?? '—' }}</span>
        <span class="stat-lbl">tests</span>
      </div>
      <div class="stat-chip">
        <span class="stat-num">{{ stats?.total_attempts?.toLocaleString() ?? '—' }}</span>
        <span class="stat-lbl">intentos</span>
      </div>
      <div class="stat-chip">
        <span class="stat-num">{{ stats?.total_users?.toLocaleString() ?? '—' }}</span>
        <span class="stat-lbl">usuarios</span>
      </div>
    </div>
  </section>
</template>

<script setup>
defineProps({
  isLoggedIn: {
    type: Boolean,
    required: true,
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
});
</script>

<style scoped>
/* ── Hero ────────────────────────────────────────────────────────────────── */
.hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 2rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 2rem 2.25rem;
  flex-wrap: wrap;
}

.hero-title {
  font-size: 1.65rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.5rem;
}

.hero-sub {
  color: #718096;
  font-size: 0.95rem;
  line-height: 1.65;
  max-width: 440px;
  margin-bottom: 1.25rem;
}

.hero-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.hero-stats {
  display: flex;
  gap: 1rem;
  flex-shrink: 0;
  flex-wrap: wrap;
}

.stat-chip {
  background: #f8f9fa;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.9rem 1.4rem;
  text-align: center;
  min-width: 100px;
}

.stat-num {
  display: block;
  font-size: 1.5rem;
  font-weight: 600;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-lbl {
  display: block;
  font-size: 0.8rem;
  color: #a0aec0;
  margin-top: 2px;
}

/* ── Botones ──────────────────────────────────────────────────────────────── */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.55rem 1.25rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.35);
}

.btn-ghost {
  background: transparent;
  color: #4a5568;
  border: 1px solid #e2e8f0;
}

.btn-ghost:hover {
  background: #f8f9fa;
  border-color: #cbd5e0;
}

/* ── Dark mode ────────────────────────────────────────────────────────────── */
body.dark-mode .hero {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .hero-title {
  color: var(--tm-text) !important;
}

body.dark-mode .hero-sub,
body.dark-mode .stat-lbl {
  color: var(--tm-muted) !important;
}

body.dark-mode .stat-chip {
  background: var(--tm-bg);
  border-color: var(--tm-border);
}

body.dark-mode .btn-ghost {
  color: var(--tm-muted);
  border-color: var(--tm-border);
}

body.dark-mode .btn-ghost:hover {
  background: var(--tm-hover);
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 1.5rem;
  }

  .hero-stats {
    width: 100%;
    justify-content: space-between;
  }

  .stat-chip {
    flex: 1;
    min-width: 0;
  }
}
</style>
