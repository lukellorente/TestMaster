<template>
  <figure class="cc">
    <div ref="box" class="cc__plot" @pointerleave="hover = null">
      <svg v-if="width" :width="width" :height="HEIGHT" role="img" :aria-label="summary" class="cc__svg">
        <g aria-hidden="true">
          <template v-for="t in ticks" :key="t">
            <line :x1="PAD.left" :x2="width - PAD.right" :y1="y(t)" :y2="y(t)" class="cc__grid" />
            <text :x="PAD.left - 8" :y="y(t)" class="cc__tick" text-anchor="end" dominant-baseline="middle">{{ t }}</text>
          </template>
        </g>

        <!-- Columnas: ≤ 24px, extremo redondeado de 4px, base recta -->
        <path
          v-for="(d, i) in data"
          :key="d.date"
          :d="columnPath(i, d.value)"
          class="cc__col"
          :class="{ 'is-hover': hover === i }"
          aria-hidden="true"
        />

        <!-- Zonas de puntero más anchas que la columna -->
        <rect
          v-for="(d, i) in data"
          :key="`hit-${d.date}`"
          :x="PAD.left + i * band"
          :y="PAD.top"
          :width="band"
          :height="HEIGHT - PAD.top - PAD.bottom"
          fill="transparent"
          @pointerenter="hover = i"
        />

        <text :x="PAD.left" :y="HEIGHT - 6" class="cc__tick" aria-hidden="true">{{ shortDate(data[0]?.date) }}</text>
        <text :x="width - PAD.right" :y="HEIGHT - 6" class="cc__tick" text-anchor="end" aria-hidden="true">{{ shortDate(data[data.length - 1]?.date) }}</text>
      </svg>

      <div v-if="hover !== null" class="cc__tip" :style="tipStyle" aria-hidden="true">
        <span>{{ shortDate(data[hover].date) }}</span>
        <strong class="tabular">{{ data[hover].value }}</strong>
      </div>
    </div>

    <table class="sr-only">
      <caption>{{ summary }}</caption>
      <thead><tr><th>Día</th><th>{{ label }}</th></tr></thead>
      <tbody>
        <tr v-for="d in data" :key="d.date"><td>{{ shortDate(d.date) }}</td><td>{{ d.value }}</td></tr>
      </tbody>
    </table>
  </figure>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  // [{ date: 'YYYY-MM-DD', value: number }]
  data: { type: Array, required: true },
  label: { type: String, required: true },
});

const HEIGHT = 160;
const PAD = { top: 12, right: 8, bottom: 24, left: 32 };

const box = ref(null);
const width = ref(0);
const hover = ref(null);
let observer = null;

onMounted(() => {
  observer = new ResizeObserver(([e]) => { width.value = Math.round(e.contentRect.width); });
  observer.observe(box.value);
});
onBeforeUnmount(() => observer?.disconnect());

// Máximo redondeado a un número limpio para los ticks
const max = computed(() => {
  const m = Math.max(1, ...props.data.map((d) => d.value));
  const step = m <= 5 ? 1 : m <= 10 ? 2 : m <= 50 ? 10 : 10 ** Math.floor(Math.log10(m));
  return Math.ceil(m / step) * step;
});
const ticks = computed(() => [0, max.value / 2, max.value].map((t) => Math.round(t)).filter((t, i, a) => a.indexOf(t) === i));

const band = computed(() => (width.value - PAD.left - PAD.right) / Math.max(1, props.data.length));
const y = (v) => PAD.top + (1 - v / max.value) * (HEIGHT - PAD.top - PAD.bottom);

function columnPath(i, value) {
  if (!value) return '';
  // Hueco de 2px entre columnas y ancho máximo de 24px
  const w = Math.max(1, Math.min(24, band.value - 2));
  const x = PAD.left + i * band.value + (band.value - w) / 2;
  const top = y(value);
  const base = y(0);
  const r = Math.min(4, w / 2, base - top);
  return `M${x},${base} V${top + r} Q${x},${top} ${x + r},${top} H${x + w - r} Q${x + w},${top} ${x + w},${top + r} V${base} Z`;
}

const shortDate = (s) => (s ? new Date(`${s}T00:00:00Z`).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', timeZone: 'UTC' }) : '');

const summary = computed(() => {
  const total = props.data.reduce((s, d) => s + d.value, 0);
  return `${props.label} por día en los últimos ${props.data.length} días: ${total} en total.`;
});

const tipStyle = computed(() => {
  const i = hover.value;
  const x = PAD.left + (i + 0.5) * band.value;
  const flip = x > width.value - 100;
  return {
    left: `${x}px`,
    top: `${y(props.data[i].value)}px`,
    transform: `translate(${flip ? 'calc(-100% - 8px)' : '8px'}, -100%)`,
  };
});
</script>

<style scoped>
.cc {
  margin: 0;
}

.cc__plot {
  position: relative;
  height: 160px;
}

.cc__svg {
  display: block;
  overflow: visible;
}

.cc__grid {
  stroke: var(--border);
  stroke-width: 1;
}

.cc__tick {
  fill: var(--muted);
  font-size: 11px;
}

.cc__col {
  fill: var(--chart-1);
}

.cc__col.is-hover {
  fill: var(--accent-hover);
}

.cc__tip {
  position: absolute;
  z-index: 2;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  padding: 6px 10px;
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  background: var(--bg);
  box-shadow: 0 4px 16px rgb(0 0 0 / 0.08);
  font-size: var(--fs-meta);
  color: var(--muted);
  white-space: nowrap;
}

.cc__tip strong {
  color: var(--text);
  font-size: 0.95rem;
}
</style>
