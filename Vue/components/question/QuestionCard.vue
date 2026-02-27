<template>
  <div class="question-card">
    <div class="question-header">
      <span class="question-number">Pregunta {{ number }}</span>
      <span v-if="points" class="question-points">{{ points }} pt{{ points !== 1 ? 's' : '' }}</span>
      <slot name="actions" />
    </div>
    
    <div class="question-content" v-html="formattedContent"></div>
    
    <img
      v-if="imageUrl"
      :src="imageUrl"
      alt="Imagen de la pregunta"
      class="question-image"
    />
    
    <div class="question-body">
      <slot />
    </div>
    
    <div v-if="explanation && showExplanation" class="question-explanation">
      <strong><i class="fa-solid fa-lightbulb"></i> Explicación:</strong>
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
  imageUrl: {
    type: String,
    default: '',
  },
  points: {
    type: Number,
    default: 0,
  },
  explanation: {
    type: String,
    default: '',
  },
  showExplanation: {
    type: Boolean,
    default: false,
  },
});

const formattedContent = computed(() => {
  return (props.content || '').replace(/\n/g, '<br>');
});
</script>

<style scoped>
.question-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 1.5rem;
}

.question-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.question-number {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
}

.question-points {
  color: #a0aec0;
  font-size: 0.85rem;
}

.question-content {
  font-size: 1.05rem;
  color: #2d3748;
  margin-bottom: 1rem;
  line-height: 1.6;
}

.question-image {
  max-width: 100%;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.question-body {
  margin-top: 1rem;
}

.question-explanation {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #fffbeb;
  border-radius: 8px;
  border-left: 4px solid #f59e0b;
}

.question-explanation strong {
  color: #92400e;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.question-explanation p {
  color: #78350f;
  margin: 0;
  font-size: 0.95rem;
}
</style>
