<template>
    <Head title="Cash Flow Statement" />
    <SectionHeader title="Cash Flow Statement" description="Direct method cash flow report" />
    
    <Card>
          <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <DateInput
              v-model="form.date_from"
              label="From Date"
              @update:model-value="loadReport"
            />
            <DateInput
              v-model="form.date_to"
              label="To Date"
              @update:model-value="loadReport"
            />
            <div class="flex items-end gap-2">
              <PrimaryButton @click="loadReport">Load Report</PrimaryButton>
              <a :href="`/reports/cashflow/pdf?date_from=${form.date_from}&date_to=${form.date_to}`" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-pdf mr-2"></i> PDF
              </a>
              <a :href="`/reports/cashflow/excel?date_from=${form.date_from}&date_to=${form.date_to}`" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-excel mr-2"></i> Excel
              </a>
            </div>
          </div>

          <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Paradise International School</h2>
            <p class="text-lg text-gray-600">Cash Flow Statement</p>
            <p class="text-sm text-gray-500">For the period {{ dateFrom }} to {{ dateTo }}</p>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">Cash from Operating Activities</td>
                  <td
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm text-right font-semibold',
                      operating >= 0 ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ formatCurrency(operating) }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">Cash from Investing Activities</td>
                  <td
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm text-right font-semibold',
                      investing >= 0 ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ formatCurrency(investing) }}
                  </td>
                </tr>
                <tr>
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">Cash from Financing Activities</td>
                  <td
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm text-right font-semibold',
                      financing >= 0 ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ formatCurrency(financing) }}
                  </td>
                </tr>
                <tr class="bg-gray-50 font-semibold border-t-2 border-gray-300">
                  <td class="px-6 py-4 text-sm font-medium text-gray-900">Net Cash Flow</td>
                  <td
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm text-right font-semibold',
                      netCashflow >= 0 ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ formatCurrency(netCashflow) }}
                  </td>
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
  operating: {
    type: Number,
    default: 0,
  },
  investing: {
    type: Number,
    default: 0,
  },
  financing: {
    type: Number,
    default: 0,
  },
  netCashflow: {
    type: Number,
    default: 0,
  },
  dateFrom: String,
  dateTo: String,
});

const form = reactive({
  date_from: props.dateFrom || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
  date_to: props.dateTo || new Date().toISOString().split('T')[0],
});

const loadReport = () => {
  router.get('/reports/cashflow', form, {
    preserveState: true,
    preserveScroll: true,
  });
};

onMounted(() => {
  if (!props.dateFrom || !props.dateTo) {
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
