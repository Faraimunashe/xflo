<template>
    <Head title="Journal Entry Details" />
    <div class="mb-6 flex justify-between items-center">
        <SectionHeader :title="`Journal Entry #${entry.id}`" />
          <div class="flex gap-3">
            <Link
              v-if="entry.status === 'draft'"
              :href="`/journal-entries/${entry.id}/edit`"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
            >
              <i class="fas fa-edit mr-2"></i> Edit
            </Link>
            <form
              v-if="entry.status === 'draft'"
              @submit.prevent="postEntry"
              class="inline-block"
            >
              <PrimaryButton type="submit" :processing="posting">
                <i class="fas fa-check mr-2"></i> Post Entry
              </PrimaryButton>
            </form>
            <DangerButton
              v-if="entry.status === 'posted'"
              @click="showReverseConfirm = true"
            >
              <i class="fas fa-undo mr-2"></i> Reverse Entry
            </DangerButton>
          </div>
        </div>

        <Card>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-500">Entry Date</label>
              <p class="mt-1 text-sm text-gray-900">{{ entry.entry_date }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Reference</label>
              <p class="mt-1 text-sm text-gray-900">{{ entry.reference || '-' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Source</label>
              <p class="mt-1 text-sm text-gray-900">{{ entry.source ? entry.source.replace('_', ' ') : '-' }}</p>
            </div>
            <div class="md:col-span-3">
              <label class="block text-sm font-medium text-gray-500">Description</label>
              <p class="mt-1 text-sm text-gray-900">{{ entry.description }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-500">Status</label>
              <span
                :class="[
                  entry.status === 'posted' ? 'bg-green-100 text-green-800' :
                  entry.status === 'reversed' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800',
                  'mt-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full'
                ]"
              >
                {{ entry.status.charAt(0).toUpperCase() + entry.status.slice(1) }}
              </span>
            </div>
            <div v-if="entry.posted_at">
              <label class="block text-sm font-medium text-gray-500">Posted At</label>
              <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(entry.posted_at) }}</p>
            </div>
            <div v-if="entry.posted_by">
              <label class="block text-sm font-medium text-gray-500">Posted By</label>
              <p class="mt-1 text-sm text-gray-900">{{ entry.posted_by?.name }}</p>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Journal Lines</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="line in entry.journal_lines" :key="line.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ line.account.code }} - {{ line.account.name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ line.narration || '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(line.debit) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(line.credit) }}</td>
                  </tr>
                  <tr class="bg-gray-50 font-semibold">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="2">Total</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalDebit) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(totalCredit) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="entry.reversal" class="mt-6 border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Reversal Entry</h3>
            <Link :href="`/journal-entries/${entry.reversal.id}`" class="text-blue-600 hover:text-blue-900">
              View Reversal Entry #{{ entry.reversal.id }}
            </Link>
          </div>
        </Card>

    <div class="mt-6">
        <SecondaryButton @click="$inertia.visit('/journal-entries')">
            Back to List
        </SecondaryButton>
    </div>

    <ConfirmModal
      :show="showReverseConfirm"
      title="Reverse Journal Entry"
      :message="`Are you sure you want to reverse journal entry #${entry.id}? This will create a reversal entry and cannot be undone.`"
      confirm-text="Reverse Entry"
      @confirm="reverseEntry"
      @close="showReverseConfirm = false"
    />
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import SecondaryButton from '../../Shared/Components/SecondaryButton.vue';
import DangerButton from '../../Shared/Components/DangerButton.vue';
import ConfirmModal from '../../Shared/Components/ConfirmModal.vue';

defineOptions({
    layout: Layout,
});

const showReverseConfirm = ref(false);
const posting = ref(false);

const props = defineProps({
  entry: Object,
});

const totalDebit = computed(() => {
  if (!props.entry?.journal_lines) return 0;
  return props.entry.journal_lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
});

const totalCredit = computed(() => {
  if (!props.entry?.journal_lines) return 0;
  return props.entry.journal_lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
});

const postEntry = () => {
  posting.value = true;
  router.post(`/journal-entries/${props.entry.id}/post`, {}, {
    preserveScroll: true,
    onFinish: () => {
      posting.value = false;
    },
  });
};

const reverseEntry = () => {
  showReverseConfirm.value = false;
  router.post(`/journal-entries/${props.entry.id}/reverse`, {}, {
    preserveScroll: true,
  });
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

const formatDateTime = (value) => {
  return new Date(value).toLocaleString();
};
</script>
