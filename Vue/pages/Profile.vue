<template>
  <div v-if="loading" style="text-align: center; padding: 3rem; color: #666">
    <i class="fa-solid fa-spinner fa-spin"></i> Cargando perfil...
  </div>

  <main v-else-if="profile" style="max-width: 1000px; margin: 0 auto">
    <!-- Profile Header -->
    <div class="profile-header">
      <div class="profile-info">
        <!-- Avatar con opción de cambiar si es propio perfil -->
        <div class="profile-avatar-container" :class="{ editable: profile.is_own_profile }">
          <div 
            class="profile-avatar" 
            @click="profile.is_own_profile && triggerFileInput()"
            :style="avatarStyle"
          >
            <template v-if="!avatarUrl">{{ initial }}</template>
          </div>
          <!-- Overlay para cambiar foto -->
          <div v-if="profile.is_own_profile" class="avatar-overlay" @click="triggerFileInput">
            <i class="fa-solid fa-camera"></i>
            <span>Cambiar</span>
          </div>
          <!-- Input file oculto -->
          <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            style="display: none"
            @change="handleFileSelect"
          />
          <!-- Botón eliminar avatar -->
          <button 
            v-if="profile.is_own_profile && avatarUrl" 
            class="avatar-delete-btn"
            @click.stop="deleteAvatar"
            title="Eliminar foto de perfil"
          >
            <i class="fa-solid fa-times"></i>
          </button>
        </div>
        <!-- Estado de carga del avatar -->
        <div v-if="uploadingAvatar" class="avatar-loading">
          <i class="fa-solid fa-spinner fa-spin"></i> Subiendo...
        </div>
        <div class="profile-details">
          <h1 class="profile-name">{{ profile.user.name }}</h1>
          <div class="profile-meta">
            Miembro desde {{ formatDate(profile.user.created_at) }}
            <template v-if="profile.user.last_login">
              &bull; Última actividad: {{ formatDate(profile.user.last_login) }}
            </template>
          </div>
          <p v-if="profile.user.bio" style="margin-top: 1rem; color: #4a5568" v-html="nl2br(profile.user.bio)"></p>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <div :class="['tab', { active: activeTab === 'tests' }]" @click="activeTab = 'tests'">
        <i class="fa-solid fa-file-lines"></i> Tests ({{ profile.created_tests?.length || 0 }})
      </div>
      <div :class="['tab', { active: activeTab === 'activity' }]" @click="activeTab = 'activity'">
        <i class="fa-solid fa-bolt"></i> Actividad ({{ profile.recent_activity?.length || 0 }})
      </div>
      <div :class="['tab', { active: activeTab === 'completed' }]" @click="activeTab = 'completed'">
        <i class="fa-solid fa-circle-check"></i> Realizados ({{ profile.completed_tests?.length || 0 }})
      </div>
      <div :class="['tab', { active: activeTab === 'stats' }]" @click="activeTab = 'stats'">
        <i class="fa-solid fa-chart-bar"></i> Estadísticas
      </div>
    </div>

    <!-- Tab: Created Tests -->
    <div v-if="activeTab === 'tests'">
      <div v-if="!profile.created_tests?.length" class="empty-state-box">
        <i class="fa-solid fa-file-lines" style="font-size: 3rem; color: #a0aec0"></i>
        <p>{{ profile.user.name }} aún no ha creado tests públicos</p>
      </div>
      <div v-else style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0">
        <div
          v-for="t in profile.created_tests"
          :key="t.id"
          class="test-item"
          @click="$router.push(`/test/${t.id}`)"
          style="cursor: pointer"
        >
          <router-link :to="`/test/${t.id}`" class="test-title">{{ t.title }}</router-link>
          <span class="test-meta time">{{ formatDate(t.created_at) }}</span>
          <span class="test-meta questions">{{ t.num_questions }} preg</span>
          <span class="test-meta views">{{ t.attempts_count }} intento{{ t.attempts_count != 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>

    <!-- Tab: Activity -->
    <div v-if="activeTab === 'activity'">
      <div v-if="!profile.recent_activity?.length" class="empty-state-box">
        <i class="fa-solid fa-bolt" style="font-size: 3rem; color: #a0aec0"></i>
        <p>Sin actividad reciente</p>
      </div>
      <div v-else style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0">
        <div
          v-for="(a, i) in profile.recent_activity"
          :key="i"
          style="padding: 1rem 0; border-bottom: 1px solid #f7fafc; display: flex; gap: 1rem; align-items: center"
        >
          <div style="font-size: 1.5rem">
            <i :class="a.type === 'test' ? 'fa-solid fa-file-lines' : 'fa-solid fa-circle-check'"></i>
          </div>
          <div style="flex: 1">
            <div style="color: #1a202c; margin-bottom: 0.25rem">
              <template v-if="a.type === 'test'">Creó el test <strong>{{ a.title }}</strong></template>
              <template v-else>Completó <strong>{{ a.title }}</strong></template>
            </div>
            <div style="font-size: 0.85rem; color: #a0aec0">{{ formatDateTime(a.date) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Completed Tests -->
    <div v-if="activeTab === 'completed'">
      <div v-if="!profile.completed_tests?.length" class="empty-state-box">
        <i class="fa-solid fa-circle-check" style="font-size: 3rem; color: #a0aec0"></i>
        <p>{{ profile.user.name }} aún no ha completado tests</p>
      </div>
      <div v-else style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0">
        <div
          v-for="at in profile.completed_tests"
          :key="at.id"
          style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center"
        >
          <div style="flex: 1">
            <div style="font-weight: 600; color: #1a202c; margin-bottom: 0.25rem">{{ at.title }}</div>
            <div style="font-size: 0.85rem; color: #718096">
              Por {{ at.author_name }} &bull; {{ formatDate(at.completed_at) }}
            </div>
          </div>
          <div style="text-align: right">
            <div :style="{ fontSize: '1.5rem', fontWeight: 600, color: at.passed ? '#10b981' : '#ef4444' }">
              {{ Number(at.score).toFixed(1) }}%
            </div>
            <div style="font-size: 0.75rem; color: #718096">{{ at.passed ? 'Aprobado' : 'Reprobado' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: Statistics -->
    <div v-if="activeTab === 'stats'">
      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-value">{{ stats.tests_created }}</div>
          <div class="stat-label">Tests Creados</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ stats.tests_completed }}</div>
          <div class="stat-label">Tests Completados</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ stats.tests_passed }}</div>
          <div class="stat-label">Aprobados</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ Number(stats.average_score || 0).toFixed(1) }}%</div>
          <div class="stat-label">Promedio</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ stats.perfect_scores }}</div>
          <div class="stat-label">Scores Perfectos</div>
        </div>
        <div class="stat-box">
          <div class="stat-value">{{ stats.current_streak }}</div>
          <div class="stat-label">Racha Actual</div>
        </div>
      </div>

      <!-- Progress bar -->
      <div
        v-if="stats.tests_completed > 0"
        style="background: white; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 2rem"
      >
        <h3 style="margin-bottom: 1.5rem; color: #1a202c">Rendimiento General</h3>
        <div style="margin-bottom: 1.5rem">
          <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem">
            <span style="color: #718096">Tasa de Aprobación</span>
            <span style="font-weight: 600; color: #667eea">{{ passRate }}%</span>
          </div>
          <div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden">
            <div
              :style="{
                background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                height: '100%',
                width: passRate + '%',
                transition: 'width 0.5s',
              }"
            ></div>
          </div>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const loading = ref(true);
