<template>
    <nav class="bg-gray-800 sticky top-0 z-[100]" style="isolation: isolate;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <Link href="/" class="text-xl font-bold text-white flex items-center">
                            <i class="fas fa-book mr-2"></i>
                            Xflo Accounting
                        </Link>
                    </div>
                    
                    <div class="hidden sm:ml-6 sm:flex sm:items-center sm:space-x-1">
                        <Link
                            href="/journal-entries"
                            :class="link_classes('/journal-entries', '/')"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium h-full"
                        >
                            <i class="fas fa-file-invoice-dollar mr-2"></i>
                            Journal Entries
                        </Link>
                        
                        <Link
                            href="/accounts"
                            :class="link_classes('/accounts')"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium h-full"
                        >
                            <i class="fas fa-chart-line mr-2"></i>
                            Accounts
                        </Link>
                        
                        <div class="relative group flex items-center h-full" ref="reportsDropdownRef">
                            <button
                                type="button"
                                :class="dropdown_active('/reports/ledger', '/reports/trial-balance', '/reports/income-statement', '/reports/balance-sheet', '/reports/cashflow')"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium h-full"
                                ref="reportsButtonRef"
                            >
                                <i class="fas fa-chart-bar mr-2"></i>
                                Reports
                                <i class="fas fa-chevron-down ml-1 text-xs"></i>
                            </button>
                            <div 
                                class="absolute left-0 top-full mt-1 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200" 
                                style="z-index: 99999; transform: translateZ(0);"
                            >
                                <div class="py-1">
                                    <Link
                                        href="/reports/ledger"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <i class="fas fa-list-alt mr-2"></i>
                                        Ledger
                                    </Link>
                                    <Link
                                        href="/reports/trial-balance"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <i class="fas fa-balance-scale mr-2"></i>
                                        Trial Balance
                                    </Link>
                                    <Link
                                        href="/reports/income-statement"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <i class="fas fa-chart-line mr-2"></i>
                                        Income Statement
                                    </Link>
                                    <Link
                                        href="/reports/balance-sheet"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <i class="fas fa-file-invoice mr-2"></i>
                                        Balance Sheet
                                    </Link>
                                    <Link
                                        href="/reports/cashflow"
                                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                    >
                                        <i class="fas fa-money-bill-wave mr-2"></i>
                                        Cash Flow
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-if="auth && auth.user" class="hidden sm:ml-6 sm:flex sm:items-center">
                    <div class="relative group flex items-center h-full">
                        <button
                            type="button"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium h-full text-gray-300 hover:text-white border-b-2 border-transparent hover:border-gray-300"
                        >
                            <i class="fas fa-user-circle mr-2 text-lg"></i>
                            <span class="max-w-[150px] truncate">{{ auth.user.name }}</span>
                            <i class="fas fa-chevron-down ml-2 text-xs"></i>
                        </button>
                        <div 
                            class="absolute right-0 top-full mt-1 w-64 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200" 
                            style="z-index: 99999; transform: translateZ(0);"
                        >
                            <div class="py-2">
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-user-circle text-3xl text-gray-400"></i>
                                        </div>
                                        <div class="ml-3 flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ auth.user.name }}
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">
                                                {{ auth.user.email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="py-1">
                                    <form @submit.prevent="handle_logout">
                                        <button
                                            type="submit"
                                            class="w-full text-left block px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                                        >
                                            <i class="fas fa-sign-out-alt mr-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1 px-4">
                <Link
                    href="/journal-entries"
                    :class="mobile_link_classes('/journal-entries', '/')"
                    class="block px-3 py-2 text-base font-medium"
                >
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Journal Entries
                </Link>
                <Link
                    href="/accounts"
                    :class="mobile_link_classes('/accounts')"
                    class="block px-3 py-2 text-base font-medium"
                >
                    <i class="fas fa-chart-line mr-2"></i>
                    Accounts
                </Link>
                <div class="px-3 py-2 text-base font-medium text-gray-300">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Reports
                </div>
                <div class="pl-8 space-y-1">
                    <Link
                        href="/reports/ledger"
                        class="block px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-md"
                    >
                        Ledger
                    </Link>
                    <Link
                        href="/reports/trial-balance"
                        class="block px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-md"
                    >
                        Trial Balance
                    </Link>
                    <Link
                        href="/reports/income-statement"
                        class="block px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-md"
                    >
                        Income Statement
                    </Link>
                    <Link
                        href="/reports/balance-sheet"
                        class="block px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-md"
                    >
                        Balance Sheet
                    </Link>
                    <Link
                        href="/reports/cashflow"
                        class="block px-3 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white rounded-md"
                    >
                        Cash Flow
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from '../helpers';

const props = defineProps({
    auth: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const current_url = computed(() => page.url);

const link_classes = (url, alt_url = null) => {
    const base = 'border-b-2';
    const active = 'border-blue-500 text-white';
    const inactive = 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white';
    
    const current_path = current_url.value.split('?')[0];
    const check_path = url.split('?')[0];
    const alt_path = alt_url ? alt_url.split('?')[0] : null;
    
    const is_active = current_path === check_path || current_path.startsWith(check_path + '/') || 
                     (alt_path && (current_path === alt_path || current_path.startsWith(alt_path + '/')));
    
    return is_active ? `${base} ${active}` : `${base} ${inactive}`;
};

const dropdown_active = (...urls) => {
    const current_path = current_url.value.split('?')[0];
    const is_active = urls.some(url => {
        const check_path = url.split('?')[0];
        return current_path === check_path || current_path.startsWith(check_path + '/');
    });
    
    const base = 'border-b-2';
    const active = 'border-blue-500 text-white';
    const inactive = 'border-transparent text-gray-300 hover:border-gray-300 hover:text-white';
    
    return is_active ? `${base} ${active}` : `${base} ${inactive}`;
};

const mobile_link_classes = (url, alt_url = null) => {
    const current_path = current_url.value.split('?')[0];
    const check_path = url.split('?')[0];
    const alt_path = alt_url ? alt_url.split('?')[0] : null;
    
    const is_active = current_path === check_path || current_path.startsWith(check_path + '/') || 
                     (alt_path && (current_path === alt_path || current_path.startsWith(alt_path + '/')));
    
    if (is_active) {
        return 'bg-gray-900 text-white';
    }
    return 'text-gray-300 hover:bg-gray-700 hover:text-white';
};

const handle_logout = () => {
    router.post(route('logout'));
};
</script>
