<template>
  <div class="field">
    <div v-if="label || $slots.aside" class="field__head">
      <label v-if="label" :for="inputId" class="field__label">
        {{ label }}<span v-if="optional" class="field__optional"> (opcional)</span>
      </label>
      <slot name="aside" />
    </div>

    <div v-if="type !== 'textarea'" class="field__control">
      <input
        :id="inputId"
        :type="currentType"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :autocomplete="autocomplete"
        :aria-invalid="error ? 'true' : undefined"
        :aria-describedby="describedBy"
        class="form-input field__input"
        :class="{ 'has-toggle': type === 'password', 'is-invalid': error }"
        v-bind="$attrs"
        @input="$emit('update:modelValue', $event.target.value)"
      />
      <button
        v-if="type === 'password'"
        type="button"
        class="field__toggle"
        :aria-label="revealed ? 'Ocultar contraseña' : 'Mostrar contraseña'"
        :aria-pressed="revealed ? 'true' : 'false'"
        @click="revealed = !revealed"
      >
        <i :class="revealed ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'" aria-hidden="true"></i>
      </button>
    </div>
    <textarea
      v-else
      :id="inputId"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      :maxlength="maxlength"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      class="form-input"
      :class="{ 'is-invalid': error }"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
    ></textarea>

    <p v-if="helpText" :id="`${inputId}-help`" class="help-text">{{ helpText }}</p>
    <p v-if="error" :id="`${inputId}-error`" class="error-text">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, ref, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
  // Los campos obligatorios no se marcan; se marca el opcional
  optional: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  maxlength: { type: [String, Number], default: null },
  min: { type: [String, Number], default: null },
  max: { type: [String, Number], default: null },
  rows: { type: [String, Number], default: 4 },
  autocomplete: { type: String, default: undefined },
  helpText: { type: String, default: '' },
  error: { type: String, default: '' },
  id: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const autoId = useId();
const inputId = computed(() => props.id || `f-${autoId}`);
const revealed = ref(false);
const currentType = computed(() => (props.type === 'password' && revealed.value ? 'text' : props.type));

const describedBy = computed(() => {
  const ids = [];
  if (props.helpText) ids.push(`${inputId.value}-help`);
  if (props.error) ids.push(`${inputId.value}-error`);
  return ids.join(' ') || undefined;
});
</script>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
}

.field__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 12px;
}

.field__label {
  font-size: var(--fs-small);
  font-weight: 500;
  color: var(--text);
}

.field__optional {
  font-weight: 400;
  color: var(--muted);
}

.field__control {
  position: relative;
}

.field__input {
  height: 44px;
}

.field__input.has-toggle {
  padding-right: 44px;
}

.form-input.is-invalid {
  border-color: var(--negative);
}

.field__toggle {
  position: absolute;
  right: 4px;
  top: 50%;
  transform: translateY(-50%);
  width: 36px;
  height: 36px;
  border: 0;
  border-radius: var(--radius-control);
  background: transparent;
  color: var(--muted);
  cursor: pointer;
}

.field__toggle:hover {
  color: var(--text);
  background: var(--raised);
}

.help-text,
.error-text {
  margin: 0;
}
</style>
