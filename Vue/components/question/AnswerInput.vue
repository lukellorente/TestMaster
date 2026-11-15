<template>
  <!-- Opción múltiple y verdadero/falso: radios reales, estilados como tarjetas -->
  <fieldset v-if="question.type === 'multiple_choice' || question.type === 'true_false'" class="ans">
    <legend class="sr-only">Respuestas a la pregunta {{ number }}</legend>
    <label
      v-for="(opt, i) in choices"
      :key="opt.value"
      class="ans__opt"
      :class="{ 'is-sel': modelValue === opt.value }"
    >
      <input
        type="radio"
        class="sr-only"
        :name="`q_${question.id}`"
        :value="opt.value"
        :checked="modelValue === opt.value"
        @change="$emit('update:modelValue', opt.value)"
      />
      <span class="ans__key" aria-hidden="true">{{ String.fromCharCode(65 + i) }}</span>
      <span class="ans__text">{{ opt.label }}</span>
    </label>
  </fieldset>

  <!-- Selección múltiple: checkboxes -->
  <fieldset v-else-if="question.type === 'multiple_select'" class="ans" :aria-describedby="`q_${question.id}_hint`">
    <legend class="sr-only">Respuestas a la pregunta {{ number }}</legend>
    <p :id="`q_${question.id}_hint`" class="ans__hint">Marca todas las respuestas correctas.</p>
    <label
      v-for="(opt, i) in choices"
      :key="opt.value"
      class="ans__opt"
      :class="{ 'is-sel': selected.includes(opt.value) }"
    >
      <input
        type="checkbox"
        class="sr-only"
        :value="opt.value"
        :checked="selected.includes(opt.value)"
        @change="toggle(opt.value)"
      />
      <span class="ans__key ans__key--box" aria-hidden="true">
        <i v-if="selected.includes(opt.value)" class="fa-solid fa-check"></i>
        <template v-else>{{ String.fromCharCode(65 + i) }}</template>
      </span>
      <span class="ans__text">{{ opt.label }}</span>
    </label>
  </fieldset>

  <!-- Rellenar huecos: un campo por hueco, numerado igual que en el enunciado -->
  <fieldset v-else-if="question.type === 'fill_blank'" class="ans ans--blanks">
    <legend class="sr-only">Huecos de la pregunta {{ number }}</legend>
    <div v-for="n in blankCount" :key="n" class="ans__blank">
      <label :for="`q_${question.id}_b${n}`" class="ans__blank-n tabular">({{ n }})</label>
      <input
        :id="`q_${question.id}_b${n}`"
        class="ans__input"
        type="text"
        autocomplete="off"
        :aria-label="`Hueco ${n}`"
        :placeholder="`Hueco ${n}`"
        :value="blanks[n - 1] ?? ''"
        @input="setBlank(n - 1, $event.target.value)"
      />
    </div>
  </fieldset>

  <!-- Ordenar elementos: lista con botones de subir/bajar (accesible con teclado y en móvil) -->
  <div v-else-if="question.type === 'order_items'" class="ans">
    <p :id="`q_${question.id}_hint`" class="ans__hint">Ordena los elementos con las flechas. El primero va arriba.</p>
    <ol class="ans__order" :aria-describedby="`q_${question.id}_hint`">
      <li v-for="(item, i) in orderedItems" :key="item.id" class="ans__order-item" :class="{ 'is-sel': hasOrder }">
        <span class="ans__key tabular" aria-hidden="true">{{ i + 1 }}</span>
        <span class="ans__text">{{ item.content }}</span>
        <span class="ans__order-tools">
          <button
            type="button"
            class="icon-btn"
            :disabled="i === 0"
            :aria-label="`Subir «${item.content}» a la posición ${i}`"
            @click="move(i, -1)"
          ><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
          <button
            type="button"
            class="icon-btn"
            :disabled="i === orderedItems.length - 1"
            :aria-label="`Bajar «${item.content}» a la posición ${i + 2}`"
            @click="move(i, 1)"
          ><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></button>
        </span>
      </li>
    </ol>
    <p class="sr-only" aria-live="polite">{{ liveMessage }}</p>
    <button v-if="!hasOrder" type="button" class="btn btn--ghost ans__keep" @click="emitOrder(orderedItems)">
      <i class="fa-solid fa-check" aria-hidden="true"></i>Dar este orden por bueno
    </button>
  </div>

  <!-- Numérica -->
  <div v-else-if="question.type === 'numeric'" class="ans ans--short">
    <label :for="`q_${question.id}_num`" class="ans__label">Tu respuesta</label>
    <input
      :id="`q_${question.id}_num`"
      class="ans__input ans__input--num tabular"
      type="text"
      inputmode="decimal"
      autocomplete="off"
      placeholder="Por ejemplo, 3,14"
      :value="modelValue ?? ''"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>

  <!-- Respuesta corta -->
  <div v-else class="ans ans--short">
    <label :for="`q_${question.id}_text`" class="ans__label">Tu respuesta</label>
    <input
      :id="`q_${question.id}_text`"
      class="ans__input"
      type="text"
      autocomplete="off"
      placeholder="Escribe la respuesta"
      :value="modelValue ?? ''"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  question: { type: Object, required: true },
  number: { type: Number, required: true },
  // Texto (opción, V/F, corta, numérica) o lista (selección múltiple, huecos, orden)
  modelValue: { type: [String, Array], default: undefined },
});

