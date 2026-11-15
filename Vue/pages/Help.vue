<template>
  <div class="page-wrap hp">
    <header class="hp__head">
      <h1 class="hp__title">Centro de ayuda</h1>
      <p class="hp__intro">Respuestas rápidas sobre tu cuenta, los tests y los resultados.</p>
      <div class="hp__search" role="search">
        <i class="fa-solid fa-magnifying-glass hp__search-icon" aria-hidden="true"></i>
        <label for="help-search" class="sr-only">Buscar en la ayuda</label>
        <input
          id="help-search"
          v-model="searchQuery"
          type="search"
          class="hp__search-input"
          placeholder="Por ejemplo: tiempo límite, foto de perfil…"
          @input="onSearch"
        />
        <button v-if="searchQuery" type="button" class="icon-btn hp__search-clear" aria-label="Borrar búsqueda" @click="clearSearch">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
    </header>

    <div class="hp__layout">
      <div class="hp__main">
        <div class="chips hp__chips" role="group" aria-label="Filtrar por tema">
          <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            class="chip"
            :aria-pressed="String(activeCategory === cat.id)"
            @click="setCategory(cat.id)"
          >{{ cat.label }} <span class="hp__count tabular">{{ categoryCount(cat.id) }}</span></button>
        </div>

        <p class="sr-only" aria-live="polite">{{ resultsAnnouncement }}</p>

        <div v-if="!filteredFAQs.length" class="hp__empty panel">
          <p class="hp__empty-title">No hay respuestas para «{{ searchQuery }}»</p>
          <p class="hp__empty-text">Prueba con otras palabras o escríbenos y te ayudamos.</p>
          <div class="hp__empty-actions">
            <button type="button" class="btn btn--secondary" @click="clearSearch">Borrar búsqueda</button>
            <router-link to="/contact" class="btn">Contactar</router-link>
          </div>
        </div>

        <section v-for="group in groupedFAQs" :key="group.id" class="hp__group" :aria-labelledby="`hg-${group.id}`">
          <h2 :id="`hg-${group.id}`" class="hp__h2">{{ group.label }}</h2>
          <div class="hp__faqs">
            <div v-for="faq in group.items" :key="faq.id" class="hp__faq" :class="{ 'is-open': openFAQ === faq.id }">
              <h3 class="hp__q">
                <button
                  :id="`fq-${faq.id}`"
                  type="button"
                  class="hp__q-btn"
                  :aria-expanded="String(openFAQ === faq.id)"
                  :aria-controls="`fa-${faq.id}`"
                  @click="toggleFAQ(faq.id)"
                >
                  <span>{{ faq.question }}</span>
                  <i class="fa-solid fa-chevron-down hp__chev" aria-hidden="true"></i>
                </button>
              </h3>
              <div v-show="openFAQ === faq.id" :id="`fa-${faq.id}`" class="hp__a" role="region" :aria-labelledby="`fq-${faq.id}`">
                <p v-for="(para, i) in faq.answer" :key="i">{{ para }}</p>
                <ul v-if="faq.list" class="hp__a-list">
                  <li v-for="item in faq.list" :key="item">{{ item }}</li>
                </ul>
                <p v-if="faq.tip" class="hp__tip">{{ faq.tip }}</p>
                <router-link v-if="faq.link" :to="faq.link.to" class="hp__a-link">{{ faq.link.label }}</router-link>
              </div>
            </div>
          </div>
        </section>
      </div>

      <aside class="hp__side">
        <section class="panel hp__contact" aria-labelledby="h-contact">
          <h2 id="h-contact" class="hp__h3">¿No encuentras la respuesta?</h2>
          <p>Escríbenos: respondemos en menos de 24 horas laborables.</p>
          <router-link to="/contact" class="btn">Contactar con soporte</router-link>
        </section>
      </aside>
    </div>

    <section class="hp__guides" aria-labelledby="h-guides">
      <h2 id="h-guides" class="hp__h2">Primeros pasos</h2>
      <div class="hp__guides-grid">
        <div v-for="guide in guides" :key="guide.id" class="hp__guide">
          <h3 class="hp__h3">{{ guide.title }}</h3>
          <p class="hp__guide-desc">{{ guide.description }}</p>
          <ol class="hp__steps">
            <li v-for="step in guide.steps" :key="step">{{ step }}</li>
          </ol>
          <router-link :to="guide.cta.to" class="btn btn--secondary hp__guide-cta">{{ guide.cta.label }}</router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { plural } from '../utils/tests';

const searchQuery = ref('');
const activeCategory = ref('all');
const openFAQ = ref(null);

