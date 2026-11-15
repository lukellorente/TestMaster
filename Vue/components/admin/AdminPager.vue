<template>
  <nav v-if="lastPage > 1" class="ap" aria-label="Paginación">
    <button type="button" class="btn btn--ghost" :disabled="page <= 1 || busy" @click="$emit('go', page - 1)">
      <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Anterior
    </button>
    <span class="ap__info tabular">Página {{ page }} de {{ lastPage }} · {{ total }} en total</span>
    <button type="button" class="btn btn--ghost" :disabled="page >= lastPage || busy" @click="$emit('go', page + 1)">
      Siguiente<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    </button>
  </nav>
</template>

<script setup>
defineProps({
  page: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  busy: { type: Boolean, default: false },
});
defineEmits(['go']);
</script>

<style scoped>
.ap {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
}

.ap__info {
  font-size: var(--fs-small);
  color: var(--muted);
  text-align: center;
}
</style>
