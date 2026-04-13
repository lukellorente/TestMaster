<template>
  <div class="help-page">

    <!-- ═══════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════ -->
    <section class="help-hero">
      <div class="help-hero-inner">
        <div class="help-hero-icon">
          <i class="fa-solid fa-circle-question"></i>
        </div>
        <h1 class="help-hero-title">Centro de Ayuda</h1>
        <p class="help-hero-sub">
          Encuentra respuestas a tus preguntas y aprende a sacar el máximo partido a TestMaster.
        </p>
        <div class="help-search-wrap">
          <i class="fa-solid fa-magnifying-glass help-search-icon"></i>
          <input
            v-model="searchQuery"
            type="text"
            class="help-search-input"
            placeholder="Buscar en la ayuda..."
            @input="filterFAQs"
          />
          <button v-if="searchQuery" class="help-search-clear" @click="clearSearch">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         CATEGORÍAS RÁPIDAS
    ═══════════════════════════════════════════════════ -->
    <section class="quick-cats">
      <button
        v-for="cat in categories"
        :key="cat.id"
        :class="['qcat-card', { active: activeCategory === cat.id }]"
        @click="setCategory(cat.id)"
      >
        <div class="qcat-icon">
          <i :class="cat.icon"></i>
        </div>
        <span class="qcat-label">{{ cat.label }}</span>
      </button>
    </section>

    <!-- ═══════════════════════════════════════════════════
         LAYOUT PRINCIPAL
    ═══════════════════════════════════════════════════ -->
    <div class="help-layout">

      <!-- SIDEBAR DE CATEGORÍAS -->
      <aside class="help-sidebar">
        <div class="sidebar-card">
          <p class="sidebar-eyebrow">Categorías</p>
          <nav class="sidebar-nav">
            <button
              v-for="cat in categories"
              :key="cat.id"
              :class="['sidebar-item', { active: activeCategory === cat.id }]"
              @click="setCategory(cat.id)"
            >
              <i :class="cat.icon"></i>
              <span>{{ cat.label }}</span>
              <span class="sidebar-count">{{ getCategoryCount(cat.id) }}</span>
            </button>
          </nav>
        </div>

        <div class="contact-card">
          <div class="contact-icon">
            <i class="fa-solid fa-headset"></i>
          </div>
          <p class="contact-title">¿No encuentras lo que buscas?</p>
          <p class="contact-sub">Nuestro equipo está disponible para ayudarte.</p>
          <a href="mailto:soporte@testmaster.app" class="contact-btn">
            <i class="fa-solid fa-envelope"></i>
            Contactar soporte
          </a>
        </div>
      </aside>

      <!-- CONTENIDO PRINCIPAL -->
      <div class="help-main">

        <!-- Sin resultados -->
        <div v-if="filteredFAQs.length === 0" class="empty-help">
          <i class="fa-solid fa-magnifying-glass empty-help-icon"></i>
          <p class="empty-help-title">Sin resultados</p>
          <p class="empty-help-sub">No se encontraron artículos para "<strong>{{ searchQuery }}</strong>".</p>
          <button class="empty-help-btn" @click="clearSearch">Limpiar búsqueda</button>
        </div>

        <!-- Grupos de FAQs -->
        <template v-else>
          <div
            v-for="group in groupedFAQs"
            :key="group.categoryId"
            class="faq-group"
          >
            <div class="faq-group-header">
              <div class="faq-group-icon">
                <i :class="getCategoryIcon(group.categoryId)"></i>
              </div>
              <h2 class="faq-group-title">{{ getCategoryLabel(group.categoryId) }}</h2>
              <span class="faq-group-badge">{{ group.items.length }}</span>
            </div>

            <div class="faq-list">
              <div
                v-for="faq in group.items"
                :key="faq.id"
                :class="['faq-item', { open: openFAQ === faq.id }]"
              >
                <button class="faq-question" @click="toggleFAQ(faq.id)">
                  <span>{{ faq.question }}</span>
                  <i :class="['fa-solid', openFAQ === faq.id ? 'fa-chevron-up' : 'fa-chevron-down', 'faq-chevron']"></i>
                </button>
                <div v-show="openFAQ === faq.id" class="faq-answer">
                  <p v-for="(para, i) in faq.answer" :key="i">{{ para }}</p>
                  <div v-if="faq.tip" class="faq-tip">
                    <i class="fa-solid fa-lightbulb"></i>
                    <span>{{ faq.tip }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════
         GUÍAS RÁPIDAS
    ═══════════════════════════════════════════════════ -->
    <section class="guides-section">
      <h2 class="guides-title">Guías de inicio rápido</h2>
      <div class="guides-grid">
        <div
          v-for="guide in guides"
          :key="guide.id"
          class="guide-card"
        >
          <div :class="['guide-badge', `guide-badge--${guide.color}`]">
            <i :class="guide.icon"></i>
          </div>
          <div class="guide-body">
            <p class="guide-label">{{ guide.title }}</p>
            <p class="guide-desc">{{ guide.description }}</p>
          </div>
          <div class="guide-steps">
            <div
              v-for="(step, idx) in guide.steps"
              :key="idx"
              class="guide-step"
            >
              <span class="guide-step-num">{{ idx + 1 }}</span>
              <span class="guide-step-text">{{ step }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

// ─── Estado ──────────────────────────────────────────────────────────────────
const searchQuery   = ref('');
const activeCategory = ref('all');
const openFAQ       = ref(null);

// ─── Categorías ───────────────────────────────────────────────────────────────
const categories = [
  { id: 'all',      label: 'Todo',            icon: 'fa-solid fa-border-all' },
  { id: 'account',  label: 'Mi cuenta',       icon: 'fa-solid fa-circle-user' },
  { id: 'tests',    label: 'Tests',           icon: 'fa-solid fa-file-lines' },
  { id: 'results',  label: 'Resultados',      icon: 'fa-solid fa-chart-bar' },
  { id: 'security', label: 'Seguridad',       icon: 'fa-solid fa-shield-halved' },
];

// ─── FAQs ────────────────────────────────────────────────────────────────────
const allFAQs = [
  {
    id: 1,
    category: 'account',
    question: '¿Cómo me registro en TestMaster?',
    answer: [
      'Registrarte es gratuito y solo tarda unos segundos. Haz clic en el botón "Registrarse" en la parte superior derecha de la pantalla.',
      'Puedes crear una cuenta con tu correo electrónico y una contraseña, o usar Google o GitHub para un registro más rápido.',
    ],
    tip: 'Una vez registrado, podrás crear, realizar y guardar tests ilimitados.',
  },
  {
    id: 2,
    category: 'account',
    question: '¿Cómo cambio mi foto de perfil?',
    answer: [
      'Accede a tu perfil haciendo clic en tu nombre en la esquina superior derecha y selecciona "mi panel".',
      'En tu página de perfil, pasa el cursor sobre tu avatar y haz clic en el icono de cámara que aparece. Se admiten imágenes JPEG, PNG, GIF y WebP de hasta 2 MB.',
    ],
  },
  {
    id: 3,
    category: 'account',
    question: '¿Cómo cambio mi contraseña?',
    answer: [
      'En la actualidad, el cambio de contraseña está disponible desde la sección de ajustes de cuenta. Si has olvidado tu contraseña, usa la opción "¿Olvidaste tu contraseña?" en la pantalla de inicio de sesión.',
    ],
    tip: 'Si iniciaste sesión con Google o GitHub no necesitas contraseña.',
  },
  {
    id: 4,
    category: 'account',
    question: '¿Puedo tener varias cuentas?',
    answer: [
      'Cada persona debe tener una única cuenta. El uso de múltiples cuentas para manipular estadísticas o clasificaciones está prohibido por nuestros términos de uso.',
    ],
  },
  {
    id: 5,
    category: 'tests',
    question: '¿Cómo creo un test?',
    answer: [
      'Inicia sesión y haz clic en "crear test" en la barra de navegación. Rellena el título, descripción y configura las opciones del test.',
      'Puedes añadir tres tipos de preguntas: opción múltiple, verdadero/falso y respuesta corta. Pulsa el botón correspondiente para añadir cada tipo.',
      'Cuando hayas terminado, elige entre guardar como borrador o publicar directamente.',
    ],
    tip: 'Puedes reordenar preguntas en cualquier momento antes de publicar el test.',
  },
  {
    id: 6,
    category: 'tests',
    question: '¿Qué significa publicar un test?',
    answer: [
      'Un test publicado es visible para todos los usuarios de TestMaster y aparece en la página principal.',
      'Los borradores solo son visibles para ti y no aparecen en las búsquedas públicas. Puedes convertir un borrador en publicado desde el panel de edición.',
    ],
  },
  {
    id: 7,
    category: 'tests',
    question: '¿Puedo editar un test después de publicarlo?',
    answer: [
      'Sí. Accede a la página del test y, si eres el creador, verás el botón "Editar test completo" en el panel lateral.',
      'Ten en cuenta que modificar las preguntas o respuestas correctas puede afectar a los intentos ya registrados.',
    ],
  },
  {
    id: 8,
    category: 'tests',
    question: '¿Cómo elimino un test?',
    answer: [
      'Ve a la página del test que quieres eliminar. Si eres el propietario, encontrarás el botón "Eliminar test" en el panel de gestión.',
      'La eliminación es permanente e irreversible; todos los intentos y resultados asociados también se borrarán.',
    ],
    tip: 'Antes de eliminar, considera cambiar el test a "borrador" para ocultarlo temporalmente.',
  },
  {
    id: 9,
    category: 'tests',
    question: '¿Qué tipos de preguntas puedo usar?',
    answer: [
      'TestMaster admite tres tipos de preguntas:',
      '· Opción múltiple: el usuario elige una respuesta entre varias opciones (A, B, C, D…).',
      '· Verdadero/Falso: el usuario indica si un enunciado es verdadero o falso.',
      '· Respuesta corta: el usuario escribe una respuesta de texto libre que se compara sin distinción de mayúsculas/minúsculas.',
    ],
  },
  {
    id: 10,
    category: 'tests',
    question: '¿Cómo configuro el tiempo límite?',
    answer: [
      'En la sección "Configuración del test" durante la creación o edición, encontrarás el campo "Tiempo límite (minutos)".',
      'Introduce el número de minutos o deja el campo en 0 para no establecer límite. Durante el test aparecerá un contador regresivo visible en la esquina.',
    ],
  },
  {
    id: 11,
    category: 'results',
    question: '¿Dónde veo mis resultados?',
    answer: [
      'Al terminar un test serás redirigido automáticamente a la página de resultados, donde verás tu puntuación, el detalle de cada respuesta y, si el creador lo ha activado, las explicaciones.',
      'También puedes consultar tu historial de tests completados en tu perfil, dentro de la pestaña "Completados".',
    ],
  },
  {
    id: 12,
    category: 'results',
    question: '¿Cómo se calcula la puntuación?',
    answer: [
      'La puntuación se calcula como el porcentaje de preguntas respondidas correctamente sobre el total. Si las preguntas tienen pesos distintos (puntos personalizados), se usa una media ponderada.',
      'El creador del test define el porcentaje mínimo para aprobar (por defecto, 70%).',
    ],
    tip: 'Las preguntas sin responder cuentan como incorrectas.',
  },
  {
    id: 13,
    category: 'results',
    question: '¿Puedo repetir un test?',
    answer: [
      'Sí, siempre que el creador lo permita. Cada test tiene un número máximo de intentos configurado (por defecto, ilimitados).',
      'Si has agotado tus intentos, verás un aviso en la página del test.',
    ],
  },
  {
    id: 14,
    category: 'results',
    question: '¿Por qué no veo las respuestas correctas?',
    answer: [
      'Que se muestren o no las respuestas correctas y las explicaciones es una decisión del creador del test.',
      'Si el creador ha desactivado esta opción, solo verás si cada respuesta fue correcta o incorrecta, pero no cuál era la respuesta correcta.',
    ],
  },
  {
    id: 15,
    category: 'security',
    question: '¿Cómo protege TestMaster mis datos?',
    answer: [
      'Todas las comunicaciones entre tu navegador y nuestros servidores se realizan mediante HTTPS cifrado.',
      'Las contraseñas se almacenan de forma segura usando algoritmos de hash irreversibles. Nunca guardamos tu contraseña en texto plano.',
    ],
  },
  {
    id: 16,
    category: 'security',
    question: '¿Qué datos recopila TestMaster?',
    answer: [
      'Recopilamos la información que proporcionas al registrarte (nombre, correo electrónico), los tests que creas y los resultados de los intentos que realizas.',
      'Consulta nuestra política de privacidad para una descripción completa del tratamiento de datos.',
    ],
  },
  {
    id: 17,
    category: 'security',
    question: '¿Cómo cierro sesión?',
    answer: [
      'Haz clic en "salir" en la barra de navegación. Tu sesión se cerrará de forma segura y serás redirigido a la página de inicio.',
      'Para mayor seguridad, cierra siempre sesión cuando uses un dispositivo compartido.',
    ],
  },
];

// ─── Guías de inicio rápido ───────────────────────────────────────────────────
const guides = [
  {
    id: 1,
    title: 'Realizar tu primer test',
    description: 'Empieza a aprender respondiendo tests de la comunidad.',
    icon: 'fa-solid fa-play',
    color: 'blue',
    steps: [
      'Explora los tests en la página principal',
      'Haz clic en el test que te interese',
      'Pulsa "Empezar test" en el panel lateral',
      'Responde las preguntas y envía',
    ],
  },
  {
    id: 2,
    title: 'Crear y publicar un test',
    description: 'Comparte tu conocimiento con la comunidad.',
    icon: 'fa-solid fa-pen-to-square',
    color: 'purple',
    steps: [
      'Inicia sesión y pulsa "crear test"',
      'Añade título, descripción y categoría',
      'Crea tus preguntas y respuestas',
      'Configura opciones y publica',
    ],
  },
  {
    id: 3,
    title: 'Revisar tu progreso',
    description: 'Consulta tu historial y estadísticas en tu perfil.',
    icon: 'fa-solid fa-chart-line',
    color: 'green',
    steps: [
      'Accede a "mi panel" desde el menú',
      'Revisa la pestaña "Completados"',
      'Analiza tus estadísticas globales',
      'Identifica áreas de mejora',
    ],
  },
];

// ─── Lógica de filtrado ───────────────────────────────────────────────────────
const filteredFAQs = computed(() => {
  let items = allFAQs;
  if (activeCategory.value !== 'all') {
    items = items.filter(f => f.category === activeCategory.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    items = items.filter(f =>
      f.question.toLowerCase().includes(q) ||
      f.answer.some(a => a.toLowerCase().includes(q))
    );
  }
  return items;
});

const groupedFAQs = computed(() => {
  const map = {};
  for (const faq of filteredFAQs.value) {
    if (!map[faq.category]) map[faq.category] = [];
    map[faq.category].push(faq);
  }
  return Object.entries(map).map(([categoryId, items]) => ({ categoryId, items }));
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getCategoryCount = (id) => {
  if (id === 'all') return allFAQs.length;
  return allFAQs.filter(f => f.category === id).length;
};

const getCategoryIcon  = (id) => categories.find(c => c.id === id)?.icon || '';
const getCategoryLabel = (id) => categories.find(c => c.id === id)?.label || id;

// ─── Acciones ─────────────────────────────────────────────────────────────────
const setCategory = (id) => {
  activeCategory.value = id;
  openFAQ.value = null;
};

const filterFAQs = () => {
  openFAQ.value = null;
  if (searchQuery.value) activeCategory.value = 'all';
};

const clearSearch = () => {
  searchQuery.value = '';
  openFAQ.value = null;
};

const toggleFAQ = (id) => {
  openFAQ.value = openFAQ.value === id ? null : id;
};
</script>

<style scoped>
/* ── Página ──────────────────────────────────────────────────────────────── */
.help-page {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.help-hero {
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

.help-hero-inner {
  max-width: 640px;
  margin: 0 auto;
  text-align: center;
}

.help-hero-icon {
  font-size: 2.5rem;
  color: rgba(255,255,255,0.85);
  margin-bottom: 0.75rem;
}

.help-hero-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.help-hero-sub {
  font-size: 1rem;
  color: rgba(255,255,255,0.82);
  margin-bottom: 1.75rem;
  line-height: 1.65;
}

.help-search-wrap {
  position: relative;
  max-width: 480px;
  margin: 0 auto;
}

.help-search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.9rem;
  pointer-events: none;
}

.help-search-input {
  width: 100%;
  padding: 0.8rem 2.5rem 0.8rem 2.5rem;
  border: none;
  border-radius: 12px;
  font-size: 0.95rem;
  font-family: inherit;
  background: white;
  color: #2d3748;
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
  transition: box-shadow 0.2s;
}

.help-search-input:focus {
  outline: none;
  box-shadow: 0 4px 24px rgba(0,0,0,0.2);
}

.help-search-clear {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #a0aec0;
  font-size: 0.9rem;
  padding: 0;
}

.help-search-clear:hover { color: #4a5568; }

/* ── Categorías rápidas ──────────────────────────────────────────────────── */
.quick-cats {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.qcat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.25rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  flex: 1;
  min-width: 90px;
}

.qcat-card:hover {
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102,126,234,0.12);
}

.qcat-card.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.qcat-icon {
  font-size: 1.25rem;
  color: #667eea;
}

.qcat-card.active .qcat-icon {
  color: rgba(255,255,255,0.9);
}

.qcat-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #4a5568;
  white-space: nowrap;
}

.qcat-card.active .qcat-label {
  color: white;
}

/* ── Layout ──────────────────────────────────────────────────────────────── */
.help-layout {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 1.5rem;
  align-items: start;
}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.help-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  position: static;
  top: 80px;
}

.sidebar-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
}

.sidebar-eyebrow {
  font-size: 11px;
  font-weight: 600;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  margin-bottom: 0.75rem;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.sidebar-item {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  border: none;
  background: transparent;
  font-family: inherit;
  font-size: 13px;
  color: #718096;
  cursor: pointer;
  transition: all 0.15s;
  width: 100%;
  text-align: left;
}

.sidebar-item i {
  font-size: 12px;
  width: 16px;
  text-align: center;
  flex-shrink: 0;
}

.sidebar-item span {
  flex: 1;
}

.sidebar-item:hover {
  background: #f8f9fa;
  color: #2d3748;
}

.sidebar-item.active {
  background: #ebf0ff;
  color: #667eea;
  font-weight: 500;
}

.sidebar-item.active i {
  color: #667eea;
}

.sidebar-count {
  font-size: 11px;
  background: rgba(0,0,0,0.06);
  padding: 1px 6px;
  border-radius: 20px;
  flex-shrink: 0 !important;
  font-weight: 500;
}

.sidebar-item.active .sidebar-count {
  background: rgba(102,126,234,0.15);
  color: #667eea;
}

.contact-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
  text-align: center;
}

.contact-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  margin: 0 auto 0.75rem;
}

.contact-title {
  font-size: 13px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.35rem;
  line-height: 1.4;
}

.contact-sub {
  font-size: 12px;
  color: #a0aec0;
  margin-bottom: 0.9rem;
  line-height: 1.5;
}

.contact-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white !important;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s;
}

.contact-btn:hover {
  box-shadow: 0 4px 12px rgba(102,126,234,0.35);
  transform: translateY(-1px);
}

/* ── Contenido principal ─────────────────────────────────────────────────── */
.help-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Estado vacío ────────────────────────────────────────────────────────── */
.empty-help {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 3.5rem 1.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.6rem;
}

.empty-help-icon {
  font-size: 2.5rem;
  color: #cbd5e0;
  margin-bottom: 0.25rem;
}

.empty-help-title {
  font-size: 1rem;
  font-weight: 600;
  color: #4a5568;
}

.empty-help-sub {
  font-size: 0.9rem;
  color: #a0aec0;
}

.empty-help-sub strong { color: #718096; }

.empty-help-btn {
  margin-top: 0.5rem;
  padding: 8px 20px;
  border-radius: 8px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 13px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}

.empty-help-btn:hover {
  box-shadow: 0 4px 12px rgba(102,126,234,0.35);
}

/* ── Grupos FAQ ──────────────────────────────────────────────────────────── */
.faq-group {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
}

.faq-group-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.1rem 1.5rem;
  border-bottom: 1px solid #f7fafc;
}

.faq-group-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #ebf0ff;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  flex-shrink: 0;
}

