<template>
  <div class="short-answer-container">
    <input
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="['short-answer-input', inputClass]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    
    <div v-if="showResult" class="result-feedback">
      <template v-if="isCorrect">
        <i class="fa-solid fa-check correct"></i>
        <span class="correct">¡Correcto!</span>
      </template>
      <template v-else>
        <i class="fa-solid fa-xmark incorrect"></i>
        <span class="incorrect">Incorrecto</span>
        <span v-if="correctAnswer" class="correct-answer">
          Respuesta correcta: <strong>{{ correctAnswer }}</strong>
        </span>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Escribe tu respuesta aquí...',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  showResult: {
    type: Boolean,
    default: false,
  },
  isCorrect: {
    type: Boolean,
    default: false,
  },
  correctAnswer: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);

const inputClass = computed(() => {
  if (!props.showResult) return '';
  return props.isCorrect ? 'input--correct' : 'input--incorrect';
});
</script>

<style scoped>
.short-answer-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.short-answer-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  transition: all 0.2s;
  background: #f8f9fa;
}

.short-answer-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.short-answer-input:disabled {
  background: #f1f5f9;
  cursor: not-allowed;
}

.short-answer-input.input--correct {
  border-color: #10b981;
  background: #d1fae5;
}

.short-answer-input.input--incorrect {
  border-color: #ef4444;
  background: #fee2e2;
}

.result-feedback {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.result-feedback .correct {
  color: #059669;
}

.result-feedback .incorrect {
  color: #dc2626;
}

.correct-answer {
  color: #718096;
  margin-left: 0.5rem;
}

.correct-answer strong {
  color: #059669;
}
</style>
