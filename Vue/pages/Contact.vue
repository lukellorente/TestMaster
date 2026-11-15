<template>
  <div class="page-wrap cn">
    <header class="cn__head">
      <h1 class="cn__title">Contacto</h1>
      <p class="cn__intro">
        Respondemos en menos de 24 horas laborables. Antes de escribir, mira si tu duda ya está en el
        <router-link to="/help">centro de ayuda</router-link>.
      </p>
    </header>

    <div class="cn__layout">
      <!-- ═══ Formulario ═══ -->
      <section class="cn__form-card" aria-labelledby="h-form">
        <h2 id="h-form" class="cn__h2">Escríbenos</h2>
        <p class="cn__sub">Al enviar se abrirá tu programa de correo con el mensaje ya preparado; solo tendrás que pulsar enviar.</p>

        <form novalidate @submit.prevent="handleSubmit">
          <fieldset class="cn__subjects">
            <legend class="cn__legend">¿Sobre qué es tu consulta?</legend>
            <div class="chips">
              <label v-for="subj in subjects" :key="subj.value" class="cn__subject" :class="{ 'is-sel': form.subject === subj.value }">
                <input v-model="form.subject" type="radio" name="subject" :value="subj.value" class="sr-only" />
                {{ subj.label }}
              </label>
            </div>
            <p v-if="errors.subject" class="error-text">{{ errors.subject }}</p>
          </fieldset>

          <div class="cn__row">
            <BaseInput
              v-model="form.name"
              label="Nombre"
              autocomplete="name"
              maxlength="80"
              :error="errors.name"
              @blur="validateField('name')"
            />
            <BaseInput
              v-model="form.email"
              label="Correo electrónico"
              type="email"
              autocomplete="email"
              placeholder="tu@email.com"
              help-text="Te responderemos a esta dirección."
              :error="errors.email"
              @blur="validateField('email')"
            />
          </div>

          <BaseInput
            v-model="form.message"
            label="Mensaje"
            type="textarea"
            rows="6"
            maxlength="2000"
            placeholder="Cuéntanos qué ha pasado, en qué página y qué esperabas que ocurriera."
            :help-text="`${form.message.length} de 2000 caracteres · mínimo 20`"
            :error="errors.message"
            @blur="validateField('message')"
          >
          </BaseInput>

          <label class="cn__privacy">
            <input v-model="form.privacy" type="checkbox" :aria-invalid="errors.privacy ? 'true' : undefined" />
            <span>
              He leído la <router-link to="/privacy">política de privacidad</router-link> y acepto que uséis mis datos para responder a esta consulta.
            </span>
          </label>
          <p v-if="errors.privacy" class="error-text">{{ errors.privacy }}</p>

          <div class="cn__actions">
            <button type="submit" class="btn btn--lg">Preparar correo</button>
            <span class="cn__to">Se enviará a {{ recipient }}</span>
          </div>

          <p v-if="opened" class="cn__opened" role="status">
            Hemos abierto tu programa de correo. Si no se ha abierto, escribe directamente a
            <a :href="`mailto:${recipient}`">{{ recipient }}</a>.
          </p>
        </form>
      </section>

      <!-- ═══ Lateral ═══ -->
      <aside class="cn__side">
        <section class="cn__panel" aria-labelledby="h-channels">
          <h2 id="h-channels" class="cn__h3">Correos directos</h2>
          <ul class="cn__channels">
            <li v-for="c in channels" :key="c.id">
              <span class="cn__channel-title">{{ c.title }}</span>
              <a :href="c.href" :target="c.external ? '_blank' : undefined" :rel="c.external ? 'noopener noreferrer' : undefined">{{ c.value }}</a>
              <span class="cn__channel-meta">{{ c.meta }}</span>
            </li>
          </ul>
        </section>

        <section class="cn__panel" aria-labelledby="h-hours">
          <h2 id="h-hours" class="cn__h3">Horario de atención</h2>
          <dl class="cn__rows">
            <div v-for="slot in schedule" :key="slot.day">
              <dt>{{ slot.day }}</dt>
              <dd class="tabular">{{ slot.closed ? 'Cerrado' : slot.time }}</dd>
            </div>
          </dl>
          <p class="cn__note">Hora peninsular española (CET/CEST).</p>
        </section>

        <section class="cn__panel" aria-labelledby="h-times">
          <h2 id="h-times" class="cn__h3">Tiempos de respuesta</h2>
          <dl class="cn__rows">
            <div v-for="rt in responseTimes" :key="rt.type">
              <dt>{{ rt.type }}</dt>
              <dd>{{ rt.time }}</dd>
            </div>
          </dl>
        </section>

        <section class="cn__panel" aria-labelledby="h-social">
          <h2 id="h-social" class="cn__h3">Síguenos</h2>
          <ul class="cn__social">
            <li v-for="s in socials" :key="s.name">
              <a :href="s.href" target="_blank" rel="noopener noreferrer">
                <i :class="s.icon" aria-hidden="true"></i>{{ s.name }}
              </a>
            </li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import BaseInput from '../components/base/BaseInput.vue';