.faq-group-title {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  flex: 1;
}

.faq-group-badge {
  font-size: 11px;
  background: #f0f0ff;
  color: #667eea;
  padding: 2px 8px;
  border-radius: 20px;
  font-weight: 500;
}

/* ── Items FAQ ───────────────────────────────────────────────────────────── */
.faq-list {
  display: flex;
  flex-direction: column;
}

.faq-item {
  border-bottom: 1px solid #f7fafc;
}

.faq-item:last-child {
  border-bottom: none;
}

.faq-question {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  padding: 1rem 1.5rem;
  background: transparent;
  border: none;
  font-family: inherit;
  font-size: 14px;
  font-weight: 500;
  color: #2d3748;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
}

.faq-question:hover {
  background: #f8f9fa;
}

.faq-item.open .faq-question {
  background: #f8f9fa;
  color: #667eea;
}

.faq-chevron {
  font-size: 11px;
  color: #a0aec0;
  flex-shrink: 0;
  transition: transform 0.2s;
}

.faq-item.open .faq-chevron {
  color: #667eea;
}

.faq-answer {
  padding: 0 1.5rem 1.25rem;
  border-top: 1px solid #f7fafc;
  background: #fcfcfd;
}

.faq-answer p {
  font-size: 14px;
  color: #718096;
  line-height: 1.7;
  margin-top: 0.85rem;
}

