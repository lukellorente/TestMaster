<template>
  <div class="page-wrap lg">
    <header class="lg__head">
      <h1 class="lg__title">{{ title }}</h1>
      <p class="lg__intro">{{ intro }}</p>
      <p v-if="meta.length" class="lg__meta">{{ meta.join(' · ') }}</p>
    </header>

    <div class="lg__layout">
      <!-- Índice -->
      <nav class="lg__toc" aria-labelledby="lg-toc-title">
        <p id="lg-toc-title" class="lg__toc-title">En esta página</p>
        <ol class="lg__toc-list">
          <li v-for="s in sections" :key="s.id">
            <a
              :href="`#${s.id}`"
              class="lg__toc-link"
              :aria-current="active === s.id ? 'location' : undefined"
              @click.prevent="scrollTo(s.id)"
            >{{ s.title }}</a>
          </li>
        </ol>
      </nav>

      <article class="lg__body">
        <!-- Resumen -->
        <section v-if="summary" class="lg__summary" aria-labelledby="lg-summary">
          <h2 id="lg-summary" class="lg__summary-title">{{ summary.title }}</h2>
          <p v-if="summary.text" class="lg__p">{{ summary.text }}</p>
          <ul v-if="summary.points" class="lg__points">
            <li v-for="p in summary.points" :key="p.text" :class="`is-${p.type}`">
              <i :class="pointIcon[p.type]" aria-hidden="true"></i>{{ p.text }}
            </li>
          </ul>
        </section>

        <section v-for="s in sections" :id="s.id" :key="s.id" class="lg__section" :aria-labelledby="`h-${s.id}`">
          <h2 :id="`h-${s.id}`" class="lg__h2">{{ s.title }}</h2>

          <p v-for="(para, i) in s.content || []" :key="`c${i}`" class="lg__p" v-html="para"></p>

          <div v-if="s.table" class="lg__table-wrap">
            <table class="lg__table">
              <thead>
                <tr><th v-for="col in s.table.headers" :key="col" scope="col">{{ col }}</th></tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in s.table.rows" :key="i">
                  <td v-for="(cell, j) in row" :key="j">{{ cell }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <ul v-if="s.list" class="lg__list">
            <li v-for="(item, i) in s.list" :key="i" v-html="item.text || item"></li>
          </ul>

          <div v-for="sub in s.subsections || []" :key="sub.title" class="lg__sub">
            <h3 class="lg__h3">{{ sub.title }}</h3>
            <p v-for="(p, i) in sub.content || []" :key="i" class="lg__p" v-html="p"></p>
            <ul v-if="sub.list" class="lg__list">
              <li v-for="(item, i) in sub.list" :key="i" v-html="item.text || item"></li>
            </ul>
          </div>

          <p v-for="(para, i) in s.content2 || []" :key="`d${i}`" class="lg__p" v-html="para"></p>

          <div v-if="s.highlight" class="lg__note" :class="`is-${s.highlight.type}`">
            <i :class="noteIcon[s.highlight.type] || noteIcon.info" aria-hidden="true"></i>
            <p v-html="s.highlight.text"></p>
          </div>
        </section>

        <section v-if="footer" class="lg__end" aria-labelledby="lg-end">
          <h2 id="lg-end" class="lg__summary-title">{{ footer.title }}</h2>
          <p class="lg__p">{{ footer.text }}</p>
          <div class="lg__end-actions">
            <template v-for="a in footer.actions" :key="a.label">
              <a v-if="a.href" :href="a.href" class="btn" :class="a.primary ? '' : 'btn--secondary'">{{ a.label }}</a>
              <router-link v-else :to="a.to" class="btn" :class="a.primary ? '' : 'btn--secondary'">{{ a.label }}</router-link>
            </template>
          </div>
        </section>
      </article>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
  title: { type: String, required: true },
  intro: { type: String, default: '' },
  meta: { type: Array, default: () => [] },
  summary: { type: Object, default: null },
  sections: { type: Array, required: true },
  footer: { type: Object, default: null },
});

const pointIcon = {
  ok: 'fa-solid fa-check',
  warn: 'fa-solid fa-triangle-exclamation',
  info: 'fa-solid fa-circle-info',
};

const noteIcon = {
  info: 'fa-solid fa-circle-info',
  success: 'fa-solid fa-circle-check',
  warning: 'fa-solid fa-triangle-exclamation',
};

const active = ref(props.sections[0]?.id ?? '');

const scrollTo = (id) => {
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  history.replaceState(null, '', `#${id}`);
  active.value = id;
};

// Marca en el índice la sección que se está leyendo
let observer = null;
onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      const visible = entries.filter((e) => e.isIntersecting).sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
      if (visible[0]) active.value = visible[0].target.id;
    },
    { rootMargin: '-80px 0px -65% 0px' }
  );
  props.sections.forEach((s) => {
    const el = document.getElementById(s.id);
    if (el) observer.observe(el);
  });

  const hash = decodeURIComponent(location.hash.slice(1));
  if (hash && props.sections.some((s) => s.id === hash)) {
    requestAnimationFrame(() => document.getElementById(hash)?.scrollIntoView());
  }
});