const channels = [
  { id: 'email', title: 'Soporte general', value: 'lukemartenllorente@gmail.com', meta: 'Respuesta en menos de 24 h', href: 'mailto:lukemartenllorente@gmail.com' },
  { id: 'legal', title: 'Consultas legales', value: 'lukemartenllorente@gmail.com', meta: 'Términos y condiciones', href: 'mailto:lukemartenllorente@gmail.com' },
  { id: 'privacy', title: 'Protección de datos', value: 'lukemartenllorente@gmail.com', meta: 'Derechos RGPD', href: 'mailto:lukemartenllorente@gmail.com' },
  { id: 'twitter', title: 'Twitter / X', value: '@TestMasterApp', meta: 'Respuesta rápida', href: 'https://twitter.com/TestMasterApp', external: true },
];

// Cada asunto se dirige al buzón que corresponde
const subjects = [
  { value: 'soporte', label: 'Soporte técnico', to: 'lukemartenllorente@gmail.com' },
  { value: 'cuenta', label: 'Mi cuenta', to: 'lukemartenllorente@gmail.com' },
  { value: 'contenido', label: 'Contenido de un test', to: 'lukemartenllorente@gmail.com' },
  { value: 'legal', label: 'Legal', to: 'lukemartenllorente@gmail.com' },
  { value: 'rgpd', label: 'Mis datos (RGPD)', to: 'lukemartenllorente@gmail.com' },
  { value: 'sugerencia', label: 'Sugerencia', to: 'lukemartenllorente@gmail.com' },
  { value: 'otro', label: 'Otro', to: 'lukemartenllorente@gmail.com' },
];

const schedule = [
  { day: 'Lunes a viernes', time: '09:00 – 18:00' },
  { day: 'Sábado', time: '10:00 – 14:00' },
  { day: 'Domingo', closed: true },
];

const responseTimes = [
  { type: 'Soporte técnico', time: 'Menos de 24 h laborables' },
  { type: 'Consultas legales', time: 'Menos de 48 h laborables' },
  { type: 'Informes de abuso', time: 'Menos de 12 h' },
  { type: 'Redes sociales', time: 'Menos de 4 h' },
];

const socials = [
  { name: 'Twitter / X', icon: 'fa-brands fa-x-twitter', href: 'https://twitter.com/TestMasterApp' },
  { name: 'GitHub', icon: 'fa-brands fa-github', href: 'https://github.com/testmaster' },
  { name: 'LinkedIn', icon: 'fa-brands fa-linkedin', href: 'https://linkedin.com/company/testmaster' },
];

// ─── Formulario ──────────────────────────────────────────────────────────────
const form = reactive({ subject: '', name: '', email: '', message: '', privacy: false });
const errors = reactive({ subject: '', name: '', email: '', message: '', privacy: '' });
const opened = ref(false);

const currentSubject = computed(() => subjects.find((s) => s.value === form.subject));
const recipient = computed(() => currentSubject.value?.to ?? 'lukemartenllorente@gmail.com');

const validateField = (field) => {
  if (field === 'name') errors.name = form.name.trim() ? '' : 'Escribe tu nombre.';
  if (field === 'email') {
    errors.email = !form.email.trim()
      ? 'Escribe tu correo para poder responderte.'
      : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())
        ? ''
        : 'Este correo no parece válido.';
  }
  if (field === 'message') {
    const len = form.message.trim().length;
    errors.message = !len ? 'Escribe tu mensaje.' : len < 20 ? 'Cuéntanos un poco más (al menos 20 caracteres).' : '';
  }
};

