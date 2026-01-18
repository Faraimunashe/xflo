<template>
    <Head title="Ledger Report" />
    <SectionHeader title="Account Statement (Ledger)" description="View transactions and running balance for an account" />
    
    <Card>
          <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <SearchSelect
              v-model="form.account_id"
              :options="accounts"
              option-label="label"
              option-value="id"
              track-by="id"
              label="Account"
              placeholder="Select Account"
              @update:model-value="loadReport"
            />
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
              <a v-if="form.account_id && form.date_from && form.date_to" :href="`/reports/ledger/pdf?account_id=${form.account_id}&date_from=${form.date_from}&date_to=${form.date_to}`" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-pdf mr-2"></i> PDF
              </a>
              <a v-if="form.account_id && form.date_from && form.date_to" :href="`/reports/ledger/excel?account_id=${form.account_id}&date_from=${form.date_from}&date_to=${form.date_to}`" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                <i class="fas fa-file-excel mr-2"></i> Excel
              </a>
            </div>
          </div>

          <div v-if="transactions.length > 0 || openingBalance !== 0" class="overflow-x-auto">
            <div class="mb-4 text-sm text-gray-600">
              <span class="font-medium">Opening Balance:</span> {{ formatCurrency(openingBalance) }}
            </div>
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" colspan="6">Opening Balance</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ formatCurrency(openingBalance) }}</td>
                </tr>
                <tr v-for="transaction in transactions" :key="transaction.entry_id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(transaction.entry_date) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ transaction.reference || '-' }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ transaction.description }}</td>
                  <td class="px-6 py-4 text-sm text-gray-500">{{ transaction.narration || '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(transaction.debit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(transaction.credit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-semibold">{{ formatCurrency(transaction.balance) }}</td>
                </tr>
                <tr class="bg-gray-50 font-semibold">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="6">Closing Balance</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(closingBalance) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
    </Card>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import DateInput from '../../Shared/Components/DateInput.vue';
import SearchSelect from '../../Shared/Components/SearchSelect.vue';

defineOptions({
    layout: Layout,
});

const props = defineProps({
  transactions: {
    type: Array,
    default: () => [],
  },
  openingBalance: {
    type: Number,
    default: 0,
  },
  closingBalance: {
    type: Number,
    default: 0,
  },
  accounts: Array,
  filters: Object,
});

const form = reactive({
  account_id: props.filters?.account_id || '',
  date_from: props.filters?.date_from || new Date(new Date().getFullYear(), 0, 1).toISOString().split('T')[0],
  date_to: props.filters?.date_to || new Date().toISOString().split('T')[0],
});

const loadReport = () => {
  router.get('/reports/ledger', form, {
    preserveState: true,
    preserveScroll: true,
  });
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};
</script>