const profile = ref(null);
const activeTab = ref('tests');
const fileInput = ref(null);
const uploadingAvatar = ref(false);

const stats = computed(() => profile.value?.stats || {
  tests_created: 0, tests_completed: 0, tests_passed: 0,
  average_score: 0, perfect_scores: 0, current_streak: 0,
});

const initial = computed(() =>
  (profile.value?.user?.name || '?').charAt(0).toUpperCase()
);

// URL del avatar (imagen subida o null)
const avatarUrl = computed(() => profile.value?.user?.avatar || null);

// Estilo del avatar: imagen de fondo si existe, sino gradiente
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

const passRate = computed(() => {
  const s = stats.value;
  return s.tests_completed > 0
    ? ((s.tests_passed / s.tests_completed) * 100).toFixed(1)
    : 0;
});

const nl2br = (s) => (s || '').replace(/\n/g, '<br>');
const formatDate = (d) => d ? new Date(d).toLocaleDateString('es-ES') : '';
const formatDateTime = (d) => d ? new Date(d).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '';

// Abre el selector de archivos
const triggerFileInput = () => {
  fileInput.value?.click();
};

// Maneja la selección de archivo
const handleFileSelect = async (event) => {
  const file = event.target.files?.[0];
  if (!file) return;

  // Validar tipo de archivo
  const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  if (!validTypes.includes(file.type)) {
    alert('Por favor selecciona una imagen válida (JPEG, PNG, GIF o WebP)');
    return;
  }

  // Validar tamaño (máximo 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('La imagen no puede superar los 2MB');
    return;
  }

  await uploadAvatar(file);
};