const categories = [
  { id: 'all', label: 'Todo' },
  { id: 'account', label: 'Mi cuenta' },
  { id: 'tests', label: 'Crear tests' },
  { id: 'taking', label: 'Hacer tests' },
  { id: 'results', label: 'Resultados' },
  { id: 'security', label: 'Privacidad y seguridad' },
];

const allFAQs = [
  // ── Cuenta ──
  {
    id: 1,
    category: 'account',
    question: '¿Cómo me registro?',
    answer: [
      'Pulsa «Crear cuenta» en la parte superior derecha. Solo necesitas un nombre, un correo electrónico y una contraseña de al menos 8 caracteres.',
      'Registrarte es gratis y te permite hacer tests, guardar tus notas y crear tus propios tests.',
    ],
    link: { to: '/register', label: 'Crear una cuenta' },
  },
  {
    id: 2,
    category: 'account',
    question: '¿Cómo cambio mi foto de perfil?',
    answer: [
      'Entra en «Mi panel» y pulsa «Cambiar foto» junto a tu nombre. Se admiten imágenes JPG, PNG, GIF y WebP de hasta 2 MB.',
      'Si quieres volver a la imagen por defecto, pulsa «Quitar foto».',
    ],
  },
  {
    id: 3,
    category: 'account',
    question: '¿Cómo cambio mi contraseña?',
    answer: [
      'Por ahora no se puede cambiar la contraseña desde la web. Escríbenos desde la página de contacto con el correo de tu cuenta y te ayudamos.',
    ],
    link: { to: '/contact', label: 'Ir a contacto' },
  },
  {
    id: 4,
    category: 'account',
    question: '¿Puedo tener varias cuentas?',
    answer: [
      'Cada persona debe tener una única cuenta. Usar varias cuentas para manipular estadísticas está prohibido por los términos de uso.',
    ],
  },
  {
    id: 5,
    category: 'account',
    question: '¿Cómo activo el modo oscuro?',
    answer: [
      'TestMaster usa el mismo tema que tu sistema la primera vez que entras. Puedes cambiarlo con el botón de luna o sol de la barra superior; tu elección se recuerda en ese navegador.',
    ],
  },

  // ── Crear tests ──
  {
    id: 10,
    category: 'tests',
    question: '¿Cómo creo un test?',
    answer: [
      'Inicia sesión y pulsa «Crear test» en la barra superior. Escribe el título, elige la categoría y la dificultad, y añade tus preguntas.',
      'Cuando termines, guárdalo como borrador para seguir más tarde o publícalo directamente.',
    ],
    tip: 'Puedes subir, bajar, duplicar y eliminar preguntas con los botones de cada una.',
    link: { to: '/create-test', label: 'Crear un test' },
  },
  {
    id: 11,
    category: 'tests',
    question: '¿Qué tipos de preguntas puedo usar?',
    answer: ['Hay tres tipos:'],
    list: [
      'Opción múltiple: entre 2 y 8 respuestas, con una correcta.',
      'Verdadero o falso.',
      'Respuesta corta: se escribe el texto y se compara sin distinguir mayúsculas de minúsculas.',
    ],
  },
  {
    id: 12,
    category: 'tests',
    question: '¿Cómo pongo un tiempo límite o limito los intentos?',
    answer: [
      'En el panel «Ajustes» al crear o editar el test, desmarca «Sin límite» y escribe los minutos. Para los intentos, desmarca «Ilimitados» y escribe cuántos tiene cada persona.',
    ],
  },
  {
    id: 13,
    category: 'tests',
    question: '¿Puedo elegir cómo se muestran las preguntas?',
    answer: [
      'Sí. En «Ajustes», «Presentación de las preguntas» permite elegir entre una pregunta por pantalla (lo recomendado, sobre todo en móvil) o todas en una sola página.',
    ],
  },
  {
    id: 14,
    category: 'tests',
    question: '¿Qué diferencia hay entre borrador y publicado?',
    answer: [
      'Un test publicado aparece en el catálogo y cualquiera puede hacerlo. Un borrador solo lo ves tú.',
      'Puedes pasar un test de uno a otro editándolo y pulsando «Guardar borrador» o «Guardar y publicar».',
    ],
  },
  {
    id: 15,
    category: 'tests',
    question: '¿Puedo editar o eliminar un test publicado?',
    answer: [
      'Sí. En la página del test, si eres quien lo creó, verás «Editar test» y «Eliminar test» en el panel lateral.',
      'Eliminar es definitivo: también se borran todos los intentos y resultados del test. Si solo quieres ocultarlo, guárdalo como borrador.',
    ],
  },

  // ── Hacer tests ──
  {
    id: 20,
    category: 'taking',
    question: '¿Cómo funciona un test?',
    answer: [
      'Responde a tu ritmo y muévete entre preguntas con «Anterior» y «Siguiente», con las flechas del teclado o con los números que aparecen bajo cada pregunta. La barra superior muestra cuántas llevas respondidas y el tiempo que queda.',
      'Antes de enviar verás un resumen con las preguntas que te quedan sin responder.',
    ],
  },
  {
    id: 21,
    category: 'taking',
    question: '¿Qué pasa si se acaba el tiempo?',
    answer: [
      'Te avisamos cuando quedan 2 minutos. Al llegar a cero, tus respuestas se envían automáticamente.',
    ],
  },
  {
    id: 22,
    category: 'taking',
    question: '¿Qué pasa si salgo a mitad de un test?',
    answer: [
      'Te pediremos confirmación. Si sales, tus respuestas se pierden y no cuenta como intento.',
    ],
  },

  // ── Resultados ──
  {
    id: 30,
    category: 'results',
    question: '¿Dónde veo mis resultados?',
    answer: [
      'Al enviar un test verás tu nota, una comparación con la media del test y el detalle de cada respuesta.',
      'Tu historial completo está en «Mi panel», en la pestaña «Completados».',
    ],
  },
  {
    id: 31,
    category: 'results',
    question: '¿Cómo se calcula la nota?',
    answer: [
      'Es el porcentaje de preguntas acertadas. Si el autor ha dado más puntos a algunas preguntas, se usa una media ponderada.',
      'Cada test tiene su propia nota para aprobar, que verás en la página del test antes de empezar.',
    ],
    tip: 'Las preguntas sin responder cuentan como fallos.',
  },
  {
    id: 32,
    category: 'results',
    question: '¿Puedo repetir un test?',
    answer: [
      'Sí, si el autor lo permite. En la página del test y en tus resultados verás cuántos intentos te quedan.',
    ],
  },
  {
    id: 33,
    category: 'results',
    question: '¿Por qué no veo las respuestas correctas?',
    answer: [
      'Mostrar las respuestas correctas y las explicaciones es una decisión del autor del test. Si lo ha desactivado, solo verás si acertaste o fallaste cada pregunta.',
    ],
  },

  // ── Seguridad ──
  {
    id: 40,
    category: 'security',
    question: '¿Cómo protege TestMaster mis datos?',
    answer: [
      'Las comunicaciones van cifradas por HTTPS y las contraseñas se guardan con un hash irreversible; nunca en texto plano.',
    ],
    link: { to: '/privacy', label: 'Leer la política de privacidad' },
  },
  {
    id: 41,
    category: 'security',
    question: '¿Cómo cierro sesión?',
    answer: [
      'Pulsa el botón de salida de la barra superior (en móvil, abre el menú y elige «Cerrar sesión»). Hazlo siempre que uses un dispositivo compartido.',
    ],
  },
];

