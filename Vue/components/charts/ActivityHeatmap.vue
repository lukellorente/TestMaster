<template>
  <figure class="hm">
    <div class="hm__wrap" @pointerleave="hover = null">
      <div class="hm__months" aria-hidden="true" :style="{ gridTemplateColumns: columns }">
        <span v-for="m in monthLabels" :key="m.col" :style="{ gridColumn: `${m.col + 1} / span 3` }">{{ m.label }}</span>
      </div>

      <div class="hm__body">
        <div class="hm__weekdays" aria-hidden="true">
          <span v-for="(d, i) in ['L', '', 'X', '', 'V', '', '']" :key="i">{{ d }}</span>
        </div>

        <div
          ref="grid"
          class="hm__grid"
          role="img"
          :aria-label="summary"
          :style="{ gridTemplateColumns: columns }"
        >
          <template v-for="(week, w) in weeks" :key="w">
            <span
              v-for="cell in week"
              :key="cell.date"
              class="hm__cell"
              :class="[`is-l${cell.level}`, { 'is-future': cell.future, 'is-today': cell.date === today }]"
              :style="{ gridColumn: w + 1, gridRow: cell.weekday + 1 }"
              @pointerenter="onEnter($event, cell)"
            ></span>
          </template>
        </div>
      </div>

      <div v-if="hover" class="hm__tip" :style="tipStyle" aria-hidden="true">
        <strong>{{ hover.count ? plural(hover.count, 'sesión', 'sesiones') : 'Sin actividad' }}</strong>
        <span>{{ longDate(hover.date) }}</span>
      </div>
    </div>

    <figcaption class="hm__legend">
      <span>Menos</span>
      <span v-for="l in [0, 1, 2, 3, 4]" :key="l" class="hm__cell hm__cell--key" :class="`is-l${l}`" aria-hidden="true"></span>
      <span>Más</span>
    </figcaption>
  </figure>
</template>

<script setup>
import { computed, ref } from 'vue';
import { plural } from '../../utils/tests';

const props = defineProps({
  // { 'YYYY-MM-DD': número de intentos }
  days: { type: Object, required: true },
  // Hoy en la zona horaria del usuario (lo calcula el servidor)
  today: { type: String, required: true },
  weekCount: { type: Number, default: 26 },
});

const grid = ref(null);
const hover = ref(null);
const hoverPos = ref({ x: 0, y: 0 });

// Fechas como texto Y-m-d en UTC para no depender de la zona del navegador
const parse = (s) => new Date(`${s}T00:00:00Z`);
const fmt = (d) => d.toISOString().slice(0, 10);
const addDays = (d, n) => new Date(d.getTime() + n * 86400000);
const weekdayMon0 = (d) => (d.getUTCDay() + 6) % 7;

// Escala secuencial de un tono: 0 · 1 · 2 · 3-4 · 5+
const level = (n) => (n <= 0 ? 0 : n === 1 ? 1 : n === 2 ? 2 : n <= 4 ? 3 : 4);

// Columnas de hasta 16px: en móvil se encogen sin provocar scroll horizontal
const columns = computed(() => `repeat(${props.weekCount}, minmax(0, 16px))`);

const weeks = computed(() => {
  const today = parse(props.today);
  const start = addDays(today, -weekdayMon0(today) - (props.weekCount - 1) * 7);
  return Array.from({ length: props.weekCount }, (_, w) =>
    Array.from({ length: 7 }, (_, d) => {
      const date = addDays(start, w * 7 + d);
      const key = fmt(date);
      const count = Number(props.days[key] || 0);
      return { date: key, weekday: d, count, level: level(count), future: date > today };
    })
  );
});

const monthLabels = computed(() => {
  const labels = [];
  let prev = null;
  weeks.value.forEach((week, col) => {
    const month = parse(week[0].date).getUTCMonth();
    if (month !== prev) {
      // Evita una etiqueta cortada en la primera columna si el mes empieza justo después
      if (!(col === 0 && parse(week[6].date).getUTCDate() < 7 && weeks.value.length > 1)) {
        labels.push({ col, label: parse(week[0].date).toLocaleDateString('es-ES', { month: 'short', timeZone: 'UTC' }) });
      }
      prev = month;
    }
  });
  return labels;
});

const longDate = (s) => parse(s).toLocaleDateString('es-ES', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' });

const summary = computed(() => {
  const all = weeks.value.flat().filter((c) => !c.future);
  const active = all.filter((c) => c.count > 0).length;
  const sessions = all.reduce((s, c) => s + c.count, 0);
  return `Actividad de las últimas ${props.weekCount} semanas: ${plural(active, 'día', 'días')} con estudio, ${plural(sessions, 'sesión', 'sesiones')}.`;
});

function onEnter(e, cell) {
  if (cell.future) {
    hover.value = null;
    return;
  }
  const wrap = grid.value.closest('.hm__wrap').getBoundingClientRect();
  const r = e.target.getBoundingClientRect();
  hoverPos.value = { x: r.left - wrap.left + r.width / 2, y: r.top - wrap.top, flip: r.left - wrap.left > wrap.width - 160 };
  hover.value = cell;
}

const tipStyle = computed(() => ({
  left: `${hoverPos.value.x}px`,
  top: `${hoverPos.value.y}px`,
  transform: `translate(${hoverPos.value.flip ? '-100%' : '-50%'}, calc(-100% - 8px))`,
}));
</script>

<style scoped>
.hm {
  margin: 0;
}

.hm__wrap {
  position: relative;
}

.hm__months {
  display: grid;
  margin-left: 20px;
  gap: 3px;
  font-size: 11px;
  color: var(--muted);
  height: 16px;
}

.hm__months span {
  grid-row: 1;
  white-space: nowrap;
}

.hm__body {
  display: flex;
  gap: 4px;
}

.hm__weekdays {
  display: grid;
  grid-template-rows: repeat(7, 1fr);
  gap: 3px;
  width: 16px;
  font-size: 10px;
  color: var(--muted);
}

.hm__weekdays span {
  display: flex;
  align-items: center;
}

.hm__grid {
  flex: 1;
  min-width: 0;
  display: grid;
  grid-template-rows: repeat(7, auto);
  gap: 3px;
}

.hm__cell {
  aspect-ratio: 1;
  border-radius: 3px;
  background: var(--sunk);
}

/* Un solo tono, de claro a oscuro, mezclado con la superficie */
.hm__cell.is-l1 { background: color-mix(in srgb, var(--chart-1) 30%, var(--bg)); }
.hm__cell.is-l2 { background: color-mix(in srgb, var(--chart-1) 55%, var(--bg)); }
.hm__cell.is-l3 { background: color-mix(in srgb, var(--chart-1) 80%, var(--bg)); }
.hm__cell.is-l4 { background: var(--chart-1); }

.hm__cell.is-future {
  visibility: hidden;
}

.hm__cell.is-today {
  outline: 1px solid var(--text);
  outline-offset: 1px;
}

.hm__tip {
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
  color: var(--text-body);
  white-space: nowrap;
}

.hm__tip strong {
  color: var(--text);
}

.hm__legend {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
  margin-top: 10px;
  font-size: 11px;
  color: var(--muted);
}

.hm__cell--key {
  aspect-ratio: auto;
  width: 12px;
  height: 12px;
}
</style>
