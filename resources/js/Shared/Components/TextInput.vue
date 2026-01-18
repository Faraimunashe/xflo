<template>
  <div>
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="[
        'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2',
        error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '',
        disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'
      ]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <FormError v-if="error" :message="error" />
  </div>
</template>

<script setup>
import FormError from './FormError.vue';

defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  id: {
    type: String,
    default: () => `input-${Math.random().toString(36).substr(2, 9)}`,
  },
  type: {
    type: String,
    default: 'text',
  },
  label: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  error: String,
});

defineEmits(['update:modelValue']);
</script>
