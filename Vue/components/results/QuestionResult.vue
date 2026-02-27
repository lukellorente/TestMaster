<template>
  <div class="question-result" :class="{ 'result--correct': isCorrect, 'result--incorrect': !isCorrect && answered, 'result--unanswered': !answered }">
    <div class="result-header">
      <span class="result-number">Pregunta {{ number }}</span>
      <span class="result-status">
        <i v-if="isCorrect" class="fa-solid fa-check"></i>
        <i v-else-if="answered" class="fa-solid fa-xmark"></i>
        <i v-else class="fa-solid fa-minus"></i>
        {{ statusText }}
      </span>
      <span v-if="points" class="result-points">{{ earnedPoints }}/{{ points }} pts</span>
    </div>
    
    <div class="result-content" v-html="formattedContent"></div>
    
    <div class="result-answer">
      <div class="answer-row">
        <span class="answer-label">Tu respuesta:</span>
        <span :class="['answer-value', { correct: isCorrect, incorrect: !isCorrect && answered }]">
          {{ userAnswerText || '(Sin responder)' }}
        </span>
      </div>
      <div v-if="!isCorrect && correctAnswerText" class="answer-row">
        <span class="answer-label">Respuesta correcta:</span>
        <span class="answer-value correct">{{ correctAnswerText }}</span>
      </div>
    </div>
    
    <div v-if="explanation && showExplanation" class="result-explanation">
      <i class="fa-solid fa-lightbulb"></i>
      <p>{{ explanation }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  number: {
    type: Number,
    required: true,
  },
  content: {
    type: String,
    default: '',
  },
  isCorrect: {
    type: Boolean,
    default: false,
  },
  answered: {
    type: Boolean,
    default: true,
  },
  userAnswerText: {
    type: String,
    default: '',
  },
  correctAnswerText: {
    type: String,
    default: '',
  },
  points: {
    type: Number,
    default: 0,
  },
  earnedPoints: {
    type: Number,
    default: 0,
  },
  explanation: {
    type: String,
    default: '',
  },
  showExplanation: {
    type: Boolean,
    default: true,
  },
});

const statusText = computed(() => {
  if (props.isCorrect) return 'Correcto';
  if (!props.answered) return 'Sin responder';
  return 'Incorrecto';
});

const formattedContent = computed(() => {
  return (props.content || '').replace(/\n/g, '<br>');
});
</script>

<style scoped>
.question-result {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.result--correct {
  border-left: 4px solid #10b981;
}

.result--incorrect {
  border-left: 4px solid #ef4444;
}

.result--unanswered {
  border-left: 4px solid #a0aec0;
}

.result-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.result-number {
  font-weight: 600;
  color: #2d3748;
}

.result-status {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.85rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
}

.result--correct .result-status {
  background: #d1fae5;
  color: #059669;
}

.result--incorrect .result-status {
  background: #fee2e2;
  color: #dc2626;
}

.result--unanswered .result-status {
  background: #f1f5f9;
  color: #64748b;
}

.result-points {
  margin-left: auto;
  font-size: 0.85rem;
  color: #718096;
}

.result-content {
  color: #4a5568;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.result-answer {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.answer-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.answer-row:last-child {
  margin-bottom: 0;
}

.answer-label {
  font-size: 0.85rem;
  color: #718096;
  min-width: 120px;
}

.answer-value {
  font-weight: 500;
  color: #2d3748;
}

.answer-value.correct {
  color: #059669;
}

.answer-value.incorrect {
  color: #dc2626;
}

.result-explanation {
  display: flex;
  gap: 0.75rem;
  margin-top: 1rem;
  padding: 1rem;
  background: #fffbeb;
  border-radius: 8px;
  color: #92400e;
}

.result-explanation i {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.result-explanation p {
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.5;
}
</style>
