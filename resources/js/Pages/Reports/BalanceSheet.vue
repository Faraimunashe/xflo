<template>
    <Head title="Balance Sheet" />
    <SectionHeader title="Balance Sheet" description="Statement of Financial Position" />
    
    <Card>
          <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <DateInput
              v-model="form.as_at_date"
              label="As-At Date"
              @update:model-value="loadReport"
            />
            <div class="flex items-end gap-2">
              <PrimaryButton @click="loadReport">Load Report</PrimaryButton>
              <a :href="`/reports/balance-sheet/pdf?as_at_date=${form.as_at_date}`" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-pdf mr-2"></i> PDF
              </a>
              <a :href="`/reports/balance-sheet/excel?as_at_date=${form.as_at_date}`" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-excel mr-2"></i> Excel
              </a>
            </div>
          </div>

          <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Paradise International School</h2>
            <p class="text-lg text-gray-600">Balance Sheet</p>
            <p class="text-sm text-gray-500">As at {{ asAtDate }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Assets</h3>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in assetItems" :key="item.code">
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.code }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.name }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                    </tr>
                    <tr v-if="assetItems.length === 0">
                      <td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No asset accounts found</td>
                    </tr>
                    <tr class="bg-gray-50">
                      <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900">Total Assets</td>
                      <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ formatCurrency(totalAssets) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Liabilities & Equity</h3>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                      <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr>
                      <td colspan="3" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 uppercase">Liabilities</td>
                    </tr>
                    <tr v-for="item in liabilityItems" :key="item.code">
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.code }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.name }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                    </tr>
                    <tr v-if="liabilityItems.length === 0">
                      <td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No liability accounts found</td>
                    </tr>
                    <tr class="bg-gray-50">
                      <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900">Total Liabilities</td>
                      <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ formatCurrency(totalLiabilities) }}</td>
                    </tr>
                    <tr>
                      <td colspan="3" class="px-4 py-2 text-xs font-semibold text-gray-700 bg-gray-100 uppercase">Equity</td>
                    </tr>
                    <tr v-for="item in equityItems" :key="item.code">
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.code }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900">{{ item.name }}</td>
                      <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                    </tr>
                    <tr v-if="equityItems.length === 0 && currentSurplus === 0">
                      <td colspan="3" class="px-4 py-3 text-sm text-gray-500 text-center">No equity accounts found</td>
                    </tr>
                    <tr v-if="currentSurplus !== 0">
                      <td class="px-4 py-3 text-sm text-gray-900"></td>
                      <td class="px-4 py-3 text-sm text-gray-900">Current Surplus</td>
                      <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(currentSurplus) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                      <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-900">Total Equity</td>
                      <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">{{ formatCurrency(totalEquity) }}</td>
                    </tr>
                    <tr class="bg-blue-50">
                      <td colspan="2" class="px-4 py-3 text-sm font-bold text-gray-900">Total Liabilities & Equity</td>
                      <td class="px-4 py-3 text-sm font-bold text-gray-900 text-right">{{ formatCurrency(totalLiabilitiesEquity) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="mt-6 text-center">
            <div
              :class="[
                'inline-block px-4 py-2 rounded-md text-sm font-semibold',
                Math.abs(totalAssets - totalLiabilitiesEquity) < 0.01
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ Math.abs(totalAssets - totalLiabilitiesEquity) < 0.01 ? 'Balanced' : 'Not Balanced' }}
              (Difference: {{ formatCurrency(Math.abs(totalAssets - totalLiabilitiesEquity)) }})
            </div>
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
  assetItems: {
    type: Array,
    default: () => [],
  },
  liabilityItems: {
    type: Array,
    default: () => [],
  },
  equityItems: {
    type: Array,
    default: () => [],
  },
  totalAssets: {
    type: Number,
    default: 0,
  },
  totalLiabilities: {
    type: Number,
    default: 0,
  },
  totalEquityAccounts: {
    type: Number,
    default: 0,
  },
  currentSurplus: {
    type: Number,
    default: 0,
  },
  totalEquity: {
    type: Number,
    default: 0,
  },
  totalLiabilitiesEquity: {
    type: Number,
    default: 0,
  },
  asAtDate: String,
});

const form = reactive({
  as_at_date: props.asAtDate || new Date().toISOString().split('T')[0],
});

const loadReport = () => {
  router.get('/reports/balance-sheet', form, {
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
