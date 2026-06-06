<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ token: String, email: String });

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => form.post('/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

        <h2 class="text-2xl font-bold text-gray-900 mb-6">Set new password</h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Email address" :required="true" />
                <TextInput v-model="form.email" type="email" class="mt-1 block w-full" autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel value="New password" :required="true" />
                <TextInput v-model="form.password" type="password" class="mt-1 block w-full" autofocus autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <InputLabel value="Confirm new password" :required="true" />
                <TextInput v-model="form.password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full justify-center py-2.5" :disabled="form.processing">
                {{ form.processing ? 'Resetting…' : 'Reset password' }}
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
