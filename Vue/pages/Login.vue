<template>
  <div style="max-width: 500px; margin: 2rem auto">
    <div style="background: white; padding: 2.5rem; border-radius: 12px; border: 1px solid #e2e8f0">
      <h1 style="text-align: center; margin-bottom: 0.5rem; color: #1a202c">Iniciar Sesión</h1>
      <p style="text-align: center; color: #a0aec0; margin-bottom: 2rem">
        Accede a tu cuenta de TestMaster
      </p>

      <BaseAlert v-if="error" variant="error" dismissible @dismiss="error = ''">
        {{ error }}
      </BaseAlert>

      <form @submit.prevent="handleLogin">
        <BaseInput
          v-model="form.email"
          label="Correo Electrónico"
          type="email"
          placeholder="tu@email.com"
          required
          autofocus
        />

        <BaseInput
          v-model="form.password"
          label="Contraseña"
          type="password"
          placeholder="••••••••"
          required
        />

        <BaseButton
          type="submit"
          :loading="submitting"
          style="width: 100%; padding: 0.75rem; margin-bottom: 1rem"
        >
          {{ submitting ? 'Iniciando...' : 'Iniciar Sesión' }}
        </BaseButton>
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
import BaseButton from '../components/base/BaseButton.vue';
import BaseInput from '../components/base/BaseInput.vue';
import BaseAlert from '../components/base/BaseAlert.vue';

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
