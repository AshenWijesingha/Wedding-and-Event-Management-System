<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ email: '' });
const submit = () => form.post('/forgot-password');
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <h2 class="text-2xl font-bold text-ink mb-2">Reset password</h2>
        <p class="text-sm text-ink-muted mb-6">Enter your email and we'll send a reset link.</p>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">{{ status }}</div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Email address" :required="true" />
                <TextInput v-model="form.email" type="email" class="mt-1 block w-full" autofocus />
                <InputError :message="form.errors.email" />
            </div>

            <PrimaryButton class="w-full justify-center py-2.5" :disabled="form.processing">
                {{ form.processing ? 'Sending…' : 'Send reset link' }}
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
