<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () => form.post('/register', { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h2 class="text-2xl font-bold text-ink mb-6">Create your account</h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel value="Full name" :required="true" />
                <TextInput v-model="form.name" type="text" class="mt-1 block w-full" autofocus autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <InputLabel value="Email address" :required="true" />
                <TextInput v-model="form.email" type="email" class="mt-1 block w-full" autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div>
                <InputLabel value="Password" :required="true" />
                <TextInput v-model="form.password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <InputLabel value="Confirm password" :required="true" />
                <TextInput v-model="form.password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <PrimaryButton class="w-full justify-center py-2.5" :disabled="form.processing">
                {{ form.processing ? 'Creating account…' : 'Create account' }}
            </PrimaryButton>
        </form>

        <p class="mt-6 text-center text-sm text-ink-muted">
            Already have an account?
            <Link href="/login" class="font-medium text-primary hover:text-primary">Sign in</Link>
        </p>
    </GuestLayout>
</template>
