<template>
  <div :class="['user-avatar', `avatar--${size}`]" :style="avatarStyle">
    <template v-if="!imageUrl">{{ initial }}</template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  user: {
    type: Object,
    default: () => ({}),
  },
  name: {
    type: String,
    default: '',
  },
  imageUrl: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'medium',
    validator: (v) => ['small', 'medium', 'large'].includes(v),
  },
});

const userName = computed(() => props.user?.name || props.name || '?');

const initial = computed(() => {
  return userName.value.charAt(0).toUpperCase();
});

const avatarUrl = computed(() => {
  if (props.imageUrl) return props.imageUrl;
  if (props.user?.avatar_url) return `/uploads/avatars/${props.user.avatar_url}`;
  return '';
});

const avatarStyle = computed(() => {
  if (avatarUrl.value) {
    return {
      backgroundImage: `url(${avatarUrl.value})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    };
  }
  return {};
});
</script>

<style scoped>
.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  border-radius: 50%;
  flex-shrink: 0;
}

.avatar--small {
  width: 2rem;
  height: 2rem;
  font-size: 0.875rem;
}

.avatar--medium {
  width: 3rem;
  height: 3rem;
  font-size: 1.25rem;
}

.avatar--large {
  width: 5rem;
  height: 5rem;
  font-size: 2rem;
}
</style>
