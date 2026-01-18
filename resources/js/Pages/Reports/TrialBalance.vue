<template>
    <Head title="Trial Balance" />
    <SectionHeader title="Trial Balance" description="As-at date trial balance report" />
    
    <Card>
          <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <DateInput
              v-model="form.as_at_date"
              label="As-At Date"
              @update:model-value="loadReport"
            />
            <div class="flex items-end gap-2">
              <PrimaryButton @click="loadReport">Load Report</PrimaryButton>
              <a :href="`/reports/trial-balance/pdf?as_at_date=${form.as_at_date}`" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-pdf mr-2"></i> PDF
              </a>
              <a :href="`/reports/trial-balance/excel?as_at_date=${form.as_at_date}`" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-excel mr-2"></i> Excel
              </a>
            </div>
          </div>

          <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Paradise International School</h2>
            <p class="text-lg text-gray-600">Trial Balance</p>
            <p class="text-sm text-gray-500">As at {{ asAtDate }}</p>
          </div>

          <div v-if="trialBalance.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in trialBalance" :key="item.code">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.code }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ item.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.type }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.debit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.credit) }}</td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="3">Total</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalDebit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalCredit) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
    </Card>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive, onMounted } from 'vue';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import DateInput from '../../Shared/Components/DateInput.vue';

defineOptions({
    layout: Layout,
});

const props = defineProps({
  trialBalance: {
    type: Array,
    default: () => [],
  },
  totalDebit: {
    type: Number,
    default: 0,
  },
  totalCredit: {
    type: Number,
    default: 0,
  },
  asAtDate: String,
});

const form = reactive({
  as_at_date: props.asAtDate || new Date().toISOString().split('T')[0],
});

const loadReport = () => {
  router.get('/reports/trial-balance', form, {
    preserveState: true,
    preserveScroll: true,
  });
};

onMounted(() => {
  if (!props.asAtDate) {
    loadReport();
  }
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};
</script>