.faq-tip {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  margin-top: 0.9rem;
  padding: 0.75rem 1rem;
  background: #fffbeb;
  border-radius: 8px;
  border-left: 3px solid #f59e0b;
}

.faq-tip i {
  color: #f59e0b;
  font-size: 13px;
  margin-top: 2px;
  flex-shrink: 0;
}

.faq-tip span {
  font-size: 13px;
  color: #92400e;
  line-height: 1.55;
}

/* ── Guías rápidas ───────────────────────────────────────────────────────── */
.guides-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.guides-title {
  font-size: 11px;
  font-weight: 600;
  color: #a0aec0;
  text-transform: uppercase;
  letter-spacing: 0.07em;
}

.guides-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.guide-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.guide-badge {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.guide-badge--blue   { background: #ebf0ff; color: #4c5fd5; }
.guide-badge--purple { background: #f3e8ff; color: #7c3aed; }
.guide-badge--green  { background: #d1fae5; color: #059669; }

.guide-label {
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.2rem;
}

.guide-desc {
  font-size: 12px;
  color: #a0aec0;
  line-height: 1.5;
}

.guide-steps {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding-top: 0.25rem;
  border-top: 1px solid #f7fafc;
}

.guide-step {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
}

.guide-step-num {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 10px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 1px;
}

.guide-step-text {
  font-size: 12.5px;
  color: #718096;
  line-height: 1.55;
}

/* ── Dark mode ───────────────────────────────────────────────────────────── */
body.dark-mode .qcat-card,
body.dark-mode .sidebar-card,
body.dark-mode .contact-card,
body.dark-mode .faq-group,
body.dark-mode .empty-help,
body.dark-mode .guide-card {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .qcat-label,
body.dark-mode .sidebar-item,
body.dark-mode .faq-question,
body.dark-mode .faq-group-title,
body.dark-mode .guide-label,
body.dark-mode .contact-title,
body.dark-mode .empty-help-title {
  color: var(--tm-text) !important;
}

body.dark-mode .help-search-input {
  background: var(--tm-surface) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .help-search-input::placeholder {
  color: var(--tm-muted) !important;
}

body.dark-mode .help-search-icon {
  color: var(--tm-muted);
}

body.dark-mode .contact-sub,
body.dark-mode .empty-help-sub,
body.dark-mode .faq-answer p,
body.dark-mode .guide-desc,
body.dark-mode .guide-step-text,
body.dark-mode .sidebar-eyebrow,
body.dark-mode .faq-group-badge {
  color: var(--tm-muted) !important;
}

body.dark-mode .sidebar-item:hover,
body.dark-mode .faq-question:hover,
body.dark-mode .faq-item.open .faq-question {
  background: var(--tm-hover) !important;
}

body.dark-mode .sidebar-item.active {
  background: rgba(102, 126, 234, 0.15) !important;
  color: #a78bfa !important;
}

body.dark-mode .faq-group-icon {
  background: rgba(102, 126, 234, 0.15) !important;
}

body.dark-mode .faq-group-badge {
  background: rgba(102, 126, 234, 0.12) !important;
  color: #a78bfa !important;
}

body.dark-mode .faq-group-header,
body.dark-mode .faq-item,
body.dark-mode .guide-steps {
  border-color: var(--tm-border) !important;
}

body.dark-mode .faq-answer {
  background: var(--tm-bg) !important;
  border-top-color: var(--tm-border) !important;
}

body.dark-mode .faq-tip {
  background: rgba(245, 158, 11, 0.1) !important;
  border-left-color: #d97706 !important;
}

body.dark-mode .faq-tip span {
  color: #fcd34d !important;
}

body.dark-mode .guide-badge--blue   { background: rgba(76,95,213,0.15) !important; }
body.dark-mode .guide-badge--purple { background: rgba(124,58,237,0.15) !important; }
body.dark-mode .guide-badge--green  { background: rgba(5,150,105,0.15) !important; }

body.dark-mode .qcat-icon { color: #a78bfa; }

body.dark-mode .qcat-card.active {
  color: white !important;
}

body.dark-mode .qcat-card.active .qcat-icon,
body.dark-mode .qcat-card.active .qcat-label {
  color: white !important;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 900px) {
  .help-layout {
    grid-template-columns: 1fr;
  }

  .help-sidebar {
    position: static;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .guides-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .help-hero {
    padding: 2.5rem 1rem 2.25rem;
  }

  .help-hero-title {
    font-size: 1.6rem;
  }

  .quick-cats {
    gap: 0.5rem;
  }

  .qcat-card {
    padding: 0.75rem 0.5rem;
    min-width: 70px;
  }

  .help-sidebar {
    grid-template-columns: 1fr;
  }

  .faq-question {
    padding: 0.875rem 1rem;
  }

  .faq-answer {
    padding: 0 1rem 1rem;
  }
}
</style>