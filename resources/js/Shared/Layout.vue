<template>
    <div class="min-h-screen bg-gray-50">
        <NavBar :auth="auth" class="no-print" />
        
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import NavBar from './NavBar.vue';

defineProps({
    auth: {
        type: Object,
        default: null,
    },
});
</script>

<style>
@media print {
    .no-print,
    nav,
    header,
    .no-print * {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
    }

    main {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    body,
    .bg-gray-50,
    .min-h-screen {
        background: white !important;
    }
}
</style>
