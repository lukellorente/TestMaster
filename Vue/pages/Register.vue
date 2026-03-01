<template>
  <div class="auth-hero">
    <div style="max-width: 520px; width: 100%; margin: 0 auto">
      <div class="glass-card">
    
      <h1 style="text-align: center; margin-bottom: 0.25rem; color: #1a202c">Crear Cuenta</h1>
      <p style="text-align: center; color: #a0aec0; margin-bottom: 1rem">
        Únete a TestMaster gratis
      </p>

      <!-- Social Login -->
      <div class="social-login">
        <button type="button" class="btn-social btn-google">
          <i class="fa-brands fa-google"></i>
          Registrarse con Google
        </button>
        <button type="button" class="btn-social btn-github">
          <i class="fa-brands fa-github"></i>
          Registrarse con GitHub
        </button>
      </div>

      <div class="auth-divider"><span>o</span></div>

      <BaseAlert v-if="error" variant="error" dismissible @dismiss="error = ''">
        {{ error }}
      </BaseAlert>
      <BaseAlert v-if="success" variant="success">
        {{ success }}
      </BaseAlert>

      <form @submit.prevent="handleRegister">
        <BaseInput
          v-model="form.name"
          label="Nombre Completo"
          type="text"
          icon="fa-solid fa-user"
          placeholder="Juan Pérez"
          required
          autofocus
        />

        <BaseInput
          v-model="form.email"
          label="Correo Electrónico"
          type="email"
          icon="fa-solid fa-envelope"
          placeholder="tu@email.com"
          required
        />

        <BaseInput
          v-model="form.password"
          label="Contraseña"
          type="password"
          icon="fa-solid fa-lock"
          placeholder="Mínimo 6 caracteres"
          help="Mínimo 6 caracteres"
          required
        />

        <BaseInput
          v-model="form.password_confirmation"
          label="Confirmar Contraseña"
          type="password"
          icon="fa-solid fa-lock"
          placeholder="Repite tu contraseña"
          required
        />

        <BaseButton
          type="submit"
          :loading="submitting"
          style="width: 100%; padding: 0.6rem; margin-bottom: 0.75rem"
        >
          {{ submitting ? 'Creando...' : 'Crear Cuenta' }}
        </BaseButton>
      </form>

      <div style="text-align: center; padding-top: 0.75rem; border-top: 1px solid #e2e8f0">
        <p style="color: #718096; font-size: 0.85rem">
          ¿Ya tienes cuenta?
          <router-link to="/login" style="color: #667eea; font-weight: 600; text-decoration: none">
            Inicia sesión aquí
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

const form = ref({ name: '', email: '', password: '', password_confirmation: '' });
const error = ref('');
const success = ref('');
const submitting = ref(false);

onMounted(() => {
  if (authStore.isLoggedIn) {
    router.push('/');
  }
});

const handleRegister = async () => {
  error.value = '';
  success.value = '';
  submitting.value = true;
  try {
    await authStore.register(
      form.value.name,
      form.value.email,
      form.value.password,
      form.value.password_confirmation
    );
    success.value = 'Cuenta creada exitosamente. Redirigiendo...';
    setTimeout(() => router.push('/'), 1500);
  } catch (err) {
    const errors = err.response?.data?.errors;
    if (errors) {
      error.value = Object.values(errors).flat().join(' ');
    } else {
      error.value = err.response?.data?.message || 'Error al crear la cuenta.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>
