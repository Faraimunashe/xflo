<template>
    <Head title="Journal Entries" />
    <div class="mb-6 flex justify-between items-center">
        <SectionHeader title="Journal Entries" />
        <PrimaryButton @click="$inertia.visit('/journal-entries/create')">
            <i class="fas fa-plus mr-2"></i> New Entry
        </PrimaryButton>
    </div>

    <Card>
          <div class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-4">
            <DateInput
              v-model="form.date_from"
              label="From Date"
              @update:model-value="search"
            />
            <DateInput
              v-model="form.date_to"
              label="To Date"
              @update:model-value="search"
            />
            <select
              v-model="form.status"
              @change="search"
              class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
            >
              <option value="">All Statuses</option>
              <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
            <select
              v-model="form.source"
              @change="search"
              class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
            >
              <option value="">All Sources</option>
              <option v-for="source in sources" :key="source.value" :value="source.value">
                {{ source.label }}
              </option>
            </select>
            <TextInput
              v-model="form.reference"
              placeholder="Reference..."
              @update:model-value="search"
            />
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Debit</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Credit</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="entry in entries.data" :key="entry.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(entry.entry_date) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.reference || '-' }}</td>
                  <td class="px-6 py-4 text-sm text-gray-900">{{ entry.description }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ entry.source ? entry.source.replace('_', ' ') : '-' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="[
                        entry.status === 'posted' ? 'bg-green-100 text-green-800' :
                        entry.status === 'reversed' ? 'bg-red-100 text-red-800' :
                        'bg-gray-100 text-gray-800',
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
                      ]"
                    >
                      {{ entry.status.charAt(0).toUpperCase() + entry.status.slice(1) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(entry.total_debit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(entry.total_credit) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <Link :href="`/journal-entries/${entry.id}`" class="text-blue-600 hover:text-blue-900 mr-3">
                      <i class="fas fa-eye"></i>
                    </Link>
                    <Link
                      v-if="entry.status === 'draft'"
                      :href="`/journal-entries/${entry.id}/edit`"
                      class="text-blue-600 hover:text-blue-900 mr-3"
                    >
                      <i class="fas fa-edit"></i>
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="entries.data.length === 0" class="text-center py-8 text-gray-500">
            No journal entries found.
          </div>

          <div v-if="entries.links && entries.links.length > 3" class="mt-4 flex justify-center">
            <div v-for="link in entries.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                  'px-4 py-2 border border-gray-300 rounded-md mx-1 hover:bg-gray-50'
                ]"
              />
              <span
                v-else
                v-html="link.label"
                :class="[
                  'px-4 py-2 border border-gray-300 rounded-md mx-1 bg-gray-100 text-gray-400 cursor-not-allowed'
                ]"
              />
            </div>
          </div>
    </Card>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import TextInput from '../../Shared/Components/TextInput.vue';
import DateInput from '../../Shared/Components/DateInput.vue';

defineOptions({
    layout: Layout,
});

defineProps({
  entries: Object,
  statuses: Array,
  sources: Array,
});

const form = reactive({
  date_from: '',
  date_to: '',
  status: '',
  source: '',
  reference: '',
});

const search = () => {
  router.get('/journal-entries', form, {
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
