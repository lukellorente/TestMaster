<template>
  <div class="home page-wrap">

    <!-- ═══ Visitante: portada ═══ -->
    <template v-if="!authStore.isLoggedIn">
      <HomeHero :stats="stats" class="home__hero" />

      <section id="como" class="home__section how" aria-labelledby="h-how">
        <div class="how__copy">
          <h2 id="h-how" class="home__h2">Cómo funciona</h2>
          <ol class="how__steps">
            <li>
              <strong>Elige un test</strong>
              <span>Filtra por categoría y dificultad, o busca un tema concreto.</span>
            </li>
            <li>
              <strong>Respóndelo a tu ritmo</strong>
              <span>Una pregunta por pantalla, con la barra de progreso siempre a la vista y temporizador si el autor lo ha puesto.</span>
            </li>
            <li>
              <strong>Repasa tus fallos</strong>
              <span>Ves cada respuesta corregida y tu nota frente a la media del test.</span>
            </li>
          </ol>
        </div>

        <!-- Ilustración: así se ve un resultado -->
        <div class="how__demo panel" aria-hidden="true">
          <div class="demo-score">
            <div class="demo-ring" style="--pct: 75"><span class="tabular">75 %</span></div>
            <div class="demo-score__text">
              <strong>Aprobado</strong>
              <span>9 de 12 correctas · 11 min 24 s</span>
              <span class="demo-score__delta">11 puntos por encima de la media del test</span>
            </div>
          </div>
          <div class="demo-row"><span class="demo-x"><i class="fa-solid fa-xmark"></i></span><span>¿Qué hace un LEFT JOIN con las filas sin coincidencia?</span><em>Pregunta 7</em></div>
          <div class="demo-row"><span class="demo-x"><i class="fa-solid fa-xmark"></i></span><span>COUNT(*) ignora las filas con valores NULL.</span><em>Pregunta 10</em></div>
        </div>
      </section>
    </template>

    <!-- ═══ Con sesión: saludo ═══ -->
    <section v-else class="greet" aria-labelledby="h-greet">
      <div class="greet__text">
        <h1 id="h-greet" class="greet__title">Hola, {{ firstName }}</h1>
        <p v-if="lastAttempt" class="greet__line">
          Tu último intento fue un {{ Math.round(lastAttempt.score) }} % en {{ lastAttempt.test?.title }}, {{ relativeDay(lastAttempt.completed_at) }}.
          <router-link :to="`/results/${lastAttempt.id}`">Ver resultado</router-link>
        </p>
        <p v-else-if="profileLoaded" class="greet__line">Aún no has hecho ningún test. Elige uno del catálogo para empezar.</p>
      </div>
      <dl v-if="myStats" class="greet__stats" aria-label="Tu actividad">
        <div><dt>Tests hechos</dt><dd class="tabular">{{ myStats.tests_completed ?? 0 }}</dd></div>
        <div><dt>Aprobados</dt><dd class="tabular">{{ myStats.tests_passed ?? 0 }}</dd></div>
        <div>
          <dt>Nota media</dt>
          <dd class="tabular">{{ myStats.tests_completed ? `${Math.round(myStats.average_score)} %` : '—' }}</dd>
        </div>
      </dl>
    </section>

    <!-- ═══ Catálogo ═══ -->
    <section id="tests" class="catalog" :class="{ 'home__section': !authStore.isLoggedIn }" aria-labelledby="h-tests">
      <h2 v-if="!authStore.isLoggedIn" class="home__h2">Explora los tests</h2>

      <div class="catalog__filters" role="search">
        <div class="search">
          <i class="fa-solid fa-magnifying-glass search__icon" aria-hidden="true"></i>
          <label for="catalog-search" class="sr-only">Buscar tests</label>
          <input
            id="catalog-search"
            v-model="searchQuery"
            type="search"
            class="search__input"
            placeholder="Buscar por tema, título o autor"
            @input="onSearchInput"
            @keyup.enter="applySearch"
          />
          <button v-if="searchQuery" type="button" class="search__clear icon-btn" aria-label="Borrar búsqueda" @click="clearSearch">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </div>

        <div class="catalog__row">
          <div class="chips" role="group" aria-label="Categoría">
            <button type="button" class="chip" :aria-pressed="String(!filters.category)" @click="setCategory('')">Todas</button>
            <button
              v-for="cat in categories"
              :key="cat.id"
              type="button"
              class="chip"
              :aria-pressed="String(String(filters.category) === String(cat.id))"
              @click="setCategory(cat.id)"
            >{{ cat.name }}</button>
          </div>
          <div class="catalog__selects">
            <label for="f-diff" class="catalog__label">Dificultad</label>
            <select id="f-diff" v-model="filters.difficulty" @change="onFilterChange">
              <option v-for="opt in diffOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
            <label for="f-sort" class="catalog__label">Ordenar</label>
            <select id="f-sort" v-model="filters.sort" @change="onFilterChange">
              <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <FeaturedTestCard v-if="featuredTest && !hasActiveFilters" :test="featuredTest" />

      <div class="catalog__head">
        <h2 :class="authStore.isLoggedIn ? 'catalog__h' : 'sr-only'">{{ listHeading }}</h2>
        <span v-if="!loading && pagination" class="catalog__count tabular">{{ plural(pagination.total, 'test', 'tests') }}</span>
      </div>

      <!-- Cargando: esqueleto con la forma de las tarjetas -->
      <div v-if="loading" class="grid" aria-busy="true" aria-label="Cargando tests">
        <div v-for="n in 6" :key="n" class="skeleton"></div>
      </div>

      <div v-else-if="loadError" class="state panel" role="alert">
        <p class="state__title">No se han podido cargar los tests</p>
        <p class="state__text">Comprueba tu conexión e inténtalo de nuevo.</p>
        <button type="button" class="btn btn--secondary" @click="fetchTests">Reintentar</button>
      </div>

      <div v-else-if="tests.length === 0" class="state panel">
        <p class="state__title">No hay tests que coincidan</p>
        <p class="state__text">Prueba con otra categoría o con menos filtros.</p>
        <div class="state__actions">
          <button type="button" class="btn btn--secondary" @click="resetFilters">Quitar filtros</button>
          <router-link v-if="authStore.isLoggedIn" to="/create-test" class="btn">Crear test</router-link>
        </div>
      </div>

      <div v-else class="grid">
        <CatalogCard v-for="test in tests" :key="test.id" :test="test" />
      </div>

      <nav v-if="pagination && pagination.last_page > 1" class="pager" aria-label="Paginación">
        <button
          type="button"
          class="btn btn--secondary"
          :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)"
        >
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Anterior
        </button>
        <span class="pager__info tabular">Página {{ pagination.current_page }} de {{ pagination.last_page }}</span>
        <button
          type="button"
          class="btn btn--secondary"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          Siguiente<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </nav>
    </section>

    <!-- ═══ Visitante: crear tests ═══ -->
    <section v-if="!authStore.isLoggedIn" class="home__section create" aria-labelledby="h-create">
      <div class="create__copy">
        <h2 id="h-create" class="home__h2">Crea tests para tu clase o tu equipo</h2>
        <p>
          Opción múltiple, verdadero o falso y respuesta corta. Tú decides el tiempo, la nota de aprobado,
          los intentos y si las preguntas salen una a una o todas juntas.
        </p>
        <div><router-link to="/register" class="btn btn--lg">Crear mi primer test</router-link></div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import HomeHero from '../components/home/HomeHero.vue';
