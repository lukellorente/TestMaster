<template>
  <div class="form-group">
    <label v-if="label" :for="inputId" class="form-label">
      {{ label }}
      <span v-if="required" style="color: #ef4444">*</span>
    </label>
    <input
      v-if="type !== 'textarea'"
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :maxlength="maxlength"
      :min="min"
      :max="max"
      class="form-input"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <textarea
      v-else
      :id="inputId"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :rows="rows"
      class="form-input"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
    ></textarea>
    <p v-if="helpText" class="help-text">{{ helpText }}</p>
    <p v-if="error" class="error-text">{{ error }}</p>
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
  type: {
    type: String,
    default: 'text',
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
  maxlength: {
    type: [String, Number],
    default: null,
  },
  min: {
    type: [String, Number],
    default: null,
  },
  max: {
    type: [String, Number],
    default: null,
  },
  rows: {
    type: [String, Number],
    default: 4,
  },
  helpText: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);

const inputId = computed(() => `input-${Math.random().toString(36).slice(2, 9)}`);
</script>
