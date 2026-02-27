<template>
  <div class="question-options">
    <label
      v-for="option in options"
      :key="option.id"
      :class="['option-label', optionClass(option)]"
    >
      <input
        type="radio"
        :name="`question_${questionId}`"
        :value="String(option.id)"
        :checked="modelValue === String(option.id)"
        :disabled="disabled"
        @change="$emit('update:modelValue', String(option.id))"
      />
      <span class="option-marker">{{ getMarker(option) }}</span>
      <span class="option-text">{{ option.content }}</span>
      <i v-if="showResult && option.is_correct" class="fa-solid fa-check result-icon correct"></i>
      <i v-if="showResult && isSelected(option) && !option.is_correct" class="fa-solid fa-xmark result-icon incorrect"></i>
    </label>
  </div>
</template>

<script setup>
const props = defineProps({
  questionId: {
    type: [Number, String],
    required: true,
  },
  options: {
    type: Array,
    required: true,
  },
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  showResult: {
    type: Boolean,
    default: false,
  },
  showMarkers: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['update:modelValue']);

const isSelected = (option) => props.modelValue === String(option.id);

const optionClass = (option) => {
  if (!props.showResult) return '';
  const selected = isSelected(option);
  if (option.is_correct) return 'option--correct';
  if (selected && !option.is_correct) return 'option--incorrect';
  return '';
};

const getMarker = (option) => {
  if (!props.showMarkers) return '';
  const index = props.options.findIndex((o) => o.id === option.id);
  return String.fromCharCode(65 + index); // A, B, C, D...
};
</script>

<style scoped>
.question-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.option-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  background: #f8f9fa;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.option-label:hover:not(.option--correct):not(.option--incorrect) {
  border-color: #667eea;
  background: #f0f4ff;
}

.option-label input {
  display: none;
}

.option-label:has(input:checked) {
  border-color: #667eea;
  background: #eff2ff;
}

.option-marker {
  width: 1.75rem;
  height: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 50%;
  font-size: 0.85rem;
  font-weight: 600;
  color: #667eea;
  flex-shrink: 0;
}

.option-label:has(input:checked) .option-marker {
  background: #667eea;
  border-color: #667eea;
  color: white;
}

.option-text {
  flex: 1;
  color: #2d3748;
}

.result-icon {
  font-size: 1.1rem;
}

.result-icon.correct {
  color: #10b981;
}

.result-icon.incorrect {
  color: #ef4444;
}

.option--correct {
  background: #d1fae5;
  border-color: #10b981;
}

.option--incorrect {
  background: #fee2e2;
  border-color: #ef4444;
}
</style>