import FeaturedTestCard from '../components/home/FeaturedTestCard.vue';
import CatalogCard from '../components/test/CatalogCard.vue';
import { plural, relativeDay } from '../utils/tests';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

// ─── Estado ──────────────────────────────────────────────────────────────────
const tests = ref([]);
const stats = ref({});
const categories = ref([]);
const featuredTest = ref(null);
const loading = ref(true);
const loadError = ref(false);
const searchQuery = ref(route.query.search || '');
const pagination = ref(null);

const myStats = ref(null);
const lastAttempt = ref(null);
const profileLoaded = ref(false);

const filters = reactive({
  category: route.query.category || '',
  sort: route.query.sort || 'recent',
  difficulty: route.query.difficulty || '',
  page: Number(route.query.page) || 1,
});

const diffOptions = [
  { value: '', label: 'Todas' },
  { value: 'easy', label: 'Fácil' },
  { value: 'medium', label: 'Media' },
  { value: 'hard', label: 'Difícil' },
];

const sortOptions = [
  { value: 'recent', label: 'Más recientes' },
  { value: 'attempts', label: 'Más realizados' },
  { value: 'shortest', label: 'Más cortos' },
  { value: 'longest', label: 'Más largos' },
];

const firstName = computed(() => (authStore.userName || '').split(' ')[0] || 'de nuevo');

const hasActiveFilters = computed(
  () => Boolean(searchQuery.value || filters.category || filters.difficulty || filters.page > 1)
);