// Sube el avatar al servidor
const uploadAvatar = async (file) => {
  uploadingAvatar.value = true;
  
  try {
    const formData = new FormData();
    formData.append('avatar', file);

    const { data } = await api.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    // Actualizar el avatar en el perfil local
    if (profile.value?.user) {
      profile.value.user.avatar = data.avatar;
    }

    // También actualizar en localStorage si es el usuario actual
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      const user = JSON.parse(storedUser);
      if (user.id === profile.value?.user?.id) {
        user.avatar = data.avatar;
        localStorage.setItem('user', JSON.stringify(user));
      }
    }
  } catch (err) {
    console.error('Error al subir avatar:', err);
    alert(err.response?.data?.message || 'Error al subir la imagen');
  } finally {
    uploadingAvatar.value = false;
    // Limpiar input para permitir seleccionar el mismo archivo
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
};

// Elimina el avatar
const deleteAvatar = async () => {
  if (!confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')) {
    return;
  }

  uploadingAvatar.value = true;
  
  try {
    await api.delete('/profile/avatar');

    // Limpiar avatar del perfil local
    if (profile.value?.user) {
      profile.value.user.avatar = null;
    }

    // También limpiar en localStorage
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      const user = JSON.parse(storedUser);
      if (user.id === profile.value?.user?.id) {
        user.avatar = null;
        localStorage.setItem('user', JSON.stringify(user));
      }
    }
  } catch (err) {
    console.error('Error al eliminar avatar:', err);
    alert('Error al eliminar la imagen');
  } finally {
    uploadingAvatar.value = false;
  }
};

onMounted(async () => {
  try {
    const { data } = await api.get(`/profile/${route.params.id}`);
    profile.value = data;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.profile-header {
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  background: white;
}
.profile-info {
  display: flex;
  gap: 2rem;
  align-items: center;
}
.profile-avatar-container {
  position: relative;
  flex-shrink: 0;
}
.profile-avatar-container.editable .profile-avatar {
  cursor: pointer;
}
.profile-avatar-container.editable:hover .avatar-overlay {
  opacity: 1;
}
.profile-avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: white;
  font-weight: 600;
  flex-shrink: 0;
  overflow: hidden;
  transition: filter 0.2s;
}
.profile-avatar-container.editable:hover .profile-avatar {
  filter: brightness(0.8);
}
.avatar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  opacity: 0;
  transition: opacity 0.2s;
  cursor: pointer;
}
.avatar-overlay i {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}
.avatar-delete-btn {
  position: absolute;
  top: -5px;
  right: -5px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: #ef4444;
  border: 2px solid white;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.75rem;
  transition: transform 0.2s, background 0.2s;
}
.avatar-delete-btn:hover {
  background: #dc2626;
  transform: scale(1.1);
}
.avatar-loading {
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.75rem;
  color: #667eea;
  white-space: nowrap;
}
.profile-details { flex: 1; }
.profile-name { font-size: 2rem; font-weight: 600; color: #1a202c; margin-bottom: 0.5rem; }
.profile-meta { color: #718096; font-size: 0.9rem; }
.tabs {
  display: flex;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
}
.tab {
  flex: 1;
  padding: 1rem;
  text-align: center;
  cursor: pointer;
  border-right: 1px solid #e2e8f0;
  transition: all 0.2s;
  font-weight: 500;
  color: #718096;
}
.tab:last-child { border-right: none; }
.tab:hover { background: #f8f9fa; }
.tab.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}
.stat-box {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
}
.stat-value { font-size: 2rem; font-weight: 600; color: #667eea; margin-bottom: 0.25rem; }
.stat-label { font-size: 0.85rem; color: #718096; }
.empty-state-box {
  background: white;
  padding: 3rem;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-align: center;
}
@media (max-width: 768px) {
  .profile-info { flex-direction: column; text-align: center; }
  .tabs { flex-direction: column; }
  .tab { border-right: none; border-bottom: 1px solid #e2e8f0; }
}
</style>
