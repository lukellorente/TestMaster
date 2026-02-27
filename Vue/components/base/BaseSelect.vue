<template>
  <div class="form-group">
    <label v-if="label" :for="selectId" class="form-label">
      {{ label }}
      <span v-if="required" style="color: #ef4444">*</span>
    </label>
    <select
      :id="selectId"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      class="form-input"
      v-bind="$attrs"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option
        v-for="option in normalizedOptions"
        :key="option.value"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    <p v-if="helpText" class="help-text">{{ helpText }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
  required: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  helpText: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);

const selectId = computed(() => `select-${Math.random().toString(36).slice(2, 9)}`);

// Normaliza las opciones para soportar tanto arrays de objetos como arrays simples
const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    if (typeof opt === 'object' && opt !== null) {
      return {
        value: opt.value ?? opt.id ?? opt,
        label: opt.label ?? opt.name ?? opt.text ?? opt.value ?? opt,
      };
    }
    return { value: opt, label: opt };
  });
});
</script>
