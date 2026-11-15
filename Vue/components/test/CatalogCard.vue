<template>
  <router-link :to="`/test/${test.id}`" class="ccard">
    <div class="ccard__top">
      <span class="ccard__cat">{{ categoryLabel }}</span>
      <span v-if="test.difficulty" :class="['pill', difficultyClass]">{{ difficultyLabel }}</span>
    </div>
    <h3 class="ccard__title">{{ test.title }}</h3>
    <p v-if="test.description" class="ccard__desc">{{ test.description }}</p>
    <span class="ccard__spacer"></span>
    <div class="ccard__foot">
      <span class="tabular">{{ meta }}</span>
      <span v-if="test.user" class="ccard__author">
        <UserAvatar :user="test.user" size="small" />
        <span>{{ test.user.name }}</span>
      </span>
      <span v-else class="ccard__author">Usuario eliminado</span>
    </div>
  </router-link>
</template>

<script setup>
import { computed } from 'vue';
import UserAvatar from '../user/UserAvatar.vue';
import { difficultyClass as diffClass, difficultyLabel as diffLabel, formatTestMeta } from '../../utils/tests';

const props = defineProps({
  test: { type: Object, required: true },
});

const categoryLabel = computed(() => (props.test.categories || []).map((c) => c.name).join(' · ') || 'General');
const difficultyLabel = computed(() => diffLabel(props.test.difficulty));
const difficultyClass = computed(() => diffClass(props.test.difficulty));
const meta = computed(() => formatTestMeta(props.test));
</script>

<style scoped>
.ccard {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 20px;
  border-radius: var(--radius-panel);
  background: var(--raised);
  color: var(--text);
  text-decoration: none;
  transition: background 0.15s;
  min-width: 0;
}

.ccard:hover {
  background: var(--sunk);
  color: var(--text);
}

.ccard__top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.ccard__cat {
  font-size: var(--fs-meta);
  color: var(--muted);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ccard__title {
  font-size: 1.0625rem;
  font-weight: 600;
  line-height: 1.3;
  margin: 0;
}

.ccard__desc {
  font-size: var(--fs-small);
  color: var(--text-body);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ccard__spacer {
  flex: 1;
}

.ccard__foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: var(--fs-meta);
  color: var(--muted);
}

.ccard__author {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.ccard__author span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ccard__author :deep(.user-avatar) {
  width: 24px !important;
  height: 24px !important;
  flex-shrink: 0;
}
</style>
