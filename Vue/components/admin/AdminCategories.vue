<template>
  <div class="ac">
    <div class="ac__head">
      <p class="adm-muted">Las categorías agrupan los tests en el catálogo y en «Dominio por tema».</p>
      <button type="button" class="btn" @click="openForm()"><i class="fa-solid fa-plus" aria-hidden="true"></i>Nueva categoría</button>
    </div>

    <BaseLoader v-if="loading" text="Cargando categorías…" />
    <p v-else-if="error" class="adm-error" role="alert">{{ error }}</p>
    <p v-else-if="!rows.length" class="adm-empty">Aún no hay categorías.</p>

    <ul v-else class="adm-list">
      <li v-for="c in rows" :key="c.id" class="adm-row">
        <div class="adm-row__main">
          <span class="adm-row__title">
            <span class="ac__swatch" :style="{ background: c.color || 'var(--sunk)' }" aria-hidden="true">
              <i v-if="c.icon" :class="c.icon"></i>
            </span>
            {{ c.name }}
          </span>
          <span class="adm-muted">{{ plural(c.tests_total, 'test', 'tests') }}<template v-if="c.description"> · {{ c.description }}</template></span>
        </div>
        <div class="adm-row__actions">
          <button type="button" class="btn btn--ghost" @click="openForm(c)"><i class="fa-solid fa-pen" aria-hidden="true"></i>Editar</button>
          <button type="button" class="btn btn--ghost adm-danger" @click="toDelete = c"><i class="fa-regular fa-trash-can" aria-hidden="true"></i>Eliminar</button>
        </div>
      </li>
    </ul>

    <!-- Crear / editar -->
    <BaseModal v-model="showForm" :title="editing ? 'Editar categoría' : 'Nueva categoría'" max-width="480px">
      <form id="ac-form" novalidate @submit.prevent="save">
        <BaseInput v-model="form.name" label="Nombre" maxlength="100" :error="errors.name" required />
        <BaseInput v-model="form.description" label="Descripción" type="textarea" rows="2" maxlength="500" optional :error="errors.description" />
        <BaseInput
          v-model="form.icon"
          label="Icono"
          optional
          placeholder="fa-solid fa-flask"
          help-text="Clase de Font Awesome, por ejemplo fa-solid fa-landmark."
          :error="errors.icon"
        >
          <template #aside><i v-if="validIcon" :class="form.icon" aria-hidden="true"></i></template>
        </BaseInput>
        <div class="ac__color">
          <label for="ac-color" class="ac__label">Color <span class="adm-muted">(opcional)</span></label>
          <div class="ac__color-row">
            <input id="ac-color" v-model="form.color" type="color" class="ac__picker" />
            <span class="tabular adm-muted">{{ form.color || 'Sin color' }}</span>
            <button v-if="form.color" type="button" class="btn btn--ghost" @click="form.color = ''">Quitar</button>
          </div>
          <p v-if="errors.color" class="error-text">{{ errors.color }}</p>
        </div>
      </form>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="showForm = false">Cancelar</button>
        <button type="submit" form="ac-form" class="btn" :disabled="saving">{{ editing ? 'Guardar' : 'Crear' }}</button>
      </template>
    </BaseModal>

    <BaseModal :model-value="Boolean(toDelete)" title="¿Eliminar esta categoría?" max-width="440px" @update:model-value="toDelete = null">
      <p class="adm-modal-text">
        «{{ toDelete?.name }}» desaparecerá del catálogo.
        <template v-if="toDelete?.tests_total">Sus {{ plural(toDelete.tests_total, 'test', 'tests') }} no se borran: se quedan sin categoría.</template>
      </p>
      <template #footer>
        <button type="button" class="btn btn--secondary" @click="toDelete = null">Cancelar</button>
        <button type="button" class="btn btn--danger" :disabled="saving" @click="remove">Eliminar</button>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import api from '../../api/axios';
import BaseLoader from '../base/BaseLoader.vue';
import BaseModal from '../base/BaseModal.vue';
import BaseInput from '../base/BaseInput.vue';
import { plural } from '../../utils/tests';
import { useToast } from '../../composables/useToast';

const toast = useToast();
const rows = ref([]);
const loading = ref(true);
const error = ref('');
const saving = ref(false);
const showForm = ref(false);
const editing = ref(null);
const toDelete = ref(null);
const form = reactive({ name: '', description: '', icon: '', color: '' });
const errors = reactive({});

const validIcon = computed(() => /^fa-(solid|regular|brands) fa-[a-z0-9-]+$/.test(form.icon.trim()));

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/admin/categories');
    rows.value = data;
  } catch {
    error.value = 'No se han podido cargar las categorías.';
  } finally {
    loading.value = false;
  }
}

function openForm(category = null) {
  editing.value = category;
  Object.assign(form, {
    name: category?.name ?? '',
    description: category?.description ?? '',
    icon: category?.icon ?? '',
    color: category?.color ?? '',
  });
  Object.keys(errors).forEach((k) => delete errors[k]);
  showForm.value = true;
}

async function save() {
  saving.value = true;
  Object.keys(errors).forEach((k) => delete errors[k]);
  const payload = {
    name: form.name.trim(),
    description: form.description.trim() || null,
    icon: form.icon.trim() || null,
    color: form.color || null,
  };
  try {
    const { data } = editing.value
      ? await api.put(`/admin/categories/${editing.value.id}`, payload)
      : await api.post('/admin/categories', payload);
    showForm.value = false;
    toast.success(data.message);
    await load();
  } catch (err) {
    const errs = err.response?.data?.errors;
    if (errs) Object.entries(errs).forEach(([k, v]) => { errors[k] = v[0]; });
    else toast.error('No se ha podido guardar la categoría.');
  } finally {
    saving.value = false;
  }
}

async function remove() {
  saving.value = true;
  try {
    await api.delete(`/admin/categories/${toDelete.value.id}`);
    toDelete.value = null;
    toast.success('Categoría eliminada.');
    await load();
  } catch {
    toast.error('No se ha podido eliminar la categoría.');
  } finally {
    saving.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.ac__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.ac__swatch {
  display: inline-grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-control);
  color: #fff;
  font-size: 0.8rem;
  flex-shrink: 0;
}

.ac__label {
  font-size: var(--fs-small);
  font-weight: 500;
}

.ac__color {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.ac__color-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ac__picker {
  width: 44px;
  height: 36px;
  padding: 2px;
  border: 1px solid var(--control);
  border-radius: var(--radius-control);
  background: var(--bg);
  cursor: pointer;
}
</style>
