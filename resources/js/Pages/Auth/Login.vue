<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post('/login', { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout>
        <Head title="Sign In" />

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">{{ status }}</div>

        <h2 class="text-2xl font-bold text-gray-900 mb-6">Sign in to EventPro</h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Email address" :required="true" />
                <TextInput v-model="form.email" type="email" class="mt-1 block w-full" autofocus autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel value="Password" :required="true" />
                <TextInput v-model="form.password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
                <Link v-if="canResetPassword" href="/forgot-password" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Forgot password?
                </Link>
            </div>

            <PrimaryButton class="w-full justify-center py-2.5" :disabled="form.processing">
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </PrimaryButton>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            No account?
            <Link href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Register here</Link>
        </p>
    </GuestLayout>
</template>
