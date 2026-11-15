<template>
  <figure class="sc">
    <div ref="box" class="sc__plot" @pointermove="onMove" @pointerleave="hover = null">
      <svg
        v-if="width"
        :width="width"
        :height="HEIGHT"
        role="img"
        :aria-label="summary"
        class="sc__svg"
      >
        <!-- Rejilla: 0, 50 y 100 % -->
        <g class="sc__grid" aria-hidden="true">
          <template v-for="t in [0, 50, 100]" :key="t">
            <line :x1="PAD.left" :x2="width - PAD.right" :y1="y(t)" :y2="y(t)" />
            <text :x="PAD.left - 8" :y="y(t)" class="sc__tick" text-anchor="end" dominant-baseline="middle">{{ t }}</text>
          </template>
        </g>

        <!-- Nota para aprobar -->
        <g class="sc__pass" aria-hidden="true">
          <line :x1="PAD.left" :x2="width - PAD.right" :y1="y(passScore)" :y2="y(passScore)" />
          <!-- A la izquierda: a la derecha va la etiqueta del último intento -->
          <text :x="PAD.left + 6" :y="y(passScore) - 6">Aprobado {{ passScore }} %</text>
        </g>

        <path v-if="points.length > 1" :d="areaPath" class="sc__area" aria-hidden="true" />
        <path v-if="points.length > 1" :d="linePath" class="sc__line" aria-hidden="true" />

        <!-- Cursor -->
        <line
          v-if="hover !== null"
          :x1="x(hover)" :x2="x(hover)" :y1="PAD.top" :y2="HEIGHT - PAD.bottom"
          class="sc__cross"
          aria-hidden="true"
        />

        <circle
          v-for="(p, i) in points"
          :key="p.id"
          :cx="x(i)"
          :cy="y(p.score)"
          :r="hover === i ? 6 : 4"
          class="sc__dot"
          aria-hidden="true"
        />

        <!-- Etiqueta solo en el último punto -->
        <text
          :x="x(points.length - 1)"
          :y="y(last.score) - 12"
          :text-anchor="points.length > 1 ? 'end' : 'middle'"
          class="sc__end tabular"
          aria-hidden="true"
        >{{ formatScore(last.score) }}</text>

        <!-- Eje X: primer y último intento -->
        <text :x="x(0)" :y="HEIGHT - 6" class="sc__tick" :text-anchor="points.length > 1 ? 'start' : 'middle'" aria-hidden="true">
          {{ shortDate(points[0].completed_at) }}
        </text>
        <text v-if="points.length > 1" :x="x(points.length - 1)" :y="HEIGHT - 6" class="sc__tick" text-anchor="end" aria-hidden="true">
          {{ shortDate(last.completed_at) }}
        </text>
      </svg>

      <div
        v-if="hover !== null"
        class="sc__tip"
        :style="tipStyle"
        aria-hidden="true"
      >
        <span class="sc__tip-title">Intento {{ hover + 1 }} · {{ shortDate(points[hover].completed_at) }}</span>
        <strong class="tabular">{{ formatScore(points[hover].score) }}</strong>
        <span>{{ points[hover].passed ? 'Aprobado' : 'No aprobado' }}</span>
      </div>
    </div>

    <!-- Vista de tabla para lectores de pantalla -->
    <table class="sr-only">
      <caption>{{ summary }}</caption>
      <thead><tr><th>Intento</th><th>Fecha</th><th>Nota</th><th>Resultado</th></tr></thead>
      <tbody>
        <tr v-for="(p, i) in points" :key="p.id">
          <td>{{ i + 1 }}</td>
          <td>{{ shortDate(p.completed_at) }}</td>
          <td><router-link :to="`/results/${p.id}`">{{ formatScore(p.score) }}</router-link></td>
          <td>{{ p.passed ? 'Aprobado' : 'No aprobado' }}</td>
        </tr>
      </tbody>
    </table>
  </figure>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  // [{ id, score, passed, completed_at }] en orden cronológico
  points: { type: Array, required: true },
  passScore: { type: Number, default: 50 },
});