onBeforeUnmount(() => observer?.disconnect());
</script>

<style scoped>
.lg {
  padding-top: 48px;
}

.lg__head {
  max-width: 760px;
  margin-bottom: 48px;
}

.lg__title {
  font-size: 2.25rem;
  letter-spacing: -0.025em;
  margin: 0 0 12px;
}

.lg__intro {
  font-size: var(--fs-lead);
  color: var(--text-body);
  margin: 0 0 12px;
}

.lg__meta {
  font-size: var(--fs-small);
  color: var(--muted);
  margin: 0;
}

.lg__layout {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  gap: 64px;
  align-items: start;
}

/* Índice */
.lg__toc {
  position: sticky;
  top: calc(var(--header-h) + 24px);
  max-height: calc(100vh - var(--header-h) - 48px);
  overflow-y: auto;
}

.lg__toc-title {
  font-size: var(--fs-meta);
  font-weight: 600;
  color: var(--muted);
  margin: 0 0 8px;
}

.lg__toc-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.lg__toc-link {
  display: block;
  padding: 6px 0 6px 12px;
  border-left: 2px solid var(--border);
  color: var(--muted);
  font-size: var(--fs-small);
  text-decoration: none;
  transition: color 0.12s, border-color 0.12s;
}

.lg__toc-link:hover {
  color: var(--text);
}

.lg__toc-link[aria-current] {
  color: var(--text);
  font-weight: 500;
  border-left-color: var(--accent);
}

/* Cuerpo */
.lg__body {
  max-width: 720px;
  display: flex;
  flex-direction: column;
  gap: 48px;
}

.lg__summary,
.lg__end {
  background: var(--raised);
  border-radius: var(--radius-panel);
  padding: 24px 28px;
}

.lg__summary-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 8px;
}

.lg__points {
  list-style: none;
  margin: 8px 0 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px 24px;
}

.lg__points li {
  display: flex;
  gap: 10px;
  align-items: baseline;
  font-size: var(--fs-small);
}

.lg__points .is-ok i { color: var(--positive); }
.lg__points .is-warn i { color: var(--warning-ink); }
.lg__points .is-info i { color: var(--accent); }

.lg__section {
  scroll-margin-top: calc(var(--header-h) + 24px);
}

.lg__h2 {
  font-size: 1.375rem;
  font-weight: 600;
  letter-spacing: -0.01em;
  margin: 0 0 16px;
}

.lg__h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 24px 0 8px;
}

.lg__p {
  font-size: 1rem;
  line-height: 1.7;
  color: var(--text-body);
  margin: 0 0 14px;
}

.lg__p :deep(strong),
.lg__list :deep(strong),
.lg__note :deep(strong) {
  color: var(--text);
  font-weight: 600;
}

.lg__list {
  margin: 0 0 14px;
  padding-left: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: var(--text-body);
  line-height: 1.65;
}

.lg__list li::marker {
  color: var(--muted);
}

.lg__table-wrap {
  overflow-x: auto;
  margin: 4px 0 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius-card);
}

.lg__table {
  width: 100%;
  border-collapse: collapse;
  font-size: var(--fs-small);
}

.lg__table th {
  text-align: left;
  font-weight: 500;
  color: var(--muted);
  background: var(--raised);
  padding: 10px 14px;
  white-space: nowrap;
}

.lg__table td {
  padding: 10px 14px;
  border-top: 1px solid var(--rule);
  color: var(--text-body);
  vertical-align: top;
}

.lg__note {
  display: flex;
  gap: 12px;
  align-items: baseline;
  padding: 14px 16px;
  border-radius: var(--radius-card);
  font-size: var(--fs-small);
  line-height: 1.6;
}

.lg__note p {
  margin: 0;
}

.lg__note.is-info {
  background: var(--info-bg);
  color: var(--info-ink);
}

.lg__note.is-success {
  background: var(--positive-bg);
  color: var(--positive-ink);
}

.lg__note.is-warning {
  background: var(--warning-bg);
  color: var(--warning-ink);
}

.lg__note :deep(strong) {
  color: inherit;
}

.lg__end-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 8px;
}

@media (max-width: 960px) {
  .lg__layout {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .lg__toc {
    position: static;
    max-height: none;
    background: var(--raised);
    border-radius: var(--radius-panel);
    padding: 16px 20px;
  }
}

@media (max-width: 640px) {
  .lg {
    padding-top: 32px;
  }

  .lg__title {
    font-size: 1.75rem;
  }

  .lg__points {
    grid-template-columns: minmax(0, 1fr);
  }

  .lg__summary,
  .lg__end {
    padding: 20px;
  }
}
</style>
