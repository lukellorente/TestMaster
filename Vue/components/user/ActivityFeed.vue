<template>
  <div class="activity-feed">
    <div v-if="activities.length === 0" class="empty-state">
      <i class="fa-solid fa-bolt"></i>
      <p>{{ emptyMessage }}</p>
    </div>
    
    <div v-else class="activity-list">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="activity-item"
      >
        <div class="activity-icon" :class="`icon--${activity.type || 'default'}`">
          <i :class="getIcon(activity.type)"></i>
        </div>
        <div class="activity-content">
          <p class="activity-text">
            <slot name="item" :activity="activity">
              {{ activity.description || activity.text }}
            </slot>
          </p>
          <span class="activity-time">{{ formatTime(activity.created_at || activity.date) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  activities: {
    type: Array,
    default: () => [],
  },
  emptyMessage: {
    type: String,
    default: 'No hay actividad reciente',
  },
});

const getIcon = (type) => {
  const icons = {
    test_created: 'fa-solid fa-file-lines',
    test_completed: 'fa-solid fa-circle-check',
    test_started: 'fa-solid fa-play',
    comment: 'fa-solid fa-comment',
    rating: 'fa-solid fa-star',
    default: 'fa-solid fa-bolt',
  };
  return icons[type] || icons.default;
};

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now = new Date();
  const diff = Math.floor((now - date) / 1000);
  
  if (diff < 60) return 'Ahora mismo';
  if (diff < 3540) return `hace ${Math.floor(diff / 60)} min`;
  if (diff < 86400) return `hace ${Math.max(1, Math.round(diff / 3600))} h`;
  if (diff < 604800) return `hace ${Math.floor(diff / 86400)} d`;
  
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
};
</script>

<style scoped>
.activity-feed {
  background: white;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #a0aec0;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
}

.activity-list {
  padding: 0.5rem 0;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 0.875rem 1.25rem;
  transition: background 0.2s;
}

.activity-item:hover {
  background: #f8f9fa;
}

.activity-icon {
  width: 2.25rem;
  height: 2.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  flex-shrink: 0;
}

.icon--default,
.icon--test_created {
  background: #eff2ff;
  color: #667eea;
}

.icon--test_completed {
  background: #d1fae5;
  color: #059669;
}

.icon--test_started {
  background: #fef3c7;
  color: #d97706;
}

.icon--comment {
  background: #e0e7ff;
  color: #4f46e5;
}

.icon--rating {
  background: #fef9c3;
  color: #ca8a04;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-text {
  color: #2d3748;
  font-size: 0.95rem;
  margin: 0 0 0.25rem 0;
  line-height: 1.4;
}

.activity-time {
  color: #a0aec0;
  font-size: 0.8rem;
}
</style>
