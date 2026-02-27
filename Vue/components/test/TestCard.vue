<template>
  <div class="test-card" @click="$emit('click')">
    <div class="test-card-header">
      <router-link :to="`/test/${test.id}`" class="test-card-title">
        {{ test.title }}
      </router-link>
      <BaseBadge v-if="isPopular" variant="popular">popular</BaseBadge>
      <BaseBadge v-if="test.difficulty" :variant="difficultyVariant">
        {{ difficultyText }}
      </BaseBadge>
    </div>
    
    <p v-if="test.description && showDescription" class="test-card-description">
      {{ truncatedDescription }}
    </p>
    
    <div class="test-card-meta">
      <span class="meta-item">
        <i class="fa-solid fa-file-lines"></i>
        {{ test.questions_count || test.num_questions || 0 }} preg
      </span>
      <span v-if="test.time_limit" class="meta-item">
        <i class="fa-solid fa-clock"></i>
        {{ formatTime(test.time_limit) }}
      </span>
      <span class="meta-item">
        <i class="fa-solid fa-eye"></i>
        {{ test.completed_attempts_count || test.attempts_count || 0 }} visto{{ (test.completed_attempts_count || test.attempts_count || 0) !== 1 ? 's' : '' }}
      </span>
      <span v-if="showDate" class="meta-item">
        <i class="fa-solid fa-calendar"></i>
        {{ formatDate(test.created_at) }}
      </span>
    </div>
    
    <div v-if="showAuthor && test.user" class="test-card-author">
      <UserAvatar :user="test.user" size="small" />
      <span>{{ test.user.name }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import BaseBadge from '../base/BaseBadge.vue';
import UserAvatar from '../user/UserAvatar.vue';

const props = defineProps({
  test: {
    type: Object,
    required: true,
  },
  showDescription: {
    type: Boolean,
    default: false,
  },
  showDate: {
    type: Boolean,
    default: true,
  },
  showAuthor: {
    type: Boolean,
    default: false,
  },
  popularThreshold: {
    type: Number,
    default: 10,
  },
});

defineEmits(['click']);

const isPopular = computed(() => {
  const attempts = props.test.completed_attempts_count || props.test.attempts_count || 0;
  return attempts > props.popularThreshold;
});

const difficultyMap = { easy: 'Fácil', medium: 'Medio', hard: 'Difícil' };
const difficultyVariantMap = { easy: 'success', medium: 'warning', hard: 'danger' };

const difficultyText = computed(() => difficultyMap[props.test.difficulty] || '');
const difficultyVariant = computed(() => difficultyVariantMap[props.test.difficulty] || 'secondary');

const truncatedDescription = computed(() => {
  const desc = props.test.description || '';
  return desc.length > 120 ? desc.slice(0, 120) + '...' : desc;
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatTime = (seconds) => {
  if (!seconds) return '';
  const minutes = Math.floor(seconds / 60);
  return `${minutes} min`;
};
</script>

<style scoped>
.test-card {
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
  cursor: pointer;
}

.test-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  border-color: #cbd5e0;
}

.test-card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 0.5rem;
}

.test-card-title {
  font-weight: 600;
  color: #2d3748;
  text-decoration: none;
  font-size: 1.05rem;
}

.test-card-title:hover {
  color: #667eea;
}

.test-card-description {
  color: #718096;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
  line-height: 1.5;
}

.test-card-meta {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: #a0aec0;
  font-size: 0.85rem;
}

.meta-item i {
  color: #667eea;
}

.test-card-author {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f7fafc;
  font-size: 0.85rem;
  color: #718096;
}
</style>
