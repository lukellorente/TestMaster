<template>
  <div :class="['user-avatar', `avatar--${size}`]" :style="avatarStyle">
  </div>
</template>

<script setup>
import { computed } from 'vue';

const DEFAULT_AVATAR_URL = '/images/defaultpfp.svg';

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

const avatarUrl = computed(() => {
  if (props.imageUrl) return props.imageUrl;

  const raw = props.user?.avatar || props.user?.avatar_url || '';
  if (!raw) return DEFAULT_AVATAR_URL;

  // Si viene como URL absoluta o ruta pública, úsala tal cual
  if (typeof raw === 'string' && (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/'))) {
    return raw;
  }

  // Fallback: si solo llega el nombre de archivo, asumimos storage/app/public/avatars
  return `/storage/avatars/${raw}`;
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
