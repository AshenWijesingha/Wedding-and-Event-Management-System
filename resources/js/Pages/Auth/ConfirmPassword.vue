<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => form.post('/confirm-password', { onFinish: () => form.reset() });
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <p class="text-[0.68rem] font-medium uppercase tracking-[0.3em] text-[#c8a96a]">Security check</p>
        <h2 class="font-display mt-2 mb-4 text-3xl font-light text-[#1a1512]">Confirm your password</h2>
        <p class="mb-6 text-sm text-[#5c5246]">
            This is a secure area. Please confirm your password before continuing.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Password" :required="true" />
                <TextInput v-model="form.password" type="password" class="mt-1 block w-full" autofocus autocomplete="current-password" />
                <InputError :message="form.errors.password" />
            </div>

            <button type="submit" :disabled="form.processing"
                class="inline-flex w-full items-center justify-center rounded-full py-3 text-sm font-semibold text-white shadow-lg transition-transform duration-300 hover:-translate-y-0.5 disabled:opacity-70"
                style="background-color: var(--color-primary);">
                {{ form.processing ? 'Confirming…' : 'Confirm' }}
            </button>
        </form>
    </GuestLayout>
</template>
