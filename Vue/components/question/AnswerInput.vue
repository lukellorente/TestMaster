<template>
  <!-- Opción múltiple y verdadero/falso: radios reales, estilados como tarjetas -->
  <fieldset v-if="question.type !== 'short_answer'" class="ans">
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
import { computed } from 'vue';

const props = defineProps({
  question: { type: Object, required: true },
  number: { type: Number, required: true },
  modelValue: { type: String, default: undefined },
});

defineEmits(['update:modelValue']);

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

.ans__opt {
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
  cursor: pointer;
  transition: background 0.12s, border-color 0.12s;
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

.ans__opt.is-sel .ans__key {
  background: var(--accent);
  color: var(--on-accent);
}

.ans__text {
  min-width: 0;
  overflow-wrap: anywhere;
}

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

.ans__input::placeholder {
  color: var(--muted);
}

.ans__input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}
</style>
