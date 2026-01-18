<template>
    <Head title="Login" />

    <div 
        class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 bg-gray-50"
    >
        <div class="max-w-md w-full">
            <Card class="shadow-2xl bg-white bg-opacity-95 backdrop-blur-sm">
                <div class="text-center mb-8">
                    <div class="mx-auto flex items-center justify-center mb-4">
                        <img :src="logoImage" alt="Xflo Accounting Logo" class="h-20 w-auto object-contain" />
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900">
                        Xflo Accounting
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        School Accounting System
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        Please sign in to your account
                    </p>
                </div>

                <form class="space-y-6" @submit.prevent="handle_submit">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                required
                                v-model="form.email"
                                :class="[
                                    'block w-full pl-10 pr-3 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                    errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300',
                                    'bg-white'
                                ]"
                                placeholder="admin@xflom.com"
                            />
                        </div>
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                v-model="form.password"
                                :class="[
                                    'block w-full pl-10 pr-3 py-2.5 border rounded-lg shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
                                    errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300',
                                    'bg-white'
                                ]"
                                placeholder="Enter your password"
                            />
                        </div>
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember-me"
                                name="remember-me"
                                type="checkbox"
                                v-model="form.remember"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <div>
                        <PrimaryButton
                            type="submit"
                            :processing="processing"
                            class="w-full justify-center py-2.5"
                        >
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Sign in
                        </PrimaryButton>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-center text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Secure login powered by Xflo Security
                    </p>
                </div>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '../../Shared/Components/PrimaryButton.vue';
import Card from '../../Shared/Components/Card.vue';

// Use images from public folder (accessible directly)
const logoImage = '/logo.png';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const processing = ref(false);

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});


const handle_submit = () => {
    processing.value = true;
    form.post('/login', {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

