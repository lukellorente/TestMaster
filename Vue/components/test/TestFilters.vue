<template>
  <div class="filters">
    <BaseSelect
      v-model="localCategory"
      :options="categoryOptions"
      placeholder="Todas las categorías"
    />
    <BaseSelect
      v-model="localSort"
      :options="sortOptions"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import BaseSelect from '../base/BaseSelect.vue';

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  category: {
    type: [String, Number],
    default: '',
  },
  sort: {
    type: String,
    default: 'recent',
  },
});

const emit = defineEmits(['update:category', 'update:sort', 'change']);

const localCategory = ref(props.category);
const localSort = ref(props.sort);

const categoryOptions = computed(() => 
  props.categories.map(cat => ({
    value: cat.id,
    label: cat.name,
  }))
);

const sortOptions = [
  { value: 'recent', label: 'Más recientes' },
  { value: 'views', label: 'Más vistos' },
  { value: 'attempts', label: 'Más realizados' },
];

watch(localCategory, (val) => {
  emit('update:category', val);
  emit('change', { category: val, sort: localSort.value });
});

watch(localSort, (val) => {
  emit('update:sort', val);
  emit('change', { category: localCategory.value, sort: val });
});

watch(() => props.category, (val) => { localCategory.value = val; });
watch(() => props.sort, (val) => { localSort.value = val; });
</script>