const guides = [
  {
    id: 1,
    title: 'Hacer tu primer test',
    description: 'Elige un tema y pon a prueba lo que sabes.',
    steps: ['Busca un test o filtra por categoría', 'Revisa la duración y la nota para aprobar', 'Pulsa «Empezar test» y responde', 'Repasa tus fallos en los resultados'],
    cta: { to: '/', label: 'Explorar tests' },
  },
  {
    id: 2,
    title: 'Crear un test',
    description: 'Comparte lo que sabes con tu clase o tu equipo.',
    steps: ['Pulsa «Crear test»', 'Escribe el título y elige la categoría', 'Añade las preguntas y marca las respuestas correctas', 'Ajusta tiempo e intentos, y publica'],
    cta: { to: '/create-test', label: 'Crear un test' },
  },
  {
    id: 3,
    title: 'Seguir tu progreso',
    description: 'Mira cómo evolucionan tus notas.',
    steps: ['Entra en «Mi panel»', 'Abre la pestaña «Completados»', 'Pulsa un intento para ver el detalle', 'Repite los tests que quieras mejorar'],
    cta: { to: '/', label: 'Ir al catálogo' },
  },
];

// ─── Filtrado ────────────────────────────────────────────────────────────────
const normalize = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

const filteredFAQs = computed(() => {
  let items = allFAQs;
  if (activeCategory.value !== 'all') items = items.filter((f) => f.category === activeCategory.value);
  const q = normalize(searchQuery.value.trim());
  if (q) {
    items = items.filter((f) =>
      [f.question, ...f.answer, ...(f.list || []), f.tip || ''].some((t) => normalize(t).includes(q))
    );
  }
  return items;
});

const groupedFAQs = computed(() =>
  categories
    .filter((c) => c.id !== 'all')
    .map((c) => ({ ...c, items: filteredFAQs.value.filter((f) => f.category === c.id) }))
    .filter((g) => g.items.length)
);

const categoryCount = (id) => (id === 'all' ? allFAQs.length : allFAQs.filter((f) => f.category === id).length);

