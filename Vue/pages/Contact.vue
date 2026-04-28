<template>
  <div class="contact-page">

    <!-- ═══════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════ -->
    <section class="contact-hero">
      <div class="contact-hero-inner">
        <div class="contact-hero-icon">
          <i class="fa-solid fa-paper-plane"></i>
        </div>
        <h1 class="contact-hero-title">¿En qué podemos ayudarte?</h1>
        <p class="contact-hero-sub">
          Nuestro equipo responde en menos de 24 horas laborables.
          Elige la vía que más te convenga.
        </p>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         CANALES RÁPIDOS
    ═══════════════════════════════════════════════════ -->
    <div class="channels-grid">
      <a
        v-for="channel in channels"
        :key="channel.id"
        :href="channel.href"
        :target="channel.external ? '_blank' : undefined"
        :rel="channel.external ? 'noopener noreferrer' : undefined"
        :class="['channel-card', { 'channel-card--primary': channel.primary }]"
      >
        <div :class="['channel-icon', `channel-icon--${channel.color}`]">
          <i :class="channel.icon"></i>
        </div>
        <div class="channel-body">
          <p class="channel-title">{{ channel.title }}</p>
          <p class="channel-value">{{ channel.value }}</p>
          <p class="channel-meta">{{ channel.meta }}</p>
        </div>
        <i class="fa-solid fa-arrow-right channel-arrow"></i>
      </a>
    </div>

    <!-- ═══════════════════════════════════════════════════
         LAYOUT PRINCIPAL
    ═══════════════════════════════════════════════════ -->
    <div class="contact-layout">

      <!-- FORMULARIO -->
      <div class="contact-form-col">
        <div class="form-card">
          <div class="form-card-header">
            <h2 class="form-card-title">Envíanos un mensaje</h2>
            <p class="form-card-sub">Completa el formulario y te responderemos lo antes posible.</p>
          </div>

          <!-- Éxito -->
          <div v-if="submitted" class="form-success">
            <div class="form-success-icon">
              <i class="fa-solid fa-circle-check"></i>
            </div>
            <p class="form-success-title">¡Mensaje enviado!</p>
            <p class="form-success-sub">
              Hemos recibido tu mensaje y te responderemos en menos de 24 horas laborables en
              <strong>{{ form.email }}</strong>.
            </p>
            <button class="form-success-btn" @click="resetForm">
              Enviar otro mensaje
            </button>
          </div>

          <!-- Formulario -->
          <form v-else @submit.prevent="handleSubmit" class="contact-form" novalidate>

            <!-- Asunto -->
            <div class="form-group">
              <label class="form-label">Asunto <span class="required">*</span></label>
              <div class="subject-grid">
                <button
                  v-for="subj in subjects"
                  :key="subj.value"
                  type="button"
                  :class="['subject-btn', { active: form.subject === subj.value }]"
                  @click="form.subject = subj.value"
                >
                  <i :class="subj.icon"></i>
                  {{ subj.label }}
                </button>
              </div>
              <p v-if="errors.subject" class="field-error">{{ errors.subject }}</p>
            </div>

            <!-- Nombre y email -->
            <div class="form-row">
              <div class="form-group">
                <label for="contact-name" class="form-label">
                  Nombre <span class="required">*</span>
                </label>
                <div class="input-wrap">
                  <i class="fa-solid fa-user input-icon"></i>
                  <input
                    id="contact-name"
                    v-model="form.name"
                    type="text"
                    class="form-input"
                    :class="{ 'input-error': errors.name }"
                    placeholder="Tu nombre"
                    maxlength="80"
                    @blur="validateField('name')"
                  />
                </div>
                <p v-if="errors.name" class="field-error">{{ errors.name }}</p>
              </div>

              <div class="form-group">
                <label for="contact-email" class="form-label">
                  Correo electrónico <span class="required">*</span>
                </label>
                <div class="input-wrap">
                  <i class="fa-solid fa-envelope input-icon"></i>
                  <input
                    id="contact-email"
                    v-model="form.email"
                    type="email"
                    class="form-input"
                    :class="{ 'input-error': errors.email }"
                    placeholder="tu@email.com"
                    @blur="validateField('email')"
                  />
                </div>
                <p v-if="errors.email" class="field-error">{{ errors.email }}</p>
              </div>
            </div>

            <!-- Mensaje -->
            <div class="form-group">
              <label for="contact-message" class="form-label">
                Mensaje <span class="required">*</span>
              </label>
              <textarea
                id="contact-message"
                v-model="form.message"
                class="form-input form-textarea"
                :class="{ 'input-error': errors.message }"
                placeholder="Describe tu consulta con el máximo detalle posible…"
                rows="6"
                maxlength="2000"
                @blur="validateField('message')"
              ></textarea>
              <div class="textarea-footer">
                <p v-if="errors.message" class="field-error">{{ errors.message }}</p>
                <span class="char-count" :class="{ 'char-count--near': form.message.length > 1800 }">
                  {{ form.message.length }}/2000
                </span>
              </div>
            </div>

            <!-- Adjunto (UI informativa) -->
            <div class="form-group">
              <label class="form-label">Adjunto <span class="optional">(opcional)</span></label>
              <div
                class="dropzone"
                :class="{ 'dropzone--active': dragActive, 'dropzone--filled': attachment }"
                @dragover.prevent="dragActive = true"
                @dragleave="dragActive = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input
                  ref="fileInput"
                  type="file"
                  accept="image/*,.pdf,.txt"
                  style="display:none"
                  @change="handleFileSelect"
                />
                <template v-if="attachment">
                  <i class="fa-solid fa-file-circle-check dropzone-check"></i>
                  <span class="dropzone-filename">{{ attachment.name }}</span>
                  <span class="dropzone-size">{{ formatSize(attachment.size) }}</span>
                  <button type="button" class="dropzone-remove" @click.stop="attachment = null">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </template>
                <template v-else>
                  <i class="fa-solid fa-cloud-arrow-up dropzone-icon"></i>
                  <span class="dropzone-text">Arrastra un archivo o <u>haz clic aquí</u></span>
                  <span class="dropzone-hint">PNG, JPG, PDF o TXT · Máx. 5 MB</span>
                </template>
              </div>
            </div>

            <!-- Privacidad -->
            <label class="privacy-check">
              <input v-model="form.privacy" type="checkbox" />
              <span>
                He leído y acepto la
                <router-link to="/privacidad" class="privacy-link">Política de Privacidad</router-link>
                y el tratamiento de mis datos para gestionar esta consulta.
              </span>
            </label>
            <p v-if="errors.privacy" class="field-error">{{ errors.privacy }}</p>

            <!-- Submit -->
            <button
              type="submit"
              class="submit-btn"
              :disabled="submitting"
            >
              <i v-if="submitting" class="fa-solid fa-spinner fa-spin"></i>
              <i v-else class="fa-solid fa-paper-plane"></i>
              {{ submitting ? 'Enviando…' : 'Enviar mensaje' }}
            </button>

          </form>
        </div>
      </div>

      <!-- SIDEBAR INFORMATIVO -->
      <aside class="contact-sidebar">

        <!-- Horario -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon info-card-icon--green">
              <i class="fa-solid fa-clock"></i>
            </div>
            <h3 class="info-card-title">Horario de atención</h3>
          </div>
          <div class="schedule-list">
            <div v-for="slot in schedule" :key="slot.day" class="schedule-row">
              <span class="schedule-day">{{ slot.day }}</span>
              <span :class="['schedule-time', { 'schedule-time--closed': slot.closed }]">
                {{ slot.closed ? 'Cerrado' : slot.time }}
              </span>
            </div>
          </div>
          <div class="schedule-note">
            <i class="fa-solid fa-circle-info"></i>
            Hora peninsular española (CET/CEST)
          </div>
        </div>

        <!-- Tiempos de respuesta -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon info-card-icon--blue">
              <i class="fa-solid fa-bolt"></i>
            </div>
            <h3 class="info-card-title">Tiempos de respuesta</h3>
          </div>
          <div class="response-list">
            <div v-for="rt in responseTimes" :key="rt.type" class="response-row">
              <div :class="['response-dot', `response-dot--${rt.color}`]"></div>
              <div class="response-body">
                <span class="response-type">{{ rt.type }}</span>
                <span class="response-time">{{ rt.time }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- FAQ rápida -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon info-card-icon--purple">
              <i class="fa-solid fa-circle-question"></i>
            </div>
            <h3 class="info-card-title">Preguntas frecuentes</h3>
          </div>
          <p class="info-card-desc">
            Antes de escribirnos, puede que ya tengamos la respuesta en nuestro centro de ayuda.
          </p>
          <router-link to="/ayuda" class="info-card-link">
            <i class="fa-solid fa-arrow-right"></i>
            Ir al centro de ayuda
          </router-link>
        </div>

        <!-- Redes -->
        <div class="info-card">
          <div class="info-card-header">
            <div class="info-card-icon info-card-icon--indigo">
              <i class="fa-solid fa-share-nodes"></i>
            </div>
            <h3 class="info-card-title">Síguenos</h3>
          </div>
          <div class="social-list">
            <a
              v-for="social in socials"
              :key="social.name"
              :href="social.href"
              target="_blank"
              rel="noopener noreferrer"
              class="social-item"
            >
              <i :class="social.icon"></i>
              <span>{{ social.name }}</span>
            </a>
          </div>
        </div>

      </aside>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';

// ─── Canales ──────────────────────────────────────────────────────────────────
const channels = [
  {
    id: 'email',
    icon: 'fa-solid fa-envelope',
    color: 'blue',
    title: 'Soporte general',
    value: 'soporte@testmaster.app',
    meta: 'Respuesta en < 24 h',
    href: 'mailto:soporte@testmaster.app',
    primary: true,
  },
  {
    id: 'legal',
    icon: 'fa-solid fa-scale-balanced',
    color: 'purple',
    title: 'Consultas legales',
    value: 'legal@testmaster.app',
    meta: 'Términos y privacidad',
    href: 'mailto:legal@testmaster.app',
  },
  {
    id: 'privacy',
    icon: 'fa-solid fa-shield-halved',
    color: 'green',
    title: 'Protección de datos',
    value: 'privacidad@testmaster.app',
    meta: 'Derechos RGPD',
    href: 'mailto:privacidad@testmaster.app',
  },
  {
    id: 'twitter',
    icon: 'fa-brands fa-x-twitter',
    color: 'gray',
    title: 'Twitter / X',
    value: '@TestMasterApp',
    meta: 'Respuesta rápida',
    href: 'https://twitter.com/TestMasterApp',
    external: true,
  },
];

// ─── Asuntos ──────────────────────────────────────────────────────────────────
const subjects = [
  { value: 'soporte',   label: 'Soporte técnico',  icon: 'fa-solid fa-wrench' },
  { value: 'cuenta',    label: 'Mi cuenta',         icon: 'fa-solid fa-circle-user' },
  { value: 'contenido', label: 'Contenido',         icon: 'fa-solid fa-file-lines' },
  { value: 'legal',     label: 'Legal / RGPD',      icon: 'fa-solid fa-scale-balanced' },
  { value: 'sugerencia',label: 'Sugerencia',        icon: 'fa-solid fa-lightbulb' },
  { value: 'otro',      label: 'Otro',              icon: 'fa-solid fa-ellipsis' },
];

// ─── Horario ──────────────────────────────────────────────────────────────────
const schedule = [
  { day: 'Lunes – Viernes', time: '09:00 – 18:00' },
  { day: 'Sábado',          time: '10:00 – 14:00' },
  { day: 'Domingo',         closed: true },
];

// ─── Tiempos de respuesta ─────────────────────────────────────────────────────
const responseTimes = [
  { type: 'Soporte técnico',  time: '< 24 h laborables', color: 'green' },
  { type: 'Consultas legales', time: '< 48 h laborables', color: 'blue' },
  { type: 'Informes de abuso', time: '< 12 h',            color: 'red' },
  { type: 'Redes sociales',    time: '< 4 h',             color: 'purple' },
];

// ─── Redes ────────────────────────────────────────────────────────────────────
const socials = [
  { name: 'Twitter / X',  icon: 'fa-brands fa-x-twitter', href: 'https://twitter.com/TestMasterApp' },
  { name: 'GitHub',       icon: 'fa-brands fa-github',    href: 'https://github.com/testmaster' },
  { name: 'LinkedIn',     icon: 'fa-brands fa-linkedin',  href: 'https://linkedin.com/company/testmaster' },
];

// ─── Estado del formulario ───────────────────────────────────────────────────
const form = reactive({
  subject:  '',
  name:     '',
  email:    '',
  message:  '',
  privacy:  false,
});

const errors     = reactive({});
const submitting = ref(false);
const submitted  = ref(false);
const dragActive = ref(false);
const attachment = ref(null);
const fileInput  = ref(null);

// ─── Validación ───────────────────────────────────────────────────────────────
const validateField = (field) => {
  delete errors[field];
  if (field === 'name' && !form.name.trim()) {
    errors.name = 'El nombre es obligatorio.';
  }
  if (field === 'email') {
    if (!form.email.trim()) {
      errors.email = 'El correo electrónico es obligatorio.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
      errors.email = 'Introduce un correo electrónico válido.';
    }
  }
  if (field === 'message') {
    if (!form.message.trim()) {
      errors.message = 'El mensaje es obligatorio.';
    } else if (form.message.trim().length < 20) {
      errors.message = 'El mensaje debe tener al menos 20 caracteres.';
    }
  }
};

const validateAll = () => {
  ['name', 'email', 'message'].forEach(validateField);
  if (!form.subject)  errors.subject  = 'Selecciona un asunto.';
  if (!form.privacy)  errors.privacy  = 'Debes aceptar la política de privacidad.';
  return Object.keys(errors).length === 0;
};

// ─── Envío ────────────────────────────────────────────────────────────────────
const handleSubmit = async () => {
  Object.keys(errors).forEach(k => delete errors[k]);
  if (!validateAll()) return;

  submitting.value = true;
  // Simulación de envío (sustituir por llamada real a la API)
  await new Promise(r => setTimeout(r, 1400));
  submitting.value = false;
  submitted.value  = true;
};

const resetForm = () => {
  form.subject  = '';
  form.name     = '';
  form.email    = '';
  form.message  = '';
  form.privacy  = false;
  attachment.value = null;
  submitted.value  = false;
  Object.keys(errors).forEach(k => delete errors[k]);
};

// ─── Adjuntos ─────────────────────────────────────────────────────────────────
const handleFileSelect = (e) => {
  const file = e.target.files?.[0];
  if (file) applyFile(file);
};

const handleDrop = (e) => {
  dragActive.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) applyFile(file);
};

