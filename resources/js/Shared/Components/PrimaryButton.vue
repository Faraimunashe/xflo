<template>
  <button
    :type="type"
    :disabled="disabled || processing"
    :class="[
      'inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150',
      className
    ]"
  >
    <span v-if="processing" class="mr-2">
      <i class="fas fa-spinner fa-spin"></i>
    </span>
    <slot />
  </button>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  processing: {
    type: Boolean,
    default: false,
  },
  className: {
    type: String,
    default: '',
  },
});
</script>