const emit = defineEmits(['update:modelValue']);

// El backend espera el id de la opción (string) o 'true' / 'false'
const choices = computed(() => {
  if (props.question.type === 'true_false') {
    return [
      { value: 'true', label: 'Verdadero' },
      { value: 'false', label: 'Falso' },
    ];
  }
  return (props.question.options || []).map((o) => ({ value: String(o.id), label: o.content }));
});

// ─── Selección múltiple ──────────────────────────────────────────────────────
const selected = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

function toggle(value) {
  const next = selected.value.includes(value)
    ? selected.value.filter((v) => v !== value)
    : [...selected.value, value];
  emit('update:modelValue', next);
}

// ─── Huecos ──────────────────────────────────────────────────────────────────
const blankCount = computed(() => Number(props.question.blank_count) || 0);
const blanks = computed(() => (Array.isArray(props.modelValue) ? props.modelValue : []));

function setBlank(index, value) {
  const next = Array.from({ length: blankCount.value }, (_, i) => blanks.value[i] ?? '');
  next[index] = value;
  emit('update:modelValue', next);
}

// ─── Ordenar ─────────────────────────────────────────────────────────────────
const hasOrder = computed(() => Array.isArray(props.modelValue) && props.modelValue.length > 0);
const liveMessage = ref('');

const orderedItems = computed(() => {
  const items = props.question.items || [];
  if (!hasOrder.value) return items;
  const byId = new Map(items.map((it) => [it.id, it]));
  const ordered = props.modelValue.map((id) => byId.get(id)).filter(Boolean);
  return ordered.length === items.length ? ordered : items;
});

const emitOrder = (items) => emit('update:modelValue', items.map((it) => it.id));

function move(index, dir) {
  const to = index + dir;
  const list = [...orderedItems.value];
  if (to < 0 || to >= list.length) return;
  [list[index], list[to]] = [list[to], list[index]];
  liveMessage.value = `«${list[to].content}» ahora en la posición ${to + 1} de ${list.length}.`;
  emitOrder(list);
}
</script>

<style scoped>
.ans {
  border: 0;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
}

.ans__hint {
  margin: 0 0 2px;
  font-size: var(--fs-small);
  color: var(--muted);
}

.ans__opt,
.ans__order-item {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 56px;
  padding: 12px 16px;
  border-radius: var(--radius-card);
  border: 1px solid var(--border);
  background: var(--bg);
  color: var(--text);
  font-size: 1rem;
  transition: background 0.12s, border-color 0.12s;
}

.ans__opt {
  cursor: pointer;
}

.ans__opt:hover {
  background: var(--raised);
  border-color: var(--control);
}

.ans__opt:has(input:focus-visible) {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.ans__opt.is-sel {
  border-color: var(--accent);
  background: var(--accent-soft);
  box-shadow: inset 0 0 0 1px var(--accent);
}

.ans__key {
  display: inline-grid;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: var(--radius-control);
  background: var(--raised);
  font-size: var(--fs-meta);
  font-weight: 600;
  color: var(--muted);
  flex-shrink: 0;
  transition: background 0.12s, color 0.12s;
}

.ans__opt.is-sel .ans__key,
.ans__order-item.is-sel .ans__key {
  background: var(--accent);
  color: var(--on-accent);
}

.ans__text {
  min-width: 0;
  overflow-wrap: anywhere;
}

/* Huecos */
.ans__blank {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ans__blank-n {
  flex-shrink: 0;
  min-width: 32px;
  font-weight: 600;
  color: var(--muted);
}

/* Ordenar */
.ans__order {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.ans__order-item .ans__text {
  flex: 1;
}

.ans__order-tools {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}

.ans__order-tools .icon-btn {
  width: 40px;
  height: 40px;
}

.ans__order-tools .icon-btn:disabled {
  opacity: 0.3;
  cursor: default;
  background: transparent;
}

.ans__keep {
  align-self: flex-start;
}

/* Texto y número */
.ans--short {
  gap: 8px;
}

.ans__label {
  font-size: var(--fs-small);
  font-weight: 500;
}

.ans__input {
  width: 100%;
  height: 52px;
  border: 1px solid var(--control);
  border-radius: var(--radius-card);
  padding: 0 16px;
  font: inherit;
  font-size: 1rem;
  color: var(--text);
  background: var(--bg);
  transition: border-color 0.15s, box-shadow 0.15s;
}

.ans__input--num {
  max-width: 240px;
}

.ans__input::placeholder {
  color: var(--muted);
}

.ans__input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}
</style>
