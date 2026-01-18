<template>
    <Head title="Create Journal Entry" />
    <SectionHeader title="Create Journal Entry" />
    
    <Card>
          <form @submit.prevent="submit" class="overflow-visible">
            <div class="space-y-6 overflow-visible">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <DateInput
                  v-model="form.entry_date"
                  label="Entry Date"
                  required
                  :error="errors['entry_date'] || errors['lines']"
                />
                <TextInput
                  v-model="form.reference"
                  label="Reference"
                  placeholder="Optional"
                />
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Source
                  </label>
                  <select
                    v-model="form.source"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                  >
                    <option :value="null">Select Source</option>
                    <option v-for="source in sources" :key="source.value" :value="source.value">
                      {{ source.label }}
                    </option>
                  </select>
                </div>
                <div></div>
              </div>

              <TextInput
                v-model="form.description"
                label="Description"
                required
                :error="errors.description"
              />

              <div>
                <div class="mb-2 flex justify-between items-center">
                  <label class="block text-sm font-medium text-gray-700">
                    Journal Lines <span class="text-red-500">*</span>
                  </label>
                </div>
                <div class="overflow-x-auto overflow-y-visible relative">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="(line, index) in form.lines" :key="index">
                        <td class="px-4 py-3 relative z-10">
                          <div class="relative z-10">
                            <SearchSelect
                              v-model="line.account_id"
                              :options="accounts"
                              option-label="label"
                              option-value="id"
                              track-by="id"
                              placeholder="Select Account"
                              required
                              @update:model-value="checkAndAddLine(index)"
                            />
                          </div>
                          <span v-if="errors[`lines.${index}.account_id`]" class="text-xs text-red-600">
                            {{ errors[`lines.${index}.account_id`] }}
                          </span>
                        </td>
                        <td class="px-4 py-3">
                          <input
                            v-model="line.narration"
                            type="text"
                            placeholder="Optional"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                          />
                        </td>
                        <td class="px-4 py-3">
                          <input
                            v-model.number="line.debit"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="block w-full text-right rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                            @input="onDebitChange(index)"
                          />
                        </td>
                        <td class="px-4 py-3">
                          <input
                            v-model.number="line.credit"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="block w-full text-right rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
                            @input="onCreditChange(index)"
                          />
                        </td>
                        <td class="px-4 py-3 text-center">
                          <button
                            type="button"
                            @click="removeLine(index)"
                            class="text-red-600 hover:text-red-900"
                            :disabled="form.lines.length === 1"
                          >
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <button
                  type="button"
                  @click="addLine"
                  class="mt-2 text-sm text-blue-600 hover:text-blue-900"
                >
                  <i class="fas fa-plus mr-1"></i> Add Line
                </button>
              </div>
            </div>

            <div class="mt-6 sticky bottom-0 bg-white border-t border-gray-200 p-4 -mx-6 -mb-6 z-10">
              <div class="flex justify-between items-center mb-4">
                <div class="flex gap-8">
                  <div>
                    <span class="text-sm font-medium text-gray-700">Total Debit:</span>
                    <span class="ml-2 text-lg font-semibold">{{ formatCurrency(totalDebit) }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-700">Total Credit:</span>
                    <span class="ml-2 text-lg font-semibold">{{ formatCurrency(totalCredit) }}</span>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-700">Difference:</span>
                    <span
                      :class="[
                        'ml-2 text-lg font-semibold',
                        isBalanced ? 'text-green-600' : 'text-red-600'
                      ]"
                    >
                      {{ formatCurrency(Math.abs(totalDebit - totalCredit)) }}
                    </span>
                  </div>
                  <div>
                    <span
                      :class="[
                        'px-3 py-1 rounded-full text-sm font-semibold',
                        isBalanced ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                      ]"
                    >
                      {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="flex justify-end space-x-3">
                <SecondaryButton type="button" @click="$inertia.visit('/journal-entries')">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :processing="form.processing">
                  Save Draft
                </PrimaryButton>
              </div>
            </div>
          </form>
    </Card>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Layout from '../../Shared/Layout.vue';
import Card from '../../Shared/Components/Card.vue';
import SectionHeader from '../../Shared/Components/SectionHeader.vue';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import SecondaryButton from '../../Shared/Components/SecondaryButton.vue';
import TextInput from '../../Shared/Components/TextInput.vue';
import DateInput from '../../Shared/Components/DateInput.vue';
import SearchSelect from '../../Shared/Components/SearchSelect.vue';

defineOptions({
    layout: Layout,
});

defineProps({
  sources: Array,
  accounts: Array,
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  entry_date: new Date().toISOString().split('T')[0],
  reference: '',
  description: '',
  source: null,
  lines: [
    { account_id: null, narration: '', debit: 0, credit: 0 },
    { account_id: null, narration: '', debit: 0, credit: 0 },
  ],
});

const totalDebit = computed(() => {
  return form.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
});

const totalCredit = computed(() => {
  return form.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
});

const isBalanced = computed(() => {
  return Math.abs(totalDebit.value - totalCredit.value) < 0.01;
});

const onDebitChange = (index) => {
  if (form.lines[index].debit > 0) {
    form.lines[index].credit = 0;
  }
  checkAndAddLine(index);
};

const onCreditChange = (index) => {
  if (form.lines[index].credit > 0) {
    form.lines[index].debit = 0;
  }
  checkAndAddLine(index);
};

const checkAndAddLine = (index) => {
  if (index === form.lines.length - 1) {
    const lastLine = form.lines[form.lines.length - 1];
    if (lastLine.account_id && (lastLine.debit > 0 || lastLine.credit > 0)) {
      addLine();
    }
  }
};

const addLine = () => {
  form.lines.push({ account_id: null, narration: '', debit: 0, credit: 0 });
};

const removeLine = (index) => {
  if (form.lines.length > 1) {
    form.lines.splice(index, 1);
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0);
};

const submit = () => {
  form.post('/journal-entries', {
    preserveScroll: true,
  });
};
</script>
