<template>
  <AuthShell title="Crear cuenta" subtitle="Es gratis. Podrás hacer tests, guardar tus notas y crear los tuyos.">
    <div v-if="error" class="auth-error" role="alert">{{ error }}</div>

    <form novalidate @submit.prevent="handleRegister">
      <BaseInput
        v-model="form.name"
        label="Nombre"
        autocomplete="name"
        maxlength="100"
        :error="fieldErrors.name"
        required
        autofocus
      />
      <BaseInput
        v-model="form.email"
        label="Correo electrónico"
        type="email"
        autocomplete="email"
        placeholder="tu@email.com"
        :error="fieldErrors.email"
        required
      />
      <BaseInput
        v-model="form.password"
        label="Contraseña"
        type="password"
        autocomplete="new-password"
        help-text="Al menos 8 caracteres."
        :error="fieldErrors.password"
        required
      />
      <BaseInput
        v-model="form.password_confirmation"
        label="Repite la contraseña"
        type="password"
        autocomplete="new-password"
        :error="fieldErrors.password_confirmation"
        required
      />
      <label class="auth-consent" :class="{ 'auth-consent--error': fieldErrors.accept_terms }">
        <input v-model="form.accept_terms" type="checkbox" required />
        <span>
          Tengo 14 años o más y he leído la
          <router-link to="/privacy" target="_blank">Política de Privacidad</router-link>
          y acepto los <router-link to="/terms" target="_blank">Términos de uso</router-link>.
        </span>
      </label>
      <p v-if="fieldErrors.accept_terms" class="auth-consent__error" role="alert">{{ fieldErrors.accept_terms }}</p>
      <BaseButton type="submit" size="lg" :loading="submitting" class="auth-submit">
        {{ submitting ? 'Creando cuenta…' : 'Crear cuenta' }}
      </BaseButton>
    </form>

    <template #footer>
      ¿Ya tienes cuenta? <router-link :to="{ path: '/login', query: route.query }">Inicia sesión</router-link>
    </template>
  </AuthShell>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AuthShell from '../components/auth/AuthShell.vue';
import BaseButton from '../components/base/BaseButton.vue';
import BaseInput from '../components/base/BaseInput.vue';
import { safeRedirect } from '../utils/redirect';
import { useToast } from '../composables/useToast';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();

const form = reactive({ name: '', email: '', password: '', password_confirmation: '', accept_terms: false });
const fieldErrors = reactive({ name: '', email: '', password: '', password_confirmation: '', accept_terms: '' });
const error = ref('');
const submitting = ref(false);

onMounted(() => {
  if (authStore.isLoggedIn) router.replace(safeRedirect(route.query.redirect));
});

const validate = () => {
  fieldErrors.name = form.name.trim() ? '' : 'Escribe tu nombre.';
  fieldErrors.email = !form.email.trim()
    ? 'Escribe tu correo electrónico.'
    : !/^\S+@\S+\.\S+$/.test(form.email.trim())
      ? 'Este correo no parece válido.'
      : '';
  fieldErrors.password = form.password.length >= 8 ? '' : 'La contraseña necesita al menos 8 caracteres.';
  fieldErrors.password_confirmation =
    form.password_confirmation === form.password ? '' : 'Las contraseñas no coinciden.';
  fieldErrors.accept_terms = form.accept_terms
    ? ''
    : 'Debes confirmar que tienes al menos 14 años y aceptar la Política de Privacidad y los Términos.';
  return !Object.values(fieldErrors).some(Boolean);
};

const handleRegister = async () => {
  error.value = '';
  if (!validate()) return;
  submitting.value = true;
  try {
    await authStore.register(form.name.trim(), form.email.trim(), form.password, form.password_confirmation, form.accept_terms);
    toast.success(`Cuenta creada. ¡Bienvenido, ${form.name.trim().split(' ')[0]}!`);
    router.push(safeRedirect(route.query.redirect));
  } catch (err) {
    const errs = err.response?.data?.errors;
    if (errs) {
      for (const key of Object.keys(fieldErrors)) fieldErrors[key] = errs[key]?.[0] ?? '';
      if (!Object.values(fieldErrors).some(Boolean)) error.value = Object.values(errs).flat().join(' ');
    } else {
      error.value = err.response?.data?.message || 'No se ha podido crear la cuenta. Inténtalo de nuevo.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.auth-error {
  background: var(--negative-bg);
  color: var(--negative-ink);
  border-radius: var(--radius-card);
  padding: 12px 14px;
  font-size: var(--fs-small);
  margin-bottom: 16px;
}

.auth-consent {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: var(--fs-small);
  line-height: 1.45;
  margin: 4px 0 8px;
  cursor: pointer;
}

.auth-consent input {
  margin-top: 3px;
  flex-shrink: 0;
  width: 16px;
  height: 16px;
}

.auth-consent--error {
  color: var(--negative-ink);
}

.auth-consent__error {
  color: var(--negative-ink);
  font-size: var(--fs-small);
  margin: 0 0 8px;
}

.auth-submit {
  width: 100%;
  margin-top: 8px;
}
</style>
