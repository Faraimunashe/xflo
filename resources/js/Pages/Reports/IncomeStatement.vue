<template>
    <Head title="Income Statement" />
    <SectionHeader title="Income Statement" description="Profit & Loss Statement" />
    
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
              <a :href="`/reports/income-statement/pdf?date_from=${form.date_from}&date_to=${form.date_to}`" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-pdf mr-2"></i> PDF
              </a>
              <a :href="`/reports/income-statement/excel?date_from=${form.date_from}&date_to=${form.date_to}`" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-excel mr-2"></i> Excel
              </a>
            </div>
          </div>

          <div class="mb-4 text-center">
            <h2 class="text-2xl font-bold text-gray-900">Paradise International School</h2>
            <p class="text-lg text-gray-600">Income Statement</p>
            <p class="text-sm text-gray-500">For the period {{ dateFrom }} to {{ dateTo }}</p>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account Name</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td colspan="3" class="px-6 py-3 text-sm font-bold text-gray-900 bg-gray-100">REVENUE</td>
                </tr>
                <tr v-for="item in revenueItems" :key="item.code">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.code }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ item.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                </tr>
                <tr v-if="revenueItems.length === 0">
                  <td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">No revenue transactions</td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                  <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">Total Revenue</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalRevenue) }}</td>
                </tr>
                <tr>
                  <td colspan="3" class="px-6 py-3 text-sm font-bold text-gray-900 bg-gray-100">EXPENSES</td>
                </tr>
                <tr v-for="item in expenseItems" :key="item.code">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.code }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ item.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.amount) }}</td>
                </tr>
                <tr v-if="expenseItems.length === 0">
                  <td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">No expense transactions</td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                  <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">Total Expenses</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalExpenses) }}</td>
                </tr>
                <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                  <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">Net Surplus / (Deficit)</td>
                  <td
                    :class="[
                      'px-6 py-4 whitespace-nowrap text-sm text-right font-semibold',
                      netSurplus >= 0 ? 'text-green-600' : 'text-red-600'
                    ]"
                  >
                    {{ formatCurrency(netSurplus) }}
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
  revenueItems: {
    type: Array,
    default: () => [],
  },
  expenseItems: {
    type: Array,
    default: () => [],
  },
  totalRevenue: {
    type: Number,
    default: 0,
  },
  totalExpenses: {
    type: Number,
    default: 0,
  },
  netSurplus: {
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
  router.get('/reports/income-statement', form, {
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
