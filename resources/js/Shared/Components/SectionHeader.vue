<template>
  <div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">{{ title }}</h2>
    <p v-if="description" class="mt-1 text-sm text-gray-600">{{ description }}</p>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
  description: String,
});
</script>
