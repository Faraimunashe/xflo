<template>
    <Head title="Create Account" />
    <SectionHeader title="Create Account" />
    
    <Card>
          <form @submit.prevent="submit">
            <div class="space-y-6">
              <div class="grid grid-cols-2 gap-4">
                <TextInput
                  v-model="form.code"
                  label="Account Code"
                  required
                  :error="errors.code"
                />
                <TextInput
                  v-model="form.name"
                  label="Account Name"
                  required
                  :error="errors.name"
                />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Account Type <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.type"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.type }"
                  >
                    <option value="">Select Type</option>
                    <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </select>
                  <FormError v-if="errors.type" :message="errors.type" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Normal Balance <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.normal_balance"
                    required
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                    :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.normal_balance }"
                  >
                    <option value="">Select Balance</option>
                    <option v-for="balance in normalBalances" :key="balance.value" :value="balance.value">
                      {{ balance.label }}
                    </option>
                  </select>
                  <FormError v-if="errors.normal_balance" :message="errors.normal_balance" />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="flex items-center">
                    <input
                      type="checkbox"
                      v-model="form.is_cash"
                      class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <span class="ml-2 text-sm text-gray-700">Is Cash Account</span>
                  </label>
                </div>

                <div v-if="form.is_cash">
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Cash Flow Type
                  </label>
                  <select
                    v-model="form.cashflow_type"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                  >
                    <option :value="null">Select Type</option>
                    <option v-for="type in cashflowTypes" :key="type.value" :value="type.value">
                      {{ type.label }}
                    </option>
                  </select>
                </div>
              </div>

              <div>
                <label class="flex items-center">
                  <input
                    type="checkbox"
                    v-model="form.is_active"
                    :checked="form.is_active !== false"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  />
                  <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
              <SecondaryButton type="button" @click="$inertia.visit('/accounts')">
                Cancel
              </SecondaryButton>
              <PrimaryButton type="submit" :processing="processing">
                Create Account
              </PrimaryButton>
            </div>
          </form>
    </Card>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import SecondaryButton from '../../Shared/Components/SecondaryButton.vue';
import TextInput from '../../Shared/Components/TextInput.vue';
import FormError from '../../Shared/Components/FormError.vue';

defineOptions({
    layout: Layout,
});

defineProps({
  accountTypes: Array,
  normalBalances: Array,
  cashflowTypes: Array,
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  code: '',
  name: '',
  type: '',
  normal_balance: '',
  is_cash: false,
  cashflow_type: null,
  is_active: true,
});

const submit = () => {
  form.post('/accounts');
};
</script>
