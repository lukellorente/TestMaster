<template>
  <AuthShell title="Iniciar sesión" subtitle="Entra para hacer tests y guardar tus notas.">
    <div v-if="error" class="auth-error" role="alert">{{ error }}</div>

    <form novalidate @submit.prevent="handleLogin">
      <BaseInput
        v-model="form.email"
        label="Correo electrónico"
        type="email"
        autocomplete="email"
        placeholder="tu@email.com"
        :error="fieldErrors.email"
        required
        autofocus
      />
      <BaseInput
        v-model="form.password"
        label="Contraseña"
        type="password"
        autocomplete="current-password"
        :error="fieldErrors.password"
        required
      />
      <BaseButton type="submit" size="lg" :loading="submitting" class="auth-submit">
        {{ submitting ? 'Entrando…' : 'Iniciar sesión' }}
      </BaseButton>
    </form>

    <template #footer>
      ¿No tienes cuenta? <router-link :to="{ path: '/register', query: route.query }">Crea una gratis</router-link>
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

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const form = reactive({ email: '', password: '' });
const fieldErrors = reactive({ email: '', password: '' });
const error = ref('');
const submitting = ref(false);

onMounted(() => {
  if (authStore.isLoggedIn) router.replace(safeRedirect(route.query.redirect));
});

const validate = () => {
  fieldErrors.email = !form.email.trim()
    ? 'Escribe tu correo electrónico.'
    : !/^\S+@\S+\.\S+$/.test(form.email.trim())
      ? 'Este correo no parece válido.'
      : '';
  fieldErrors.password = form.password ? '' : 'Escribe tu contraseña.';
  return !fieldErrors.email && !fieldErrors.password;
};

const handleLogin = async () => {
  error.value = '';
  if (!validate()) return;
  submitting.value = true;
  try {
    await authStore.login(form.email.trim(), form.password);
    router.push(safeRedirect(route.query.redirect));
  } catch (err) {
    const errs = err.response?.data?.errors;
    if (errs?.email || errs?.password) {
      fieldErrors.email = errs.email?.[0] ?? '';
      fieldErrors.password = errs.password?.[0] ?? '';
    } else {
      error.value = err.response?.data?.message || 'El correo o la contraseña no son correctos.';
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

.auth-submit {
  width: 100%;
  margin-top: 8px;
}
</style>
