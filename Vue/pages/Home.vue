<template>
  <div class="home">

    <!-- ═══════════════════════════════════════════════════
         HERO
    ═══════════════════════════════════════════════════ -->
    <HomeHero :is-logged-in="authStore.isLoggedIn" :stats="stats" />

    <!-- ═══════════════════════════════════════════════════
         TEST DESTACADO (is_featured)
    ═══════════════════════════════════════════════════ -->
    <FeaturedTestCard v-if="featuredTest" :test="featuredTest" />

    <!-- ═══════════════════════════════════════════════════
         BARRA DE FILTROS
    ═══════════════════════════════════════════════════ -->
    <section class="filter-section">
      <div class="filter-bar">
        <!-- Búsqueda -->
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Buscar tests..."
            @keyup.enter="applySearch"
          />
          <button v-if="searchQuery" class="search-clear" @click="clearSearch">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Categoría -->
        <select v-model="filters.category" class="filter-select" @change="fetchTests">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>

        <!-- Dificultad (pills) -->
        <div class="pill-group">
          <button
            v-for="opt in diffOptions"
            :key="opt.value"
            :class="['pill', { active: filters.difficulty === opt.value }]"
            @click="setDifficulty(opt.value)"
          >{{ opt.label }}</button>
        </div>
      </div>

      <!-- Ordenación -->
      <div class="sort-row">
        <span class="sort-label">Ordenar por</span>
        <button
          v-for="opt in sortOptions"
          :key="opt.value"
          :class="['sort-btn', { active: filters.sort === opt.value }]"
          @click="setSort(opt.value)"
        >{{ opt.label }}</button>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════
         GRID DE TESTS
    ═══════════════════════════════════════════════════ -->
    <section class="tests-section">

      <!-- Loading -->
      <div v-if="loading" class="state-box">
        <i class="fa-solid fa-spinner fa-spin"></i>
        <p>Cargando tests...</p>
      </div>

      <!-- Empty -->
      <div v-else-if="tests.length === 0" class="state-box">
        <i class="fa-solid fa-box-open"></i>
        <p>No hay tests que coincidan con tu búsqueda.</p>
        <button class="btn btn-ghost" @click="resetFilters">Limpiar filtros</button>
      </div>

      <!-- Grid -->
      <div v-else class="cards-grid">
        <router-link
          v-for="test in tests"
          :key="test.id"
          :to="`/test/${test.id}`"
          class="test-card"
        >
          <!-- Cabecera de la card -->
          <div class="card-header">
            <div class="card-cats">
              <span
                v-for="cat in test.categories"
                :key="cat.id"
                class="cat-badge"
              >{{ cat.name }}</span>
            </div>
            <div class="card-badges">
              <span v-if="test.completed_attempts_count > 10" class="pop-badge">
                popular
              </span>
              <span :class="['diff-badge', `diff-${test.difficulty}`]">
                {{ diffLabel(test.difficulty) }}
              </span>
            </div>
          </div>

          <!-- Título -->
          <h3 class="card-title">{{ test.title }}</h3>

          <!-- Descripción -->
          <p v-if="test.description" class="card-desc">
            {{ truncate(test.description, 90) }}
          </p>

          <!-- Stats de la card -->
          <div class="card-footer">
            <div class="card-stats">
              <span class="cstat">
                <i class="fa-solid fa-circle-question"></i>
                {{ test.questions_count }} preg.
              </span>
              <span v-if="test.time_limit" class="cstat">
                <i class="fa-regular fa-clock"></i>
                {{ Math.round(test.time_limit / 60) }} min
              </span>
              <span class="cstat">
                <i class="fa-regular fa-eye"></i>
                {{ test.completed_attempts_count }}
              </span>
            </div>
            <div v-if="test.user" class="card-author">
              <UserAvatar :user="test.user" size="small" />
              <span class="author-name">{{ test.user.name }}</span>
            </div>
          </div>
        </router-link>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="pagination">
        <button
          class="page-btn"
          :disabled="pagination.current_page === 1"
          @click="changePage(pagination.current_page - 1)"
        >
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span class="page-info">
          {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          class="page-btn"
          :disabled="pagination.current_page === pagination.last_page"
          @click="changePage(pagination.current_page + 1)"
        >
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';
import HomeHero from '../components/home/HomeHero.vue';
import FeaturedTestCard from '../components/home/FeaturedTestCard.vue';
import UserAvatar from '../components/user/UserAvatar.vue';

const authStore = useAuthStore();
const route    = useRoute();
const router   = useRouter();

// ─── Estado ──────────────────────────────────────────────────────────────────
const tests        = ref([]);
const stats        = ref({});
const categories   = ref([]);
const featuredTest = ref(null);
const loading      = ref(true);
const searchQuery  = ref(route.query.search || '');
const pagination   = ref(null);

const filters = reactive({
  category:   route.query.category   || '',
  sort:       route.query.sort       || 'recent',
  difficulty: route.query.difficulty || '',
  page:       Number(route.query.page) || 1,
});

// ─── Opciones estáticas ───────────────────────────────────────────────────────
const diffOptions = [
  { value: '',     label: 'Todos' },
  { value: 'easy',   label: 'Fácil' },
  { value: 'medium', label: 'Medio' },
  { value: 'hard',   label: 'Difícil' },
];

const sortOptions = [
  { value: 'recent',   label: 'Recientes' },
  { value: 'views',    label: 'Más vistos' },
  { value: 'attempts', label: 'Más realizados' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const diffLabel = (d) => ({ easy: 'Fácil', medium: 'Medio', hard: 'Difícil' }[d] || d);
const truncate  = (str, n) => str && str.length > n ? str.slice(0, n).trimEnd() + '…' : str;

// ─── Peticiones API ───────────────────────────────────────────────────────────
const fetchTests = async () => {
  loading.value = true;
  try {
    const params = {
      sort:    filters.sort,
      page:    filters.page,
      per_page: 12,
    };
    if (filters.category)   params.category_id = filters.category;
    if (filters.difficulty)  params.difficulty  = filters.difficulty;
    if (searchQuery.value)   params.search      = searchQuery.value;

    const { data } = await api.get('/tests', { params });

    // La API devuelve paginación Laravel (data.data) o array directo
    if (data.data) {
      tests.value = data.data;
      pagination.value = {
        current_page: data.current_page,
        last_page:    data.last_page,
        total:        data.total,
      };
    } else {
      tests.value    = data;
      pagination.value = null;
    }

    // Destacado: primer test con is_featured = true
    featuredTest.value = tests.value.find(t => t.is_featured) || null;
  } catch (err) {
    console.error('Error cargando tests:', err);
  } finally {
    loading.value = false;
  }
};

const fetchMeta = async () => {
  try {
    const [statsRes, catsRes] = await Promise.all([
      api.get('/tests/stats'),
      api.get('/categories'),
    ]);
    stats.value      = statsRes.data;
    categories.value = catsRes.data;
  } catch (err) {
    console.error('Error cargando metadatos:', err);
  }
};

// ─── Acciones de filtrado ─────────────────────────────────────────────────────
const applySearch = () => {
  filters.page = 1;
  syncQueryParams();
  fetchTests();
};

const clearSearch = () => {
  searchQuery.value = '';
  applySearch();
};

const setDifficulty = (val) => {
  filters.difficulty = val;
  filters.page = 1;
  syncQueryParams();
  fetchTests();
};

const setSort = (val) => {
  filters.sort = val;
  filters.page = 1;
  syncQueryParams();
  fetchTests();
};

const changePage = (page) => {
  filters.page = page;
  syncQueryParams();
  fetchTests();
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const resetFilters = () => {
  searchQuery.value  = '';
  filters.category   = '';
  filters.difficulty = '';
  filters.sort       = 'recent';
  filters.page       = 1;
  syncQueryParams();
  fetchTests();
};

// Mantiene la URL sincronizada con los filtros activos
const syncQueryParams = () => {
  const query = {};
  if (searchQuery.value)   query.search     = searchQuery.value;
  if (filters.category)    query.category   = filters.category;
  if (filters.difficulty)  query.difficulty = filters.difficulty;
  if (filters.sort !== 'recent') query.sort = filters.sort;
  if (filters.page > 1)    query.page       = filters.page;
  router.replace({ query });
};

// ─── Watchers ─────────────────────────────────────────────────────────────────
// Reactiva la búsqueda cuando el header empuja un ?search= nuevo
watch(
  () => route.query.search,
  (val) => {
    if (val !== searchQuery.value) {
      searchQuery.value = val || '';
      filters.page = 1;
      fetchTests();
    }
  }
);

// ─── Init ─────────────────────────────────────────────────────────────────────
onMounted(() => {
  fetchMeta();
  fetchTests();
});
</script>

<style scoped>
/* ── Página ──────────────────────────────────────────────────────────────── */
.home {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Badges ───────────────────────────────────────────────────────────────── */
.cat-badge {
  font-size: 0.72rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
  background: #ebf0ff;
  color: #4c5fd5;
  white-space: nowrap;
}

.diff-badge {
  font-size: 0.72rem;
  font-weight: 500;
  padding: 3px 10px;
  border-radius: 20px;
  white-space: nowrap;
}

.diff-easy   { background: #e8f5e9; color: #2e7d32; }
.diff-medium { background: #fff8e1; color: #e65100; }
.diff-hard   { background: #fce4ec; color: #c62828; }

.pop-badge {
  font-size: 0.68rem;
  font-weight: 500;
  padding: 3px 9px;
  border-radius: 20px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  white-space: nowrap;
}

/* ── Filtros ──────────────────────────────────────────────────────────────── */
.filter-section {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.filter-bar {
  display: flex;
  gap: 0.75rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.search-icon {
  position: absolute;
  left: 0.85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a0aec0;
  font-size: 0.85rem;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 0.55rem 2.25rem 0.55rem 2.25rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  font-family: inherit;
  background: white;
  color: #2d3748;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-clear {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #a0aec0;
  font-size: 0.85rem;
  padding: 0;
  line-height: 1;
}

.search-clear:hover { color: #4a5568; }

.filter-select {
  padding: 0.55rem 2rem 0.55rem 0.85rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  font-family: inherit;
  background: white;
  color: #2d3748;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg width='10' height='6' viewBox='0 0 10 6' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L5 5L9 1' stroke='%23a0aec0' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  cursor: pointer;
  transition: border-color 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
}

.pill-group {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.pill {
  padding: 5px 14px;
  border-radius: 20px;
  border: 1px solid #e2e8f0;
  font-size: 0.82rem;
  font-family: inherit;
  cursor: pointer;
  background: white;
  color: #718096;
  transition: all 0.15s;
}

.pill:hover { border-color: #cbd5e0; background: #f8f9fa; }

.pill.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.sort-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.sort-label {
  font-size: 0.82rem;
  color: #a0aec0;
}

.sort-btn {
  padding: 4px 12px;
  border-radius: 6px;
  border: none;
  background: none;
  font-size: 0.82rem;
  font-family: inherit;
  color: #718096;
  cursor: pointer;
  transition: all 0.15s;
}

.sort-btn:hover { background: #f8f9fa; color: #2d3748; }

.sort-btn.active {
  background: #f0f0ff;
  color: #667eea;
  font-weight: 500;
}

/* ── Estado vacío / loading ───────────────────────────────────────────────── */
.state-box {
  text-align: center;
  padding: 3.5rem 1.5rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  color: #a0aec0;
}

.state-box i {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  display: block;
}

.state-box p {
  margin-bottom: 1.25rem;
  font-size: 0.95rem;
}

/* ── Grid de cards ────────────────────────────────────────────────────────── */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
}

.test-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 1.25rem;
  text-decoration: none;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  transition: all 0.2s;
}

.test-card:hover {
  border-color: #c3cfe2;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
  transform: translateY(-2px);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.5rem;
}

.card-cats {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
  flex: 1;
}

.card-badges {
  display: flex;
  gap: 5px;
  flex-shrink: 0;
}

.card-title {
  font-size: 0.98rem;
  font-weight: 600;
  color: #1a202c;
  line-height: 1.4;
}

.card-desc {
  font-size: 0.85rem;
  color: #718096;
  line-height: 1.55;
  flex: 1;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 0.6rem;
  border-top: 1px solid #f7fafc;
}

.card-stats {
  display: flex;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.cstat {
  font-size: 0.78rem;
  color: #a0aec0;
  display: flex;
  align-items: center;
  gap: 4px;
}

.cstat i { font-size: 0.72rem; color: #cbd5e0; }

.card-author {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-shrink: 0;
}

.author-name {
  font-size: 0.78rem;
  color: #a0aec0;
}

/* ── Paginación ───────────────────────────────────────────────────────────── */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
}

.page-btn {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #4a5568;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  transition: all 0.15s;
}

.page-btn:hover:not(:disabled) {
  border-color: #667eea;
  color: #667eea;
}

.page-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.88rem;
  color: #718096;
  min-width: 60px;
  text-align: center;
}

/* ── Dark mode ────────────────────────────────────────────────────────────── */
body.dark-mode .test-card,
body.dark-mode .state-box {
  background: var(--tm-surface) !important;
  border-color: var(--tm-border) !important;
}

body.dark-mode .card-title,
body.dark-mode .feat-n {
  color: var(--tm-text) !important;
}

body.dark-mode .card-desc,
body.dark-mode .author-name,
body.dark-mode .cstat {
  color: var(--tm-muted) !important;
}

body.dark-mode .search-input,
body.dark-mode .filter-select {
  background: var(--tm-bg) !important;
  border-color: var(--tm-border) !important;
  color: var(--tm-text) !important;
}

body.dark-mode .pill {
  background: var(--tm-bg);
  border-color: var(--tm-border);
  color: var(--tm-muted);
}

body.dark-mode .pill:hover {
  background: var(--tm-hover);
}

body.dark-mode .sort-btn:hover {
  background: var(--tm-hover);
  color: var(--tm-text);
}

body.dark-mode .card-footer {
  border-top-color: var(--tm-border);
}


body.dark-mode .page-btn {
  background: var(--tm-surface);
  border-color: var(--tm-border);
  color: var(--tm-muted);
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
  .filter-bar { flex-direction: column; align-items: stretch; }

  .search-wrap,
  .filter-select { width: 100%; }

  .cards-grid { grid-template-columns: 1fr; }
}
</style>