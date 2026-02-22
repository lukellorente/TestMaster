<template>
  <div style="max-width: 500px; margin: 2rem auto">
    <div style="background: white; padding: 2.5rem; border-radius: 12px; border: 1px solid #e2e8f0">
      <h1 style="text-align: center; margin-bottom: 0.5rem; color: #1a202c">Iniciar Sesión</h1>
      <p style="text-align: center; color: #a0aec0; margin-bottom: 2rem">
        Accede a tu cuenta de TestMaster
      </p>

      <div v-if="error" class="alert alert-error">{{ error }}</div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label class="form-label" for="email">Correo Electrónico</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            class="form-input"
            placeholder="tu@email.com"
            required
            autofocus
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            class="form-input"
            placeholder="••••••••"
            required
          />
        </div>

        <button
          type="submit"
          class="btn"
          style="width: 100%; padding: 0.75rem; margin-bottom: 1rem"
          :disabled="submitting"
        >
          {{ submitting ? 'Iniciando...' : 'Iniciar Sesión' }}
        </button>
      </form>

      <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e2e8f0">
        <p style="color: #718096; font-size: 0.9rem">
          ¿No tienes cuenta?
          <router-link to="/register" style="color: #667eea; font-weight: 600; text-decoration: none">
            Regístrate aquí
          </router-link>
        </p>
      </div>

      <!-- Usuarios de prueba -->
      <div
        style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e2e8f0"
      >
        <p style="font-size: 0.85rem; color: #4a5568; margin-bottom: 0.5rem; font-weight: 600">
          <i class="fa-solid fa-wrench"></i> Usuarios de prueba:
        </p>
        <div style="font-size: 0.8rem; color: #718096; line-height: 1.8">
          <strong>Estudiante:</strong> estudiante@test.com<br />
          <strong>Profesor:</strong> profesor@test.com<br />
          <strong>Admin:</strong> admin@testmaster.com<br />
          <em>Contraseña para todos: password123</em>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({ email: '', password: '' });
const error = ref('');
const submitting = ref(false);

onMounted(() => {
  if (authStore.isLoggedIn) {
    router.push('/');
  }
});

const handleLogin = async () => {
  error.value = '';
  submitting.value = true;
  try {
    await authStore.login(form.value.email, form.value.password);
    router.push('/');
  } catch (err) {
    error.value = err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Email o contraseña incorrectos.';
  } finally {
    submitting.value = false;
  }
};
</script>