const resultsAnnouncement = computed(() =>
  searchQuery.value ? plural(filteredFAQs.value.length, 'respuesta encontrada', 'respuestas encontradas') : ''
);

// ─── Acciones ────────────────────────────────────────────────────────────────
const setCategory = (id) => {
  activeCategory.value = id;
  openFAQ.value = null;
};

const onSearch = () => {
  openFAQ.value = null;
  if (searchQuery.value) activeCategory.value = 'all';
  // Con un único resultado, se abre directamente
  if (filteredFAQs.value.length === 1) openFAQ.value = filteredFAQs.value[0].id;
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
.hp {
  padding-top: 48px;
}

.hp__head {
  max-width: 720px;
  margin-bottom: 40px;
}

.hp__title {
  font-size: 2.25rem;
  letter-spacing: -0.025em;
  margin: 0 0 8px;
}

.hp__intro {
  font-size: var(--fs-lead);
  color: var(--text-body);
  margin: 0 0 24px;
}

.hp__search {
  position: relative;
}

.hp__search-icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
}

.hp__search-input {
  width: 100%;
  height: 52px;
  border: 1px solid var(--control);
  border-radius: var(--radius-card);
  padding: 0 52px 0 44px;
  font: inherit;
  font-size: 1rem;
  color: var(--text);
  background: var(--bg);
}

.hp__search-input::placeholder {
  color: var(--muted);
}

.hp__search-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}

.hp__search-input::-webkit-search-cancel-button {
  display: none;
}

.hp__search-clear {
  position: absolute;
  right: 6px;
  top: 6px;
}

.hp__layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 300px;
  gap: 48px;
  align-items: start;
}

.hp__main {
  display: flex;
  flex-direction: column;
  gap: 32px;
  min-width: 0;
}

.hp__chips {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.hp__count {
  color: inherit;
  opacity: 0.65;
  font-size: 0.8em;
  margin-left: 2px;
}

.hp__h2 {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0 0 8px;
}

.hp__h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.hp__faq {
  border-top: 1px solid var(--rule);
}

.hp__faq:last-child {
  border-bottom: 1px solid var(--rule);
}

.hp__q {
  margin: 0;
  font-size: 1rem;
  font-weight: 500;
}

.hp__q-btn {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border: 0;
  background: none;
  color: var(--text);
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.hp__q-btn:hover {
  color: var(--accent);
}

.hp__chev {
  color: var(--muted);
  font-size: 0.8rem;
  transition: transform 0.2s;
  flex-shrink: 0;
}

.is-open .hp__chev {
  transform: rotate(180deg);
}

.hp__a {
  padding: 0 32px 18px 0;
  color: var(--text-body);
  line-height: 1.65;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.hp__a p {
  margin: 0;
}

.hp__a-list {
  margin: 0;
  padding-left: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.hp__tip {
  font-size: var(--fs-small);
  background: var(--info-bg);
  color: var(--info-ink);
  border-radius: var(--radius-card);
  padding: 10px 14px;
}

.hp__a-link {
  font-weight: 500;
  font-size: var(--fs-small);
  align-self: flex-start;
}

.hp__empty {
  padding: 40px 24px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.hp__empty-title {
  font-weight: 600;
  margin: 0;
}

.hp__empty-text {
  color: var(--text-body);
  margin: 0 0 8px;
}

.hp__empty-actions {
  display: flex;
  gap: 8px;
}

.hp__side {
  position: sticky;
  top: calc(var(--header-h) + 24px);
}

.hp__contact {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.hp__contact p {
  color: var(--text-body);
  font-size: var(--fs-small);
  margin: 0 0 4px;
}

.hp__guides {
  margin-top: 96px;
}

.hp__guides .hp__h2 {
  margin-bottom: 16px;
}

.hp__guides-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.hp__guide {
  background: var(--raised);
  border-radius: var(--radius-panel);
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.hp__guide-desc {
  color: var(--text-body);
  font-size: var(--fs-small);
  margin: 0;
}

.hp__steps {
  margin: 8px 0 12px;
  padding-left: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: var(--fs-small);
  color: var(--text-body);
  flex: 1;
}

.hp__steps li::marker {
  color: var(--muted);
  font-variant-numeric: tabular-nums;
}

.hp__guide-cta {
  align-self: flex-start;
}

@media (max-width: 960px) {
  .hp__layout {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .hp__side {
    position: static;
  }

  .hp__guides-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 640px) {
  .hp {
    padding-top: 32px;
  }

  .hp__title {
    font-size: 1.75rem;
  }

  .hp__chips {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 4px;
  }

  .hp__guides {
    margin-top: 64px;
  }
}
</style>
