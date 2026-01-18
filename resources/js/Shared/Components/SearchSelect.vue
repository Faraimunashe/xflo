<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <Multiselect
      v-model="selectedValue"
      :options="options"
      :placeholder="placeholder || 'Select...'"
      :searchable="true"
      :allow-empty="!required"
      :disabled="disabled"
      :label="optionLabel"
      :track-by="trackBy"
      :custom-label="customLabelFn"
      :class="[
        error ? 'multiselect--error' : ''
      ]"
    />
    <FormError v-if="error" :message="error" />
  </div>
</template>

<script setup>
import Multiselect from 'vue-multiselect';
import FormError from './FormError.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, Object, null],
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  label: String,
  placeholder: String,
  required: Boolean,
  disabled: Boolean,
  error: String,
  optionLabel: {
    type: String,
    default: 'label',
  },
  optionValue: {
    type: String,
    default: 'id',
  },
  trackBy: {
    type: String,
    default: 'id',
  },
  customLabel: Function,
});

const emit = defineEmits(['update:modelValue']);

// Convert modelValue (ID) to the full object for multiselect
const selectedValue = computed({
  get: () => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
      return null;
    }
    // Find the option that matches the modelValue
    return props.options.find(opt => {
      if (typeof opt === 'object') {
        return opt[props.optionValue] === props.modelValue;
      }
      return opt === props.modelValue;
    }) || null;
  },
  set: (value) => {
    // Extract the value based on optionValue prop
    if (value === null || value === undefined) {
      emit('update:modelValue', null);
    } else if (typeof value === 'object') {
      emit('update:modelValue', value[props.optionValue]);
    } else {
      emit('update:modelValue', value);
    }
  }
});

// Custom label function to display the label property
const customLabelFn = (option) => {
  if (props.customLabel) {
    return props.customLabel(option);
  }
  if (typeof option === 'object' && option !== null) {
    return option[props.optionLabel] || option.label || option.name || JSON.stringify(option);
  }
  return option;
};
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style>
.multiselect--error .multiselect__tags {
  border-color: #ef4444;
}

/* Ensure dropdown appears above other elements */
.multiselect {
  position: relative;
}

/* Fix z-index for dropdown in tables and scrollable containers */
.multiselect__content-wrapper {
  z-index: 9999 !important;
  position: absolute !important;
  overflow: visible !important;
}

.multiselect__content {
  z-index: 9999 !important;
  max-height: 300px !important;
  overflow-y: auto !important;
  overflow-x: visible !important;
}

/* Ensure dropdown list items are visible */
.multiselect__option {
  z-index: 9999 !important;
}
</style>
