<template>
  <div class="auth-hero">
    <div style="max-width: 520px; width: 100%; margin: 0 auto">
      <div class="glass-card">

        <h1 style="text-align: center; margin-bottom: 0.25rem; color: #1a202c">Iniciar Sesión</h1>
        <p style="text-align: center; color: #a0aec0; margin-bottom: 1rem">
          Accede a tu cuenta de TestMaster
        </p>

        <!-- Social Login -->
        <div class="social-login">
          <button type="button" class="btn-social btn-google">
            <i class="fa-brands fa-google"></i>
            Continuar con Google
          </button>
          <button type="button" class="btn-social btn-github">
            <i class="fa-brands fa-github"></i>
            Continuar con GitHub
          </button>
        </div>

        <div class="auth-divider"><span>o</span></div>

        <BaseAlert v-if="error" variant="error" dismissible @dismiss="error = ''">
          {{ error }}
        </BaseAlert>

        <form @submit.prevent="handleLogin">
          <BaseInput v-model="form.email" label="Correo Electrónico" type="email" icon="fa-solid fa-envelope"
            placeholder="tu@email.com" required autofocus />

          <BaseInput v-model="form.password" label="Contraseña" type="password" icon="fa-solid fa-lock"
            placeholder="••••••••" required />

          <BaseButton type="submit" :loading="submitting" style="width: 100%; padding: 0.6rem; margin-bottom: 0.75rem">
            {{ submitting ? 'Iniciando...' : 'Iniciar Sesión' }}
          </BaseButton>
        </form>

        <div style="text-align: center; padding-top: 0.75rem; border-top: 1px solid #e2e8f0">
          <p style="color: #718096; font-size: 0.85rem">
            ¿No tienes cuenta?
            <router-link to="/register" style="color: #667eea; font-weight: 600; text-decoration: none">
              Regístrate aquí
            </router-link>
          </p>
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
