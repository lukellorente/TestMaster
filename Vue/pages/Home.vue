<template>
  <div>
    <div class="page-header">
      <div>
        <h1>Tests recientes</h1>
        <p class="subtitle">
          <span class="count">{{ stats.total_tests?.toLocaleString() || 0 }}</span> tests disponibles
        </p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filters">
      <select v-model="filters.category">
        <option value="">Todas las categorías</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ cat.name }}
        </option>
      </select>

      <select v-model="filters.sort">
        <option value="recent">Más recientes</option>
        <option value="views">Más vistos</option>
        <option value="attempts">Más realizados</option>
      </select>
    </div>

    <!-- Lista de tests -->
    <div class="tests-table-container">
      <div v-if="loading" style="text-align: center; padding: 3rem; color: #666">
        <i class="fa-solid fa-spinner fa-spin"></i> Cargando tests...
      </div>
      <div
        v-else-if="tests.length === 0"
        style="text-align: center; padding: 3rem; color: #666; background: white; border-radius: 12px; border: 1px solid #e2e8f0"
      >
        <p>No hay tests disponibles aún.</p>
        <router-link v-if="authStore.isLoggedIn" to="/create-test">
          <button class="btn" style="margin-top: 1rem">Crear el Primer Test</button>
        </router-link>
      </div>
      <table v-else class="tests-table">
        <thead>
          <tr>
            <th class="th-title">Últimos tests</th>
            <th class="th-date"><i class="fa-solid fa-clock"></i> Fecha</th>
            <th class="th-questions"><i class="fa-solid fa-file-lines"></i> Preg.</th>
            <th class="th-views"><i class="fa-solid fa-eye"></i> Visto</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="test in tests" :key="test.id" class="test-row">
            <td class="td-title">
              <router-link :to="`/test/${test.id}`" class="test-link">
                {{ test.title }}
                <span v-if="test.completed_attempts_count > 10" class="badge">popular</span>
              </router-link>
            </td>
            <td class="td-date">{{ formatDate(test.created_at) }}</td>
            <td class="td-questions">{{ test.questions_count }} preg</td>
            <td class="td-views">
              {{ test.completed_attempts_count }} visto{{ test.completed_attempts_count !== 1 ? 's' : '' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const authStore = useAuthStore();
const route = useRoute();

const tests = ref([]);
const stats = ref({});
const categories = ref([]);
const loading = ref(true);
const filters = ref({
  category: '',
  sort: 'recent',
});

const formatDate = (dateStr) => {
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', {
    month: '2-digit', day: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
  });
};

const fetchTests = async () => {
  loading.value = true;
  try {
    const params = { sort: filters.value.sort };
    if (filters.value.category) params.category_id = filters.value.category;
    if (route.query.search) params.search = route.query.search;

    const [testsRes, statsRes] = await Promise.all([
      api.get('/tests', { params }),
      api.get('/tests/stats'),
    ]);
    tests.value = testsRes.data.data || testsRes.data;
    stats.value = statsRes.data;
  } catch (err) {
    console.error('Error fetching tests:', err);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const { data } = await api.get('/categories');
    categories.value = data;
  } catch (err) {
    console.error('Error fetching categories:', err);
  }
};

onMounted(() => {
  fetchTests();
  fetchCategories();
});

watch(filters, fetchTests, { deep: true });
watch(() => route.query.search, fetchTests);
</script>
