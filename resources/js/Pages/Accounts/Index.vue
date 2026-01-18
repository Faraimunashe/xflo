<template>
    <Head title="Accounts" />
    <div class="mb-6 flex justify-between items-center">
        <SectionHeader title="Accounts" />
        <PrimaryButton @click="$inertia.visit('/accounts/create')">
            <i class="fas fa-plus mr-2"></i> New Account
        </PrimaryButton>
    </div>

    <Card>
          <div class="mb-4 flex gap-4">
            <TextInput
              v-model="form.search"
              placeholder="Search by code or name..."
              @update:model-value="search"
              className="flex-1"
            />
            <select
              v-model="form.type"
              @change="search"
              class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
            >
              <option value="">All Types</option>
              <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
            <select
              v-model="form.is_active"
              @change="search"
              class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm px-3 py-2 bg-white"
            >
              <option :value="null">All Status</option>
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Normal Balance</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cash</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="account in accounts.data" :key="account.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ account.code }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ account.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ account.type }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ account.normal_balance }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span v-if="account.is_cash" class="text-green-600"><i class="fas fa-check"></i></span>
                    <span v-else class="text-gray-400"><i class="fas fa-times"></i></span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="[
                        account.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800',
                        'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
                      ]"
                    >
                      {{ account.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <Link :href="`/accounts/${account.id}/edit`" class="text-blue-600 hover:text-blue-900 mr-4">
                      <i class="fas fa-edit"></i>
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="accounts.data.length === 0" class="text-center py-8 text-gray-500">
            No accounts found.
          </div>

          <div v-if="accounts.links && accounts.links.length > 3" class="mt-4 flex justify-center">
            <div v-for="link in accounts.links" :key="link.label">
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

defineOptions({
    layout: Layout,
});

defineProps({
  accounts: Object,
  accountTypes: Array,
});

const form = reactive({
  search: '',
  type: '',
  is_active: null,
});

const search = () => {
  router.get('/accounts', form, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>