const HEIGHT = 200;
const PAD = { top: 20, right: 16, bottom: 28, left: 36 };

const box = ref(null);
const width = ref(0);
const hover = ref(null);
let observer = null;

onMounted(() => {
  observer = new ResizeObserver(([entry]) => {
    width.value = Math.round(entry.contentRect.width);
  });
  observer.observe(box.value);
});

onBeforeUnmount(() => observer?.disconnect());

const last = computed(() => props.points[props.points.length - 1]);

const x = (i) => {
  const n = props.points.length;
  if (n === 1) return (PAD.left + width.value - PAD.right) / 2;
  return PAD.left + (i / (n - 1)) * (width.value - PAD.left - PAD.right);
};
const y = (score) => PAD.top + (1 - Math.min(100, Math.max(0, score)) / 100) * (HEIGHT - PAD.top - PAD.bottom);

const linePath = computed(() => props.points.map((p, i) => `${i ? 'L' : 'M'}${x(i)},${y(p.score)}`).join(' '));
const areaPath = computed(() =>
  `${linePath.value} L${x(props.points.length - 1)},${y(0)} L${x(0)},${y(0)} Z`
);

const formatScore = (s) => `${Math.round(Number(s))} %`;
const shortDate = (iso) => new Date(iso).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });

const summary = computed(() => {
  const first = props.points[0];
  const n = props.points.length;
  if (n === 1) return `Tu nota en este test: ${formatScore(first.score)}.`;
  return `Evolución de tus notas en ${n} intentos: de ${formatScore(first.score)} a ${formatScore(last.value.score)}.`;
});

// El punto más cercano al puntero en horizontal
function onMove(e) {
  const rect = box.value.getBoundingClientRect();
  const px = e.clientX - rect.left;
  let best = 0;
  props.points.forEach((_, i) => {
    if (Math.abs(x(i) - px) < Math.abs(x(best) - px)) best = i;
  });
  hover.value = best;
}

const tipStyle = computed(() => {
  if (hover.value === null) return {};
  const left = x(hover.value);
  const flip = left > width.value - 150;
  return {
    left: `${left}px`,
    top: `${y(props.points[hover.value].score)}px`,
    transform: `translate(${flip ? 'calc(-100% - 12px)' : '12px'}, -50%)`,
  };
});
</script>

<style scoped>
.sc {
  margin: 0;
}

.sc__plot {
  position: relative;
  height: 200px;
  touch-action: pan-y;
}

.sc__svg {
  display: block;
  overflow: visible;
}

.sc__grid line {
  stroke: var(--border);
  stroke-width: 1;
}

.sc__tick {
  fill: var(--muted);
  font-size: 11px;
}

.sc__pass line {
  stroke: var(--muted);
  stroke-width: 1;
}

.sc__pass text {
  fill: var(--muted);
  font-size: 11px;
}

.sc__area {
  fill: var(--chart-wash);
}

.sc__line {
  fill: none;
  stroke: var(--chart-1);
  stroke-width: 2;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.sc__dot {
  fill: var(--chart-1);
  stroke: var(--bg);
  stroke-width: 2;
  transition: r 0.1s;
}

.sc__cross {
  stroke: var(--control);
  stroke-width: 1;
}

.sc__end {
  fill: var(--text);
  font-size: 12px;
  font-weight: 600;
}

.sc__tip {
  position: absolute;
  z-index: 2;
  pointer-events: none;
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 8px 12px;
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  background: var(--bg);
  box-shadow: 0 4px 16px rgb(0 0 0 / 0.08);
  font-size: var(--fs-small);
  color: var(--text-body);
  white-space: nowrap;
}

.sc__tip-title {
  color: var(--muted);
  font-size: var(--fs-meta);
}

.sc__tip strong {
  color: var(--text);
  font-size: 1rem;
}
</style>
