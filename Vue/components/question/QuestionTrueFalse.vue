<template>
  <div class="question-options">
    <label :class="['option-label', optionClass('true')]">
      <input
        type="radio"
        :name="`question_${questionId}`"
        value="true"
        :checked="modelValue === 'true'"
        :disabled="disabled"
        @change="$emit('update:modelValue', 'true')"
      />
      <span class="option-icon true"><i class="fa-solid fa-check"></i></span>
      <span class="option-text">Verdadero</span>
      <i v-if="showResult && correctAnswer === true" class="fa-solid fa-check result-icon correct"></i>
      <i v-if="showResult && modelValue === 'true' && correctAnswer === false" class="fa-solid fa-xmark result-icon incorrect"></i>
    </label>
    
    <label :class="['option-label', optionClass('false')]">
      <input
        type="radio"
        :name="`question_${questionId}`"
        value="false"
        :checked="modelValue === 'false'"
        :disabled="disabled"
        @change="$emit('update:modelValue', 'false')"
      />
      <span class="option-icon false"><i class="fa-solid fa-xmark"></i></span>
      <span class="option-text">Falso</span>
      <i v-if="showResult && correctAnswer === false" class="fa-solid fa-check result-icon correct"></i>
      <i v-if="showResult && modelValue === 'false' && correctAnswer === true" class="fa-solid fa-xmark result-icon incorrect"></i>
    </label>
  </div>
</template>

<script setup>
const props = defineProps({
  questionId: {
    type: [Number, String],
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
  correctAnswer: {
    type: Boolean,
    default: null,
  },
});

defineEmits(['update:modelValue']);

const optionClass = (value) => {
  if (!props.showResult) return '';
  const isCorrect = (value === 'true' && props.correctAnswer === true) || 
                    (value === 'false' && props.correctAnswer === false);
  const isSelected = props.modelValue === value;
  
  if (isCorrect) return 'option--correct';
  if (isSelected && !isCorrect) return 'option--incorrect';
  return '';
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

.option-icon {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.option-icon.true {
  background: #d1fae5;
  color: #059669;
}

.option-icon.false {
  background: #fee2e2;
  color: #dc2626;
}

.option-text {
  flex: 1;
  color: #2d3748;
  font-weight: 500;
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
