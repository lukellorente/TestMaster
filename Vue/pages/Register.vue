<template>
  <div style="max-width: 500px; margin: 2rem auto">
    <div style="background: white; padding: 2.5rem; border-radius: 12px; border: 1px solid #e2e8f0">
      <h1 style="text-align: center; margin-bottom: 0.5rem; color: #1a202c">Crear Cuenta</h1>
      <p style="text-align: center; color: #a0aec0; margin-bottom: 2rem">
        Únete a TestMaster gratis
      </p>

      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <div v-if="success" class="alert alert-success">{{ success }}</div>

      <form @submit.prevent="handleRegister">
        <div class="form-group">
          <label class="form-label" for="name">Nombre Completo</label>
          <input
            type="text"
            id="name"
            v-model="form.name"
            class="form-input"
            placeholder="Juan Pérez"
            required
            autofocus
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Correo Electrónico</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            class="form-input"
            placeholder="tu@email.com"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Contraseña</label>
          <input
            type="password"
            id="password"
            v-model="form.password"
            class="form-input"
            placeholder="Mínimo 6 caracteres"
            required
          />
          <small style="color: #718096; font-size: 0.85rem">Mínimo 6 caracteres</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="password_confirmation">Confirmar Contraseña</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="form.password_confirmation"
            class="form-input"
            placeholder="Repite tu contraseña"
            required
          />
        </div>

        <button
          type="submit"
          class="btn"
          style="width: 100%; padding: 0.75rem; margin-bottom: 1rem"
          :disabled="submitting"
        >
          {{ submitting ? 'Creando...' : 'Crear Cuenta' }}
        </button>
      </form>

      <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e2e8f0">
        <p style="color: #718096; font-size: 0.9rem">
          ¿Ya tienes cuenta?
          <router-link to="/login" style="color: #667eea; font-weight: 600; text-decoration: none">
            Inicia sesión aquí
          </router-link>
        </p>
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
