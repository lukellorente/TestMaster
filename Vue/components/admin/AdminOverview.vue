<template>
  <BaseLoader v-if="loading" text="Cargando métricas…" />
  <p v-else-if="error" class="adm-error" role="alert">{{ error }}</p>

  <div v-else class="ov">
    <dl class="ov__tiles">
      <div class="ov__tile panel">
        <dt>Usuarios</dt>
        <dd class="tabular">{{ m.users.total }}</dd>
        <span>+{{ m.users.new_30d }} en 30 días · {{ m.users.active_7d }} activos esta semana</span>
      </div>
      <div class="ov__tile panel">
        <dt>Tests publicados</dt>
        <dd class="tabular">{{ m.tests.published }}</dd>
        <span>{{ m.tests.draft }} borradores · {{ m.tests.archived }} archivados · {{ m.tests.featured }} destacados</span>
      </div>
      <div class="ov__tile panel">
        <dt>Intentos (30 días)</dt>
        <dd class="tabular">{{ m.attempts.last_30d }}</dd>
        <span>{{ m.attempts.reviews_30d }} repasos · {{ m.attempts.total }} desde el inicio</span>
      </div>
      <div class="ov__tile panel">
        <dt>Cuentas suspendidas</dt>
        <dd class="tabular">{{ m.users.suspended }}</dd>
        <span>{{ plural(m.users.admins, 'administrador', 'administradores') }}</span>
      </div>
    </dl>

    <div class="ov__charts">
      <section class="panel ov__chart" aria-labelledby="ov-attempts">
        <h3 id="ov-attempts" class="ov__h3">Intentos terminados por día</h3>
        <ColumnChart :data="m.series.map((d) => ({ date: d.date, value: d.attempts }))" label="Intentos" />
      </section>
      <section class="panel ov__chart" aria-labelledby="ov-signups">
        <h3 id="ov-signups" class="ov__h3">Registros por día</h3>
        <ColumnChart :data="m.series.map((d) => ({ date: d.date, value: d.signups }))" label="Registros" />
      </section>
    </div>
    <p class="ov__note">Días contados en la zona horaria del servidor ({{ m.timezone }}).</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../api/axios';
import BaseLoader from '../base/BaseLoader.vue';
import ColumnChart from '../charts/ColumnChart.vue';
import { plural } from '../../utils/tests';

const loading = ref(true);
const error = ref('');
const m = ref(null);

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/metrics');
    m.value = data;
  } catch {
    error.value = 'No se han podido cargar las métricas.';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.ov {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.ov__tiles {
  margin: 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
  gap: 16px;
}

.ov__tile {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.ov__tile dt {
  font-size: var(--fs-small);
  color: var(--muted);
}

.ov__tile dd {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 600;
}

.ov__tile span {
  font-size: var(--fs-meta);
  color: var(--muted);
}

.ov__charts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
  gap: 24px;
}

.ov__chart {
  padding: 20px 24px;
  min-width: 0;
}

.ov__h3 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 12px;
}

.ov__note {
  margin: -12px 0 0;
  font-size: var(--fs-meta);
  color: var(--muted);
}
</style>
