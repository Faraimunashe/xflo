<template>
  <button
    :type="type"
    :disabled="disabled || processing"
    :class="[
      'inline-flex items-center justify-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring focus:ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150',
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