const listHeading = computed(() => {
  if (searchQuery.value) return `Resultados para «${searchQuery.value}»`;
  const cat = categories.value.find((c) => String(c.id) === String(filters.category));
  if (cat) return cat.name;
  return sortOptions.find((o) => o.value === filters.sort)?.label ?? 'Tests';
});

// ─── API ─────────────────────────────────────────────────────────────────────
const fetchTests = async () => {
  loading.value = true;
  loadError.value = false;
  try {
    const params = { sort: filters.sort, page: filters.page, per_page: 12 };
    if (filters.category) params.category_id = filters.category;
    if (filters.difficulty) params.difficulty = filters.difficulty;
    if (searchQuery.value) params.search = searchQuery.value;

    const { data } = await api.get('/tests', { params });

    if (data.data) {
      tests.value = data.data;
      pagination.value = { current_page: data.current_page, last_page: data.last_page, total: data.total };
    } else {
      tests.value = data;
      pagination.value = { current_page: 1, last_page: 1, total: data.length };
    }

    featuredTest.value = tests.value.find((t) => t.is_featured) || null;
  } catch (err) {
    console.error('Error cargando tests:', err);
    loadError.value = true;
  } finally {
    loading.value = false;
  }
};

const fetchMeta = async () => {
  try {
    const [statsRes, catsRes] = await Promise.all([api.get('/tests/stats'), api.get('/categories')]);
    stats.value = statsRes.data;
    categories.value = catsRes.data;
  } catch (err) {
    console.error('Error cargando metadatos:', err);
  }
};

const fetchMyProfile = async () => {
  if (!authStore.isLoggedIn || !authStore.userId) return;
  try {
    const { data } = await api.get(`/profile/${authStore.userId}`);
    myStats.value = data.stats || null;
    lastAttempt.value = data.completed_tests?.[0] || null;
  } catch (err) {
    console.error('Error cargando el perfil:', err);
  } finally {
    profileLoaded.value = true;
  }
};

// ─── Filtros ─────────────────────────────────────────────────────────────────
const syncQueryParams = () => {
  const query = {};
  if (searchQuery.value) query.search = searchQuery.value;
  if (filters.category) query.category = filters.category;
  if (filters.difficulty) query.difficulty = filters.difficulty;
  if (filters.sort !== 'recent') query.sort = filters.sort;
  if (filters.page > 1) query.page = filters.page;
  router.replace({ query });
};

const refresh = () => {
  syncQueryParams();
  fetchTests();
};

const applySearch = () => {
  clearTimeout(searchTimer);
  filters.page = 1;
  refresh();
};

// Busca mientras se escribe, sin lanzar una petición por tecla
let searchTimer = null;
const onSearchInput = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(applySearch, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  applySearch();
};

const setCategory = (id) => {
  filters.category = id;
  filters.page = 1;
  refresh();
};

const onFilterChange = () => {
  filters.page = 1;
  refresh();
};