const applyFile = (file) => {
  if (file.size > 5 * 1024 * 1024) {
    alert('El archivo no puede superar los 5 MB.');
    return;
  }
  attachment.value = file;
};

const formatSize = (bytes) => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};
</script>

<style scoped>
/* ── Página ──────────────────────────────────────────────────────────────── */
.contact-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.contact-hero {
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  width: 100vw;
  margin-top: -2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 3.5rem 1.5rem 3rem;
}

.contact-hero-inner {
  max-width: 560px;
  margin: 0 auto;
  text-align: center;
}

.contact-hero-icon {
  font-size: 2.5rem;
  color: rgba(255,255,255,0.85);
  margin-bottom: 0.75rem;
}

.contact-hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.contact-hero-sub {
  font-size: 1rem;
  color: rgba(255,255,255,0.82);
  line-height: 1.65;
}

/* ── Canales ─────────────────────────────────────────────────────────────── */
.channels-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
}

.channel-card {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem 1.1rem;
  text-decoration: none;
  transition: all 0.2s;
  min-width: 0;
}

.channel-card:hover {
  border-color: #667eea;
  box-shadow: 0 3px 12px rgba(102,126,234,0.1);
  transform: translateY(-2px);
}

.channel-card--primary {
  border-color: #c3cfe2;
  background: linear-gradient(135deg, #f7f9ff 0%, #eef2ff 100%);
}

.channel-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.channel-icon--blue   { background: #ebf0ff; color: #4c5fd5; }
.channel-icon--purple { background: #f3e8ff; color: #7c3aed; }
.channel-icon--green  { background: #d1fae5; color: #059669; }
.channel-icon--gray   { background: #f1f5f9; color: #475569; }

.channel-body {
  flex: 1;
  min-width: 0;
}

.channel-title {
  font-size: 11px;
  font-weight: 600;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 2px;
}

.channel-value {
  font-size: 13px;
  font-weight: 600;
  color: #2d3748;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.channel-meta {
  font-size: 11px;
  color: #a0aec0;
  margin-top: 1px;
}

.channel-arrow {
  font-size: 11px;
  color: #cbd5e0;
  flex-shrink: 0;
  transition: transform 0.2s, color 0.2s;
}

.channel-card:hover .channel-arrow {
  color: #667eea;
  transform: translateX(3px);
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.contact-layout {
  display: grid;
  grid-template-columns: 1fr 280px;
  gap: 1.5rem;
  align-items: start;
}

/* ── Formulario ──────────────────────────────────────────────────────────── */
.form-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
}

.form-card-header {
  padding: 1.5rem 1.75rem 1.25rem;
  border-bottom: 1px solid #f7fafc;
}

.form-card-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a202c;
  margin-bottom: 0.25rem;
}

.form-card-sub {
  font-size: 13.5px;
  color: #a0aec0;
}

.contact-form {
  padding: 1.5rem 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Éxito */
.form-success {
  padding: 3rem 1.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.75rem;
}

.form-success-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #d1fae5;
  color: #059669;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.form-success-title {
  font-size: 1.15rem;
  font-weight: 600;
  color: #1a202c;
}

.form-success-sub {
  font-size: 14px;
  color: #718096;
  line-height: 1.65;
  max-width: 360px;
}

.form-success-btn {
  margin-top: 0.5rem;
  padding: 9px 22px;
  border-radius: 9px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 13.5px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}

.form-success-btn:hover {
  box-shadow: 0 4px 12px rgba(102,126,234,0.35);
  transform: translateY(-1px);
}

/* Grupos y campos */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.form-label {
  font-size: 13px;
  font-weight: 500;
  color: #2d3748;
}

.required { color: #ef4444; }
.optional  { color: #a0aec0; font-weight: 400; }

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.input-wrap {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.85rem;
  pointer-events: none;
}

.form-input {
  width: 100%;
  padding: 0.65rem 0.9rem 0.65rem 2.35rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 9px;
  font-size: 14px;
  font-family: inherit;
  color: #2d3748;
  background: #f8f9fa;
  transition: all 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.form-input.input-error {
  border-color: #ef4444;
  background: #fff5f5;
}

.form-textarea {
  padding-left: 0.9rem;
  resize: vertical;
  min-height: 140px;
}

.textarea-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 18px;
}

.char-count {
  font-size: 11px;
  color: #a0aec0;
  margin-left: auto;
}

.char-count--near { color: #f59e0b; }

.field-error {
  font-size: 12px;
  color: #e53e3e;
  margin-top: 0;
}

/* Asuntos */
.subject-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.subject-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.6rem 0.5rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 9px;
  font-size: 12.5px;
  font-family: inherit;
  color: #718096;
  background: #f8f9fa;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}

.subject-btn i { font-size: 11px; }

.subject-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f0f4ff;
}

.subject-btn.active {
  border-color: #667eea;
  background: #ebf0ff;
  color: #4c5fd5;
  font-weight: 500;
}

/* Dropzone */
.dropzone {
  border: 2px dashed #e2e8f0;
  border-radius: 10px;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: all 0.2s;
  background: #f8f9fa;
  text-align: center;
}

.dropzone:hover,
.dropzone--active {
  border-color: #667eea;
  background: #f0f4ff;
}

.dropzone--filled {
  border-style: solid;
  border-color: #10b981;
  background: #f0fff4;
  flex-direction: row;
  justify-content: center;
  flex-wrap: wrap;
}

.dropzone-icon {
  font-size: 1.5rem;
  color: #cbd5e0;
}

.dropzone--active .dropzone-icon { color: #667eea; }

.dropzone-text {
  font-size: 13px;
  color: #718096;
}

.dropzone-hint {
  font-size: 11.5px;
  color: #a0aec0;
}

.dropzone-check {
  font-size: 1.2rem;
  color: #059669;
}

.dropzone-filename {
  font-size: 13px;
  font-weight: 500;
  color: #2d3748;
}

.dropzone-size {
  font-size: 11px;
  color: #a0aec0;
}

.dropzone-remove {
  background: #fee2e2;
  border: none;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #dc2626;
  font-size: 10px;
  transition: background 0.15s;
  flex-shrink: 0;
}

.dropzone-remove:hover { background: #fecaca; }

/* Privacidad */
.privacy-check {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  font-size: 13px;
  color: #718096;
  line-height: 1.55;
  cursor: pointer;
}

.privacy-check input { margin-top: 2px; flex-shrink: 0; accent-color: #667eea; }

.privacy-link {
  color: #667eea;
  font-weight: 500;
  text-decoration: none;
}

.privacy-link:hover { text-decoration: underline; }

/* Botón enviar */
.submit-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.8rem 1.5rem;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 14.5px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: all 0.2s;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(102,126,234,0.4);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* ── Sidebar info ─────────────────────────────────────────────────────────── */
.contact-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: sticky;
  top: 80px;
}

.info-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.info-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.info-card-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  flex-shrink: 0;
}

.info-card-icon--green  { background: #d1fae5; color: #059669; }
.info-card-icon--blue   { background: #ebf0ff; color: #4c5fd5; }
.info-card-icon--purple { background: #f3e8ff; color: #7c3aed; }
.info-card-icon--indigo { background: #e0e7ff; color: #4338ca; }

.info-card-title {
  font-size: 13.5px;
  font-weight: 600;
  color: #2d3748;
}

/* Horario */
.schedule-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  margin-bottom: 0.75rem;
}

.schedule-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12.5px;
}

.schedule-day { color: #4a5568; }

.schedule-time {
  font-weight: 500;
  color: #059669;
  font-size: 12px;
}

.schedule-time--closed { color: #a0aec0; }

.schedule-note {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 11px;
  color: #a0aec0;
  padding-top: 0.65rem;
  border-top: 1px solid #f7fafc;
}

.schedule-note i { color: #cbd5e0; }

/* Tiempos de respuesta */
.response-list {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.response-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.response-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.response-dot--green  { background: #10b981; }
.response-dot--blue   { background: #3b82f6; }
.response-dot--red    { background: #ef4444; }
.response-dot--purple { background: #8b5cf6; }

.response-body {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex: 1;
}

.response-type { font-size: 12px; color: #718096; }
.response-time { font-size: 11.5px; font-weight: 600; color: #4a5568; }

/* FAQ link */
.info-card-desc {
  font-size: 12.5px;
  color: #a0aec0;
  line-height: 1.55;
  margin-bottom: 0.85rem;
}

.info-card-link {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 12.5px;
  font-weight: 600;
  color: #667eea;
  text-decoration: none;
  transition: gap 0.2s;
}

.info-card-link:hover { gap: 0.65rem; }

/* Social */
.social-list {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.social-item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.5rem 0.65rem;
  border-radius: 8px;
  font-size: 13px;
  color: #718096;
  text-decoration: none;
  transition: all 0.15s;
}

.social-item i { font-size: 14px; width: 18px; text-align: center; }

.social-item:hover {
  background: #f8f9fa;
  color: #2d3748;
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .channel-card,
body.dark-mode .form-card,
body.dark-mode .info-card {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .channel-card--primary {
  background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.08) 100%) !important;
}

body.dark-mode .channel-value,
body.dark-mode .form-card-title,
body.dark-mode .form-label,
body.dark-mode .info-card-title,
body.dark-mode .schedule-day,
body.dark-mode .response-time,
body.dark-mode .form-success-title {
  color: var(--tm-text) !important;
}

body.dark-mode .channel-meta,
body.dark-mode .channel-title,
body.dark-mode .form-card-sub,
body.dark-mode .acceptance-text,
body.dark-mode .response-type,
body.dark-mode .info-card-desc,
body.dark-mode .social-item,
body.dark-mode .schedule-note,
body.dark-mode .form-success-sub,
body.dark-mode .privacy-check {
  color: var(--tm-muted) !important;
}

body.dark-mode .form-input {
  background: var(--tm-bg) !important;
  border-color: var(--tm-border) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .form-input:focus {
  background: var(--tm-surface) !important;
  border-color: #667eea !important;
}

body.dark-mode .form-input.input-error {
  background: rgba(239,68,68,0.08) !important;
  border-color: #ef4444 !important;
}

body.dark-mode .form-input::placeholder {
  color: var(--tm-muted) !important;
}

body.dark-mode .subject-btn {
  background: var(--tm-bg) !important;
  border-color: var(--tm-border) !important;
  color: var(--tm-muted) !important;
}

body.dark-mode .subject-btn:hover {
  border-color: #667eea !important;
  color: #a78bfa !important;
  background: rgba(102,126,234,0.08) !important;
}

body.dark-mode .subject-btn.active {
  background: rgba(102,126,234,0.15) !important;
  border-color: #667eea !important;
  color: #a78bfa !important;
}

body.dark-mode .dropzone {
  background: var(--tm-bg) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .dropzone:hover,
body.dark-mode .dropzone--active {
  border-color: #667eea !important;
  background: rgba(102,126,234,0.08) !important;
}

body.dark-mode .dropzone--filled {
  background: rgba(16,185,129,0.08) !important;
  border-color: #10b981 !important;
}

body.dark-mode .dropzone-text { color: var(--tm-muted) !important; }
body.dark-mode .dropzone-filename { color: var(--tm-text) !important; }

body.dark-mode .form-card-header,
body.dark-mode .schedule-note {
  border-color: var(--tm-border) !important;
}

body.dark-mode .form-success-icon {
  background: rgba(16,185,129,0.15) !important;
}

body.dark-mode .social-item:hover {
  background: var(--tm-hover) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .channel-icon--blue   { background: rgba(76,95,213,0.18) !important; }
body.dark-mode .channel-icon--purple { background: rgba(124,58,237,0.18) !important; }
body.dark-mode .channel-icon--green  { background: rgba(5,150,105,0.18) !important; }
body.dark-mode .channel-icon--gray   { background: rgba(71,85,105,0.18) !important; }

body.dark-mode .info-card-icon--green  { background: rgba(5,150,105,0.18) !important; }
body.dark-mode .info-card-icon--blue   { background: rgba(76,95,213,0.18) !important; }
body.dark-mode .info-card-icon--purple { background: rgba(124,58,237,0.18) !important; }
body.dark-mode .info-card-icon--indigo { background: rgba(67,56,202,0.18) !important; }

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .channels-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 900px) {
  .contact-layout {
    grid-template-columns: 1fr;
  }

  .contact-sidebar {
    position: static;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
}

@media (max-width: 640px) {
  .contact-hero {
    padding: 2.5rem 1rem 2.25rem;
  }

  .contact-hero-title {
    font-size: 1.6rem;
  }

  .channels-grid {
    grid-template-columns: 1fr 1fr;
  }

  .channel-body { display: none; }
  .channel-card {
    flex-direction: column;
    text-align: center;
    gap: 0.4rem;
    padding: 0.9rem 0.5rem;
  }
  .channel-body { display: block; }
  .channel-arrow { display: none; }
  .channel-value { font-size: 11px; }

  .form-row {
    grid-template-columns: 1fr;
  }

  .subject-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .contact-form {
    padding: 1.25rem;
  }

  .form-card-header {
    padding: 1.25rem;
  }

  .contact-sidebar {
    grid-template-columns: 1fr;
  }
}
</style>