const validateAll = () => {
  ['name', 'email', 'message'].forEach(validateField);
  errors.subject = form.subject ? '' : 'Elige un tema.';
  errors.privacy = form.privacy ? '' : 'Necesitamos tu conformidad para responderte.';
  return !Object.values(errors).some(Boolean);
};

// Sin backend de correo: preparamos el email y lo abre el programa del usuario
const handleSubmit = () => {
  opened.value = false;
  if (!validateAll()) return;

  const subject = `[TestMaster] ${currentSubject.value.label}`;
  const body = `${form.message.trim()}\n\n—\nNombre: ${form.name.trim()}\nCorreo de respuesta: ${form.email.trim()}`;
  window.location.href = `mailto:${recipient.value}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  opened.value = true;
};
</script>

<style scoped>
.cn {
  padding-top: 48px;
}

.cn__head {
  max-width: 720px;
  margin-bottom: 40px;
}

.cn__title {
  font-size: 2.25rem;
  letter-spacing: -0.025em;
  margin: 0 0 8px;
}

.cn__intro {
  font-size: var(--fs-lead);
  color: var(--text-body);
  margin: 0;
}

.cn__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 48px;
  align-items: start;
}

.cn__form-card {
  background: var(--raised);
  border-radius: var(--radius-panel);
  padding: 28px;
}

.cn__form-card :deep(.form-input) {
  background-color: var(--bg);
}

.cn__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0 0 4px;
}

.cn__h3 {
  font-size: var(--fs-body);
  font-weight: 600;
  margin: 0 0 12px;
}

.cn__sub {
  color: var(--text-body);
  font-size: var(--fs-small);
  margin: 0 0 24px;
}

.cn__subjects {
  border: 0;
  padding: 0;
  margin: 0 0 20px;
}

.cn__legend {
  font-size: var(--fs-small);
  font-weight: 500;
  padding: 0;
  margin-bottom: 8px;
}

.cn__subjects .chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.cn__subject {
  display: inline-flex;
  align-items: center;
  height: 36px;
  padding: 0 14px;
  border-radius: var(--radius-pill);
  border: 1px solid var(--border);
  background: var(--bg);
  font-size: var(--fs-small);
  cursor: pointer;
  transition: background 0.15s;
}

.cn__subject:hover {
  background: var(--sunk);
}

.cn__subject:has(input:focus-visible) {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.cn__subject.is-sel {
  background: var(--text);
  border-color: var(--text);
  color: var(--bg);
}

.cn__subjects .error-text {
  margin-top: 6px;
}

.cn__row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.cn__privacy {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  font-size: var(--fs-small);
  color: var(--text-body);
  cursor: pointer;
}

.cn__privacy input {
  width: 18px;
  height: 18px;
  margin-top: 2px;
  accent-color: var(--accent);
  flex-shrink: 0;
}

.cn__actions {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-top: 24px;
}

.cn__to {
  font-size: var(--fs-small);
  color: var(--muted);
}

.cn__opened {
  margin: 16px 0 0;
  font-size: var(--fs-small);
  background: var(--info-bg);
  color: var(--info-ink);
  padding: 12px 14px;
  border-radius: var(--radius-card);
}

/* Lateral */
.cn__side {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.cn__channels,
.cn__social {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.cn__channels li {
  display: flex;
  flex-direction: column;
}

.cn__channel-title {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.cn__channels a {
  font-weight: 500;
  text-decoration: none;
  overflow-wrap: anywhere;
}

.cn__channel-meta {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.cn__rows {
  margin: 0;
}

.cn__rows > div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 0;
  border-top: 1px solid var(--rule);
  font-size: var(--fs-small);
}

.cn__rows dt {
  color: var(--text-body);
}

.cn__rows dd {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

.cn__note {
  font-size: var(--fs-meta);
  color: var(--muted);
  margin: 8px 0 0;
}

.cn__social {
  flex-direction: row;
  flex-wrap: wrap;
  gap: 16px;
}

.cn__social a {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--text-body);
  text-decoration: none;
  font-size: var(--fs-small);
}

.cn__social a:hover {
  color: var(--text);
}

@media (max-width: 960px) {
  .cn__layout {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 640px) {
  .cn {
    padding-top: 32px;
  }

  .cn__title {
    font-size: 1.75rem;
  }

  .cn__form-card {
    padding: 20px;
  }

  .cn__row {
    grid-template-columns: minmax(0, 1fr);
    gap: 0;
  }

  .cn__actions .btn {
    width: 100%;
  }
}
</style>