const changePage = (page) => {
  filters.page = page;
  refresh();
  document.getElementById('tests')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const resetFilters = () => {
  searchQuery.value = '';
  filters.category = '';
  filters.difficulty = '';
  filters.sort = 'recent';
  filters.page = 1;
  refresh();
};

watch(
  () => route.query.search,
  (val) => {
    if ((val || '') !== searchQuery.value) {
      searchQuery.value = val || '';
      filters.page = 1;
      fetchTests();
    }
  }
);

watch(() => authStore.isLoggedIn, (logged) => {
  if (logged) fetchMyProfile();
});

onMounted(() => {
  fetchMeta();
  fetchTests();
  fetchMyProfile();
});
</script>

<style scoped>
.home {
  padding-top: 56px;
}

.home__hero {
  padding-top: 32px;
}

.home__section {
  margin-top: 160px;
}

.home__h2 {
  font-size: 2rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  line-height: 1.15;
  margin: 0;
}

/* ── Cómo funciona ── */
.how {
  display: grid;
  grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
  gap: 64px;
  align-items: center;
}

.how__copy {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.how__steps {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.how__steps li {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.how__steps strong {
  font-size: var(--fs-lead);
  font-weight: 600;
}

.how__steps span {
  color: var(--text-body);
}

.how__demo {
  padding: 28px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.demo-score {
  display: flex;
  align-items: center;
  gap: 24px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.demo-ring {
  width: 112px;
  height: 112px;
  border-radius: 50%;
  background: conic-gradient(var(--accent) calc(var(--pct) * 1%), var(--border) 0);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}

.demo-ring span {
  width: 92px;
  height: 92px;
  border-radius: 50%;
  background: var(--raised);
  display: grid;
  place-items: center;
  font-size: 1.6rem;
  font-weight: 600;
}

.demo-score__text {
  display: flex;
  flex-direction: column;
  gap: 4px;
  color: var(--text-body);
}

.demo-score__text strong {
  font-size: 1.25rem;
  color: var(--text);
}

.demo-score__delta {
  color: var(--positive);
  font-size: var(--fs-small);
  font-weight: 500;
}

.demo-row {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--bg);
  border-radius: var(--radius-card);
  padding: 12px 14px;
  font-size: var(--fs-small);
}

.demo-row span:nth-child(2) {
  flex: 1;
}

.demo-row em {
  font-style: normal;
  font-size: var(--fs-meta);
  color: var(--muted);
}

.demo-x {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--negative-bg);
  color: var(--negative-ink);
  display: inline-grid;
  place-items: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}

/* ── Saludo ── */
.greet {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 48px;
  align-items: end;
  margin-bottom: 40px;
}

.greet__text {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.greet__title {
  font-size: 1.875rem;
  margin: 0;
}

.greet__line {
  color: var(--text-body);
  margin: 0;
}

.greet__line a {
  font-weight: 500;
}

.greet__stats {
  display: flex;
  margin: 0;
}

.greet__stats > div {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 0 24px;
  border-left: 1px solid var(--border);
}

.greet__stats > div:first-child {
  border-left: 0;
  padding-left: 0;
}

.greet__stats dt {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.greet__stats dd {
  margin: 0;
  font-size: 1.375rem;
}

/* ── Catálogo ── */
.catalog {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.catalog__filters {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.search {
  position: relative;
}

.search__icon {
  position: absolute;
  left: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
}

.search__input {
  width: 100%;
  height: 48px;
  border: 1px solid var(--control);
  border-radius: var(--radius-card);
  padding: 0 48px 0 44px;
  font: inherit;
  font-size: var(--fs-body);
  color: var(--text);
  background: var(--bg);
  transition: border-color 0.15s, box-shadow 0.15s;
}

.search__input::placeholder {
  color: var(--muted);
}

.search__input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-soft);
}

.search__input::-webkit-search-cancel-button {
  display: none;
}

.search__clear {
  position: absolute;
  right: 4px;
  top: 4px;
}

.catalog__row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.chips {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.catalog__selects {
  display: flex;
  gap: 8px;
  align-items: center;
}

.catalog__selects select {
  height: 36px;
}

.catalog__label {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.catalog__label + select + .catalog__label {
  margin-left: 8px;
}

.catalog__head {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  gap: 16px;
  margin-bottom: -12px;
}

.catalog__h {
  font-size: var(--fs-section);
  font-weight: 600;
  margin: 0;
}

.catalog__count {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.skeleton {
  height: 196px;
  border-radius: var(--radius-panel);
  background: var(--raised);
  animation: pulse 1.4s ease-in-out infinite;
}

@keyframes pulse {
  50% { opacity: 0.55; }
}

.state {
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  text-align: center;
}

.state__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.state__text {
  color: var(--text-body);
  margin: 0 0 8px;
}

.state__actions {
  display: flex;
  gap: 8px;
}

.pager {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
}

.pager__info {
  font-size: var(--fs-small);
  color: var(--muted);
}

/* ── Crear tests ── */
.create {
  background: var(--raised);
  border-radius: 12px;
  padding: 64px;
}

.create__copy {
  display: flex;
  flex-direction: column;
  gap: 20px;
  max-width: 560px;
}

.create__copy p {
  font-size: var(--fs-lead);
  color: var(--text-body);
  margin: 0;
}

/* ── Responsive ── */
@media (max-width: 960px) {
  .how,
  .greet {
    grid-template-columns: minmax(0, 1fr);
    gap: 32px;
  }

  .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .home {
    padding-top: 32px;
  }

  .home__section {
    margin-top: 96px;
  }

  .home__h2 {
    font-size: 1.625rem;
  }

  .grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .chips {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 4px;
    width: 100%;
  }

  .catalog__selects {
    width: 100%;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 8px 12px;
  }

  .catalog__label + select + .catalog__label {
    margin-left: 0;
  }

  .greet__stats > div {
    padding: 0 16px;
  }

  .create {
    padding: 32px 24px;
  }
}
</style>
