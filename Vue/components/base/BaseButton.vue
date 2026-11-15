<template>
  <component
    :is="to ? 'router-link' : 'button'"
    :to="to"
    :type="to ? undefined : type"
    :disabled="disabled || loading"
    :class="['btn', `btn--${variant}`, { 'btn--loading': loading, 'btn--lg': size === 'lg' }]"
    v-bind="$attrs"
  >
    <i v-if="loading" class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
    <i v-else-if="icon" :class="icon" aria-hidden="true"></i>
    <slot />
  </component>
</template>

<script setup>
defineProps({
  // Si tiene 'to', renderiza como router-link
  to: {
    type: [String, Object],
    default: null,
  },
  // Tipo de botón HTML
  type: {
    type: String,
    default: 'button',
  },
  // Variantes de color
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'ghost', 'success', 'warning', 'danger'].includes(v),
  },
  // Icono FontAwesome
  icon: {
    type: String,
    default: null,
  },
  // Tamaño: md (40px) o lg (44px)
  size: {
    type: String,
    default: 'md',
  },
  // Estado de carga
  loading: {
    type: Boolean,
    default: false,
  },
  // Deshabilitado
  disabled: {
    type: Boolean,
    default: false,
  },
});
</script>
