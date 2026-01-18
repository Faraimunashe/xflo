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
              <div class="border border-gray-200 rounded-lg p-4">
                <div class="text-right text-lg font-semibold text-gray-900">{{ formatCurrency(assets) }}</div>
              </div>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Liabilities & Equity</h3>
              <div class="border border-gray-200 rounded-lg p-4 space-y-2">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Liabilities</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatCurrency(liabilities) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600">Equity</span>
                  <span class="text-sm font-medium text-gray-900">{{ formatCurrency(equity) }}</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-200">
                  <span class="text-sm font-semibold text-gray-900">Total</span>
                  <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(totalLiabilitiesEquity) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 text-center">
            <div
              :class="[
                'inline-block px-4 py-2 rounded-md text-sm font-semibold',
                Math.abs(assets - totalLiabilitiesEquity) < 0.01
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
              ]"
            >
              {{ Math.abs(assets - totalLiabilitiesEquity) < 0.01 ? 'Balanced' : 'Not Balanced' }}
              (Difference: {{ formatCurrency(Math.abs(assets - totalLiabilitiesEquity)) }})
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
  assets: {
    type: Number,
    default: 0,
  },
  liabilities: {
    type: Number,
    default: 0,
  },
  equity: {
    type: Number,
    default: 0,
  },
  currentSurplus: {
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
